<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use App\Enums\LoadingStatus;
use App\Models\EquipmentCheck;
use App\Models\LoadingFinding;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

class EquipmentCheckPage extends Page
{
    protected static string $resource = LoadingSessionResource::class;

    protected static ?string $title = 'Cek Alat Loading';

    protected static string $view = 'filament.fc.resources.loading-session-resource.pages.equipment-check';

    public $record;
    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $equipmentCheck = $this->record->equipmentCheck;
        if ($equipmentCheck) {
            $this->form->fill($equipmentCheck->toArray());
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
                Section::make('Katrol')
                    ->columns(2)
                    ->schema([
                        Select::make('pulley_top_status')
                            ->label('Katrol Atas')
                            ->options(['ok' => 'OK', 'not_ok' => 'Tidak OK'])
                            ->required(),
                        Select::make('pulley_bottom_status')
                            ->label('Katrol Bawah')
                            ->options(['ok' => 'OK', 'not_ok' => 'Tidak OK'])
                            ->required(),
                        FileUpload::make('pulley_top_photo')
                            ->label('Foto Katrol Atas')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                        FileUpload::make('pulley_bottom_photo')
                            ->label('Foto Katrol Bawah')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                        Textarea::make('pulley_top_notes')
                            ->label('Catatan Katrol Atas')
                            ->rows(2)
                            ->columnSpan(1),
                        Textarea::make('pulley_bottom_notes')
                            ->label('Catatan Katrol Bawah')
                            ->rows(2)
                            ->columnSpan(1),
                    ]),

                Section::make('Tali Mono & Rantai')
                    ->columns(2)
                    ->schema([
                        Select::make('mono_rope_condition')
                            ->label('Kondisi Tali Mono')
                            ->options(['new' => 'Baru', 'worn' => 'Aus', 'ok' => 'OK'])
                            ->required(),
                        Select::make('chain_strength')
                            ->label('Kekuatan Rantai')
                            ->options(['strong' => 'Kuat', 'loose' => 'Longgar', 'ok' => 'OK'])
                            ->required(),
                        FileUpload::make('mono_rope_photo')
                            ->label('Foto Tali Mono')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                        FileUpload::make('chain_photo')
                            ->label('Foto Rantai')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                    ]),

                Section::make('Mur/Baut & Bambu')
                    ->columns(2)
                    ->schema([
                        Select::make('bolt_nut_status')
                            ->label('Status Mur/Baut')
                            ->options(['tight' => 'Kencang', 'loose' => 'Longgar', 'ok' => 'OK'])
                            ->required(),
                        Select::make('bamboo_condition')
                            ->label('Kondisi Bambu')
                            ->options(['thick' => 'Tebal', 'cracked' => 'Retak', 'ok' => 'OK'])
                            ->required(),
                        FileUpload::make('bolt_nut_photo')
                            ->label('Foto Mur/Baut')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                        FileUpload::make('bamboo_photo')
                            ->label('Foto Bambu')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                    ]),

                Section::make('Tangga & Sponds')
                    ->columns(2)
                    ->schema([
                        Select::make('ladder_stability')
                            ->label('Stabilitas Tangga')
                            ->options(['stable' => 'Stabil', 'unstable' => 'Tidak Stabil', 'ok' => 'OK'])
                            ->required(),
                        Select::make('sponds_cleanliness')
                            ->label('Kebersihan Sponds')
                            ->options(['clean' => 'Bersih', 'dirty' => 'Kotor', 'ok' => 'OK'])
                            ->required(),
                        FileUpload::make('ladder_photo')
                            ->label('Foto Tangga')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                        FileUpload::make('sponds_photo')
                            ->label('Foto Sponds')
                            ->image()
                            ->directory('loading-sessions/equipment'),
                    ]),

                Section::make('Peralatan Lainnya')
                    ->schema([
                        Textarea::make('other_equipment')
                            ->label('Peralatan Tambahan')
                            ->rows(2),
                        Textarea::make('other_equipment_notes')
                            ->label('Catatan')
                            ->rows(2),
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

        $equipmentCheck = EquipmentCheck::updateOrCreate(
            ['loading_session_id' => $this->record->id],
            $data
        );

        $equipmentCheck->updateSafetyStatus();
        $this->createFindings($equipmentCheck);

        $this->record->equipment_check_completed = true;
        $this->record->status = LoadingStatus::UnitCheck;
        $this->record->save();

        $this->record->recalculateSafetyStatus();

        Notification::make()
            ->title('Cek alat berhasil disimpan')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
    }

    protected function createFindings(EquipmentCheck $equipmentCheck): void
    {
        $findings = $equipmentCheck->getFindings();

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
