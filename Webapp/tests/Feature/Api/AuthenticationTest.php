<?php

namespace Tests\Feature\Api;

use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_intern_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'intern@bjuka.io',
            'password' => bcrypt('password'),
            'role' => UserRole::INTERN,
            'profile_photo_path' => 'profile-photos/intern.jpg',
            'must_change_password' => true,
        ]);
        Intern::factory()->create(['user_id' => $user->id]);

        $response = $this->postJson('/api/login', [
            'email' => 'intern@bjuka.io',
            'password' => 'password',
            'device_name' => 'Mobile Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.avatar', url('/storage/profile-photos/intern.jpg'))
            ->assertJsonPath('user.must_change_password', true);
    }

    public function test_non_intern_cannot_login_to_api()
    {
        $user = User::factory()->create([
            'email' => 'admin@bjuka.io',
            'password' => bcrypt('password'),
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@bjuka.io',
            'password' => 'password',
            'device_name' => 'Mobile Test Device',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized. Only interns can log in to the mobile app.']);
    }

    public function test_user_can_get_authenticated_user_profile()
    {
        $user = User::factory()->create([
            'role' => UserRole::INTERN,
            'profile_photo_path' => 'profile-photos/intern.jpg',
        ]);
        $token = $user->createToken('test', ['mobile-access'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.avatar', url('/storage/profile-photos/intern.jpg'));
    }

    public function test_intern_can_change_temporary_password(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::INTERN,
            'must_change_password' => true,
        ]);
        Intern::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('test', ['mobile-access'])->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/password/change', [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertOk()
            ->assertJsonPath('user.must_change_password', false);

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_inactive_intern_cannot_login()
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $intern = Intern::factory()->create([
            'user_id' => $user->id,
            'status' => InternStatus::INACTIVE,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Mobile Test Device',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Account is deactivated. Please contact your supervisor.']);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $this->assertCount(0, $user->tokens);
    }
}
