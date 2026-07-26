<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
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

        $this->post("/task/{$task->id}/attachments", [])->assertRedirect('/login');
    }

    public function test_a_project_member_can_upload_an_attachment()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;

        $response = $this->actingAs($member)->post("/task/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf'),
        ]);

        $response->assertSuccessful();

        $attachment = Attachment::firstOrFail();
        $this->assertSame('rapport.pdf', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_an_extremely_long_filename_is_truncated()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;
        $longName = str_repeat('a', 300).'.pdf';

        $this->actingAs($member)->post("/task/{$task->id}/attachments", [
            'file' => UploadedFile::fake()->create($longName, 500, 'application/pdf'),
        ])->assertSuccessful();

        $attachment = Attachment::firstOrFail();
        $this->assertLessThanOrEqual(255, mb_strlen($attachment->original_name));
    }

    public function test_a_non_member_cannot_upload_an_attachment()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->post("/task/{$task->id}/attachments", [
                'file' => UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_the_uploader_can_delete_their_attachment()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;
        $path = UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf')->store('attachments', 'local');
        $attachment = $task->attachments()->create([
            'user_id' => $member->id,
            'original_name' => 'rapport.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 500 * 1024,
        ]);

        $this->actingAs($member)->delete("/attachments/{$attachment->id}")->assertSuccessful();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_another_member_cannot_delete_someones_attachment()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $author = $task->assignedUser;
        $otherMember = User::factory()->create();
        $task->project->members()->attach($otherMember->id);

        $path = UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf')->store('attachments', 'local');
        $attachment = $task->attachments()->create([
            'user_id' => $author->id,
            'original_name' => 'rapport.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 500 * 1024,
        ]);

        $this->actingAs($otherMember)->delete("/attachments/{$attachment->id}")->assertForbidden();

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }

    public function test_the_project_owner_can_delete_another_members_attachment()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $owner = $task->creator;
        $member = User::factory()->create();
        $task->project->members()->attach($member->id);

        $path = UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf')->store('attachments', 'local');
        $attachment = $task->attachments()->create([
            'user_id' => $member->id,
            'original_name' => 'rapport.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 500 * 1024,
        ]);

        $this->actingAs($owner)->delete("/attachments/{$attachment->id}")->assertSuccessful();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_a_disallowed_file_type_is_rejected()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;

        $this->actingAs($member)
            ->post("/task/{$task->id}/attachments", [
                'file' => UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_a_harmless_file_disguised_with_a_dangerous_extension_is_rejected()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;

        // Contenu réel = simple texte (sniffé "text/plain"), mais nommé .exe —
        // la validation ne doit pas se fier uniquement au contenu détecté.
        $this->actingAs($member)
            ->post("/task/{$task->id}/attachments", [
                'file' => UploadedFile::fake()->createWithContent('malware.exe', 'contenu inoffensif'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_a_file_too_large_is_rejected()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;

        $this->actingAs($member)
            ->post("/task/{$task->id}/attachments", [
                'file' => UploadedFile::fake()->create('gros-fichier.pdf', 3000, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_the_eleventh_attachment_on_a_task_is_rejected()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;

        for ($i = 0; $i < 10; $i++) {
            $path = UploadedFile::fake()->create("fichier{$i}.pdf", 100, 'application/pdf')->store('attachments', 'local');
            $task->attachments()->create([
                'user_id' => $member->id,
                'original_name' => "fichier{$i}.pdf",
                'path' => $path,
                'mime_type' => 'application/pdf',
                'size' => 100 * 1024,
            ]);
        }

        $this->actingAs($member)
            ->post("/task/{$task->id}/attachments", [
                'file' => UploadedFile::fake()->create('fichier-en-trop.pdf', 100, 'application/pdf'),
            ])
            ->assertStatus(422);

        $this->assertSame(10, $task->attachments()->count());
    }

    public function test_downloading_requires_project_membership()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;
        $stranger = User::factory()->create();

        $path = UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf')->store('attachments', 'local');
        $attachment = $task->attachments()->create([
            'user_id' => $member->id,
            'original_name' => 'rapport.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 500 * 1024,
        ]);

        $this->actingAs($stranger)->get("/attachments/{$attachment->id}/download")->assertForbidden();
        $this->actingAs($member)->get("/attachments/{$attachment->id}/download")->assertSuccessful();
    }

    public function test_deleting_a_task_removes_its_attachments()
    {
        Storage::fake('local');

        $task = $this->makeTask();
        $member = $task->assignedUser;

        $path = UploadedFile::fake()->create('rapport.pdf', 500, 'application/pdf')->store('attachments', 'local');
        $attachment = $task->attachments()->create([
            'user_id' => $member->id,
            'original_name' => 'rapport.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 500 * 1024,
        ]);

        $this->actingAs($task->creator)->delete("/task/{$task->id}")->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($path);
    }
}
