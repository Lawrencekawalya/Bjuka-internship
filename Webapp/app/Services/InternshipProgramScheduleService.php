<?php

namespace App\Services;

use App\Models\InternshipBatch;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InternshipProgramScheduleService
{
    public function ensureDefaultSchedule(InternshipBatch $batch): Collection
    {
        if ($batch->programWeeks()->exists()) {
            return $batch->programWeeks()->get();
        }

        DB::transaction(function () use ($batch): void {
            if ($batch->programWeeks()->lockForUpdate()->exists()) {
                return;
            }

            foreach ($this->defaultRows($batch->start_date) as $row) {
                $batch->programWeeks()->create($row);
            }
        });

        return $batch->programWeeks()->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultRows(?CarbonInterface $batchStartDate): array
    {
        $start = $batchStartDate?->copy()->startOfDay() ?? now()->startOfDay();

        return collect($this->defaultContent())
            ->map(function (array $content, int $index) use ($start) {
                $weekStart = $start->copy()->addWeeks($index);
                $weekEnd = $weekStart->copy()->addDays(4);

                return [
                    'week_number' => $index + 1,
                    'title' => $content['title'],
                    'start_date' => $weekStart->toDateString(),
                    'end_date' => $weekEnd->toDateString(),
                    'objectives' => $content['objectives'],
                    'topics' => $content['topics'],
                    'activities' => $content['activities'],
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultContent(): array
    {
        return [
            [
                'title' => 'Hardware Repair & Maintenance Fundamentals',
                'objectives' => "- Understand computer hardware components\n- Learn computer safety procedures\n- Perform basic maintenance and troubleshooting",
                'topics' => "Computer architecture\nMotherboard components\nCPU and RAM installation\nHDD vs SSD\nPower Supply Unit (PSU)\nLaptop disassembly\nDesktop assembly\nBIOS settings\nPreventive maintenance\nDiagnosing hardware faults",
                'activities' => "Assemble/disassemble desktop\nReplace RAM, HDD/SSD & PSU\nProfessional cleaning\nTroubleshoot boot failures",
            ],
            [
                'title' => 'Cabling & Network Installation',
                'objectives' => "- Learn networking basics\n- Install/configure small office networks",
                'topics' => "Networking fundamentals\nLAN vs WAN\nIP Addressing\nRouters\nSwitches\nEthernet standards\nT568A & T568B\nCable testing\nPrinter sharing\nFile sharing\nNetwork troubleshooting",
                'activities' => "Terminate Ethernet cables\nTest cables\nConfigure two PCs\nShare folders\nShare printers\nConfigure static IPs",
            ],
            [
                'title' => 'Desktop Management',
                'objectives' => 'Learn Windows administration and maintenance',
                'topics' => "Windows installation\nDriver installation\nWindows Updates\nUser Accounts\nDisk Management\nDevice Manager\nBackup & Restore\nAntivirus\nSoftware installation\nPerformance optimization",
                'activities' => "Install Windows 11\nInstall drivers\nPartition drive\nInstall Office\nCreate users\nOptimize startup\nConfigure Local Users and Groups",
            ],
            [
                'title' => 'Frontend Development',
                'objectives' => 'Learn how websites are built',
                'topics' => "HTML5\nCSS3\nResponsive Design\nFlexbox\nCSS Grid\nJavaScript Basics\nDOM Manipulation\nForms\nGit Basics",
                'activities' => "Build: Personal Portfolio, Business Website, Contact Form, Product Landing Page\nMini Project: B. JUKA Technologies Homepage",
            ],
            [
                'title' => 'Backend Development & Introduction to Flutter',
                'objectives' => "Consolidate backend web development concepts.\nUnderstand the fundamentals of Flutter and cross-platform mobile application development.\nLearn Flutter project structure, widgets, and navigation.\nBuild a simple mobile application using Flutter.",
                'topics' => "Backend Development Review\n- JavaScript Functions\n- CRUD Operations\n- Browser Local Storage\n- Database Fundamentals\n- Web Project Structure\n\nIntroduction to Flutter\n- What is Flutter?\n- Installing Flutter & Android Studio\n- Flutter Project Structure\n- Dart Basics\n- Widgets (Stateless & Stateful)\n- Layouts (Row, Column, Container)\n- Navigation Between Screens\n- Forms & User Input",
                'activities' => "Build a Login Screen.\nBuild a Dashboard Screen.\nCreate a Customer Registration Form.\nImplement Navigation Between Pages.\nBuild a Simple Repair Management Mobile App (UI Only).\nRun the application on an Android Emulator or Physical Device.",
            ],
            [
                'title' => 'CCTV Camera Installation & Final Project',
                'objectives' => "Install/configure surveillance systems\nIntegrate previous learning",
                'topics' => "Types of CCTV\nDVR vs NVR\nCamera Positioning\nCable Routing\nPower Supply\nIP Cameras\nNetwork Configuration\nRemote Viewing\nMaintenance\nTroubleshooting",
                'activities' => "Install 4 cameras\nConfigure DVR/NVR\nRemote viewing\nTest recording\nFault diagnosis\nFinal Project: PC repair, Windows install, LAN setup, frontend website, backend repair system, CCTV installation, presentation",
            ],
        ];
    }
}
