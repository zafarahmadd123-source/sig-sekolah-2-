<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_authenticated_user_can_open_password_change_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('password.edit'))
            ->assertOk()
            ->assertSee('Ubah Kata Sandi');
    }

    public function test_user_can_change_password_after_entering_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
    }

    public function test_user_cannot_change_password_with_an_incorrect_current_password(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'incorrect',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');

        expect($user->fresh()->password)->toBe($oldPassword);
    }
}
