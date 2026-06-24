<?php

namespace App\Http\Controllers;

use App\Enums\BatchStatus;
use App\Models\ApprovedNetwork;
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
            'approvedNetworks',
        ]);

        // Dashboard Stats
        $stats = [
            'total_interns' => $batch->interns()->count(),
            'present_today' => $batch->interns()->whereHas('attendances', function ($query) {
                $query->whereDate('date', now()->toDateString());
            })->count(),
            'attendance_rate' => 0, // Placeholder for aggregation logic
            'total_supervisors' => $batch->interns()->with('supervisors')->get()->pluck('supervisors')->flatten()->unique('id')->count(),
            'progress' => $batch->progress_percentage,
        ];

        return Inertia::render('batches/Show', [
            'batch' => $batch,
            'stats' => $stats,
        ]);
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
