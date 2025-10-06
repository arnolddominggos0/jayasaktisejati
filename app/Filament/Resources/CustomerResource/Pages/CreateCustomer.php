<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['code'])) {
            $data['code'] = $this->generateCustomerCode();
        }

        if (($data['pic_same'] ?? true)) {
            $data['pic_name']  = $data['pic_name']  ?: ($data['name']  ?? null);
            $data['pic_phone'] = $data['pic_phone'] ?: ($data['phone'] ?? null);
            $data['pic_email'] = $data['pic_email'] ?: ($data['email'] ?? null);
        }

        unset($data['pic_same']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['pic_same'] ?? false)) {
            $data['pic_name']  = $data['pic_name']  ?: ($data['name']  ?? null);
            $data['pic_phone'] = $data['pic_phone'] ?: ($data['phone'] ?? null);
            $data['pic_email'] = $data['pic_email'] ?: ($data['email'] ?? null);
        }

        unset($data['pic_same']);

        return $data;
    }

    private function generateCustomerCode(): string
    {
        $prefix = config('codes.customer.prefix', 'CTM');
        $pad    = (int) config('codes.customer.pad', 4);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $base = (int) (Customer::count() + 1 + $attempt);
            $code = sprintf('%s-%s', strtoupper($prefix), str_pad((string) $base, $pad, '0', STR_PAD_LEFT));
            if (! Customer::where('code', $code)->exists()) {
                return $code;
            }
        }

        return strtoupper($prefix) . '-' . strtoupper(Str::random(6));
    }
}
