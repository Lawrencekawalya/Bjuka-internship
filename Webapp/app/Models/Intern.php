<?php

namespace App\Models;

use App\Enums\InternStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'certificate_path',
    ];

    protected $casts = [
        'status' => InternStatus::class,
    ];

    protected $appends = [
        'certificate_url',
    ];

    protected function certificateUrl(): Attribute
    {
        return Attribute::get(fn () => $this->certificate_path
            ? Storage::disk('public')->url($this->certificate_path)
            : null);
    }

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

    public function reportGenerationQuota(): HasOne
    {
        return $this->hasOne(InternReportGenerationQuota::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(InternReport::class);
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
