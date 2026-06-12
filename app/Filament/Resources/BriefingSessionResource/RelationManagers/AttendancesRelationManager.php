<?php

namespace App\Filament\Resources\BriefingSessionResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Admin panel — Daftar kehadiran MP (READ-ONLY).
 *
 * Admin hanya melihat, tidak bisa create / edit / delete.
 * final_mp_status = accessor (bukan DB column) — gunakan ->state() closure.
 */
class AttendancesRelationManager extends RelationManager
{
    protected static string  $relationship        = 'attendances';
    protected static ?string $title               = 'Daftar Kehadiran MP';
    protected static ?string $recordTitleAttribute = 'id';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'asc')
            ->columns([
                TextColumn::make('manpower.name')
                    ->label('Nama MP')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('final_mp_status')
                    ->label('Status Final')
                    ->state(fn ($record) => $record->final_mp_status)
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Siap Kerja'               => 'success',
                        'Perlu Pemeriksaan Ulang',
                        'Istirahat 30 Menit',
                        'APD Tidak Lengkap'        => 'warning',
                        'Tidak Fit'                => 'danger',
                        default                    => 'gray',
                    }),

                TextColumn::make('attendance_status')
                    ->label('Kehadiran')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((string) $state) {
                        'present' => 'Hadir',
                        'absent'  => 'Tidak Hadir',
                        'sick'    => 'Sakit',
                        default   => ucfirst((string) $state),
                    })
                    ->color(fn ($state) => match ((string) $state) {
                        'present' => 'success',
                        'sick'    => 'warning',
                        default   => 'danger',
                    }),

                TextColumn::make('temperature')
                    ->label('Suhu')
                    ->state(fn ($record) => $record->temperature
                        ? number_format((float) $record->temperature, 1) . '°C'
                        : '—')
                    ->color(fn ($record) => (
                        $record->temperature &&
                        ($record->temperature < 36.5 || $record->temperature > 37.6)
                    ) ? 'danger' : null),

                TextColumn::make('bp')
                    ->label('Tensi')
                    ->state(fn ($record) => ($record->bp_systolic && $record->bp_diastolic)
                        ? "{$record->bp_systolic}/{$record->bp_diastolic} mmHg"
                        : '—'),

                TextColumn::make('has_ppe')
                    ->label('APD')
                    ->state(fn ($record) => $record->has_ppe ? 'Lengkap' : 'Tidak Lengkap')
                    ->badge()
                    ->color(fn ($record) => $record->has_ppe ? 'success' : 'warning'),
            ])
            ->filters([
                Filter::make('hadir')
                    ->label('Hadir saja')
                    ->query(fn (EloquentBuilder $q) => $q->where('attendance_status', 'present')),

                Filter::make('tidak_hadir')
                    ->label('Tidak hadir')
                    ->query(fn (EloquentBuilder $q) => $q->where('attendance_status', '!=', 'present')),
            ])
            // Read-only — tidak ada create / edit / delete
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
