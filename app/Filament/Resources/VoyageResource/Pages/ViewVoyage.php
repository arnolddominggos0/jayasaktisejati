<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Voyage Registry — Audit & Lifecycle View
 *
 * Shows: Timeline, Delay Audit Trail, Milestones, Carrier Readiness snapshot.
 * NOT for operational daily monitoring — use Monitoring Kapal TAM for that.
 * NOT for SLA/lead-time analysis — use Evaluasi Voyage for that.
 */
class ViewVoyage extends ViewRecord
{
    protected static string $resource = VoyageResource::class;

    protected static string $view = 'filament.resources.voyage-resource.pages.view-voyage';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load([
            'vessel',
            'pol',
            'pod',
            'shippingLine',
            'milestones',
            'checkpoints',
            'vesselChecks',
            'delayLogs',
            'scheduleHistories',
        ]);
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}
