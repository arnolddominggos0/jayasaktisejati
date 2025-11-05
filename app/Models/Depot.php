<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Depot extends Model
{
    protected $fillable = [
        'code',
        'name',
        'mode',
        'port_id',
        'service_types',
        'address',
        'branch_id',
        'coordinator_user_id',
    ];

    protected $casts = [
        'service_types' => 'array',
    ];

    protected static function booted(): void
    {+-
        static::saving(function (Depot $m) {
            if ($m->mode === 'sea_freight') $m->mode = 'sea';
            if (! in_array($m->mode, ['sea', 'land'], true)) $m->mode = 'land';
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }
    public function manpowers(): HasMany
    {
        return $this->hasMany(Manpower::class);
    }
    public function armadas(): HasMany
    {
        return $this->hasMany(Armada::class);
    }
    public function briefingSessions(): HasMany
    {
        return $this->hasMany(BriefingSession::class);
    }
    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_id');
    }
    public function shipments(): HasMany
    {
        return $this->hasMany(\App\Models\Shipment::class, 'assigned_depot_id');
    }

    public static function resolveIdFor(?int $branchId, ?string $mode, ?int $voyageId = null): ?int
    {
        if (!$branchId || !$mode) return null;
        $mode = $mode === 'sea_freight' ? 'sea' : $mode;
        $q = static::query()->where('branch_id', $branchId)->where('mode', $mode);
        if ($mode === 'sea' && $voyageId) {
            $polId = \App\Models\Voyage::whereKey($voyageId)->value('port_from_id');
            if ($polId) {
                $byPol = (clone $q)->where('port_id', $polId)->orderBy('name')->value('id');
                if ($byPol) return (int) $byPol;
            }
        }
        return $q->orderBy('name')->value('id');
    }
}
