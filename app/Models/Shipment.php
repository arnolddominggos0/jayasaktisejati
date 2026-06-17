<?php

namespace App\Models;

use App\Enums\CargoType;
use App\Enums\DeliveryScope;
use App\Enums\RequestType;
use App\Enums\ServiceType;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Services\MpCheckGate;
use App\Services\ShipmentKpiEvaluator;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'customer_id',
        'receiver_id',
        'origin_city_id',
        'destination_city_id',
        'origin_office_id',
        'destination_office_id',
        'branch_id',
        'pic_name',
        'pic_phone',
        'pickup_contact_name',
        'pickup_contact_phone',
        'delivery_contact_name',
        'delivery_contact_phone',
        'request_type',
        'doc_number',
        'priority',
        'attachments',
        'mode',
        'route_from',
        'route_to',
        'route_summary',
        'service_type',
        'service_option',
        'delivery_scope',
        'cargo_type',
        'container_size',
        'container_qty',
        'container_no',
        'seal_no',
        'packages_total',
        'cbm_total',
        'weight_total',
        'vessel_name',
        'voyage',
        'pol',
        'pod',
        'etd',
        'eta',
        'voyage_id',
        'shipping_schedule_id',
        'assigned_depot_id',
        'vehicle_type',
        'vehicle_plate',
        'pickup_date',
        'driver_id',
        'vehicle_kind',
        'vehicle_loading',
        'status',
        'notes',
        'delivered_at',
        'requested_at',
        'cancelled_at',
        'cancelled_by',
        'edited_fields',
        'last_edited_by',
        'confirm_is_true',
        'estimated_ready_at',
        'containers',
        'lcl_items',
        'units',
        'pol_id',
        'pod_id',
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'mode' => ShipmentMode::class,
        'service_type' => ServiceType::class,
        'cargo_type' => CargoType::class,
        'request_type' => RequestType::class,
        'delivery_scope' => DeliveryScope::class,
        'container_qty' => 'integer',
        'packages_total' => 'integer',
        'cbm_total' => 'decimal:3',
        'weight_total' => 'decimal:2',
        'requested_at' => 'datetime',
        'attachments' => 'array',
        'etd' => 'datetime',
        'eta' => 'datetime',
        'pickup_date' => 'datetime',
        'estimated_ready_at' => 'datetime',
        'confirm_is_true' => 'boolean',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'edited_fields' => 'array',
        'containers' => 'array',
        'lcl_items' => 'array',
        'units'               => 'array',
        'pol_id' => 'integer',
        'pod_id' => 'integer',
    ];

    protected static array $colCache = [];

    protected static function hasCol(string $name): bool
    {
        if (! array_key_exists($name, self::$colCache)) {
            self::$colCache[$name] = Schema::hasColumn('shipments', $name);
        }

        return self::$colCache[$name];
    }

    protected static function booted(): void
    {
        static::creating(function (Shipment $m) {
            $mode = $m->mode?->value ?? (string) $m->mode;

            if (blank($m->code)) {
                $m->code = self::generateCode($mode);
            }

            if (blank($m->status)) {
                $m->status = ShipmentStatus::Draft;
            }

            $reqType = $m->request_type?->value ?? (string) $m->request_type;
            if (blank($m->doc_number)) {
                $m->doc_number = $reqType === RequestType::SPPB_DO->value
                    ? 'SPPB-' . now()->format('YmdHis')
                    : 'AUTO-' . now()->format('Ymd-His');
            }

            if (blank($m->eta)) {
                $modeCode = in_array(strtolower($mode), ['sea', 'sea_freight'], true) ? 'SH' : 'TC';
                $m->eta = self::computeEta($modeCode, (string) ($m->priority ?? 'normal'))->toDateTimeString();
            }

            if (blank($m->branch_id)) {
                if (Auth::check() && Auth::user()->effectiveBranchId()) {
                    $m->branch_id = Auth::user()->effectiveBranchId();
                } elseif ($m->origin_office_id) {
                    $m->branch_id = Office::whereKey($m->origin_office_id)->value('branch_id');
                }
            }

            if (blank($m->coordinator_id) && Auth::check()) {
                $u = Auth::user();
                if (method_exists($u, 'hasRole') && $u->hasRole('field_coordinator')) {
                    $m->coordinator_id = $u->id;
                }
            }
        });

        static::created(function (Shipment $m) {
            $m->ensureTrackSkeleton();
        });

        static::saving(function (Shipment $m) {
            if ($m->mode === ShipmentMode::Sea) {
                $m->service_type = ServiceType::SeaFreight;
                $m->pickup_date = null;
                $m->estimated_ready_at = null;

                if ($m->shipping_schedule_id && $m->shippingSchedule) {
                    $sch = $m->shippingSchedule;

                    $m->voyage_id = $sch->voyage_id ?? $m->voyage_id;
                    $m->vessel_name = $sch->vessel_name
                        ?? $sch->vessel?->name
                        ?? $m->vessel_name;

                    $m->voyage = $sch->voyage_no ?? $m->voyage;

                    $m->pol = $sch->voyage?->pol?->name
                        ?? $m->pol;

                    $m->pod = $sch->voyage?->pod?->name
                        ?? $m->pod;

                    $m->etd = $sch->etd ?? $m->etd;
                    $m->eta = $sch->eta ?? $m->eta;
                }
            } else {
                $m->voyage_id = null;
                $m->vessel_name = null;
                $m->voyage = null;
                $m->pol = null;
                $m->pod = null;
                $m->etd = null;
                $m->eta = $m->eta;
                $m->container_size = null;
                $m->container_qty = null;

                $opt = (string) $m->service_option;

                if ($opt === 'car_carrier') {
                    $m->service_type = ServiceType::CarCarrier;
                } else {
                    $m->service_type = ServiceType::LandTrucking;
                }

                if (! $opt && $m->vehicle_type) {
                    $m->service_option = match ($m->vehicle_type) {
                        'car_carrier' => 'car_carrier',
                        'towing' => 'towing',
                        default => 'truck',
                    };
                }

                $base = $m->pickup_date ?: $m->requested_at ?: now();
                $m->estimated_ready_at = strtolower((string) $m->priority) === 'urgent'
                    ? Carbon::parse($base)->endOfDay()
                    : Carbon::parse($base)->addDay()->endOfDay();
            }

            if (blank($m->branch_id)) {
                if (Auth::check() && Auth::user()->effectiveBranchId()) {
                    $m->branch_id = Auth::user()->effectiveBranchId();
                } elseif ($m->origin_office_id) {
                    $m->branch_id = Office::whereKey($m->origin_office_id)->value('branch_id');
                }
            }

            $from = $m->originCity->name
                ?? ($m->route_from ?: null);

            $to = $m->destinationCity->name
                ?? ($m->route_to ?: null);

            $m->route_summary = sprintf(
                '%s → %s',
                $from ?: '—',
                $to ?: '—',
            );

            $orig = $m->getOriginal('status');
            $prev = $orig instanceof ShipmentStatus ? $orig : ShipmentStatus::tryFrom((string) $orig);
            $currRaw = $m->status;
            $curr = $currRaw instanceof ShipmentStatus ? $currRaw : ShipmentStatus::tryFrom((string) $currRaw);

            if ($curr !== $prev) {
                if ($curr === ShipmentStatus::Delivered) {
                    $deliveredTrackAt = $m->exists
                        ? optional(
                            $m->tracks()
                                ->where('status', TrackStatus::Delivered->value)
                                ->latest('tracked_at')
                                ->first()
                        )->tracked_at
                        : null;

                    if (self::hasCol('delivered_at')) {
                        $m->delivered_at = $deliveredTrackAt ?: now();
                    }

                    $m->cancelled_at = null;
                    $m->cancelled_by = null;
                } elseif ($curr === ShipmentStatus::Cancelled) {
                    if (self::hasCol('cancelled_at')) {
                        $m->cancelled_at = $m->cancelled_at ?: now();
                    }

                    $m->cancelled_by = $m->cancelled_by ?: (Auth::id() ?: null);

                    if (self::hasCol('delivered_at')) {
                        $m->delivered_at = null;
                    }
                } else {
                    if (self::hasCol('delivered_at')) {
                        $m->delivered_at = null;
                    }

                    if ($prev === ShipmentStatus::Cancelled) {
                        $m->cancelled_at = null;
                        $m->cancelled_by = null;
                    }
                }
            }
        });

        static::updated(function (Shipment $m) {
            $changed = array_keys($m->getChanges());
            $ignore = ['updated_at', 'created_at', 'edited_fields', 'last_edited_by'];
            $changed = array_values(array_diff($changed, $ignore));

            if (! empty($changed)) {
                $editorId = Auth::id();
                $payload = ['edited_fields' => $changed];

                if ($editorId) {
                    $payload['last_edited_by'] = $editorId;
                }

                $m->forceFill($payload)->saveQuietly();
            }
        });
    }

    public function sendToFc(): void
    {
        $dbStatusBefore = DB::table('shipments')->where('id', $this->id)->value('status');
        logger()->info('[SEND_TO_FC] START', [
            'shipment_id'      => $this->id,
            'code'             => $this->code,
            'memory_status'    => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'db_status_before' => $dbStatusBefore,
        ]);

        if ($this->status !== ShipmentStatus::Draft) {
            logger()->info('[SEND_TO_FC] EARLY_RETURN — status is not draft', [
                'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            ]);
            return;
        }

        $user = Auth::user();

        $resolvedDepotId = $this->assigned_depot_id
            ?? Depot::resolveIdFor(
                $this->branch_id ?: $user?->branch_id,
                $this->mode?->value ?? (string) $this->mode,
                $this->voyage_id
            );

        logger()->info('[SEND_TO_FC] DEPOT_RESOLVED', [
            'resolved_depot_id' => $resolvedDepotId,
        ]);

        if (! $resolvedDepotId) {
            $branchName = $this->branch?->name ?? ($user?->branch?->name ?? '-');
            $modeLabel = $this->mode?->label() ?? (string) $this->mode;
            throw new DomainException(
                "Depot untuk cabang \"{$branchName}\" dengan moda \"{$modeLabel}\" tidak ditemukan. Pastikan depot sudah dikonfigurasi di menu Depo."
            );
        }

        logger()->info('[SEND_TO_FC] PRE_SAVE', [
            'dirty' => $this->getDirty(),
        ]);

        $this->forceFill([
            'status' => ShipmentStatus::Pending,
            'assigned_depot_id' => $resolvedDepotId,
        ])->saveQuietly();

        $dbStatusAfterSave = DB::table('shipments')->where('id', $this->id)->value('status');
        logger()->info('[SEND_TO_FC] STATUS_PENDING_SAVED', [
            'memory_status'    => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'db_status_after'  => $dbStatusAfterSave,
        ]);

        $tracksBefore = $this->tracks()->count();
        $this->ensureTrackSkeleton();
        $tracksAfter = $this->tracks()->count();
        $dbStatusAfterSkeleton = DB::table('shipments')->where('id', $this->id)->value('status');

        logger()->info('[SEND_TO_FC] TRACK_SKELETON_DONE', [
            'tracks_before'         => $tracksBefore,
            'tracks_after'          => $tracksAfter,
            'new_tracks_created'    => $tracksAfter - $tracksBefore,
            'db_status_after_skel'  => $dbStatusAfterSkeleton,
        ]);

        // SC.3B.20 — one BriefingSession per depot per day; many shipments attached.
        $session = BriefingSession::firstOrCreate(
            [
                'depot_id' => $resolvedDepotId,
                'date'     => now()->toDateString(),
            ],
            [
                'summary_headcount' => 5,
                'mp_check_status'   => 'draft',
            ]
        );

        logger()->info('[SEND_TO_FC] BRIEFING_SESSION', [
            'session_id'       => $session->id,
            'was_created'      => $session->wasRecentlyCreated,
        ]);

        $session->shipments()->syncWithoutDetaching([$this->id]);

        $dbStatusFinal = DB::table('shipments')->where('id', $this->id)->value('status');
        logger()->info('[SEND_TO_FC] DONE', [
            'db_status_final' => $dbStatusFinal,
        ]);
    }

    /**
     * Auto-create the BriefingSession for this shipment when it is sent to FC.
     * Idempotent — calling multiple times is safe (firstOrCreate).
     */
    public function ensureBriefingSession(int $depotId): BriefingSession
    {
        $session = BriefingSession::firstOrCreate(
            ['shipment_id' => $this->id],
            [
                'depot_id'          => $depotId,
                'date'              => now()->toDateString(),
                'summary_headcount' => 5,
                'mp_check_status'   => 'draft',
            ]
        );

        if ($session->wasRecentlyCreated) {
            foreach (['helm', 'rompi', 'sepatu', 'sarung_tangan'] as $type) {
                \App\Models\StockApdCheck::firstOrCreate(
                    ['session_id' => $session->id, 'ppe_type' => $type],
                    ['required_quantity' => 5, 'stock_available' => null]
                );
            }
        }

        return $session;
    }

    public static function generateCode(?string $mode = null, ?int $year = null, ?int $month = null): string
    {
        $now = now();
        $year = $year ?: $now->year;
        $month = $month ?: $now->month;

        $prefix = 'JSS' . str_pad($month, 2, '0', STR_PAD_LEFT) . substr($year, -2);
        $modeCode = match (strtolower((string) $mode)) {
            'sea', 'sea_freight' => 'SH',
            'land', 'land_trucking', 'car_carrier', 'towing', 'truck' => 'TC',
            default => 'XX',
        };

        $prefix .= $modeCode;

        $last = static::query()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = 1;
        if ($last && preg_match('/^' . $prefix . '(\d{4})$/', $last, $matches)) {
            $seq = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function computeEta(string $modeCode, string $priority, ?Carbon $base = null): Carbon
    {
        $base = $base?->copy() ?? now();

        if ($modeCode === 'SH') {
            $days = strtolower($priority) === 'urgent' ? 17 : 19;

            return $base->addDays($days)->endOfDay();
        }

        return strtolower($priority) === 'urgent'
            ? $base->endOfDay()
            : $base->addDay()->endOfDay();
    }

    public function canCancel(): bool
    {
        return ! in_array($this->status, [ShipmentStatus::Delivered, ShipmentStatus::Cancelled], true);
    }

    public function cancel(?int $userId = null): void
    {
        if (! $this->canCancel()) {
            throw new DomainException('Pesanan tidak dapat dibatalkan (sudah terkirim / sudah dibatalkan).');
        }

        $this->status = ShipmentStatus::Cancelled;

        if (self::hasCol('cancelled_at')) {
            $this->cancelled_at = now();
        }

        $this->cancelled_by = $userId;
        $this->save();
    }

    public function uncancel(?int $userId = null): void
    {
        if ($this->status !== ShipmentStatus::Cancelled) {
            return;
        }

        $this->status = ShipmentStatus::Pending->value;

        if (self::hasCol('cancelled_at')) {
            $this->cancelled_at = null;
        }

        $this->cancelled_by = null;
        $this->save();
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
        $order = TrackStatus::orderForMode($this->mode);
        $current = $this->currentTrackStatus();

        if (! $current) {
            return $order[0] ?? null;
        }

        $values = array_map(fn(TrackStatus $s) => $s->value, $order);
        $currValue = $current->value;
        $idx = array_search($currValue, $values, strict: true);

        if ($idx === false) {
            return null;
        }

        return $order[$idx + 1] ?? null;
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
    ): ShipmentTrack {
        if ($this->status === ShipmentStatus::Draft) {
            throw new DomainException('Shipment masih draft. Kirim ke FC terlebih dahulu.');
        }

        $this->guardInvalidStatusTransition($status);

        $this->ensureTrackSkeleton();

        if ($this->requiresMpCheck($status)) {
            $this->handleMpCheck($status, $override);
        }

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
            throw new DomainException('Status "' . $status->label() . '" sudah pernah dicapai pada ' . $track->tracked_at->format('d M Y H:i') . '.');
        }

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
            'plan_loading_time_at' => $planLoadingTimeAt,
            'plan_closing_time_at' => $planClosingTimeAt,
            'updated_by' => Auth::id(),
        ]);

        if ($to = $status->toShipmentStatus()) {
            $this->status = $to;

            if ($to === ShipmentStatus::Delivered) {
                $this->delivered_at = $track->tracked_at;
            }

            $this->saveQuietly();
        }

        return $track;
    }

    protected function guardInvalidStatusTransition(TrackStatus $status): void
    {
        $isSea = ($this->mode?->value ?? $this->mode) === 'sea';

        if (! $isSea) {
            return;
        }

        if (in_array($status, [TrackStatus::Hold, TrackStatus::Cancelled], true)) {
            return;
        }

        $order = TrackStatus::orderSea();
        $orderValues = array_map(fn (TrackStatus $s) => $s->value, $order);

        // Query fresh to avoid stale relation cache on the model instance
        $latest = $this->latestTrack()->first();
        $current = $latest?->status instanceof TrackStatus
            ? $latest->status
            : TrackStatus::tryFrom((string) ($latest?->status ?? ''));

        // If currently on Hold, look back to the last non-terminal tracked status
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
            throw new DomainException('Status "' . $status->label() . '" tidak valid untuk shipment laut.');
        }

        if ($targetIndex <= $currentIndex) {
            throw new DomainException('Tidak dapat mengubah status ke tahap sebelumnya atau yang sudah pernah dicapai.');
        }

        $isImmediateNext = $targetIndex === $currentIndex + 1;

        // Allow rack shipment to skip Stuffing and go Handover -> DeliveryToPort
        $isValidSkip = $current?->value === TrackStatus::Handover->value
            && $status === TrackStatus::DeliveryToPort
            && \App\Services\LoadingSessionAutoCreate::isRackShipment($this);

        if (! $isImmediateNext && ! $isValidSkip) {
            $expected = $order[$currentIndex + 1] ?? null;
            $expectedLabel = $expected?->label() ?? 'tidak ada';

            throw new DomainException(
                'Status hanya dapat dilanjutkan ke tahap berikutnya secara berurutan. Status berikutnya yang diharapkan: ' . $expectedLabel . '.'
            );
        }
    }

    protected function requiresMpCheck(TrackStatus $status): bool
    {
        // SC.3B.20 — gate at Pickup only (work-start authorisation).
        // Briefing cleared = permission to begin all subsequent operations.
        return $status === TrackStatus::Pickup;
    }

    protected function handleMpCheck(TrackStatus $status, ?array $override): void
    {
        try {
            MpCheckGate::ensureApproved($this);
        } catch (DomainException $e) {
            $isSea = ($this->mode?->value ?? $this->mode) === 'sea';

            if (! Auth::user()?->hasRole('super_admin')) {
                throw $e;
            }

            if ($isSea) {
                if (! is_array($override) || empty($override['reason'])) {
                    throw new DomainException(
                        'MP Check belum Cleared. Super admin harus menyertakan alasan override (minimal 20 karakter).'
                    );
                }

                $reason = trim((string) $override['reason']);
                if (strlen($reason) < 20) {
                    throw new DomainException(
                        'Alasan override untuk sea shipment harus minimal 20 karakter.'
                    );
                }
            } else {
                if (! is_array($override) || empty($override['reason'])) {
                    throw $e;
                }
            }

            DB::table('mp_check_overrides')->insert([
                'shipment_id'  => $this->id,
                'depot_id'     => $this->assigned_depot_id,
                'track_status' => $status->value,
                'override_by'  => Auth::id(),
                'reason'       => $override['reason'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }

    public function isWithinHMinus3Eta(): bool
    {
        if (! $this->eta) {
            return false;
        }

        $hMinus3 = Carbon::parse($this->eta)->subDays(3)->startOfDay();

        return now()->greaterThanOrEqualTo($hMinus3);
    }

    public function ensureTrackSkeleton(): void
    {
        if ($this->status === ShipmentStatus::Draft) {
            return;
        }

        $order = TrackStatus::orderForMode($this->mode);
        $necessaryStatuses = array_merge($order, [
            TrackStatus::Hold,
            TrackStatus::Cancelled,
        ]);

        $existingStatuses = $this->tracks()
            ->pluck('status')
            ->map(fn($s) => $s instanceof TrackStatus ? $s->value : (string) $s)
            ->toArray();

        foreach ($necessaryStatuses as $st) {
            $value = $st instanceof TrackStatus ? $st->value : (string) $st;
            $this->tracks()->firstOrCreate(
                ['status' => $value],
                ['tracked_at' => null],
            );
        }
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', array_map(fn($event) => $event->value, ShipmentStatus::active()));
    }

    public function scopeHistory($q)
    {
        return $q->whereIn('status', array_map(fn($e) => $e->value, ShipmentStatus::completed()));
    }

    public function getCompletedAtAttribute(): ?Carbon
    {
        $st = $this->status instanceof ShipmentStatus
            ? $this->status
            : ShipmentStatus::tryFrom((string) $this->status);

        if ($st === ShipmentStatus::Delivered) {
            $deliveredTrack = $this->tracks()
                ->where('status', TrackStatus::Delivered->value)
                ->latest('tracked_at')
                ->first();

            return $deliveredTrack?->tracked_at ?: ($this->updated_at ?? null);
        }

        if ($st === ShipmentStatus::Cancelled) {
            return $this->cancelled_at ?: ($this->updated_at ?? null);
        }

        return null;
    }

    public function getDwellingDaysAttribute(): ?int
    {
        $ms = $this->milestoneTimes();

        return $this->diffDaysNullable(
            $ms['pickup'] ?? $this->requested_at,
            $ms['onboard'] ?? null
        );
    }

    public function getSailingDaysAttribute(): ?int
    {
        $ms = $this->milestoneTimes();

        return $this->diffDaysNullable(
            $ms['onboard'] ?? null,
            $ms['arrived'] ?? null
        );
    }

    public function getDooringDaysAttribute(): ?int
    {
        $ms = $this->milestoneTimes();

        return $this->diffDaysNullable(
            $ms['arrived'] ?? null,
            $ms['deliv'] ?? null
        );
    }

    public function getLeadTimeDaysAttribute(): ?int
    {
        $ms = $this->milestoneTimes();

        $dw = $this->diffDaysNullable($ms['pickup'] ?? $this->requested_at, $ms['onboard'] ?? null);
        $sa = $this->diffDaysNullable($ms['onboard'] ?? null, $ms['arrived'] ?? null);
        $dr = $this->diffDaysNullable($ms['arrived'] ?? null, $ms['deliv'] ?? null);

        return is_null($dw) || is_null($sa) || is_null($dr) ? null : ($dw + $sa + $dr);
    }

    public function rebuildMilestonesFromTracks(): void
    {
        // Only tracks with a real timestamp count — skeleton tracks (tracked_at=null)
        // must never contribute to milestone fields.
        $tracks = $this->tracks()->whereNotNull('tracked_at')->orderBy('tracked_at')->get();

        if (self::hasCol('pickup_started_at')) {
            $this->pickup_started_at = null;
        }

        if (self::hasCol('onboard_at')) {
            $this->onboard_at = null;
        }

        if (self::hasCol('arrived_at')) {
            $this->arrived_at = null;
        }

        if (self::hasCol('delivered_at')) {
            $this->delivered_at = null;
        }

        foreach ($tracks as $t) {
            $s = $t->status instanceof TrackStatus ? $t->status : TrackStatus::tryFrom((string) $t->status);
            $ts = $t->tracked_at; // non-null guaranteed by whereNotNull filter above

            if (self::hasCol('pickup_started_at') && ! $this->pickup_started_at && in_array($s, [
                TrackStatus::Pickup,
                TrackStatus::Handover,
                TrackStatus::Stuffing,
                TrackStatus::DeliveryToPort,
                TrackStatus::Stacking,
            ], true)) {
                $this->pickup_started_at = $ts;
            }

            if (self::hasCol('onboard_at') && ! $this->onboard_at && in_array($s, [
                TrackStatus::UnitLoading,
                TrackStatus::OnShip,
                TrackStatus::VesselDepart,
            ], true)) {
                $this->onboard_at = $ts;
            }

            if (self::hasCol('arrived_at') && ! $this->arrived_at && in_array($s, [
                TrackStatus::VesselArrival,
                TrackStatus::Unloading,
            ], true)) {
                $this->arrived_at = $ts;
            }

            if (self::hasCol('delivered_at') && ! $this->delivered_at && $s === TrackStatus::Delivered) {
                $this->delivered_at = $ts;
            }
        }

        $this->saveQuietly();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Customer::class, 'receiver_id');
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class, 'voyage_id');
    }

    // voyageRecord() resolves the BelongsTo relation unambiguously.
    // Use this everywhere you expect a Voyage model — never use ->voyage
    // on a Shipment to access the model, because shipments.voyage is a
    // string snapshot column that shadows this relation in getAttribute().
    public function voyageRecord()
    {
        return $this->belongsTo(Voyage::class, 'voyage_id');
    }

    public function armada()
    {
        return $this->belongsTo(Armada::class, 'armada_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function originOffice()
    {
        return $this->belongsTo(Office::class, 'origin_office_id');
    }

    public function assignedDepot()
    {
        return $this->belongsTo(Depot::class, 'assigned_depot_id');
    }

    /**
     * The depot at the shipment's Port of Discharge.
     *
     * Resolution chain: shipment.pod_id → depots.port_id
     * FK-complete — no migration needed.
     * Returns null if pod_id is unset or no depot serves that port.
     *
     * Used by handleMpCheck() to validate the correct depot's MP briefing
     * for destination-side operations (Unloading).
     */
    public function destinationDepot(): ?Depot
    {
        if (! $this->pod_id) {
            return null;
        }

        return Depot::where('port_id', $this->pod_id)->first();
    }

    public function destinationOffice()
    {
        return $this->belongsTo(Office::class, 'destination_office_id');
    }

    public function briefingSession()
    {
        return $this->hasOne(BriefingSession::class, 'shipment_id');
    }

    public function briefingSessions(): BelongsToMany
    {
        return $this->belongsToMany(
            BriefingSession::class,
            'briefing_session_shipments',
            'shipment_id',
            'briefing_session_id'
        );
    }

    public function tracks()
    {
        return $this->hasMany(ShipmentTrack::class, 'shipment_id', 'id');
    }

    public function latestTrack()
    {
        return $this->hasOne(ShipmentTrack::class, 'shipment_id', 'id')
            ->whereNotNull('tracked_at')
            ->latestOfMany('tracked_at');
    }

    public function shippingSchedule()
    {
        return $this->belongsTo(ShippingSchedule::class, 'shipping_schedule_id');
    }

    public function getLatestTrackStatusAttribute(): ?TrackStatus
    {
        return $this->latestTrack?->status;
    }

    public function getLatestTrackedAtAttribute(): ?Carbon
    {
        return $this->latestTrack?->tracked_at;
    }

    public function originCity()
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destinationCity()
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function pol()
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod()
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public function units()
    {
        return $this->hasMany(Unit::class, 'shipment_id', 'id');
    }

    public function loadingSessions()
    {
        return $this->hasMany(LoadingSession::class, 'shipment_id');
    }

    public function getRouteLabelAttribute(): string
    {
        $origin = $this->originCity?->name
            ?? $this->route_from
            ?? $this->origin
            ?? $this->origin_name
            ?? $this->pol_name
            ?? $this->from;

        $dest = $this->destinationCity?->name
            ?? $this->route_to
            ?? $this->destination
            ?? $this->destination_name
            ?? $this->pod_name
            ?? $this->to;

        return trim(($origin ?: '—') . ' → ' . ($dest ?: '—'));
    }

    public function getAttachmentUrlsAttribute(): array
    {
        $paths = $this->attachments ?? [];

        return array_values(
            array_map(
                fn($p) => Storage::disk('public')->url($p),
                $paths
            )
        );
    }

    public function isHistorical(): bool
    {
        $st = $this->status instanceof ShipmentStatus
            ? $this->status
            : ShipmentStatus::tryFrom((string) $this->status);

        return in_array($st, [ShipmentStatus::Delivered, ShipmentStatus::Cancelled], true);
    }

    public function getContainerMapAttribute(): array
    {
        $containers = collect($this->containers ?? [])
            ->filter(fn($c) => ! empty($c['container_no']))
            ->mapWithKeys(function ($c) {
                $no = trim((string) $c['container_no']);

                return [
                    $no => [
                        'container_no' => $no,
                        'seal_no' => $c['seal_no'] ?? null,
                        'lcl_count' => 0,
                        'unit_count' => 0,
                    ],
                ];
            })->all();

        foreach (($this->lcl_items ?? []) as $row) {
            $ref = trim((string) ($row['container_no_ref'] ?? ''));

            if ($ref !== '' && isset($containers[$ref])) {
                $containers[$ref]['lcl_count']++;
            }
        }

        $unitRows = $this->relationLoaded('units')
            ? $this->units
            : (is_array($this->getRawOriginal('units'))
                ? collect($this->getRawOriginal('units'))
                : collect());

        foreach ($unitRows as $row) {
            $ref = trim((string) ($row['container_no_ref'] ?? ''));
            $qty = (int) ($row['qty'] ?? 1);

            if ($ref !== '' && isset($containers[$ref])) {
                $containers[$ref]['unit_count'] += max(1, $qty);
            }
        }

        return array_values($containers);
    }

    public function getContainerSummaryAttribute(): ?string
    {
        $map = $this->container_map;
        if (empty($map)) {
            return null;
        }

        $parts = [];

        foreach ($map as $c) {
            $seal = $c['seal_no'] ? " / {$c['seal_no']}" : '';
            $detail = [];

            if ($c['lcl_count'] > 0) {
                $detail[] = "{$c['lcl_count']} koli LCL";
            }

            if ($c['unit_count'] > 0) {
                $detail[] = "{$c['unit_count']} unit";
            }

            $det = $detail ? ' • ' . implode(', ', $detail) : '';
            $parts[] = "{$c['container_no']}{$seal}{$det}";
        }

        return implode('  |  ', $parts);
    }

    // ─── Unit-derived container accessors ──────────────────────────────────────
    // Source of truth: units.container_display (not shipments.container_no).
    // These accessors are always derived from child Unit rows and are never
    // written back to the shipments table, so they stay correct after any
    // SPPB consolidation or unit reassignment.

    /**
     * Return an ordered, deduplicated list of container identifiers
     * taken from the child Unit rows for this shipment.
     *
     * @return string[]
     */
    public function getContainerListAttribute(): array
    {
        return $this->units()
            ->whereNotNull('container_display')
            ->pluck('container_display')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Number of distinct containers used by units of this shipment.
     */
    public function getContainerCountAttribute(): int
    {
        return count($this->container_list);
    }

    /**
     * Comma-separated container identifiers, suitable for display in a
     * single text field.  Returns an empty string when no units have
     * container_display set.
     */
    public function getContainerDisplayAttribute(): string
    {
        return collect($this->container_list)->implode(', ');
    }

    public function kpiBranchId(): ?int
    {
        $destOfficeBranchId = $this->destination_office_id
            ? optional($this->destinationOffice)->branch_id
            : null;

        $destBranchId = property_exists($this, 'destination_branch_id')
            ? ($this->destination_branch_id ?? null)
            : null;

        return (int) ($destOfficeBranchId ?? $destBranchId ?? $this->branch_id ?? 0) ?: null;
    }

    public function isManadoKpiTarget(): bool
    {
        return app(ShipmentKpiEvaluator::class)->isManadoKpiTarget($this);
    }

    public function kpiManadoThresholds(): array
    {
        return app(ShipmentKpiEvaluator::class)->getManadoThresholds();
    }

    protected function diffDaysNullable($from, $to): ?int
    {
        return app(ShipmentKpiEvaluator::class)->diffDaysNullable($from, $to);
    }

    public function evaluateKpiForManado(): array
    {
        return app(ShipmentKpiEvaluator::class)->evaluateManadoKpi($this);
    }

    public function kpiManadoSummaryText(): ?string
    {
        return app(ShipmentKpiEvaluator::class)->manadoSummaryText($this);
    }

    public function milestoneTimes(): array
    {
        return app(ShipmentKpiEvaluator::class)->getMilestoneTimes($this);
    }

    public function scopeTamKpi(Builder $q): Builder
    {
        $cfg = config('jss_kpi.manado', []);
        $customerIds = array_map('intval', $cfg['customer_ids'] ?? []);

        if (empty($customerIds)) {
            return $q->whereRaw('1 = 0');
        }

        return $q->whereIn('customer_id', $customerIds);
    }

    public function scopeManadoKpiTarget(Builder $q): Builder
    {
        return $q->tamKpi();
    }

    public function getPlannedKpiAttribute(): array
    {
        return app(ShipmentKpiEvaluator::class)->getPlannedKpi();
    }

    public function getKpiEvaluationAttribute(): array
    {
        $evaluator = app(ShipmentKpiEvaluator::class);
        $plan = $this->planned_kpi;

        return [
            'dwelling' => $evaluator->compare($this->dwelling_days, $plan['dwelling']),
            'sailing' => $evaluator->compare($this->sailing_days, $plan['sailing']),
            'dooring' => $evaluator->compare($this->dooring_days, $plan['dooring']),
            'total' => $evaluator->compare($this->lead_time_days, $plan['total']),
        ];
    }
}
