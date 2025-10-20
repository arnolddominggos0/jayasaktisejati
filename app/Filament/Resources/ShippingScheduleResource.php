<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource\Pages\CreateShippingSchedule;
use App\Filament\Resources\ShippingScheduleResource\Pages\EditShippingSchedule;
use App\Filament\Resources\ShippingScheduleResource\Pages\ListShippingSchedules;
use App\Filament\Resources\ShippingScheduleResource\Pages\PreviewShippingSchedule;
use App\Filament\Resources\ShippingScheduleResource\RelationManagers\ItemsRelationManager;
use App\Models\ShippingSchedule;
use App\Supports\ScheduleExport;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationLabel = 'Paket Jadwal Bulanan';
    protected static ?string $navigationIcon = 'heroicon-m-clipboard-document-list';
    protected static ?int $navigationSort = 9;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Judul')
                ->maxLength(150),

            TextInput::make('period_ym')
                ->label('Periode (YYYY-MM)')
                ->required()
                ->rule('regex:/^\d{4}\-\d{2}$/')
                ->helperText('Contoh: 2025-10'),

            Select::make('customer_id')
                ->relationship('customer', 'name')
                ->label('Customer')
                ->required()
                ->preload()
                ->searchable(),

            Select::make('pol_id')
                ->relationship('pol', 'name')
                ->label('POL')
                ->required()
                ->preload()
                ->searchable(),

            Select::make('pod_id')
                ->relationship('pod', 'name')
                ->label('POD')
                ->required()
                ->preload()
                ->searchable(),

            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_ym')
                    ->label('Periode')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->wrap(),

                Tables\Columns\TextColumn::make('pol.code')
                    ->label('POL')
                    ->badge(),

                Tables\Columns\TextColumn::make('pod.code')
                    ->label('POD')
                    ->badge(),

                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'final',
                    ]),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Baris'),

                Tables\Columns\TextColumn::make('finalized_at')
                    ->label('Finalized')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('Status')
                    ->options([
                        ScheduleState::Draft->value => 'Draft',
                        ScheduleState::Final->value => 'Final',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('preview_draft')
                    ->label('Preview / Print')
                    ->icon('heroicon-o-document-text')
                    ->url(fn($record) => static::getUrl('preview', ['record' => $record]))
                    ->openUrlInNewTab(),

                Action::make('export_draft_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (ShippingSchedule $record) {
                        $csv = ScheduleExport::csv($record);
                        $filename = 'draft-jadwal-' . ($record->customer?->name ?? 'customer') . '-' . $record->period_ym . '.csv';
                        return new StreamedResponse(function () use ($csv) {
                            echo $csv;
                        }, 200, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        ]);
                    }),

                Action::make('log_final_from_customer')
                    ->label('Log Final dari Customer (Email/WA)')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn($record) => $record->state === ScheduleState::Draft->value)
                    ->form([
                        TextInput::make('approved_by_name')
                            ->label('Disetujui oleh')
                            ->maxLength(120),

                        TextInput::make('line_hint')
                            ->label('Line (opsional, jika tidak ada di tabel)')
                            ->placeholder('Tanto Line / Meratus / lainnya'),

                        Textarea::make('paste_table')
                            ->label('Tempel Tabel Final (copy dari Email / Excel)')
                            ->rows(12)
                            ->required(),

                        FileUpload::make('final_attachment')
                            ->label('Lampiran Email / Excel')
                            ->disk('public')
                            ->directory('schedules/' . date('Y/m'))
                            ->visibility('public'),

                        Textarea::make('final_note')->label('Catatan Final'),
                    ])
                    ->action(function (ShippingSchedule $record, array $data) {
                        $path = null;
                        if (!empty($data['final_attachment'])) {
                            $path = is_array($data['final_attachment'])
                                ? ($data['final_attachment'][0] ?? null)
                                : $data['final_attachment'];
                        }

                        $result = $record->finalizeFromWhatsapp(
                            (string) ($data['paste_table'] ?? ''),
                            $data['final_note'] ?? null,
                            $path,
                            $data['approved_by_name'] ?? null,
                            auth()->id(),
                            lineHint: $data['line_hint'] ?? null
                        );

                        Notification::make()
                            ->title('Final jadwal dicatat: ' . $result['items'] . ' baris')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'   => ListShippingSchedules::route('/'),
            'create'  => CreateShippingSchedule::route('/create'),
            'edit'    => EditShippingSchedule::route('/{record}/edit'),
            'preview' => PreviewShippingSchedule::route('/{record}/preview'),
        ];
    }
}
