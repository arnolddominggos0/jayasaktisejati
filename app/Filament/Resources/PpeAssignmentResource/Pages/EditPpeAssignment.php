<?php

namespace App\Filament\Resources\PpeAssignmentResource\Pages;

use App\Filament\Resources\PpeAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPpeAssignment extends EditRecord
{
    protected static string $resource = PpeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $r = $this->record;
        if ($r->returned_at) {
            $r->item()->update([
                'status' => 'in_stock',
                'current_manpower_id' => null,
                'assigned_at' => null,
            ]);
        } else {
            $r->item()->update([
                'status' => 'assigned',
                'current_manpower_id' => $r->manpower_id,
                'assigned_at' => $r->assigned_at ?? now(),
            ]);
        }
    }
}
