<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_own_account()
    {
        $user = User::factory()->create(['name' => 'Ancien nom']);

        $this->actingAs($user)->put("/user/{$user->id}", [
            'name' => 'Nouveau nom',
            'email' => $user->email,
        ])->assertRedirect(route('user.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nouveau nom']);
    }

    public function test_user_cannot_update_another_users_account()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->put("/user/{$other->id}", [
            'name' => 'Piraté',
            'email' => $other->email,
        ])->assertForbidden();
    }

    public function test_user_cannot_delete_their_account_if_they_created_projects()
    {
        $user = User::factory()->create();
        Project::factory()->create(['created_by' => $user->id, 'updated_by' => $user->id]);

        $this->actingAs($user)->delete("/user/{$user->id}")->assertRedirect(route('user.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_user_can_delete_their_account_if_unlinked()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete("/user/{$user->id}")->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
