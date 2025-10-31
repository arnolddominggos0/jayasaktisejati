<?php

namespace App\Filament\Resources\VoyageResource\RelationManagers;

use App\Enums\ScheduleState;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class ScheduleRelationManager extends RelationManager
{
    protected static string $relationship = 'schedule';
    protected static ?string $recordTitleAttribute = 'voyage_id';
    protected static ?string $title = 'TAM Finalisasi';

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('cargo_plan')->numeric()->required()->default(0)->label('Cargo Plan'),
            Forms\Components\Select::make('state')->options(ScheduleState::options())->required()->default(ScheduleState::Draft->value)->label('Status'),
            Forms\Components\TextInput::make('approved_by_name')->label('Disetujui oleh'),
            Forms\Components\Textarea::make('final_note')->label('Catatan Final'),
            Forms\Components\TextInput::make('final_source')->label('Sumber'),
            Forms\Components\FileUpload::make('final_attachment_path')->label('Lampiran')->directory('schedule-attachments')->preserveFilenames(),
            Forms\Components\DateTimePicker::make('finalized_at')->label('Tanggal Final'),
        ]);
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cargo_plan')->label('Cargo Plan'),
                Tables\Columns\TextColumn::make('state')->label('Status')->badge(),
                Tables\Columns\TextColumn::make('approved_by_name')->label('Disetujui oleh'),
                Tables\Columns\TextColumn::make('finalized_at')->dateTime()->label('Tanggal Final'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data) {
                        return $this->getOwnerRecord()->schedule()->create($data);
                    })
                    ->visible(fn() => $this->getOwnerRecord()->schedule()->doesntExist()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => $this->getOwnerRecord()->schedule()->exists()),
            ])
            ->paginated(false);
    }
}
