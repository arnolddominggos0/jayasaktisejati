<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingLineResource\Pages;
use App\Filament\Resources\ShippingLineResource\RelationManagers\VesselsRelationManager;
use App\Models\ShippingLine;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class ShippingLineResource extends Resource
{
    protected static ?string $model = ShippingLine::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Shipping Line';
    protected static ?string $pluralLabel = 'Shipping Line';
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $modelLabel = 'Shipping Line';
    protected static ?int    $navigationSort  = 30;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Kode')->unique(ignoreRecord: true)->maxLength(20),
            Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(120),
            Forms\Components\TextInput::make('contact_name')->label('PIC')->maxLength(120),
            Forms\Components\TextInput::make('contact_phone')->label('Telepon')->maxLength(60),
            Forms\Components\TextInput::make('email')->email()->label('Email')->maxLength(120),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->badge()->label('Kode')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('contact_name')->label('PIC')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('contact_phone')->label('Telepon')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make()->label('Ubah'),
            \Filament\Tables\Actions\Action::make('hapus')
                ->label('Hapus')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (\App\Models\ShippingLine $record) {
                    $blocked =
                        $record->vessels()->exists()
                        || \App\Models\ShippingSchedule::where('shipping_line_id', $record->id)->exists()
                        || \App\Models\Voyage::where('shipping_line_id', $record->id)->exists();

                    if ($blocked) {
                        \Filament\Notifications\Notification::make()
                            ->title('Tidak bisa dihapus')
                            ->body('Shipping Line masih dipakai oleh Vessel/Jadwal/Voyage. Hapus atau pindahkan relasinya dulu.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $record->delete();

                    \Filament\Notifications\Notification::make()
                        ->title('Berhasil dihapus')
                        ->success()
                        ->send();
                }),
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'contact_name', 'contact_phone', 'email'];
    }

    public static function getRelations(): array
    {
        return [
            VesselsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingLines::route('/'),
            'create' => Pages\CreateShippingLine::route('/create'),
            'edit'   => Pages\EditShippingLine::route('/{record}/edit'),
        ];
    }
}
