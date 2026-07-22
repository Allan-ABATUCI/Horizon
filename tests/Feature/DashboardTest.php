<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertOk();
    }

    public function test_dashboard_only_lists_tasks_created_by_or_assigned_to_the_user()
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $stranger->id, 'updated_by' => $stranger->id]);

        $createdByMe = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'assigned_user_id' => $stranger->id,
        ]);
        $assignedToMe = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $stranger->id,
            'updated_by' => $stranger->id,
            'assigned_user_id' => $user->id,
        ]);
        $unrelated = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $stranger->id,
            'updated_by' => $stranger->id,
            'assigned_user_id' => $stranger->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $page = json_decode(json_encode($response->viewData('page')), true);
        $taskIds = collect($page['props']['tasks']['data'])->pluck('id');

        $this->assertTrue($taskIds->contains($createdByMe->id));
        $this->assertTrue($taskIds->contains($assignedToMe->id));
        $this->assertFalse($taskIds->contains($unrelated->id));
    }

    public function test_scope_all_lists_tasks_from_every_user()
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $stranger->id, 'updated_by' => $stranger->id]);

        $unrelated = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $stranger->id,
            'updated_by' => $stranger->id,
            'assigned_user_id' => $stranger->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard?scope=all');

        $response->assertOk();
        $page = json_decode(json_encode($response->viewData('page')), true);
        $taskIds = collect($page['props']['tasks']['data'])->pluck('id');

        $this->assertTrue($taskIds->contains($unrelated->id));
    }

    public function test_project_id_filter_restricts_tasks_to_that_project()
    {
        $user = User::factory()->create();
        $projectA = Project::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);
        $projectB = Project::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);

        $taskInA = Task::factory()->create([
            'project_id' => $projectA->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'assigned_user_id' => $user->id,
        ]);
        $taskInB = Task::factory()->create([
            'project_id' => $projectB->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'assigned_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get("/dashboard?scope=all&project_id={$projectA->id}");

        $response->assertOk();
        $page = json_decode(json_encode($response->viewData('page')), true);
        $taskIds = collect($page['props']['tasks']['data'])->pluck('id');

        $this->assertTrue($taskIds->contains($taskInA->id));
        $this->assertFalse($taskIds->contains($taskInB->id));
    }
}
