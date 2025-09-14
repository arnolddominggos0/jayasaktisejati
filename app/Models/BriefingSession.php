<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BriefingSession extends Model
{
    protected $fillable = ['date','depot_id','coordinator_user_id','notes'];

    protected $casts = ['date' => 'date'];

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(BriefingChecklist::class, 'session_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ManpowerAttendance::class, 'session_id');
    }
}
