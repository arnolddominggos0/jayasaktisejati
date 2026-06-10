<?php

namespace App\Models;

use App\Enums\SlaStatus;
use App\Enums\VoyageDelayReason;
use App\Enums\VoyageOperationalStatus;
use App\Enums\VoyageRegistryStatus;
use App\Services\OperationalDaysHelper;
use App\Services\SlaEvaluator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voyage extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_plan_id',
        'vessel_plan_item_id',
        'shipping_line_id',

        'vessel_id',
        'pol_id',
        'pod_id',

        'voyage_no',
        'service',
        'etd',
        'eta',
        'etb',
        'cargo_plan',

        'atb_at',
        'atd_at',
        'ata_at',
        'cargo_actual',

        'period_month',
        'actual_sailing_days',

        'manual_delay_reason',
        'final_note',

        'closing_at',
        'registry_status',
        'archived_at',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'etb' => 'datetime',

        'atb_at' => 'datetime',
        'atd_at' => 'datetime',
        'ata_at' => 'datetime',

        'closing_at' => 'datetime',
        'period_month' => 'date',

        'actual_sailing_days' => 'decimal:2',
        'manual_delay_reason' => VoyageDelayReason::class,
        'registry_status' => VoyageRegistryStatus::class,
        'archived_at' => 'datetime',

        'cargo_plan' => 'integer',
        'cargo_actual' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (Voyage $voyage) {

            if (
                $voyage->isDirty('etd') ||
                $voyage->isDirty('eta') ||
                $voyage->isDirty('etb')
            ) {
                VoyageDelayLog::create([
                    'voyage_id'  => $voyage->id,
                    'old_etd'    => $voyage->getOriginal('etd'),
                    'new_etd'    => $voyage->etd,
                    'old_eta'    => $voyage->getOriginal('eta'),
                    'new_eta'    => $voyage->eta,
                    'new_etb'    => $voyage->etb,
                    'new_atb_at' => $voyage->atb_at,
                    'reason'     => $voyage->manual_delay_reason?->value,
                    'changed_by' => optional(auth_user())->name,
                ]);
            }

            if ($voyage->atd_at && $voyage->ata_at) {
                $voyage->actual_sailing_days = round(
                    $voyage->atd_at->diffInSeconds($voyage->ata_at) / 86400,
                    2
                );
            }

            if (
                $voyage->isDirty('cargo_actual') &&
                is_null($voyage->cargo_actual_reported_at ?? null)
            ) {
                $voyage->cargo_actual_reported_at = now();
                $voyage->cargo_actual_reported_by = optional(auth_user())->name;
            }
        });

        static::saved(function (Voyage $voyage) {

            if ($voyage->atd_at && $voyage->ata_at) {
                SlaEvaluator::evaluateVoyage($voyage);

                // Auto-save 'actual' schedule history ketika ATD & ATA tersedia.
                // updateOrCreate agar tidak duplikasi jika voyage di-update ulang.
                VoyageScheduleHistory::updateOrCreate(
                    [
                        'voyage_id'     => $voyage->id,
                        'schedule_type' => 'actual',
                    ],
                    [
                        'etd'          => $voyage->atd_at,
                        'eta'          => $voyage->ata_at,
                        'sailing_days' => VoyageScheduleHistory::calcSailingDays(
                            $voyage->atd_at,
                            $voyage->ata_at
                        ),
                        'notes'        => 'Actual — diambil dari ATD/ATA voyage',
                        'captured_at'  => now(),
                        'captured_by'  => optional(auth_user())->name ?? 'System',
                    ]
                );
            }

            if ($voyage->eta) {
                foreach ([-2, -1] as $offset) {
                    $voyage->checkpoints()->updateOrCreate(
                        [
                            'voyage_id' => $voyage->id,
                            'code' => 'eta_d' . abs($offset)
                        ],
                        [
                            'offset_days' => $offset,
                            'scheduled_at' => $voyage->eta->copy()->addDays($offset),
                        ]
                    );
                }
            }
        });

        static::updated(function (Voyage $voyage) {

            if ($voyage->wasChanged('atd_at') && $voyage->atd_at) {

                foreach ([2, 4, 6, 8, 10, 12] as $d) {

                    $voyage->milestones()->updateOrCreate(
                        [
                            'voyage_id' => $voyage->id,
                            'code' => "d{$d}"
                        ],
                        [
                            'milestone_date' => $voyage->atd_at->copy()->addDays($d)
                        ]
                    );
                }
            }
        });
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

    public function vesselPlanItem()
    {
        return $this->belongsTo(VesselPlanItem::class);
    }

    public function delayLogs(): HasMany
    {
        return $this->hasMany(VoyageDelayLog::class);
    }

    public function scheduleHistories(): HasMany
    {
        return $this->hasMany(VoyageScheduleHistory::class)->orderBy('schedule_type');
    }

    // ──────────────────────────────────────────────────────────────────────
    // Schedule Analysis — Vessel Plan (murni dari ETD/ETA, standar TAM)
    //
    // Standar TAM:
    //   Dwelling  = 6 hari  (konstanta — tidak dihitung, selalu 6)
    //   Sailing   = eta - etd (satu-satunya variabel)
    //   Dooring   = 3 hari  (konstanta — tidak dihitung, selalu 3)
    //   Lead Time = 6 + sailing + 3
    //   Standard  = 19 hari
    //   Variance  = lead_time - 19
    // ──────────────────────────────────────────────────────────────────────

    const TAM_DWELLING_STANDARD  = 6;
    const TAM_SAILING_STANDARD   = 10;
    const TAM_DOORING_STANDARD   = 3;
    const TAM_LEAD_TIME_STANDARD = 19;   // 6 + 10 + 3

    /**
     * Sailing Schedule (hari): Final ETD → Final ETA.
     * Ini satu-satunya komponen yang bervariasi per voyage.
     */
    public function getSailingScheduleDaysAttribute(): ?float
    {
        if (! $this->etd || ! $this->eta) {
            return null;
        }

        return round($this->etd->diffInSeconds($this->eta) / 86400, 1);
    }

    /**
     * Lead Time Schedule (hari) = Dwelling(6) + Sailing + Dooring(3).
     */
    public function getLeadTimeScheduleDaysAttribute(): ?float
    {
        $sailing = $this->sailing_schedule_days;

        if ($sailing === null) {
            return null;
        }

        return self::TAM_DWELLING_STANDARD + $sailing + self::TAM_DOORING_STANDARD;
    }

    /**
     * Variance dari Lead Time Standard TAM (19 hari).
     * Positif = lebih lama (buruk), Negatif = lebih cepat (baik).
     */
    public function getLeadTimeVarianceAttribute(): ?float
    {
        $lt = $this->lead_time_schedule_days;

        if ($lt === null) {
            return null;
        }

        return round($lt - self::TAM_LEAD_TIME_STANDARD, 1);
    }

    /**
     * Status analisa jadwal berdasarkan variance Lead Time.
     *   on_standard     : variance ≤ 0
     *   minor_deviation : variance 1–2
     *   high_deviation  : variance > 2
     */
    public function getScheduleAnalysisStatusAttribute(): string
    {
        $v = $this->lead_time_variance;

        if ($v === null) {
            return 'unknown';
        }

        if ($v <= 0) {
            return 'on_standard';
        }

        if ($v <= 2) {
            return 'minor_deviation';
        }

        return 'high_deviation';
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(VoyageMilestone::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(VoyageCheckpoint::class);
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

    public function vesselCheckCases(): HasMany
    {
        return $this->hasMany(VesselCheckCase::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'voyage_id');
    }

    public function getPlannedSailingDaysAttribute(): ?float
    {
        return $this->vesselPlanItem?->planned_sailing_days;
    }

    public function getCargoVarianceAttribute(): ?int
    {
        if ($this->cargo_actual === null || $this->cargo_plan === null) {
            return null;
        }

        return $this->cargo_actual - $this->cargo_plan;
    }

    public function getDepartureDelayDaysAttribute(): ?int
    {
        return OperationalDaysHelper::delayDays($this->etd, $this->atd_at);
    }

    public function getSailingDelayDaysAttribute(): ?float
    {
        if (!$this->planned_sailing_days || !$this->actual_sailing_days) {
            return null;
        }

        return round(
            $this->actual_sailing_days - $this->planned_sailing_days,
            2
        );
    }

    public function getSailingStatusAttribute(): string
    {
        if ($this->sailing_delay_days === null) {
            return 'unknown';
        }

        return $this->sailing_delay_days <= 0 ? 'ontime' : 'delay';
    }

    public function getIsDelayedAttribute(): bool
    {
        return ($this->departure_delay_days > 0)
            || ($this->sailing_delay_days > 0);
    }

    public function getDelayRootCauseAttribute(): ?string
    {
        $dep = $this->departure_delay_days;
        $sail = $this->sailing_delay_days;

        if (is_null($dep) && is_null($sail)) return null;

        if ($dep > 0 && $sail > 0) return 'MULTIPLE';
        if ($dep > 0) return 'PORT';
        if ($sail > 0) return 'SAILING';

        return 'ONTIME';
    }

    public function getDelayRootCauseLabelAttribute(): ?string
    {
        return match ($this->delay_root_cause) {
            'PORT' => 'Delay Pelabuhan',
            'SAILING' => 'Delay Pelayaran',
            'MULTIPLE' => 'Multiple Delay',
            'ONTIME' => 'Tepat Waktu',
            default => '-',
        };
    }

    public function getOperationalStatusEnumAttribute(): VoyageOperationalStatus
    {
        if ($this->ata_at) return VoyageOperationalStatus::COMPLETED;
        if ($this->atd_at) return VoyageOperationalStatus::SAILING;
        if ($this->etd && $this->etd->isPast() && !$this->atd_at) return VoyageOperationalStatus::DELAYED;
        return VoyageOperationalStatus::SCHEDULED;
    }

    public function getOverdueDaysAttribute(): ?int
    {
        if (
            $this->operational_status_enum === VoyageOperationalStatus::DELAYED
            && $this->etd?->isPast()
        ) {
            return $this->etd->diffInDays(now());
        }

        return null;
    }

    public function getSailingRiskAttribute(): bool
    {
        if (
            $this->operational_status_enum !== VoyageOperationalStatus::SAILING
            || !$this->eta
        ) {
            return false;
        }

        $days = now()->diffInDays($this->eta, false);

        return $days >= 0 && $days <= 1;
    }

    public function getEtaOverdueAttribute(): bool
    {
        return
            $this->operational_status_enum === VoyageOperationalStatus::SAILING
            && $this->eta
            && now()->gt($this->eta);
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

    public function getDepartureDelayMinutesAttribute(): ?int
    {
        if (!$this->etd || !$this->atd_at) {
            return null;
        }

        return $this->etd->diffInMinutes($this->atd_at);
    }

    public function getArrivalDelayMinutesAttribute(): ?int
    {
        if (!$this->eta || !$this->ata_at) {
            return null;
        }

        return $this->ata_at->diffInMinutes($this->eta, false);
    }

    public function getDepartureDelaySeverityAttribute(): ?string
    {
        return OperationalDaysHelper::severity($this->departure_delay_days);
    }

    public function getSlaStatusAttribute(): ?SlaStatus
    {
        return $this->sailingSla?->status;
    }

    public function getMilestoneSeverityAttribute(): string
    {
        $overdue = $this->milestones->where('is_overdue', true)->count();
        $dueToday = $this->milestones->where('is_due_today', true)->count();

        if ($overdue > 0) return 'critical';
        if ($dueToday > 0) return 'warning';

        return 'ontrack';
    }

    public function getDelayLabelAttribute(): ?string
    {
        return OperationalDaysHelper::delayLabel($this->departure_delay_days);
    }

    public function getRegistryStatusLabelAttribute(): ?string
    {
        return $this->registry_status?->label() ?? '—';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->registry_status === VoyageRegistryStatus::ACTIVE;
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->registry_status === VoyageRegistryStatus::CLOSED
            || $this->closing_at !== null;
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->registry_status === VoyageRegistryStatus::ARCHIVED
            || $this->archived_at !== null;
    }

    /**
     * Canonical operational state engine for this voyage.
     * Centralizes severity, readiness, KPI, and action evaluation.
     */
    public function getOperationalStateAttribute(): \App\Services\Operational\VoyageOperationalState
    {
        return \App\Services\Operational\VoyageOperationalState::for($this);
    }

    public function transitionRegistryStatus(VoyageRegistryStatus $newStatus): bool
    {
        if ($this->registry_status && ! $this->registry_status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->registry_status = $newStatus;

        if ($newStatus === VoyageRegistryStatus::CLOSED && ! $this->closing_at) {
            $this->closing_at = now();
        }

        if ($newStatus === VoyageRegistryStatus::ARCHIVED && ! $this->archived_at) {
            $this->archived_at = now();
        }

        return true;
    }

    public function scopeRegistryActive($query)
    {
        return $query->whereNotIn('registry_status', [
            VoyageRegistryStatus::CLOSED->value,
            VoyageRegistryStatus::ARCHIVED->value,
        ]);
    }

    public function scopeRegistryClosed($query)
    {
        return $query->whereIn('registry_status', [
            VoyageRegistryStatus::CLOSED->value,
            VoyageRegistryStatus::ARCHIVED->value,
        ]);
    }
}
