<?php

namespace App\Models;

use App\Enums\SlaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaResult extends Model
{
    protected $table = 'sla_results';

    protected $fillable = [
        'voyage_id',
        'sla_rule_id',
        'activity',
        'start_at',
        'end_at',
        'target_days',
        'actual_days',
        'status',
        'late_days',
    ];

    protected $casts = [
        'start_at'   => 'datetime',
        'end_at'     => 'datetime',
        'actual_days' => 'decimal:2',
        'late_days'  => 'decimal:2',
        'status'     => SlaStatus::class,
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(SlaRule::class, 'sla_rule_id');
    }
}
