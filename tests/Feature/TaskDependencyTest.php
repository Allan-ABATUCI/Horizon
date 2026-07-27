<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDependencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_creator_can_add_a_dependency_within_the_same_project()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $dependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);

        $this->actingAs($creator)
            ->postJson("/task/{$task->id}/dependencies", ['depends_on_id' => $dependsOn->id])
            ->assertOk();

        $this->assertTrue($task->dependsOn()->where('depends_on_id', $dependsOn->id)->exists());
    }

    public function test_a_non_creator_member_cannot_add_a_dependency()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $project->members()->attach($assignee->id);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $assignee->id]);
        $dependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);

        $this->actingAs($assignee)
            ->postJson("/task/{$task->id}/dependencies", ['depends_on_id' => $dependsOn->id])
            ->assertForbidden();

        $this->assertFalse($task->dependsOn()->where('depends_on_id', $dependsOn->id)->exists());
    }

    public function test_a_task_cannot_depend_on_itself()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);

        $this->actingAs($creator)
            ->postJson("/task/{$task->id}/dependencies", ['depends_on_id' => $task->id])
            ->assertStatus(422);
    }

    public function test_a_task_cannot_depend_on_a_task_from_another_project()
    {
        $creator = User::factory()->create();
        $projectA = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $projectB = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create(['project_id' => $projectA->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $otherProjectTask = Task::factory()->create(['project_id' => $projectB->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);

        $this->actingAs($creator)
            ->postJson("/task/{$task->id}/dependencies", ['depends_on_id' => $otherProjectTask->id])
            ->assertStatus(422);
    }

    public function test_a_duplicate_dependency_is_rejected()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $dependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $task->dependsOn()->attach($dependsOn->id);

        $this->actingAs($creator)
            ->postJson("/task/{$task->id}/dependencies", ['depends_on_id' => $dependsOn->id])
            ->assertStatus(422);
    }

    public function test_a_circular_dependency_is_rejected()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $taskA = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $taskB = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        // A dépend de B.
        $taskA->dependsOn()->attach($taskB->id);

        // B ne peut pas dépendre de A (créerait un cycle direct).
        $this->actingAs($creator)
            ->postJson("/task/{$taskB->id}/dependencies", ['depends_on_id' => $taskA->id])
            ->assertStatus(422);
    }

    public function test_a_transitive_circular_dependency_is_rejected()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $taskA = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $taskB = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $taskC = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        // A dépend de B, B dépend de C.
        $taskA->dependsOn()->attach($taskB->id);
        $taskB->dependsOn()->attach($taskC->id);

        // C ne peut pas dépendre de A (créerait un cycle A -> B -> C -> A).
        $this->actingAs($creator)
            ->postJson("/task/{$taskC->id}/dependencies", ['depends_on_id' => $taskA->id])
            ->assertStatus(422);
    }

    public function test_the_creator_can_remove_a_dependency()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $dependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $task->dependsOn()->attach($dependsOn->id);

        $this->actingAs($creator)
            ->deleteJson("/task/{$task->id}/dependencies/{$dependsOn->id}")
            ->assertNoContent();

        $this->assertFalse($task->dependsOn()->where('depends_on_id', $dependsOn->id)->exists());
    }

    public function test_a_non_creator_member_cannot_remove_a_dependency()
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $project->members()->attach($assignee->id);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $assignee->id]);
        $dependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $task->dependsOn()->attach($dependsOn->id);

        $this->actingAs($assignee)
            ->deleteJson("/task/{$task->id}/dependencies/{$dependsOn->id}")
            ->assertForbidden();

        $this->assertTrue($task->dependsOn()->where('depends_on_id', $dependsOn->id)->exists());
    }

    public function test_a_task_cannot_be_marked_done_while_a_dependency_is_unresolved()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'assigned_user_id' => $creator->id,
            'status' => 'en cours',
        ]);
        $dependsOn = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'assigned_user_id' => $creator->id,
            'status' => 'en cours',
        ]);
        $task->dependsOn()->attach($dependsOn->id);

        $this->actingAs($creator)
            ->patchJson("/task/{$task->id}/status", ['status' => 'terminé'])
            ->assertStatus(422);

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'en cours']);
    }

    public function test_a_task_can_be_marked_done_once_its_dependency_is_resolved()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'assigned_user_id' => $creator->id,
            'status' => 'en cours',
        ]);
        $dependsOn = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'assigned_user_id' => $creator->id,
            'status' => 'terminé',
        ]);
        $task->dependsOn()->attach($dependsOn->id);

        $this->actingAs($creator)
            ->patch("/task/{$task->id}/status", ['status' => 'terminé'])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'terminé']);
    }

    public function test_deleting_a_task_removes_its_dependency_rows()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $dependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $task->dependsOn()->attach($dependsOn->id);

        $task->delete();

        $this->assertDatabaseMissing('task_dependencies', ['task_id' => $task->id]);
    }

    public function test_moving_a_task_to_another_project_purges_dependencies_that_become_cross_project()
    {
        $creator = User::factory()->create();
        $projectA = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $projectB = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);

        $task = Task::factory()->create(['project_id' => $projectA->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $dependsOn = Task::factory()->create(['project_id' => $projectA->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $dependent = Task::factory()->create(['project_id' => $projectA->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);

        // $task dépend de $dependsOn, et $dependent dépend de $task.
        $task->dependsOn()->attach($dependsOn->id);
        $dependent->dependsOn()->attach($task->id);

        $this->actingAs($creator)->put("/task/{$task->id}", [
            'name' => $task->name,
            'description' => $task->description,
            'status' => 'en attente',
            'priority' => 'moyenne',
            'assigned_user_id' => $creator->id,
            'project_id' => $projectB->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('task_dependencies', ['task_id' => $task->id, 'depends_on_id' => $dependsOn->id]);
        $this->assertDatabaseMissing('task_dependencies', ['task_id' => $dependent->id, 'depends_on_id' => $task->id]);
    }

    public function test_updating_a_task_without_changing_its_project_keeps_its_dependencies()
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);

        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $dependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $task->dependsOn()->attach($dependsOn->id);

        $this->actingAs($creator)->put("/task/{$task->id}", [
            'name' => 'Nom mis à jour',
            'description' => $task->description,
            'status' => 'en cours',
            'priority' => 'haute',
            'assigned_user_id' => $creator->id,
            'project_id' => $project->id,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('task_dependencies', ['task_id' => $task->id, 'depends_on_id' => $dependsOn->id]);
    }

    public function test_candidates_excludes_self_and_already_added_dependencies_and_requires_membership()
    {
        $creator = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $alreadyDependsOn = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $candidate = Task::factory()->create(['project_id' => $project->id, 'created_by' => $creator->id, 'assigned_user_id' => $creator->id]);
        $task->dependsOn()->attach($alreadyDependsOn->id);

        $response = $this->actingAs($creator)->getJson("/task/{$task->id}/dependencies/candidates");
        $ids = collect($response->json())->pluck('id');

        $this->assertFalse($ids->contains($task->id));
        $this->assertFalse($ids->contains($alreadyDependsOn->id));
        $this->assertTrue($ids->contains($candidate->id));

        $this->actingAs($stranger)->getJson("/task/{$task->id}/dependencies/candidates")->assertForbidden();
    }
}
