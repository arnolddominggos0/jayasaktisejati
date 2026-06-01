<?php

namespace App\Filament\Resources\VoyageResource\Widgets;

use App\Enums\VoyageRegistryStatus;
use App\Models\Voyage;
use Filament\Widgets\Widget;

class VoyageRegistrySummaryStrip extends Widget
{
    protected static string $view = 'filament.resources.voyage-resource.widgets.voyage-registry-summary-strip';

    protected int | string | array $columnSpan = 'full';

    public function getCounts(): array
    {
        return [
            'active'   => Voyage::where('registry_status', VoyageRegistryStatus::ACTIVE->value)->count(),
            'delayed'  => Voyage::where('registry_status', VoyageRegistryStatus::DELAYED->value)->count(),
            'closed'   => Voyage::where('registry_status', VoyageRegistryStatus::CLOSED->value)->count(),
            'archived' => Voyage::where('registry_status', VoyageRegistryStatus::ARCHIVED->value)->count(),
        ];
    }
}
