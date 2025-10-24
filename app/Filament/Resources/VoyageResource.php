<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoyageResource\Pages\ListVoyages;
use App\Filament\Resources\VoyageResource\Pages\ViewVoyage;
use App\Filament\Resources\VoyageResource\Pages\CreateVoyage;
use App\Filament\Resources\VoyageResource\Pages\EditVoyage;
use App\Filament\Resources\VoyageResource\RelationManagers\PlansRelationManager;
use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\Vessel;
use App\Models\Voyage;
use App\Models\VoyagePlan;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Data Pelayaran';
    protected static ?string $pluralLabel     = 'Data Pelayaran';
    protected static ?string $modelLabel      = 'Data Pelayaran';
    protected static ?string $navigationIcon  = 'heroicon-m-calendar-days';
    protected static ?int    $navigationSort  = 9;

    public static function getSlug(): string
    {
        return 'data-pelayaran';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data Voyage')
                ->schema([
                    Forms\Components\Select::make('shipping_line_id')->label('Shipping Line')->relationship('shippingLine', 'name')->preload()->searchable()->required(),
                    Forms\Components\Select::make('vessel_id')->label('Kapal')->relationship('vessel', 'name')->preload()->searchable()->required(),
                    Forms\Components\Select::make('port_from_id')->label('POL')->relationship('portFrom', 'name')->preload()->searchable()->required(),
                    Forms\Components\Select::make('port_to_id')->label('POD')->relationship('portTo', 'name')->preload()->searchable()->required(),
                    Forms\Components\TextInput::make('voyage_no')->label('Voyage No')->maxLength(50)->required(),
                    Forms\Components\TextInput::make('service')->label('Service')->maxLength(50)->nullable(),
                ])->columns(2),

            Section::make('Final Plan')
                ->schema([
                    DateTimePicker::make('plan_etd')->label('ETD')->native(false)->dehydrated(false),
                    DateTimePicker::make('plan_eta')->label('ETA')->native(false)->dehydrated(false),
                    Forms\Components\Textarea::make('plan_notes')->label('Catatan')->rows(3)->dehydrated(false),
                    Forms\Components\TextInput::make('plan_source')->label('Sumber')->default('manual')->dehydrated(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->addSelect([
                    'latest_final_etd' => \App\Models\VoyagePlan::selectRaw("(payload->>'etd')::timestamp")
                        ->whereColumn('voyage_id', 'voyages.id')
                        ->where('state', 'final')
                        ->orderByDesc('finalized_at')
                        ->limit(1),
                ])->withCount('plans');
            })
            ->columns([
                Tables\Columns\TextColumn::make('latest_final_etd')
                    ->label('ETD')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan_eta')->label('ETA')->dateTime(),
                Tables\Columns\TextColumn::make('shippingLine.name')->label('Line')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('vessel.name')->label('Kapal')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('voyage_no')->label('Voyage')->searchable(),
                Tables\Columns\TextColumn::make('portFrom.code')->label('POL')->badge(),
                Tables\Columns\TextColumn::make('portTo.code')->label('POD')->badge(),
                Tables\Columns\TextColumn::make('plans_count')->label('Jumlah Finalisasi'),
            ])
            ->filters([
                SelectFilter::make('port_to_id')->label('POD')->relationship('portTo', 'name')->preload(),
                Filter::make('etd_between')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('ETD dari'),
                        \Filament\Forms\Components\DatePicker::make('to')->label('ETD sampai'),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->defaultSort('latest_final_etd', 'asc');
    }

    public static function getRelations(): array
    {
        return [PlansRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListVoyages::route('/'),
            'create' => CreateVoyage::route('/create'),
            'edit'   => EditVoyage::route('/{record}/edit'),
            'view'   => ViewVoyage::route('/{record}'),
        ];
    }
}
