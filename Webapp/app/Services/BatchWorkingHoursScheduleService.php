<?php

namespace App\Services;

use App\Models\BatchWorkingHour;
use App\Models\InternshipBatch;
use Illuminate\Support\Collection;

class BatchWorkingHoursScheduleService
{
    /**
     * @return Collection<int, BatchWorkingHour>
     */
    public function ensureDefaultSchedule(InternshipBatch $batch): Collection
    {
        if ($batch->workingHours()->count() === 0) {
            foreach ($this->defaultSchedule() as $workingHour) {
                $batch->workingHours()->create($workingHour);
            }
        }

        return $batch->workingHours()->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultSchedule(): array
    {
        return collect(range(1, 7))
            ->map(fn (int $day) => [
                'day_of_week' => $day,
                'is_working_day' => $day <= 5,
                'start_time' => $day <= 5 ? '08:00:00' : null,
                'end_time' => $day <= 5 ? '17:00:00' : null,
                'break_start_time' => $day <= 5 ? '13:00:00' : null,
                'break_end_time' => $day <= 5 ? '14:00:00' : null,
                'notes' => $day <= 5
                    ? 'Standard office training day.'
                    : 'Weekend. Attendance is not expected.',
            ])
            ->all();
    }
}
