<?php

namespace App\Filament\Customer\Pages;

use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Customer Profile Page
 * 
 * Allows customers to view and update their profile information
 */
class EditProfile extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?string $title = 'Profil Customer';

    protected static ?string $navigationGroup = 'Akun';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.customer.pages.edit-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $customer = $user?->customer;

        if ($customer) {
            $this->form->fill($customer->toArray());
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Perusahaan')
                    ->description('Data perusahaan tidak dapat diubah. Hubungi admin untuk perubahan.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Customer')
                            ->disabled(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->disabled(),

                        Forms\Components\TextInput::make('npwp')
                            ->label('NPWP')
                            ->disabled()
                            ->visible(fn (callable $get) => $get('type') === 'Company'),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK')
                            ->disabled()
                            ->visible(fn (callable $get) => $get('type') === 'Individual'),

                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'Company' => 'Perusahaan',
                                'Individual' => 'Perorangan',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Kontak & Alamat')
                    ->description('Informasi yang dapat diupdate.')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->required(),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3),

                        Forms\Components\TextInput::make('postal_code')
                            ->label('Kode Pos'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('PIC (Person In Charge)')
                    ->description('Informasi penanggung jawab.')
                    ->schema([
                        Forms\Components\TextInput::make('pic_name')
                            ->label('Nama PIC')
                            ->required(),

                        Forms\Components\TextInput::make('pic_phone')
                            ->label('Telepon PIC')
                            ->tel()
                            ->required(),

                        Forms\Components\TextInput::make('pic_email')
                            ->label('Email PIC')
                            ->email(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = Auth::user();
        $customer = $user?->customer;

        if (!$customer) {
            Notification::make()
                ->title('Error')
                ->body('Data customer tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();

        // Only update allowed fields
        $allowedFields = [
            'email',
            'phone',
            'address',
            'postal_code',
            'pic_name',
            'pic_phone',
            'pic_email',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        $customer->update($updateData);

        Notification::make()
            ->title('Berhasil')
            ->body('Profil berhasil diperbarui.')
            ->success()
            ->send();
    }
}
