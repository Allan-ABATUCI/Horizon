<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistItemTest extends TestCase
{
    use RefreshDatabase;

    private function makeTask(): Task
    {
        $creator = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $creator->id, 'updated_by' => $creator->id]);

        return Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
            'assigned_user_id' => $creator->id,
        ]);
    }

    public function test_guests_are_redirected_to_the_login_page()
    {
        $task = $this->makeTask();

        $this->post("/task/{$task->id}/checklist-items", ['label' => 'Un élément'])->assertRedirect('/login');
    }

    public function test_a_project_member_can_add_a_checklist_item()
    {
        $task = $this->makeTask();
        $member = $task->assignedUser; // membre du projet via l'assignation

        $this->actingAs($member)
            ->postJson("/task/{$task->id}/checklist-items", ['label' => 'Un élément'])
            ->assertSuccessful();

        $this->assertDatabaseHas('checklist_items', [
            'task_id' => $task->id,
            'label' => 'Un élément',
            'is_done' => false,
        ]);
    }

    public function test_a_non_member_cannot_add_a_checklist_item()
    {
        $task = $this->makeTask();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->postJson("/task/{$task->id}/checklist-items", ['label' => 'Un élément'])
            ->assertForbidden();
    }

    public function test_a_project_member_can_toggle_an_item_they_did_not_create()
    {
        $task = $this->makeTask();
        $author = User::factory()->create();
        $task->project->members()->attach($author->id);
        $otherMember = User::factory()->create();
        $task->project->members()->attach($otherMember->id);
        $item = ChecklistItem::create(['task_id' => $task->id, 'label' => 'À cocher', 'is_done' => false]);

        $this->actingAs($otherMember)
            ->patchJson("/checklist-items/{$item->id}", ['is_done' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('checklist_items', ['id' => $item->id, 'is_done' => true]);
    }

    public function test_a_non_member_cannot_toggle_an_item()
    {
        $task = $this->makeTask();
        $stranger = User::factory()->create();
        $item = ChecklistItem::create(['task_id' => $task->id, 'label' => 'Protégé', 'is_done' => false]);

        $this->actingAs($stranger)
            ->patchJson("/checklist-items/{$item->id}", ['is_done' => true])
            ->assertForbidden();

        $this->assertDatabaseHas('checklist_items', ['id' => $item->id, 'is_done' => false]);
    }

    public function test_a_project_member_can_delete_an_item_they_did_not_create()
    {
        $task = $this->makeTask();
        $member = $task->assignedUser;
        $item = ChecklistItem::create(['task_id' => $task->id, 'label' => 'À supprimer', 'is_done' => false]);

        $this->actingAs($member)->delete("/checklist-items/{$item->id}")->assertSuccessful();

        $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
    }

    public function test_a_non_member_cannot_delete_an_item()
    {
        $task = $this->makeTask();
        $stranger = User::factory()->create();
        $item = ChecklistItem::create(['task_id' => $task->id, 'label' => 'Protégé', 'is_done' => false]);

        $this->actingAs($stranger)->delete("/checklist-items/{$item->id}")->assertForbidden();

        $this->assertDatabaseHas('checklist_items', ['id' => $item->id]);
    }

    public function test_deleting_a_task_cascades_to_its_checklist_items()
    {
        $task = $this->makeTask();
        $item = ChecklistItem::create(['task_id' => $task->id, 'label' => 'Lié à la tâche', 'is_done' => false]);

        $task->delete();

        $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
    }

    public function test_an_oversized_label_is_rejected()
    {
        $task = $this->makeTask();
        $member = $task->assignedUser;

        $this->actingAs($member)
            ->postJson("/task/{$task->id}/checklist-items", ['label' => str_repeat('a', 300)])
            ->assertStatus(422);
    }
}
