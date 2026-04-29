<?php

namespace App\Filament\FC\Resources;

use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use App\Filament\FC\Resources\LoadingSessionResource\Pages;
use App\Models\LoadingSession;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoadingSessionResource extends Resource
{
    protected static ?string $model = LoadingSession::class;

    // Hidden from navigation - only accessible as Shipment sub-process
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel       = 'Sesi Loading';
    protected static ?string $pluralModelLabel = 'Sesi Loading';

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

        $u = Filament::auth()->user();
        if (! $u) {
            return $q->whereRaw('1=0');
        }

        $branchId = app()->bound('scope.branch_id')
            ? app('scope.branch_id')
            : ($u->branch_id ?? null);

        if ($branchId) {
            $q->where('branch_id', $branchId);
        }

        $depotId = app()->bound('scope.depot_id')
            ? app('scope.depot_id')
            : Depot::where('coordinator_user_id', $u->id)->value('id');

        if ($depotId) {
            $q->where('depot_id', $depotId);
        }

        return $q->with([
            'depot:id,name',
            'coordinator:id,name',
            'briefingSession:id,date',
            'shipment:id,ship_code',
            'finalDecision',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Header Information
                Section::make('Informasi Sesi')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Kode Sesi')
                                    ->disabled()
                                    ->dehydrated(false),

                                Select::make('operation_type')
                                    ->label('Jenis Operasi')
                                    ->options(LoadingOperationType::class)
                                    ->required()
                                    ->disabled(fn ($record) => $record !== null),

                                Select::make('status')
                                    ->label('Status')
                                    ->options(LoadingStatus::class)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('briefing_session_id')
                                    ->label('Sesi Briefing')
                                    ->relationship('briefingSession', 'date')
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn ($record) => $record !== null)
                                    ->helperText('Pilih sesi briefing yang terkait'),

                                Select::make('shipment_id')
                                    ->label('Pengiriman')
                                    ->relationship('shipment', 'ship_code')
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn ($record) => $record !== null),
                            ]),
                    ]),

                // Progress Section
                Section::make('Progres Pemeriksaan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('mp_attendance_completed')
                                    ->label('Kehadiran MP')
                                    ->disabled(),

                                Toggle::make('health_check_completed')
                                    ->label('Cek Kesehatan')
                                    ->disabled(),

                                Toggle::make('apd_check_completed')
                                    ->label('Cek APD')
                                    ->disabled(),

                                Toggle::make('equipment_check_completed')
                                    ->label('Cek Alat')
                                    ->disabled(),

                                Toggle::make('rack_container_check_completed')
                                    ->label('Cek Rack Container')
                                    ->disabled(),

                                Toggle::make('unit_check_completed')
                                    ->label('Cek Unit')
                                    ->disabled(),

                                Toggle::make('stock_apd_check_completed')
                                    ->label('Cek Stok APD')
                                    ->disabled(),

                                Toggle::make('manpower_availability_completed')
                                    ->label('Ketersediaan MP')
                                    ->disabled(),

                                Toggle::make('final_decision_completed')
                                    ->label('Keputusan Final')
                                    ->disabled(),
                            ]),
                    ])
                    ->visible(fn ($record) => $record !== null),

                // Summary Section
                Section::make('Ringkasan')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('mp_required')
                                    ->label('MP Dibutuhkan')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('mp_present')
                                    ->label('MP Hadir')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('mp_sufficient')
                                    ->label('MP Mencukupi')
                                    ->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak')
                                    ->disabled(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('critical_issues_count')
                                    ->label('Isu Kritis')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('warning_issues_count')
                                    ->label('Isu Peringatan')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('final_decision_status')
                                    ->label('Keputusan Final')
                                    ->formatStateUsing(fn ($state) => $state?->label() ?? '-')
                                    ->disabled(),
                            ]),
                    ])
                    ->visible(fn ($record) => $record !== null),

                // Notes
                Section::make('Catatan')
                    ->schema([
                        Textarea::make('general_notes')
                            ->label('Catatan Umum')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('operation_type')
                    ->label('Operasi')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color())
                    ->icon(fn ($state) => $state->icon()),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color())
                    ->icon(fn ($state) => $state->icon()),

                TextColumn::make('depot.name')
                    ->label('Depot')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('shipment.ship_code')
                    ->label('Pengiriman')
                    ->searchable()
                    ->placeholder('-')
                    ->url(fn ($record) => $record->shipment_id ? ShipmentResource::getUrl('view', ['record' => $record->shipment_id]) : null, true),

                TextColumn::make('briefingSession.date')
                    ->label('Tanggal Briefing')
                    ->date('d M Y')
                    ->placeholder('-'),

                TextColumn::make('progress')
                    ->label('Progres')
                    ->badge()
                    ->color(fn ($record) => $record->getProgressPercentage() === 100 ? 'success' : 'warning')
                    ->formatStateUsing(fn ($record) => $record->getProgressPercentage() . '%'),

                IconColumn::make('has_critical')
                    ->label('Isu Kritis')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->critical_issues_count > 0)
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon('heroicon-o-check-circle')
                    ->falseColor('success'),

                BadgeColumn::make('final_decision_status')
                    ->label('Keputusan')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Belum Ada')
                    ->color(fn ($state) => $state?->color() ?? 'gray'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('operation_type')
                    ->label('Jenis Operasi')
                    ->options(LoadingOperationType::class),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(LoadingStatus::class),

                Tables\Filters\Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query) => $query->today()),

                Tables\Filters\Filter::make('has_critical')
                    ->label('Ada Isu Kritis')
                    ->query(fn (Builder $query) => $query->where('critical_issues_count', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye'),

                Tables\Actions\Action::make('continue')
                    ->label('Lanjutkan')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->visible(fn ($record) => ! $record->isCompleted() && ! $record->isStopped()),

                Tables\Actions\Action::make('rack_check')
                    ->label('Cek Rack')
                    ->icon('heroicon-o-cube')
                    ->color('warning')
                    ->url(fn ($record) => static::getUrl('rack-check', ['record' => $record]))
                    ->visible(fn ($record) => $record->rack_container_check_completed === false),

                Tables\Actions\Action::make('equipment_check')
                    ->label('Cek Alat')
                    ->icon('heroicon-o-wrench')
                    ->color('warning')
                    ->url(fn ($record) => static::getUrl('equipment-check', ['record' => $record]))
                    ->visible(fn ($record) => $record->equipment_check_completed === false),

                Tables\Actions\Action::make('unit_check')
                    ->label('Cek Unit')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->url(fn ($record) => static::getUrl('unit-check', ['record' => $record]))
                    ->visible(fn ($record) => $record->unit_check_completed === false),

                Tables\Actions\Action::make('final_decision')
                    ->label('Keputusan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->url(fn ($record) => static::getUrl('final-decision', ['record' => $record]))
                    ->visible(fn ($record) => $record->canProceed() === false && $record->final_decision_completed),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Filament::auth()->user()?->hasRole('super_admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoadingSessions::route('/'),
            'create' => Pages\CreateLoadingSession::route('/create'),
            'view' => Pages\ViewLoadingSession::route('/{record}'),
            'edit' => Pages\EditLoadingSession::route('/{record}/edit'),
            'rack-check' => Pages\RackContainerCheckPage::route('/{record}/rack-check'),
            'equipment-check' => Pages\EquipmentCheckPage::route('/{record}/equipment-check'),
            'unit-check' => Pages\UnitCheckPage::route('/{record}/unit-check'),
            'final-decision' => Pages\FinalDecisionPage::route('/{record}/final-decision'),
        ];
    }
}
