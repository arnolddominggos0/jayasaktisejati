<?php

namespace App\Filament\Resources\BriefingSessionResource\Pages;

use App\Filament\Resources\BriefingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBriefingSession extends EditRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('kelolaAbsensi')
                ->label('Kelola Absensi')
                ->icon('heroicon-m-clipboard-document-list')
                ->url(fn () => route(
                    'filament.admin.resources.briefing-attendances.index',
                    ['session_id' => $this->record->id]
                )),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }
}
