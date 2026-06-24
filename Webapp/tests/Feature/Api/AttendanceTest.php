<?php

namespace Tests\Feature\Api;

use App\Enums\AttendanceStatus;
use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Intern;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_intern_can_check_in(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
                'wifi_bssid' => '00:11:22:33:44:55',
            ]);

        $response->assertCreated()
            ->assertJsonPath('attendance.status', AttendanceStatus::PRESENT->value)
            ->assertJsonPath('attendance.wifi_ssid', 'BJUKA_WIFI');

        $this->assertDatabaseHas('attendances', [
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24 00:00:00',
            'status' => AttendanceStatus::PRESENT->value,
        ]);
    }

    public function test_intern_cannot_check_in_twice_on_same_day(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in')
            ->assertStatus(409)
            ->assertJsonPath('message', 'You have already checked in today.');
    }

    public function test_intern_can_check_out_after_checking_in(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();

        $attendance = Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        Carbon::setTestNow('2026-06-24 17:00:00');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-out', [
                'device_time' => '2026-06-24T16:59:00+03:00',
            ]);

        $response->assertOk()
            ->assertJsonPath('attendance.work_duration_minutes', 510);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_duration_minutes' => 510,
        ]);
    }

    public function test_today_endpoint_returns_current_attendance_state(): void
    {
        Carbon::setTestNow('2026-06-24 10:00:00');
        $user = $this->activeInternUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/attendance/today')
            ->assertOk()
            ->assertJsonPath('attendance', null)
            ->assertJsonPath('can_check_in', true)
            ->assertJsonPath('can_check_out', false);
    }

    private function activeInternUser(): User
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);

        Intern::factory()->create([
            'user_id' => $user->id,
            'status' => InternStatus::ACTIVE,
        ]);

        return $user->load('intern');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
