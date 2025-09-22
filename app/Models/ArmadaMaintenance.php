<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmadaMaintenance extends Model
{
    protected $fillable = [
        'armada_id',
        'reason',
        'started_at',
        'closed_at',
        'odometer',
        'note'
    ];

    protected $casts = [
        'started_at' => 'date',
        'closed_at'    => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            if ($m->armada->status === \App\Enums\ArmadaStatus::OnDuty) {
                throw new \DomainException('Armada sedang bertugas. Tidak bisa masuk perawatan.');
            }
            $m->armada->transitionTo(\App\Enums\ArmadaStatus::Maintenance, 'Ticket perawatan dibuat');
        });

        static::updated(function ($m) {
            if ($m->wasChanged('closed_at') && $m->closed_at) {
                $m->armada->transitionTo(\App\Enums\ArmadaStatus::Available, 'Perawatan selesai');
            }
        });
    }

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }
}
