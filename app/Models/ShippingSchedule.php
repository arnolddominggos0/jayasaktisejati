<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingSchedule extends Model
{
    protected $fillable = [
        'shipping_line_id',
        'code',
        'state',
        'etd',
        'eta',
        'vessel_id',
        'vessel_name',
        'voyage_no',
        'cargo_plan_total',
        'final_source',
        'final_attachment_path',
        'final_note',
        'approved_by_name',
        'approved_at',
        'final_email_message_id',
        'final_email_subject',
        'final_email_from',
        'final_email_received_at',
        'revision_count',
        'last_revision_at',
        'period_month',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'approved_at' => 'datetime',
        'final_email_received_at' => 'datetime',
        'period_month' => 'date',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShippingScheduleItem::class, 'shipping_schedule_id');
    }
}
