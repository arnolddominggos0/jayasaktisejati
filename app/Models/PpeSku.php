<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpeSku extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'brand',
        'model',
        'size',
        'is_serialized',
        'min_qty',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $sku) {
            // Kalau user nekad ngisi manual, ya sudah dipakai. Kalau kosong, generate.
            if (!filled($sku->code)) {
                $sku->code = static::generateCode($sku->type);
            }
        });
    }

    public static function generateCode(string $type): string
    {
        $map = [
            'helm'           => 'HELM',
            'rompi'          => 'ROMP',
            'sarung_tangan'  => 'STGN',
            'sepatu'         => 'SEPA',
        ];

        $prefix = $map[$type] ?? strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $type), 0, 4)) ?: 'APD';
        $year   = now()->format('Y');

        $lastCode = static::query()
            ->where('type', $type)
            ->whereYear('created_at', now()->year)
            ->where('code', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($lastCode && preg_match('/(\d+)$/', $lastCode, $m)) {
            $next = (int) $m[1] + 1;
        }

        return sprintf('%s-%s-%03d', $prefix, $year, $next);
    }

    public function items()
    {
        return $this->hasMany(PpeItem::class);
    }
}
