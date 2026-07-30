<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class MpMedicalAction extends Model
{
    protected $table = 'mp_medical_actions';

    protected $fillable = [
        'mp_check_id',
        'action',
        'notes',
        'performed_at',
        'performed_by',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
    ];

    public function check(): BelongsTo
    {
        return $this->belongsTo(MpCheck::class, 'mp_check_id');
    }

    protected static function booted(): void
    {
        static::updating(function (self $action) {
            throw new RuntimeException('MpMedicalAction bersifat immutable — tidak boleh diubah. Buat record baru.');
        });

        static::deleting(function (self $action) {
            throw new RuntimeException('MpMedicalAction bersifat immutable — tidak boleh dihapus.');
        });
    }
}
