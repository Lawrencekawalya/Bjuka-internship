<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternReportGenerationQuota extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'intern_id',
        'generation_count',
        'generation_limit',
        'reset_requested_at',
        'reset_approved_at',
        'reset_used',
        'permanently_locked_at',
    ];

    protected $casts = [
        'generation_count' => 'integer',
        'generation_limit' => 'integer',
        'reset_requested_at' => 'datetime',
        'reset_approved_at' => 'datetime',
        'reset_used' => 'boolean',
        'permanently_locked_at' => 'datetime',
    ];

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function remainingGenerations(): int
    {
        return max($this->generation_limit - $this->generation_count, 0);
    }

    public function canGenerate(): bool
    {
        return $this->permanently_locked_at === null
            && $this->remainingGenerations() > 0;
    }

    public function canRequestReset(): bool
    {
        return ! $this->reset_used
            && $this->remainingGenerations() === 0
            && $this->reset_requested_at === null
            && $this->permanently_locked_at === null;
    }
}
