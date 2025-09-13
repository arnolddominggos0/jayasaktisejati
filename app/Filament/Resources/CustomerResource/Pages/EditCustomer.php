<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('buat_portal_user')
                ->label('Buat Akun Portal')
                ->icon('heroicon-o-user-plus')
                ->modalHeading('Buat Akun Portal Customer')
                ->form([
                    TextInput::make('name')
                        ->label('Nama User')
                        ->required()
                        ->default(fn() => $this->record->name),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->default(fn() => $this->record->email),

                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->helperText('Berikan password ini kepada customer (bisa diubah nanti).'),
                ])
                ->action(function (array $data) {
                    $customer = $this->record;

                    $user = \App\Models\User::create([
                        'name'        => $data['name'],
                        'email'       => $data['email'],
                        'password'    => Hash::make($data['password']),
                        'customer_id' => $customer->id,
                    ]);

                    $user->syncRoles(['customer']);

                    $this->notify('success', 'Akun portal customer berhasil dibuat.');
                })
                ->visible(fn() => auth_user()?->hasAnyRole(['super_admin', 'office_admin']) ?? false)

        ];
    }
}
