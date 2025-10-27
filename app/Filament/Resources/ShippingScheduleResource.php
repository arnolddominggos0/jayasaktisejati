<?php

namespace App\Filament\Resources;

use App\Models\ShippingSchedule;
use Filament\Resources\Resource;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Kapal (TAM)';
    protected static ?string $slug = 'shipping-schedules';

    public static function getPages(): array
    {
        return [
            'index'    => ShippingScheduleResource\Pages\ListShippingSchedules::route('/'),
            'overview' => ShippingScheduleResource\Pages\OverviewShippingSchedules::route('/overview'),
            'create'   => ShippingScheduleResource\Pages\CreateShippingSchedule::route('/create'),
            'edit'     => ShippingScheduleResource\Pages\EditShippingSchedule::route('/{record}/edit'),
        ];
    }
}
