<?php

namespace App\Models;

use App\Enums\TrackStatus;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;

class UnitTrack extends Model
{
    protected $table = 'unit_tracks';

    protected $fillable = [
        'unit_id',
        'status',
        'status_normalized',
        'tracked_at',
        'started_at',
        'location',
        'note',
        'attachments',
        'check_result',
        'checkseet',
        'plan_loading_time_at',
        'plan_closing_time_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'started_at' => 'datetime',
        'status'     => TrackStatus::class,
        'attachments' => 'array',
        'checkseet' => 'array',
        'check_result' => 'array',
        'plan_loading_time_at' => 'datetime',
        'plan_closing_time_at' => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
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
    }

    public function validateTrackingState(): void
    {
        $this->validateNoteForCriticalStatus();
        $this->validateChecksheetConsistency();
    }

    protected function validateNoteForCriticalStatus(): void
    {
        if (empty($this->tracked_at)) {
            return;
        }

        $status = $this->status instanceof TrackStatus
            ? $this->status
            : TrackStatus::tryFrom((string) $this->status);

        if (! $status?->requiresNote()) {
            return;
        }

        $shipment = $this->unit?->shipment;
        if (! $shipment || ($shipment->mode?->value ?? $shipment->mode) !== 'sea') {
            return;
        }

        $note = trim((string) $this->note);
        if (strlen($note) < 10) {
            throw ValidationException::withMessages([
                'note' => "Status \"{$status->label()}\" memerlukan catatan minimal 10 karakter.",
            ]);
        }
    }

    protected function validateChecksheetConsistency(): void
    {
        if (empty($this->tracked_at)) {
            return;
        }

        $shipment = $this->unit?->shipment;
        if (! $shipment || ($shipment->mode?->value ?? $shipment->mode) !== 'sea') {
            return;
        }

        $checkseet = $this->checkseet;
        if (! is_array($checkseet) || empty($checkseet)) {
            return;
        }

        $hasNg = false;
        foreach ($checkseet as $item) {
            if (is_array($item) && ($item['checkseet_status'] ?? null) === 'ng') {
                $hasNg = true;
                break;
            }
        }

        if (! $hasNg) {
            return;
        }

        $note = trim((string) $this->note);
        if (strlen($note) < 10) {
            throw ValidationException::withMessages([
                'note' => 'Checksheet memiliki status NG. Catatan minimal 10 karakter wajib diisi.',
            ]);
        }
    }

    protected static function booted(): void
    {
        static::creating(function (UnitTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->created_by ??= $uid;
                $track->updated_by ??= $uid;
            }

            $track->validateNoteForCriticalStatus();
            $track->validateChecksheetConsistency();
        });

        static::updating(function (UnitTrack $track) {
            $track->validateNoteForCriticalStatus();
            $track->validateChecksheetConsistency();

            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->updated_by = $uid;
            }
        });

        static::saving(function (UnitTrack $track) {
            $status = $track->status instanceof TrackStatus
                ? $track->status
                : TrackStatus::tryFrom((string) $track->status);

            if (! $status) {
                throw new \InvalidArgumentException('Unknown track status: ' . (string) $track->status);
            }

            $track->status_normalized = $status->toNormalizedValue();
        });
    }
}
