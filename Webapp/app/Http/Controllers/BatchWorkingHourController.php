<?php

namespace App\Http\Controllers;

use App\Models\BatchWorkingHour;
use App\Models\InternshipBatch;
use App\Services\BatchWorkingHoursScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BatchWorkingHourController extends Controller
{
    public function update(
        Request $request,
        InternshipBatch $batch,
        BatchWorkingHoursScheduleService $workingHoursSchedule
    ): RedirectResponse {
        $workingHoursSchedule->ensureDefaultSchedule($batch);

        $validated = $request->validate([
            'working_hours' => ['required', 'array', 'size:7'],
            'working_hours.*.id' => [
                'required',
                Rule::exists('batch_working_hours', 'id')->where('batch_id', $batch->id),
            ],
            'working_hours.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'working_hours.*.is_working_day' => ['required', 'boolean'],
            'working_hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'working_hours.*.end_time' => ['nullable', 'date_format:H:i'],
            'working_hours.*.break_start_time' => ['nullable', 'date_format:H:i'],
            'working_hours.*.break_end_time' => ['nullable', 'date_format:H:i'],
            'working_hours.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($validated['working_hours'] as $row) {
            if ((bool) $row['is_working_day'] && (blank($row['start_time'] ?? null) || blank($row['end_time'] ?? null))) {
                return back()
                    ->withErrors(['working_hours' => 'Working days must have both a start time and an end time.'])
                    ->withInput();
            }

            /** @var BatchWorkingHour $workingHour */
            $workingHour = $batch->workingHours()->whereKey($row['id'])->firstOrFail();
            $isWorkingDay = (bool) $row['is_working_day'];

            $workingHour->update([
                'day_of_week' => $row['day_of_week'],
                'is_working_day' => $isWorkingDay,
                'start_time' => $isWorkingDay ? $row['start_time'] : null,
                'end_time' => $isWorkingDay ? $row['end_time'] : null,
                'break_start_time' => $isWorkingDay ? ($row['break_start_time'] ?? null) : null,
                'break_end_time' => $isWorkingDay ? ($row['break_end_time'] ?? null) : null,
                'notes' => $row['notes'] ?? null,
            ]);
        }

        return redirect()
            ->route('batches.show', $batch)
            ->with('success', 'Working hours updated successfully.');
    }
}
