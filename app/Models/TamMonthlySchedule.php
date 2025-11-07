<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TamMonthlySchedule extends Model
{
    protected $fillable = [
        'period_month',
        'status',
        'version',
        'payload',
        'schedule_ids',
        'total_plan',
        'draft_path',
        'final_path',
        'draft_message',
        'generated_at',
        'sent_at',
        'feedback_received_at',
        'finalized_at',
        'generated_by_name',
        'approved_by_name',
    ];

    protected $casts = [
        'period_month' => 'date',
        'payload' => 'array',
        'schedule_ids' => 'array',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
        'feedback_received_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFeedback(): bool
    {
        return $this->status === 'feedback';
    }

    public function isFinal(): bool
    {
        return $this->status === 'final';
    }
}
