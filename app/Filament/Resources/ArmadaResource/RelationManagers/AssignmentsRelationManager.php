<?php

namespace App\Filament\Resources\ArmadaResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';
    protected static ?string $title = 'Penugasan';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->required()->label('Tanggal'),
            Forms\Components\Select::make('shipment_id')->relationship('shipment','code')->searchable()->label('Shipment'),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('date')->date()->label('Tanggal'),
            Tables\Columns\TextColumn::make('shipment.code')->label('Shipment')->badge(),
            Tables\Columns\TextColumn::make('created_at')->since()->label('Dibuat'),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->label('Tambah'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
            Tables\Actions\DeleteAction::make()->label('Hapus'),
        ]);
    }
}
