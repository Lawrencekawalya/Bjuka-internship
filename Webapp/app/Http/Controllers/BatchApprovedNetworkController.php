<?php

namespace App\Http\Controllers;

use App\Models\ApprovedNetwork;
use App\Models\InternshipBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BatchApprovedNetworkController extends Controller
{
    public function store(Request $request, InternshipBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ssid' => [
                'required',
                'string',
                'max:255',
                Rule::unique('approved_networks', 'ssid')->where('batch_id', $batch->id),
            ],
            'bssid' => ['required', 'string', 'max:255'],
        ]);

        $batch->approvedNetworks()->create($validated);

        return redirect()
            ->route('batches.show', $batch)
            ->with('success', 'Approved network added successfully.');
    }

    public function update(Request $request, InternshipBatch $batch, ApprovedNetwork $network): RedirectResponse
    {
        abort_unless($network->batch_id === $batch->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ssid' => [
                'required',
                'string',
                'max:255',
                Rule::unique('approved_networks', 'ssid')
                    ->where('batch_id', $batch->id)
                    ->ignore($network->id),
            ],
            'bssid' => ['required', 'string', 'max:255'],
        ]);

        $network->update($validated);

        return redirect()
            ->route('batches.show', $batch)
            ->with('success', 'Approved network updated successfully.');
    }
}
