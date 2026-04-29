<?php

namespace App\Filament\Resources\BriefingSessionResource\Pages;

use App\Filament\Resources\BriefingSessionResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditBriefingSession extends EditRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openPpe')
                ->label('Kelola Stok APD')
                ->icon('heroicon-m-cube')
                ->url(route('filament.admin.resources.ppe-items.index'))
                ->color('gray'),
            Actions\DeleteAction::make()->label('Hapus Sesi'),
        ];
    }

    public function getTitle(): string
    {
        $record = $this->record;
        return "Kelola Briefing • {$record->date} • {$record->depot?->name}";
    }
}
