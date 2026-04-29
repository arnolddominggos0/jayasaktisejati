<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class VoyageMilestone extends Model
{
    protected $fillable = [
        'voyage_id',
        'code',
        'milestone_date',
        'actual_date',
        'port_id',
        'speed_knots',
        'note',
        'status',
    ];

    protected $casts = [
        'milestone_date' => 'datetime',
        'actual_date' => 'datetime',
        'speed_knots' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($milestone) {

            if ($milestone->actual_date && $milestone->actual_date->isFuture()) {
                throw ValidationException::withMessages([
                    'actual_date' => 'Tanggal laporan tidak boleh melewati tanggal saat ini.'
                ]);
            }

            if (
                $milestone->speed_knots !== null &&
                $milestone->speed_knots < 10 &&
                empty($milestone->note)
            ) {
                throw ValidationException::withMessages([
                    'note' => 'Speed < 10 knots wajib isi catatan.'
                ]);
            }

            if ($milestone->actual_date && $milestone->milestone_date) {

                $milestone->status =
                    $milestone->actual_date->lte($milestone->milestone_date)
                    ? 'ontime'
                    : 'late';
            } else {

                $milestone->status = null;
            }
        });
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->milestone_date || $this->actual_date) {
            return false;
        }

        return now()->gt($this->milestone_date);
    }

    public function getIsDueTodayAttribute(): bool
    {
        if (!$this->milestone_date || $this->actual_date) {
            return false;
        }

        return now()->isSameDay($this->milestone_date);
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }
}
