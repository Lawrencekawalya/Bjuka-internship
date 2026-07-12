<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternshipBatch extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'batch_code',
        'name',
        'description',
        'report_format_text',
        'report_format_path',
        'report_format_original_name',
        'start_date',
        'end_date',
        'capacity',
        'expected_working_days',
        'status',
        'coordinator_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => BatchStatus::class,
    ];

    protected $appends = [
        'virtual_status',
        'progress_percentage',
    ];

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function virtualStatus(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status === BatchStatus::ACTIVE && $this->start_date && $this->start_date->isFuture()) {
                return 'upcoming';
            }

            return $this->status->value;
        });
    }

    public function progressPercentage(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->start_date || ! $this->end_date) {
                return 0;
            }

            $today = now()->startOfDay();

            if ($today->isBefore($this->start_date)) {
                return 0;
            }

            if ($today->isAfter($this->end_date)) {
                return 100;
            }

            $totalDays = $this->start_date->diffInDays($this->end_date);
            $elapsedDays = $this->start_date->diffInDays($today);

            if ($totalDays <= 0) {
                return 100;
            }

            return round(($elapsedDays / $totalDays) * 100);
        });
    }

    public function interns(): HasMany
    {
        return $this->hasMany(Intern::class, 'batch_id');
    }

    public function approvedNetworks(): HasMany
    {
        return $this->hasMany(ApprovedNetwork::class, 'batch_id');
    }

    public function programWeeks(): HasMany
    {
        return $this->hasMany(InternshipProgramWeek::class, 'batch_id')->orderBy('week_number');
    }
}
