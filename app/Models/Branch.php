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
     * Branch is the sole source of truth for Origin. City is a direct
     * attribute of Branch.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
