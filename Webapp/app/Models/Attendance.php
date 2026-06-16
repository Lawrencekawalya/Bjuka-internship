<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'intern_id',
        'date',
        'check_in_device_time',
        'check_in_server_time',
        'check_out_device_time',
        'check_out_server_time',
        'work_duration_minutes',
        'status',
        'wifi_ssid',
        'wifi_bssid',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_device_time' => 'datetime',
        'check_in_server_time' => 'datetime',
        'check_out_device_time' => 'datetime',
        'check_out_server_time' => 'datetime',
        'status' => AttendanceStatus::class,
    ];

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function learningLog(): HasOne
    {
        return $this->hasOne(DailyLearningLog::class);
    }
}
