<?php

namespace App\Filament\Pages;

use App\Enums\ShipmentStatus;
use App\Enums\VesselCheckDelayReason;
use App\Enums\VesselCheckLogStatus;
use App\Enums\VoyageDelayReason;
use App\Enums\VoyageOperationalStatus;
use App\Models\VesselCheck;
use App\Models\Voyage;
use App\Models\VoyageMilestone;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Monitoring Kapal TAM — Cargo Monitoring Scope
 *
 * Menjawab pertanyaan: "Apakah cargo TAM berjalan sesuai target?"
 *
 * Source of truth: Voyage::whereHas('shipments')
 * Hanya voyage yang memiliki shipment aktif yang masuk ke:
 *   - Monitoring dashboard
 *   - Summary / Achievement / KPI
 *   - Delay Analysis
 *   - Calendar
 *
 * PERBEDAAN DENGAN VESSEL CHECK:
 *   Vessel Check menjawab: "Apakah carrier siap berangkat sesuai jadwal?"
 *   Source: Voyage::whereBetween('etd', H-2/H-1)  — TANPA filter shipment
 *   Scope: semua voyage yang akan berangkat
 *
 *   JANGAN mengubah baseQuery() agar menggunakan seluruh voyage.
 *   Kedua modul memiliki scope berbeda secara disengaja.
 */
class MonitoringKapalTam extends Page
{
    protected static string $view = 'filament.pages.monitoring-kapal-tam';
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Manajemen Kapal';
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

    // ── Operational Action Modal ────────────────────────────────────────
    public bool   $showActionModal    = false;
    public string $actionModalType    = ''; // atb | atd | ata | closing | delay | readiness
    public ?int   $actionVoyageId     = null;
    public array  $actionForm         = [
        'datetime'               => '',
        'note'                   => '',
        'delay_reason'           => '',
        'delay_note'             => '',
        'readiness'              => '',
        'readiness_note'         => '',
        'readiness_delay_reason' => '',
        'cargo'                  => '',
        'cargo_note'             => '',
    ];

    // ── Voyage Drawer ───────────────────────────────────────────────────
    public bool  $showDrawer         = false;
    public ?int  $drawerVoyageId     = null;

    // ── Evaluation ──────────────────────────────────────────────────────
    public array $evaluation = [];

    // ── Carrier Readiness (scope: all voyages H-2/H-1, no shipment filter) ──
    public array $carrierReadiness = [];

    public function mount(): void
    {
        $this->period = now()->format('Y-m');
        $this->acknowledged = session()->get('monitoring_acknowledged', []);
        $this->generateMonthOptions();
        $this->loadData();
        $this->loadCarrierReadiness();
    }

    // ═══════════════════════════════════════════════════════════════════
    // Operational Action Modal
    // ═══════════════════════════════════════════════════════════════════

    public function openOpModal(int $voyageId, string $type): void
    {
        $v = $this->resolveVoyage($voyageId);

        $this->actionVoyageId  = $voyageId;
        $this->actionModalType = $type;
        $this->actionForm      = [
            'datetime'       => '',
            'note'           => '',
            'delay_reason'   => '',
            'delay_note'     => '',
            'readiness'              => VesselCheckLogStatus::OK->value,
            'readiness_note'         => '',
            'readiness_delay_reason' => '',
            'cargo'          => '',
            'cargo_note'     => '',
        ];

        match ($type) {
            'atb'      => $this->actionForm['datetime'] = $v?->atb_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'),
            'atd'      => $this->actionForm['datetime'] = $v?->atd_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'),
            'ata'      => $this->actionForm['datetime'] = $v?->ata_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'),
            'closing'  => $this->actionForm['datetime'] = $v?->closing_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'),
            'readiness'=> $this->actionForm['readiness'] = $v?->vesselChecks
                ->whereIn('day_code', ['H-1', 'H-2'])
                ->sortBy(fn($vc) => $vc->day_code === 'H-1' ? 1 : 2)
                ->first()?->status?->value ?? VesselCheckLogStatus::OK->value,
            'cargo'    => $this->actionForm['cargo'] = $v?->cargo_actual !== null ? (string) $v->cargo_actual : '',
            default    => null,
        };

        $this->showActionModal = true;
    }

