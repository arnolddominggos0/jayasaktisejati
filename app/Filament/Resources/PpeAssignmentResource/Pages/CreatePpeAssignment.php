<?php

namespace App\Filament\Resources\PpeAssignmentResource\Pages;

use App\Filament\Resources\PpeAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePpeAssignment extends CreateRecord
{
    protected static string $resource = PpeAssignmentResource::class;

    protected function afterCreate(): void
    {
        $r = $this->record;
        $r->item()->update([
            'status' => 'assigned',
            'current_manpower_id' => $r->manpower_id,
            'assigned_at' => $r->assigned_at ?? now(),
        ]);
    }
}
