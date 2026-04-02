<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(VesselPlan::class, 'vessel_plan_id');
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

    public function getGapToNextAttribute(): ?int
    {
        if (! $this->relationLoaded('plan') && ! $this->plan) {
            return null;
        }

        $plan = $this->plan;

        if (! $plan || ! $plan->exists) {
            return null;
        }

        $next = $plan->items()
            ->where('planned_etd', '>', $this->planned_etd)
            ->orderBy('planned_etd')
            ->first();

        return $next
            ? $this->planned_etd->diffInDays($next->planned_etd)
            : null;
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

    public function getPlannedDwellingDaysAttribute(): int
    {
        return config('kpi.manado.thresholds.dwelling_days', 6);
    }

    public function getPlannedDooringDaysAttribute(): int
    {
        return config('kpi.manado.thresholds.dooring_days', 3);
    }

    public function getPlannedTotalDaysAttribute(): ?float
    {
        if (!$this->planned_sailing_days) {
            return null;
        }

        return round(
            $this->planned_dwelling_days +
                $this->planned_sailing_days +
                $this->planned_dooring_days,
            2
        );
    }

    public function getSopStatusAttribute(): string
    {
        $total = $this->planned_total_days;

        if (!$total) {
            return 'unknown';
        }

        $limit = config('kpi.manado.thresholds.total_days.normal', 19);

        return $total <= $limit ? 'ok' : 'late';
    }

    public function getSopStatusLabelAttribute(): string
    {
        return match ($this->sop_status) {
            'ok' => 'SESUAI SOP',
            'late' => 'MELEBIHI SOP',
            default => 'UNKNOWN',
        };
    }
    
    protected static function booted(): void
    {
        static::deleting(function (VesselPlanItem $item) {
            if ($item->voyage) {
                $item->voyage->delete();
            }
        });
    }
}
