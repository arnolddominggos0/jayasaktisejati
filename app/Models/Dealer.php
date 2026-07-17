<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DOMAIN-02 — Dealer: jaringan distribusi milik Commercial Customer.
 * Berlaku hanya untuk Vehicle Shipment; General Cargo tidak memakai master ini.
 *
 * Aturan domain (frozen): Customer = hubungan bisnis; Dealer = jaringan
 * distribusinya. Dealer TIDAK PERNAH dianggap sebagai Customer. OCR hanya
 * me-resolve Dealer; customer_id shipment DITURUNKAN dari dealer->customer_id.
 */
class Dealer extends Model
{
    protected $fillable = [
        'customer_id',
        'name',
        'aliases',
        'is_active',
    ];

    protected $casts = [
        'aliases'   => 'array',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Normalisasi nama untuk pencocokan OCR — generik, tanpa aturan
     * per-perusahaan: buang prefiks badan usaha + tanda baca, uppercase.
     * "PT. Hasjrat Abadi" → "HASJRAT ABADI".
     */
    public static function normalizeName(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $n = strtoupper(trim($name));
        $n = preg_replace('/^(PT|CV|UD|TB)\.?\s+/', '', $n) ?? $n;
        $n = preg_replace('/[.,]/', '', $n) ?? $n;

        return trim(preg_replace('/\s+/', ' ', $n) ?? $n);
    }

    /**
     * Cari dealer aktif berdasarkan teks dokumen (nama atau alias),
     * dengan normalisasi dua arah. Null bila tidak ada / ambigu —
     * keputusan tetap di Office Admin.
     */
    public static function resolveFromText(?string $text): ?self
    {
        $needle = self::normalizeName($text);
        if ($needle === '' || mb_strlen($needle) < 3) {
            return null;
        }

        $matches = self::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (self $dealer) use ($needle) {
                if (self::normalizeName($dealer->name) === $needle) {
                    return true;
                }
                foreach ($dealer->aliases ?? [] as $alias) {
                    if (self::normalizeName($alias) === $needle) {
                        return true;
                    }
                }

                return false;
            })
            ->values();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
