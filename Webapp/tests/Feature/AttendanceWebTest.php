<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Intern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_attendance_records(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $intern = Intern::factory()->create();

        Attendance::factory()->create([
            'intern_id' => $intern->id,
            'date' => today(),
            'check_in_server_time' => now()->setTime(8, 45),
            'status' => AttendanceStatus::PRESENT,
        ]);

        $response = $this->actingAs($admin)->get(route('attendances.index'));

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('attendance/Index')
                ->has('attendances.data', 1)
                ->where('attendances.data.0.intern.name', $intern->user->name)
                ->where('stats.checked_in_today', 1)
            );
    }

    public function test_attendance_records_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        Attendance::factory()->create(['status' => AttendanceStatus::PRESENT]);
        Attendance::factory()->create(['status' => AttendanceStatus::LATE]);

        $response = $this->actingAs($admin)->get(route('attendances.index', [
            'status' => AttendanceStatus::LATE->value,
        ]));

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('attendances.data', 1)
                ->where('attendances.data.0.status', AttendanceStatus::LATE->value)
            );
    }

    public function test_interns_cannot_view_attendance_register(): void
    {
        $intern = User::factory()->create(['role' => UserRole::INTERN]);

        $this
            ->actingAs($intern)
            ->get(route('attendances.index'))
            ->assertForbidden();
    }
}
