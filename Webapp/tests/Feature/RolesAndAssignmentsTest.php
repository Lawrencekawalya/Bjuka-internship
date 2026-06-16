<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\InternSupervisorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RolesAndAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])->get('/test-admin', function () {
            return 'admin';
        });

        Route::middleware(['web', 'auth', 'role:supervisor'])->get('/test-supervisor', function () {
            return 'supervisor';
        });

        Route::middleware(['web', 'auth', 'role:intern'])->get('/test-intern', function () {
            return 'intern';
        });
    }

    public function test_admin_can_access_admin_routes()
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin)
            ->get('/test-admin')
            ->assertStatus(200);
    }

    public function test_intern_cannot_access_admin_routes()
    {
        $intern = User::factory()->create(['role' => UserRole::INTERN]);

        $this->actingAs($intern)
            ->get('/test-admin')
            ->assertStatus(403);
    }

    public function test_supervisor_can_be_assigned_to_intern()
    {
        $supervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $intern = Intern::factory()->create();

        $assignment = InternSupervisorAssignment::create([
            'intern_id' => $intern->id,
            'supervisor_id' => $supervisor->id,
            'assigned_at' => now(),
        ]);

        $this->assertCount(1, $supervisor->managedInterns);
        $this->assertEquals($intern->id, $supervisor->managedInterns->first()->id);
        $this->assertCount(1, $intern->supervisors);
        $this->assertEquals($supervisor->id, $intern->supervisors->first()->id);
    }

    public function test_user_has_default_intern_role()
    {
        $user = User::factory()->create();
        $this->assertEquals(UserRole::INTERN, $user->role);
    }
}
