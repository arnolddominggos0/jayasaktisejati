<?php

namespace App\Filament\FC\Resources\ShipmentResource\RelationManagers;

use App\Enums\TrackStatus;
use App\Filament\FC\Resources\ShipmentResource;
use App\Models\Unit;
use App\Models\UnitInspection;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ShipmentUnitsRelationManager extends RelationManager
{
    protected static string $relationship          = 'units';
    protected static ?string $title               = 'Unit & Inspeksi';
    protected static ?string $recordTitleAttribute = 'chassis_no';

    // ── Form (tidak digunakan — canCreate() = false) ──────────────────────────

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('chassis_no')
            ->heading('Daftar Unit & Status Inspeksi')
            ->description(fn () => $this->buildStatsDescription())
            ->modifyQueryUsing(fn (Builder $query) => $query->with('inspections'))
            ->columns([

                // ── Identitas unit ────────────────────────────────────────────

                TextColumn::make('chassis_no')
                    ->label('Chassis No')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('model_no')
                    ->label('Model')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('reg_no')
                    ->label('No. Polisi')
                    ->default('—'),

                // ── Tahap Aktif (dari TrackStatus shipment) ───────────────────
                // Nilai sama untuk semua unit dalam satu shipment

                TextColumn::make('tahap_aktif')
                    ->label('Tahap Aktif')
                    ->getStateUsing(fn (Unit $record) =>
                        $this->getActiveStageLabel() ?? '—'
                    )
                    ->badge()
                    ->color(fn (string $state) => $state === '—' ? 'gray' : 'info'),

                // ── Status tahap aktif per unit ───────────────────────────────
                // ✓ = sudah ada inspection untuk stage ini
                // ⏱ = belum ada inspection untuk stage ini
                // — = tidak ada tahap aktif (on ship, delivered, dll)

                IconColumn::make('status_tahap_aktif')
                    ->label('Status')
                    ->getStateUsing(function (Unit $record): string {
                        $stage = $this->getActiveInspectionStage();

                        if (! $stage) {
                            return 'no_stage';
                        }

                        $inspection = $record->inspections->firstWhere('stage', $stage);

                        if (! $inspection) {
                            return 'pending';
                        }

                        return $inspection->submitted_at !== null ? 'done' : 'pending';
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'done'    => 'heroicon-m-check-circle',
                        'pending' => 'heroicon-o-clock',
                        default   => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'done'    => 'success',
                        'pending' => 'warning',
                        default   => 'gray',
                    }),

                // ── Riwayat 6 stage (full progression) ───────────────────────

                IconColumn::make('stage_pickup')
                    ->label('Pickup')
                    ->getStateUsing(fn (Unit $record) =>
                        $record->inspections->firstWhere('stage', 'pickup')?->submitted_at !== null
                    )
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-o-ellipsis-horizontal-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('stage_handover')
                    ->label('H.Depo')
                    ->getStateUsing(fn (Unit $record) =>
                        $record->inspections->firstWhere('stage', 'handover_depot')?->submitted_at !== null
                    )
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-o-ellipsis-horizontal-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('stage_loading')
                    ->label('Load')
                    ->getStateUsing(fn (Unit $record) =>
                        $record->inspections->firstWhere('stage', 'loading')?->submitted_at !== null
                    )
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-o-ellipsis-horizontal-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('stage_unloading')
                    ->label('Unload')
                    ->getStateUsing(fn (Unit $record) =>
                        $record->inspections->firstWhere('stage', 'unloading')?->submitted_at !== null
                    )
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-o-ellipsis-horizontal-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('stage_selfdrive')
                    ->label('Drive')
                    ->getStateUsing(fn (Unit $record) =>
                        $record->inspections->firstWhere('stage', 'selfdrive')?->submitted_at !== null
                    )
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-o-ellipsis-horizontal-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                IconColumn::make('stage_dooring')
                    ->label('Dooring')
                    ->getStateUsing(fn (Unit $record) =>
                        $record->inspections->firstWhere('stage', 'dooring')?->submitted_at !== null
                    )
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-o-ellipsis-horizontal-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])

            // ── Action kontekstual ────────────────────────────────────────────

            ->actions([
                Tables\Actions\Action::make('inspect_or_view')
                    ->label(fn (Unit $record) => $this->buildActionLabel($record))
                    ->icon(fn (Unit $record) => $this->buildActionIcon($record))
                    ->color(fn (Unit $record) => $this->buildActionColor($record))
                    ->size('sm')
                    ->visible(fn (Unit $record) => $this->getActiveInspectionStage() !== null)
                    ->disabled(fn (Unit $record) => $this->resolveInspectionForActive($record) === null)
                    ->url(fn (Unit $record) => ShipmentResource::getUrl(
                        'inspect-unit',
                        ['record' => $this->ownerRecord->getKey(), 'unit' => $record->getKey()]
                    )),
            ])
            ->paginated(false)
            ->striped();
    }

    // ── TrackStatus → Inspection stage mapping ────────────────────────────────

    /**
     * Maps the Shipment's current TrackStatus to the active inspection stage key.
     * Returns null if no inspection is expected at the current status
     * (e.g. OnShip, VesselDepart, Delivered, Hold, Cancelled).
     */
    private function getActiveInspectionStage(): ?string
    {
        $status = $this->ownerRecord?->currentTrackStatus();

        if (! $status instanceof TrackStatus) {
            return null;
        }

        return match ($status) {
            TrackStatus::Pickup                              => 'pickup',
            TrackStatus::Handover                            => 'handover_depot',
            TrackStatus::Stuffing,
            TrackStatus::DeliveryToPort,
            TrackStatus::Stacking,
            TrackStatus::UnitLoading                         => 'loading',
            TrackStatus::Unloading                           => 'unloading',
            TrackStatus::HandoverTrucking                    => 'selfdrive',
            TrackStatus::DeliveryToCustomer                  => 'dooring',
            default                                          => null,
        };
    }

    private function getActiveStageLabel(): ?string
    {
        $stage = $this->getActiveInspectionStage();

        return $stage ? (UnitInspection::STAGE_LABELS[$stage] ?? $stage) : null;
    }

    // ── Action helpers ────────────────────────────────────────────────────────

    private function resolveInspectionForActive(Unit $record): ?UnitInspection
    {
        $stage = $this->getActiveInspectionStage();
        if (! $stage) {
            return null;
        }

        return $record->inspections->firstWhere('stage', $stage);
    }

    private function buildActionLabel(Unit $record): string
    {
        $stage = $this->getActiveInspectionStage();

        if (! $stage) {
            return 'Tidak Ada Tahap Aktif';
        }

        $label      = UnitInspection::STAGE_LABELS[$stage] ?? $stage;
        $inspection = $record->inspections->firstWhere('stage', $stage);

        if (! $inspection) {
            return 'Mulai Inspeksi — ' . $label;
        }

        return $inspection->submitted_at
            ? 'Lihat Inspeksi — ' . $label
            : 'Mulai Inspeksi — ' . $label;
    }

    private function buildActionIcon(Unit $record): string
    {
        $stage = $this->getActiveInspectionStage();

        if (! $stage) {
            return 'heroicon-o-minus-circle';
        }

        $inspection = $record->inspections->firstWhere('stage', $stage);

        if (! $inspection) {
            return 'heroicon-m-clipboard-document-check';
        }

        return $inspection->submitted_at
            ? 'heroicon-m-eye'
            : 'heroicon-m-clipboard-document-check';
    }

    private function buildActionColor(Unit $record): string
    {
        $stage = $this->getActiveInspectionStage();

        if (! $stage) {
            return 'gray';
        }

        $inspection = $record->inspections->firstWhere('stage', $stage);

        if (! $inspection) {
            return 'warning';
        }

        return $inspection->submitted_at ? 'gray' : 'warning';
    }

    // ── Stats description ─────────────────────────────────────────────────────

    private function buildStatsDescription(): HtmlString
    {
        if (! $this->ownerRecord) {
            return new HtmlString('');
        }

        $units = $this->ownerRecord->units()
            ->withCount([
                'inspections',
                'inspections as return_to_pdc_count' => fn (Builder $query) =>
                    $query->where('gate_decision', UnitInspection::GATE_RETURN_TO_PDC),
            ])
            ->get();

        $total    = $units->count();
        $sudah    = $units->filter(fn ($u) => $u->inspections_count > 0)->count();
        $belum    = $total - $sudah;
        $rtnPdc   = $units->filter(fn ($u) => $u->return_to_pdc_count > 0)->count();
        $stage    = $this->getActiveStageLabel();
        $stageHtml = $stage
            ? '<span class="text-blue-700 dark:text-blue-400">Tahap Aktif: <strong>' . e($stage) . '</strong></span>'
            : '<span class="text-gray-500 dark:text-gray-400">Tahap Aktif: <strong>—</strong></span>';

        return new HtmlString(
            '<span class="flex flex-wrap gap-x-6 gap-y-1">' .
            $stageHtml .
            '<span class="text-gray-700 dark:text-gray-300">Total Unit: <strong>' . $total . '</strong></span>' .
            '<span class="text-green-700 dark:text-green-400">Sudah Diperiksa: <strong>' . $sudah . '</strong></span>' .
            '<span class="text-amber-700 dark:text-amber-400">Belum Diperiksa: <strong>' . $belum . '</strong></span>' .
            '<span class="text-red-700 dark:text-red-400">Return To PDC: <strong>' . $rtnPdc . '</strong></span>' .
            '</span>'
        );
    }

    public function canCreate(): bool
    {
        return false;
    }
}
