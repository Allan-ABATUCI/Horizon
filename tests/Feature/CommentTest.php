<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
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

        $this->post("/task/{$task->id}/comments", ['body' => 'Un commentaire'])->assertRedirect('/login');
    }

    public function test_a_project_member_can_comment_on_a_task()
    {
        $task = $this->makeTask();
        $commenter = $task->assignedUser; // membre du projet via l'assignation

        $this->actingAs($commenter)
            ->post("/task/{$task->id}/comments", ['body' => 'Un commentaire'])
            ->assertSuccessful();

        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'body' => 'Un commentaire',
        ]);
    }

    public function test_a_non_member_cannot_comment_on_a_task()
    {
        $task = $this->makeTask();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post("/task/{$task->id}/comments", ['body' => 'Un commentaire'])
            ->assertForbidden();
    }

    public function test_author_can_delete_their_own_comment()
    {
        $task = $this->makeTask();
        $author = User::factory()->create();
        $comment = Comment::create(['task_id' => $task->id, 'user_id' => $author->id, 'body' => 'À supprimer']);

        $this->actingAs($author)->delete("/comments/{$comment->id}")->assertSuccessful();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_other_user_cannot_delete_someones_comment()
    {
        $task = $this->makeTask();
        $author = User::factory()->create();
        $other = User::factory()->create();
        $comment = Comment::create(['task_id' => $task->id, 'user_id' => $author->id, 'body' => 'Protégé']);

        $this->actingAs($other)->delete("/comments/{$comment->id}")->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_the_project_owner_can_delete_another_members_comment()
    {
        $task = $this->makeTask();
        $owner = $task->creator; // makeTask() : le créateur du projet est aussi celui de la tâche
        $author = User::factory()->create();
        $task->project->members()->attach($author->id);
        $comment = Comment::create(['task_id' => $task->id, 'user_id' => $author->id, 'body' => 'À modérer']);

        $this->actingAs($owner)->delete("/comments/{$comment->id}")->assertSuccessful();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_deleting_a_task_cascades_to_its_comments()
    {
        $task = $this->makeTask();
        $author = User::factory()->create();
        $comment = Comment::create(['task_id' => $task->id, 'user_id' => $author->id, 'body' => 'Lié à la tâche']);

        $task->delete();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
