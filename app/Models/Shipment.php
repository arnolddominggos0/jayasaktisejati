<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\{ShipmentStatus, ShipmentMode, ServiceType, CargoType, DeliveryScope, RequestType};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

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

        // A. Data Customer & Dokumen
        'pic_name',
        'pic_phone',
        'request_type',
        'doc_number',
        'priority',
        'attachments',

        // B. Informasi Rute & Moda
        'mode',
        'route_from',
        'route_to',
        'route_summary',

        // Layanan & Muatan
        'service_type',
        'service_option',
        'delivery_scope',
        'cargo_type',
        'container_size',
        'container_qty',

        // Totals LCL
        'packages_total',
        'cbm_total',
        'weight_total',

        // Laut
        'vessel_name',
        'voyage',
        'pol',
        'pod',
        'etd',
        'eta',
        'schedule_id',

        // Darat
        'vehicle_type',
        'vehicle_plate',
        'pickup_date',
        'driver_id',

        // Umum
        'status',
        'notes',

        // Perubahan
        'requested_at',
        'cancelled_at',
        'cancelled_by',
        'edited_fields',
        'last_edited_by',

        // Konfirmasi
        'confirm_is_true',

        // Lead Time TL
        'estimated_ready_at',
    ];

    protected $casts = [
        'status'             => ShipmentStatus::class,
        'mode'               => ShipmentMode::class,
        'service_type'       => ServiceType::class,
        'cargo_type'         => CargoType::class,
        'request_type'       => RequestType::class,
        'delivery_scope'     => DeliveryScope::class,

        'container_qty'      => 'integer',
        'packages_total'     => 'integer',
        'cbm_total'          => 'decimal:3',
        'weight_total'       => 'decimal:2',

        'requested_at'       => 'datetime',
        'attachments'        => 'array',
        'etd'                => 'datetime',
        'eta'                => 'datetime',
        'pickup_date'        => 'datetime',
        'estimated_ready_at' => 'datetime',
        'confirm_is_true'    => 'boolean',
        'cancelled_at'       => 'datetime',
        'edited_fields'      => 'array',
    ];

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
                $m->eta = self::computeEta($modeCode, (string) ($m->priority ?? 'normal'))->toDateTimeString();
            }
        });

        static::saving(function (Shipment $m) {
            if ($m->mode === ShipmentMode::Sea) {
                $m->service_type   = ServiceType::SeaFreight;
                $m->service_option = in_array($m->service_option, ['fcl', 'lcl'], true)
                    ? $m->service_option
                    : ($m->service_option ?: 'fcl');

                if ($m->service_option !== 'fcl') {
                    $m->container_size = null;
                    $m->container_qty  = null;
                }

                $m->vehicle_type = $m->vehicle_plate = $m->driver_name = $m->driver_phone = null;
                $m->pickup_date = $m->estimated_ready_at = null;
            } else {
                $m->service_type   = $m->vehicle_type === 'car_carrier'
                    ? ServiceType::CarCarrier
                    : ServiceType::LandTrucking;

                $m->service_option = match ($m->vehicle_type) {
                    'car_carrier' => 'car_carrier',
                    'towing'      => 'towing',
                    default       => 'truck',
                };

                $base = $m->pickup_date ?: $m->requested_at ?: now();

                $m->estimated_ready_at = strtolower((string) $m->priority) === 'urgent'
                    ? Carbon::parse($base)->endOfDay()
                    : Carbon::parse($base)->addDay()->endOfDay();

                $m->vessel_name = $m->voyage = $m->pol = $m->pod = null;
                $m->etd = null;
                $m->schedule_id = null;
                $m->container_size = null;
                $m->container_qty  = null;
            }

            $middle = $m->mode === ShipmentMode::Sea
                ? strtoupper((string)$m->service_option)
                : ucfirst(str_replace('_', ' ', (string)$m->service_option));

            $scope = DeliveryScope::short($m->delivery_scope) ? ' • ' . DeliveryScope::short($m->delivery_scope) : '';

            $m->route_summary = implode(' → ', array_filter([
                optional($m->originCity ?? null)->name ?? '' . $m->route_from,
                $middle,
                optional($m->destinationCity ?? null)->name ?? '' . $m->route_to,
            ])) . $scope;
        });

        static::updated(function (Shipment $m) {
            $changed = array_keys($m->getChanges());
            $ignore  = ['updated_at', 'created_at', 'edited_fields', 'last_edited_by'];
            $changed = array_values(array_diff($changed, $ignore));

            if ($changed) {
                $editorId = Auth::id();
                $payload = ['edited_fields' => $changed];
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
        if ($last && preg_match('/^' . $prefix . '(\d{4})$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }

    public static function computeEta(string $modeCode, string $priority, ?Carbon $base = null): Carbon
    {
        $base = $base?->copy() ?? now();
        if ($modeCode === 'SH') { 
            $days = strtolower($priority) === 'urgent' ? 17 : 19;
            return $base->addDays($days)->endOfDay();
        }
        return strtolower($priority) === 'urgent' ? $base->endOfDay() : $base->addDay()->endOfDay();
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

        $this->status       = ShipmentStatus::Cancelled;
        $this->cancelled_at = now();
        $this->cancelled_by = $userId;
        $this->save();
    }

    public function uncancel(?int $userId = null): void
    {
        if ($this->status !== ShipmentStatus::Cancelled) {
            return;
        }

        $this->status       = ShipmentStatus::Pending;
        $this->cancelled_at = null;
        $this->cancelled_by = null;
        $this->save();
    }

    // Relations
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
    public function receiver()
    {
        return $this->belongsTo(Customer::class, 'receiver_id');
    }
    public function schedule()
    {
        return $this->belongsTo(FleetSchedule::class, 'schedule_id');
    }
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }
    public function originCity()
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }
    public function destinationCity()
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }
    public function originOffice()
    {
        return $this->belongsTo(Office::class, 'origin_office_id');
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
}
