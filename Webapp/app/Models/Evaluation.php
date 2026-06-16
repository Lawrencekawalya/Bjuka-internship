<?php

namespace App\Models;

use App\Enums\EvaluationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'intern_id',
        'supervisor_id',
        'evaluation_type',
        'technical_score',
        'communication_score',
        'teamwork_score',
        'problem_solving_score',
        'conduct_score',
        'attendance_score',
        'strengths',
        'improvement_areas',
        'remarks',
    ];

    protected $casts = [
        'evaluation_type' => EvaluationType::class,
    ];

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
