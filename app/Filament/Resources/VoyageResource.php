<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoyageResource\Pages\ListVoyages;
use App\Filament\Resources\VoyageResource\Pages\ViewVoyage;
use App\Filament\Resources\VoyageResource\RelationManagers\PlansRelationManager;
use App\Models\Port;
use App\Models\Voyage;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Jadwal Kapal';
    protected static ?string $navigationIcon = 'heroicon-m-rocket-launch';
    protected static ?int $navigationSort = 11;

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $bitungIds = Port::query()
                    ->where('code', 'IDBIT')
                    ->orWhere('name', 'ilike', '%Bitung%')
                    ->orWhere('name', 'ilike', '%Manado%')
                    ->pluck('id')->all();

                $query->onlyFinal();
                if (!empty($bitungIds)) {
                    $query->whereIn('port_to_id', $bitungIds);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('plan_etd')->label('ETD')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('plan_eta')->label('ETA')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('shippingLine.name')->label('Line')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('vessel.name')->label('Vessel')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('voyage_no')->label('Voyage')->searchable(),
                Tables\Columns\TextColumn::make('portFrom.code')->label('POL')->badge(),
                Tables\Columns\TextColumn::make('portTo.code')->label('POD')->badge(),
                Tables\Columns\TextColumn::make('plans_count')->counts('plans')->label('Revisi Final'),
            ])
            ->filters([
                SelectFilter::make('port_to_id')
                    ->label('POD')
                    ->relationship('portTo', 'name')
                    ->preload()
                    ->default(fn() => Port::query()
                        ->where('code', 'IDBIT')
                        ->orWhere('name', 'ilike', '%Bitung%')
                        ->orWhere('name', 'ilike', '%Manado%')->value('id')),

                Filter::make('etd_between')
                    ->form([
                        DatePicker::make('from')->label('ETD dari'),
                        DatePicker::make('to')->label('ETD sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->whereHas('plans', function (Builder $p) use ($data) {
                            if ($data['from'] ?? null) {
                                $p->whereRaw("(payload->>'etd')::timestamp >= ?", [$data['from'] . ' 00:00:00']);
                            }
                            if ($data['to'] ?? null) {
                                $p->whereRaw("(payload->>'etd')::timestamp <= ?", [$data['to'] . ' 23:59:59']);
                            }
                            $p->where('state', 'final');
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
            ])
            ->defaultSort('plan_etd', 'asc');
    }

    public static function getRelations(): array
    {
        return [PlansRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVoyages::route('/'),
            'view'  => ViewVoyage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit($record): bool
    {
        return false;
    }
    public static function canDelete($record): bool
    {
        return false;
    }
}
