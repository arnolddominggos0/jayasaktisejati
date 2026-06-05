<?php

namespace App\Filament\Pages;

use App\Enums\VesselCheckLogStatus;
use App\Enums\VesselCheckStatus;
use App\Models\ShippingSchedule;
use App\Models\VesselCheck;
use App\Models\VesselCheckCase;
use App\Models\Voyage;
use App\Models\VoyageMilestone;
use App\Services\Monitoring\ShippingAchievementService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MonitoringKapalTam extends Page
{
    protected static string $view = 'filament.pages.monitoring-kapal-tam';
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Operasional';
    protected static ?int $navigationSort = 1;

    public string $period;
    public string $search = '';

    public array $monthOptions = [];
    public $rows;
    public array $summary = [];
    public array $achievement = [];
    public array $calendar = [];

    public $selectedMilestone = null;
    public $showMilestoneModal = false;

    public $milestoneForm = [
        'code' => '',
        'milestone_date' => null,
        'actual_date' => null,
        'port_name' => '',
        'speed_knots' => null,
        'note' => '',
        'status' => '',
    ];

    public array $acknowledged = [];

    // ── Inline operational modal state ─────────────────────────────────
    public bool $showInlineModal = false;
    public string $inlineModalType = '';
    public ?int $inlineModalVoyageId = null;
    public ?int $inlineModalVesselCheckId = null;
    public ?int $inlineModalCaseId = null;
    public string $inlineModalVesselName = '';
    public string $inlineModalVoyageNo = '';
    public string $inlineModalPlanRef = '';

    public array $inlineForm = [
        'datetime' => '',
        'status' => '',
        'note' => '',
    ];

    public function mount(): void
    {
        $this->period = now()->format('Y-m');
        $this->acknowledged = session()->get('monitoring_acknowledged', []);
        $this->generateMonthOptions();
        $this->loadData();
    }

    /**
     * Runs after every subsequent request — restore computed data from DB
     * so it is never stored in wire:snapshot.
     */
    public function hydrate(): void
    {
        logger()->channel('single')->info('[TAM] hydrate()', [
            'period' => $this->period,
            'search' => $this->search,
        ]);
        $this->loadData();
    }

    /**
     * Runs before every snapshot serialization — strip computed data so
     * wire:snapshot stays small and its HMAC never fails on truncation.
     */
    public function dehydrate(): void
    {
        logger()->channel('single')->info('[TAM] dehydrate()');
        $this->rows        = null;
        $this->summary     = [];
        $this->achievement = [];
        $this->calendar    = [];
    }

    public function acknowledgeVoyage(int $voyageId): void
    {
        if (! in_array($voyageId, $this->acknowledged, true)) {
            $this->acknowledged[] = $voyageId;
            session()->put('monitoring_acknowledged', $this->acknowledged);
        }

        Notification::make()->title('Issue di-acknowledge')->success()->send();
    }

    public function updatedPeriod(): void
    {
        logger()->channel('single')->info('[TAM] updatedPeriod()', ['new_period' => $this->period]);
        $this->search = '';
        $this->loadData();
    }

    public function updatedSearch(): void
    {
        $this->loadData();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Inline operational action modals
    // ═══════════════════════════════════════════════════════════════════════

    public function openAtbModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'atb';
        $this->inlineModalVoyageId = $voyageId;
        $v = $this->resolveVoyage($voyageId);
        $this->inlineModalVesselName  = $v?->vessel?->name ?? '';
        $this->inlineModalVoyageNo    = $v?->voyage_no ?? '';
        $this->inlineModalPlanRef     = $v?->etb ? 'ETB ' . $v->etb->format('d M Y H:i') : '';
        $this->inlineForm['datetime'] = $v?->atb_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal        = true;
    }

    public function openAtdModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'atd';
        $this->inlineModalVoyageId = $voyageId;
        $v = $this->resolveVoyage($voyageId);

        logger()->channel('single')->info('[TAM] openAtdModal()', [
            'voyage_id'       => $voyageId,
            'voyage_no'       => $v?->voyage_no,
            'existing_atd_at' => $v?->atd_at?->toDateTimeString(),
        ]);

        $this->inlineModalVesselName  = $v?->vessel?->name ?? '';
        $this->inlineModalVoyageNo    = $v?->voyage_no ?? '';
        $this->inlineModalPlanRef     = $v?->etd ? 'ETD ' . $v->etd->format('d M Y H:i') : '';
        $this->inlineForm['datetime'] = $v?->atd_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal        = true;
    }

    public function openAtaModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'ata';
        $this->inlineModalVoyageId = $voyageId;
        $v = $this->resolveVoyage($voyageId);
        $this->inlineModalVesselName  = $v?->vessel?->name ?? '';
        $this->inlineModalVoyageNo    = $v?->voyage_no ?? '';
        $this->inlineModalPlanRef     = $v?->eta ? 'ETA ' . $v->eta->format('d M Y H:i') : '';
        $this->inlineForm['datetime'] = $v?->ata_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal        = true;
    }

    public function openClosingModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'closing';
        $this->inlineModalVoyageId = $voyageId;
        $v = $this->resolveVoyage($voyageId);
        $this->inlineModalVesselName  = $v?->vessel?->name ?? '';
        $this->inlineModalVoyageNo    = $v?->voyage_no ?? '';
        $this->inlineModalPlanRef     = '';
        $this->inlineForm['datetime'] = $v?->closing_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal        = true;
    }

    public function openVesselCheckModal(int $vesselCheckId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'vessel_check';
        $this->inlineModalVesselCheckId = $vesselCheckId;
        $vc = VesselCheck::find($vesselCheckId);
        // Use getRawOriginal to avoid ValueError if DB has unexpected enum string
        $this->inlineForm['status'] = $vc?->getRawOriginal('status') ?? VesselCheckLogStatus::ON_SCHEDULE->value;
        $this->inlineForm['note']   = $vc?->note ?? '';
        $this->showInlineModal      = true;
    }

    public function openDelayCaseModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'delay_case';
        $this->inlineModalVoyageId = $voyageId;
        $v = $this->resolveVoyage($voyageId);
        $this->inlineModalVesselName = $v?->vessel?->name ?? '';
        $this->inlineModalVoyageNo   = $v?->voyage_no ?? '';
        $this->showInlineModal       = true;
    }

    public function saveInlineModal(): void
    {
        logger()->channel('single')->info('[TAM] saveInlineModal()', [
            'modal_type' => $this->inlineModalType,
            'voyage_id'  => $this->inlineModalVoyageId,
            'datetime'   => $this->inlineForm['datetime'],
        ]);

        match ($this->inlineModalType) {
            'atb', 'atd', 'ata', 'closing' => $this->saveVoyageTimestamp(),
            'vessel_check'                  => $this->saveVesselCheck(),
            'delay_case'                    => $this->saveDelayCase(),
            default                         => null,
        };

        $this->closeInlineModal();
        $this->loadData();
    }

    protected function saveVoyageTimestamp(): void
    {
        $field = match ($this->inlineModalType) {
            'atb'     => 'atb_at',
            'atd'     => 'atd_at',
            'ata'     => 'ata_at',
            'closing' => 'closing_at',
            default   => null,
        };

        if (! $field || ! $this->inlineModalVoyageId) {
            return;
        }

        $this->validate([
            'inlineForm.datetime' => 'nullable|date',
            'inlineForm.note'     => 'nullable|string|max:500',
        ]);

        $voyage = Voyage::find($this->inlineModalVoyageId);

        logger()->channel('single')->info('[TAM] saveVoyageTimestamp()', [
            'field'     => $field,
            'voyage_no' => $voyage?->voyage_no,
            'new_value' => $this->inlineForm['datetime'],
        ]);

        if (! $voyage) {
            return;
        }

        $data = [$field => $this->inlineForm['datetime'] ?: null];

        if ($this->inlineForm['note']) {
            $prefix = now()->format('d M H:i') . ' [' . strtoupper($this->inlineModalType) . ']: ';
            $data['final_note'] = $voyage->final_note
                ? $voyage->final_note . "\n" . $prefix . $this->inlineForm['note']
                : $prefix . $this->inlineForm['note'];
        }

        $voyage->update($data);

        $label = match ($this->inlineModalType) {
            'atb'     => 'ATB',
            'atd'     => 'ATD',
            'ata'     => 'ATA',
            'closing' => 'Closing',
            default   => strtoupper($this->inlineModalType),
        };

        Notification::make()->title("Data {$label} berhasil disimpan")->success()->send();
    }

    protected function saveVesselCheck(): void
    {
        if (! $this->inlineModalVesselCheckId) {
            return;
        }

        $this->validate([
            'inlineForm.status' => 'nullable|in:' . implode(',', array_column(VesselCheckLogStatus::cases(), 'value')),
            'inlineForm.note'   => 'nullable|string|max:500',
        ]);

        $vc = VesselCheck::find($this->inlineModalVesselCheckId);
        if (! $vc) {
            return;
        }

        $data = ['note' => $this->inlineForm['note']];
        if ($this->inlineForm['status']) {
            $data['status'] = $this->inlineForm['status'];
        }

        $vc->update($data);

        Notification::make()->title('Readiness updated')->success()->send();
    }

    protected function saveDelayCase(): void
    {
        if (! $this->inlineModalVoyageId) {
            return;
        }

        $voyage = Voyage::find($this->inlineModalVoyageId);
        if (! $voyage) {
            return;
        }

        $ss = ShippingSchedule::where('voyage_id', $voyage->id)->first();
        if (! $ss) {
            Notification::make()->title('No shipping schedule found for this voyage')->warning()->send();
            return;
        }

        VesselCheckCase::create([
            'shipping_schedule_id' => $ss->id,
            'voyage_id'            => $voyage->id,
            'case_status'          => VesselCheckStatus::ETD_DELAY,
            'delay_flag'           => true,
            'opened_at'            => now(),
        ]);

        Notification::make()->title('Delay case created')->success()->send();
    }

    public function closeInlineModal(): void
    {
        $this->showInlineModal          = false;
        $this->inlineModalType          = '';
        $this->inlineModalVoyageId      = null;
        $this->inlineModalVesselCheckId = null;
        $this->inlineModalCaseId        = null;
        $this->inlineModalVesselName    = '';
        $this->inlineModalVoyageNo      = '';
        $this->inlineModalPlanRef       = '';
        $this->inlineForm               = ['datetime' => '', 'status' => '', 'note' => ''];
    }

    protected function resetInlineModal(): void
    {
        $this->inlineForm               = ['datetime' => '', 'status' => '', 'note' => ''];
        $this->inlineModalVoyageId      = null;
        $this->inlineModalVesselCheckId = null;
        $this->inlineModalCaseId        = null;
        $this->inlineModalVesselName    = '';
        $this->inlineModalVoyageNo      = '';
        $this->inlineModalPlanRef       = '';
    }

    // ═══════════════════════════════════════════════════════════════════════

    protected function resolveVoyage(int $voyageId): ?Voyage
    {
        return $this->rows?->firstWhere('id', $voyageId) ?? Voyage::find($voyageId);
    }

    protected function generateMonthOptions(): void
    {
        $start = now()->subMonths(12)->startOfMonth();
        $end   = now()->addMonths(12)->startOfMonth();

        while ($start <= $end) {
            $this->monthOptions[$start->format('Y-m')] = $start->translatedFormat('F Y');
            $start->addMonth();
        }
    }

    protected function baseQuery(Carbon $dt): Builder
    {
        return Voyage::query()
            ->with([
                'vessel',
                'pol',
                'pod',
                'milestones',
                'checkpoints',
                'vesselChecks',
                'vesselCheckCases',
                'sailingSla',
            ])
            ->whereYear('period_month', $dt->year)
            ->whereMonth('period_month', $dt->month)
            ->when(
                config('tam.route.force') && config('tam.route.pol_code') && config('tam.route.pod_code'),
                fn($q) => $q
                    ->whereHas('pol', fn($p) => $p->where('code', config('tam.route.pol_code')))
                    ->whereHas('pod', fn($p) => $p->where('code', config('tam.route.pod_code')))
            )
            ->when($this->search, function ($query) {
                $s = $this->search;
                $query->where(function ($q) use ($s) {
                    $q->where('voyage_no', 'like', "%{$s}%")
                        ->orWhereHas('vessel', fn($v) => $v->where('name', 'like', "%{$s}%"))
                        ->orWhereHas('pol', fn($p) => $p->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"))
                        ->orWhereHas('pod', fn($p) => $p->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"));
                });
            });
    }

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();

        logger()->channel('single')->info('[TAM] loadData()', [
            'period' => $this->period,
            'search' => $this->search,
        ]);

        $rows = $this->baseQuery($dt)
            ->get()
            ->sortByDesc(fn($v) => $v->operationalState->priorityWeight())
            ->values();

        logger()->channel('single')->info('[TAM] loadData() — rows', [
            'count' => $rows->count(),
            'ids'   => $rows->pluck('id')->all(),
        ]);

        $this->rows = $rows;
        $this->buildSummary();
        $this->buildAchievement();
        $this->buildCalendar($dt);

        logger()->channel('single')->info('[TAM] loadData() — calendar', [
            'days_count' => $this->calendar['days_count'] ?? 0,
        ]);
    }

    protected function buildSummary(): void
    {
        $delays = $this->rows
            ->pluck('departure_delay_days')
            ->filter(fn($d) => $d !== null && $d > 0);

        $milestoneOverdue = $this->rows
            ->flatMap(fn($v) => $v->milestones)
            ->where('is_overdue', true)
            ->count();

        $this->summary = [
            'total_voyage'        => $this->rows->count(),
            'voyage_delay'        => $delays->count(),
            'milestone_overdue'   => $milestoneOverdue,
            'total_delay_days'    => $delays->sum(),
            'average_delay_days'  => $delays->count() ? (int) round($delays->avg()) : 0,
            'max_delay_days'      => $delays->max() ?? 0,
        ];
    }

    protected function buildAchievement(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period);
        $this->achievement = app(ShippingAchievementService::class)
            ->summary((int) $dt->year, (int) $dt->month);
    }

    protected function buildCalendar(Carbon $dt): void
    {
        $start     = $dt->copy()->startOfMonth();
        $end       = $dt->copy()->endOfMonth();
        $daysCount = $dt->daysInMonth;

        $days = [];
        for ($i = 1; $i <= $daysCount; $i++) {
            $d      = $start->copy()->day($i);
            $days[] = [
                'n'         => $i,
                'dow'       => strtoupper($d->format('D')),
                'isWeekend' => $d->isWeekend(),
                'isToday'   => $d->isToday(),
            ];
        }

        $lanes  = ['etd' => 'ETD (Plan)', 'eta' => 'ETA (Plan)', 'atd' => 'ATD (Actual)', 'ata' => 'ATA (Actual)'];
        $bucket = [];
        foreach (array_keys($lanes) as $lane) {
            $bucket[$lane] = array_fill(1, $daysCount, []);
        }

        foreach ($this->rows as $voyage) {
            $state = $voyage->operationalState;

            // Only scalars — no enum objects in $this->calendar
            $chipBase = [
                'voyage_id'    => $voyage->id,
                'vessel'       => $voyage->vessel?->name ?? '-',
                'voyage_no'    => $voyage->voyage_no ?? "v{$voyage->id}",
                'status_color' => $state->status->color(),
                'status_label' => $state->status->label(),
            ];

            $planChip   = array_merge($chipBase, ['delay_label' => null, 'severity' => null]);
            $actualChip = array_merge($chipBase, ['delay_label' => $state->delayLabel(), 'severity' => $state->calendarSeverity()]);

            if ($voyage->etd?->between($start, $end)) {
                $bucket['etd'][$voyage->etd->day][] = $planChip;
            }
            if ($voyage->eta?->between($start, $end)) {
                $bucket['eta'][$voyage->eta->day][] = $planChip;
            }
            if ($voyage->atd_at?->between($start, $end)) {
                $bucket['atd'][$voyage->atd_at->day][] = $actualChip;
            }
            if ($voyage->ata_at?->between($start, $end)) {
                $bucket['ata'][$voyage->ata_at->day][] = $actualChip;
            }
        }

        $this->calendar = [
            'month_label' => $start->translatedFormat('F Y'),
            'days'        => $days,
            'days_count'  => $daysCount,
            'lanes'       => $lanes,
            'bucket'      => $bucket,
        ];
    }

    public function showMilestone($milestoneId): void
    {
        $this->selectedMilestone = VoyageMilestone::with(
            'voyage.vessel',
            'voyage.pol',
            'voyage.pod',
            'port'
        )->find($milestoneId);

        if (! $this->selectedMilestone) {
            return;
        }

        $this->milestoneForm = [
            'code'           => $this->selectedMilestone->code,
            'milestone_date' => $this->selectedMilestone->milestone_date?->format('Y-m-d'),
            'actual_date'    => $this->selectedMilestone->actual_date?->format('Y-m-d'),
            'port_name'      => $this->selectedMilestone->port?->name ?? '-',
            'speed_knots'    => $this->selectedMilestone->speed_knots,
            'note'           => $this->selectedMilestone->note,
            'status'         => $this->selectedMilestone->status,
        ];

        $this->showMilestoneModal = true;
    }

    public function saveMilestone(): void
    {
        if (! $this->selectedMilestone) {
            return;
        }

        $this->validate([
            'milestoneForm.actual_date' => 'required|date',
            'milestoneForm.speed_knots' => 'nullable|numeric|min:0|max:40',
            'milestoneForm.note'        => 'nullable|string|max:500',
        ]);

        $this->selectedMilestone->update([
            'actual_date' => $this->milestoneForm['actual_date'],
            'speed_knots' => $this->milestoneForm['speed_knots'],
            'note'        => $this->milestoneForm['note'],
        ]);

        Notification::make()->title('Milestone disimpan')->success()->send();

        $this->showMilestoneModal = false;
        $this->loadData();
    }
}
