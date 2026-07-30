<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class MpCheck extends Model
{
    protected $table = 'mp_checks';

    public const TYPE_INITIAL = 'initial';

    public const TYPE_RECHECK = 'recheck';

    protected $fillable = [
        'attendance_id',
        'check_type',
        'temperature',
        'bp_systolic',
        'bp_diastolic',
        'status',
        'health_complaint',
        'checked_at',
        'checked_by',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'checked_at'  => 'datetime',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(BriefingAttendance::class, 'attendance_id');
    }

    public function medicalActions(): HasMany
    {
        return $this->hasMany(MpMedicalAction::class, 'mp_check_id')
            ->orderBy('performed_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->check_type === self::TYPE_RECHECK
            ? 'Pemeriksaan Ulang'
            : 'Pemeriksaan';
    }

    public function getBpAttribute(): ?string
    {
        if ($this->bp_systolic && $this->bp_diastolic) {
            return "{$this->bp_systolic}/{$this->bp_diastolic}";
        }

        return null;
    }

    protected static function booted(): void
    {
        static::updating(function (self $check) {
            throw new RuntimeException('MpCheck bersifat immutable — tidak boleh diubah. Buat record baru.');
        });

        static::deleting(function (self $check) {
            throw new RuntimeException('MpCheck bersifat immutable — tidak boleh dihapus.');
        });
    }
}
