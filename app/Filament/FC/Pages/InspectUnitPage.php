<?php

namespace App\Filament\FC\Pages;

use App\Models\Shipment;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use App\Services\InspectionDraftAutoCreate;
use App\Services\InspectionGateEvaluator;
use App\Services\ShipmentOwnership;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class InspectUnitPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.fc.resources.shipment-resource.pages.inspect-unit';

    // {record} in route → Livewire binds Shipment model (property name matches)
    public ?Shipment $record = null;

    public ?Unit $inspectedUnit = null;
    public ?UnitInspection $inspection = null;
    public bool $isReadOnly = false;
    public ?array $data = [];

    public static function getSlug(): string
    {
        return 'operational-inspections';
    }

    public static function getRoutePath(): string
    {
        return 'operational-inspections/{record}/{unit}';
    }

    public function mount(Shipment $record, int|string $unit): void
    {
        $this->record = $record;

        abort_unless(auth()->user()?->can('view', $this->record), 403);

        $this->inspectedUnit = Unit::findOrFail($unit);

        abort_if(
            (int) $this->inspectedUnit->shipment_id !== (int) $this->record->getKey(),
            403,
            'Unit tidak milik shipment ini.'
        );

        $stage = $this->resolveStage();
        abort_if(! $stage, 404, 'Tidak ada tahap inspeksi aktif untuk shipment ini.');

        $this->inspection = UnitInspection::with(['items', 'checkedBy'])
            ->where('unit_id', $this->inspectedUnit->id)
            ->where('stage', $stage)
            ->firstOrFail();

        $this->isReadOnly = $this->inspection->submitted_at !== null;

        $this->form->fill([
            'items' => $this->inspection->items->map(fn(UnitInspectionItem $item) => [
                'id'           => $item->id,
                'category'     => $item->category,
                'item_name'    => $item->item_name,
                'result'       => $item->result,
                'finding_type' => $item->finding_type,
                'notes'        => $item->notes,
            ])->toArray(),
        ]);
    }

    private function resolveStage(): ?string
    {
        $status = $this->record?->currentTrackStatus();
        if (! $status) {
            return null;
        }

        return InspectionDraftAutoCreate::resolveStage($status);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('items')
                    ->label('Item Pemeriksaan')
                    ->schema([
                        Hidden::make('id'),

                        Grid::make(4)->schema([
                            TextInput::make('category')
                                ->label('Kategori')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('item_name')
                                ->label('Item')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false),

                            ToggleButtons::make('result')
                                ->label('Hasil')
                                ->options([
                                    UnitInspectionItem::RESULT_OK => 'OK',
                                    UnitInspectionItem::RESULT_NG => 'NG',
                                ])
                                ->colors([
                                    UnitInspectionItem::RESULT_OK => 'success',
                                    UnitInspectionItem::RESULT_NG => 'danger',
                                ])
                                ->default(UnitInspectionItem::RESULT_OK)
                                ->required()
                                ->live()
                                ->disabled($this->isReadOnly)
                                ->grouped(),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('finding_type')
                                ->label('Jenis Temuan')
                                ->options(UnitInspectionItem::FINDING_LABELS)
                                ->required(fn(Get $get) => $get('result') === UnitInspectionItem::RESULT_NG)
                                ->visible(fn(Get $get) => $get('result') === UnitInspectionItem::RESULT_NG)
                                ->disabled($this->isReadOnly)
                                ->live(),

                            Textarea::make('notes')
                                ->label('Catatan / Deskripsi Temuan')
                                ->rows(2)
                                ->required(fn(Get $get) => $get('result') === UnitInspectionItem::RESULT_NG)
                                ->visible(fn(Get $get) => $get('result') === UnitInspectionItem::RESULT_NG)
                                ->disabled($this->isReadOnly),
                        ]),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        if ($this->isReadOnly) {
            return;
        }

        abort_unless(
            auth()->user() && ShipmentOwnership::canEdit(auth()->user(), $this->record),
            403
        );

        $formData = $this->form->getState();

        foreach ($formData['items'] as $itemData) {
            $isNg = $itemData['result'] === UnitInspectionItem::RESULT_NG;

            $this->inspection->items()->whereKey($itemData['id'])->update([
                'result'       => $itemData['result'],
                'finding_type' => $isNg ? ($itemData['finding_type'] ?? null) : null,
                'notes'        => $isNg ? ($itemData['notes'] ?? null) : null,
            ]);
        }

        $this->inspection->refresh();

        $gateDecision = app(InspectionGateEvaluator::class)->evaluate($this->inspection);
        $hasNg        = $this->inspection->items()->where('result', UnitInspectionItem::RESULT_NG)->exists();

        $this->inspection->update([
            'submitted_at'  => now(),
            'checked_at'    => now(),
            'checked_by'    => auth()->id(),
            'status'        => $hasNg ? UnitInspection::STATUS_FAILED : UnitInspection::STATUS_PASSED,
            'gate_decision' => $gateDecision,
        ]);

        Notification::make()
            ->title('Inspeksi berhasil disubmit')
            ->body('Gate Decision: ' . (UnitInspection::GATE_LABELS[$gateDecision] ?? $gateDecision))
            ->success()
            ->send();

        $this->redirect(OperationalShipmentPage::getUrl(['record' => $this->record->getKey()]));
    }

    public function getBreadcrumbs(): array
    {
        return [
            OperationalTasks::getUrl() => 'Tugas Operasional',
            OperationalShipmentPage::getUrl(['record' => $this->record->getKey()]) => 'Detail Pengiriman',
            '#' => $this->getTitle(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        $stageLabel = $this->inspection?->stage_label ?? 'Inspeksi';
        $chassis    = $this->inspectedUnit?->chassis_no ?? '—';

        return "Inspeksi: {$stageLabel} — {$chassis}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetForReinspection')
                ->label('Reset untuk Re-Inspeksi')
                ->icon('heroicon-m-arrow-path')
                ->color('warning')
                ->visible(
                    fn () => $this->inspection?->gate_decision === UnitInspection::GATE_RETURN_TO_PDC
                        && auth()->user() !== null
                        && ShipmentOwnership::canEdit(auth()->user(), $this->record)
                )
                ->requiresConfirmation()
                ->modalHeading('Reset Inspeksi untuk Re-Inspeksi?')
                ->modalDescription(
                    'Status inspeksi unit ini akan dikembalikan ke draft. '
                    . 'Item-item sebelumnya tetap tersimpan dan dapat diubah saat re-inspeksi.'
                )
                ->modalSubmitActionLabel('Ya, Reset')
                ->action(function (): void {
                    $this->inspection->update([
                        'submitted_at'  => null,
                        'gate_decision' => null,
                        'status'        => UnitInspection::STATUS_PENDING,
                        'checked_at'    => null,
                        'checked_by'    => null,
                    ]);

                    Notification::make()
                        ->title('Unit dikembalikan ke Waiting Inspection')
                        ->body('Inspeksi dapat diisi ulang.')
                        ->success()
                        ->send();

                    $this->redirect(request()->url());
                }),

            Action::make('back')
                ->label('Kembali ke Shipment')
                ->url(OperationalShipmentPage::getUrl(['record' => $this->record->getKey()]))
                ->icon('heroicon-m-arrow-left')
                ->color('gray'),
        ];
    }
}
