<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoadingSession extends EditRecord
{
    protected static string $resource = LoadingSessionResource::class;

    protected static ?string $title = 'Edit Sesi Loading';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan berhasil disimpan';
    }
}
