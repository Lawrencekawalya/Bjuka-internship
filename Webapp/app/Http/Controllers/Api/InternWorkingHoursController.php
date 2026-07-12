<?php

namespace App\Http\Controllers\Api;

use App\Enums\InternStatus;
use App\Http\Controllers\Controller;
use App\Models\BatchWorkingHour;
use App\Services\BatchWorkingHoursScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternWorkingHoursController extends Controller
{
    public function show(Request $request, BatchWorkingHoursScheduleService $workingHoursSchedule): JsonResponse
    {
        $intern = $request->user()->intern;

        if (! $intern || $intern->status !== InternStatus::ACTIVE || ! $intern->batch) {
            return response()->json([
                'message' => 'Active intern profile required.',
            ], 403);
        }

        $workingHours = $workingHoursSchedule->ensureDefaultSchedule($intern->batch);

        return response()->json([
            'working_hours' => [
                'batch' => [
                    'id' => $intern->batch->id,
                    'name' => $intern->batch->name,
                    'batch_code' => $intern->batch->batch_code,
                ],
                'timezone' => config('app.timezone'),
                'days' => $workingHours
                    ->map(fn (BatchWorkingHour $workingHour) => [
                        'id' => $workingHour->id,
                        'day_of_week' => $workingHour->day_of_week,
                        'day_name' => $this->dayName($workingHour->day_of_week),
                        'is_working_day' => $workingHour->is_working_day,
                        'start_time' => $workingHour->start_time?->format('H:i'),
                        'end_time' => $workingHour->end_time?->format('H:i'),
                        'break_start_time' => $workingHour->break_start_time?->format('H:i'),
                        'break_end_time' => $workingHour->break_end_time?->format('H:i'),
                        'notes' => $workingHour->notes,
                    ])
                    ->values(),
            ],
        ]);
    }

    private function dayName(int $dayOfWeek): string
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ][$dayOfWeek] ?? 'Unknown';
    }
}
