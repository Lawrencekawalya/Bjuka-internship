<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Intern;
use App\Models\InternReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;

class InternshipReportDraftService
{
    /**
     * @return array<string, mixed>
     */
    public function generate(Intern $intern): array
    {
        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $intern->loadMissing(['user', 'batch']);
        $context = $this->reportContext($intern);
        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.report_model'),
                'input' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($context, JSON_PRETTY_PRINT),
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('OpenAI could not generate the report draft.');
        }

        return $this->normalizeDraft($this->extractText($response->json()), $context);
    }

    public function writeDocx(InternReport $report): string
    {
        $report->loadMissing('intern.user');

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginRight' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
        ]);

        $content = $report->content ?? [];
        $section->addTitle($content['title'] ?? 'Internship Report Draft', 1);
        $section->addText('Generated draft for: '.$report->intern->user?->name, ['bold' => true]);
        $section->addText('Review, edit, format, and add your real training images before submission.');
        $section->addTextBreak();

        foreach (($content['sections'] ?? []) as $draftSection) {
            $heading = (string) ($draftSection['heading'] ?? 'Section');
            $body = (string) ($draftSection['body'] ?? '');

            $section->addTitle($heading, 2);
            foreach (preg_split('/\R+/', trim($body)) ?: [] as $paragraph) {
                if (trim($paragraph) !== '') {
                    $section->addText(trim($paragraph));
                }
            }

            foreach (($draftSection['image_placeholders'] ?? []) as $placeholder) {
                $section->addText(
                    '[Insert image: '.trim((string) $placeholder).']',
                    ['italic' => true, 'color' => '666666']
                );
            }

            $section->addTextBreak();
        }

        $relativePath = sprintf(
            'intern-reports/%s/%s.docx',
            $report->intern_id,
            Str::slug(($report->intern->user?->name ?? 'intern').'-report-'.$report->id)
        );
        $absolutePath = Storage::disk('public')->path($relativePath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($absolutePath);

        return $relativePath;
    }

    /**
     * @return array<string, mixed>
     */
    private function reportContext(Intern $intern): array
    {
        $attendances = Attendance::query()
            ->with('learningLog')
            ->where('intern_id', $intern->id)
            ->whereNotNull('check_out_server_time')
            ->orderBy('date')
            ->get()
            ->map(fn (Attendance $attendance) => [
                'date' => $attendance->date?->toDateString(),
                'status' => $attendance->status->value,
                'duration_minutes' => $attendance->work_duration_minutes,
                'activities' => $attendance->learningLog?->tasks_completed,
                'challenges' => $attendance->learningLog?->challenges,
            ])
            ->values()
            ->all();

        return [
            'report_format' => $this->defaultReportFormat(),
            'intern' => [
                'name' => $intern->user?->name,
                'institution' => $intern->institution,
                'course' => $intern->course,
                'registration_number' => $intern->registration_number,
            ],
            'batch' => [
                'name' => $intern->batch?->name,
                'start_date' => $intern->batch?->start_date?->toDateString(),
                'end_date' => $intern->batch?->end_date?->toDateString(),
                'expected_working_days' => $intern->batch?->expected_working_days,
            ],
            'daily_logs' => $attendances,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function defaultReportFormat(): array
    {
        return [
            'Title Page',
            'Acknowledgement',
            'Executive Summary',
            'Introduction',
            'Training Activities and Weekly Work Done',
            'Skills and Knowledge Acquired',
            'Challenges Faced and Solutions',
            'Tools, Technologies, and Equipment Used',
            'Image Evidence Placeholders',
            'Conclusion and Recommendations',
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You generate editable internship report drafts from verified attendance checkout logs.
Return only valid JSON with this exact shape:
{
  "title": "string",
  "sections": [
    {
      "heading": "string",
      "body": "string",
      "image_placeholders": ["string"]
    }
  ]
}
Follow the report_format exactly. Use the intern's logs as the factual source. Do not invent exact activities that are not supported by the logs. Use professional student-report language. Include practical image placeholders where evidence photos would strengthen the report.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractText(array $response): string
    {
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        foreach (($response['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return (string) $content['text'];
                }
            }
        }

        throw new RuntimeException('OpenAI returned an unreadable report draft.');
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function normalizeDraft(string $rawDraft, array $context): array
    {
        $rawDraft = trim($rawDraft);
        $rawDraft = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $rawDraft) ?? $rawDraft;
        $decoded = json_decode($rawDraft, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid report JSON.');
        }

        $sections = collect($decoded['sections'] ?? [])
            ->filter(fn ($section) => is_array($section))
            ->map(fn ($section) => [
                'heading' => trim((string) ($section['heading'] ?? 'Section')),
                'body' => trim((string) ($section['body'] ?? '')),
                'image_placeholders' => array_values(array_filter(
                    array_map('strval', $section['image_placeholders'] ?? [])
                )),
            ])
            ->values()
            ->all();

        if ($sections === []) {
            throw new RuntimeException('OpenAI returned an empty report draft.');
        }

        return [
            'title' => trim((string) ($decoded['title'] ?? 'Internship Report Draft')),
            'intern' => $context['intern'],
            'batch' => $context['batch'],
            'sections' => $sections,
        ];
    }
}
