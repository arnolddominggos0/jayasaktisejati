<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BriefingSession extends Model
{
    protected $table = 'briefing_sessions';

    protected $fillable = [
        'date',
        'depot_id',
        'coordinator_user_id',
        'notes',
        'summary_headcount',
        'summary_sufficient',
        'summary_solution',
    ];

    protected $casts = [
        'date' => 'date',
        'summary_sufficient' => 'boolean',
    ];

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'depot_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(BriefingAttendance::class, 'session_id');
    }

    public function presentAttendances()
    {
        return $this->hasMany(\App\Models\BriefingAttendance::class, 'session_id')
            ->where('attendance_status', AttendanceStatus::Present->value);
    }

    public function getDisplayLabelAttribute(): string
    {
        $date  = $this->date ? $this->date->format('Y-m-d') : (string) $this->date;
        $depot = $this->depot->name ?? '-';
        return "{$date} · {$depot}";
    }

    protected static function booted()
    {
        static::saving(function ($session) {
            $present = $session->presentAttendances()->count();
            $target  = (int) $session->summary_headcount;
            $session->summary_sufficient = $target > 0 && $present >= $target;
        });
    }
}
