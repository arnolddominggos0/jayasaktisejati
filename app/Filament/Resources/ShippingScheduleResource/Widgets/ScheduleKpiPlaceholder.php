<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ScheduleKpiPlaceholder extends Widget
{
    protected static string $view = 'filament.widgets.schedule-kpi-placeholder';
    protected int|string|array $columnSpan = 'full';
}