    public function closeOpModal(): void
    {
        $this->showActionModal = false;
        $this->actionModalType = '';
        $this->actionVoyageId  = null;
    }

    public function saveOpModal(): void
    {
        match ($this->actionModalType) {
            'atb', 'atd', 'ata', 'closing' => $this->saveTimestamp(),
            'delay'     => $this->saveDelay(),
            'readiness' => $this->saveReadiness(),
            'cargo'     => $this->saveCargoActual(),
            default     => null,
        };

        $this->closeOpModal();
        $this->loadData();
        $this->loadCarrierReadiness();
    }

    protected function saveTimestamp(): void
    {
        $field = match ($this->actionModalType) {
            'atb'     => 'atb_at',
            'atd'     => 'atd_at',
            'ata'     => 'ata_at',
            'closing' => 'closing_at',
            default   => null,
        };

        if (!$field || !$this->actionVoyageId) return;

        $this->validate([
            'actionForm.datetime' => 'required|date',
            'actionForm.note'     => 'nullable|string|max:500',
        ]);

        $voyage = Voyage::find($this->actionVoyageId);
        if (!$voyage) return;

        $data = [$field => $this->actionForm['datetime'] ?: null];

        if ($this->actionForm['note']) {
            $prefix = now()->format('d M H:i') . ' [' . strtoupper($this->actionModalType) . ']: ';
            $data['final_note'] = $voyage->final_note
                ? $voyage->final_note . "\n" . $prefix . $this->actionForm['note']
                : $prefix . $this->actionForm['note'];
        }

        $voyage->update($data);

        $label = strtoupper($this->actionModalType);
        Notification::make()->title("Data {$label} berhasil disimpan")->success()->send();
    }

    protected function saveDelay(): void
    {
        if (!$this->actionVoyageId) return;

        $this->validate([
            'actionForm.delay_reason' => 'required|string',
            'actionForm.delay_note'   => 'nullable|string|max:500',
        ]);

        $voyage = Voyage::find($this->actionVoyageId);
        if (!$voyage) return;

        $data = ['manual_delay_reason' => $this->actionForm['delay_reason']];

        if ($this->actionForm['delay_note']) {
            $prefix = now()->format('d M H:i') . ' [DELAY]: ';
            $data['final_note'] = $voyage->final_note
                ? $voyage->final_note . "\n" . $prefix . $this->actionForm['delay_note']
                : $prefix . $this->actionForm['delay_note'];
        }

        $voyage->update($data);

        Notification::make()->title('Penyebab delay disimpan')->success()->send();
    }

    protected function saveCargoActual(): void
    {
        if (!$this->actionVoyageId) return;

        $this->validate([
            'actionForm.cargo'      => 'nullable|numeric|min:0',
            'actionForm.cargo_note' => 'nullable|string|max:500',
        ]);

        $voyage = Voyage::find($this->actionVoyageId);
        if (!$voyage) return;

        $data = [
            'cargo_actual' => $this->actionForm['cargo'] !== '' ? (int) $this->actionForm['cargo'] : null,
        ];

        if ($this->actionForm['cargo_note']) {
            $prefix = now()->format('d M H:i') . ' [CARGO]: ';
            $data['final_note'] = $voyage->final_note
                ? $voyage->final_note . "\n" . $prefix . $this->actionForm['cargo_note']
                : $prefix . $this->actionForm['cargo_note'];
        }

        $voyage->update($data);

        Notification::make()->title('Cargo actual berhasil disimpan')->success()->send();
    }

