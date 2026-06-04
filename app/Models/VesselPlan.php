<?php

namespace App\Models;

use App\Enums\VesselPlanStatus;
use App\Models\Customer;
use App\Models\Port;
use App\Services\VesselPlanAnalyzer;
use App\Services\VesselPlanFinalizationService;
use App\Services\VesselPlanGenerator;
use App\Services\VesselPlanSubmissionService;
use App\Services\WhatsappMessageBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class VesselPlan extends Model
{
    public const SNAPSHOT_STAGE_DRAFT = 'draft_submitted';
    public const SNAPSHOT_STAGE_FINAL = 'final_approved';
    public const REVIEW_ACTION_DRAFT_SUBMITTED = 'draft_submitted';
    public const REVIEW_ACTION_REVISION_REQUESTED = 'revision_requested';
    public const REVIEW_ACTION_APPROVED = 'approved';

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

    public function snapshots(): HasMany
    {
        return $this->hasMany(VesselPlanSnapshot::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VesselPlanReview::class)->latest('acted_at');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function pol(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public static function resolveTamCustomer(): ?Customer
    {
        $configuredId = (int) config('jss_customers.tam_id', 0);

        if ($configuredId > 0) {
            $customer = Customer::find($configuredId);
            if ($customer) {
                return $customer;
            }
        }

        return Customer::query()
            ->whereRaw('LOWER(name) = ?', ['toyota astra motor'])
            ->orWhereRaw('LOWER(name) like ?', ['%toyota astra motor%'])
            ->first();
    }

    public static function generateForMonth(Carbon $periodMonth): self
    {
        return app(VesselPlanGenerator::class)->generateForMonth($periodMonth);
    }

    public function resolveRoutePortIds(): array
    {
        $polId = $this->pol_id;
        $podId = $this->pod_id;

        if ($polId && $podId) {
            return ['pol_id' => $polId, 'pod_id' => $podId];
        }

        [$routePol, $routePod] = array_pad(explode('-', (string) $this->route_code, 2), 2, null);

        $polCode = $routePol ?: config('tam.route.pol_code');
        $podCode = $routePod ?: config('tam.route.pod_code');

        if (! $polCode || ! $podCode) {
            return ['pol_id' => $polId, 'pod_id' => $podId];
        }

        $resolvedPolId = $polId ?: Port::query()->where('code', strtoupper($polCode))->value('id');
        $resolvedPodId = $podId ?: Port::query()->where('code', strtoupper($podCode))->value('id');

        return ['pol_id' => $resolvedPolId, 'pod_id' => $resolvedPodId];
    }

    public function syncRoutePorts(): bool
    {
        $ports = $this->resolveRoutePortIds();

        if (! $ports['pol_id'] || ! $ports['pod_id']) {
            return false;
        }

        if ($this->pol_id === $ports['pol_id'] && $this->pod_id === $ports['pod_id']) {
            return true;
        }

        $this->forceFill($ports)->saveQuietly();

        return true;
    }

    public function getWhatsappPhoneAttribute(): ?string
    {
        return $this->customer?->pic_phone ?? $this->customer?->phone;
    }

    public function getWhatsappRecipientNameAttribute(): ?string
    {
        return $this->customer?->pic_name ?: $this->customer?->name;
    }

    public function getKpiTotalAttribute(): ?float
    {
        return match ($this->status) {
            VesselPlanStatus::Final => $this->final_kpi_total,
            VesselPlanStatus::Sent  => $this->draft_kpi_total,
            default => $this->analyze()['sailing_avg'] ?? null,
        };
    }

    public function getKpiDeviationAttribute(): ?float
    {
        if (!$this->draft_kpi_total || !$this->final_kpi_total) {
            return null;
        }

        return $this->final_kpi_total - $this->draft_kpi_total;
    }

    public function getKpiDeviationLabelAttribute(): string
    {
        if (is_null($this->kpi_deviation)) return '-';

        if ($this->kpi_deviation === 0) return 'Tidak berubah';

        if ($this->kpi_deviation > 0) {
            return '+' . $this->kpi_deviation . ' hari';
        }

        return $this->kpi_deviation . ' hari';
    }
    public function etdGaps(): array
    {
        return $this->analyze()['gaps'] ?? [];
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

    public function buildScheduleSnapshot(): array
    {
        $this->loadMissing(['items.shippingLine', 'items.vessel', 'customer']);

        return $this->items
            ->sortBy('planned_etd')
            ->values()
            ->map(function (VesselPlanItem $item) {
                return [
                    'item_id' => $item->id,
                    'shipping_line_id' => $item->shipping_line_id,
                    'shipping_line_name' => $item->shippingLine?->name,
                    'vessel_id' => $item->vessel_id,
                    'vessel_name' => $item->vessel?->name,
                    'planned_etd' => optional($item->planned_etd)?->toIso8601String(),
                    'planned_eta' => optional($item->planned_eta)?->toIso8601String(),
                    'planned_sailing_days' => $item->planned_sailing_days,
                    'note' => $item->note,
                ];
            })
            ->all();
    }

    public function buildSopSnapshot(?array $analysis = null): array
    {
        $analysis ??= $this->analyze();

        return [
            'sailing_avg' => $analysis['sailing_avg'] ?? 0,
            'max_gap' => $analysis['max_gap'] ?? 0,
            'gap_limit' => $analysis['gap_limit'] ?? 6,
            'gap_ok' => $analysis['gap_ok'] ?? false,
            'schedule_count' => $analysis['schedule_count'] ?? $this->items()->count(),
            'ok' => $analysis['ok'] ?? false,
        ];
    }

    public function latestSnapshotForStage(string $stage): ?VesselPlanSnapshot
    {
        return $this->snapshots()
            ->where('stage', $stage)
            ->latest('id')
            ->first();
    }

    public function draftSnapshot(): ?VesselPlanSnapshot
    {
        return $this->latestSnapshotForStage(self::SNAPSHOT_STAGE_DRAFT);
    }

    public function finalSnapshot(): ?VesselPlanSnapshot
    {
        return $this->latestSnapshotForStage(self::SNAPSHOT_STAGE_FINAL);
    }

    public function logReviewAction(
        string $action,
        ?int $userId = null,
        ?string $note = null,
        ?array $meta = null
    ): VesselPlanReview {
        return $this->reviews()->create([
            'action' => $action,
            'note' => $note,
            'acted_by' => $userId,
            'acted_at' => now(),
            'meta' => $meta,
        ]);
    }

    public function waUrl(): ?string
    {
        if (! $this->hasWhatsappRecipient()) {
            return null;
        }

        $message = app(WhatsappMessageBuilder::class)->buildFullMessage($this);
        $normalizedPhone = preg_replace('/\D+/', '', $this->whatsapp_phone);

        if (str_starts_with($normalizedPhone, '0')) {
            $normalizedPhone = '62' . substr($normalizedPhone, 1);
        }
        return 'https://wa.me/' . $normalizedPhone . '?text=' . rawurlencode($message);
    }

    public function sopStatus(): array
    {
        $analysis = $this->analyze();

        if (($analysis['schedule_count'] ?? 0) === 0) {
            return [
                'label' => 'BELUM ADA DATA',
                'color' => 'gray',
                'reason' => 'Belum ada jadwal kapal.',
            ];
        }

        if ($analysis['ok'] ?? false) {
            return [
                'label' => 'SESUAI SOP',
                'color' => 'success',
                'reason' => 'Jadwal dan ETD gap masih dalam batas SOP.',
            ];
        }

        return [
            'label' => 'PERLU REVISI',
            'color' => 'danger',
            'reason' => implode(' ', $analysis['violations'] ?? []),
        ];
    }

    public function hasWhatsappRecipient(): bool
    {
        return filled($this->customer_id) && filled($this->whatsapp_phone);
    }

    public function submitDraft(int $userId): void
    {
        app(VesselPlanSubmissionService::class)->submit($this, $userId);
    }

    public function canSubmitDraft(): bool
    {
        if (! $this->isDraft()) {
            return false;
        }

        if (! $this->hasWhatsappRecipient()) {
            return false;
        }

        $analysis = $this->analyze();

        return $analysis['ok'] ?? false;
    }

    public function maxEtdGap(): ?int
    {
        $gaps = $this->etdGaps();

        if (empty($gaps)) {
            return null;
        }

        return max($gaps);
    }

    public function finalizeSchedule(int $userId): int
    {
        return app(VesselPlanFinalizationService::class)->finalize($this, $userId);
    }

    public function reject(string $reason, int $userId): void
    {
        $this->update([
            'status'          => VesselPlanStatus::Revision,
            'feedback_reason' => $reason,
            'feedback_by'     => $userId,
            'feedback_at'     => now(),
        ]);

        $this->logReviewAction(
            self::REVIEW_ACTION_REVISION_REQUESTED,
            $userId,
            $reason,
            ['status' => VesselPlanStatus::Revision->value]
        );
    }
}
