<?php

namespace App\Filament\Resources;

use App\Enums\VesselPlanStatus;
use App\Filament\Resources\VesselPlanResource\Pages;
use App\Filament\Resources\VesselPlanResource\RelationManagers\VesselPlanItemRelationManager;
use App\Models\VesselPlan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class VesselPlanResource extends Resource
{
    protected static ?string $model = VesselPlan::class;

    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Vessel Plan';


    public static function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('period_month')
                ->label('Periode')
                ->displayFormat('F Y')
                ->required()
                ->native(false)
                ->rules([
                    function (Get $get, ?\App\Models\VesselPlan $record) {
                        return function (string $attribute, $value, $fail) use ($get, $record) {
                            $exists = \App\Models\VesselPlan::query()
                                ->where('period_month', \Carbon\Carbon::parse($value)->startOfMonth())
                                ->where('pol_id', $get('pol_id'))
                                ->where('pod_id', $get('pod_id'))
                                ->when(
                                    $record,
                                    fn($q) => $q->where('id', '!=', $record->id)
                                )
                                ->exists();

                            if ($exists) {
                                $fail('Vessel plan untuk periode dan rute ini sudah ada.');
                            }
                        };
                    },
                ])
                ->helperText('Pilih bulan operasional vessel plan')
                ->disabled(fn($record) => $record?->isFinal()),

            Select::make('pol_id')
                ->label('Port Asal (POL)')
                ->relationship('pol', 'code')
                ->required()
                ->live()
                ->disabled(fn($record) => $record?->isFinal()),

            Select::make('pod_id')
                ->label('Port Tujuan (POD)')
                ->relationship('pod', 'code')
                ->required()
                ->disabled(fn(Get $get) => blank($get('pol_id')))
                ->different('pol_id')
                ->helperText('Port tujuan harus berbeda dari port asal')
                ->disabled(fn($record) => $record?->isFinal()),

            Hidden::make('status')
                ->default(VesselPlanStatus::Draft),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('M Y')
                    ->sortable(),

                TextColumn::make('route_code')
                    ->label('Rute'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),

                TextColumn::make('max_gap')
                    ->label('Max ETD Gap')
                    ->getStateUsing(fn($record) => $record->maxEtdGap() . ' hari')
                    ->color(fn($record) => $record->maxEtdGap() > 6 ? 'danger' : 'success'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListVesselPlans::route('/'),
            'edit'  => Pages\EditVesselPlan::route('/{record}/edit'),
        ];
    }
}
