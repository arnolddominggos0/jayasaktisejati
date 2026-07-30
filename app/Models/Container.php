<?php

namespace App\Models;

use App\Enums\ContainerAllocationType;
use App\Enums\ContainerStuffingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Container extends Model
{
    protected $fillable = [
        'container_no',
        'type',
        'capacity',
        'container_readiness_session_id',
        'is_ready_for_stuffing',
        'marked_ready_at',
        'marked_ready_by',
        'stuffing_status',
        'stuffing_started_at',
        'stuffing_completed_at',
    ];

    protected $casts = [
        'type' => ContainerAllocationType::class,
        'capacity' => 'integer',
        'is_ready_for_stuffing' => 'boolean',
        'marked_ready_at' => 'datetime',
        'stuffing_status' => ContainerStuffingStatus::class,
        'stuffing_started_at' => 'datetime',
        'stuffing_completed_at' => 'datetime',
    ];

    public function readinessSession(): BelongsTo
    {
        return $this->belongsTo(ContainerReadinessSession::class, 'container_readiness_session_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'container_id');
    }

    public function markedReadyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_ready_by');
    }

    public function filledCount(): int
    {
        return $this->exists ? $this->units()->count() : 0;
    }

    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->filledCount());
    }

    public function isFull(): bool
    {
        $remaining = $this->remainingCapacity();

        return $remaining !== null && $remaining <= 0;
    }
    public function plannedUnitCount(): int
    {
        return $this->filledCount();
    }

    public function stuffedUnitCount(): int
    {
        return $this->exists
            ? $this->units()->where('allocation_status', \App\Enums\UnitAllocationStatus::Stuffed->value)->count()
            : 0;
    }

    public function isStuffingComplete(): bool
    {
        $planned = $this->plannedUnitCount();

        return $planned > 0 && $this->stuffedUnitCount() === $planned;
    }

    public function shipment(): ?Shipment
    {
        if (! $this->exists) {
            return null;
        }

        $shipmentIds = $this->units()->distinct()->pluck('shipment_id');

        return $shipmentIds->count() === 1
            ? Shipment::find($shipmentIds->first())
            : null;
    }

    public static function resolveForSession(ContainerReadinessSession $session, string $containerNo): self
    {
        $containerNo = strtoupper(trim($containerNo));

        if (! in_array($containerNo, $session->container_number_list, true)) {
            throw new \DomainException(
                "Container {$containerNo} tidak terdaftar di Container Readiness hari ini — tidak bisa dipakai alokasi."
            );
        }

        return static::firstOrCreate([
            'container_readiness_session_id' => $session->id,
            'container_no' => $containerNo,
        ]);
    }
}
