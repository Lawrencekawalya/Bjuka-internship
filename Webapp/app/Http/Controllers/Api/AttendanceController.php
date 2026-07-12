<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceStatus;
use App\Enums\InternStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovedNetwork;
use App\Models\Attendance;
use App\Models\DailyLearningLog;
use App\Models\Intern;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function today(Request $request): JsonResponse
    {
        $intern = $request->user()->intern;

        if (! $intern || $intern->status !== InternStatus::ACTIVE) {
            return response()->json([
                'message' => 'Active intern profile required.',
            ], 403);
        }

        $intern->loadMissing('batch');
        $attendance = $this->todayAttendance($intern->id);

        $batchProgressPercentage = $intern->batch?->progress_percentage ?? 0;

        return response()->json([
            'attendance' => $attendance ? $this->attendancePayload($attendance) : null,
            'can_check_in' => ! $attendance,
            'can_check_out' => $attendance && ! $attendance->check_out_server_time,
            'batch_progress_percentage' => $batchProgressPercentage,
            'attendance_summary' => $this->attendanceSummaryPayload($intern),
            'certificate_download_url' => $batchProgressPercentage >= 100
                ? $intern->certificate_url
                : null,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $intern = $request->user()->intern;

        if (! $intern || $intern->status !== InternStatus::ACTIVE) {
            return response()->json([
                'message' => 'Active intern profile required.',
            ], 403);
        }

        $attendances = Attendance::query()
            ->with('learningLog')
            ->where('intern_id', $intern->id)
            ->latest('date')
            ->latest('check_in_server_time')
            ->limit(60)
            ->get()
            ->map(fn (Attendance $attendance) => $this->attendancePayload($attendance))
            ->values();

        return response()->json([
            'attendances' => $attendances,
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_time' => ['nullable', 'date'],
            'wifi_ssid' => ['required', 'string', 'max:255'],
            'wifi_bssid' => ['nullable', 'string', 'max:255'],
        ]);

        $intern = $request->user()->intern;

        if (! $intern || $intern->status !== InternStatus::ACTIVE) {
            return response()->json([
                'message' => 'Active intern profile required.',
            ], 403);
        }

        if ($this->todayAttendance($intern->id)) {
            return response()->json([
                'message' => 'You have already checked in today.',
            ], 409);
        }

        $wifiSsid = $this->normalizeWifiValue($validated['wifi_ssid']);
        $wifiBssid = $this->normalizeWifiValue($validated['wifi_bssid'] ?? null);

        if (! $this->isApprovedNetwork($intern->batch_id, $wifiSsid)) {
            return response()->json([
                'message' => 'You must be connected to an approved office Wi-Fi network to check in.',
            ], 403);
        }

        $serverTime = now();
        $attendance = Attendance::create([
            'intern_id' => $intern->id,
            'date' => $serverTime->toDateString(),
            'check_in_device_time' => $this->deviceTime($validated),
            'check_in_server_time' => $serverTime,
            'status' => $this->statusForCheckIn($serverTime),
            'wifi_ssid' => $wifiSsid,
            'wifi_bssid' => $wifiBssid,
        ]);

        return response()->json([
            'message' => 'Checked in successfully.',
            'attendance' => $this->attendancePayload($attendance),
        ], 201);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_time' => ['nullable', 'date'],
            'wifi_ssid' => ['required', 'string', 'max:255'],
            'wifi_bssid' => ['nullable', 'string', 'max:255'],
            'activities' => ['required', 'string', 'max:1000'],
        ]);

        $activities = $this->validatedActivities($validated['activities']);

        $intern = $request->user()->intern;

        if (! $intern || $intern->status !== InternStatus::ACTIVE) {
            return response()->json([
                'message' => 'Active intern profile required.',
            ], 403);
        }

        $attendance = $this->todayAttendance($intern->id);

        if (! $attendance) {
            return response()->json([
                'message' => 'You need to check in before checking out.',
            ], 409);
        }

        if ($attendance->check_out_server_time) {
            return response()->json([
                'message' => 'You have already checked out today.',
            ], 409);
        }

        $wifiSsid = $this->normalizeWifiValue($validated['wifi_ssid']);
        $wifiBssid = $this->normalizeWifiValue($validated['wifi_bssid'] ?? null);

        if (! $this->isApprovedNetwork($intern->batch_id, $wifiSsid)) {
            return response()->json([
                'message' => 'You must be connected to an approved office Wi-Fi network to check out.',
            ], 403);
        }

        $serverTime = now();
        $attendance->update([
            'check_out_device_time' => $this->deviceTime($validated),
            'check_out_server_time' => $serverTime,
            'work_duration_minutes' => $attendance->check_in_server_time->diffInMinutes($serverTime),
        ]);

        DailyLearningLog::updateOrCreate(
            ['attendance_id' => $attendance->id],
            [
                'studied_content' => $activities,
                'tasks_completed' => $activities,
            ],
        );

        return response()->json([
            'message' => 'Checked out successfully.',
            'attendance' => $this->attendancePayload($attendance->fresh('learningLog')),
        ]);
    }

    private function todayAttendance(string $internId): ?Attendance
    {
        return Attendance::where('intern_id', $internId)
            ->with('learningLog')
            ->whereDate('date', today())
            ->first();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function deviceTime(array $validated): ?CarbonInterface
    {
        return isset($validated['device_time'])
            ? Carbon::parse($validated['device_time'])
            : null;
    }

    private function statusForCheckIn(CarbonInterface $serverTime): AttendanceStatus
    {
        return $serverTime->greaterThan($serverTime->copy()->setTime(9, 30))
            ? AttendanceStatus::LATE
            : AttendanceStatus::PRESENT;
    }

    private function isApprovedNetwork(string $batchId, ?string $ssid): bool
    {
        if ($ssid === null) {
            return false;
        }

        return ApprovedNetwork::query()
            ->where('batch_id', $batchId)
            ->whereRaw('lower(trim(ssid)) = ?', [strtolower($ssid)])
            ->exists();
    }

    private function normalizeWifiValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cleaned = trim($value, " \t\n\r\0\x0B\"");

        return $cleaned === '' ? null : $cleaned;
    }

    private function validatedActivities(string $activities): string
    {
        $cleaned = trim(preg_replace('/\s+/', ' ', $activities) ?? '');

        if ($cleaned === '') {
            throw ValidationException::withMessages([
                'activities' => 'Tell us what you worked on today before checking out.',
            ]);
        }

        if (str_word_count($cleaned) > 70) {
            throw ValidationException::withMessages([
                'activities' => 'Activities must not be more than 70 words.',
            ]);
        }

        return $cleaned;
    }

    /**
     * @return array<string, mixed>
     */
    private function attendancePayload(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'date' => $attendance->date?->toDateString(),
            'check_in_device_time' => $attendance->check_in_device_time?->toIso8601String(),
            'check_in_server_time' => $attendance->check_in_server_time?->toIso8601String(),
            'check_out_device_time' => $attendance->check_out_device_time?->toIso8601String(),
            'check_out_server_time' => $attendance->check_out_server_time?->toIso8601String(),
            'work_duration_minutes' => $attendance->work_duration_minutes,
            'status' => $attendance->status->value,
            'wifi_ssid' => $attendance->wifi_ssid,
            'wifi_bssid' => $attendance->wifi_bssid,
            'daily_activities' => $attendance->learningLog?->tasks_completed,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function attendanceSummaryPayload(Intern $intern): array
    {
        $intern->loadMissing('batch');

        $expectedDays = max((int) ($intern->batch?->expected_working_days ?? 0), 0);
        $daysAttended = Attendance::query()
            ->where('intern_id', $intern->id)
            ->whereIn('status', [
                AttendanceStatus::PRESENT->value,
                AttendanceStatus::LATE->value,
                AttendanceStatus::PARTIAL->value,
            ])
            ->count();
        $attendanceRate = $expectedDays > 0
            ? (int) round(($daysAttended / $expectedDays) * 100)
            : 0;

        return [
            'days_attended' => $daysAttended,
            'expected_days' => $expectedDays,
            'attendance_rate' => min($attendanceRate, 100),
            'remaining_days' => max($expectedDays - $daysAttended, 0),
        ];
    }
}
