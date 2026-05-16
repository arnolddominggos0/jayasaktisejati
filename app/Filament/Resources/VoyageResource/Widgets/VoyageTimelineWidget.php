<?php

namespace App\Filament\Resources\VoyageResource\Widgets;

use App\Models\Voyage;
use Filament\Widgets\Widget;

class VoyageTimelineWidget extends Widget
{
    protected static string $view = 'filament.resources.voyage-resource.widgets.voyage-timeline-widget';

    public function getRecord(): ?Voyage
    {
        $record = $this->page?->record ?? null;

        return $record instanceof Voyage ? $record : null;
    }
}
