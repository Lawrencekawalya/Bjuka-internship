<?php

namespace Tests\Feature\Api;

use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\BatchWorkingHour;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternWorkingHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_intern_can_view_batch_working_hours(): void
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $batch = InternshipBatch::factory()->create([
            'batch_code' => 'INT-2026-JUNE',
        ]);
        Intern::factory()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'status' => InternStatus::ACTIVE,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/intern/working-hours')
            ->assertOk()
            ->assertJsonPath('working_hours.batch.batch_code', 'INT-2026-JUNE')
            ->assertJsonPath('working_hours.days.0.day_name', 'Monday')
            ->assertJsonPath('working_hours.days.0.is_working_day', true)
            ->assertJsonPath('working_hours.days.0.start_time', '08:00')
            ->assertJsonPath('working_hours.days.0.end_time', '17:00')
            ->assertJsonPath('working_hours.days.5.day_name', 'Saturday')
            ->assertJsonPath('working_hours.days.5.is_working_day', false)
            ->assertJsonCount(7, 'working_hours.days');
    }

    public function test_existing_batch_working_hours_are_returned_without_overwrite(): void
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $batch = InternshipBatch::factory()->create();
        Intern::factory()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'status' => InternStatus::ACTIVE,
        ]);
        BatchWorkingHour::create([
            'batch_id' => $batch->id,
            'day_of_week' => 1,
            'is_working_day' => true,
            'start_time' => '09:00',
            'end_time' => '16:00',
            'break_start_time' => '12:30',
            'break_end_time' => '13:30',
            'notes' => 'Adjusted Monday schedule.',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/intern/working-hours')
            ->assertOk()
            ->assertJsonCount(1, 'working_hours.days')
            ->assertJsonPath('working_hours.days.0.start_time', '09:00')
            ->assertJsonPath('working_hours.days.0.notes', 'Adjusted Monday schedule.');
    }

    public function test_non_intern_cannot_view_working_hours(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/intern/working-hours')
            ->assertForbidden();
    }
}
