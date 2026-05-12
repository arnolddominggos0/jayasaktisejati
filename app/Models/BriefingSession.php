<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\MPCheckStatus;
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
        'mp_check_status',
        'approved_at',
        'approved_by',
        'backup_required',
        'backup_type',     // internal/external
        'backup_notes',
        'briefing_evidence_path',
    ];

    protected $casts = [
        'date' => 'date',
        'summary_sufficient' => 'boolean',
        'mp_check_status' => MPCheckStatus::class,
        'approved_at' => 'datetime',
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
        return $this->hasMany(BriefingAttendance::class, 'session_id')
            ->where('attendance_status', AttendanceStatus::Present->value);
    }

    public function getDisplayLabelAttribute(): string
    {
        $date = $this->date ? $this->date->format('Y-m-d') : (string) $this->date;
        $depot = $this->depot->name ?? '-';

        return "{$date} · {$depot}";
    }

    public function refreshSufficientFlag(): void
    {
        $present = $this->presentAttendances()->count();
        $target = (int) $this->summary_headcount;
        $this->summary_sufficient = $target > 0 && $present >= $target;
        $this->saveQuietly();
    }

    protected static function booted()
    {
        static::saving(function ($session) {
            $present = $session->presentAttendances()->count();
            $target = (int) $session->summary_headcount;
            $session->summary_sufficient = $target > 0 && $present >= $target;
        });
    }
}
