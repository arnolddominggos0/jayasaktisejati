<?php

namespace App\Filament\FC\Pages\Dashboard;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-m-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $slug = '/';
    protected static ?string $navigationGroup = 'Tugas Lapangan';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Dashboard';
    protected static ?string $breadcrumb = 'Dashboard';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'Dashboard';
    protected static ?string $pluralModelLabel = 'Dashboard';
}
