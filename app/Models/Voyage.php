<?php

namespace App\Models;

use App\Enums\SlaStatus;
use App\Enums\VoyageDelayReason;
use App\Enums\VoyageOperationalStatus;
use App\Services\SlaEvaluator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Voyage extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_plan_id',
        'shipping_line_id',
        'vessel_id',
        'pol_id',
        'pod_id',
        'voyage_no',
        'etd',
        'eta',
        'etb',
        'atb_at',
        'period_month',
        'atd_at',
        'ata_at',
        'actual_sailing_days',
        'is_delayed',
        'delay_reason',
        'cargo_plan',
        'cargo_actual',
        'cargo_actual_reported_at',
        'cargo_actual_reported_by',
        'final_note',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'etb' => 'datetime',
        'atb_at' => 'datetime',
        'atd_at' => 'datetime',
        'ata_at' => 'datetime',
        'period_month' => 'date',
        'actual_sailing_days' => 'decimal:2',
        'is_delayed' => 'boolean',
        'delay_reason' => VoyageDelayReason::class,
        'cargo_plan' => 'integer',
        'cargo_actual' => 'integer',
        'cargo_actual_reported_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (Voyage $voyage) {

            $etdChanged = $voyage->isDirty('etd');
            $etaChanged = $voyage->isDirty('eta');
            $etbChanged = $voyage->isDirty('etb');

            if ($voyage->exists && ($etdChanged || $etaChanged || $etbChanged)) {

                VoyageDelayLog::create([
                    'voyage_id'   => $voyage->id,
                    'old_etd'     => $voyage->getOriginal('etd'),
                    'new_etd'     => $voyage->etd,
                    'old_eta'     => $voyage->getOriginal('eta'),
                    'new_eta'     => $voyage->eta,
                    'new_etb'     => $voyage->etb,
                    'new_atb_at'  => $voyage->atb_at,
                    'reason'      => $voyage->delay_reason?->value,
                    'changed_by'  => optional(auth()->user())->name,
                ]);

                $voyage->is_delayed = true;
            }

            if ($voyage->atd_at) {
                $end = $voyage->ata_at ?? now();
                $voyage->actual_sailing_days = round(
                    $voyage->atd_at->diffInSeconds($end) / 86400,
                    2
                );
            }

            if (
                $voyage->isDirty('cargo_actual') &&
                is_null($voyage->cargo_actual_reported_at)
            ) {
                $voyage->cargo_actual_reported_at = now();
                $voyage->cargo_actual_reported_by = optional(auth_user())->name;
            }
        });

        static::saved(function (Voyage $voyage) {

            if ($voyage->atd_at && $voyage->ata_at) {
                SlaEvaluator::evaluateVoyage($voyage);
            }

            if ($voyage->eta) {

                $voyage->checkpoints()
                    ->whereIn('type', ['eta_d2', 'eta_d1'])
                    ->delete();

                $voyage->checkpoints()->createMany([
                    [
                        'type' => 'eta_d2',
                        'title' => 'Reminder ETA D-2',
                        'scheduled_at' => $voyage->eta->copy()->subDays(2),
                    ],
                    [
                        'type' => 'eta_d1',
                        'title' => 'Reminder ETA D-1',
                        'scheduled_at' => $voyage->eta->copy()->subDay(),
                    ],
                ]);
            }
        });
    }

    public function delayLogs(): HasMany
    {
        return $this->hasMany(VoyageDelayLog::class);
    }

    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
    }

    public function pol()
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod()
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public function sailingSla()
    {
        return $this->hasOne(SlaResult::class)
            ->where('activity', 'sailing');
    }

    public function slaResults()
    {
        return $this->hasMany(SlaResult::class);
    }

    public function vesselChecks(): HasMany
    {
        return $this->hasMany(VesselCheck::class);
    }

    public function getOperationalStatusAttribute(): string
    {
        if ($this->ata_at) {
            return VoyageOperationalStatus::COMPLETED->value;
        }

        if ($this->atd_at && ! $this->ata_at) {
            return VoyageOperationalStatus::SAILING->value;
        }

        if ($this->etd && $this->etd->isPast() && ! $this->atd_at) {
            return VoyageOperationalStatus::DELAYED->value;
        }

        return VoyageOperationalStatus::SCHEDULED->value;
    }

    public function getOperationalStatusLabelAttribute(): string
    {
        return VoyageOperationalStatus::from($this->operational_status)->label();
    }

    public function getOperationalStatusEnumAttribute(): VoyageOperationalStatus
    {
        return VoyageOperationalStatus::from($this->operational_status);
    }

    public function getOtbStatusAttribute(): ?string
    {
        if ($this->etb === null || $this->atb_at === null) {
            return null;
        }

        return $this->atb_at->lte($this->etb) ? 'ontime' : 'late';
    }

    public function getOtdStatusAttribute(): ?string
    {
        if ($this->etd === null || $this->atd_at === null) {
            return null;
        }

        return $this->atd_at->lte($this->etd) ? 'ontime' : 'late';
    }

    public function getOtaStatusAttribute(): ?string
    {
        if ($this->eta === null || $this->ata_at === null) {
            return null;
        }

        return $this->ata_at->lte($this->eta) ? 'ontime' : 'late';
    }

    public function getOverdueDaysAttribute(): ?int
    {
        if (
            $this->operational_status === VoyageOperationalStatus::DELAYED->value &&
            $this->etd instanceof Carbon &&
            $this->etd->isPast()
        ) {
            return $this->etd->diffInDays(now());
        }

        return null;
    }

    public function getSlaStatusAttribute(): ?SlaStatus
    {
        return $this->sailingSla?->status;
    }

    public function getDelaySeverityAttribute(): string
    {
        if (!$this->overdue_days) return 'none';

        return match (true) {
            $this->overdue_days > 7 => 'critical',
            $this->overdue_days >= 3 => 'medium',
            default => 'minor',
        };
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(VoyageCheckpoint::class);
    }

    public function getSailingRiskAttribute(): bool
    {
        if (
            $this->operational_status === 'sailing' &&
            $this->eta &&
            now()->diffInHours($this->eta, false) < 24
        ) {
            return true;
        }

        return false;
    }
}
