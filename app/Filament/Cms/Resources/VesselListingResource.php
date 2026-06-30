<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\VesselListingResource\Pages;
use App\Models\JslVesselListing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VesselListingResource extends Resource
{
    protected static ?string $model = JslVesselListing::class;

    protected static ?string $navigationGroup = 'Vessel Listings';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Vessel Listings';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Public Listing Information')
                ->description('These fields are visible on the public website.')
                ->schema([
                    Forms\Components\TextInput::make('public_ref_code')
                        ->label('Public Reference Code')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('A non-sensitive code shown publicly (e.g. TUG-001)'),
                    Forms\Components\Select::make('vessel_type')
                        ->label('Vessel Type')
                        ->options([
                            'tugboat' => 'Tugboat',
                            'barge' => 'Barge',
                            'tanker' => 'Tanker',
                            'cargo' => 'Cargo Ship',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('year_built')
                        ->label('Year Built')
                        ->numeric()
                        ->minValue(1900)
                        ->maxValue(date('Y')),
                    Forms\Components\TextInput::make('flag_registry')
                        ->label('Flag Registry'),
                    Forms\Components\TextInput::make('gross_tonnage')
                        ->label('Gross Tonnage (GT)')
                        ->numeric()
                        ->step(0.01),
                    Forms\Components\TextInput::make('deadweight')
                        ->label('Deadweight (DWT)')
                        ->numeric()
                        ->step(0.01),
                    Forms\Components\TextInput::make('loa_length')
                        ->label('LOA Length (m)')
                        ->numeric()
                        ->step(0.01),
                    Forms\Components\TextInput::make('beam')
                        ->label('Beam (m)')
                        ->numeric()
                        ->step(0.01),
                    Forms\Components\TextInput::make('draft')
                        ->label('Draft (m)')
                        ->numeric()
                        ->step(0.01),
                    Forms\Components\TextInput::make('engine_power')
                        ->label('Engine Power'),
                    Forms\Components\TextInput::make('trading_area')
                        ->label('Trading Area'),
                    Forms\Components\RichEditor::make('marketing_description')
                        ->label('Marketing Description (ID)')
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('marketing_description_en')
                        ->label('Marketing Description (EN)')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'open' => 'Open / Available',
                            'sold' => 'Sold / Closed',
                            'withdrawn' => 'Withdrawn',
                        ])
                        ->default('open')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Sensitive / Confidential Data')
                ->description('These fields are NEVER shown on the public website. For internal use only.')
                ->schema([
                    Forms\Components\TextInput::make('real_vessel_name')
                        ->label('Real Vessel Name'),
                    Forms\Components\TextInput::make('imo_number')
                        ->label('IMO Number'),
                    Forms\Components\Textarea::make('owner_details')
                        ->label('Owner Details')
                        ->rows(3),
                    Forms\Components\Textarea::make('certificates')
                        ->label('Certificates')
                        ->rows(4),
                    Forms\Components\Textarea::make('price_commercial_terms')
                        ->label('Price / Commercial Terms')
                        ->rows(3),
                ])
                ->columns(2)
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('public_ref_code')
                    ->label('Ref Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vessel_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year_built')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('flag_registry')
                    ->label('Flag')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'open',
                        'danger' => 'sold',
                        'warning' => 'withdrawn',
                    ]),
                Tables\Columns\IconColumn::make('has_sensitive')
                    ->label('Sensitive Data')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hasSensitiveData()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselListings::route('/'),
            'create' => Pages\CreateVesselListing::route('/create'),
            'edit' => Pages\EditVesselListing::route('/{record}/edit'),
        ];
    }
}
