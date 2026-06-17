<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\BatchStatus;
use App\Enums\EvaluationType;
use App\Enums\InternStatus;
use App\Enums\NoteCategory;
use App\Enums\UserRole;
use App\Models\ApprovedNetwork;
use App\Models\Attendance;
use App\Models\DailyLearningLog;
use App\Models\Evaluation;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\InternSupervisorAssignment;
use App\Models\SupervisorNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InternshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create one user for each administrative/staff role
        User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@bjuka.io',
            'role' => UserRole::ADMIN,
        ]);

        User::factory()->create([
            'name' => 'HR Manager',
            'email' => 'hr@bjuka.io',
            'role' => UserRole::HR,
        ]);

        User::factory()->create([
            'name' => 'Project Manager',
            'email' => 'manager@bjuka.io',
            'role' => UserRole::MANAGER,
        ]);

        User::factory()->create([
            'name' => 'Center Director',
            'email' => 'director@bjuka.io',
            'role' => UserRole::CENTER_DIRECTOR,
        ]);

        // 2. Create Supervisors (Keeping a few for assignments)
        $supervisors = User::factory()->count(3)->create([
            'role' => UserRole::SUPERVISOR,
        ]);

        // Specific supervisor for testing
        $supervisors->first()->update([
            'name' => 'Lead Supervisor',
            'email' => 'supervisor@bjuka.io',
        ]);

        // 3. Create 2 Internship Batches
        $batches = [
            InternshipBatch::factory()->create([
                'batch_code' => 'SPRING-2026',
                'name' => 'Spring 2026 Batch',
                'status' => BatchStatus::ACTIVE,
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->addMonths(1),
                'capacity' => 20,
            ]),
            InternshipBatch::factory()->create([
                'batch_code' => 'WINTER-2025',
                'name' => 'Winter 2025 Batch',
                'status' => BatchStatus::CLOSED,
                'start_date' => Carbon::now()->subMonths(6),
                'end_date' => Carbon::now()->subMonths(3),
                'capacity' => 15,
            ]),
        ];

        foreach ($batches as $batch) {
            // 4. Create 1 Approved Network per Batch
            ApprovedNetwork::factory()->create([
                'batch_id' => $batch->id,
                'name' => $batch->name.' Office WiFi',
                'ssid' => 'BJUKA_WIFI_'.strtoupper(substr($batch->id, 0, 4)),
            ]);

            // 5. Create Interns (10 total)
            $interns = Intern::factory()->count(5)->create([
                'batch_id' => $batch->id,
                'status' => InternStatus::ACTIVE,
            ]);

            foreach ($interns as $intern) {
                // Assign role to the user associated with the intern
                $intern->user->update(['role' => UserRole::INTERN]);

                // 6. Create Supervisor Assignments
                $supervisor = $supervisors->random();
                InternSupervisorAssignment::create([
                    'intern_id' => $intern->id,
                    'supervisor_id' => $supervisor->id,
                    'assigned_at' => now(),
                ]);

                // 7. Generate heavy data for interns in the active batch
                if ($batch->status === BatchStatus::ACTIVE) {
                    $this->generateInternActivity($intern, $supervisor);
                }
            }
        }
    }

    private function generateInternActivity(Intern $intern, User $assignedSupervisor): void
    {
        $startDate = $intern->batch->start_date;
        $numDays = rand(20, 30);

        for ($i = 0; $i < $numDays; $i++) {
            $currentDate = (clone $startDate)->addDays($i);

            // Skip weekends
            if ($currentDate->isWeekend()) {
                continue;
            }
            if ($currentDate->isAfter(Carbon::now())) {
                break;
            }

            // 90% attendance rate
            if (rand(1, 100) > 10) {
                // Realistic check-in around 08:00 - 09:00
                $checkInBase = (clone $currentDate)->setHour(rand(8, 9))->setMinute(rand(0, 59));

                // Determine status based on time
                $status = AttendanceStatus::PRESENT;
                if ($checkInBase->hour >= 9 && $checkInBase->minute > 0) {
                    $status = AttendanceStatus::LATE;
                }

                $checkOutBase = (clone $checkInBase)->addHours(rand(7, 9));
                $duration = $checkInBase->diffInMinutes($checkOutBase);

                $attendance = Attendance::create([
                    'intern_id' => $intern->id,
                    'date' => $currentDate->toDateString(),
                    'check_in_device_time' => $checkInBase->subMinutes(rand(0, 5)),
                    'check_in_server_time' => $checkInBase,
                    'check_out_device_time' => $checkOutBase->addMinutes(rand(0, 5)),
                    'check_out_server_time' => $checkOutBase,
                    'work_duration_minutes' => $duration,
                    'status' => $status,
                    'wifi_ssid' => $intern->batch->approvedNetworks->first()->ssid,
                    'wifi_bssid' => '00:11:22:33:44:55',
                ]);

                // 8. Daily Learning Logs
                DailyLearningLog::factory()->create([
                    'attendance_id' => $attendance->id,
                ]);
            }
        }

        // 9. Supervisor Notes (2-4 per intern)
        SupervisorNote::factory()->count(rand(2, 4))->create([
            'intern_id' => $intern->id,
            'supervisor_id' => $assignedSupervisor->id,
            'category' => NoteCategory::TECHNICAL,
        ]);

        // 10. Periodic Evaluations (1 per intern)
        Evaluation::factory()->create([
            'intern_id' => $intern->id,
            'supervisor_id' => $assignedSupervisor->id,
            'evaluation_type' => EvaluationType::PERIODIC,
        ]);
    }
}
