<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource;
use App\Models\ShippingSchedule;
use App\Models\Voyage;
use Filament\Resources\Pages\CreateRecord;

class CreateShippingSchedule extends CreateRecord
{
    protected static string $resource = ShippingScheduleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $voy = Voyage::find($data['voyage_id']);
        if ($voy) {
            $data['etd'] = $voy->etd;
            $data['eta'] = $voy->eta;
            $data['period_month'] = optional($voy->etd)?->startOfMonth();
            $data['vessel_id'] = $voy->vessel_id;
            $data['vessel_name'] = $voy->vessel?->name;
            $data['shipping_line_id'] = $voy->vessel?->shippingLine?->id;
            $data['voyage_no'] = $voy->voyage_no;
        }

        if (($data['state'] ?? null) === ScheduleState::Final->value) {
            $tmp = new ShippingSchedule($data);
            if (!$tmp->canFinalize()) throw new \Exception('Tidak bisa final: ETD/ETA wajib dan Cargo Plan > 0.');
            $data['finalized_at'] = $data['finalized_at'] ?? now();
        }

        return $data;
    }
}
