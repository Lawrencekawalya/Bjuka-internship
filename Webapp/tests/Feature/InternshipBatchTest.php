<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\BatchStatus;
use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\ApprovedNetwork;
use App\Models\Attendance;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\InternshipProgramWeek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InternshipBatchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    }

    public function test_can_list_batches()
    {
        InternshipBatch::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('batches.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('batches/Index'));
    }

    public function test_can_view_create_batch_page()
    {
        $response = $this->actingAs($this->admin)->get(route('batches.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('batches/Create'));
    }

    public function test_can_create_batch()
    {
        $data = [
            'batch_code' => 'TEST-BATCH-001',
            'name' => 'Test Batch',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'capacity' => 25,
            'expected_working_days' => 60,
            'status' => BatchStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('batches.store'), $data);

        $response->assertRedirect(route('batches.index'));
        $this->assertDatabaseHas('internship_batches', ['batch_code' => 'TEST-BATCH-001']);
        $this->assertDatabaseHas('approved_networks', [
            'ssid' => 'BJUKA_WIFI',
            'bssid' => 'any',
        ]);
    }

    public function test_can_view_edit_batch_page()
    {
        $batch = InternshipBatch::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('batches.edit', $batch));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('batches/Edit'));
    }

    public function test_can_update_batch()
    {
        $batch = InternshipBatch::factory()->create([
            'batch_code' => 'OLD-BATCH-001',
            'name' => 'Old Batch Name',
        ]);

        $response = $this->actingAs($this->admin)->put(route('batches.update', $batch), [
            'batch_code' => 'NEW-BATCH-001',
            'name' => 'New Batch Name',
            'description' => 'Updated batch description.',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'capacity' => 30,
            'expected_working_days' => 65,
            'status' => BatchStatus::ACTIVE->value,
        ]);

        $response->assertRedirect(route('batches.index'));
        $this->assertDatabaseHas('internship_batches', [
            'id' => $batch->id,
            'batch_code' => 'NEW-BATCH-001',
            'name' => 'New Batch Name',
            'expected_working_days' => 65,
        ]);
    }

    public function test_admin_can_add_approved_network_to_batch()
    {
        $batch = InternshipBatch::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('batches.approved-networks.store', $batch), [
                'name' => 'Engineering Office WiFi',
                'ssid' => 'BJUKA_ENGINEERING',
                'bssid' => 'any',
            ]);

        $response->assertRedirect(route('batches.show', $batch));
        $this->assertDatabaseHas('approved_networks', [
            'batch_id' => $batch->id,
            'name' => 'Engineering Office WiFi',
            'ssid' => 'BJUKA_ENGINEERING',
            'bssid' => 'any',
        ]);
    }

    public function test_admin_can_update_approved_network()
    {
        $batch = InternshipBatch::factory()->create();
        $network = ApprovedNetwork::factory()->create([
            'batch_id' => $batch->id,
            'name' => 'Old Office WiFi',
            'ssid' => 'OLD_WIFI',
            'bssid' => 'any',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('batches.approved-networks.update', [$batch, $network]), [
                'name' => 'Updated Office WiFi',
                'ssid' => 'UPDATED_WIFI',
                'bssid' => '00:11:22:33:44:55',
            ]);

        $response->assertRedirect(route('batches.show', $batch));
        $this->assertDatabaseHas('approved_networks', [
            'id' => $network->id,
            'batch_id' => $batch->id,
            'name' => 'Updated Office WiFi',
            'ssid' => 'UPDATED_WIFI',
            'bssid' => '00:11:22:33:44:55',
        ]);
    }

    public function test_admin_can_add_program_week_to_batch()
    {
        $batch = InternshipBatch::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('batches.program-weeks.store', $batch), [
                'week_number' => 7,
                'title' => 'Advanced Deployment',
                'start_date' => '2026-07-27',
                'end_date' => '2026-07-31',
                'objectives' => 'Learn production deployment practices.',
                'topics' => "Linux servers\nLaravel queues\nBackups",
                'activities' => "Deploy Laravel app\nConfigure backups",
            ]);

        $response->assertRedirect(route('batches.show', $batch));
        $this->assertDatabaseHas('internship_program_weeks', [
            'batch_id' => $batch->id,
            'week_number' => 7,
            'title' => 'Advanced Deployment',
            'topics' => "Linux servers\nLaravel queues\nBackups",
        ]);
    }

    public function test_admin_can_update_program_week()
    {
        $batch = InternshipBatch::factory()->create();
        $week = InternshipProgramWeek::create([
            'batch_id' => $batch->id,
            'week_number' => 1,
            'title' => 'Old Title',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-19',
            'objectives' => 'Old objectives',
            'topics' => 'Old topics',
            'activities' => 'Old activities',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('batches.program-weeks.update', [$batch, $week]), [
                'week_number' => 1,
                'title' => 'Hardware Fundamentals',
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-19',
                'objectives' => 'Understand hardware components.',
                'topics' => 'Motherboard components',
                'activities' => 'Assemble a desktop',
            ]);

        $response->assertRedirect(route('batches.show', $batch));
        $this->assertDatabaseHas('internship_program_weeks', [
            'id' => $week->id,
            'title' => 'Hardware Fundamentals',
            'activities' => 'Assemble a desktop',
        ]);
    }

    public function test_admin_can_remove_program_week()
    {
        $batch = InternshipBatch::factory()->create();
        $week = InternshipProgramWeek::create([
            'batch_id' => $batch->id,
            'week_number' => 1,
            'title' => 'Temporary Week',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-19',
            'objectives' => 'Temporary objectives',
            'topics' => 'Temporary topics',
            'activities' => 'Temporary activities',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('batches.program-weeks.destroy', [$batch, $week]))
            ->assertRedirect(route('batches.show', $batch));

        $this->assertDatabaseMissing('internship_program_weeks', [
            'id' => $week->id,
        ]);
    }

    public function test_non_admin_cannot_manage_program_weeks()
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $batch = InternshipBatch::factory()->create();
        $week = InternshipProgramWeek::create([
            'batch_id' => $batch->id,
            'week_number' => 1,
            'title' => 'Week One',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-19',
            'objectives' => 'Objectives',
            'topics' => 'Topics',
            'activities' => 'Activities',
        ]);

        $this->actingAs($hr)
            ->post(route('batches.program-weeks.store', $batch), [
                'week_number' => 2,
                'title' => 'Blocked',
                'start_date' => '2026-06-22',
                'end_date' => '2026-06-26',
                'objectives' => 'Blocked',
                'topics' => 'Blocked',
                'activities' => 'Blocked',
            ])
            ->assertForbidden();

        $this->actingAs($hr)
            ->patch(route('batches.program-weeks.update', [$batch, $week]), [
                'week_number' => 1,
                'title' => 'Blocked',
                'start_date' => '2026-06-15',
                'end_date' => '2026-06-19',
                'objectives' => 'Blocked',
                'topics' => 'Blocked',
                'activities' => 'Blocked',
            ])
            ->assertForbidden();

        $this->actingAs($hr)
            ->delete(route('batches.program-weeks.destroy', [$batch, $week]))
            ->assertForbidden();
    }

    public function test_admin_can_export_batch_interns_csv()
    {
        $batch = InternshipBatch::factory()->create([
            'batch_code' => 'INT-EXPORT',
        ]);
        $intern = Intern::factory()->create([
            'batch_id' => $batch->id,
            'institution' => 'Kabale University',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('batches.interns.export', $batch));

        $response
            ->assertOk()
            ->assertDownload('int-export-interns.csv');

        $content = $response->streamedContent();

        $this->assertStringContainsString($intern->user->email, $content);
        $this->assertStringContainsString('Kabale University', $content);
    }

    public function test_admin_can_generate_batch_report_document()
    {
        $batch = InternshipBatch::factory()->create([
            'batch_code' => 'INT-REPORT',
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-23',
            'expected_working_days' => 30,
        ]);
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);

        Attendance::factory()->create([
            'intern_id' => $intern->id,
            'date' => '2026-06-15',
            'status' => AttendanceStatus::PRESENT,
            'work_duration_minutes' => 480,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('batches.report', $batch));

        $response
            ->assertOk()
            ->assertDownload('int-report-batch-report.docx');
    }

    public function test_non_admin_cannot_export_batch_files()
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $batch = InternshipBatch::factory()->create();

        $this->actingAs($hr)
            ->get(route('batches.interns.export', $batch))
            ->assertForbidden();

        $this->actingAs($hr)
            ->get(route('batches.report', $batch))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_manage_approved_networks()
    {
        $hr = User::factory()->create(['role' => UserRole::HR]);
        $batch = InternshipBatch::factory()->create();
        $network = ApprovedNetwork::factory()->create(['batch_id' => $batch->id]);

        $this->actingAs($hr)
            ->post(route('batches.approved-networks.store', $batch), [
                'name' => 'HR WiFi',
                'ssid' => 'HR_WIFI',
                'bssid' => 'any',
            ])
            ->assertForbidden();

        $this->actingAs($hr)
            ->patch(route('batches.approved-networks.update', [$batch, $network]), [
                'name' => 'Updated HR WiFi',
                'ssid' => 'UPDATED_HR_WIFI',
                'bssid' => 'any',
            ])
            ->assertForbidden();
    }

    public function test_can_close_batch()
    {
        $batch = InternshipBatch::factory()->create([
            'status' => BatchStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('batches.close', $batch));

        $response->assertRedirect(route('batches.show', $batch));
        $this->assertDatabaseHas('internship_batches', [
            'id' => $batch->id,
            'status' => BatchStatus::CLOSED->value,
        ]);
    }

    public function test_batch_validation_rejects_invalid_dates_and_status()
    {
        $response = $this->actingAs($this->admin)
            ->from(route('batches.create'))
            ->post(route('batches.store'), [
                'batch_code' => 'TEST-BATCH-002',
                'name' => 'Invalid Batch',
                'start_date' => now()->addMonth()->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'capacity' => 25,
                'expected_working_days' => 60,
                'status' => 'paused',
            ]);

        $response->assertRedirect(route('batches.create'));
        $response->assertSessionHasErrors(['end_date', 'status']);
        $this->assertDatabaseMissing('internship_batches', ['batch_code' => 'TEST-BATCH-002']);
    }

    public function test_non_admin_cannot_manage_batches()
    {
        $supervisor = User::factory()->create(['role' => UserRole::SUPERVISOR]);
        $batch = InternshipBatch::factory()->create();

        $this->actingAs($supervisor)->get(route('batches.index'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('batches.create'))->assertForbidden();
        $this->actingAs($supervisor)->get(route('batches.edit', $batch))->assertForbidden();
        $this->actingAs($supervisor)->patch(route('batches.close', $batch))->assertForbidden();
        $this->actingAs($supervisor)->delete(route('batches.destroy', $batch))->assertForbidden();
    }

    public function test_virtual_status_is_upcoming_for_future_active_batch()
    {
        $batch = InternshipBatch::factory()->create([
            'status' => BatchStatus::ACTIVE,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addMonths(3),
        ]);

        $this->assertEquals('upcoming', $batch->virtual_status);
    }

    public function test_virtual_status_is_active_for_current_active_batch()
    {
        $batch = InternshipBatch::factory()->create([
            'status' => BatchStatus::ACTIVE,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addMonths(2),
        ]);

        $this->assertEquals('active', $batch->virtual_status);
    }

    public function test_can_archive_batch()
    {
        $batch = InternshipBatch::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('batches.destroy', $batch->id));

        $response->assertRedirect(route('batches.index'));

        $batch->refresh();
        $this->assertNotNull($batch->deleted_at);
        $this->assertTrue($batch->trashed());
    }

    public function test_batch_show_calculates_attendance_rate_from_elapsed_weekdays_and_returns_attendance_log(): void
    {
        Carbon::setTestNow('2026-06-19 12:00:00');
        $batch = InternshipBatch::factory()->create([
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-15',
            'expected_working_days' => 10,
        ]);
        $interns = Intern::factory()->count(2)->create([
            'batch_id' => $batch->id,
            'status' => InternStatus::ACTIVE,
        ]);

        foreach ([
            ['intern' => $interns[0], 'date' => '2026-06-15'],
            ['intern' => $interns[0], 'date' => '2026-06-16'],
            ['intern' => $interns[0], 'date' => '2026-06-17'],
            ['intern' => $interns[0], 'date' => '2026-06-18'],
            ['intern' => $interns[1], 'date' => '2026-06-15'],
            ['intern' => $interns[1], 'date' => '2026-06-16'],
            ['intern' => $interns[1], 'date' => '2026-06-17'],
        ] as $record) {
            Attendance::factory()->create([
                'intern_id' => $record['intern']->id,
                'date' => $record['date'],
                'check_in_server_time' => $record['date'].' 08:30:00',
                'work_duration_minutes' => 480,
                'status' => AttendanceStatus::PRESENT,
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('batches.show', $batch))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('batches/Show')
                ->where('stats.attendance_rate', 70)
                ->where('batch_performance.overview.expected_records', 20)
                ->where('batch_performance.overview.actual_records', 7)
                ->where('batch_performance.overview.missing_records', 13)
                ->where('batch_performance.overview.average_hours_per_attendance', 8)
                ->where('batch_performance.overview.at_risk_interns', 2)
                ->has('batch_performance.daily_attendance', 5)
                ->where('batch_performance.daily_attendance.0.attendance_rate', 100)
                ->where('batch_performance.daily_attendance.0.interns.present.0', $interns[0]->user->name)
                ->where('batch_performance.daily_attendance.0.interns.present.1', $interns[1]->user->name)
                ->where('batch_performance.daily_attendance.3.attendance_rate', 50)
                ->where('batch_performance.daily_attendance.3.interns.absent.0', $interns[1]->user->name)
                ->where('batch_performance.status_distribution.0.status', AttendanceStatus::PRESENT->value)
                ->where('batch_performance.status_distribution.0.count', 7)
                ->has('batch_performance.intern_performance', 2)
                ->where('batch_performance.intern_performance.0.attendance_rate', 30)
                ->where('batch_performance.intern_performance.0.missed_days', 7)
                ->where('batch_performance.intern_performance.1.attendance_rate', 40)
                ->where('batch_performance.intern_performance.1.missed_days', 6)
                ->has('batch_attendances', 7)
            );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
