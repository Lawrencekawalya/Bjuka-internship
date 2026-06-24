<?php

namespace Tests\Feature\Api;

use App\Enums\AttendanceStatus;
use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\ApprovedNetwork;
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
        $this->approvedNetworkFor($user);

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

    public function test_intern_cannot_check_in_without_wifi_details(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['wifi_ssid']);
    }

    public function test_intern_can_check_in_when_device_does_not_expose_bssid(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
            ])
            ->assertCreated()
            ->assertJsonPath('attendance.wifi_ssid', 'BJUKA_WIFI');
    }

    public function test_intern_can_check_in_from_approved_ssid_with_different_access_point_bssid(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
                'wifi_bssid' => '66:77:88:99:AA:BB',
            ])
            ->assertCreated()
            ->assertJsonPath('attendance.wifi_bssid', '66:77:88:99:AA:BB');
    }

    public function test_intern_can_check_in_when_device_wraps_approved_ssid_in_quotes(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
                'wifi_ssid' => '"BJUKA_WIFI"',
                'wifi_bssid' => '00:11:22:33:44:55',
            ])
            ->assertCreated()
            ->assertJsonPath('attendance.wifi_ssid', 'BJUKA_WIFI');
    }

    public function test_intern_can_check_in_when_batch_has_canonical_bjuka_wifi_network(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();

        ApprovedNetwork::factory()->create([
            'batch_id' => $user->intern->batch_id,
            'ssid' => 'OLD_OFFICE_WIFI',
            'bssid' => '00:11:22:33:44:55',
        ]);

        ApprovedNetwork::factory()->create([
            'batch_id' => $user->intern->batch_id,
            'ssid' => 'BJUKA_WIFI',
            'bssid' => 'any',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
                'wifi_bssid' => 'AA:BB:CC:DD:EE:FF',
            ])
            ->assertCreated()
            ->assertJsonPath('attendance.wifi_ssid', 'BJUKA_WIFI');
    }

    public function test_intern_cannot_check_in_from_unapproved_wifi(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
                'wifi_ssid' => 'HOME_WIFI',
                'wifi_bssid' => '66:77:88:99:AA:BB',
                'activities' => 'Reviewed tickets and completed the assigned Laravel API fixes.',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'You must be connected to an approved office Wi-Fi network to check in.');
    }

    public function test_intern_cannot_check_in_twice_on_same_day(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-in', [
                'device_time' => '2026-06-24T08:29:30+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
                'wifi_bssid' => '00:11:22:33:44:55',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'You have already checked in today.');
    }

    public function test_intern_can_check_out_after_checking_in(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        $attendance = Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        Carbon::setTestNow('2026-06-24 17:00:00');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-out', [
                'device_time' => '2026-06-24T16:59:00+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
                'wifi_bssid' => '00:11:22:33:44:55',
                'activities' => 'Reviewed tickets and completed the assigned Laravel API fixes.',
            ]);

        $response->assertOk()
            ->assertJsonPath('attendance.work_duration_minutes', 510)
            ->assertJsonPath('attendance.daily_activities', 'Reviewed tickets and completed the assigned Laravel API fixes.');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_duration_minutes' => 510,
        ]);

        $this->assertDatabaseHas('daily_learning_logs', [
            'attendance_id' => $attendance->id,
            'tasks_completed' => 'Reviewed tickets and completed the assigned Laravel API fixes.',
        ]);
    }

    public function test_intern_cannot_check_out_without_wifi_details(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        Carbon::setTestNow('2026-06-24 17:00:00');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-out', [
                'device_time' => '2026-06-24T16:59:00+03:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['wifi_ssid']);
    }

    public function test_intern_cannot_check_out_without_activities(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        Carbon::setTestNow('2026-06-24 17:00:00');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-out', [
                'device_time' => '2026-06-24T16:59:00+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activities']);
    }

    public function test_intern_cannot_check_out_with_more_than_seventy_activity_words(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        Carbon::setTestNow('2026-06-24 17:00:00');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-out', [
                'device_time' => '2026-06-24T16:59:00+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
                'activities' => implode(' ', array_fill(0, 71, 'task')),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['activities']);
    }

    public function test_intern_can_check_out_when_device_does_not_expose_bssid(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        Carbon::setTestNow('2026-06-24 17:00:00');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-out', [
                'device_time' => '2026-06-24T16:59:00+03:00',
                'wifi_ssid' => 'BJUKA_WIFI',
                'activities' => 'Reviewed tickets and completed the assigned Laravel API fixes.',
            ])
            ->assertOk()
            ->assertJsonPath('attendance.work_duration_minutes', 510);
    }

    public function test_intern_cannot_check_out_from_unapproved_wifi(): void
    {
        Carbon::setTestNow('2026-06-24 08:30:00');
        $user = $this->activeInternUser();
        $this->approvedNetworkFor($user);

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => now(),
        ]);

        Carbon::setTestNow('2026-06-24 17:00:00');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/attendance/check-out', [
                'device_time' => '2026-06-24T16:59:00+03:00',
                'wifi_ssid' => 'HOME_WIFI',
                'wifi_bssid' => '66:77:88:99:AA:BB',
                'activities' => 'Reviewed tickets and completed the assigned Laravel API fixes.',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'You must be connected to an approved office Wi-Fi network to check out.');
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

    public function test_intern_can_view_only_their_attendance_history(): void
    {
        $user = $this->activeInternUser();
        $otherUser = $this->activeInternUser();

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-23',
            'check_in_server_time' => '2026-06-23 08:30:00',
            'check_out_server_time' => '2026-06-23 17:00:00',
        ]);

        Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-06-24',
            'check_in_server_time' => '2026-06-24 08:40:00',
            'check_out_server_time' => '2026-06-24 17:10:00',
        ]);

        Attendance::factory()->create([
            'intern_id' => $otherUser->intern->id,
            'date' => '2026-06-25',
            'check_in_server_time' => '2026-06-25 08:30:00',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/attendance/history')
            ->assertOk()
            ->assertJsonCount(2, 'attendances')
            ->assertJsonPath('attendances.0.date', '2026-06-24')
            ->assertJsonPath('attendances.1.date', '2026-06-23');
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

    private function approvedNetworkFor(User $user): ApprovedNetwork
    {
        return ApprovedNetwork::factory()->create([
            'batch_id' => $user->intern->batch_id,
            'ssid' => 'BJUKA_WIFI',
            'bssid' => '00:11:22:33:44:55',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
