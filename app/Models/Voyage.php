<?php

namespace App\Models;

use App\Actions\CreateShippingSchedule;
use App\Enums\VoyageDelayReason;
use App\Enums\VoyageOperationalStatus;
use App\Services\SlaEvaluator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        'period_month',
        'atd_at',
        'ata_at',
        'actual_sailing_days',
        'is_delayed',
        'delay_reason',
        'cargo_actual',
        'cargo_actual_reported_at',
        'cargo_actual_reported_by',
    ];


    protected $attributes = [
        'kpi_sailing_days' => 10,
    ];

    protected $casts = [
        'etd'                     => 'datetime',
        'eta'                     => 'datetime',
        'atd_at'                  => 'datetime',
        'ata_at'                  => 'datetime',
        'period_month'            => 'date',
        'actual_sailing_days'     => 'decimal:2',
        'is_delayed'              => 'boolean',
        'delay_reason'            => VoyageDelayReason::class,
        'cargo_actual'            => 'integer',
        'cargo_actual_reported_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Voyage $voyage) {

            if ($voyage->isDirty(['etd', 'eta']) && $voyage->exists) {

                VoyageDelayLog::create([
                    'voyage_id' => $voyage->id,
                    'old_etd'   => $voyage->getOriginal('etd'),
                    'new_etd'   => $voyage->etd,
                    'old_eta'   => $voyage->getOriginal('eta'),
                    'new_eta'   => $voyage->eta,
                    'reason'    => $voyage->delay_reason?->value,
                    'changed_by' => auth_user()?->name,
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
                $voyage->cargo_actual_reported_by = auth_user()?->name;
            }
        });


        static::saved(function (Voyage $voyage) {
            if (
                $voyage->wasChanged(['atd_at', 'ata_at']) &&
                $voyage->ata_at
            ) {
                SlaEvaluator::evaluateVoyage($voyage);
            }
        });

        static::updating(function (Voyage $voyage) {

            if (
                $voyage->is_delayed &&
                ($voyage->isDirty('etd') || $voyage->isDirty('eta'))
            ) {
                VoyageDelayLog::create([
                    'voyage_id' => $voyage->id,
                    'old_etd'   => $voyage->getOriginal('etd'),
                    'new_etd'   => $voyage->etd,
                    'old_eta'   => $voyage->getOriginal('eta'),
                    'new_eta'   => $voyage->eta,
                    'reason'    => $voyage->delay_reason?->value,
                    'changed_by' => auth_user()?->name,
                ]);
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

    public function vesselPlan()
    {
        return $this->belongsTo(VesselPlan::class, 'vessel_plan_id');
    }

    public function vesselPlanItem()
    {
        return $this->belongsTo(VesselPlanItem::class, 'vessel_plan_item_id');
    }

    public function vesselChecks(): HasMany
    {
        return $this->hasMany(VesselCheck::class);
    }

    public function pol()
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod()
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public function getElapsedSailingDaysAttribute(): ?int
    {
        if (! $this->atd_at) {
            return null;
        }

        return $this->atd_at->diffInDays($this->ata_at ?? now());
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

    public function getOperationalStatusColorAttribute(): string
    {
        return VoyageOperationalStatus::from($this->operational_status)->color();
    }

    public function getRiskLevelAttribute(): string
    {
        if (! $this->atd_at || $this->ata_at) {
            return 'none';
        }

        if ($this->elapsed_sailing_days > 10) {
            return 'risk';
        }

        if ($this->elapsed_sailing_days >= 8) {
            return 'warning';
        }

        return 'normal';
    }

    public function getSlaDaysAttribute(): ?int
    {
        if (! $this->etd || ! $this->ata_at) {
            return null;
        }

        return $this->etd->diffInDays($this->ata_at);
    }

    public function getSlaStatusAttribute(): string
    {
        if ($this->ata_at) {
            return 'Selesai';
        }

        if ($this->atd_at && ! $this->ata_at) {
            return $this->is_delayed ? 'Terlambat' : 'Berjalan';
        }

        return 'Belum jalan';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status_label) {
            'Terlambat'   => 'danger',
            'Berjalan'    => 'warning',
            'Selesai'     => 'success',
            default       => 'gray',
        };
    }

    public function sailingSla()
    {
        return $this->hasOne(SlaResult::class)
            ->where('activity', 'sailing');
    }

    public function delayLogs()
    {
        return $this->hasMany(VoyageDelayLog::class);
    }


    public function slaResults()
    {
        return $this->hasMany(SlaResult::class);
    }

    public function getSailingElapsedDaysAttribute(): ?float
    {
        if (! $this->atd_at || $this->ata_at) {
            return null;
        }

        return round(
            Carbon::parse($this->atd_at)->diffInSeconds(now()) / 86400,
            2
        );
    }

    public function getSailingTargetDaysAttribute(): ?int
    {
        if (! $this->pol_id || ! $this->pod_id) {
            return null;
        }

        return SlaRule::query()
            ->where('is_active', true)
            ->where('mode', 'sea')
            ->where('activity', 'sailing')
            ->where('pol_id', $this->pol_id)
            ->where('pod_id', $this->pod_id)
            ->value('target_days');
    }

    public function getSailingProgressLevelAttribute(): ?string
    {
        if (! $this->sailing_elapsed_days || ! $this->sailing_target_days) {
            return null;
        }

        if ($this->sailing_elapsed_days >= $this->sailing_target_days) {
            return 'late';
        }

        if ($this->sailing_elapsed_days >= $this->sailing_target_days * 0.8) {
            return 'warning';
        }

        return 'normal';
    }
}
