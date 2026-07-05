<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\DailyLearningLog;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    private const LATE_THRESHOLD_HOUR = 9;

    private const LATE_THRESHOLD_MINUTE = 30;

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
            'import_errors' => session('attendance_import_errors', []),
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $validated['attendance_file']->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'attendance_file' => 'The attendance file could not be opened.',
            ]);
        }

        $rows = $this->csvRows($path);
        $summary = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($rows, &$summary): void {
            foreach ($rows as $row) {
                try {
                    $attendanceData = $this->attendanceDataFromImportRow($row);

                    if (Attendance::query()
                        ->where('intern_id', $attendanceData['intern_id'])
                        ->whereDate('date', $attendanceData['date'])
                        ->exists()) {
                        $summary['skipped']++;

                        continue;
                    }

                    $attendance = Attendance::create($attendanceData);

                    if (($row['activities'] ?? '') !== '') {
                        DailyLearningLog::create([
                            'attendance_id' => $attendance->id,
                            'studied_content' => $row['activities'],
                            'tasks_completed' => $row['activities'],
                        ]);
                    }

                    $summary['imported']++;
                } catch (ValidationException $exception) {
                    $summary['errors'][] = "Row {$row['_row_number']}: ".$this->firstValidationError($exception);
                }
            }
        });

        $message = "Attendance import complete. Imported {$summary['imported']} row(s), skipped {$summary['skipped']} duplicate row(s).";

        if (count($summary['errors']) > 0) {
            $message .= ' '.count($summary['errors']).' row(s) had errors.';
        }

        Inertia::flash('toast', [
            'type' => count($summary['errors']) > 0 ? 'warning' : 'success',
            'message' => $message,
        ]);

        return redirect()
            ->route('attendances.index')
            ->with('attendance_import_errors', array_slice($summary['errors'], 0, 10));
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

    /**
     * @return array<int, array<string, string|int>>
     */
    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'attendance_file' => 'The attendance file could not be opened.',
            ]);
        }

        $header = null;
        $rows = [];
        $rowNumber = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isEmptyCsvRow($data)) {
                continue;
            }

            if ($header === null) {
                $header = array_map(fn (string $value) => strtolower(trim($this->stripBom($value))), $data);
                $this->validateCsvHeader($header);

                continue;
            }

            $data = array_pad($data, count($header), '');
            $row = array_combine($header, array_map(fn (?string $value) => trim((string) $value), $data));

            if ($row === false) {
                continue;
            }

            $row['_row_number'] = $rowNumber;
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int, string>  $header
     */
    private function validateCsvHeader(array $header): void
    {
        $required = ['date', 'intern_email', 'check_in_time'];
        $missing = array_diff($required, $header);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'attendance_file' => 'Missing required CSV column(s): '.implode(', ', $missing).'.',
            ]);
        }
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isEmptyCsvRow(array $row): bool
    {
        return collect($row)->every(fn (?string $value) => trim((string) $value) === '');
    }

    /**
     * @param  array<string, string|int>  $row
     * @return array<string, mixed>
     */
    private function attendanceDataFromImportRow(array $row): array
    {
        $date = $this->importDate($row['date'] ?? '');
        $checkIn = $this->importDateTime($date, $row['check_in_time'] ?? '');
        $checkOut = $this->optionalImportDateTime($date, $row['check_out_time'] ?? '');
        $intern = User::query()
            ->whereRaw('lower(email) = ?', [strtolower((string) ($row['intern_email'] ?? ''))])
            ->where('role', UserRole::INTERN)
            ->with('intern')
            ->first();

        if (! $intern?->intern) {
            throw ValidationException::withMessages([
                'intern_email' => 'No intern account found for '.$row['intern_email'].'.',
            ]);
        }

        if ($checkOut && $checkOut->lessThan($checkIn)) {
            throw ValidationException::withMessages([
                'check_out_time' => 'Check out time must be after check in time.',
            ]);
        }

        return [
            'intern_id' => $intern->intern->id,
            'date' => $date->toDateString(),
            'check_in_device_time' => $checkIn,
            'check_in_server_time' => $checkIn,
            'check_out_device_time' => $checkOut,
            'check_out_server_time' => $checkOut,
            'work_duration_minutes' => $checkOut ? $checkIn->diffInMinutes($checkOut) : null,
            'status' => $this->statusForImportedCheckIn($checkIn),
            'wifi_ssid' => ($row['wifi_ssid'] ?? '') !== '' ? $row['wifi_ssid'] : 'Manual import',
            'wifi_bssid' => ($row['wifi_bssid'] ?? '') !== '' ? $row['wifi_bssid'] : null,
        ];
    }

    private function importDate(string|int $value): CarbonInterface
    {
        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'date' => 'Date must be valid. Use YYYY-MM-DD, for example 2026-06-24.',
            ]);
        }
    }

    private function importDateTime(CarbonInterface $date, string|int $time): CarbonInterface
    {
        if (trim((string) $time) === '') {
            throw ValidationException::withMessages([
                'check_in_time' => 'Check in time is required.',
            ]);
        }

        try {
            return Carbon::parse($date->toDateString().' '.trim((string) $time));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'check_in_time' => 'Check in time must be valid.',
            ]);
        }
    }

    private function optionalImportDateTime(CarbonInterface $date, string|int $time): ?CarbonInterface
    {
        if (trim((string) $time) === '') {
            return null;
        }

        try {
            return Carbon::parse($date->toDateString().' '.trim((string) $time));
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'check_out_time' => 'Check out time must be valid.',
            ]);
        }
    }

    private function statusForImportedCheckIn(CarbonInterface $checkIn): AttendanceStatus
    {
        return $checkIn->greaterThan($checkIn->copy()->setTime(self::LATE_THRESHOLD_HOUR, self::LATE_THRESHOLD_MINUTE))
            ? AttendanceStatus::LATE
            : AttendanceStatus::PRESENT;
    }

    private function firstValidationError(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? $exception->getMessage();
    }

    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }
}
