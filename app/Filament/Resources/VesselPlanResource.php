<?php

namespace App\Filament\Resources;

use App\Models\VesselPlan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\VesselPlanResource\Pages;
use App\Filament\Resources\VesselPlanResource\RelationManagers\VesselPlanItemRelationManager;

class VesselPlanResource extends Resource
{
    protected static ?string $model = VesselPlan::class;

    protected static ?string $navigationGroup = 'Perencanaan';
    protected static ?string $navigationLabel = 'Perencanaan Kapal';
    protected static ?string $pluralLabel     = 'Perencanaan Kapal';
    protected static ?string $modelLabel      = 'Perencanaan Kapal';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('F Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->color(fn($state) => $state?->color())
                    ->sortable(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Jadwal'),

                TextColumn::make('avg_sailing')
                    ->label('Avg Sailing')
                    ->getStateUsing(function ($record) {
                        if (!$record) return '-';

                        $avg = $record->analyze()['sailing_avg'] ?? 0;

                        return $avg ? $avg . ' hari' : '-';
                    }),

                TextColumn::make('max_gap')
                    ->label('Max Gap')
                    ->getStateUsing(function ($record) {
                        if (!$record) return '-';

                        $gap = $record->analyze()['max_gap'] ?? 0;

                        return $gap . ' hari';
                    })
                    ->color(
                        fn($record) => ($record?->analyze()['max_gap'] ?? 0) > 6 ? 'danger' : 'success'
                    ),

                TextColumn::make('status_sop')
                    ->label('Status SOP')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        $record?->sopStatus()['label'] ?? '-'
                    )
                    ->color(
                        fn($record) =>
                        $record?->sopStatus()['color'] ?? 'gray'
                    )
                    ->tooltip(fn($record) => $record?->sopStatus()['reason'] ?? null),

                TextColumn::make('feedback_reason')
                    ->label('Alasan Revisi')
                    ->limit(40)
                    ->toggleable()
                    ->visible(fn($record) => $record?->isRevision()),
            ])

            ->actions([

                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record?->isEditable()),

                Tables\Actions\Action::make('submitDraft')
                    ->action(fn($record) => $record->submitDraft(auth()->id())),

                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn($record) => $record->waUrl())
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->hasWhatsappRecipient()),

                Tables\Actions\Action::make('finalize')
                    ->label('Finalisasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn($record) => $record?->finalizeSchedule(auth()->id()))
                    ->visible(fn($record) => $record?->isSent()),

                Tables\Actions\Action::make('feedback')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')
                            ->label('Alasan Revisi')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(
                        fn($record, $data) =>
                        $record?->reject($data['reason'], auth()->id())
                    )
                    ->visible(fn($record) => $record?->isSent()),
            ])
            ->defaultSort('period_month', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            VesselPlanItemRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVesselPlans::route('/'),
            'create' => Pages\CreateVesselPlan::route('/create'),
            'edit'   => Pages\EditVesselPlan::route('/{record}/edit'),
        ];
    }
}
