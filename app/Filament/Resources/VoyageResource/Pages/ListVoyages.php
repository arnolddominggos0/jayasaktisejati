<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use Filament\Resources\Pages\ListRecords;

class ListVoyages extends ListRecords
{
    protected static string $resource = VoyageResource::class;

    protected function getHeaderHeading(): string
    {
        return 'Data Pelayaran';
    }

    protected function getHeaderSubheading(): ?string
    {
        return 'Daftar pelayaran yang sudah terkonfirmasi (final).';
    }
}
