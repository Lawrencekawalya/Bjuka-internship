<?php

namespace App\Http\Controllers;

use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BatchInternController extends Controller
{
    public function store(Request $request, InternshipBatch $batch): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'temporary_password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:2048'],
        ]);

        DB::transaction(function () use ($batch, $validated): void {
            $profilePhotoPath = null;
            if (($validated['profile_photo'] ?? null) instanceof UploadedFile) {
                $profilePhotoPath = $validated['profile_photo']->store('profile-photos', 'public');
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['temporary_password'],
                'profile_photo_path' => $profilePhotoPath,
                'role' => UserRole::INTERN,
                'must_change_password' => true,
            ]);

            Intern::create([
                'user_id' => $user->id,
                'batch_id' => $batch->id,
                'phone' => $validated['phone'] ?? null,
                'institution' => $validated['institution'] ?? null,
                'course' => $validated['course'] ?? null,
                'registration_number' => $validated['registration_number'] ?? null,
                'status' => InternStatus::ACTIVE,
            ]);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Intern onboarded successfully. They must change the temporary password on first login.',
        ]);

        return redirect()->route('batches.show', $batch);
    }

    public function resetPassword(Request $request, InternshipBatch $batch, Intern $intern): RedirectResponse
    {
        abort_unless($intern->batch_id === $batch->id, 404);

        $validated = $request->validate([
            'temporary_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $intern->loadMissing('user');

        $intern->user->update([
            'password' => $validated['temporary_password'],
            'must_change_password' => true,
        ]);

        $intern->user->tokens()->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Intern password reset successfully. They must change the temporary password on next login.',
        ]);

        return redirect()->route('batches.show', $batch);
    }
}
