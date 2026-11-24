<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'role' => Role::Dinas,
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_deactivate_another_user(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $operator = User::factory()->create(['role' => Role::Dinas]);

        $this->actingAs($admin)->put(route('admin.users.update', $operator), [
            'name' => $operator->name,
            'email' => $operator->email,
            'role' => Role::Dinas->value,
            'is_active' => false,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $operator->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => Role::Admin->value,
            'is_active' => false,
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('is_active');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }
}
