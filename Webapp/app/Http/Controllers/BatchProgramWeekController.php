<?php

namespace App\Http\Controllers;

use App\Models\InternshipBatch;
use App\Models\InternshipProgramWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BatchProgramWeekController extends Controller
{
    public function store(Request $request, InternshipBatch $batch): RedirectResponse
    {
        $validated = $this->validated($request, $batch);

        $batch->programWeeks()->create($validated);

        return redirect()
            ->route('batches.show', $batch)
            ->with('success', 'Intern program week added successfully.');
    }

    public function update(Request $request, InternshipBatch $batch, InternshipProgramWeek $programWeek): RedirectResponse
    {
        abort_unless($programWeek->batch_id === $batch->id, 404);

        $programWeek->update($this->validated($request, $batch, $programWeek));

        return redirect()
            ->route('batches.show', $batch)
            ->with('success', 'Intern program week updated successfully.');
    }

    public function destroy(InternshipBatch $batch, InternshipProgramWeek $programWeek): RedirectResponse
    {
        abort_unless($programWeek->batch_id === $batch->id, 404);

        $programWeek->delete();

        return redirect()
            ->route('batches.show', $batch)
            ->with('success', 'Intern program week removed successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, InternshipBatch $batch, ?InternshipProgramWeek $programWeek = null): array
    {
        return $request->validate([
            'week_number' => [
                'required',
                'integer',
                'min:1',
                'max:52',
                Rule::unique('internship_program_weeks', 'week_number')
                    ->where('batch_id', $batch->id)
                    ->ignore($programWeek?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'objectives' => ['required', 'string'],
            'topics' => ['required', 'string'],
            'activities' => ['required', 'string'],
        ]);
    }
}
