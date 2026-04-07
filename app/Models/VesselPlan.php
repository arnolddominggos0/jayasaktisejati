<?php

namespace App\Models;

use App\Enums\VesselPlanStatus;
use App\Services\VesselPlanAnalyzer;
use App\Services\WhatsappService;
use App\Services\WhatsappMessageBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use DomainException;

class VesselPlan extends Model
{
    protected $fillable = [
        'customer_id',
        'period_month',
        'pol_id',
        'pod_id',
        'route_code',
        'status',
        'draft_kpi_total',
        'final_kpi_total',
        'sent_at',
        'sent_by',
        'feedback_reason',
        'feedback_by',
        'feedback_at',
    ];

    protected $casts = [
        'period_month' => 'date',
        'status'       => VesselPlanStatus::class,
        'sent_at'      => 'datetime',
        'feedback_at'  => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(VesselPlanItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getWhatsappPhoneAttribute(): ?string
    {
        return $this->customer?->pic_phone ?? $this->customer?->phone;
    }

    public function isDraft(): bool
    {
        return $this->status === VesselPlanStatus::Draft;
    }

    public function isSent(): bool
    {
        return $this->status === VesselPlanStatus::Sent;
    }

    public function isFinal(): bool
    {
        return $this->status === VesselPlanStatus::Final;
    }

    public function isRevision(): bool
    {
        return $this->status === VesselPlanStatus::Revision;
    }

    public function isEditable(): bool
    {
        return $this->isDraft() || $this->isRevision();
    }

    public function analyze(): array
    {
        return app(VesselPlanAnalyzer::class)->analyze($this);
    }

    public function sopStatus(): array
    {
        $analysis = $this->analyze();

        if (! ($analysis['total'] ?? null)) {
            return [
                'label' => 'BELUM ADA DATA',
                'color' => 'gray',
            ];
        }

        if (! $analysis['ok']) {
            return [
                'label' => 'MELEBIHI SOP',
                'color' => 'danger',
            ];
        }

        return [
            'label' => 'SESUAI SOP',
            'color' => 'success',
        ];
    }

    public function markAsSent(int $userId): void
    {
        if (! $this->isDraft()) {
            throw new DomainException('Harus draft.');
        }

        $analysis = $this->analyze();

        if (! $analysis['ok']) {
            throw new DomainException('Belum sesuai SOP.');
        }

        $this->update([
            'status'           => VesselPlanStatus::Sent,
            'sent_at'          => now(),
            'sent_by'          => $userId,
            'draft_kpi_total'  => $analysis['total'],
        ]);

        $phone = $this->whatsapp_phone;

        if ($phone) {
            $message = app(WhatsappMessageBuilder::class)
                ->buildFullMessage($this);

            app(WhatsappService::class)->send($phone, $message);
        }
    }

    public function markAsFinal(int $userId): void
    {
        if (! $this->isSent()) {
            throw new DomainException('Harus sent.');
        }

        $analysis = $this->analyze();

        DB::transaction(function () use ($analysis) {

            $this->update([
                'status'           => VesselPlanStatus::Final,
                'final_kpi_total'  => $analysis['total'],
            ]);

            foreach ($this->items as $item) {

                $item->voyage ?? $item->voyage()->create([
                    'vessel_plan_id'    => $this->id,
                    'shipping_line_id' => $item->shipping_line_id,
                    'vessel_id'        => $item->vessel_id,
                    'pol_id'           => $this->pol_id,
                    'pod_id'           => $this->pod_id,
                    'voyage_no'        => 'VY-' . $item->planned_etd->format('Ym') . '-' . $item->id,
                    'etd'              => $item->planned_etd,
                    'eta'              => $item->planned_eta,
                    'period_month'     => $this->period_month,
                ]);
            }
        });
    }

    public function approve(int $userId): void
    {
        $this->markAsFinal($userId);
    }

    public function reject(string $reason, int $userId): void
    {
        $this->update([
            'status'          => VesselPlanStatus::Revision,
            'feedback_reason' => $reason,
            'feedback_by'     => $userId,
            'feedback_at'     => now(),
        ]);
    }

    public function kpiDeviation(): ?float
    {
        if (!$this->draft_kpi_total || !$this->final_kpi_total) {
            return null;
        }

        return $this->final_kpi_total - $this->draft_kpi_total;
    } 
}
