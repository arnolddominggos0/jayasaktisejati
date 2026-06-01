<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use App\Enums\LoadingStatus;
use App\Models\UnitCheck;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;

class UnitCheckPage extends Page
{
    protected static string $resource = LoadingSessionResource::class;

    protected static ?string $title = 'Cek Unit (Mobil)';

    protected static string $view = 'filament.fc.resources.loading-session-resource.pages.unit-check';

    public $record;
    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        $unitCheck = $this->record->unitCheck;
        if ($unitCheck) {
            $this->form->fill($unitCheck->toArray());
        } else {
            $this->form->fill([
                'loading_session_id' => $this->record->id,
                'validation_ranges' => UnitCheck::getDefaultValidationRanges(),
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Unit')
                    ->columns(3)
                    ->schema([
                        TextInput::make('unit_type')
                            ->label('Jenis Unit')
                            ->placeholder('Contoh: Mobil Box, Truk, dll'),
                        TextInput::make('unit_plate_number')
                            ->label('Nomor Plat'),
                        Select::make('armada_id')
                            ->label('Armada (Jika Ada)')
                            ->relationship('armada', 'plate_number')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),

                Section::make('Pengukuran Jarak (cm)')
                    ->description('Masukkan pengukuran dalam centimeter')
                    ->columns(4)
                    ->schema([
                        TextInput::make('distance_front_rh')
                            ->label('Jarak Front RH')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                        TextInput::make('distance_rear_rh')
                            ->label('Jarak Rear RH')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                        TextInput::make('distance_back_door')
                            ->label('Jarak Back Door')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                        TextInput::make('distance_rear_lh')
                            ->label('Jarak Rear LH')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                        TextInput::make('distance_front_lh')
                            ->label('Jarak Front LH')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                        TextInput::make('drop_floor_front_height')
                            ->label('Tinggi Drop Floor Depan')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                        TextInput::make('drop_floor_rear_height')
                            ->label('Tinggi Drop Floor Belakang')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                        TextInput::make('container_roof_distance')
                            ->label('Jarak Atap Container')
                            ->numeric()
                            ->suffix('cm')
                            ->required(),
                    ]),

                Section::make('Foto Unit')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('photo_front_view')
                            ->label('Foto Tampak Depan')
                            ->image()
                            ->directory('loading-sessions/units'),
                        FileUpload::make('photo_side_view')
                            ->label('Foto Tampak Samping')
                            ->image()
                            ->directory('loading-sessions/units'),
                        FileUpload::make('photo_rear_view')
                            ->label('Foto Tampak Belakang')
                            ->image()
                            ->directory('loading-sessions/units'),
                        FileUpload::make('photo_top_view')
                            ->label('Foto Tampak Atas (Opsional)')
                            ->image()
                            ->directory('loading-sessions/units'),
                    ]),

                Section::make('Hasil Pemeriksaan')
                    ->schema([
                        Toggle::make('measurements_valid')
                            ->label('Pengukuran Valid')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('measurement_notes')
                            ->label('Catatan Pengukuran')
                            ->rows(2),
                        Toggle::make('unit_safe_for_loading')
                            ->label('Unit Aman untuk Loading')
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('safety_notes')
                            ->label('Catatan Keselamatan')
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

        $unitCheck = UnitCheck::updateOrCreate(
            ['loading_session_id' => $this->record->id],
            $data
        );

        $unitCheck->updateSafetyStatus();

        $this->record->unit_check_completed = true;
        $this->record->status = LoadingStatus::FinalDecision;
        $this->record->save();

        $this->record->recalculateSafetyStatus();

        Notification::make()
            ->title('Cek unit berhasil disimpan')
            ->success()
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
