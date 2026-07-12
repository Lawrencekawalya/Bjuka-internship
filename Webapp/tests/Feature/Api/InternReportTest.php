<?php

namespace Tests\Feature\Api;

use App\Enums\InternStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\DailyLearningLog;
use App\Models\Intern;
use App\Models\InternReportGenerationQuota;
use App\Models\InternshipBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InternReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_generation_is_only_available_after_completion(): void
    {
        Carbon::setTestNow('2026-07-12 10:00:00');
        config(['services.openai.api_key' => 'test-key']);
        $user = $this->activeInternUser([
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/intern/report/generate')
            ->assertForbidden()
            ->assertJsonPath('message', 'Report generation becomes available after internship completion.');
    }

    public function test_intern_can_generate_report_and_docx_after_completion(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-25 10:00:00');
        config(['services.openai.api_key' => 'test-key']);
        Http::fake([
            'api.openai.com/*' => function ($request) {
                $this->assertStringContainsString(
                    'Chapter One: Organization Background',
                    $request->data()['input'][1]['content']
                );
                $this->assertStringContainsString(
                    'CCTV Camera Installation & Final Project',
                    $request->data()['input'][1]['content']
                );
                $this->assertStringContainsString(
                    '"weekly_logs"',
                    $request->data()['input'][1]['content']
                );

                return Http::response([
                    'output_text' => json_encode([
                        'title' => 'B. JUKA Technologies & Partners Internship Report',
                        'sections' => [
                            [
                                'heading' => 'Hardware Repair & Maintenance Fundamentals',
                                'paragraphs' => [
                                    'The intern completed practical training activities based on checkout logs and handled Cabling & Network Installation tasks.',
                                    'The report connects daily practical work to professional learning outcomes with references such as Nesheim, T., Sigernes, T., & Bjørkli.',
                                ],
                                'bullet_points' => ['Installed CCTV cameras & configured remote viewing'],
                                'image_placeholders' => ['Cabling & installation activity photo'],
                            ],
                        ],
                    ]),
                ]);
            },
        ]);
        $user = $this->activeInternUser([
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-24',
            'report_format_text' => "Cover Page\nChapter One: Organization Background\nChapter Two: Internship Activities",
        ]);
        $attendance = Attendance::factory()->create([
            'intern_id' => $user->intern->id,
            'date' => '2026-07-20',
            'check_out_server_time' => '2026-07-20 17:00:00',
        ]);
        DailyLearningLog::factory()->create([
            'attendance_id' => $attendance->id,
            'tasks_completed' => 'Installed CCTV cameras and configured remote viewing.',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/intern/report/generate');

        $response
            ->assertCreated()
            ->assertJsonPath('quota.remaining_generations', 2)
            ->assertJsonPath('report.status', 'ready');

        $report = $user->intern->reports()->firstOrFail();

        $this->assertSame($response->json('report.id'), $report->id);
        Storage::disk('public')->assertExists($report->docx_path);

        $documentXml = file_get_contents('zip://'.Storage::disk('public')->path($report->docx_path).'#word/document.xml');

        $this->assertIsString($documentXml);
        $this->assertStringContainsString('Hardware Repair &amp; Maintenance Fundamentals', $documentXml);
        $this->assertStringContainsString('Cabling &amp; Network Installation', $documentXml);

        libxml_use_internal_errors(true);
        $parsedXml = simplexml_load_string($documentXml);

        $this->assertNotFalse($parsedXml, collect(libxml_get_errors())->pluck('message')->implode("\n"));
        libxml_clear_errors();
    }

    public function test_intern_can_request_one_reset_after_using_three_attempts(): void
    {
        Carbon::setTestNow('2026-07-25 10:00:00');
        $user = $this->activeInternUser([
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-24',
        ]);
        InternReportGenerationQuota::create([
            'intern_id' => $user->intern->id,
            'generation_count' => 3,
            'generation_limit' => 3,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/intern/report/request-reset')
            ->assertOk()
            ->assertJsonPath('quota.reset_requested', true)
            ->assertJsonPath('quota.can_request_reset', false);
    }

    public function test_admin_can_approve_reset_only_once(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);
        InternReportGenerationQuota::create([
            'intern_id' => $intern->id,
            'generation_count' => 3,
            'generation_limit' => 3,
            'reset_requested_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('batches.interns.report-generation-reset.update', [$batch, $intern]))
            ->assertRedirect();

        $quota = $intern->reportGenerationQuota()->firstOrFail();

        $this->assertSame(0, $quota->generation_count);
        $this->assertTrue($quota->reset_used);

        $this->actingAs($admin)
            ->patch(route('batches.interns.report-generation-reset.update', [$batch, $intern]))
            ->assertSessionHas('error');
    }

    public function test_generation_is_permanently_locked_after_reset_attempts_are_used(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-25 10:00:00');
        config(['services.openai.api_key' => 'test-key']);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'output_text' => json_encode([
                    'title' => 'Final Report Draft',
                    'sections' => [
                        [
                            'heading' => 'Conclusion',
                            'paragraphs' => ['The intern completed the training program.'],
                            'bullet_points' => [],
                            'image_placeholders' => [],
                        ],
                    ],
                ]),
            ]),
        ]);
        $user = $this->activeInternUser([
            'start_date' => '2026-06-15',
            'end_date' => '2026-07-24',
        ]);
        InternReportGenerationQuota::create([
            'intern_id' => $user->intern->id,
            'generation_count' => 2,
            'generation_limit' => 3,
            'reset_used' => true,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/intern/report/generate')
            ->assertCreated()
            ->assertJsonPath('quota.remaining_generations', 0)
            ->assertJsonPath('quota.permanently_locked', true);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/intern/report/request-reset')
            ->assertConflict();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/intern/report/generate')
            ->assertTooManyRequests();
    }

    public function test_admin_can_reset_report_generation_restrictions_for_all_interns_in_batch(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $batch = InternshipBatch::factory()->create();
        $otherBatch = InternshipBatch::factory()->create();
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);
        $lockedIntern = Intern::factory()->create(['batch_id' => $batch->id]);
        $otherIntern = Intern::factory()->create(['batch_id' => $otherBatch->id]);

        InternReportGenerationQuota::create([
            'intern_id' => $intern->id,
            'generation_count' => 3,
            'generation_limit' => 3,
            'reset_requested_at' => now(),
            'reset_used' => true,
            'permanently_locked_at' => now(),
        ]);
        InternReportGenerationQuota::create([
            'intern_id' => $lockedIntern->id,
            'generation_count' => 3,
            'generation_limit' => 3,
            'reset_used' => true,
            'permanently_locked_at' => now(),
        ]);
        InternReportGenerationQuota::create([
            'intern_id' => $otherIntern->id,
            'generation_count' => 3,
            'generation_limit' => 3,
            'reset_used' => true,
            'permanently_locked_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('batches.report-generation-reset.update', $batch))
            ->assertRedirect();

        foreach ([$intern, $lockedIntern] as $batchIntern) {
            $quota = $batchIntern->reportGenerationQuota()->firstOrFail();

            $this->assertSame(0, $quota->generation_count);
            $this->assertSame(3, $quota->generation_limit);
            $this->assertNull($quota->reset_requested_at);
            $this->assertNull($quota->reset_approved_at);
            $this->assertFalse($quota->reset_used);
            $this->assertNull($quota->permanently_locked_at);
        }

        $otherQuota = $otherIntern->reportGenerationQuota()->firstOrFail();

        $this->assertSame(3, $otherQuota->generation_count);
        $this->assertTrue($otherQuota->reset_used);
        $this->assertNotNull($otherQuota->permanently_locked_at);
    }

    private function activeInternUser(array $batchAttributes): User
    {
        $user = User::factory()->create(['role' => UserRole::INTERN]);
        $batch = InternshipBatch::factory()->create($batchAttributes);

        Intern::factory()->create([
            'user_id' => $user->id,
            'batch_id' => $batch->id,
            'status' => InternStatus::ACTIVE,
        ]);

        return $user->load('intern');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
