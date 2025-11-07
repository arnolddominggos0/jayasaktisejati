<?php

namespace App\Filament\Resources;

use App\Actions\Schedule\CreateTamDraftSnapshot;
use App\Actions\Schedule\FinalizeSchedule;
use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource\Pages;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Operasional Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Pengiriman TAM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('voyage_id')
                ->relationship('voyage', 'voyage_no')
                ->required()->searchable()->label('Voyage'),
            Forms\Components\TextInput::make('cargo_plan')->numeric()->required()->minValue(1)->label('Cargo Plan'),
            Forms\Components\TextInput::make('jss')->maxLength(100)->label('JSS'),
            Forms\Components\TextInput::make('dwelling_days')->numeric()->label('Dwelling (hari)'),
            Forms\Components\TextInput::make('state')->label('Status')
                ->disabled()
                ->formatStateUsing(fn($state) => is_string($state) ? strtoupper($state) : strtoupper($state?->value)),
            Forms\Components\Textarea::make('final_note')->label('Catatan'),
            Forms\Components\FileUpload::make('final_attachment_path')
                ->label('Lampiran')
                ->directory('schedule-attachments')
                ->preserveFilenames(),
            Forms\Components\DateTimePicker::make('finalized_at')->label('Tanggal Final')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('voyage.vessel.shippingLine.name')->label('Shipping Line')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('voyage.vessel.name')->label('Vessel')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('voyage.vessel.capacity')->label('Vessel Capacity'),
            Tables\Columns\TextColumn::make('voyage.voyage_no')->label('Voyage No')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('jss')->label('JSS'),
            Tables\Columns\TextColumn::make('cargo_plan')->label('Cargo Plan'),
            Tables\Columns\TextColumn::make('dwelling_days')->label('Dwelling'),
            Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime()->sortable(),

            Tables\Columns\TextColumn::make('state')
                ->label('Status')
                ->badge()
                ->color(fn($state) => match (is_string($state) ? $state : $state?->value) {
                    'draft'    => 'warning',
                    'feedback' => 'info',
                    'final'    => 'success',
                    default    => 'gray',
                })
                ->formatStateUsing(fn($state) => strtoupper(is_string($state) ? $state : $state?->value)),

            Tables\Columns\TextColumn::make('tam_draft_path')
                ->label('Draft TAM')
                ->formatStateUsing(fn($s) => $s ? 'Unduh' : '-')
                ->url(fn($record) => $record->tam_draft_path ? asset('storage/' . $record->tam_draft_path) : null, true),

        ])->filters([
            Tables\Filters\SelectFilter::make('state')->label('Status')
                ->options([
                    'draft'    => 'Draft',
                    'feedback' => 'Feedback',
                    'final'    => 'Final',
                ]),
        ])->actions([
            Tables\Actions\EditAction::make(),

            Tables\Actions\Action::make('generateDraft')
                ->label('Buat Draft TAM')
                ->icon('heroicon-o-document-plus')
                ->action(fn($record) => CreateTamDraftSnapshot::run($record))
                ->visible(fn($record) => $record->state === ScheduleState::Draft),

            Tables\Actions\Action::make('markFeedback')
                ->label('Tandai Feedback')
                ->color('info')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->requiresConfirmation()
                ->action(fn($record) => $record->update(['state' => ScheduleState::Feedback]))
                ->visible(fn($record) => $record->state === ScheduleState::Draft),

            Tables\Actions\Action::make('finalize')
                ->label('Finalize')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->action(fn($record) => FinalizeSchedule::run($record, auth_user()->name ?? 'System'))
                ->visible(fn($record) => $record->state === ScheduleState::Feedback),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ])->defaultSort('etd', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingSchedules::route('/'),
            'create' => Pages\CreateShippingSchedule::route('/create'),
            'edit'   => Pages\EditShippingSchedule::route('/{record}/edit'),
        ];
    }
}
