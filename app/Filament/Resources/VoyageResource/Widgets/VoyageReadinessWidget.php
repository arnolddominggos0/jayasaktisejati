<?php

namespace App\Filament\Resources\VoyageResource\Widgets;

use App\Models\Voyage;
use Filament\Widgets\Widget;

class VoyageReadinessWidget extends Widget
{
    protected static string $view = 'filament.resources.voyage-resource.widgets.voyage-readiness-widget';

    public function getRecord(): ?Voyage
    {
        $record = $this->page?->record ?? null;

        if ($record instanceof Voyage) {
            return $record->load(['checkpoints', 'vesselChecks']);
        }

        return null;
    }
}
