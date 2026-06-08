<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @deprecated Voyage-centric architecture. ShippingSchedule tidak lagi dipakai
 *             sebagai layer operasional. Akan dihapus setelah satu siklus operasional
 *             berjalan menggunakan Voyage langsung.
 */
class ShippingSchedule extends Model
{
    protected $fillable = [
        'voyage_id',
        'period_month',
        'jss',
        'cargo_plan',
        'state',
    ];

    protected $casts = [
        'period_month' => 'date',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function vesselChecks(): HasMany
    {
        return $this->hasMany(VesselCheck::class);
    }

    public function vesselCheckCases(): HasMany
    {
        return $this->hasMany(VesselCheckCase::class);
    }

    public function getEtdDateAttribute()
    {
        return $this->voyage?->etd;
    }

    public function getEtaDateAttribute()
    {
        return $this->voyage?->eta;
    }

    public function getAtdDateAttribute()
    {
        return $this->voyage?->atd_at;
    }

    public function getAtaDateAttribute()
    {
        return $this->voyage?->ata_at;
    }
}
