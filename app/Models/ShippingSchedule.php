<?php

namespace App\Models;

use App\Enums\ScheduleState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingSchedule extends Model
{
    protected $fillable = [
        'voyage_id',
        'shipping_line_id',
        'vessel_id',
        'pol_id',
        'pod_id',
        'voyage_no',
        'cargo_plan',
        'jss',
        'dwelling_days',
        'etd',
        'eta',
        'period_month',
        'state',
        'approved_by_name',
        'final_note',
        'final_source',
        'final_attachment_path',
        'finalized_at',
        'kpi_sailing_days',
        'actual_sailing_days',
        'vessel_name',
        'is_urgent',
    ];

    protected $casts = [
        'etd'          => 'datetime',
        'eta'          => 'datetime',
        'period_month' => 'date',
        'finalized_at' => 'datetime',
        'state'        => ScheduleState::class,
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_id');
    }

    public function pol(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }
}
