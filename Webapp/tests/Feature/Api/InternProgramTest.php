<?php

namespace Tests\Feature\Api;

use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_intern_can_view_batch_program_schedule(): void
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $batch = InternshipBatch::factory()->create([
            'batch_code' => 'INT-2026-JUNE',
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-24',
        ]);
        Intern::factory()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'status' => InternStatus::ACTIVE,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/intern/program')
            ->assertOk()
            ->assertJsonPath('program.batch.batch_code', 'INT-2026-JUNE')
            ->assertJsonPath('program.weeks.0.week_number', 1)
            ->assertJsonPath('program.weeks.0.title', 'Hardware Repair & Maintenance Fundamentals')
            ->assertJsonPath('program.weeks.4.title', 'Backend Development & Introduction to Flutter')
            ->assertJsonPath('program.weeks.5.title', 'CCTV Camera Installation & Final Project');
    }

    public function test_non_intern_cannot_view_intern_program_schedule(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/intern/program')
            ->assertForbidden();
    }
}
