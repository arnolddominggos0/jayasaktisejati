<?php

namespace App\Models;

use App\Enums\MPDomain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manpower extends Model
{
    protected $fillable = [
        'appsheet_id',
        'name',
        'domain',
        'skills',
        'certs',
        'phone',
        'license_number',
        'branch_id',
        'depot_id',
        'active',
    ];

    protected $casts = [
        'domain' => MPDomain::class,
        'skills' => 'array',
        'certs'  => 'array',
        'active' => 'bool',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function attendances()
    {
        return $this->hasMany(ManpowerAttendance::class, 'manpower_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ManpowerAssignment::class);
    }
}
