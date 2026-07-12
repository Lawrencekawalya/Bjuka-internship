<?php

namespace App\Http\Controllers\Api;

use App\Enums\InternStatus;
use App\Http\Controllers\Controller;
use App\Models\Intern;
use App\Models\InternReport;
use App\Models\InternReportGenerationQuota;
use App\Services\InternshipReportDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class InternReportController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $intern = $this->activeIntern($request);
        $quota = $this->quotaFor($intern);
        $latestReport = $intern->reports()
            ->where('status', 'ready')
            ->latest()
            ->first();

        return response()->json([
            'report' => [
                'available' => $this->internshipComplete($intern),
                'completion_required' => ! $this->internshipComplete($intern),
                'quota' => $this->quotaPayload($quota),
                'latest_report' => $latestReport ? $this->reportPayload($latestReport) : null,
            ],
        ]);
    }

    public function generate(Request $request, InternshipReportDraftService $draftService): JsonResponse
    {
        $intern = $this->activeIntern($request);

        if (! $this->internshipComplete($intern)) {
            return response()->json([
                'message' => 'Report generation becomes available after internship completion.',
            ], 403);
        }

        if (! config('services.openai.api_key')) {
            return response()->json([
                'message' => 'OpenAI API key is not configured.',
            ], 503);
        }

        try {
            $report = DB::transaction(function () use ($intern): InternReport {
                $quota = InternReportGenerationQuota::query()
                    ->where('intern_id', $intern->id)
                    ->lockForUpdate()
                    ->first() ?? InternReportGenerationQuota::create([
                        'intern_id' => $intern->id,
                        'generation_count' => 0,
                        'generation_limit' => 3,
                    ]);

                if (! $quota->canGenerate()) {
                    abort(429, $quota->reset_used
                        ? 'Report generation is permanently locked.'
                        : 'You have used all report generation attempts.');
                }

                $quota->increment('generation_count');
                $quota->refresh();

                if ($quota->remainingGenerations() === 0 && $quota->reset_used) {
                    $quota->update([
                        'permanently_locked_at' => now(),
                    ]);
                }

                return InternReport::create([
                    'intern_id' => $intern->id,
                    'status' => 'generating',
                ]);
            });

            $content = $draftService->generate($intern);
            $report->update([
                'content' => $content,
                'status' => 'ready',
                'generated_at' => now(),
            ]);
            $report->refresh();
            $report->update([
                'docx_path' => $draftService->writeDocx($report->load('intern.user')),
            ]);
            $report->refresh();
        } catch (HttpException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            Log::error('Intern report generation failed.', [
                'intern_id' => $intern->id,
                'report_id' => isset($report) ? $report->id : null,
                'message' => $exception->getMessage(),
            ]);

            if (isset($report)) {
                $report->update([
                    'status' => 'failed',
                    'failure_reason' => $exception->getMessage(),
                ]);
            }

            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        } catch (Throwable $exception) {
            Log::error('Unexpected intern report generation failure.', [
                'intern_id' => $intern->id,
                'report_id' => isset($report) ? $report->id : null,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            if (isset($report)) {
                $report->update([
                    'status' => 'failed',
                    'failure_reason' => $exception->getMessage(),
                ]);
            }

            return response()->json([
                'message' => 'Report draft was generated, but the Word document could not be created. Please contact admin.',
            ], 502);
        }

        $intern->refresh();

        return response()->json([
            'message' => 'Internship report draft generated successfully.',
            'report' => $this->reportPayload($report),
            'quota' => $this->quotaPayload($this->quotaFor($intern)),
        ], 201);
    }

    public function requestReset(Request $request): JsonResponse
    {
        $intern = $this->activeIntern($request);
        $quota = $this->quotaFor($intern);

        if (! $quota->canRequestReset()) {
            return response()->json([
                'message' => 'Report generation reset cannot be requested.',
            ], 409);
        }

        $quota->update([
            'reset_requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Report generation reset requested.',
            'quota' => $this->quotaPayload($quota->fresh()),
        ]);
    }

    private function activeIntern(Request $request): Intern
    {
        $intern = $request->user()->intern;

        abort_if(! $intern || $intern->status !== InternStatus::ACTIVE, 403, 'Active intern profile required.');

        return $intern->loadMissing('batch');
    }

    private function internshipComplete(Intern $intern): bool
    {
        return ($intern->batch?->progress_percentage ?? 0) >= 100;
    }

    private function quotaFor(Intern $intern): InternReportGenerationQuota
    {
        return $intern->reportGenerationQuota()->firstOrCreate([], [
            'generation_limit' => 3,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function quotaPayload(InternReportGenerationQuota $quota): array
    {
        return [
            'generation_count' => $quota->generation_count,
            'generation_limit' => $quota->generation_limit,
            'remaining_generations' => $quota->remainingGenerations(),
            'reset_requested' => $quota->reset_requested_at !== null,
            'reset_used' => $quota->reset_used,
            'can_generate' => $quota->canGenerate(),
            'can_request_reset' => $quota->canRequestReset(),
            'permanently_locked' => $quota->permanently_locked_at !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function reportPayload(InternReport $report): array
    {
        return [
            'id' => $report->id,
            'status' => $report->status,
            'content' => $report->content,
            'download_url' => $report->download_url,
            'generated_at' => $report->generated_at?->toIso8601String(),
        ];
    }
}
