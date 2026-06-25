<?php

namespace App\Filament\FC\Resources\LoadingSessionResource\Pages;

use App\Filament\FC\Resources\LoadingSessionResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewLoadingSession extends ViewRecord
{
    protected static string $resource = LoadingSessionResource::class;

    protected static ?string $title = 'Detail Sesi Loading';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->isSuperAdmin()),
            Actions\Action::make('rack_check')
                ->label('Cek Rack Container')
                ->icon('heroicon-o-cube')
                ->color('warning')
                ->url(fn ($record) => static::getResource()::getUrl('rack-check', ['record' => $record]))
                ->visible(fn ($record) => ! $record->rack_container_check_completed),
            Actions\Action::make('equipment_check')
                ->label('Cek Alat')
                ->icon('heroicon-o-wrench')
                ->color('warning')
                ->url(fn ($record) => static::getResource()::getUrl('equipment-check', ['record' => $record]))
                ->visible(fn ($record) => ! $record->equipment_check_completed),
            Actions\Action::make('unit_check')
                ->label('Cek Unit')
                ->icon('heroicon-o-truck')
                ->color('warning')
                ->url(fn ($record) => static::getResource()::getUrl('unit-check', ['record' => $record]))
                ->visible(fn ($record) => ! $record->unit_check_completed),
            Actions\Action::make('final_decision')
                ->label('Keputusan Final')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(fn ($record) => static::getResource()::getUrl('final-decision', ['record' => $record]))
                ->visible(fn ($record) => ! $record->final_decision_completed),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Sesi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode Sesi'),
                        TextEntry::make('operation_type')
                            ->label('Jenis Operasi')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state) => $state->color()),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state) => $state->color()),
                        TextEntry::make('depot.name')
                            ->label('Depot'),
                        TextEntry::make('coordinator.name')
                            ->label('Koordinator'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i'),
                    ]),

                Section::make('Progres Pemeriksaan')
                    ->columns(3)
                    ->schema([
                        IconEntry::make('mp_attendance_completed')
                            ->label('Kehadiran MP')
                            ->boolean(),
                        IconEntry::make('health_check_completed')
                            ->label('Cek Kesehatan')
                            ->boolean(),
                        IconEntry::make('apd_check_completed')
                            ->label('Cek APD')
                            ->boolean(),
                        IconEntry::make('equipment_check_completed')
                            ->label('Cek Alat')
                            ->boolean(),
                        IconEntry::make('rack_container_check_completed')
                            ->label('Cek Rack Container')
                            ->boolean(),
                        IconEntry::make('unit_check_completed')
                            ->label('Cek Unit')
                            ->boolean(),
                        IconEntry::make('stock_apd_check_completed')
                            ->label('Cek Stok APD')
                            ->boolean(),
                        IconEntry::make('manpower_availability_completed')
                            ->label('Ketersediaan MP')
                            ->boolean(),
                        IconEntry::make('final_decision_completed')
                            ->label('Keputusan Final')
                            ->boolean(),
                    ]),

                Section::make('Ringkasan Manpower')
                    ->columns(3)
                    ->visible(fn ($record) => $record->mp_required > 0)
                    ->schema([
                        TextEntry::make('mp_required')
                            ->label('MP Dibutuhkan'),
                        TextEntry::make('mp_present')
                            ->label('MP Hadir'),
                        TextEntry::make('mp_absent')
                            ->label('MP Tidak Hadir'),
                        TextEntry::make('mp_sick')
                            ->label('MP Sakit'),
                        TextEntry::make('mp_fit_count')
                            ->label('MP Fit'),
                        TextEntry::make('mp_unfit_count')
                            ->label('MP Tidak Fit'),
                    ]),

                Section::make('Status Keselamatan')
                    ->columns(3)
                    ->schema([
                        IconEntry::make('rack_pillars_ok')
                            ->label('Pilar Rack')
                            ->boolean(),
                        IconEntry::make('drop_floor_ok')
                            ->label('Drop Floor')
                            ->boolean(),
                        IconEntry::make('container_structure_ok')
                            ->label('Struktur Container')
                            ->boolean(),
                        IconEntry::make('equipment_safe')
                            ->label('Peralatan')
                            ->boolean(),
                        IconEntry::make('apd_complete')
                            ->label('APD Lengkap')
                            ->boolean(),
                        IconEntry::make('unit_measurements_ok')
                            ->label('Ukuran Unit')
                            ->boolean(),
                    ]),

                Section::make('Keputusan Final')
                    ->visible(fn ($record) => $record->final_decision_status !== null)
                    ->schema([
                        TextEntry::make('final_decision_status')
                            ->label('Status Keputusan')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state) => $state->color()),
                        TextEntry::make('final_decision_by.name')
                            ->label('Diputuskan Oleh'),
                        TextEntry::make('final_decision_at')
                            ->label('Waktu Keputusan')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('final_decision_notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        TextEntry::make('general_notes')
                            ->label('Catatan Umum')
                            ->placeholder('Tidak ada catatan')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
