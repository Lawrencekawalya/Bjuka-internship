<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:recalculate-statuses {--commit : Persist the recalculated statuses}', function () {
    $threshold = '09:30:00';
    $changes = DB::table('attendances')
        ->whereNotNull('check_in_server_time')
        ->select('id', 'status', 'check_in_server_time')
        ->get()
        ->map(function (object $attendance) use ($threshold) {
            $time = date('H:i:s', strtotime((string) $attendance->check_in_server_time));
            $status = $time > $threshold ? 'late' : 'present';

            return [
                'id' => $attendance->id,
                'old_status' => $attendance->status,
                'new_status' => $status,
            ];
        })
        ->filter(fn (array $change) => $change['old_status'] !== $change['new_status'])
        ->values();

    if ($changes->isEmpty()) {
        $this->info('No attendance statuses need recalculation.');

        return self::SUCCESS;
    }

    if (! $this->option('commit')) {
        $this->warn("Dry run: {$changes->count()} attendance status(es) would be updated. Re-run with --commit to persist.");

        return self::SUCCESS;
    }

    DB::transaction(function () use ($changes): void {
        foreach ($changes as $change) {
            DB::table('attendances')
                ->where('id', $change['id'])
                ->update([
                    'status' => $change['new_status'],
                    'updated_at' => now(),
                ]);
        }
    });

    $this->info("Updated {$changes->count()} attendance status(es).");

    return self::SUCCESS;
})->purpose('Recalculate existing attendance statuses using the 9:30 AM late threshold');
