<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use App\Enums\LoadingStatus;
use App\Enums\RackPillarCondition;
use App\Enums\RackPulleyHookStatus;
use App\Enums\RackTieStatus;
use App\Enums\DropFloorCondition;
use App\Enums\DropFloorStrength;
use App\Enums\IronHookStatus;
use App\Enums\ContainerStructureStatus;
use App\Models\RackContainerCheck;
use App\Models\LoadingFinding;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

class RackContainerCheckPage extends Page
{
    protected static string $resource = LoadingSessionResource::class;

    protected static ?string $title = 'Cek Rack Container';

    protected static string $view = 'filament.fc.resources.loading-session-resource.pages.rack-container-check';

    public $record;

    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        // Load existing check data if available
        $rackCheck = $this->record->rackContainerCheck;
        if ($rackCheck) {
            $this->form->fill($rackCheck->toArray());
        } else {
            $this->form->fill([
                'loading_session_id' => $this->record->id,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Pillar A - Depan Kanan
                Section::make('Pilar Depan Kanan (A)')
                    ->columns(3)
                    ->schema([
                        Select::make('pillar_a_condition')
                            ->label('Kondisi Pilar')
                            ->options(RackPillarCondition::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_a_pulley_hook')
                            ->label('Pengait Katrol')
                            ->options(RackPulleyHookStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_a_tie_status')
                            ->label('Ikatan ke Container')
                            ->options(RackTieStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        FileUpload::make('pillar_a_photo')
                            ->label('Foto Pilar A')
                            ->image()
                            ->directory('loading-sessions/rack-pillars')
                            ->columnSpan(2),
                        Textarea::make('pillar_a_notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                // Pillar B - Depan Kiri
                Section::make('Pilar Depan Kiri (B)')
                    ->columns(3)
                    ->schema([
                        Select::make('pillar_b_condition')
                            ->label('Kondisi Pilar')
                            ->options(RackPillarCondition::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_b_pulley_hook')
                            ->label('Pengait Katrol')
                            ->options(RackPulleyHookStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_b_tie_status')
                            ->label('Ikatan ke Container')
                            ->options(RackTieStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        FileUpload::make('pillar_b_photo')
                            ->label('Foto Pilar B')
                            ->image()
                            ->directory('loading-sessions/rack-pillars')
                            ->columnSpan(2),
                        Textarea::make('pillar_b_notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                // Pillar C - Belakang Kanan
                Section::make('Pilar Belakang Kanan (C)')
                    ->columns(3)
                    ->schema([
                        Select::make('pillar_c_condition')
                            ->label('Kondisi Pilar')
                            ->options(RackPillarCondition::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_c_pulley_hook')
                            ->label('Pengait Katrol')
                            ->options(RackPulleyHookStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_c_tie_status')
                            ->label('Ikatan ke Container')
                            ->options(RackTieStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        FileUpload::make('pillar_c_photo')
                            ->label('Foto Pilar C')
                            ->image()
                            ->directory('loading-sessions/rack-pillars')
                            ->columnSpan(2),
                        Textarea::make('pillar_c_notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                // Pillar D - Belakang Kiri
                Section::make('Pilar Belakang Kiri (D)')
                    ->columns(3)
                    ->schema([
                        Select::make('pillar_d_condition')
                            ->label('Kondisi Pilar')
                            ->options(RackPillarCondition::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_d_pulley_hook')
                            ->label('Pengait Katrol')
                            ->options(RackPulleyHookStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('pillar_d_tie_status')
                            ->label('Ikatan ke Container')
                            ->options(RackTieStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        FileUpload::make('pillar_d_photo')
                            ->label('Foto Pilar D')
                            ->image()
                            ->directory('loading-sessions/rack-pillars')
                            ->columnSpan(2),
                        Textarea::make('pillar_d_notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                // Drop Floor
                Section::make('Drop Floor Depan')
                    ->columns(3)
                    ->schema([
                        Select::make('drop_floor_front_condition')
                            ->label('Kondisi')
                            ->options(DropFloorCondition::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('drop_floor_front_strength')
                            ->label('Kekuatan')
                            ->options(DropFloorStrength::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('drop_floor_front_iron_hook')
                            ->label('Pengait Besi')
                            ->options(IronHookStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        FileUpload::make('drop_floor_front_photo')
                            ->label('Foto Drop Floor Depan')
                            ->image()
                            ->directory('loading-sessions/drop-floors')
                            ->columnSpan(2),
                        Textarea::make('drop_floor_front_notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                Section::make('Drop Floor Belakang')
                    ->columns(3)
                    ->schema([
                        Select::make('drop_floor_rear_condition')
                            ->label('Kondisi')
                            ->options(DropFloorCondition::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('drop_floor_rear_strength')
                            ->label('Kekuatan')
                            ->options(DropFloorStrength::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('drop_floor_rear_iron_hook')
                            ->label('Pengait Besi')
                            ->options(IronHookStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        FileUpload::make('drop_floor_rear_photo')
                            ->label('Foto Drop Floor Belakang')
                            ->image()
                            ->directory('loading-sessions/drop-floors')
                            ->columnSpan(2),
                        Textarea::make('drop_floor_rear_notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                // Container Structure
                Section::make('Struktur Dalam Container')
                    ->columns(3)
                    ->schema([
                        Select::make('container_wall_status')
                            ->label('Dinding Container')
                            ->options(ContainerStructureStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('container_floor_status')
                            ->label('Lantai Container')
                            ->options(ContainerStructureStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        Select::make('container_roof_status')
                            ->label('Atap Container')
                            ->options(ContainerStructureStatus::class)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateSummary()),
                        FileUpload::make('container_structure_photo')
                            ->label('Foto Struktur Container')
                            ->image()
                            ->directory('loading-sessions/container-structure')
                            ->columnSpan(2),
                        Textarea::make('container_structure_notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan & Lanjutkan')
                ->color('primary')
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
        $data['checked_by'] = auth()->id();
        $data['checked_at'] = now();

        // Create or update rack container check
        $rackCheck = RackContainerCheck::updateOrCreate(
            ['loading_session_id' => $this->record->id],
            $data
        );

        // Update issue counts and safety status
        $rackCheck->updateIssueCounts();

        // Create findings for critical issues
        $this->createFindings($rackCheck);

        // Update loading session
        $this->record->rack_container_check_completed = true;
        $this->record->status = LoadingStatus::EquipmentCheck;
        $this->record->save();

        // Recalculate safety status
        $this->record->recalculateSafetyStatus();

        Notification::make()
            ->title('Cek rack container berhasil disimpan')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
    }

    protected function updateSummary(): void
    {
        // This method can be used to update real-time summary display
    }

    protected function createFindings(RackContainerCheck $rackCheck): void
    {
        $findings = $rackCheck->getFindings();

        foreach ($findings as $finding) {
            LoadingFinding::create([
                'loading_session_id' => $this->record->id,
                'category' => $finding['category'],
                'severity' => $finding['severity'],
                'item_name' => $finding['item'],
                'finding_type' => $finding['issue'],
                'description' => $finding['issue'],
                'status' => 'open',
                'created_by' => auth()->id(),
            ]);
        }

        // Update session issue counts
        $this->record->critical_issues_count = $this->record->findings()->critical()->count();
        $this->record->warning_issues_count = $this->record->findings()->warning()->count();
        $this->record->save();
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
