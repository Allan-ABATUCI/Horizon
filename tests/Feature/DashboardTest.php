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
}
