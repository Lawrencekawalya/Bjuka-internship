<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\InternStatus;
use App\Models\Attendance;
use App\Models\InternshipBatch;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\Writer\Word2007;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BatchExportController extends Controller
{
    public function exportInterns(InternshipBatch $batch): StreamedResponse
    {
        $batch->load([
            'interns.user:id,name,email',
            'interns.supervisors' => fn ($query) => $query->select('users.id', 'users.name', 'users.email'),
            'interns.reportGenerationQuota',
        ]);

        $filename = $this->safeFilename($batch, 'interns').'.csv';

        return response()->streamDownload(function () use ($batch): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Email',
                'Phone',
                'Institution',
                'Course',
                'Registration Number',
                'Status',
                'Supervisors',
                'Certificate Uploaded',
                'Report Generations Used',
                'Report Generation Limit',
                'Report Reset Requested',
            ]);

            foreach ($batch->interns as $intern) {
                fputcsv($handle, [
                    $intern->user?->name,
                    $intern->user?->email,
                    $intern->phone,
                    $intern->institution,
                    $intern->course,
                    $intern->registration_number,
                    $intern->status->value,
                    $intern->supervisors->pluck('name')->join('; '),
                    $intern->certificate_path ? 'Yes' : 'No',
                    $intern->reportGenerationQuota?->generation_count ?? 0,
                    $intern->reportGenerationQuota?->generation_limit ?? 3,
                    $intern->reportGenerationQuota?->reset_requested_at ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function generateReport(InternshipBatch $batch): BinaryFileResponse
    {
        $batch->load([
            'coordinator:id,name,email',
            'interns.user:id,name,email',
            'interns.supervisors' => fn ($query) => $query->select('users.id', 'users.name', 'users.email'),
            'approvedNetworks',
        ]);

        $activeInterns = $batch->interns->where('status', InternStatus::ACTIVE);
        $attendances = Attendance::query()
            ->whereHas('intern', fn ($query) => $query->where('batch_id', $batch->id))
            ->whereDate('date', '>=', $batch->start_date)
            ->whereDate('date', '<=', $batch->end_date)
            ->get();
        $attendedRecords = $attendances->where('status', '!=', AttendanceStatus::ABSENT);
        $expectedRecords = $activeInterns->count() * (int) $batch->expected_working_days;
        $actualRecords = $attendedRecords
            ->map(fn (Attendance $attendance) => $attendance->intern_id.'|'.$attendance->date?->toDateString())
            ->unique()
            ->count();

        $phpWord = new PhpWord;
        $phpWord->getSettings()->setThemeFontLang(new Language(Language::EN_US));
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);
        $phpWord->addTitleStyle(2, ['bold' => true, 'size' => 15], ['spaceAfter' => 160]);
        $phpWord->addFontStyle('label', ['bold' => true]);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginRight' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
        ]);

        $section->addTitle('Batch Performance Report', 1);
        $section->addText($batch->name, ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addText($batch->batch_code, ['size' => 11], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();

        $this->addKeyValueTable($section, [
            'Status' => $batch->virtual_status,
            'Duration' => $batch->start_date?->toFormattedDateString().' to '.$batch->end_date?->toFormattedDateString(),
            'Expected Working Days' => (string) $batch->expected_working_days,
            'Capacity' => (string) $batch->capacity,
            'Coordinator' => $batch->coordinator?->name ?? 'Unassigned',
            'Generated On' => now()->toDayDateTimeString(),
        ]);

        $section->addTitle('Executive Summary', 2);
        $this->addKeyValueTable($section, [
            'Total Interns' => (string) $batch->interns->count(),
            'Active Interns' => (string) $activeInterns->count(),
            'Unique Supervisors Assigned' => (string) $batch->interns->pluck('supervisors')->flatten()->unique('id')->count(),
            'Expected Attendance Records' => (string) $expectedRecords,
            'Recorded Attendance Records' => (string) $actualRecords,
            'Attendance Rate' => $expectedRecords > 0 ? round(($actualRecords / $expectedRecords) * 100).'%' : '0%',
            'Missing Attendance Records' => (string) max($expectedRecords - $actualRecords, 0),
            'Average Hours Per Attendance' => round(((int) $attendedRecords->sum('work_duration_minutes') / max($attendedRecords->whereNotNull('work_duration_minutes')->count(), 1)) / 60, 1).'h',
        ]);

        $section->addTitle('Intern Performance', 2);
        $this->addInternPerformanceTable($section, $batch->interns, $attendances, (int) $batch->expected_working_days);

        $section->addTitle('Approved Networks', 2);
        $this->addApprovedNetworksTable($section, $batch->approvedNetworks);

        $path = storage_path('app/'.$this->safeFilename($batch, 'batch-report').'.docx');
        (new Word2007($phpWord))->save($path);

        return response()
            ->download($path, basename($path), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend();
    }

    private function addKeyValueTable($section, array $rows): void
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'DDDDDD', 'cellMargin' => 90]);

        foreach ($rows as $label => $value) {
            $table->addRow();
            $table->addCell(4200)->addText($label, 'label');
            $table->addCell(5200)->addText((string) $value);
        }

        $section->addTextBreak();
    }

    private function addInternPerformanceTable($section, Collection $interns, Collection $attendances, int $expectedWorkingDays): void
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'DDDDDD', 'cellMargin' => 80]);
        $headers = ['Intern', 'Email', 'Rate', 'Attended', 'Missed', 'Hours', 'Supervisors'];

        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(1800)->addText($header, 'label');
        }

        foreach ($interns as $intern) {
            $internAttendances = $attendances->where('intern_id', $intern->id);
            $attendedDays = $internAttendances
                ->where('status', '!=', AttendanceStatus::ABSENT)
                ->pluck('date')
                ->map(fn ($date) => $date?->toDateString())
                ->unique()
                ->count();
            $rate = $expectedWorkingDays > 0 ? round(($attendedDays / $expectedWorkingDays) * 100).'%' : '0%';

            $table->addRow();
            $table->addCell(1800)->addText($intern->user?->name ?? 'Unknown');
            $table->addCell(2200)->addText($intern->user?->email ?? '-');
            $table->addCell(900)->addText($rate);
            $table->addCell(900)->addText((string) $attendedDays);
            $table->addCell(900)->addText((string) max($expectedWorkingDays - $attendedDays, 0));
            $table->addCell(900)->addText(round(((int) $internAttendances->sum('work_duration_minutes')) / 60, 1).'h');
            $table->addCell(2200)->addText($intern->supervisors->pluck('name')->join(', ') ?: 'Unassigned');
        }

        $section->addTextBreak();
    }

    private function addApprovedNetworksTable($section, Collection $networks): void
    {
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'DDDDDD', 'cellMargin' => 80]);
        $table->addRow();
        $table->addCell(3200)->addText('Name', 'label');
        $table->addCell(3200)->addText('SSID', 'label');
        $table->addCell(3200)->addText('BSSID', 'label');

        foreach ($networks as $network) {
            $table->addRow();
            $table->addCell(3200)->addText($network->name);
            $table->addCell(3200)->addText($network->ssid);
            $table->addCell(3200)->addText($network->bssid);
        }
    }

    private function safeFilename(InternshipBatch $batch, string $suffix): string
    {
        return str($batch->batch_code.'-'.$suffix)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->toString();
    }
}
