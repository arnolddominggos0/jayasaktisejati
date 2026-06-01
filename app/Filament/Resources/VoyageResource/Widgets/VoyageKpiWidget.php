<?php

namespace App\Filament\Resources\VoyageResource\Widgets;

use App\Models\Voyage;
use Filament\Widgets\Widget;

class VoyageKpiWidget extends Widget
{
    protected static string $view = 'filament.resources.voyage-resource.widgets.voyage-kpi-widget';

    public function getRecord(): ?Voyage
    {
        $record = $this->page?->record ?? null;

        return $record instanceof Voyage ? $record : null;
    }
}
