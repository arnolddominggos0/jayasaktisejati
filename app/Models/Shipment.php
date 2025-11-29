<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\{
    ShipmentStatus,
    ShipmentMode,
    ServiceType,
    CargoType,
    DeliveryScope,
    RequestType,
    TrackStatus
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

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
    ];

    protected $casts = [
        'status'              => ShipmentStatus::class,
        'mode'                => ShipmentMode::class,
        'service_type'        => ServiceType::class,
        'cargo_type'          => CargoType::class,
        'request_type'        => RequestType::class,
        'delivery_scope'      => DeliveryScope::class,
        'container_qty'       => 'integer',
        'packages_total'      => 'integer',
        'cbm_total'           => 'decimal:3',
        'weight_total'        => 'decimal:2',
        'requested_at'        => 'datetime',
        'attachments'         => 'array',
        'etd'                 => 'datetime',
        'eta'                 => 'datetime',
        'pickup_date'         => 'datetime',
        'estimated_ready_at'  => 'datetime',
        'confirm_is_true'     => 'boolean',
        'delivered_at'        => 'datetime',
        'cancelled_at'        => 'datetime',
        'edited_fields'       => 'array',
        'containers'          => 'array',
        'lcl_items'           => 'array',
        'units'               => 'array',
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

            $reqType = $m->request_type?->value ?? (string) $m->request_type;
            if (blank($m->doc_number)) {
                $m->doc_number = $reqType === RequestType::SPPB_DO->value
                    ? 'SPPB-' . now()->format('YmdHis')
                    : 'AUTO-' . now()->format('Ymd-His');
            }

            if (blank($m->eta)) {
                $modeCode = in_array(strtolower($mode), ['sea', 'sea_freight'], true) ? 'SH' : 'TC';
                $m->eta   = self::computeEta($modeCode, (string) ($m->priority ?? 'normal'))->toDateTimeString();
            }

            if (blank($m->branch_id)) {
                if (Auth::check() && Auth::user()->branch_id) {
                    $m->branch_id = Auth::user()->branch_id;
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
                $m->service_type        = ServiceType::SeaFreight;
                $m->pickup_date         = null;
                $m->estimated_ready_at  = null;

                if ($m->shipping_schedule_id && $m->shippingSchedule) {
                    $sch = $m->shippingSchedule;

                    $m->voyage_id   = $sch->voyage_id ?? $m->voyage_id;
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
                $m->voyage_id      = null;
                $m->vessel_name    = null;
                $m->voyage         = null;
                $m->pol            = null;
                $m->pod            = null;
                $m->etd            = null;
                $m->eta            = $m->eta;
                $m->container_size = null;
                $m->container_qty  = null;

                $opt = (string) $m->service_option;

                if ($opt === 'car_carrier') {
                    $m->service_type = ServiceType::CarCarrier;
                } else {
                    $m->service_type = ServiceType::LandTrucking;
                }

                if (! $opt && $m->vehicle_type) {
                    $m->service_option = match ($m->vehicle_type) {
                        'car_carrier' => 'car_carrier',
                        'towing'      => 'towing',
                        default       => 'truck',
                    };
                }

                $base = $m->pickup_date ?: $m->requested_at ?: now();
                $m->estimated_ready_at = strtolower((string) $m->priority) === 'urgent'
                    ? Carbon::parse($base)->endOfDay()
                    : Carbon::parse($base)->addDay()->endOfDay();
            }

            if (blank($m->branch_id)) {
                if (Auth::check() && Auth::user()->branch_id) {
                    $m->branch_id = Auth::user()->branch_id;
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

            $orig    = $m->getOriginal('status');
            $prev    = $orig instanceof ShipmentStatus ? $orig : ShipmentStatus::tryFrom((string) $orig);
            $currRaw = $m->status;
            $curr    = $currRaw instanceof ShipmentStatus ? $currRaw : ShipmentStatus::tryFrom((string) $currRaw);

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
            $m->ensureTrackSkeleton();

            $changed = array_keys($m->getChanges());
            $ignore  = ['updated_at', 'created_at', 'edited_fields', 'last_edited_by'];
            $changed = array_values(array_diff($changed, $ignore));

            if (! empty($changed)) {
                $editorId = Auth::id();
                $payload  = ['edited_fields' => $changed];

                if ($editorId) {
                    $payload['last_edited_by'] = $editorId;
                }

                $m->forceFill($payload)->saveQuietly();
            }
        });
    }

    public static function generateCode(?string $mode = null, ?int $year = null, ?int $month = null): string
    {
        $now   = now();
        $year  = $year ?: $now->year;
        $month = $month ?: $now->month;

        $prefix   = 'JSS' . str_pad($month, 2, '0', STR_PAD_LEFT) . substr($year, -2);
        $modeCode = match (strtolower((string) $mode)) {
            'sea', 'sea_freight'                                      => 'SH',
            'land', 'land_trucking', 'car_carrier', 'towing', 'truck' => 'TC',
            default                                                   => 'XX',
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
            throw new \DomainException('Pesanan tidak dapat dibatalkan (sudah terkirim / sudah dibatalkan).');
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

    public function appendTrack(TrackStatus $status, ?string $note = null, ?string $location = null): ShipmentTrack
    {
        $track = $this->tracks()->create([
            'status'   => $status->value,
            'note'     => $note,
            'location' => $location,
        ]);

        $ts = method_exists($track, 'getAttribute') && $track->getAttribute('tracked_at')
            ? $track->tracked_at
            : now();

        $isDwellingStart = in_array($status, [
            TrackStatus::Pickup,
            TrackStatus::Handover,
            TrackStatus::Stuffing,
            TrackStatus::DeliveryToPort,
            TrackStatus::Stacking,
        ], true);

        $isOnboard = in_array($status, [
            TrackStatus::UnitLoading,
            TrackStatus::OnShip,
            TrackStatus::VesselDepart,
        ], true);

        $isArrived = in_array($status, [
            TrackStatus::VesselArrival,
            TrackStatus::Unloading,
        ], true);

        if (self::hasCol('pickup_started_at') && $isDwellingStart && ! $this->pickup_started_at) {
            $this->pickup_started_at = $ts;
        }

        if (self::hasCol('onboard_at') && $isOnboard && ! $this->onboard_at) {
            $this->onboard_at = $ts;
        }

        if (self::hasCol('arrived_at') && $isArrived && ! $this->arrived_at) {
            $this->arrived_at = $ts;
        }

        if (self::hasCol('delivered_at') && $status === TrackStatus::Delivered && ! $this->delivered_at) {
            $this->delivered_at = $ts;
        }

        if ($to = $status->toShipmentStatus()) {
            if ($this->status !== $to) {
                $this->status = $to;
            }
        }

        $this->saveQuietly();

        return $track;
    }

    public function ensureTrackSkeleton(): void
    {
        $order = TrackStatus::orderForMode($this->mode);
        $existing = $this->tracks()
            ->pluck('status')
            ->map(fn($s) => $s instanceof \BackedEnum ? $s->value : (string) $s)
            ->all();

        $toCreate = array_filter($order, fn($st) => ! in_array($st->value, $existing, true));

        foreach ($toCreate as $st) {
            $this->tracks()->create([
                'status'     => $st->value,
                'tracked_at' => null,
            ]);
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
        $tracks = $this->tracks()->orderBy('tracked_at')->get();

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
            $this->delivered_at = $this->delivered_at;
        }

        foreach ($tracks as $t) {
            $s  = $t->status instanceof TrackStatus ? $t->status : TrackStatus::tryFrom((string) $t->status);
            $ts = $t->tracked_at ?? $t->created_at ?? now();

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

    public function destinationOffice()
    {
        return $this->belongsTo(Office::class, 'destination_office_id');
    }

    public function tracks()
    {
        return $this->hasMany(ShipmentTrack::class, 'shipment_id', 'id');
    }

    public function latestTrack()
    {
        return $this->hasOne(ShipmentTrack::class, 'shipment_id', 'id')
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

    public function getLatestTrackedAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->latestTrack?->tracked_at;
    }

    public function originCity()
    {
        return $this->belongsTo(\App\Models\City::class, 'origin_city_id');
    }

    public function destinationCity()
    {
        return $this->belongsTo(\App\Models\City::class, 'destination_city_id');
    }

    public function getRouteLabelAttribute(): string
    {
        $origin = $this->originCity?->name
            ?? $this->origin
            ?? $this->origin_name
            ?? $this->pol_name
            ?? $this->from;

        $dest = $this->destinationCity?->name
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
                        'seal_no'      => $c['seal_no'] ?? null,
                        'lcl_count'    => 0,
                        'unit_count'   => 0,
                    ],
                ];
            })->all();

        foreach (($this->lcl_items ?? []) as $row) {
            $ref = trim((string) ($row['container_no_ref'] ?? ''));

            if ($ref !== '' && isset($containers[$ref])) {
                $containers[$ref]['lcl_count']++;
            }
        }

        foreach (($this->units ?? []) as $row) {
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
            $seal   = $c['seal_no'] ? " / {$c['seal_no']}" : '';
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
        $cfg         = config('jss_kpi.manado', []);
        $customerIds = array_map('intval', $cfg['customer_ids'] ?? []);

        if (empty($customerIds)) {
            return false;
        }

        $customerId = (int) ($this->customer_id ?? 0);

        return in_array($customerId, $customerIds, true);
    }


    public function kpiManadoThresholds(): array
    {
        return config('jss_kpi.manado', [
            'dwelling_days' => 5,
            'sailing_days'  => 10,
            'dooring_days'  => 2,
            'total_days'    => ['normal' => 19, 'urgent' => 17],
        ])['thresholds'] ?? [
            'dwelling_days' => 5,
            'sailing_days'  => 10,
            'dooring_days'  => 2,
            'total_days'    => ['normal' => 19, 'urgent' => 17],
        ];
    }

    protected function diffDaysNullable($from, $to): ?int
    {
        if (! $from || ! $to) {
            return null;
        }

        $a = Carbon::parse($from)->startOfDay();
        $b = Carbon::parse($to)->startOfDay();

        return $a->diffInDays($b);
    }

    public function evaluateKpiForManado(): array
    {
        if (! $this->isManadoKpiTarget()) {
            return ['applies' => false];
        }

        $priority = $this->priority === 'urgent' ? 'urgent' : 'normal';
        $t        = $this->kpiManadoThresholds();

        $ms = $this->milestoneTimes();

        $dw = $this->diffDaysNullable($ms['pickup'],  $ms['onboard']);
        $sa = $this->diffDaysNullable($ms['onboard'], $ms['arrived']);
        $dr = $this->diffDaysNullable($ms['arrived'], $ms['deliv']);

        $tt = ($dw !== null && $sa !== null && $dr !== null) ? ($dw + $sa + $dr) : null;

        $okDw = is_null($dw) ? null : $dw <= ($t['dwelling_days'] ?? PHP_INT_MAX);
        $okSa = is_null($sa) ? null : $sa <= ($t['sailing_days']  ?? PHP_INT_MAX);
        $okDr = is_null($dr) ? null : $dr <= ($t['dooring_days']  ?? PHP_INT_MAX);
        $okTt = is_null($tt) ? null : $tt <= (($t['total_days'][$priority] ?? null) ?? PHP_INT_MAX);

        return [
            'applies'  => true,
            'priority' => $priority,
            'summary'  => [
                'dwelling' => [
                    'actual' => $dw,
                    'limit'  => $t['dwelling_days'],
                    'status' => is_null($okDw) ? 'PENDING' : ($okDw ? 'OK' : 'LATE'),
                ],
                'sailing'  => [
                    'actual' => $sa,
                    'limit'  => $t['sailing_days'],
                    'status' => is_null($okSa) ? 'PENDING' : ($okSa ? 'OK' : 'LATE'),
                ],
                'dooring'  => [
                    'actual' => $dr,
                    'limit'  => $t['dooring_days'],
                    'status' => is_null($okDr) ? 'PENDING' : ($okDr ? 'OK' : 'LATE'),
                ],
                'total'    => [
                    'actual' => $tt,
                    'limit'  => $t['total_days'][$priority] ?? null,
                    'status' => is_null($okTt) ? 'PENDING' : ($okTt ? 'OK' : 'LATE'),
                ],
            ],
            'badge' => is_null($okTt) ? 'Pending' : ($okTt ? 'On Time' : 'Late'),
        ];
    }

    public function kpiManadoSummaryText(): ?string
    {
        $ev = $this->evaluateKpiForManado();
        if (! ($ev['applies'] ?? false)) {
            return null;
        }

        $s = $ev['summary'];
        $p = $ev['priority'] ?? 'normal';

        if ($p === 'urgent') {
            return sprintf(
                'Dw %s/%s | Dor %s/%s | Total %s/%s',
                $s['dwelling']['actual'] ?? '-',
                $s['dwelling']['limit'] ?? '-',
                $s['dooring']['actual']  ?? '-',
                $s['dooring']['limit'] ?? '-',
                $s['total']['actual']    ?? '-',
                $s['total']['limit']   ?? '-',
            );
        }

        return sprintf(
            'Total %s/%s | Dw %s/%s | Sai %s/%s | Dor %s/%s',
            $s['total']['actual']    ?? '-',
            $s['total']['limit']   ?? '-',
            $s['dwelling']['actual'] ?? '-',
            $s['dwelling']['limit'] ?? '-',
            $s['sailing']['actual']  ?? '-',
            $s['sailing']['limit'] ?? '-',
            $s['dooring']['actual']  ?? '-',
            $s['dooring']['limit'] ?? '-',
        );
    }

    public function milestoneTimes(): array
    {
        $tracks = ($this->relationLoaded('tracks') ? $this->tracks : $this->tracks()->get())
            ->filter(fn($t) => ! empty($t->status) && ! empty($t->tracked_at))
            ->sortBy('tracked_at')
            ->values();

        $toVal = fn($s) => $s instanceof TrackStatus ? $s->value : (string) $s;

        $firstWhereAny = function (array $statuses) use ($tracks, $toVal) {
            foreach ($tracks as $t) {
                if (in_array($toVal($t->status), $statuses, true)) {
                    return $t->tracked_at;
                }
            }

            return null;
        };

        $lastWhere = function (string $status) use ($tracks, $toVal) {
            $ts = null;
            foreach ($tracks as $t) {
                if ($toVal($t->status) === $status) {
                    $ts = $t->tracked_at;
                }
            }

            return $ts;
        };

        $dwellingStarts = [
            TrackStatus::Pickup->value,
            TrackStatus::Handover->value,
            TrackStatus::Stuffing->value,
            TrackStatus::DeliveryToPort->value,
            TrackStatus::Stacking->value,
        ];

        $onboardMarks = [
            TrackStatus::UnitLoading->value,
            TrackStatus::OnShip->value,
            TrackStatus::VesselDepart->value,
        ];

        $arrivedMarks = [
            TrackStatus::VesselArrival->value,
            TrackStatus::Unloading->value,
        ];

        $deliveredMark = TrackStatus::Delivered->value;

        $pickup  = $firstWhereAny($dwellingStarts) ?: $this->requested_at;
        $onboard = $firstWhereAny($onboardMarks);
        $arrived = $firstWhereAny($arrivedMarks);
        $deliv   = $lastWhere($deliveredMark);

        return compact('pickup', 'onboard', 'arrived', 'deliv');
    }

    public function scopeTamKpi(Builder $q): Builder
    {
        $cfg         = config('jss_kpi.manado', []);
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
}
