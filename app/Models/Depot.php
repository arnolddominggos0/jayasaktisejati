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
        'address',
        'branch_id',
        'coordinator_user_id'
    ];

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
}
