<?php

namespace App\Models;

use App\Actions\CreateShippingSchedule;
use App\Actions\GenerateVesselChecks;
use App\Enums\VesselPlanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use DomainException;

class VesselPlan extends Model
{
    protected $fillable = [
        'period_month',
        'pol_id',
        'pod_id',
        'route_code',
        'status',
        'note',
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

    public function pol(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VesselPlanItem::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
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

    public function canSendToTam(): bool
    {
        if (! $this->isDraft()) {
            return false;
        }

        if (! $this->items()->exists()) {
            return false;
        }

        $analysis = $this->analyze();

        if (! ($analysis['ok'] ?? false)) {
            return false;
        }

        if (($analysis['max_gap'] ?? 0) > 6) {
            return false;
        }

        return true;
    }

    public function etdGaps(): array
    {
        $items = $this->items()->orderBy('planned_etd')->get();
        $gaps = [];

        foreach ($items as $i => $item) {
            $next = $items[$i + 1] ?? null;

            $gaps[$item->id] = $next
                ? $item->planned_etd->diffInDays($next->planned_etd)
                : null;
        }

        return $gaps;
    }

    public function maxEtdGap(): int
    {
        $gaps = $this->etdGaps();

        $filtered = array_filter($gaps, fn($gap) => !is_null($gap));

        return !empty($filtered) ? max($filtered) : 0;
    }

    public function analyze(): array
    {
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return [
                'ok' => false,
                'reason' => 'no_items',
                'max_gap' => 0,
            ];
        }

        $gaps = $this->etdGaps();
        $filteredGaps = array_filter($gaps, fn($gap) => !is_null($gap));
        $maxGap = !empty($filteredGaps) ? max($filteredGaps) : 0;

        $dwelling = config('kpi.manado.thresholds.dwelling_days', 6);
        $dooring  = config('kpi.manado.thresholds.dooring_days', 3);
        $limit    = config('kpi.manado.thresholds.total_days.normal', 19);

        $sailingDays = $items->map(function ($item) {
            if (!$item->planned_etd || !$item->planned_eta) {
                return null;
            }

            return $item->planned_etd->diffInDays($item->planned_eta);
        })->filter();

        $avgSailing = $sailingDays->avg() ?? 0;

        $total = $dwelling + $avgSailing + $dooring;

        return [
            'dwelling'    => $dwelling,
            'sailing_avg' => round($avgSailing, 2),
            'dooring'     => $dooring,
            'total'       => round($total, 2),
            'limit'       => $limit,
            'max_gap'     => $maxGap,
            'ok'          => $total <= $limit,
        ];
    }

    public function sopStatus(): array
    {
        if (! $this->items()->exists()) {
            return [
                'label' => 'BELUM ADA JADWAL',
                'color' => 'gray',
                'ok'    => false,
            ];
        }

        $analysis = $this->analyze();

        return $analysis['ok']
            ? [
                'label' => 'SESUAI SOP',
                'color' => 'success',
                'ok'    => true,
            ]
            : [
                'label' => 'MELEBIHI SOP',
                'color' => 'danger',
                'ok'    => false,
            ];
    }

    public function markAsSent(int $userId): void
    {
        if (! $this->canSendToTam()) {
            throw new DomainException('Jadwal belum memenuhi syarat untuk dikirim.');
        }

        $this->update([
            'status'  => VesselPlanStatus::Sent,
            'sent_at' => now(),
            'sent_by' => $userId,
        ]);
    }

    public function markAsRevision(string $reason, int $userId): void
    {
        if (! $this->isSent()) {
            throw new DomainException('Hanya yang sudah dikirim yang bisa direvisi.');
        }

        $this->update([
            'status'          => VesselPlanStatus::Revision,
            'feedback_reason' => $reason,
            'feedback_by'     => $userId,
            'feedback_at'     => now(),
        ]);
    }

    public function markAsFinal(int $userId): void
    {
        if (! $this->isSent()) {
            throw new DomainException('Hanya yang sudah dikirim yang bisa difinalisasi.');
        }

        DB::transaction(function () {

            $this->update([
                'status' => VesselPlanStatus::Final,
            ]);

            foreach ($this->items as $item) {

                $voyage = $item->voyage;

                if (!$voyage) {
                    continue;
                }

                $schedule = CreateShippingSchedule::run($voyage);

                GenerateVesselChecks::run($schedule);
            }
        });
    }

    public function approve(int $userId): void
    {
        $this->markAsFinal($userId);
    }

    public function reject(string $reason, int $userId): void
    {
        $this->markAsRevision($reason, $userId);
    }

    public function waMessage(): string
    {
        $analysis = $this->analyze();

        $lines = [
            $this->waGreeting() . ' Pak,',
            '',
            "Berikut draft jadwal kapal periode {$this->period_month->format('M Y')} rute {$this->route_code}:",
            '',
        ];

        foreach ($this->items()->with(['shippingLine', 'vessel'])->orderBy('planned_etd')->get() as $i => $item) {
            $lines[] = ($i + 1) . '. ' . ($item->shippingLine->name ?? '-');
            $lines[] = 'Kapal : ' . ($item->vessel->name ?? '-');
            $lines[] = 'ETD   : ' . $item->planned_etd->format('d M Y');
            $lines[] = 'ETA   : ' . $item->planned_eta->format('d M Y');
            $lines[] = '';
        }

        $lines[] = 'Analisa KPI:';
        $lines[] = '- Dwelling : ' . $analysis['dwelling'] . ' hari';
        $lines[] = '- Sailing  : ' . $analysis['sailing_avg'] . ' hari';
        $lines[] = '- Dooring  : ' . $analysis['dooring'] . ' hari';
        $lines[] = '- Total    : ' . $analysis['total'] . ' hari';
        $lines[] = '- Batas    : ' . $analysis['limit'] . ' hari';
        $lines[] = '- Status   : ' . ($analysis['ok'] ? 'SESUAI SOP' : 'MELEBIHI SOP');
        $lines[] = '';
        $lines[] = 'Kualitas Jadwal:';
        $lines[] = '- Max ETD Gap : ' . $analysis['max_gap'] . ' hari';
        $lines[] = '';
        $lines[] = 'Mohon konfirmasi / revisinya.';
        $lines[] = 'Terima kasih.';

        return implode("\n", $lines);
    }

    protected function waGreeting(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default    => 'Selamat malam',
        };
    }
}
