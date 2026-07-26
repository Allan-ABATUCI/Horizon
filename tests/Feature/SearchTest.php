<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/search?q=test')->assertRedirect('/login');
    }

    public function test_a_short_query_returns_empty_results()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/search?q=a');

        $response->assertOk();
        $response->assertJson(['projects' => [], 'tasks' => []]);
    }

    public function test_a_member_finds_their_own_project_and_task_by_name()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'name' => 'Refonte du site vitrine',
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'assigned_user_id' => $user->id,
            'name' => 'Écrire la page vitrine',
        ]);

        $response = $this->actingAs($user)->get('/search?q=vitrine');

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue(collect($data['projects'])->pluck('id')->contains($project->id));
        $this->assertTrue(collect($data['tasks'])->pluck('id')->contains($task->id));
    }

    public function test_a_non_members_project_and_task_never_appear()
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create([
            'created_by' => $stranger->id,
            'updated_by' => $stranger->id,
            'name' => 'Refonte du site vitrine',
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $stranger->id,
            'updated_by' => $stranger->id,
            'assigned_user_id' => $stranger->id,
            'name' => 'Écrire la page vitrine',
        ]);

        $response = $this->actingAs($user)->get('/search?q=vitrine');

        $response->assertOk();
        $data = $response->json();

        $this->assertFalse(collect($data['projects'])->pluck('id')->contains($project->id));
        $this->assertFalse(collect($data['tasks'])->pluck('id')->contains($task->id));
    }
}
