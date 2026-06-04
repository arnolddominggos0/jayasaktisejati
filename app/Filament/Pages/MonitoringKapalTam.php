<?php

namespace App\Filament\Pages;

use App\Enums\VesselCheckLogStatus;
use App\Enums\VesselCheckStatus;
use App\Enums\VoyageOperationalStatus;
use App\Models\ShippingSchedule;
use App\Models\VesselCheck;
use App\Models\VesselCheckCase;
use App\Models\Voyage;
use App\Models\VoyageMilestone;
use App\Services\Monitoring\ShippingAchievementService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

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
    public string $inlineModalType = ''; // atb, atd, ata, closing, vessel_check, delay_case
    public ?int $inlineModalVoyageId = null;
    public ?int $inlineModalVesselCheckId = null;
    public ?int $inlineModalCaseId = null;

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

    public function acknowledgeVoyage(int $voyageId): void
    {
        if (!in_array($voyageId, $this->acknowledged, true)) {
            $this->acknowledged[] = $voyageId;
            session()->put('monitoring_acknowledged', $this->acknowledged);
        }

        Notification::make()
            ->title('Issue di-acknowledge')
            ->success()
            ->send();
    }

    public function updatedPeriod(): void
    {
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
        $v = Voyage::find($voyageId);
        $this->inlineForm['datetime'] = $v?->atb_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal = true;
    }

    public function openAtdModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'atd';
        $this->inlineModalVoyageId = $voyageId;
        $v = Voyage::find($voyageId);
        $this->inlineForm['datetime'] = $v?->atd_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal = true;
    }

    public function openAtaModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'ata';
        $this->inlineModalVoyageId = $voyageId;
        $v = Voyage::find($voyageId);
        $this->inlineForm['datetime'] = $v?->ata_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal = true;
    }

    public function openClosingModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'closing';
        $this->inlineModalVoyageId = $voyageId;
        $v = Voyage::find($voyageId);
        $this->inlineForm['datetime'] = $v?->closing_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->showInlineModal = true;
    }

    public function openVesselCheckModal(int $vesselCheckId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'vessel_check';
        $this->inlineModalVesselCheckId = $vesselCheckId;
        $vc = VesselCheck::find($vesselCheckId);
        $this->inlineForm['status'] = $vc?->status?->value ?? VesselCheckLogStatus::ON_SCHEDULE->value;
        $this->inlineForm['note'] = $vc?->note ?? '';
        $this->showInlineModal = true;
    }

    public function openDelayCaseModal(int $voyageId): void
    {
        $this->resetInlineModal();
        $this->inlineModalType = 'delay_case';
        $this->inlineModalVoyageId = $voyageId;
        $this->showInlineModal = true;
    }

    public function saveInlineModal(): void
    {
        match ($this->inlineModalType) {
            'atb', 'atd', 'ata', 'closing' => $this->saveVoyageTimestamp(),
            'vessel_check' => $this->saveVesselCheck(),
            'delay_case' => $this->saveDelayCase(),
            default => null,
        };

        $this->closeInlineModal();
        $this->loadData();
    }

    protected function saveVoyageTimestamp(): void
    {
        $field = match ($this->inlineModalType) {
            'atb' => 'atb_at',
            'atd' => 'atd_at',
            'ata' => 'ata_at',
            'closing' => 'closing_at',
            default => null,
        };

        if (!$field || !$this->inlineModalVoyageId) {
            return;
        }

        $this->validate([
            'inlineForm.datetime' => 'nullable|date',
            'inlineForm.note' => 'nullable|string|max:500',
        ]);

        $voyage = Voyage::find($this->inlineModalVoyageId);
        if (!$voyage) {
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

        Notification::make()
            ->title(strtoupper($this->inlineModalType) . ' updated')
            ->success()
            ->send();
    }

    protected function saveVesselCheck(): void
    {
        if (!$this->inlineModalVesselCheckId) {
            return;
        }

        $this->validate([
            'inlineForm.status' => 'nullable|in:' . implode(',', array_column(VesselCheckLogStatus::cases(), 'value')),
            'inlineForm.note' => 'nullable|string|max:500',
        ]);

        $vc = VesselCheck::find($this->inlineModalVesselCheckId);
        if (!$vc) {
            return;
        }

        $data = ['note' => $this->inlineForm['note']];

        if ($this->inlineForm['status']) {
            $data['status'] = $this->inlineForm['status'];
        }

        $vc->update($data);

        Notification::make()
            ->title('Readiness updated')
            ->success()
            ->send();
    }

    protected function saveDelayCase(): void
    {
        if (!$this->inlineModalVoyageId) {
            return;
        }

        $voyage = Voyage::find($this->inlineModalVoyageId);
        if (!$voyage) {
            return;
        }

        $ss = ShippingSchedule::where('voyage_id', $voyage->id)->first();
        if (!$ss) {
            Notification::make()
                ->title('No shipping schedule found for this voyage')
                ->warning()
                ->send();
            return;
        }

        VesselCheckCase::create([
            'shipping_schedule_id' => $ss->id,
            'voyage_id' => $voyage->id,
            'case_status' => VesselCheckStatus::ETD_DELAY,
            'delay_flag' => true,
            'opened_at' => now(),
        ]);

        Notification::make()
            ->title('Delay case created')
            ->success()
            ->send();
    }

    public function closeInlineModal(): void
    {
        $this->showInlineModal = false;
        $this->inlineModalType = '';
        $this->inlineModalVoyageId = null;
        $this->inlineModalVesselCheckId = null;
        $this->inlineModalCaseId = null;
        $this->inlineForm = ['datetime' => '', 'status' => '', 'note' => ''];
    }

    protected function resetInlineModal(): void
    {
        $this->inlineForm = ['datetime' => '', 'status' => '', 'note' => ''];
        $this->inlineModalVoyageId = null;
        $this->inlineModalVesselCheckId = null;
        $this->inlineModalCaseId = null;
    }

    // ═══════════════════════════════════════════════════════════════════════

    protected function generateMonthOptions(): void
    {
        $start = now()->subMonths(12)->startOfMonth();
        $end   = now()->addMonths(12)->startOfMonth();

        while ($start <= $end) {
            $this->monthOptions[$start->format('Y-m')] =
                $start->translatedFormat('F Y');
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
            ])
            ->whereYear('period_month', $dt->year)
            ->whereMonth('period_month', $dt->month)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('voyage_no', 'like', "%{$this->search}%")
                        ->orWhereHas(
                            'vessel',
                            fn($v) => $v->where('name', 'like', "%{$this->search}%")
                        );
                });
            });
    }

    protected function loadData(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->period)->startOfMonth();

        $this->rows = $this->baseQuery($dt)
            ->get()
            ->sortByDesc(fn($v) => $v->operationalState->priorityWeight())
            ->values();

        $this->buildSummary();
        $this->buildAchievement();
        $this->buildCalendar($dt);
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
            'total_voyage' => $this->rows->count(),
            'voyage_delay' => $delays->count(),
            'milestone_overdue' => $milestoneOverdue,
            'total_delay_days' => $delays->sum(),
            'average_delay_days' => $delays->count()
                ? (int) round($delays->avg())
                : 0,
            'max_delay_days' => $delays->max() ?? 0,
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
        $start = $dt->copy()->startOfMonth();
        $end   = $dt->copy()->endOfMonth();
        $daysCount = $dt->daysInMonth;

        $days = [];
        for ($i = 1; $i <= $daysCount; $i++) {
            $d = $start->copy()->day($i);
            $days[] = [
                'n' => $i,
                'dow' => strtoupper($d->format('D')),
                'isWeekend' => $d->isWeekend(),
                'isToday' => $d->isToday(),
            ];
        }

        $lanes = [
            'etd' => 'ETD (Plan)',
            'eta' => 'ETA (Plan)',
            'atd' => 'ATD (Actual)',
            'ata' => 'ATA (Actual)',
        ];

        $bucket = [];
        foreach (array_keys($lanes) as $lane) {
            $bucket[$lane] = array_fill(1, $daysCount, []);
        }

        foreach ($this->rows as $voyage) {
            $state = $voyage->operationalState;

            $planChip = [
                'vessel' => $voyage->vessel?->name ?? '-',
                'voyage_no' => $voyage->voyage_no,
                'status' => $state->status,
                'delay_label' => null,
                'severity' => null,
            ];

            $actualChip = [
                'vessel' => $voyage->vessel?->name ?? '-',
                'voyage_no' => $voyage->voyage_no,
                'status' => $state->status,
                'delay_label' => $state->delayLabel(),
                'severity' => $state->calendarSeverity(),
            ];

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
            'days' => $days,
            'days_count' => $daysCount,
            'lanes' => $lanes,
            'bucket' => $bucket,
        ];
    }

    public function showMilestone($milestoneId)
    {
        $this->selectedMilestone = VoyageMilestone::with(
            'voyage.vessel',
            'voyage.pol',
            'voyage.pod',
            'port'
        )->find($milestoneId);

        if (!$this->selectedMilestone) {
            return;
        }

        $this->milestoneForm = [
            'code' => $this->selectedMilestone->code,
            'milestone_date' => $this->selectedMilestone->milestone_date?->format('Y-m-d'),
            'actual_date' => $this->selectedMilestone->actual_date?->format('Y-m-d'),
            'port_name' => $this->selectedMilestone->port?->name ?? '-',
            'speed_knots' => $this->selectedMilestone->speed_knots,
            'note' => $this->selectedMilestone->note,
            'status' => $this->selectedMilestone->status,
        ];

        $this->showMilestoneModal = true;
    }

    public function saveMilestone()
    {
        if (!$this->selectedMilestone) {
            return;
        }

        $this->validate([
            'milestoneForm.actual_date' => 'required|date',
            'milestoneForm.speed_knots' => 'nullable|numeric|min:0|max:40',
            'milestoneForm.note' => 'nullable|string|max:500',
        ]);

        $this->selectedMilestone->update([
            'actual_date' => $this->milestoneForm['actual_date'],
            'speed_knots' => $this->milestoneForm['speed_knots'],
            'note' => $this->milestoneForm['note'],
        ]);

        $this->showMilestoneModal = false;
        $this->loadData();
    }
    
}
