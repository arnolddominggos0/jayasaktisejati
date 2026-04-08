<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class VesselPlanItem extends Model
{
    protected $fillable = [
        'vessel_plan_id',
        'shipping_line_id',
        'vessel_id',
        'planned_etd',
        'planned_eta',
        'note',
    ];

    protected $casts = [
        'planned_etd' => 'datetime',
        'planned_eta' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function ($item) {
            if ($item->planned_eta && $item->planned_etd) {
                if ($item->planned_eta <= $item->planned_etd) {
                    throw ValidationException::withMessages([
                        'planned_eta' => 'ETA harus lebih besar dari ETD'
                    ]);
                }
            }
        });

        static::deleting(function (VesselPlanItem $item) {
            if ($item->voyage) {
                $item->voyage->delete();
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(VesselPlan::class);
    }

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function voyage(): HasOne
    {
        return $this->hasOne(Voyage::class);
    }

    public function getPlannedSailingDaysAttribute(): ?float
    {
        if (!$this->planned_etd || !$this->planned_eta) {
            return null;
        }

        return round(
            $this->planned_etd->diffInSeconds($this->planned_eta) / 86400,
            2
        );
    }
}