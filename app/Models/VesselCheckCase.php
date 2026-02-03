<?php

namespace App\Models;

use App\Enums\VesselCheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheckCase extends Model
{
    protected $fillable = [
        'shipping_schedule_id',
        'case_status',
        'delay_flag',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opened_at'  => 'datetime',
        'closed_at'  => 'datetime',
        'delay_flag' => 'boolean',
        'case_status'=> VesselCheckStatus::class,
    ];

    public function shippingSchedule(): BelongsTo
    {
        return $this->belongsTo(ShippingSchedule::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VesselCheckLog::class);
    }

    public function delays(): HasMany
    {
        return $this->hasMany(VesselCheckDelay::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(VesselCheckRequest::class);
    }

    public function alternatives(): HasMany
    {
        return $this->hasMany(VesselCheckAlternative::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(VesselCheckScheduleRevision::class);
    }

    /* ===== Guard Helper ===== */

    public function hasDelayAnalysis(): bool
    {
        return $this->delays()->exists();
    }

    public function hasApprovedAlternative(): bool
    {
        return $this->alternatives()
            ->where('approval_status', 'APPROVED')
            ->exists();
    }

    public function hasPendingAlternative(): bool
    {
        return $this->alternatives()
            ->where('approval_status', 'PENDING')
            ->exists();
    }

    public function hasPendingRequest(): bool
    {
        return $this->requests()
            ->where('status', 'SENT')
            ->exists();
    }
}
