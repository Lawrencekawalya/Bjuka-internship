<?php

namespace Tests\Feature;

use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\InternSupervisorAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
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

    public function test_admin_can_reset_intern_password_and_force_password_change(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $internUser = User::factory()->create([
            'role' => UserRole::INTERN,
            'password' => 'OldPassword123!',
            'must_change_password' => false,
        ]);
        $intern = Intern::factory()->create([
            'batch_id' => $batch->id,
            'user_id' => $internUser->id,
        ]);
        $token = $internUser->createToken('mobile')->plainTextToken;

        $this->actingAs($admin)
            ->patch(route('batches.interns.password.reset', [$batch, $intern]), [
                'temporary_password' => 'NewTemporary123!',
                'temporary_password_confirmation' => 'NewTemporary123!',
            ])
            ->assertRedirect(route('batches.show', $batch));

        $internUser->refresh();

        $this->assertTrue(Hash::check('NewTemporary123!', $internUser->password));
        $this->assertTrue($internUser->must_change_password);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $internUser->id,
            'name' => 'mobile',
        ]);
        $this->assertNotEmpty($token);
    }

    public function test_hr_can_reset_intern_password(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $batch = InternshipBatch::factory()->create();
        $internUser = User::factory()->create([
            'role' => UserRole::INTERN,
            'must_change_password' => false,
        ]);
        $intern = Intern::factory()->create([
            'batch_id' => $batch->id,
            'user_id' => $internUser->id,
        ]);

        $this->actingAs($hr)
            ->patch(route('batches.interns.password.reset', [$batch, $intern]), [
                'temporary_password' => 'HrTemporary123!',
                'temporary_password_confirmation' => 'HrTemporary123!',
            ])
            ->assertRedirect(route('batches.show', $batch));

        $internUser->refresh();

        $this->assertTrue(Hash::check('HrTemporary123!', $internUser->password));
        $this->assertTrue($internUser->must_change_password);
    }

    public function test_non_admin_or_hr_cannot_reset_intern_password(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $batch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($supervisor)
            ->patch(route('batches.interns.password.reset', [$batch, $intern]), [
                'temporary_password' => 'Temporary123!',
                'temporary_password_confirmation' => 'Temporary123!',
            ])
            ->assertForbidden();
    }

    public function test_cannot_reset_intern_password_through_wrong_batch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $otherBatch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $otherBatch->id]);

        $this->actingAs($admin)
            ->patch(route('batches.interns.password.reset', [$batch, $intern]), [
                'temporary_password' => 'Temporary123!',
                'temporary_password_confirmation' => 'Temporary123!',
            ])
            ->assertNotFound();
    }

    public function test_admin_can_upload_intern_certificate(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($admin)
            ->post(route('batches.interns.certificate.store', [$batch, $intern]), [
                'certificate_file' => UploadedFile::fake()->create('certificate.pdf', 128, 'application/pdf'),
            ])
            ->assertRedirect(route('batches.show', $batch));

        $intern->refresh();

        $this->assertNotNull($intern->certificate_path);
        Storage::disk('public')->assertExists($intern->certificate_path);
    }

    public function test_non_admin_cannot_upload_intern_certificate(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $batch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($hr)
            ->post(route('batches.interns.certificate.store', [$batch, $intern]), [
                'certificate_file' => UploadedFile::fake()->create('certificate.pdf', 128, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_admin_can_assign_supervisors_to_intern(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);
        $firstSupervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $secondSupervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);

        $this->actingAs($admin)
            ->patch(route('batches.interns.supervisors.update', [$batch, $intern]), [
                'supervisor_ids' => [$firstSupervisor->id, $secondSupervisor->id],
            ])
            ->assertRedirect(route('batches.show', $batch));

        $this->assertDatabaseHas('intern_supervisor_assignments', [
            'intern_id' => $intern->id,
            'supervisor_id' => $firstSupervisor->id,
        ]);
        $this->assertDatabaseHas('intern_supervisor_assignments', [
            'intern_id' => $intern->id,
            'supervisor_id' => $secondSupervisor->id,
        ]);
    }

    public function test_assigning_supervisors_replaces_previous_assignments(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);
        $oldSupervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $newSupervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);

        InternSupervisorAssignment::factory()->create([
            'intern_id' => $intern->id,
            'supervisor_id' => $oldSupervisor->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('batches.interns.supervisors.update', [$batch, $intern]), [
                'supervisor_ids' => [$newSupervisor->id],
            ])
            ->assertRedirect(route('batches.show', $batch));

        $this->assertDatabaseMissing('intern_supervisor_assignments', [
            'intern_id' => $intern->id,
            'supervisor_id' => $oldSupervisor->id,
        ]);
        $this->assertDatabaseHas('intern_supervisor_assignments', [
            'intern_id' => $intern->id,
            'supervisor_id' => $newSupervisor->id,
        ]);
    }

    public function test_non_admin_cannot_assign_supervisors_to_intern(): void
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $batch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);
        $supervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);

        $this->actingAs($hr)
            ->patch(route('batches.interns.supervisors.update', [$batch, $intern]), [
                'supervisor_ids' => [$supervisor->id],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_batch_report_format(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();

        $this->actingAs($admin)
            ->patch(route('batches.report-format.update', $batch), [
                'report_format_text' => "Cover Page\nChapter One: Introduction",
                'report_format_file' => UploadedFile::fake()->create('format.docx', 128, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->assertRedirect();

        $batch->refresh();

        $this->assertSame("Cover Page\nChapter One: Introduction", $batch->report_format_text);
        $this->assertSame('format.docx', $batch->report_format_original_name);
        Storage::disk('public')->assertExists($batch->report_format_path);
    }
}
