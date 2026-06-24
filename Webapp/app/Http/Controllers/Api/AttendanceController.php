<?php

namespace App\Http\Controllers\Api;

use App\Enums\AttendanceStatus;
use App\Enums\InternStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ApprovedNetwork;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $attendance = $this->todayAttendance($intern->id);

        return response()->json([
            'attendance' => $attendance ? $this->attendancePayload($attendance) : null,
            'can_check_in' => ! $attendance,
            'can_check_out' => $attendance && ! $attendance->check_out_server_time,
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

        if (! $this->isApprovedNetwork($intern->batch_id, $validated['wifi_ssid'], $validated['wifi_bssid'] ?? null)) {
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
            'wifi_ssid' => $validated['wifi_ssid'] ?? null,
            'wifi_bssid' => $validated['wifi_bssid'] ?? null,
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
        ]);

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

        if (! $this->isApprovedNetwork($intern->batch_id, $validated['wifi_ssid'], $validated['wifi_bssid'] ?? null)) {
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

        return response()->json([
            'message' => 'Checked out successfully.',
            'attendance' => $this->attendancePayload($attendance->fresh()),
        ]);
    }

    private function todayAttendance(string $internId): ?Attendance
    {
        return Attendance::where('intern_id', $internId)
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
        return $serverTime->greaterThan($serverTime->copy()->setTime(9, 0))
            ? AttendanceStatus::LATE
            : AttendanceStatus::PRESENT;
    }

    private function isApprovedNetwork(string $batchId, string $ssid, ?string $bssid): bool
    {
        $query = ApprovedNetwork::query()
            ->where('batch_id', $batchId)
            ->where('ssid', $ssid);

        if ($bssid) {
            $query->whereRaw('lower(bssid) = ?', [strtolower($bssid)]);
        }

        return $query->exists();
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
        ];
    }
}
