<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Forms\Components\TextInput;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('buat_portal_user')
                ->label('Buat Akun Portal')
                ->icon('heroicon-o-user-plus')
                ->visible(fn() => config('portal.auth_mode') === 'customer_monolith'
                    && (auth_user()?->isSuperAdmin() ?? false))
                ->form([
                    TextInput::make('name')->label('Nama User')->required()->default(fn() => $this->record->name),
                    TextInput::make('email')->label('Email')->email()->required()->default(fn() => $this->record->email),
                    TextInput::make('password')->label('Password')->password()->revealable()->required()->rule('min:8'),
                ])
                ->action(function (array $data) {
                    if (\App\Models\User::where('email', $data['email'])->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['email' => 'Email sudah digunakan.']);
                    }
                    $u = \App\Models\User::create([
                        'name'        => $data['name'],
                        'email'       => $data['email'],
                        'password'    => \Illuminate\Support\Facades\Hash::make($data['password']),
                        'customer_id' => $this->record->id,
                    ]);
                    $u->syncRoles(['customer']);
                    $this->notify('success', 'Akun portal customer berhasil dibuat.');
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['pic_same'] ?? false) === true) {
            $data['pic_name']  = $data['name']  ?? null;
            $data['pic_email'] = $data['email'] ?? null;
            $data['pic_phone'] = $data['phone'] ?? null;
        }

        unset($data['pic_same']);

        return $data;
    }
}
