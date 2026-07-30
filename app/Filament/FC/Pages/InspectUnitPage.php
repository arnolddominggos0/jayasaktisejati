<?php

namespace App\Filament\FC\Pages;

use App\Models\Shipment;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use App\Services\InspectionDraftAutoCreate;
use App\Services\InspectionGateEvaluator;
use App\Services\InspectionPdfGenerator;
use App\Services\ShipmentOwnership;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class InspectUnitPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.fc.pages.inspect-unit';

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
            'signed_by'       => $this->inspection->signed_by,
            'signed_position' => $this->inspection->signed_position,
            'signature_data'  => null,
        ]);
    }

    private function resolveStage(): ?string
    {
        $status = $this->inspectedUnit?->currentTrackStatus();

        return $status ? InspectionDraftAutoCreate::resolveStage($status) : null;
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
                                ->dehydrated(false)
                                ->helperText(function (Get $get): ?Htmlable {
                                    $text = InspectionDraftAutoCreate::criteriaHelperText($get('category'), $get('item_name'));

                                    if (blank($text)) {
                                        return null;
                                    }

                                    return new HtmlString(
                                        '<span class="mt-1 block max-w-full text-xs leading-relaxed text-gray-500 dark:text-gray-400">'
                                        . e($text)
                                        . '</span>'
                                    );
                                }),

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
                Section::make('Persetujuan Inspeksi (Inspection Approval)')
                    ->schema([
                        TextInput::make('signed_by')
                            ->label('Nama PIC')
                            ->required(! $this->isReadOnly)
                            ->disabled($this->isReadOnly)
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('signed_position')
                            ->label('Jabatan PIC')
                            ->required(! $this->isReadOnly)
                            ->disabled($this->isReadOnly)
                            ->maxLength(255)
                            ->columnSpan(1),

                        Hidden::make('signature_data'),

                        Placeholder::make('signature_pad')
                            ->label('Tanda Tangan Pemeriksa')
                            ->content(fn (): HtmlString => new HtmlString(
                                view('filament.fc.pages.partials.signature-pad')->render()
                            ))
                            ->visible(! $this->isReadOnly)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
        if (blank($formData['signed_by'] ?? null) || blank($formData['signed_position'] ?? null) || blank($formData['signature_data'] ?? null)) {
            Notification::make()
                ->title('Finalize ditolak')
                ->body('Nama PIC, Jabatan PIC, dan Tanda Tangan Digital wajib diisi lengkap sebelum inspeksi dapat di-Finalize.')
                ->danger()
                ->send();

            return;
        }

        $signaturePath = $this->storeSignature($formData['signature_data']);

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
            'submitted_at'    => now(),
            'checked_at'      => now(),
            'checked_by'      => auth()->id(),
            'status'          => $hasNg ? UnitInspection::STATUS_FAILED : UnitInspection::STATUS_PASSED,
            'gate_decision'   => $gateDecision,
            'signed_by'       => $formData['signed_by'],
            'signed_position' => $formData['signed_position'],
            'signed_at'       => now(),
            'signature_path'  => $signaturePath,
        ]);
        \Illuminate\Support\Facades\Log::info('INSPECTION FINALIZED', [
            'inspection_id' => $this->inspection->id,
            'unit_id' => $this->inspection->unit_id,
            'stage' => $this->inspection->stage,
            'signed_by' => $this->inspection->signed_by,
            'signed_position' => $this->inspection->signed_position,
            'signed_at' => (string) $this->inspection->signed_at,
            'result' => $this->inspection->status,
            'gate_decision' => $this->inspection->gate_decision,
        ]);

        // Generate PDF evidence — non-blocking; failure logs but does not abort redirect.
        try {
            $pdfPath = app(InspectionPdfGenerator::class)->generate($this->inspection);
            $this->inspection->updateQuietly([
                'pdf_path'         => $pdfPath,
                'pdf_generated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('InspectionPdfGenerator failed', [
                'inspection_id' => $this->inspection->id,
                'error'         => $e->getMessage(),
            ]);
        }

        Notification::make()
            ->title('Inspeksi berhasil di-Finalize')
            ->body('Gate Decision: ' . (UnitInspection::GATE_LABELS[$gateDecision] ?? $gateDecision))
            ->success()
            ->send();

        $this->redirect(OperationalTasks::getUrl());
    }

    private function storeSignature(string $dataUrl): string
    {
        [, $encoded] = explode(',', $dataUrl, 2);

        $path = 'inspections/signatures/' . Str::uuid() . '.png';

        Storage::disk('public')->put($path, base64_decode($encoded));

        return $path;
    }

    public function getBreadcrumbs(): array
    {
        return [
            OperationalTasks::getUrl() => 'Tugas Operasional',
            '#' => 'Inspeksi Unit',
            '##' => $this->inspectedUnit?->chassis_no ?? '—',
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Inspeksi Unit';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $chassis    = $this->inspectedUnit?->chassis_no ?? '—';
        $stageLabel = $this->inspection?->stage_label ?? '—';

        return "{$chassis} · {$stageLabel}";
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
                        'submitted_at'    => null,
                        'gate_decision'   => null,
                        'status'          => UnitInspection::STATUS_PENDING,
                        'checked_at'      => null,
                        'checked_by'      => null,
                        'signed_by'       => null,
                        'signed_position' => null,
                        'signed_at'       => null,
                        'signature_path'  => null,
                    ]);

                    Notification::make()
                        ->title('Unit dikembalikan ke Waiting Inspection')
                        ->body('Inspeksi dapat diisi ulang.')
                        ->success()
                        ->send();

                    $this->redirect(request()->url());
                }),

            Action::make('back')
                ->label('Kembali ke Tugas Operasional')
                ->url(OperationalTasks::getUrl())
                ->icon('heroicon-m-arrow-left')
                ->color('gray'),
        ];
    }
}
