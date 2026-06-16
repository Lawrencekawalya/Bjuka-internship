<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternshipBatch extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'expected_working_days',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => BatchStatus::class,
    ];

    public function interns(): HasMany
    {
        return $this->hasMany(Intern::class, 'batch_id');
    }

    public function approvedNetworks(): HasMany
    {
        return $this->hasMany(ApprovedNetwork::class, 'batch_id');
    }
}
