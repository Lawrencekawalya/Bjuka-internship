<?php

namespace App\Http\Controllers;

use App\Models\Intern;
use App\Models\InternshipBatch;
use Illuminate\Http\RedirectResponse;

class ReportGenerationResetController extends Controller
{
    public function update(InternshipBatch $batch, Intern $intern): RedirectResponse
    {
        abort_if($intern->batch_id !== $batch->id, 404);

        $quota = $intern->reportGenerationQuota()->firstOrCreate([], [
            'generation_limit' => 3,
        ]);

        if ($quota->reset_requested_at === null || $quota->reset_used) {
            return back()->with('error', 'This intern is not eligible for a report generation reset.');
        }

        $quota->update([
            'generation_count' => 0,
            'reset_requested_at' => null,
            'reset_approved_at' => now(),
            'reset_used' => true,
            'permanently_locked_at' => null,
        ]);

        return back()->with('success', 'Report generation permissions reset successfully.');
    }
}
