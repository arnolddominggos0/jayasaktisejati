<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['code'])) {
            $next = str_pad((string)(Customer::max('id') + 1), 4, '0', STR_PAD_LEFT);
            $data['code'] = "CTM-{$next}";
        }
        return $data;
    }
}
