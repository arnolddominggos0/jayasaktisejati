<?php

namespace App\Models;

use App\Enums\TrackStatus;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

class ShipmentTrack extends Model
{
    protected $table = 'shipment_tracks';

    protected $fillable = [
        'shipment_id',
        'status',
        'status_normalized',
        'tracked_at',
        'location',
        'note',
        'attachments',
        'check_result',
        'checkseet',
        'plan_loading_time_at',
        'plan_closing_time_at',
        'actual_loading_time_at',
        'actual_closing_time_at',
        'actual_berthing_time_at',
        'actual_unloading_start_time_at',
        'actual_unloading_end_time_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'status'     => TrackStatus::class,
        'attachments' => 'array',
        'checkseet' => 'array',
        'plan_loading_time_at' => 'datetime',
        'plan_closing_time_at' => 'datetime',
        'actual_loading_time_at' => 'datetime',
        'actual_closing_time_at' => 'datetime',
        'actual_berthing_time_at' => 'datetime',
        'actual_unloading_start_time_at' => 'datetime',
        'actual_unloading_end_time_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function setStatusAttribute($value): void
    {
        $enum = $value instanceof TrackStatus
            ? $value
            : TrackStatus::normalize((string) $value);

        if (! $enum) {
            throw ValidationException::withMessages([
                'status' => 'Status tidak dikenal: ' . (string) $value,
            ]);
        }

        $this->attributes['status'] = $enum->value;
        $this->attributes['status_normalized'] = true;
    }

    protected static function booted(): void
    {
        static::creating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->created_by ??= $uid;
                $track->updated_by ??= $uid;
            }
        });

        static::saving(function (ShipmentTrack $track) {
            $status = $track->status instanceof \BackedEnum
                ? $track->status->value
                : (string) $track->status;

            $map = [
                'pickup'               => 10,
                'handover'             => 20,
                'stuffing'             => 30,
                'delivery_to_port'     => 40,
                'stacking'             => 50,
                'unit_loading'         => 60,
                'onship'               => 70,
                'vessel_depart'        => 80,
                'vessel_arrival'       => 90,
                'unloading'            => 100,
                'delivery_to_customer' => 110,
                'delivered'            => 120,
                'hold'                 => 900,
                'cancelled'            => 999,
            ];

            $track->status_normalized = $map[$status] ?? 0;
        });

        static::updating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->updated_by = $uid;
            }
        });
    }
}
