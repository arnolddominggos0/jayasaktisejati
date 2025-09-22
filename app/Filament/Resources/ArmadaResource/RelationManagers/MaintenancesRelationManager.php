<?php

namespace App\Filament\Resources\ArmadaResource\RelationManagers;

use App\Models\ArmadaMaintenance;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';
    protected static ?string $title = 'Perawatan';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Textarea::make('reason')->label('Alasan/Deskripsi')->required()->rows(3),

            DateTimePicker::make('started_at')
                ->label('Mulai')
                ->seconds(false)
                ->native(false)
                ->default(now()),

            DateTimePicker::make('closed_at')
                ->label('Selesai')
                ->seconds(false)
                ->native(false)
                ->helperText('Kosongkan kalau masih berjalan'),

            TextInput::make('odometer')->numeric()->label('Odometer (km)'),

            Textarea::make('note')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reason')
                    ->label('Alasan')
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i'),

                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ArmadaMaintenance $m) => $m->closed_at ? 'Selesai' : 'Berjalan')
                    ->color(fn (ArmadaMaintenance $m) => $m->closed_at ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('odometer')
                    ->label('Odometer')
                    ->numeric(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buka Tiket'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),

                Action::make('close')
                    ->label('Tutup Tiket')
                    ->visible(fn (ArmadaMaintenance $row) => is_null($row->closed_at))
                    ->requiresConfirmation()
                    ->action(function (ArmadaMaintenance $row) {
                        try {
                            $row->update(['closed_at' => now()]);
                            Notification::make()->success()->title('Tiket perawatan ditutup')->send();
                        } catch (\Throwable $e) {
                            Notification::make()->danger()->title('Gagal menutup tiket')->body($e->getMessage())->send();
                            throw new Halt();
                        }
                    }),

                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->defaultSort('started_at', 'desc');
    }
}
