<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InternReport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'intern_id',
        'status',
        'content',
        'docx_path',
        'failure_reason',
        'generated_at',
    ];

    protected $casts = [
        'content' => 'array',
        'generated_at' => 'datetime',
    ];

    protected $appends = [
        'download_url',
    ];

    public function intern(): BelongsTo
    {
        return $this->belongsTo(Intern::class);
    }

    protected function downloadUrl(): Attribute
    {
        return Attribute::get(fn () => $this->docx_path
            ? Storage::disk('public')->url($this->docx_path)
            : null);
    }
}
