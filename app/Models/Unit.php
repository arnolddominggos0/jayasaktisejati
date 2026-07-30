<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Enums\UnitAllocationStatus;
use App\Services\LoadingSessionAutoCreate;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'shipment_id',
        'model_no',
        'reg_no',
        'chassis_no',
        'engine_no',
        'sjkb_no',
        'color',
        'do_number',
        'qty',
        'container_display',
        'notes',
        'container_id',
        'allocation_status',
        'stuffed_at',
        'stuffed_by',
        'stuffing_remarks',
    ];

    protected $casts = [
        'qty' => 'integer',
        'allocation_status' => UnitAllocationStatus::class,
        'stuffed_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id', 'id');
    }

    public function unitChecks(): HasMany
    {
        return $this->hasMany(UnitCheck::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(UnitInspection::class);
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class, 'container_id');
    }
    
    public function stuffedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stuffed_by');
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(UnitTrack::class, 'unit_id');
    }

    public function latestTrack()
    {
        return $this->hasOne(UnitTrack::class, 'unit_id')
            ->whereNotNull('tracked_at')
            ->latestOfMany('tracked_at');
    }

    public function getLatestTrackStatusAttribute(): ?TrackStatus
    {
        return $this->latestTrack?->status;
    }

    public function currentTrackStatus(): ?TrackStatus
    {
        $latest = $this->latestTrack;

        return $latest?->status instanceof TrackStatus
            ? $latest->status
            : ($latest?->status ? TrackStatus::tryFrom((string) $latest->status) : null);
    }

    public function nextTrackStatus(): ?TrackStatus
    {
        $order = TrackStatus::orderForMode($this->shipment?->mode);
        $current = $this->currentTrackStatus();

        if (! $current) {
            return $order[0] ?? null;
        }

        $values = array_map(fn (TrackStatus $s) => $s->value, $order);
        $idx = array_search($current->value, $values, strict: true);

        if ($idx === false) {
            return null;
        }

        return $order[$idx + 1] ?? null;
    }

    public function ensureTrackSkeleton(): void
    {
        if ($this->shipment?->status === ShipmentStatus::Draft) {
            return;
        }

        $order = TrackStatus::orderForMode($this->shipment?->mode);
        $necessaryStatuses = array_merge($order, [
            TrackStatus::Hold,
            TrackStatus::Cancelled,
        ]);

        foreach ($necessaryStatuses as $st) {
            $value = $st instanceof TrackStatus ? $st->value : (string) $st;
            $this->tracks()->firstOrCreate(
                ['status' => $value],
                ['tracked_at' => null],
            );
        }
    }

    protected function guardUnitStatusTransition(TrackStatus $status): void
    {
        $isSea = ($this->shipment?->mode?->value ?? $this->shipment?->mode) === 'sea';

        if (! $isSea) {
            return;
        }

        if (in_array($status, [TrackStatus::Hold, TrackStatus::Cancelled], true)) {
            return;
        }

        $order = TrackStatus::orderSea();
        $orderValues = array_map(fn (TrackStatus $s) => $s->value, $order);

        $latest = $this->latestTrack()->first();
        $current = $latest?->status instanceof TrackStatus
            ? $latest->status
            : TrackStatus::tryFrom((string) ($latest?->status ?? ''));

        if ($current === TrackStatus::Hold) {
            $previousTrack = $this->tracks()
                ->whereNotNull('tracked_at')
                ->whereIn('status', $orderValues)
                ->orderBy('tracked_at', 'desc')
                ->first();

            $current = $previousTrack?->status instanceof TrackStatus
                ? $previousTrack->status
                : TrackStatus::tryFrom((string) ($previousTrack?->status ?? ''));
        }

        $currentIndex = $current ? array_search($current->value, $orderValues, true) : -1;
        $targetIndex = array_search($status->value, $orderValues, true);

        if ($targetIndex === false) {
            throw new DomainException('Status "'.$status->label().'" tidak valid untuk shipment laut.');
        }

        if ($targetIndex <= $currentIndex) {
            throw new DomainException('Tidak dapat mengubah status ke tahap sebelumnya atau yang sudah pernah dicapai.');
        }

        $isImmediateNext = $targetIndex === $currentIndex + 1;

        $isValidSkip = $current?->value === TrackStatus::Handover->value
            && $status === TrackStatus::DeliveryToPort
            && LoadingSessionAutoCreate::isRackShipment($this->shipment);

        if (! $isImmediateNext && ! $isValidSkip) {
            $expected = $order[$currentIndex + 1] ?? null;
            $expectedLabel = $expected?->label() ?? 'tidak ada';

            throw new DomainException(
                'Status hanya dapat dilanjutkan ke tahap berikutnya secara berurutan. Status berikutnya yang diharapkan: '.$expectedLabel.'.'
            );
        }
    }

    public function appendTrack(
        TrackStatus $status,
        ?string $note = null,
        ?string $location = null,
        ?array $attachments = null,
        ?array $override = null,
        ?array $checkseet = null,
        ?string $planLoadingTimeAt = null,
        ?string $planClosingTimeAt = null,
    ): UnitTrack {
        if ($this->shipment?->status === ShipmentStatus::Draft) {
            throw new DomainException('Shipment masih draft. Kirim ke FC terlebih dahulu.');
        }

        $this->cancelTransition();

        $this->guardUnitStatusTransition($status);
        $this->shipment?->assertOperationalGatesForTransition($status);

        $this->ensureTrackSkeleton();

        $track = $this->tracks()
            ->where('status', $status->value)
            ->first();

        if (! $track) {
            $track = $this->tracks()->create([
                'status' => $status->value,
                'tracked_at' => null,
            ]);
        }

        if ($track->tracked_at) {
            throw new DomainException('Status "'.$status->label().'" sudah pernah dicapai pada '.$track->tracked_at->format('d M Y H:i').'.');
        }

        $track->tracked_at = now();
        $track->note = $note;
        $track->checkseet = $checkseet;
        $track->validateTrackingState();

        $track->updateQuietly([
            'tracked_at' => now(),
            'started_at' => $track->started_at ?? now(),
            'note' => $note,
            'location' => $location,
            'attachments' => $attachments,
            'checkseet' => $checkseet,
            'plan_loading_time_at' => $planLoadingTimeAt,
            'plan_closing_time_at' => $planClosingTimeAt,
            'updated_by' => Auth::id(),
        ]);

        $this->shipment?->syncTimelineFromUnits();

        return $track;
    }

    public function activeTransition(): ?TrackStatus
    {
        $row = $this->tracks()
            ->whereNotNull('started_at')
            ->whereNull('tracked_at')
            ->latest('started_at')
            ->first();

        if (! $row) {
            return null;
        }

        return $row->status instanceof TrackStatus
            ? $row->status
            : TrackStatus::tryFrom((string) $row->status);
    }

    public function startTransition(
        TrackStatus $status,
        ?string $note = null,
        ?string $planLoadingTimeAt = null,
        ?string $planClosingTimeAt = null,
    ): UnitTrack {
        if ($this->shipment?->status === ShipmentStatus::Draft) {
            throw new DomainException('Shipment masih draft. Kirim ke FC terlebih dahulu.');
        }

        $this->guardUnitStatusTransition($status);

        $this->ensureTrackSkeleton();

        $this->tracks()
            ->whereNotNull('started_at')
            ->whereNull('tracked_at')
            ->where('status', '!=', $status->value)
            ->update(['started_at' => null]);

        $track = $this->tracks()
            ->where('status', $status->value)
            ->first();

        if (! $track) {
            $track = $this->tracks()->create([
                'status' => $status->value,
                'tracked_at' => null,
            ]);
        }

        if ($track->tracked_at) {
            throw new DomainException('Status "'.$status->label().'" sudah pernah dicapai pada '.$track->tracked_at->format('d M Y H:i').'.');
        }

        $track->updateQuietly([
            'started_at' => now(),
            'note' => $note ?? $track->note,
            'plan_loading_time_at' => $planLoadingTimeAt ?? $track->plan_loading_time_at,
            'plan_closing_time_at' => $planClosingTimeAt ?? $track->plan_closing_time_at,
            'updated_by' => Auth::id(),
        ]);

        return $track;
    }

    public function completeTransition(
        TrackStatus $status,
        ?string $note = null,
        ?string $location = null,
        ?array $attachments = null,
        ?array $override = null,
        ?array $checkseet = null,
        ?string $planLoadingTimeAt = null,
        ?string $planClosingTimeAt = null,
    ): UnitTrack {
        $track = $this->tracks()
            ->where('status', $status->value)
            ->whereNotNull('started_at')
            ->whereNull('tracked_at')
            ->first();

        if (! $track) {
            throw new DomainException('Tidak ada proses transisi aktif menuju "'.$status->label().'".');
        }

        $this->shipment?->assertOperationalGatesForTransition($status);

        $track->tracked_at = now();
        $track->note = $note;
        $track->checkseet = $checkseet;
        $track->validateTrackingState();

        $track->updateQuietly([
            'tracked_at' => now(),
            'note' => $note,
            'location' => $location,
            'attachments' => $attachments,
            'checkseet' => $checkseet,
            'plan_loading_time_at' => $planLoadingTimeAt ?? $track->plan_loading_time_at,
            'plan_closing_time_at' => $planClosingTimeAt ?? $track->plan_closing_time_at,
            'updated_by' => Auth::id(),
        ]);

        $this->shipment?->syncTimelineFromUnits();

        return $track;
    }

    public function cancelTransition(): void
    {
        $this->tracks()
            ->whereNotNull('started_at')
            ->whereNull('tracked_at')
            ->update(['started_at' => null]);
    }
}
