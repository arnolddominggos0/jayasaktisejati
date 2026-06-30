<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\InquiryResource\Pages;
use App\Models\JslInquiry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = JslInquiry::class;

    protected static ?string $navigationGroup = 'Inquiries';
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Inquiries';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Inquiry Details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Name')
                        ->disabled(),
                    Forms\Components\TextInput::make('company')
                        ->label('Company')
                        ->disabled(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->disabled(),
                    Forms\Components\TextInput::make('phone')
                        ->label('Phone')
                        ->disabled(),
                    Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->rows(5)
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\Select::make('vessel_listing_id')
                        ->label('Vessel Listing')
                        ->relationship('vesselListing', 'public_ref_code')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'new' => 'New',
                            'contacted' => 'Contacted',
                            'closed' => 'Closed',
                            'spam' => 'Spam',
                        ])
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company')
                    ->label('Company')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vesselListing.public_ref_code')
                    ->label('Vessel Ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'contacted',
                        'success' => 'closed',
                        'danger' => 'spam',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListInquiries::route('/'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
