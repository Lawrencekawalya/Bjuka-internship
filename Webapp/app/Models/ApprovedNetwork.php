<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovedNetwork extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'batch_id',
        'name',
        'ssid',
        'bssid',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InternshipBatch::class, 'batch_id');
    }
}
