<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/task')->assertRedirect('/login');
    }

    public function test_creator_can_fully_update_their_task()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
            'name' => 'Ancien nom',
        ]);

        $this->actingAs($creator)->put("/task/{$task->id}", [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description',
            'status' => 'en cours',
            'priority' => 'haute',
            'assigned_user_id' => $assignee->id,
            'project_id' => $project->id,
        ])->assertRedirect(route('task.index'));

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'name' => 'Nouveau nom', 'priority' => 'haute']);
    }

    public function test_assigned_user_can_only_update_the_status()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
            'name' => 'Nom original',
            'priority' => 'basse',
        ]);

        $this->actingAs($assignee)->put("/task/{$task->id}", [
            'name' => 'Tentative de changement de nom',
            'priority' => 'haute',
            'status' => 'terminé',
        ])->assertRedirect(route('task.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Nom original',
            'priority' => 'basse',
            'status' => 'terminé',
        ]);
    }

    public function test_uninvolved_user_cannot_update_a_task()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
        ]);

        $this->actingAs($stranger)->put("/task/{$task->id}", [
            'status' => 'terminé',
        ])->assertForbidden();
    }

    public function test_assigned_user_cannot_delete_a_task()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
        ]);

        $this->actingAs($assignee)->delete("/task/{$task->id}")->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_creator_can_change_only_the_status_via_the_kanban_endpoint()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
            'name' => 'Nom inchangé',
        ]);

        $this->actingAs($creator)
            ->patch("/task/{$task->id}/status", ['status' => 'en cours'])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'name' => 'Nom inchangé', 'status' => 'en cours']);
    }

    public function test_assigned_user_can_change_the_status_via_the_kanban_endpoint()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
        ]);

        $this->actingAs($assignee)
            ->patch("/task/{$task->id}/status", ['status' => 'terminé'])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'terminé']);
    }

    public function test_uninvolved_user_cannot_change_the_status_via_the_kanban_endpoint()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $assignee->id,
        ]);

        $this->actingAs($stranger)
            ->patch("/task/{$task->id}/status", ['status' => 'terminé'])
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => $task->status]);
    }
}
