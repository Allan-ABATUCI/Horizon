<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/project')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_list_projects()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/project')->assertOk();
    }

    public function test_authenticated_user_can_create_a_project()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/project', [
            'name' => 'Nouveau projet',
            'description' => 'Une description',
            'status' => 'en attente',
        ])->assertRedirect(route('project.index'));

        $this->assertDatabaseHas('projects', [
            'name' => 'Nouveau projet',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function test_creator_can_update_their_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);

        $this->actingAs($user)->put("/project/{$project->id}", [
            'name' => 'Projet modifié',
            'status' => 'en cours',
        ])->assertRedirect(route('project.index'));

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Projet modifié']);
    }

    public function test_non_creator_cannot_update_a_project()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);

        $this->actingAs($other)->put("/project/{$project->id}", [
            'name' => 'Tentative',
            'status' => 'en cours',
        ])->assertForbidden();
    }

    public function test_non_creator_cannot_delete_a_project()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id, 'updated_by' => $owner->id]);

        $this->actingAs($other)->delete("/project/{$project->id}")->assertForbidden();
        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_creating_a_project_with_an_image_stores_it_on_the_public_disk()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/project', [
            'name' => 'Projet avec image',
            'status' => 'en attente',
            'image' => UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(route('project.index'));

        $project = Project::where('name', 'Projet avec image')->firstOrFail();
        $this->assertNotNull($project->image_path);
        Storage::disk('public')->assertExists($project->image_path);
    }

    public function test_deleting_a_project_removes_its_image_from_disk()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $path = UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg')->store('projects', 'public');
        $project = Project::factory()->create([
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'image_path' => $path,
        ]);

        $this->actingAs($user)->delete("/project/{$project->id}")->assertRedirect(route('project.index'));

        Storage::disk('public')->assertMissing($path);
    }
}
