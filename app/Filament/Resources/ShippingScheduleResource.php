<?php

namespace App\Filament\Resources;

use App\Models\ShippingSchedule;
use Filament\Resources\Resource;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [];
    }
}
