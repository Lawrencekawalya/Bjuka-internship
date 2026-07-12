<?php

namespace App\Http\Controllers\Api;

use App\Enums\InternStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipProgramWeek;
use App\Services\InternshipProgramScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternProgramController extends Controller
{
    public function show(Request $request, InternshipProgramScheduleService $programSchedule): JsonResponse
    {
        $intern = $request->user()->intern;

        if (! $intern || $intern->status !== InternStatus::ACTIVE || ! $intern->batch) {
            return response()->json([
                'message' => 'Active intern profile required.',
            ], 403);
        }

        $weeks = $programSchedule->ensureDefaultSchedule($intern->batch);

        return response()->json([
            'program' => [
                'batch' => [
                    'id' => $intern->batch->id,
                    'name' => $intern->batch->name,
                    'batch_code' => $intern->batch->batch_code,
                ],
                'weeks' => $weeks
                    ->map(fn (InternshipProgramWeek $week) => [
                        'id' => $week->id,
                        'week_number' => $week->week_number,
                        'title' => $week->title,
                        'start_date' => $week->start_date?->toDateString(),
                        'end_date' => $week->end_date?->toDateString(),
                        'objectives' => $week->objectives,
                        'topics' => $week->topics,
                        'activities' => $week->activities,
                    ])
                    ->values(),
            ],
        ]);
    }
}
