<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Intern;
use App\Models\InternReport;
use App\Models\InternshipProgramWeek;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;

class InternshipReportDraftService
{
    public function __construct(private readonly InternshipProgramScheduleService $programSchedule) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(Intern $intern): array
    {
        $apiKey = config('services.openai.api_key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $intern->loadMissing(['user', 'batch.programWeeks']);
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
            Log::warning('OpenAI report generation request failed.', [
                'intern_id' => $intern->id,
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

            $message = data_get($response->json(), 'error.message');

            throw new RuntimeException(is_string($message) && $message !== ''
                ? 'OpenAI error: '.$message
                : 'OpenAI could not generate the report draft.');
        }

        return $this->normalizeDraft($this->extractText($response->json()), $context);
    }

    public function writeDocx(InternReport $report): string
    {
        if (! class_exists(PhpWord::class)) {
            throw new RuntimeException('Word document generator is not installed. Run composer install on the backend server.');
        }

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
        $section->addTitle($this->docxText($content['title'] ?? 'Internship Report Draft'), 1);
        $section->addText($this->docxText('Generated draft for: '.$report->intern->user?->name), ['bold' => true]);
        $section->addText('Review, edit, format, and add your real training images before submission.');
        $section->addTextBreak();

        foreach (($content['sections'] ?? []) as $draftSection) {
            $heading = (string) ($draftSection['heading'] ?? 'Section');
            $paragraphs = $this->sectionParagraphs($draftSection);
            $bulletPoints = array_values(array_filter(
                array_map('strval', $draftSection['bullet_points'] ?? [])
            ));

            $section->addTitle($this->docxText($heading), 2);
            foreach ($paragraphs as $paragraph) {
                if (trim($paragraph) !== '') {
                    $section->addText($this->docxText(trim($paragraph)));
                }
            }

            foreach ($bulletPoints as $bulletPoint) {
                $section->addListItem($this->docxText(trim($bulletPoint)), 0);
            }

            foreach (($draftSection['image_placeholders'] ?? []) as $placeholder) {
                $section->addText(
                    $this->docxText('[Insert image: '.trim((string) $placeholder).']'),
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

    private function docxText(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportContext(Intern $intern): array
    {
        $programWeeks = $intern->batch
            ? $this->programSchedule->ensureDefaultSchedule($intern->batch)
            : collect();

        $attendances = Attendance::query()
            ->with('learningLog')
            ->where('intern_id', $intern->id)
            ->whereNotNull('check_out_server_time')
            ->orderBy('date')
            ->get()
            ->map(function (Attendance $attendance) use ($programWeeks) {
                $programWeek = $this->programWeekForDate($programWeeks, $attendance);

                return [
                    'date' => $attendance->date?->toDateString(),
                    'status' => $attendance->status->value,
                    'duration_minutes' => $attendance->work_duration_minutes,
                    'activities' => $attendance->learningLog?->tasks_completed,
                    'challenges' => $attendance->learningLog?->challenges,
                    'program_week' => $programWeek ? [
                        'week_number' => $programWeek->week_number,
                        'title' => $programWeek->title,
                    ] : null,
                ];
            })
            ->values();

        $weeklyLogs = $programWeeks
            ->map(fn (InternshipProgramWeek $week) => [
                'week_number' => $week->week_number,
                'title' => $week->title,
                'date_range' => $week->start_date?->toDateString().' to '.$week->end_date?->toDateString(),
                'objectives' => $week->objectives,
                'topics' => $week->topics,
                'planned_activities' => $week->activities,
                'intern_logs' => $attendances
                    ->filter(fn (array $log) => ($log['program_week']['week_number'] ?? null) === $week->week_number)
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return [
            'report_format' => $this->reportFormatFor($intern),
            'minimum_depth_requirements' => [
                'each_major_section' => 'Write 3 to 6 substantial paragraphs unless the section is a title page, table of contents, declaration, dedication, or references.',
                'weekly_activities' => 'Use all available daily checkout logs. Group related days into weekly or thematic paragraphs and include concrete activities.',
                'program_alignment' => 'Align daily logs with the official batch intern program weeks. Mention the week title/topics when the logs fall inside that week.',
                'skills_and_challenges' => 'Explain what was learned, how it was applied, and why it matters professionally.',
                'image_placeholders' => 'Add 1 to 3 specific image placeholders in practical sections where photos would support the report.',
            ],
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
            'intern_program' => $programWeeks
                ->map(fn (InternshipProgramWeek $week) => [
                    'week_number' => $week->week_number,
                    'title' => $week->title,
                    'start_date' => $week->start_date?->toDateString(),
                    'end_date' => $week->end_date?->toDateString(),
                    'objectives' => $week->objectives,
                    'topics' => $week->topics,
                    'activities' => $week->activities,
                ])
                ->values()
                ->all(),
            'weekly_logs' => $weeklyLogs,
            'daily_logs' => $attendances->all(),
        ];
    }

    private function programWeekForDate(Collection $programWeeks, Attendance $attendance): ?InternshipProgramWeek
    {
        if (! $attendance->date) {
            return null;
        }

        return $programWeeks->first(fn (InternshipProgramWeek $week) => $attendance->date->betweenIncluded($week->start_date, $week->end_date));
    }

    /**
     * @return array<int, string>|string
     */
    private function reportFormatFor(Intern $intern): array|string
    {
        $format = trim((string) ($intern->batch?->report_format_text ?? ''));

        return $format !== '' ? $format : $this->defaultReportFormat();
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
      "paragraphs": ["string"],
      "bullet_points": ["string"],
      "image_placeholders": ["string"]
    }
  ]
}
Follow the report_format exactly and create every section required by it. Generate a comprehensive editable draft, not a summary. For each major narrative section, write 3 to 6 substantial paragraphs with clear development of ideas, examples from the checkout logs, and professional reflection. Use the intern_program and weekly_logs to connect the intern's daily work to the official batch training roadmap. When writing weekly training activities, mention the relevant week number, week title, planned topics, and how the intern's logged work relates to them. Use bullet_points only for lists such as tools, skills, activities, recommendations, or image evidence notes. Use the intern's logs as the factual source. Do not invent exact completed activities that are not supported by the logs. If logs are limited, expand responsibly by explaining the official program topic, likely learning outcomes, professional relevance, and any clearly logged evidence. Include practical image placeholders where evidence photos would strengthen the report.
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $draftSection
     * @return array<int, string>
     */
    private function sectionParagraphs(array $draftSection): array
    {
        if (isset($draftSection['paragraphs']) && is_array($draftSection['paragraphs'])) {
            return array_values(array_filter(array_map('strval', $draftSection['paragraphs'])));
        }

        $body = (string) ($draftSection['body'] ?? '');

        return array_values(array_filter(preg_split('/\R+/', trim($body)) ?: []));
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
            Log::warning('OpenAI report generation returned invalid JSON.', [
                'intern_id' => $context['intern']['name'] ?? null,
                'draft' => mb_substr($rawDraft, 0, 2000),
            ]);

            throw new RuntimeException('OpenAI returned invalid report JSON.');
        }

        $sections = collect($decoded['sections'] ?? [])
            ->filter(fn ($section) => is_array($section))
            ->map(fn ($section) => [
                'heading' => trim((string) ($section['heading'] ?? 'Section')),
                'paragraphs' => $this->sectionParagraphs($section),
                'bullet_points' => array_values(array_filter(
                    array_map('strval', $section['bullet_points'] ?? [])
                )),
                'image_placeholders' => array_values(array_filter(
                    array_map('strval', $section['image_placeholders'] ?? [])
                )),
            ])
            ->values()
            ->all();

        if ($sections === []) {
            Log::warning('OpenAI report generation returned no sections.', [
                'intern_id' => $context['intern']['name'] ?? null,
                'draft' => $decoded,
            ]);

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
