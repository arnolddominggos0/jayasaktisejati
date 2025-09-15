<?php

namespace App\Filament\Resources\SeaBookingResource\RelationManagers;

use App\Enums\ContainerSize;
use App\Enums\ContainerStatus;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;

class ContainersRelationManager extends RelationManager
{
    protected static string $relationship = 'containers';
    protected static ?string $title = 'Containers';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('size_type')->label('Size/Type')
                ->options(collect(ContainerSize::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))->required(),
            Forms\Components\TextInput::make('container_no')->label('Container No'),
            Forms\Components\TextInput::make('seal_no')->label('Seal No'),
            Forms\Components\Select::make('status')->label('Status')
                ->options(collect(ContainerStatus::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))->required(),
            Forms\Components\TextInput::make('gross_weight')->numeric()->label('Gross (kg)'),
        ])->columns(2);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table->columns([
            \Filament\Tables\Columns\TextColumn::make('size_type')->badge()->label('Size/Type')
                ->state(fn($record) => $record->size_type?->label() ?? (string)$record->size_type),
            \Filament\Tables\Columns\TextColumn::make('container_no')->label('Container'),
            \Filament\Tables\Columns\TextColumn::make('seal_no')->label('Seal'),
            \Filament\Tables\Columns\TextColumn::make('status')->badge()->label('Status')
                ->state(fn($record) => $record->status?->label() ?? (string)$record->status)
                ->color(fn($record) => $record->status?->color() ?? 'gray'),
            \Filament\Tables\Columns\TextColumn::make('gross_weight')->label('Gross (kg)')->numeric(),
        ])->headerActions([
            \Filament\Tables\Actions\CreateAction::make()->label('Tambah'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make()->label('Ubah'),
            \Filament\Tables\Actions\DeleteAction::make()->label('Hapus'),
        ]);
    }
}
