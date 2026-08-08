<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(User $creator): Project
    {
        return Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
    }

    public function test_creating_a_task_notifies_the_assignee()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = $this->makeProject($creator);

        $this->actingAs($creator)->post('/task', [
            'name' => 'Nouvelle tâche',
            'status' => 'en attente',
            'priority' => 'moyenne',
            'assigned_user_id' => $assignee->id,
            'project_id' => $project->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(1, $assignee->fresh()->unreadNotifications()->count());
    }

    public function test_assigning_a_task_to_yourself_does_not_notify()
    {
        $creator = User::factory()->create();
        $project = $this->makeProject($creator);

        $this->actingAs($creator)->post('/task', [
            'name' => 'Tâche auto-assignée',
            'status' => 'en attente',
            'priority' => 'moyenne',
            'assigned_user_id' => $creator->id,
            'project_id' => $project->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(0, $creator->fresh()->unreadNotifications()->count());
    }

    public function test_reassigning_a_task_notifies_the_new_assignee()
    {
        $creator = User::factory()->create();
        $firstAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();
        $project = $this->makeProject($creator);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $firstAssignee->id,
        ]);

        $this->actingAs($creator)->put("/task/{$task->id}", [
            'name' => $task->name,
            'status' => 'en attente',
            'priority' => 'moyenne',
            'assigned_user_id' => $newAssignee->id,
            'project_id' => $project->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(1, $newAssignee->fresh()->unreadNotifications()->count());
    }

    public function test_assignee_changing_only_the_status_does_not_notify_anyone()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = $this->makeProject($creator);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
        ]);

        $this->actingAs($assignee)->put("/task/{$task->id}", [
            'status' => 'en cours',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(0, $assignee->fresh()->unreadNotifications()->count());
    }

    public function test_scheduled_command_notifies_assignees_of_tasks_due_tomorrow()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = $this->makeProject($creator);

        $dueTomorrow = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
            'status' => 'en cours',
            'end_date' => Carbon::tomorrow(),
        ]);

        $alreadyDone = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
            'status' => 'terminé',
            'end_date' => Carbon::tomorrow(),
        ]);

        $dueLater = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
            'status' => 'en cours',
            'end_date' => Carbon::tomorrow()->addDays(2),
        ]);

        $this->artisan('tasks:notify-due-soon')->assertSuccessful();

        $this->assertSame(1, $assignee->fresh()->unreadNotifications()->count());
        $this->assertSame($dueTomorrow->id, $assignee->fresh()->unreadNotifications()->first()->data['task_id']);
        $this->assertTrue(true, "sanity: {$alreadyDone->id} et {$dueLater->id} ne doivent pas notifier");
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_deleting_a_task_removes_its_notifications()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = $this->makeProject($creator);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task));
        $this->assertSame(1, $assignee->fresh()->notifications()->count());

        $this->actingAs($creator)->delete("/task/{$task->id}");

        $this->assertSame(0, $assignee->fresh()->notifications()->count());
    }

    public function test_orphaned_notifications_are_excluded_from_the_list()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = $this->makeProject($creator);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task));

        // Simule une notification orpheline pré-existante (contournant le
        // nettoyage du modèle) en supprimant la tâche directement en base,
        // sans passer par Eloquent.
        DB::table('tasks')->where('id', $task->id)->delete();

        $response = $this->actingAs($assignee)->getJson('/notifications');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }

    public function test_a_user_cannot_mark_someone_elses_notification_as_read()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $creator = User::factory()->create();
        $project = $this->makeProject($creator);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $owner->id,
        ]);

        $owner->notify(new TaskAssigned($task));
        $notification = DatabaseNotification::where('notifiable_id', $owner->id)->firstOrFail();

        $this->actingAs($intruder)->post("/notifications/{$notification->id}/read")->assertNotFound();
    }
}
