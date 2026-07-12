<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\InternshipBatch;
use App\Models\User;
use App\Services\BatchWorkingHoursScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchWorkingHourTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_batch_working_hours(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $workingHours = app(BatchWorkingHoursScheduleService::class)->ensureDefaultSchedule($batch);

        $payload = $workingHours
            ->map(fn ($day) => [
                'id' => $day->id,
                'day_of_week' => $day->day_of_week,
                'is_working_day' => $day->day_of_week <= 4,
                'start_time' => $day->day_of_week <= 4 ? '09:00' : null,
                'end_time' => $day->day_of_week <= 4 ? '16:30' : null,
                'break_start_time' => $day->day_of_week <= 4 ? '12:30' : null,
                'break_end_time' => $day->day_of_week <= 4 ? '13:30' : null,
                'notes' => $day->day_of_week <= 4 ? 'Adjusted office day.' : 'Non-working day.',
            ])
            ->values()
            ->all();

        $this->actingAs($admin)
            ->patch(route('batches.working-hours.update', $batch), [
                'working_hours' => $payload,
            ])
            ->assertRedirect(route('batches.show', $batch));

        $this->assertDatabaseHas('batch_working_hours', [
            'batch_id' => $batch->id,
            'day_of_week' => 1,
            'is_working_day' => true,
            'start_time' => '09:00',
            'end_time' => '16:30',
            'break_start_time' => '12:30',
            'break_end_time' => '13:30',
            'notes' => 'Adjusted office day.',
        ]);

        $this->assertDatabaseHas('batch_working_hours', [
            'batch_id' => $batch->id,
            'day_of_week' => 5,
            'is_working_day' => false,
            'start_time' => null,
            'end_time' => null,
            'notes' => 'Non-working day.',
        ]);
    }

    public function test_non_admin_cannot_update_batch_working_hours(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $batch = InternshipBatch::factory()->create();

        $this->actingAs($hr)
            ->patch(route('batches.working-hours.update', $batch), [
                'working_hours' => [],
            ])
            ->assertForbidden();
    }
}
