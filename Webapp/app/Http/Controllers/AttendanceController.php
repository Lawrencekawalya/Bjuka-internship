<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
        ]);

        $query = $this->visibleAttendanceQuery($request)
            ->with([
                'intern.user:id,name,email',
                'intern.batch:id,batch_code,name',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->whereHas('intern.user', function (Builder $query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('intern.batch', function (Builder $query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('batch_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('intern', function (Builder $query) use ($search) {
                            $query
                                ->where('institution', 'like', "%{$search}%")
                                ->orWhere('registration_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('date', $date))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('check_in_server_time');

        $todayQuery = $this->visibleAttendanceQuery($request)->whereDate('date', today());

        return Inertia::render('attendance/Index', [
            'attendances' => $query
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Attendance $attendance) => [
                    'id' => $attendance->id,
                    'date' => $attendance->date?->toDateString(),
                    'check_in_server_time' => $attendance->check_in_server_time?->toIso8601String(),
                    'check_out_server_time' => $attendance->check_out_server_time?->toIso8601String(),
                    'work_duration_minutes' => $attendance->work_duration_minutes,
                    'status' => $attendance->status->value,
                    'wifi_ssid' => $attendance->wifi_ssid,
                    'wifi_bssid' => $attendance->wifi_bssid,
                    'intern' => [
                        'id' => $attendance->intern?->id,
                        'name' => $attendance->intern?->user?->name,
                        'email' => $attendance->intern?->user?->email,
                        'institution' => $attendance->intern?->institution,
                        'registration_number' => $attendance->intern?->registration_number,
                        'batch' => $attendance->intern?->batch ? [
                            'id' => $attendance->intern->batch->id,
                            'batch_code' => $attendance->intern->batch->batch_code,
                            'name' => $attendance->intern->batch->name,
                        ] : null,
                    ],
                ]),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'date' => $filters['date'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'stats' => [
                'checked_in_today' => (clone $todayQuery)->count(),
                'still_checked_in' => (clone $todayQuery)->whereNull('check_out_server_time')->count(),
                'late_today' => (clone $todayQuery)->where('status', AttendanceStatus::LATE)->count(),
                'total_records' => $this->visibleAttendanceQuery($request)->count(),
            ],
            'statuses' => collect(AttendanceStatus::cases())->map(fn (AttendanceStatus $status) => [
                'label' => ucfirst($status->value),
                'value' => $status->value,
            ])->values(),
        ]);
    }

    private function visibleAttendanceQuery(Request $request): Builder
    {
        return Attendance::query()
            ->when($request->user()->role === UserRole::SUPERVISOR, function (Builder $query) use ($request) {
                $query->whereHas('intern.supervisorAssignments', function (Builder $query) use ($request) {
                    $query->where('supervisor_id', $request->user()->id);
                });
            });
    }
}
