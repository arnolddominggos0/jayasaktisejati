<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class TamDebugPage extends Page
{
    protected static string $view = 'filament.pages.tam-debug-page';
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    protected static ?string $navigationGroup = 'Operasional';
    protected static ?string $navigationLabel = 'TAM Debug';
    protected static ?int $navigationSort = 99;

    public string $period = '2026-06';

    public function updatedPeriod(): void
    {
        // intentionally empty
    }
}
