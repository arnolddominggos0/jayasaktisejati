<?php

namespace App\Filament\Resources\BriefingSessionResource\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Models\Manpower;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';
    protected static ?string $title = 'Absensi MP';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('manpower_id')->label('Manpower')
                ->options(Manpower::query()->where('active', true)->orderBy('name')->pluck('name', 'id'))
                ->searchable()->required(),
            Forms\Components\Select::make('attendance_status')->label('Status')
                ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required(),
            Forms\Components\TextInput::make('temperature')->numeric()->label('Suhu (°C)'),
            Forms\Components\TextInput::make('bp')->label('Tekanan Darah'),
            Forms\Components\Toggle::make('has_ppe')->label('APD Lengkap'),
            Forms\Components\TextInput::make('remark')->label('Catatan'),
        ])->columns(2);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('manpower.name')->label('Nama'),
            \Filament\Tables\Columns\TextColumn::make('manpower.depot.name')->label('Depot'),
            \Filament\Tables\Columns\TextColumn::make('attendance_status')->badge()->label('Status')
                ->color(fn($state) => $state instanceof AttendanceStatus ? $state->color() : 'gray')
                ->state(fn($state) => $state instanceof AttendanceStatus ? $state->label() : (string) $state),
            \Filament\Tables\Columns\IconColumn::make('has_ppe')->label('APD')->boolean(),
            \Filament\Tables\Columns\TextColumn::make('temperature')->label('Suhu'),
        ])->headerActions([
            \Filament\Tables\Actions\CreateAction::make()->label('Tambah'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make()->label('Ubah'),
            \Filament\Tables\Actions\DeleteAction::make()->label('Hapus'),
        ]);
    }
}
