<?php

namespace App\Filament\Resources\ArmadaResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';
    protected static ?string $title = 'Perawatan';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->label('Judul')->required(),
            Forms\Components\DatePicker::make('planned_at')->label('Rencana'),
            Forms\Components\DatePicker::make('done_at')->label('Selesai'),
            Forms\Components\TextInput::make('odometer')->numeric()->label('Odometer'),
            Forms\Components\TextInput::make('cost')->numeric()->label('Biaya'),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Judul'),
            Tables\Columns\TextColumn::make('planned_at')->date()->label('Rencana'),
            Tables\Columns\TextColumn::make('done_at')->date()->label('Selesai'),
            Tables\Columns\TextColumn::make('odometer')->numeric()->label('Odometer'),
            Tables\Columns\TextColumn::make('cost')->money('IDR')->label('Biaya'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->label('Tambah'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
            Tables\Actions\DeleteAction::make()->label('Hapus'),
        ]);
    }
}
