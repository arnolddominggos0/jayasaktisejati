<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\ShipmentStatus;
use BackedEnum;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'customer_id',
        'origin_office_id',
        'destination_office_id',

        // A. Data Customer & Dokumen
        'pic_name',
        'pic_phone',
        'request_type',
        'doc_number',
        'priority',
        'requested_at',
        'attachments',

        // B. Informasi Rute & Moda
        'mode',
        'route_from',
        'route_to',

        // Laut
        'vessel_name',
        'voyage',
        'pol',
        'pod',
        'etd',
        'eta',

        // Darat
        'vehicle_type',
        'vehicle_plate',
        'pickup_date',
        'driver_name',
        'driver_phone',

        // Umum
        'service_type',
        'status',
        'notes',

        // Konfirmasi
        'confirm_is_true',
    ];

    protected $casts = [
        'status'          => ShipmentStatus::class,
        'requested_at'    => 'datetime',
        'attachments'     => 'array',
        'etd'             => 'datetime',
        'eta'             => 'datetime',
        'pickup_date'     => 'datetime',
        'confirm_is_true' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            if (empty($shipment->status)) {
                $shipment->status = ShipmentStatus::default();
            }
            if (empty($shipment->code)) {
                $attempts = 0;
                do {
                    $shipment->code = self::generateCode();
                    $exists = self::where('code', $shipment->code)->exists();
                    $attempts++;
                } while ($exists && $attempts < 3);
            }
        });
    }

    public static function generateCode(?int $year = null): string
    {
        $year = $year ?: now()->year;
        $prefix = "JSS-$year-";

        $last = static::query()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('code');

        $next = 1;
        if ($last) {
            $num = (int) substr($last, strrpos($last, '-') + 1);
            $next = $num + 1;
        }

        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /** Accessor untuk UI agar selalu string, menghindari enum object di tabel/Blade */
    public function getStatusValueAttribute(): string
    {
        $s = $this->status;
        return $s instanceof BackedEnum ? $s->value : (string) $s;
    }

    // Relations
    public function customer()         { return $this->belongsTo(Customer::class); }
    public function originOffice()     { return $this->belongsTo(Office::class, 'origin_office_id'); }
    public function destinationOffice(){ return $this->belongsTo(Office::class, 'destination_office_id'); }
}
