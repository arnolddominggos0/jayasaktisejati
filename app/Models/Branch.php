<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['code', 'name', 'city_id'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Smart Origin Migration (Office -> Branch, 2026-07-20): Branch is now
     * the sole source of truth for Origin. City is a direct attribute of
     * Branch — no Office involved in this relation. See
     * docs/master-office/SMART-ORIGIN-MIGRATION-BLOCKED-SCHEMA-GAP.md.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
