<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ScheduleGanttPlaceholder extends Widget
{
    protected static string $view = 'filament.widgets.schedule-gantt-placeholder';
    protected int|string|array $columnSpan = 'full';

    public array $bars = [
        ['label' => 'TTSA 151', 'start' => 4,  'end' => 12, 'title' => 'TTSA 151 • ETD 04 → ETA 12'],
        ['label' => 'MRMA 182', 'start' => 10, 'end' => 19, 'title' => 'MRMA 182 • ETD 10 → ETA 19'],
        ['label' => 'TTJ 301',  'start' => 17, 'end' => 22, 'title' => 'TTJ 301 • ETD 17 → ETA 22'],
    ];
}
