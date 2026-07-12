<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipProgramWeek extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'batch_id',
        'week_number',
        'title',
        'start_date',
        'end_date',
        'objectives',
        'topics',
        'activities',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InternshipBatch::class, 'batch_id');
    }
}
