<?php

namespace App\Http\Controllers;

use App\Models\InternshipBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BatchReportFormatController extends Controller
{
    public function update(Request $request, InternshipBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'report_format_text' => ['nullable', 'string', 'max:20000'],
            'report_format_file' => ['nullable', 'file', 'mimes:txt,doc,docx,odt,pdf', 'max:10240'],
        ]);

        $updates = [
            'report_format_text' => $validated['report_format_text'] ?? null,
        ];

        if ($request->hasFile('report_format_file')) {
            if ($batch->report_format_path) {
                Storage::disk('public')->delete($batch->report_format_path);
            }

            $file = $request->file('report_format_file');
            $updates['report_format_path'] = $file->store('report-formats', 'public');
            $updates['report_format_original_name'] = $file->getClientOriginalName();
        }

        $batch->update($updates);

        return back()->with('success', 'Report format updated successfully.');
    }
}
