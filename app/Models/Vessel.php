<?php

namespace App\Models;

use App\Supports\VesselCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vessel extends Model
{
    protected $fillable = [
        'shipping_line_id',
        'name',
        'code',
        'imo',
        'capacity',
    ];

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }

    public function getShortNameAttribute(): ?string
    {
        if (! empty($this->code)) {
            return strtoupper(trim($this->code));
        }

        if (! empty($this->name)) {
            $parts = preg_split('/[\s\-\_\/]+/', Str::upper($this->name), -1, PREG_SPLIT_NO_EMPTY);
            $overrides = config('vessel.overrides', []);
            foreach ($overrides as $k => $v) {
                if ($k === '') continue;
                if (stripos($this->name, $k) !== false) {
                    return Str::upper($v);
                }
            }
            $codes = [];
            foreach ($parts as $part) {
                $clean = preg_replace('/[^A-Z0-9]/', '', $part);
                if ($clean === '') continue;
                $found = false;
                foreach ($overrides as $k => $v) {
                    if ($k === '') continue;
                    if (stripos($clean, $k) !== false || stripos($this->name, $k) !== false) {
                        $codes[] = Str::upper($v);
                        $found = true;
                        break;
                    }
                }
                if ($found) continue;
                $codes[] = $this->twoLetterFromWord($clean);
                if (count($codes) >= 3) break;
            }
            $joined = implode('', $codes);
            $joined = preg_replace('/[^A-Z0-9]/', '', $joined);
            $joined = Str::substr($joined, 0, 6);
            if ($joined !== '') return $joined;
            $raw = preg_replace('/[^A-Z0-9]/','', $this->name);
            return Str::substr($raw, 0, min(4, strlen($raw)));
        }

        return null;
    }


    protected function twoLetterFromWord(string $w): string
    {
        $w = preg_replace('/[^A-Z0-9]/', '', Str::upper($w));
        $letters = str_split($w);
        $consonants = [];
        foreach ($letters as $ch) {
            if (! in_array($ch, ['A','E','I','O','U'])) {
                $consonants[] = $ch;
                if (count($consonants) >= 2) break;
            }
        }
        if (count($consonants) >= 2) return implode('', array_slice($consonants, 0, 2));
        $res = implode('', array_slice($letters, 0, 2));
        return str_pad($res, 2, 'X', STR_PAD_RIGHT);
    }

    protected static function booted(): void
    {
        static::creating(function (Vessel $vessel) {
            $vessel->loadMissing('shippingLine');
            $vessel->code = $vessel->code ?? VesselCode::for($vessel);
        });
    }
}
