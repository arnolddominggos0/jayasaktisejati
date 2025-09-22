<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmadaStatusLog extends Model
{
    protected $fillable = [
        'armada_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
        'changed_at',
    ];

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
