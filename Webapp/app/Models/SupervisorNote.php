<?php

namespace App\Models;

use App\Enums\NoteCategory;
use App\Enums\NoteVisibility;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupervisorNote extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'intern_id',
        'supervisor_id',
        'note_date',
        'observation',
        'category',
        'visibility',
    ];

    protected $casts = [
        'note_date' => 'date',
        'category' => NoteCategory::class,
        'visibility' => NoteVisibility::class,
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
