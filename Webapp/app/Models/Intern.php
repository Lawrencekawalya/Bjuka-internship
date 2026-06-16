<?php

namespace App\Models;

use App\Enums\InternStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intern extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'batch_id',
        'phone',
        'institution',
        'course',
        'registration_number',
        'status',
    ];

    protected $casts = [
        'status' => InternStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InternshipBatch::class, 'batch_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function supervisorNotes(): HasMany
    {
        return $this->hasMany(SupervisorNote::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function supervisorAssignments(): HasMany
    {
        return $this->hasMany(InternSupervisorAssignment::class);
    }

    public function supervisors(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            InternSupervisorAssignment::class,
            'intern_id',
            'id',
            'id',
            'supervisor_id'
        );
    }
}
