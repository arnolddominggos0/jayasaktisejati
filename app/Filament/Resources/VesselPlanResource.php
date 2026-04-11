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

    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationLabel = 'Rencana Jadwal Kapal';
    protected static ?string $pluralLabel     = 'Rencana Jadwal Kapal';
    protected static ?string $modelLabel      = 'Rencana Jadwal Kapal';
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
                    ->label('Jumlah Jadwal'),

                TextColumn::make('kpi_total')
                    ->label('Total KPI')
                    ->getStateUsing(fn($record) =>
                        $record?->kpi_total ? $record->kpi_total . ' hari' : '-'
                    )
                    ->color(fn($record) => $record?->sopStatus()['color'] ?? 'gray'),

                TextColumn::make('kpi_breakdown')
                    ->label('Dw/Sa/Dr')
                    ->getStateUsing(function ($record) {

                        if (!$record) return '-';

                        $a = $record->analyze();

                        $dw = $a['dwelling'] ?? 0;
                        $sa = $a['sailing_avg'] ?? 0;
                        $dr = $a['dooring'] ?? 0;

                        return "{$dw} / {$sa} / {$dr}";
                    }),

                TextColumn::make('max_gap')
                    ->label('Max Gap')
                    ->getStateUsing(function ($record) {

                        if (!$record) return '-';

                        $gap = $record->analyze()['max_gap'] ?? 0;

                        return $gap . ' hari';
                    })
                    ->color(fn($record) =>
                        ($record?->analyze()['max_gap'] ?? 0) > 6 ? 'danger' : 'success'
                    ),

                TextColumn::make('status_sop')
                    ->label('Status SOP')
                    ->badge()
                    ->getStateUsing(fn($record) =>
                        $record?->sopStatus()['label'] ?? '-'
                    )
                    ->color(fn($record) =>
                        $record?->sopStatus()['color'] ?? 'gray'
                    ),

                TextColumn::make('feedback_reason')
                    ->label('Alasan Revisi')
                    ->limit(40)
                    ->toggleable()
                    ->visible(fn($record) => $record?->isRevision()),
            ])

            ->actions([

                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record?->isEditable()),

                Tables\Actions\Action::make('send')
                    ->label('Kirim')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->action(fn($record) => $record?->markAsSent(auth()->id()))
                    ->visible(fn($record) => $record?->isDraft()),

                Tables\Actions\Action::make('approve')
                    ->label('Finalisasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn($record) => $record?->approve(auth()->id()))
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
                    ->action(fn($record, $data) =>
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