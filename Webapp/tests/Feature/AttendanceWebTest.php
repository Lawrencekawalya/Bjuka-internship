<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Intern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_admin_can_import_attendance_csv(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $presentUser = User::factory()->create([
            'email' => 'present.intern@bjuka.com',
            'role' => UserRole::INTERN,
        ]);
        $lateUser = User::factory()->create([
            'email' => 'late.intern@bjuka.com',
            'role' => UserRole::INTERN,
        ]);

        Intern::factory()->create(['user_id' => $presentUser->id]);
        Intern::factory()->create(['user_id' => $lateUser->id]);

        $file = UploadedFile::fake()->createWithContent('attendance.csv', implode("\n", [
            'date,intern_email,check_in_time,check_out_time,wifi_ssid,wifi_bssid,activities',
            '2026-06-24,present.intern@bjuka.com,09:30,17:00,BJUKA_WIFI,00:11:22:33:44:55,Worked on Laravel tasks',
            '2026-06-24,late.intern@bjuka.com,09:31,17:05,BJUKA_WIFI,00:11:22:33:44:66,Worked on Flutter tasks',
        ]));

        $this->actingAs($admin)
            ->post(route('attendances.import'), [
                'attendance_file' => $file,
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseHas('attendances', [
            'intern_id' => $presentUser->intern->id,
            'date' => '2026-06-24 00:00:00',
            'status' => AttendanceStatus::PRESENT->value,
            'work_duration_minutes' => 450,
            'wifi_ssid' => 'BJUKA_WIFI',
        ]);

        $this->assertDatabaseHas('attendances', [
            'intern_id' => $lateUser->intern->id,
            'date' => '2026-06-24 00:00:00',
            'status' => AttendanceStatus::LATE->value,
            'work_duration_minutes' => 454,
        ]);

        $this->assertDatabaseHas('daily_learning_logs', [
            'tasks_completed' => 'Worked on Laravel tasks',
        ]);
    }

    public function test_import_skips_existing_intern_date_records(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $intern = Intern::factory()->create();

        Attendance::factory()->create([
            'intern_id' => $intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => '2026-06-24 08:45:00',
            'status' => AttendanceStatus::PRESENT,
        ]);

        $file = UploadedFile::fake()->createWithContent('attendance.csv', implode("\n", [
            'date,intern_email,check_in_time,check_out_time,wifi_ssid',
            "2026-06-24,{$intern->user->email},09:31,17:00,BJUKA_WIFI",
        ]));

        $this->actingAs($admin)
            ->post(route('attendances.import'), [
                'attendance_file' => $file,
            ])
            ->assertRedirect(route('attendances.index'));

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'intern_id' => $intern->id,
            'date' => '2026-06-24 00:00:00',
            'status' => AttendanceStatus::PRESENT->value,
        ]);
    }

    public function test_non_admin_cannot_import_attendance_csv(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $file = UploadedFile::fake()->createWithContent('attendance.csv', implode("\n", [
            'date,intern_email,check_in_time',
            '2026-06-24,intern@bjuka.com,08:30',
        ]));

        $this->actingAs($hr)
            ->post(route('attendances.import'), [
                'attendance_file' => $file,
            ])
            ->assertForbidden();
    }
}
