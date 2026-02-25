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
        'atd_at' => 'datetime',
        'ata_at' => 'datetime',
        'atb_at' => 'datetime',
        'closing_at' => 'datetime',
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

            if ($voyage->exists && (
                $voyage->isDirty('etd') ||
                $voyage->isDirty('eta') ||
                $voyage->isDirty('etb')
            )) {

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
                $voyage->cargo_actual_reported_by = optional(auth()->user())->name;
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
        static::updated(function ($voyage) {

            if ($voyage->wasChanged('atd_at') && $voyage->atd_at) {

                $days = [4, 6, 8, 10, 12];

                foreach ($days as $d) {

                    $voyage->milestones()->updateOrCreate(
                        ['code' => "d{$d}"],
                        [
                            'milestone_date' =>
                            $voyage->atd_at->copy()->addDays($d)
                        ]
                    );
                }
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

    public function milestones()
    {
        return $this->hasMany(VoyageMilestone::class);
    }

    public function getOperationalStatusEnumAttribute(): VoyageOperationalStatus
    {
        if ($this->ata_at) {
            return VoyageOperationalStatus::COMPLETED;
        }

        if ($this->atd_at && ! $this->ata_at) {
            return VoyageOperationalStatus::SAILING;
        }

        if ($this->etd && $this->etd->isPast() && ! $this->atd_at) {
            return VoyageOperationalStatus::DELAYED;
        }

        return VoyageOperationalStatus::SCHEDULED;
    }

    public function getOperationalStatusAttribute(): string
    {
        return $this->operational_status_enum->value;
    }

    public function getOtbStatusAttribute(): ?SlaStatus
    {
        if (!$this->etb || !$this->atb_at) {
            return null;
        }

        return $this->atb_at->lte($this->etb)
            ? SlaStatus::ONTIME
            : SlaStatus::LATE;
    }

    public function getOtdStatusAttribute(): ?SlaStatus
    {
        if (!$this->etd || !$this->atd_at) {
            return null;
        }

        return $this->atd_at->lte($this->etd)
            ? SlaStatus::ONTIME
            : SlaStatus::LATE;
    }

    public function getOtaStatusAttribute(): ?SlaStatus
    {
        if (!$this->eta || !$this->ata_at) {
            return null;
        }

        return $this->ata_at->lte($this->eta)
            ? SlaStatus::ONTIME
            : SlaStatus::LATE;
    }

    public function getOverdueDaysAttribute(): ?int
    {
        if (
            $this->operational_status_enum === VoyageOperationalStatus::DELAYED &&
            $this->etd?->isPast()
        ) {
            return $this->etd->diffInDays(now());
        }

        return null;
    }

    public function getSlaStatusAttribute(): ?SlaStatus
    {
        return $this->sailingSla?->status;
    }

    public function getSailingRiskAttribute(): bool
    {
        return
            $this->operational_status_enum === VoyageOperationalStatus::SAILING &&
            $this->eta &&
            now()->diffInHours($this->eta, false) < 24;
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(VoyageCheckpoint::class);
    }

    public function getMilestonesAttribute(): array
    {
        if (! $this->checkpoints()->exists()) {
            return [];
        }
        return [
            'd4'  => $this->atd_at->copy()->addDays(4),
            'd6'  => $this->atd_at->copy()->addDays(6),
            'd8'  => $this->atd_at->copy()->addDays(8),
            'd10' => $this->atd_at->copy()->addDays(10),
            'd12' => $this->atd_at->copy()->addDays(12),
        ];
    }
}
