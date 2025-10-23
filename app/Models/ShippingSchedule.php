<?php

namespace App\Models;

use App\Enums\ScheduleState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class ShippingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'state',
        'etd',
        'eta',
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
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'approved_at' => 'datetime',
        'final_email_received_at' => 'datetime',
        'period_month' => 'date',
        'state' => ScheduleState::class,
    ];

    public function scopeInYear($q, int $year)
    {
        return $q->whereYear('period_month', $year);
    }
    public function scopeInMonth($q, int $year, int $month)
    {
        return $q->whereYear('period_month', $year)->whereMonth('period_month', $month);
    }

    public function periodLabel(): string
    {
        return $this->period_month ? $this->period_month->translatedFormat('F Y') : '-';
    }

    public function items()
    {
        return $this->hasMany(ShippingScheduleItem::class);
    }

    public function setEmailFinalMeta(array $meta): void
    {
        $this->final_source = 'email';
        $this->final_email_message_id  = $meta['message_id'] ?? null;
        $this->final_email_subject     = $meta['subject'] ?? null;
        $this->final_email_from        = $meta['from'] ?? null;
        $this->final_email_received_at = $meta['received_at'] ?? now();
    }

    public function canFinalizeFromEmail(): bool
    {
        return $this->state === ScheduleState::Draft;
    }

    public function finalizeFromEmail(array $payload): void
    {
        $this->state = ScheduleState::Final;
        $this->final_source = 'email';
        $this->approved_by_name = $payload['approved_by_name'] ?? null;
        $this->final_note = $payload['final_note'] ?? null;
        $this->approved_at = $payload['approved_at'] ?? now();

        if (!$this->period_month) {
            $base = $this->etd ?: $this->approved_at ?: now();
            $this->period_month = $base->copy()->startOfMonth();
        }

        if (!empty($payload['final_attachment_path'])) {
            $this->final_attachment_path = $payload['final_attachment_path'];
        }
        $this->save();
    }


    public function finalBaselineDate(): ?Carbon
    {
        if ($this->etd instanceof Carbon) return $this->etd->copy();
        if ($this->approved_at instanceof Carbon) return $this->approved_at->copy();
        return null;
    }

    public function revisionDeadline(): ?Carbon
    {
        $base = $this->finalBaselineDate();
        return $base?->copy()->addDays(6);
    }

    public function withinRevisionWindow(?Carbon $now = null): bool
    {
        if ($this->state !== ScheduleState::Final) return false;
        $now ??= now();
        $deadline = $this->revisionDeadline();
        return $deadline ? $now->lte($deadline) : false;
    }

    public function applyLimitedRevision(array $data): void
    {
        if (!$this->withinRevisionWindow()) {
            throw new \RuntimeException('Batas revisi terlewati. Perubahan tidak diizinkan.');
        }

        $allowed = [
            'vessel_name',
            'voyage_no',
            'etd',
            'eta',
            'cargo_plan_total',
        ];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $this->{$key} = $data[$key];
            }
        }

        $this->last_revision_at = now();
        $this->revision_count = (int) $this->revision_count + 1;
        $this->state = ScheduleState::Final;

        $this->save();
    }
}
