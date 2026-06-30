<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\SiteSettingsResource\Pages;
use App\Models\JslSiteSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteSettingsResource extends Resource
{
    protected static ?string $model = JslSiteSettings::class;

    protected static ?string $navigationGroup = 'Website Content';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Site Settings';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Site Identity')
                ->schema([
                    Forms\Components\TextInput::make('site_name')
                        ->label('Site Name (ID)')
                        ->required(),
                    Forms\Components\TextInput::make('tagline')
                        ->label('Tagline (ID)'),
                    Forms\Components\TextInput::make('site_name_en')
                        ->label('Site Name (EN)'),
                    Forms\Components\TextInput::make('tagline_en')
                        ->label('Tagline (EN)'),
                    Forms\Components\Textarea::make('footer_text')
                        ->label('Footer Text (ID)')
                        ->rows(2),
                    Forms\Components\Textarea::make('footer_text_en')
                        ->label('Footer Text (EN)')
                        ->rows(2),
                ])
                ->columns(2),

            Forms\Components\Section::make('Contact Information')
                ->schema([
                    Forms\Components\Textarea::make('contact_address')
                        ->label('Address')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('contact_phone_display')
                        ->label('Phone (Display)'),
                    Forms\Components\TextInput::make('contact_email_display')
                        ->label('Email (Display)'),
                    Forms\Components\TextInput::make('broker_whatsapp')
                        ->label('Broker WhatsApp'),
                    Forms\Components\TextInput::make('broker_email')
                        ->label('Broker Email'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Social Media')
                ->schema([
                    Forms\Components\TextInput::make('social_facebook_url')
                        ->label('Facebook URL'),
                    Forms\Components\TextInput::make('social_instagram_url')
                        ->label('Instagram URL'),
                    Forms\Components\TextInput::make('social_linkedin_url')
                        ->label('LinkedIn URL'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site_name')
                    ->label('Site Name')
                    ->searchable(),
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
            'index' => Pages\ListSiteSettings::route('/'),
            'edit' => Pages\EditSiteSettings::route('/{record}/edit'),
        ];
    }
}
