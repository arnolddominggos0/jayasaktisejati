<?php

namespace App\Filament\Resources\BriefingSessionResource\RelationManagers;

use App\Enums\ChecklistStatus;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class ChecklistsRelationManager extends RelationManager
{
    protected static string $relationship = 'checklists';
    protected static ?string $title = 'Checklist';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('item')->label('Item')->placeholder('kehadiran / kesehatan / apd')->required(),
            Forms\Components\Select::make('status')->label('Status')
                ->options(collect(ChecklistStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required(),
            Forms\Components\TextInput::make('remark')->label('Catatan'),
        ])->columns(2);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table->columns([
            TextColumn::make('item')->label('Item')->badge(),
            TextColumn::make('status')->badge()->label('Status')
                ->color(fn($state) => $state instanceof ChecklistStatus ? $state->color() : 'gray')
                ->state(fn($state) => $state instanceof ChecklistStatus ? $state->label() : (string) $state),
            TextColumn::make('remark')->label('Catatan')->limit(30),
        ])->headerActions([
           CreateAction::make()->label('Tambah'),
           Action::make('isiDefault')->label('Isi Default')
                ->action(function ($record) {
                    foreach (['kehadiran', 'kesehatan', 'apd'] as $d) {
                        $record->checklists()->firstOrCreate(['item' => $d], ['status' => 'ok']);
                    }
                }),
        ])->actions([
           EditAction::make()->label('Ubah'),
           DeleteAction::make()->label('Hapus'),
        ]);
    }
}
