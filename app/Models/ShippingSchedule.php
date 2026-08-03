<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @deprecated LEGACY — domain jadwal lama.
 *
 * Vessel Plan adalah sumber jadwal yang otoritatif (ARCH-02 / ARCH-03):
 *
 *     Vessel Plan → Finalisasi → Voyage → Shipment → Monitoring
 *
 * Tabel `shipping_schedules` saat ini KOSONG (0 baris), namun sejumlah Action
 * dan Widget masih membaca/menulis ke sana. Selama dibiarkan aktif, ia
 * berpotensi menjadi sumber jadwal tandingan.
 *
 * JANGAN membangun fitur baru di atas model ini.
 * Kebutuhan jadwal baru harus bermuara pada Vessel Plan.
 *
 * Penghapusan menunggu keputusan produk — lihat
 * docs/audit/ARCH-02-LAYER-1-SOURCE-OF-TRUTH.md §4b.
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
