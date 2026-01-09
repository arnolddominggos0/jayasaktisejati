<?php

namespace App\Models;

use App\Actions\CreateShippingSchedule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        });

        static::saving(function (self $model) {
            if ($model->atd_at) {
                $end = $model->ata_at ?? now();
                $model->actual_sailing_days =
                    round($model->atd_at->diffInSeconds($end) / 86400, 2);
            }
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
}
