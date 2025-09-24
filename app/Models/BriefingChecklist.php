<?php

namespace App\Models;

use App\Enums\ChecklistStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefingChecklist extends Model
{
    protected $table = 'briefing_checklists';

    protected $fillable = [
        'session_id',
        'item',
        'status',
        'remark'
    ];

    protected $casts = [
        'status' => ChecklistStatus::class,
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BriefingSession::class, 'session_id');
    }
}
