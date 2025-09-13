<?php

namespace App\Models;

use App\Enums\ArmadaType;
use App\Models\Branch;
use App\Models\Manpower;
use App\Models\ShipSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Armada extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'plate_number',
        'name',
        'capacity',
        'branch_id'
    ];

    protected $casts = [
        'type' => ArmadaType::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function manpowers(): BelongsToMany
    {
        return $this->belongsToMany(Manpower::class)->withTimestamps();
    }

    public function shipSchedules(): HasMany
    {
        return $this->hasMany(ShipSchedule::class);
    }
}
