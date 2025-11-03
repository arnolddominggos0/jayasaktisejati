<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource;
use Filament\Resources\Pages\EditRecord;

class EditShippingSchedule extends EditRecord
{
    protected static string $resource = ShippingScheduleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $next = $data['state'] ?? $record->state?->value;

        if ($next === ScheduleState::Final->value) {
            $tmp = clone $record;
            $tmp->fill($data);
            if (!$tmp->canFinalize()) throw new \Exception('Tidak bisa final: ETD/ETA wajib dan Cargo Plan > 0.');
            $data['finalized_at'] = $data['finalized_at'] ?? now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->getRecord()->refreshActualSailing();
    }
}
