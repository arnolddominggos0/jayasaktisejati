<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\CompanyProfileResource\Pages;
use App\Models\JslCompanyProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyProfileResource extends Resource
{
    protected static ?string $model = JslCompanyProfile::class;

    protected static ?string $navigationGroup = 'Website Content';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Company Profile';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('About Us (Bahasa Indonesia)')
                ->schema([
                    Forms\Components\RichEditor::make('about')
                        ->label('About (ID)')
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('overview')
                        ->label('Overview (ID)')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('vision')
                        ->label('Vision (ID)')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('mission')
                        ->label('Mission (ID)')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('About Us (English)')
                ->schema([
                    Forms\Components\RichEditor::make('about_en')
                        ->label('About (EN)')
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('overview_en')
                        ->label('Overview (EN)')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('vision_en')
                        ->label('Vision (EN)')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('mission_en')
                        ->label('Mission (EN)')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyProfiles::route('/'),
            'edit' => Pages\EditCompanyProfile::route('/{record}/edit'),
        ];
    }
}
