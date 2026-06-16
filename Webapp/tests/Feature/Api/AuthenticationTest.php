<?php

namespace Tests\Feature\Api;

use App\Enums\UserRole;
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
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'intern@bjuka.io',
            'password' => 'password',
            'device_name' => 'Mobile Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
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
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $token = $user->createToken('test', ['mobile-access'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_inactive_intern_cannot_login()
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $intern = \App\Models\Intern::factory()->create([
            'user_id' => $user->id,
            'status' => \App\Enums\InternStatus::INACTIVE,
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
