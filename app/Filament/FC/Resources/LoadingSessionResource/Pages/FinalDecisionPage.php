<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use App\Enums\FinalDecisionStatus;
use App\Enums\LoadingStatus;
use App\Models\LoadingFinalDecision;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

class FinalDecisionPage extends Page
{
    protected static string $resource = LoadingSessionResource::class;

    protected static ?string $title = 'Keputusan Final';

    protected static string $view = 'filament.fc.resources.loading-session-resource.pages.final-decision';

    public $record;
    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $finalDecision = $this->record->finalDecision;
        if ($finalDecision) {
            $this->form->fill($finalDecision->toArray());
        } else {
            // Evaluate and prepare initial decision
            $suggestedStatus = $this->record->evaluateFinalDecision();

            $this->form->fill([
                'loading_session_id' => $this->record->id,
                'status' => $suggestedStatus->value,
                'category' => 'automatic',
                'pillar_issues' => ! $this->record->rack_pillars_ok,
                'drop_floor_issues' => ! $this->record->drop_floor_ok,
                'pulley_issues' => ! $this->record->equipment_safe,
                'apd_incomplete' => ! $this->record->apd_complete,
                'mp_unhealthy' => $this->record->mp_unfit_count > 0,
                'equipment_unsafe' => ! $this->record->equipment_safe,
                'unit_unsafe' => ! $this->record->unit_measurements_ok,
                'stock_apd_insufficient' => ! $this->record->stock_apd_sufficient,
                'mp_insufficient' => ! $this->record->mp_sufficient,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        $suggestedStatus = $this->record->evaluateFinalDecision();

        return $form
            ->schema([
                Section::make('Ringkasan Pemeriksaan')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('mp_summary')
                            ->label('Manpower')
                            ->content(fn () => "{$this->record->mp_present} / {$this->record->mp_required} ({$this->record->mp_sufficient})")
                            ->extraAttributes(['class' => $this->record->mp_sufficient ? 'text-green-600' : 'text-red-600']),

                        Placeholder::make('apd_summary')
                            ->label('APD')
                            ->content(fn () => $this->record->apd_complete ? 'Lengkap' : 'Tidak Lengkap')
                            ->extraAttributes(['class' => $this->record->apd_complete ? 'text-green-600' : 'text-red-600']),

                        Placeholder::make('rack_summary')
                            ->label('Rack Container')
                            ->content(fn () => $this->record->rack_container_safe ? 'Aman' : 'Tidak Aman')
                            ->extraAttributes(['class' => $this->record->rack_container_safe ? 'text-green-600' : 'text-red-600']),

                        Placeholder::make('equipment_summary')
                            ->label('Peralatan')
                            ->content(fn () => $this->record->equipment_safe ? 'Aman' : 'Tidak Aman')
                            ->extraAttributes(['class' => $this->record->equipment_safe ? 'text-green-600' : 'text-red-600']),

                        Placeholder::make('unit_summary')
                            ->label('Unit')
                            ->content(fn () => $this->record->unit_measurements_ok ? 'Aman' : 'Tidak Aman')
                            ->extraAttributes(['class' => $this->record->unit_measurements_ok ? 'text-green-600' : 'text-red-600']),

                        Placeholder::make('issues_summary')
                            ->label('Isu')
                            ->content(fn () => "Kritis: {$this->record->critical_issues_count}, Warning: {$this->record->warning_issues_count}")
                            ->extraAttributes(['class' => $this->record->critical_issues_count > 0 ? 'text-red-600' : 'text-green-600']),
                    ]),

                Section::make('Keputusan')
                    ->schema([
                        Select::make('status')
                            ->label('Status Keputusan')
                            ->options(FinalDecisionStatus::class)
                            ->required()
                            ->live()
                            ->default($suggestedStatus->value)
                            ->helperText(fn ($state) => match($state) {
                                'go' => 'Semua pemeriksaan berhasil. Loading dapat dilanjutkan.',
                                'warning' => 'Ada temuan minor. Perlu approval untuk melanjutkan.',
                                'stop' => 'ADA ISU KRITIS. Loading TIDAK BOLEH dilanjutkan!',
                                default => '',
                            }),

                        Textarea::make('reason')
                            ->label('Alasan Keputusan')
                            ->rows(2)
                            ->placeholder('Jelaskan alasan keputusan ini...'),

                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->rows(3),
                    ]),

                Section::make('Detail Isu')
                    ->visible(fn () => $this->record->critical_issues_count > 0 || $this->record->warning_issues_count > 0)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('critical_issues')
                                    ->label('Isu Kritis')
                                    ->visible(fn () => $this->record->critical_issues_count > 0)
                                    ->content(function () {
                                        $findings = $this->record->findings()->critical()->get();
                                        return $findings->map(fn ($f) => "• {$f->item_name}: {$f->description}")->join("\n");
                                    }),

                                Placeholder::make('warning_issues')
                                    ->label('Isu Peringatan')
                                    ->visible(fn () => $this->record->warning_issues_count > 0)
                                    ->content(function () {
                                        $findings = $this->record->findings()->warning()->get();
                                        return $findings->map(fn ($f) => "• {$f->item_name}: {$f->description}")->join("\n");
                                    }),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        $canProceed = $this->record->evaluateFinalDecision()->canProceed();

        return [
            Action::make('submit_decision')
                ->label('Simpan Keputusan')
                ->color($canProceed ? 'success' : 'danger')
                ->icon($canProceed ? 'heroicon-o-check-circle' : 'heroicon-o-hand-raised')
                ->action('save'),
            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(fn () => $this->getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $data['loading_session_id'] = $this->record->id;
        $data['requested_by'] = auth()->id();
        $data['requested_at'] = now();

        // Create the final decision
        $finalDecision = LoadingFinalDecision::updateOrCreate(
            ['loading_session_id' => $this->record->id],
            $data
        );

        // Update loading session
        $this->record->final_decision_status = $data['status'];
        $this->record->final_decision_by = auth()->id();
        $this->record->final_decision_at = now();
        $this->record->final_decision_notes = $data['notes'] ?? null;
        $this->record->final_decision_completed = true;

        // Set final status based on decision
        if ($data['status'] === FinalDecisionStatus::Stop->value || $data['status'] === FinalDecisionStatus::Rejected->value) {
            $this->record->status = LoadingStatus::Stopped;
            $this->record->stopped_at = now();
        } elseif ($data['status'] === FinalDecisionStatus::Go->value || $data['status'] === FinalDecisionStatus::Approved->value) {
            $this->record->status = LoadingStatus::Completed;
            $this->record->completed_at = now();
        } else {
            $this->record->status = LoadingStatus::FinalDecision;
        }

        $this->record->save();

        // Send notification
        $status = FinalDecisionStatus::from($data['status']);
        Notification::make()
            ->title('Keputusan final berhasil disimpan')
            ->body("Status: {$status->label()}")
            ->{$status->canProceed() ? 'success' : 'warning'}()
            ->send();

        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => $this->getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}
