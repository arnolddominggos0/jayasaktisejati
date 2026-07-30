<?php

namespace App\Models;

use App\Enums\ContainerAllocationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContainerReadinessSession extends Model
{
    protected $table = 'container_readiness_sessions';

    protected $fillable = [
        'session_date',
        'unit_count',
        'container_need',
        'container_available',
        'gap',
        'summary_sufficient',
        'notes',
        'container_numbers',
    ];

    protected $casts = [
        'session_date'        => 'date',
        'unit_count'          => 'integer',
        'container_need'      => 'integer',
        'container_available' => 'integer',
        'gap'                 => 'integer',
        'summary_sufficient'  => 'boolean',
        'container_numbers'   => 'array',
    ];
    public function getContainerNumberListAttribute(): array
    {
        return collect($this->container_numbers ?? [])
            ->map(fn($v) => is_array($v) ? ($v['number'] ?? '') : $v)
            ->map(fn($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function getContainerServiceListAttribute(): array
    {
        return collect($this->container_numbers ?? [])
            ->map(function ($v) {
                if (is_string($v)) {
                    return ['number' => strtoupper(trim($v)), 'service' => null];
                }

                return [
                    'number' => strtoupper(trim((string) ($v['number'] ?? ''))),
                    'service' => ContainerAllocationType::tryFrom((string) ($v['service'] ?? '')),
                ];
            })
            ->filter(fn(array $row) => $row['number'] !== '')
            ->unique('number')
            ->values()
            ->all();
    }

    public static function todayContainerOptions(): array
    {
        $session = static::whereDate('session_date', today())->first();
        $numbers = $session?->container_number_list ?? [];
        return collect($numbers)->mapWithKeys(fn($n) => [$n => $n])->all();
    }


    /**
     * Unit operasional pada session_date — diturunkan dari Handover track.
     *
     * Mirror definisi yang sudah dipakai BriefingSession::getEffectiveUnitMasukYardAttribute()
     * ("unit masuk yard hari itu"), TANPA filter depot karena tabel ini global
     * (session_date unique, tidak memiliki depot_id).
     */
    public function getDerivedUnitCountAttribute(): int
    {
        if (! $this->session_date) {
            return 0;
        }

        $date = $this->session_date instanceof \Carbon\Carbon
            ? $this->session_date->format('Y-m-d')
            : (string) $this->session_date;

        return (int) DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->join('shipment_tracks as st', function ($j) use ($date) {
                $j->on('st.shipment_id', '=', 's.id')
                  ->where('st.status', 'handover')
                  ->whereNotNull('st.tracked_at')
                  ->whereDate('st.tracked_at', $date);
            })
            ->where('s.status', '!=', 'draft')
            ->count('u.id');
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $date = $model->session_date instanceof \Carbon\Carbon
                ? $model->session_date->format('Y-m-d')
                : (string) $model->session_date;

            // unit_count — auto. Dual-source mengikuti konvensi BriefingSession::YARD_CUTOFF:
            // sebelum cutoff pakai nilai tersimpan (histori hasil backfill), sejak cutoff diturunkan.
            if ($date && $date >= BriefingSession::YARD_CUTOFF) {
                $model->unit_count = $model->derived_unit_count;
            }

            // container_available — auto dari Daftar Container (single source of truth).
            // Baris legacy (container_numbers = NULL, sebelum migration 2026_06_23)
            // mempertahankan angka tersimpannya agar histori tidak berubah arti.
            if (is_array($model->container_numbers)) {
                $model->container_available = count($model->container_number_list);
            }

            $model->gap                = $model->container_available - $model->container_need;
            $model->summary_sufficient = $model->container_available >= $model->container_need;
        });
    }

    public function getGapAttribute(): int
    {
        if (array_key_exists('gap', $this->attributes)) {
            return (int) $this->attributes['gap'];
        }

        return $this->container_available - $this->container_need;
    }

    public function getStatusAttribute(): string
    {
        return $this->summary_sufficient ? 'READY' : 'NOT READY';
    }
}
