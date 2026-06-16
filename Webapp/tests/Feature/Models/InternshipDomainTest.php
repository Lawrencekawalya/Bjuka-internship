<?php

namespace Tests\Feature\Models;

use App\Models\ApprovedNetwork;
use App\Models\Attendance;
use App\Models\DailyLearningLog;
use App\Models\Evaluation;
use App\Models\Intern;
use App\Models\InternshipBatch;
use App\Models\SupervisorNote;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InternshipDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_batch_with_networks_and_interns()
    {
        $batch = InternshipBatch::factory()->create();
        $network = ApprovedNetwork::factory()->create(['batch_id' => $batch->id]);
        $intern = Intern::factory()->create(['batch_id' => $batch->id]);

        $this->assertCount(1, $batch->approvedNetworks);
        $this->assertCount(1, $batch->interns);
        $this->assertEquals($batch->id, $network->batch->id);
        $this->assertEquals($batch->id, $intern->batch->id);
    }

    public function test_it_can_track_attendance_and_learning_logs()
    {
        $intern = Intern::factory()->create();
        $attendance = Attendance::factory()->create(['intern_id' => $intern->id]);
        $log = DailyLearningLog::factory()->create(['attendance_id' => $attendance->id]);

        $this->assertCount(1, $intern->attendances);
        $this->assertEquals($attendance->id, $log->attendance->id);
        $this->assertEquals($log->id, $attendance->learningLog->id);
    }

    public function test_it_can_add_supervisor_notes_and_evaluations()
    {
        $supervisor = User::factory()->create();
        $intern = Intern::factory()->create();

        $note = SupervisorNote::factory()->create([
            'intern_id' => $intern->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $evaluation = Evaluation::factory()->create([
            'intern_id' => $intern->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $this->assertCount(1, $intern->supervisorNotes);
        $this->assertCount(1, $intern->evaluations);
        $this->assertEquals($supervisor->id, $note->supervisor->id);
        $this->assertEquals($supervisor->id, $evaluation->supervisor->id);
    }

    public function test_it_soft_deletes_notes_and_evaluations()
    {
        $note = SupervisorNote::factory()->create();
        $evaluation = Evaluation::factory()->create();

        $note->delete();
        $evaluation->delete();

        $this->assertSoftDeleted($note);
        $this->assertSoftDeleted($evaluation);
    }

    public function test_it_uses_uuids_for_primary_keys()
    {
        $batch = InternshipBatch::factory()->create();
        $this->assertIsString($batch->id);
        $this->assertTrue(Str::isUuid($batch->id));
    }

    public function test_it_prevents_duplicate_attendance_on_same_day()
    {
        $intern = Intern::factory()->create();
        $date = now()->toDateString();

        Attendance::factory()->create([
            'intern_id' => $intern->id,
            'date' => $date,
        ]);

        $this->expectException(QueryException::class);

        Attendance::factory()->create([
            'intern_id' => $intern->id,
            'date' => $date,
        ]);
    }
}