    protected function saveReadiness(): void
    {
        if (! $this->actionVoyageId) return;

        $this->validate([
            'actionForm.readiness'              => 'required|string',
            'actionForm.readiness_note'         => 'nullable|string|max:500',
            'actionForm.readiness_delay_reason' => 'nullable|string|max:255',
        ]);

        $voyage = Voyage::find($this->actionVoyageId);
        if (! $voyage || ! $voyage->etd) return;

        // Determine which checklist day is active today.
        // Only the current operational day may be updated — operator cannot modify another day's checklist.
        $daysToEtd = (int) now()->startOfDay()->diffInDays(
            $voyage->etd->copy()->startOfDay(),
            false
        );

        if (! in_array($daysToEtd, [1, 2], true)) {
            Notification::make()
                ->title('Di luar window Vessel Check')
                ->body('Readiness hanya dapat diisi pada H-2 atau H-1 sebelum ETD.')
                ->danger()
                ->send();
            return;
        }

        $dayCode = match ($daysToEtd) {
            2 => 'H-2',
            1 => 'H-1',
        };

        // GenerateDailyVesselChecks is the sole creator — only update, never create.
        $check = VesselCheck::where('voyage_id', $voyage->id)
            ->where('day_code', $dayCode)
            ->first();

        if (! $check) {
            Notification::make()
                ->title("Checklist {$dayCode} belum tersedia")
                ->body('Sistem akan membuatnya secara otomatis pada H-2 sebelum keberangkatan.')
                ->danger()
                ->send();
            return;
        }

        $check->update([
            'status'       => $this->actionForm['readiness'],
            'delay_reason' => $this->actionForm['readiness'] === 'late'
                ? ($this->actionForm['readiness_delay_reason'] ?: null)
                : null,
            'note'         => $this->actionForm['readiness_note'] ?: null,
        ]);

        Notification::make()
            ->title("{$dayCode} — Readiness diperbarui")
            ->success()
            ->send();
    }

    // ═══════════════════════════════════════════════════════════════════
    // Voyage Drawer
    // ═══════════════════════════════════════════════════════════════════

    public function openDrawer(int $voyageId): void
    {
        $this->drawerVoyageId = $voyageId;
        $this->showDrawer     = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer     = false;
        $this->drawerVoyageId = null;
    }

    // ───────────────────────────────────────────────────────────────────

    protected function resolveVoyage(int $voyageId): ?Voyage
    {
        return $this->rows?->firstWhere('id', $voyageId) ?? Voyage::find($voyageId);
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
            ->with(['vessel', 'pol', 'pod', 'milestones', 'checkpoints', 'vesselChecks'])
            ->whereYear('period_month', $dt->year)
            ->whereMonth('period_month', $dt->month)
            ->whereHas('shipments', fn($q) => $q->where('status', '!=', ShipmentStatus::Cancelled->value))
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
            ->sortByDesc(fn($v) => $v->milestones->where('is_overdue', true)->count())
            ->values();

        $this->buildSummary();
        $this->buildAchievement();
        $this->buildCalendar($dt);
        $this->buildEvaluation();
    }

