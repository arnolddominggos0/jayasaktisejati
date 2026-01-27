<?php

namespace App\Models;

use App\Actions\CreateShippingSchedule;
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
        'vessel_id',
        'pol_id',
        'pod_id',
        'voyage_no',
        'etd',
        'eta',
        'atd_at',
        'ata_at',
        'period_month',
        'actual_sailing_days',
        'delay_reason',
    ];

    protected $attributes = [
        'kpi_sailing_days' => 10,
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'atd_at' => 'datetime',
        'ata_at' => 'datetime',
        'period_month' => 'date',
        'actual_sailing_days' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updated(function (Voyage $voyage) {

            if (
                $voyage->is_final &&
                $voyage->wasChanged('is_final')
            ) {
                CreateShippingSchedule::run($voyage);
            }

            if (
                $voyage->wasChanged('ata_at') &&
                $voyage->ata_at &&
                $voyage->atd_at
            ) {
                $exists = DB::table('sla_results')
                    ->where('voyage_id', $voyage->id)
                    ->where('activity', 'sailing')
                    ->exists();

                if (! $exists) {
                    $rule = DB::table('sla_rules')
                        ->where('mode', 'sea')
                        ->where('activity', 'sailing')
                        ->where('pol_id', $voyage->pol_id)
                        ->where('pod_id', $voyage->pod_id)
                        ->where('is_active', true)
                        ->first();

                    if ($rule) {
                        $actualDays = round(
                            \Carbon\Carbon::parse($voyage->atd_at)
                                ->diffInSeconds($voyage->ata_at) / 86400,
                            2
                        );

                        $lateDays = max(0, $actualDays - $rule->target_days);

                        DB::table('sla_results')->insert([
                            'voyage_id'   => $voyage->id,
                            'sla_rule_id' => $rule->id,
                            'activity'    => 'sailing',
                            'start_at'    => $voyage->atd_at,
                            'end_at'      => $voyage->ata_at,
                            'target_days' => $rule->target_days,
                            'actual_days' => $actualDays,
                            'status'      => $lateDays > 0 ? 'late' : 'on_time',
                            'late_days'   => $lateDays,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }
            }
        });

        static::saving(function (self $model) {
            if ($model->atd_at) {
                $end = $model->ata_at ?? now();
                $model->actual_sailing_days =
                    round($model->atd_at->diffInSeconds($end) / 86400, 2);
            }
        });

        static::saved(function (Voyage $voyage) {
            SlaEvaluator::evaluateVoyage($voyage);
        });
    }

    public function vessel()
    {
        return $this->belongsTo(Vessel::class);
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
        return $this->hasOne(\App\Models\SlaResult::class)
            ->where('activity', 'sailing');
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
