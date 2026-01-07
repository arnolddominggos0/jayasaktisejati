<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use Filament\Resources\Pages\Page;

class ListShippingSchedules extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        abort(404);
    }
}
