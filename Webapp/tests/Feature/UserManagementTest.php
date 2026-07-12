<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_user_management_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        User::factory()->create([
            'name' => 'Lead Supervisor',
            'role' => UserRole::SUPERVISOR,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('users/Index')
                ->has('users', 2)
                ->has('roles', 5));
    }

    public function test_admin_can_create_staff_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'HR Officer',
                'email' => 'hr@bjuka.io',
                'role' => UserRole::HR->value,
                'password' => 'password123',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'hr@bjuka.io')->firstOrFail();

        $this->assertSame(UserRole::HR, $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_admin_can_update_staff_user_and_reset_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::SUPERVISOR]);

        $this->actingAs($admin)
            ->patch(route('users.update', $user), [
                'name' => 'Operations Manager',
                'email' => 'manager@bjuka.io',
                'role' => UserRole::MANAGER->value,
                'password' => 'new-password123',
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertSame('Operations Manager', $user->name);
        $this->assertSame('manager@bjuka.io', $user->email);
        $this->assertSame(UserRole::MANAGER, $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('new-password123', $user->password));
    }

    public function test_admin_can_delete_staff_user_but_not_self(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::SUPERVISOR]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_management_does_not_create_intern_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Intern User',
                'email' => 'intern@bjuka.io',
                'role' => UserRole::INTERN->value,
                'password' => 'password123',
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'intern@bjuka.io']);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);

        $this->actingAs($hr)
            ->get(route('users.index'))
            ->assertForbidden();
    }
}
