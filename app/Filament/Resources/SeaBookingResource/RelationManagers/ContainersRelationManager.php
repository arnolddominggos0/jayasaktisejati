<?php

namespace App\Filament\Resources\SeaBookingResource\RelationManagers;

use App\Enums\ContainerSize;
use App\Enums\ContainerStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;

class ContainersRelationManager extends RelationManager
{
    protected static string $relationship = 'containers';
    protected static ?string $title = 'Containers';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('size_type')->label('Size/Type')
                ->options(collect(ContainerSize::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required(),
            TextInput::make('container_no')->label('Container No'),
            TextInput::make('seal_no')->label('Seal No'),
            Select::make('status')->label('Status')
                ->options(collect(ContainerStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required(),
            TextInput::make('gross_weight')->numeric()->label('Gross (kg)'),
        ])->columns(2);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table->columns([
            TextColumn::make('size_type')->badge()->label('Size/Type')
                ->state(fn($record) => $record->size_type?->label() ?? (string)$record->size_type),
            TextColumn::make('container_no')->label('Container'),
            TextColumn::make('seal_no')->label('Seal'),
            TextColumn::make('status')->badge()->label('Status')
                ->state(fn($record) => $record->status?->label() ?? (string)$record->status)
                ->color(fn($record) => $record->status?->color() ?? 'gray'),
            TextColumn::make('gross_weight')->label('Gross (kg)')->numeric(),
        ])->headerActions([
            CreateAction::make()->label('Tambah'),
        ])->actions([
            EditAction::make()->label('Ubah'),
            DeleteAction::make()->label('Hapus'),
        ]);
    }
}
