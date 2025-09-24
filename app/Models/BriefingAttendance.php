<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefingAttendance extends Model
{
    protected $table = 'briefing_attendances';

    protected $fillable = [
        'session_id',
        'manpower_id',
        'ppeitem_id',
        'attendance_status',
        'temperature',
        'bp',
        'has_ppe',
        'remark',
        'created_by',
    ];

    protected $casts = [
        'has_ppe' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BriefingSession::class, 'session_id');
    }

    public function manpower(): BelongsTo
    {
        return $this->belongsTo(Manpower::class, 'manpower_id');
    }
    public function ppeItems()
    {
        return $this->hasMany(\App\Models\BriefingAttendancePpeItem::class, 'attendance_id');
    }
    public function getPpeAllGoodAttribute(): bool
    {
        $items = $this->ppeItems()->pluck('condition', 'ppe_type');
        return $items->count() >= 4 && $items->every(fn($c) => $c === \App\Enums\PpeCondition::Baik);
    }
}
