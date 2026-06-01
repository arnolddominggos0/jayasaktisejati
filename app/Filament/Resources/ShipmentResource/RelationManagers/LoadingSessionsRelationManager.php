<?php

namespace App\Filament\Resources\ShipmentResource\RelationManagers;

use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use App\Enums\FinalDecisionStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LoadingSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'loadingSessions';

    protected static ?string $title = 'Loading & Unloading Sessions';

    protected static ?string $modelLabel = 'Loading Session';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('operation_type')
                    ->label('Jenis Operasi')
                    ->options(LoadingOperationType::class)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(LoadingStatus::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('operation_type')
                    ->label('Operasi')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progres')
                    ->formatStateUsing(fn ($record) => $record->getProgressPercentage() . '%')
                    ->badge()
                    ->color(fn ($record) => $record->getProgressPercentage() === 100 ? 'success' : 'warning'),
                Tables\Columns\BadgeColumn::make('final_decision_status')
                    ->label('Keputusan')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Belum Ada')
                    ->color(fn ($state) => $state?->color() ?? 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('operation_type')
                    ->label('Jenis Operasi')
                    ->options(LoadingOperationType::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(LoadingStatus::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Loading Session')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['shipment_id'] = $this->getOwnerRecord()->id;
                        $data['branch_id'] = $this->getOwnerRecord()->branch_id;
                        $data['depot_id'] = $this->getOwnerRecord()->assigned_depot_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.fc.resources.loading-sessions.view', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => route('filament.fc.resources.loading-sessions.edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
