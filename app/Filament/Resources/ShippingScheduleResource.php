<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingScheduleResource\Pages;
use App\Models\ShippingSchedule;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;

    protected static ?string $navigationGroup = 'Monitoring Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Monitoring Jadwal TAM';
    protected static ?string $modelLabel = 'Monitoring Jadwal TAM';
    protected static ?string $pluralModelLabel = 'Monitoring Jadwal TAM';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([])
            ->defaultSort('shipping_schedules.period_month', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['voyage.pol','voyage.pod','voyage.vessel.shippingLine']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingSchedules::route('/'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function canForceDelete($record): bool { return false; }
}