    // ── Carrier Readiness — Carrier Readiness Scope ──────────────────────
    // Query: ALL voyages H-2/H-1, regardless of shipments.
    // Scope is intentionally different from baseQuery (cargo monitoring scope).
    // See class PHPDoc for boundary explanation.
    protected function loadCarrierReadiness(): void
    {
        $voyages = Voyage::query()
            ->whereNull('atd_at')
            ->whereBetween('etd', [
                now()->addDay()->startOfDay(),
                now()->addDays(2)->endOfDay(),
            ])
            ->with(['vessel', 'vesselChecks'])
            ->orderBy('etd')
            ->get();

        $this->carrierReadiness = $voyages->map(function (Voyage $voyage) {
            $diff = (int) now()->startOfDay()->diffInDays(
                Carbon::parse($voyage->etd)->startOfDay(),
                false
            );

            $latestCheck = $voyage->vesselChecks
                ->sortByDesc('check_date')
                ->first();

            return [
                'voyage_id'    => $voyage->id,
                'vessel_name'  => $voyage->vessel?->name ?? '-',
                'voyage_no'    => $voyage->voyage_no ?? '-',
                'etd'          => $voyage->etd?->format('d M Y H:i'),
                'day_code'     => 'H-' . $diff,
                'status'       => $latestCheck?->getRawOriginal('status') ?? 'pending',
                'delay_reason' => $latestCheck?->delay_reason,
                'note'         => $latestCheck?->note,
            ];
        })->values()->toArray();
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
        $total = $this->rows->count();

        $calc = function ($collection) use ($total) {
            $ok = $collection->filter(
                fn($v) => $v !== null && $v->value === 'ontime'
            )->count();

            $ng = $collection->filter(
                fn($v) => $v !== null && $v->value === 'late'
            )->count();

            return [
                'total' => $total,
                'ok' => $ok,
                'ng' => $ng,
                'ok_percent' => $total > 0
                    ? round(($ok / $total) * 100)
                    : 0,
                'ng_percent' => $total > 0
                    ? round(($ng / $total) * 100)
                    : 0,
            ];
        };

        $reasons = $this->rows
            ->whereNotNull('delay_reason')
            ->groupBy('delay_reason')
            ->map->count()
            ->sortDesc();

        $topReason = $reasons->keys()->first();

        $avgDelay = $this->rows
            ->pluck('departure_delay_days')
            ->filter(fn($v) => $v > 0)
            ->avg();

        $this->achievement = [
            'otd' => $calc($this->rows->pluck('otd_status')),
            'ota' => $calc($this->rows->pluck('ota_status')),
            'otb' => $calc($this->rows->pluck('otb_status')),
            'penyebab_terbanyak' => $topReason
                ? VoyageDelayReason::from($topReason)->label()
                : null,
            'rata_rata_delay_berangkat' => $avgDelay
                ? (int) round($avgDelay)
                : 0,
        ];
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
            $delayLabel = $voyage->delay_label;

            $planChip = [
                'vessel' => $voyage->vessel?->name ?? '-',
                'voyage_no' => $voyage->voyage_no,
                'status' => $voyage->operational_status_enum,
                'delay_label' => null,
                'severity' => null,
            ];

            $actualChip = [
                'vessel' => $voyage->vessel?->name ?? '-',
                'voyage_no' => $voyage->voyage_no,
                'status' => $voyage->operational_status_enum,
                'delay_label' => $delayLabel,
                'severity' => $voyage->departure_delay_severity,
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

    // ═══════════════════════════════════════════════════════════════════
    // Operational Evaluation
    // ═══════════════════════════════════════════════════════════════════

    protected function buildEvaluation(): void
    {
        $delayed = $this->rows->filter(
            fn($v) => $v->operational_status_enum === VoyageOperationalStatus::DELAYED
                   || ($v->departure_delay_days > 0)
        );

        $delayDays = $this->rows
            ->pluck('departure_delay_days')
            ->filter(fn($d) => $d !== null && $d > 0);

        // Root cause breakdown
        $reasonGroups = $this->rows
            ->whereNotNull('manual_delay_reason')
            ->groupBy('manual_delay_reason')
            ->map(fn($g) => $g->count())
            ->sortDesc();

        $totalWithReason = $reasonGroups->sum();

        $reasons = $reasonGroups->map(function ($count) use ($totalWithReason) {
            return [
                'count'   => $count,
                'percent' => $totalWithReason > 0 ? round(($count / $totalWithReason) * 100) : 0,
            ];
        });

        // Top 5 delay voyages
        $top5 = $this->rows
            ->filter(fn($v) => $v->departure_delay_days > 0)
            ->sortByDesc('departure_delay_days')
            ->take(5)
            ->values();

        $this->evaluation = [
            'total_delay'   => $delayed->count(),
            'total_days'    => (int) $delayDays->sum(),
            'avg_days'      => $delayDays->count() ? (int) round($delayDays->avg()) : 0,
            'max_days'      => (int) ($delayDays->max() ?? 0),
            'reasons'       => $reasons,
            'top5'          => $top5,
        ];
    }
}
