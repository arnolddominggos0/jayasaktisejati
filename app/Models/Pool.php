<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pool extends Model
{
    protected $fillable = [
        'code',
        'name',
        'mode',
        'address',
        'branch_id',
        'coordinator_user_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (Pool $m) {
            // Guard: reject assigning a coordinator already assigned to another depot or pool.
            if ($m->isDirty('coordinator_user_id') && $m->coordinator_user_id !== null) {
                $existingPool = static::query()
                    ->where('coordinator_user_id', $m->coordinator_user_id)
                    ->whereKeyNot($m->getKey() ?? 0)
                    ->exists();

                if ($existingPool) {
                    throw new \InvalidArgumentException('Coordinator is already assigned to another pool.');
                }

                $existingDepot = \App\Models\Depot::query()
                    ->where('coordinator_user_id', $m->coordinator_user_id)
                    ->exists();

                if ($existingDepot) {
                    throw new \InvalidArgumentException('Coordinator is already assigned to a depot.');
                }
            }
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
}
