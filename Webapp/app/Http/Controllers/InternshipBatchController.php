<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\BatchStatus;
use App\Enums\InternStatus;
use App\Models\ApprovedNetwork;
use App\Models\Attendance;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class InternshipBatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $batches = InternshipBatch::query()
            ->withCount('interns')
            ->with('coordinator:id,name')
            ->latest()
            ->paginate(10);

        return Inertia::render('batches/Index', [
            'batches' => $batches,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('batches/Create', [
            'coordinators' => User::where('role', '!=', 'intern')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_code' => ['required', 'string', 'max:255', 'unique:internship_batches,batch_code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'capacity' => ['required', 'integer', 'min:1'],
            'expected_working_days' => ['required', 'integer', 'min:1'],
            'status' => ['required', new Enum(BatchStatus::class)],
            'coordinator_id' => ['nullable', 'exists:users,id'],
        ]);

        $batch = InternshipBatch::create($validated);

        ApprovedNetwork::create([
            'batch_id' => $batch->id,
            'name' => $batch->name.' Office WiFi',
            'ssid' => 'BJUKA_WIFI',
            'bssid' => 'any',
        ]);

        return redirect()->route('batches.index')
            ->with('success', 'Batch created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(InternshipBatch $batch)
    {
        $batch->load([
            'coordinator:id,name',
            'interns.user:id,name,email,profile_photo_path,must_change_password',
            'interns.reportGenerationQuota',
            'approvedNetworks',
        ]);

        $activeInterns = $batch->interns()->where('status', InternStatus::ACTIVE)->count();
        $elapsedWorkingDays = $this->elapsedWorkingDays($batch);
        $expectedAttendanceRecords = $activeInterns * $elapsedWorkingDays;
        $actualAttendanceRecords = Attendance::query()
            ->whereHas('intern', fn ($query) => $query->where('batch_id', $batch->id))
            ->where('status', '!=', AttendanceStatus::ABSENT->value)
            ->when($batch->start_date, fn ($query) => $query->whereDate('date', '>=', $batch->start_date))
            ->when($batch->end_date, fn ($query) => $query->whereDate('date', '<=', $this->attendanceRateEndDate($batch)))
            ->count();

        $batchAttendances = Attendance::query()
            ->with([
                'intern.user:id,name,email',
                'intern.batch:id,batch_code,name',
            ])
            ->whereHas('intern', fn ($query) => $query->where('batch_id', $batch->id))
            ->orderByDesc('date')
            ->orderByDesc('check_in_server_time')
            ->limit(50)
            ->get()
            ->map(fn (Attendance $attendance) => [
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
            ])
            ->values();

        $stats = [
            'total_interns' => $batch->interns()->count(),
            'present_today' => $batch->interns()->whereHas('attendances', function ($query) {
                $query->whereDate('date', now()->toDateString());
            })->count(),
            'attendance_rate' => $expectedAttendanceRecords > 0
                ? round(($actualAttendanceRecords / $expectedAttendanceRecords) * 100)
                : 0,
            'total_supervisors' => $batch->interns()->with('supervisors')->get()->pluck('supervisors')->flatten()->unique('id')->count(),
            'progress' => $batch->progress_percentage,
        ];

        return Inertia::render('batches/Show', [
            'batch' => $batch,
            'stats' => $stats,
            'batch_attendances' => $batchAttendances,
            'batch_performance' => $this->batchPerformance($batch, $elapsedWorkingDays, $activeInterns),
        ]);
    }

    private function batchPerformance(InternshipBatch $batch, int $elapsedWorkingDays, int $activeInterns): array
    {
        $start = $batch->start_date?->copy()->startOfDay();
        $end = $batch->end_date ? $this->attendanceRateEndDate($batch) : null;

        if (! $start || ! $end || $end->isBefore($start)) {
            return [
                'overview' => [
                    'elapsed_working_days' => 0,
                    'expected_records' => 0,
                    'actual_records' => 0,
                    'missing_records' => 0,
                    'average_hours_per_attendance' => 0,
                    'at_risk_interns' => 0,
                ],
                'daily_attendance' => [],
                'status_distribution' => [],
                'intern_performance' => [],
            ];
        }

        $activeInternsCollection = $batch->interns()
            ->with('user:id,name,email')
            ->where('status', InternStatus::ACTIVE)
            ->get();

        $activeInternIds = $activeInternsCollection->pluck('id');

        $attendances = Attendance::query()
            ->whereIn('intern_id', $activeInternIds)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->get();

        $attendedAttendances = $attendances
            ->where('status', '!=', AttendanceStatus::ABSENT);

        $actualRecords = $attendedAttendances
            ->map(fn (Attendance $attendance) => $attendance->intern_id.'|'.$attendance->date?->toDateString())
            ->unique()
            ->count();
        $expectedRecords = $activeInterns * $elapsedWorkingDays;
        $averageMinutes = (int) round($attendedAttendances->whereNotNull('work_duration_minutes')->avg('work_duration_minutes') ?? 0);

        $dailyAttendance = [];
        for ($date = $start->copy(); $date->lte($end); $date = $date->addDay()) {
            if (! $date->isWeekday()) {
                continue;
            }

            $dateKey = $date->toDateString();
            $dayAttendances = $attendances->filter(fn (Attendance $attendance) => $attendance->date?->toDateString() === $dateKey);
            $dayActualRecords = $dayAttendances
                ->where('status', '!=', AttendanceStatus::ABSENT)
                ->pluck('intern_id')
                ->unique()
                ->count();

            $dailyAttendance[] = [
                'date' => $dateKey,
                'label' => $date->format('M j'),
                'present' => $dayAttendances->where('status', AttendanceStatus::PRESENT)->pluck('intern_id')->unique()->count(),
                'late' => $dayAttendances->where('status', AttendanceStatus::LATE)->pluck('intern_id')->unique()->count(),
                'partial' => $dayAttendances->where('status', AttendanceStatus::PARTIAL)->pluck('intern_id')->unique()->count(),
                'absent' => max($activeInterns - $dayActualRecords, 0),
                'attendance_rate' => $activeInterns > 0 ? round(($dayActualRecords / $activeInterns) * 100) : 0,
            ];
        }

        $statusDistribution = collect(AttendanceStatus::cases())
            ->map(function (AttendanceStatus $status) use ($attendances) {
                $count = $attendances->where('status', $status)->count();

                return [
                    'status' => $status->value,
                    'count' => $count,
                    'percentage' => $attendances->count() > 0 ? round(($count / $attendances->count()) * 100) : 0,
                ];
            })
            ->values()
            ->all();

        $internPerformance = $activeInternsCollection
            ->map(function ($intern) use ($attendances, $elapsedWorkingDays) {
                $internAttendances = $attendances->where('intern_id', $intern->id);
                $attendedDays = $internAttendances
                    ->where('status', '!=', AttendanceStatus::ABSENT)
                    ->pluck('date')
                    ->map(fn ($date) => $date?->toDateString())
                    ->unique()
                    ->count();
                $totalMinutes = (int) $internAttendances
                    ->where('status', '!=', AttendanceStatus::ABSENT)
                    ->sum('work_duration_minutes');

                return [
                    'id' => $intern->id,
                    'name' => $intern->user?->name ?? 'Unknown intern',
                    'email' => $intern->user?->email,
                    'attended_days' => $attendedDays,
                    'missed_days' => max($elapsedWorkingDays - $attendedDays, 0),
                    'attendance_rate' => $elapsedWorkingDays > 0 ? round(($attendedDays / $elapsedWorkingDays) * 100) : 0,
                    'total_hours' => round($totalMinutes / 60, 1),
                    'last_attended_on' => $internAttendances
                        ->where('status', '!=', AttendanceStatus::ABSENT)
                        ->sortByDesc('date')
                        ->first()?->date?->toDateString(),
                ];
            })
            ->sortBy('attendance_rate')
            ->values();

        return [
            'overview' => [
                'elapsed_working_days' => $elapsedWorkingDays,
                'expected_records' => $expectedRecords,
                'actual_records' => $actualRecords,
                'missing_records' => max($expectedRecords - $actualRecords, 0),
                'average_hours_per_attendance' => round($averageMinutes / 60, 1),
                'at_risk_interns' => $internPerformance->where('attendance_rate', '<', 75)->count(),
            ],
            'daily_attendance' => $dailyAttendance,
            'status_distribution' => $statusDistribution,
            'intern_performance' => $internPerformance->all(),
        ];
    }

    private function elapsedWorkingDays(InternshipBatch $batch): int
    {
        if (! $batch->start_date || ! $batch->end_date) {
            return 0;
        }

        $start = $batch->start_date->copy()->startOfDay();
        $end = $this->attendanceRateEndDate($batch);

        if ($end->isBefore($start)) {
            return 0;
        }

        $workingDays = 0;

        for ($date = $start->copy(); $date->lte($end); $date = $date->addDay()) {
            if ($date->isWeekday()) {
                $workingDays++;
            }
        }

        return $workingDays;
    }

    private function attendanceRateEndDate(InternshipBatch $batch)
    {
        $today = today();
        $batchEnd = $batch->end_date->copy()->startOfDay();

        return $batchEnd->isBefore($today) ? $batchEnd : $today;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InternshipBatch $batch)
    {
        return Inertia::render('batches/Edit', [
            'batch' => $batch,
            'coordinators' => User::where('role', '!=', 'intern')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InternshipBatch $batch)
    {
        $validated = $request->validate([
            'batch_code' => ['required', 'string', 'max:255', 'unique:internship_batches,batch_code,'.$batch->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'capacity' => ['required', 'integer', 'min:1'],
            'expected_working_days' => ['required', 'integer', 'min:1'],
            'status' => ['required', new Enum(BatchStatus::class)],
            'coordinator_id' => ['nullable', 'exists:users,id'],
        ]);

        $batch->update($validated);

        return redirect()->route('batches.index')
            ->with('success', 'Batch updated successfully.');
    }

    /**
     * Close the specified batch.
     */
    public function close(InternshipBatch $batch)
    {
        $batch->update([
            'status' => BatchStatus::CLOSED,
        ]);

        return redirect()->route('batches.show', $batch)
            ->with('success', 'Batch closed successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InternshipBatch $batch)
    {
        $batch->delete();

        return redirect()->route('batches.index')
            ->with('success', 'Batch archived successfully.');
    }
}
