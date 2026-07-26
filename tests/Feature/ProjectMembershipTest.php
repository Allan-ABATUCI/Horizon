<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_project_adds_the_creator_as_a_member()
    {
        $creator = User::factory()->create();

        $this->actingAs($creator)->post('/project', [
            'name' => 'Nouveau projet',
            'status' => 'en attente',
        ]);

        $project = Project::where('name', 'Nouveau projet')->firstOrFail();

        $this->assertTrue($project->members()->where('user_id', $creator->id)->exists());
    }

    public function test_a_non_member_cannot_view_a_project()
    {
        $creator = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);

        $this->actingAs($stranger)->get("/project/{$project->id}")->assertForbidden();
    }

    public function test_a_member_can_view_a_project()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);

        $this->actingAs($creator)->get("/project/{$project->id}")->assertRedirect(route('project.index'));
    }

    public function test_the_owner_can_add_a_member()
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);

        $this->actingAs($owner)
            ->post("/project/{$project->id}/members", ['user_id' => $invitee->id])
            ->assertRedirect(route('project.index'));

        $this->assertTrue($project->members()->where('user_id', $invitee->id)->exists());
        $this->actingAs($invitee)->get("/project/{$project->id}")->assertRedirect(route('project.index'));
    }

    public function test_the_owner_can_remove_a_non_owner_member()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);
        $project->members()->attach($member->id);

        $this->actingAs($owner)
            ->delete("/project/{$project->id}/members/{$member->id}")
            ->assertRedirect(route('project.index'));

        $this->assertFalse($project->members()->where('user_id', $member->id)->exists());
    }

    public function test_the_owner_cannot_remove_themselves()
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);

        $this->actingAs($owner)->delete("/project/{$project->id}/members/{$owner->id}");

        $this->assertTrue($project->members()->where('user_id', $owner->id)->exists());
    }

    public function test_a_non_owner_member_cannot_add_or_remove_members()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $someoneElse = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);
        $project->members()->attach($member->id);

        $this->actingAs($member)
            ->post("/project/{$project->id}/members", ['user_id' => $someoneElse->id])
            ->assertForbidden();

        $this->actingAs($member)
            ->delete("/project/{$project->id}/members/{$owner->id}")
            ->assertForbidden();
    }

    public function test_assigning_a_task_adds_the_assignee_as_a_project_member()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);

        $this->actingAs($creator)->post('/task', [
            'name' => 'Nouvelle tâche',
            'status' => 'en attente',
            'priority' => 'moyenne',
            'assigned_user_id' => $assignee->id,
            'project_id' => $project->id,
        ]);

        $this->assertTrue($project->members()->where('user_id', $assignee->id)->exists());
    }

    public function test_a_non_owner_member_cannot_assign_a_task_to_a_non_member()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);
        $project->members()->attach($member->id);

        $this->actingAs($member)
            ->post('/task', [
                'name' => 'Tâche suspecte',
                'status' => 'en attente',
                'priority' => 'moyenne',
                'assigned_user_id' => $stranger->id,
                'project_id' => $project->id,
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertFalse($project->members()->where('user_id', $stranger->id)->exists());
        $this->assertDatabaseMissing('tasks', ['name' => 'Tâche suspecte']);
    }

    public function test_a_non_owner_member_can_assign_a_task_to_an_existing_member()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $otherMember = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);
        $project->members()->attach([$member->id, $otherMember->id]);

        $this->actingAs($member)->post('/task', [
            'name' => 'Tâche légitime',
            'status' => 'en attente',
            'priority' => 'moyenne',
            'assigned_user_id' => $otherMember->id,
            'project_id' => $project->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tasks', ['name' => 'Tâche légitime', 'assigned_user_id' => $otherMember->id]);
    }

    public function test_scope_all_shows_tasks_from_a_project_the_user_only_belongs_to()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);
        $project->members()->attach($member->id);

        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'updated_by' => $owner->id,
            'assigned_user_id' => $owner->id,
        ]);

        $response = $this->actingAs($member)->get('/dashboard?scope=all');

        $response->assertOk();
        $page = json_decode(json_encode($response->viewData('page')), true);
        $taskIds = collect($page['props']['tasks']['data'])->pluck('id');

        $this->assertTrue($taskIds->contains($task->id));
    }
}
