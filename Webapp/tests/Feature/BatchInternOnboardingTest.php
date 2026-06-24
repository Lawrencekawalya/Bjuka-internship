<?php

namespace Tests\Feature;

use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BatchInternOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_onboard_intern_to_batch(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();

        $this->actingAs($admin)
            ->post(route('batches.interns.store', $batch), [
                'name' => 'Demo Intern',
                'email' => 'demo.intern@bjuka.com',
                'temporary_password' => 'Temporary123!',
                'temporary_password_confirmation' => 'Temporary123!',
                'phone' => '+256700000000',
                'institution' => 'Makerere University',
                'course' => 'Software Engineering',
                'registration_number' => 'REG-2026-001',
                'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
            ])
            ->assertRedirect(route('batches.show', $batch));

        $internUser = User::where('email', 'demo.intern@bjuka.com')->firstOrFail();

        $this->assertTrue($internUser->must_change_password);
        $this->assertSame(UserRole::INTERN, $internUser->role);
        Storage::disk('public')->assertExists($internUser->profile_photo_path);

        $this->assertDatabaseHas('interns', [
            'user_id' => $internUser->id,
            'batch_id' => $batch->id,
            'institution' => 'Makerere University',
            'course' => 'Software Engineering',
            'registration_number' => 'REG-2026-001',
            'status' => InternStatus::ACTIVE->value,
        ]);
    }

    public function test_non_admin_cannot_onboard_intern_to_batch(): void
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $batch = InternshipBatch::factory()->create();

        $this->actingAs($user)
            ->post(route('batches.interns.store', $batch), [
                'name' => 'Demo Intern',
                'email' => 'demo.intern@bjuka.com',
                'temporary_password' => 'Temporary123!',
                'temporary_password_confirmation' => 'Temporary123!',
            ])
            ->assertForbidden();
    }
}
