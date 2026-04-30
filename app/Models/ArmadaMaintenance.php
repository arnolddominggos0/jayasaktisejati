<?php

namespace App\Models;

use App\Enums\ArmadaStatus;
use App\Enums\MaintenanceReason;
use App\Enums\MaintenanceStatus;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmadaMaintenance extends Model
{
    protected $table = 'armada_maintenances';

    protected $fillable = [
        'armada_id',
        'reason_code',
        'started_at',
        'closed_at',
        'odometer',
        'note',
        'status',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'closed_at'   => 'datetime',
        'reason_code' => MaintenanceReason::class,
        'status'      => MaintenanceStatus::class,
        'odometer'    => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if ($m->status === \App\Enums\MaintenanceStatus::Scheduled) {
                $m->started_at = null;
                $m->closed_at  = null;
            }
            if ($m->status === \App\Enums\MaintenanceStatus::InProgress) {
                $m->started_at = $m->started_at ?: now();
                $m->closed_at  = null;
            }
            if ($m->status === \App\Enums\MaintenanceStatus::Closed) {
                $m->started_at = $m->started_at ?: now();
                $m->closed_at  = $m->closed_at ?: now();
                if ($m->closed_at->lt($m->started_at)) {
                    throw new \DomainException('Waktu Selesai tidak boleh sebelum Waktu Mulai.');
                }
            }
        });

        static::creating(function (self $m) {
            $armada = $m->armada()->first();
            if (! $armada) {
                throw new DomainException('Armada tidak ditemukan.');
            }
            if ($armada->status === ArmadaStatus::OnDuty) {
                throw new DomainException('Armada sedang bertugas. Tidak bisa masuk perawatan.');
            }
            $armada->transitionTo(ArmadaStatus::Maintenance, 'Tiket perawatan dibuat');
        });

        static::updated(function (self $m) {
            if ($m->status === MaintenanceStatus::Closed) {
                $armada = $m->armada()->first();
                if ($armada && $armada->status !== ArmadaStatus::Available) {
                    $armada->transitionTo(ArmadaStatus::Available, 'Perawatan selesai');
                }
            }
        });
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('edit', ['record' => $record]);
    }
    
    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }
}
