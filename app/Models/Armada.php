<?php

namespace App\Models;

use App\Enums\ArmadaStatus;
use App\Enums\ArmadaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Armada extends Model
{
    protected $fillable = [
        'code','type','plate_number','capacity','status','branch_id','depot_id','notes'
    ];

    protected $casts = [
        'type'   => ArmadaType::class,
        'status' => ArmadaStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(ArmadaMaintenance::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ArmadaAssignment::class);
    }
}
