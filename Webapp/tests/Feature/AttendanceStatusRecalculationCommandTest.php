<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusRecalculationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_recalculates_existing_attendance_statuses_using_nine_thirty_threshold(): void
    {
        $nineEleven = Attendance::factory()->create([
            'check_in_server_time' => '2026-06-30 09:11:00',
            'status' => AttendanceStatus::LATE,
        ]);
        $nineNineteen = Attendance::factory()->create([
            'check_in_server_time' => '2026-06-30 09:19:00',
            'status' => AttendanceStatus::LATE,
        ]);
        $nineThirtyOne = Attendance::factory()->create([
            'check_in_server_time' => '2026-06-30 09:31:00',
            'status' => AttendanceStatus::LATE,
        ]);

        $this->artisan('attendance:recalculate-statuses')
            ->expectsOutput('Dry run: 2 attendance status(es) would be updated. Re-run with --commit to persist.')
            ->assertSuccessful();

        $this->assertSame(AttendanceStatus::LATE, $nineEleven->fresh()->status);

        $this->artisan('attendance:recalculate-statuses --commit')
            ->expectsOutput('Updated 2 attendance status(es).')
            ->assertSuccessful();

        $this->assertSame(AttendanceStatus::PRESENT, $nineEleven->fresh()->status);
        $this->assertSame(AttendanceStatus::PRESENT, $nineNineteen->fresh()->status);
        $this->assertSame(AttendanceStatus::LATE, $nineThirtyOne->fresh()->status);
    }
}
