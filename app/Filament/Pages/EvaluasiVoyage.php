<?php

namespace App\Filament\Pages;

use App\Services\LeadTimeAnalysisService;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class EvaluasiVoyage extends Page
{
    protected static string $view = 'filament.pages.evaluasi-voyage';

    protected static ?string $navigationIcon  = 'heroicon-o-magnifying-glass-circle';
    protected static ?string $navigationGroup = 'Monitoring Pengiriman';
    protected static ?string $navigationLabel = 'Evaluasi Voyage';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $title           = 'Evaluasi Voyage';

    // ── Filters ──────────────────────────────────────────────────────────
    public string  $period      = 'this_month';
    public string  $periodMonth = '';
    public string  $voyageSearch = '';

    // ── View state: 'list' | 'voyage_detail' | 'unit_detail' ─────────────
    public string $currentView   = 'list';
    public ?int   $selectedVoyageId   = null;
    public ?int   $selectedShipmentId = null;

    // ── Voyage detail filters ─────────────────────────────────────────────
    public string  $unitSearch    = '';
    public string  $statusFilter  = '';  // '' | 'OK' | 'NG'
    public int     $unitPage      = 1;
    public int     $unitPerPage   = 25;

    // ── Computed data (populated on each render) ─────────────────────────
    public array  $voyageSummaries = [];
    public array  $voyageInfo      = [];
    public array  $voyageUnits     = [];
    public array  $unitDetail      = [];

    // ── Sort ─────────────────────────────────────────────────────────────
    public string $sortBy  = 'etd';
    public string $sortDir = 'desc';

    public function mount(): void
    {
        $this->periodMonth = now()->format('Y-m');
        $this->loadVoyageSummaries();
    }

    // ── Period helpers ────────────────────────────────────────────────────

    private function parsePeriodRange(): array
    {
        if ($this->period === 'by_month' && $this->periodMonth) {
            $base = Carbon::createFromFormat('Y-m', $this->periodMonth);
            return [$base->copy()->startOfMonth(), $base->copy()->endOfMonth()];
        }

        if ($this->period === 'this_year') {
            return [now()->startOfYear(), now()->endOfYear()];
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }

    public function getMonthOptions(): array
    {
        $opts = [];
        for ($i = 0; $i < 18; $i++) {
            $dt = now()->subMonths($i)->startOfMonth();
            $opts[$dt->format('Y-m')] = $dt->translatedFormat('F Y');
        }
        return $opts;
    }

    // ── Data loaders ──────────────────────────────────────────────────────

    public function loadVoyageSummaries(): void
    {
        [$start, $end] = $this->parsePeriodRange();

        /** @var LeadTimeAnalysisService $svc */
        $svc = app(LeadTimeAnalysisService::class);

        $rows = $svc->getVoyageSummaries($start, $end, $this->voyageSearch ?: null);

        // Sort
        $sorted = match ($this->sortBy) {
            'voyage'      => $rows->sortBy('voyage_label', SORT_STRING),
            'qty_unit'    => $rows->sortBy('qty_unit'),
            'avg_dwelling' => $rows->sortBy('avg_dwelling'),
            'avg_sailing' => $rows->sortBy('avg_sailing'),
            'avg_dooring' => $rows->sortBy('avg_dooring'),
            'avg_lt'      => $rows->sortBy('avg_lt'),
            'ok_count'    => $rows->sortBy('ok_count'),
            'ng_count'    => $rows->sortBy('ng_count'),
            default       => $rows->sortBy('etd'),
        };

        $this->voyageSummaries = ($this->sortDir === 'asc'
            ? $sorted
            : $sorted->sortByDesc(fn($r) => $r[$this->sortBy] ?? $r['etd'])
        )->values()->all();
    }

    public function loadVoyageDetail(): void
    {
        if (! $this->selectedVoyageId) {
            return;
        }

        /** @var LeadTimeAnalysisService $svc */
        $svc = app(LeadTimeAnalysisService::class);

        $this->voyageInfo  = $svc->getVoyageInfo($this->selectedVoyageId) ?? [];
        $this->voyageUnits = $svc->getVoyageUnits(
            $this->selectedVoyageId,
            $this->unitSearch ?: null,
            $this->statusFilter ?: null
        )->all();

        $this->unitPage = 1;
    }

    public function loadUnitDetail(): void
    {
        if (! $this->selectedShipmentId) {
            return;
        }

        /** @var LeadTimeAnalysisService $svc */
        $svc = app(LeadTimeAnalysisService::class);

        $detail = $svc->getShipmentDetail($this->selectedShipmentId);
        $this->unitDetail = $detail ? $this->serializeDetail($detail) : [];
    }

    // ── Actions ───────────────────────────────────────────────────────────

    public function applyFilters(): void
    {
        $this->currentView = 'list';
        $this->selectedVoyageId = null;
        $this->selectedShipmentId = null;
        $this->loadVoyageSummaries();
    }

    public function openVoyage(int $voyageId): void
    {
        $this->selectedVoyageId = $voyageId;
        $this->unitSearch   = '';
        $this->statusFilter = '';
        $this->currentView  = 'voyage_detail';
        $this->loadVoyageDetail();
    }

    public function openUnit(int $shipmentId): void
    {
        $this->selectedShipmentId = $shipmentId;
        $this->currentView = 'unit_detail';
        $this->loadUnitDetail();
    }

    public function backToList(): void
    {
        $this->currentView = 'list';
        $this->selectedVoyageId = null;
        $this->selectedShipmentId = null;
    }

    public function backToVoyage(): void
    {
        $this->currentView = 'voyage_detail';
        $this->selectedShipmentId = null;
        $this->loadVoyageDetail();
    }

    public function applyUnitFilters(): void
    {
        $this->unitPage = 1;
        $this->loadVoyageDetail();
    }

    public function setSort(string $col): void
    {
        if ($this->sortBy === $col) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $col;
            $this->sortDir = 'desc';
        }
        $this->loadVoyageSummaries();
    }

    public function prevPage(): void
    {
        if ($this->unitPage > 1) {
            $this->unitPage--;
        }
    }

    public function nextPage(): void
    {
        $total = count($this->voyageUnits);
        if ($this->unitPage * $this->unitPerPage < $total) {
            $this->unitPage++;
        }
    }

    // ── Export ────────────────────────────────────────────────────────────

    public function exportVoyageList()
    {
        [$start, $end] = $this->parsePeriodRange();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EvaluasiVoyageExport($start, $end, $this->voyageSearch ?: null),
            'evaluasi-voyage-' . $start->format('Y-m') . '.xlsx'
        );
    }

    public function exportVoyageDetail()
    {
        if (! $this->selectedVoyageId) {
            return;
        }

        $info  = app(LeadTimeAnalysisService::class)->getVoyageInfo($this->selectedVoyageId);
        $label = data_get($info, 'label', 'voyage');
        $safe  = preg_replace('/[^A-Za-z0-9\-_]/', '-', $label);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EvaluasiVoyageDetailExport($this->selectedVoyageId),
            'detail-voyage-' . $safe . '.xlsx'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function serializeDetail(array $detail): array
    {
        $shipment = $detail['shipment'];
        $voyage   = $detail['voyage'];
        $s        = $detail['summary'];
        $t        = $detail['thresholds'];

        return [
            'shipment_code' => $shipment->code ?? '-',
            'customer'      => $shipment->customer?->name ?? '-',
            'voyage_label'  => trim(($voyage?->vessel?->name ?? '-') . ' ' . ($voyage?->voyage_no ?? '')),
            'period'        => $voyage?->period_month
                ? Carbon::parse($voyage->period_month)->translatedFormat('F Y')
                : null,
            'mode'          => $shipment->mode?->label() ?? $shipment->mode ?? '-',
            'applies'       => $detail['applies'],
            'badge'         => $detail['badge'],
            'dwelling'      => $s['dwelling']['actual'] ?? null,
            'dwelling_limit' => $t['dwelling_days'] ?? null,
            'dwelling_st'   => $s['dwelling']['status'] ?? 'PENDING',
            'sailing'       => $s['sailing']['actual'] ?? null,
            'sailing_limit' => $t['sailing_days'] ?? null,
            'sailing_st'    => $s['sailing']['status'] ?? 'PENDING',
            'dooring'       => $s['dooring']['actual'] ?? null,
            'dooring_limit' => $t['dooring_days'] ?? null,
            'dooring_st'    => $s['dooring']['status'] ?? 'PENDING',
            'lt_total'      => $s['total']['actual'] ?? null,
            'lt_limit'      => $s['total']['limit'] ?? null,
            'lt_status'     => $s['total']['status'] ?? 'PENDING',
            'units' => ($shipment->getRelation('units') ?? collect())
                ->map(fn($u) => [
                    'chassis_no' => $u->chassis_no ?? '-',
                    'engine_no'  => $u->engine_no ?? '-',
                    'model'      => $u->model_no ?? '-',
                    'color'      => $u->color ?? '-',
                ])
                ->all(),
            'timeline'      => $detail['timeline'],
            'milestones'    => $detail['milestones'],
        ];
    }

    public function getPaginatedUnits(): array
    {
        $offset = ($this->unitPage - 1) * $this->unitPerPage;
        return array_slice($this->voyageUnits, $offset, $this->unitPerPage);
    }

    public function getTotalUnitPages(): int
    {
        return (int) ceil(count($this->voyageUnits) / $this->unitPerPage);
    }
}
