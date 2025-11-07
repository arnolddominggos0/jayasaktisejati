<?php

namespace App\Filament\Resources;

use App\Actions\Schedule\BuildMonthlyDraft;
use App\Actions\Schedule\FinalizeFromBatch;
use App\Filament\Resources\TamMonthlyScheduleResource\Pages;
use App\Models\TamMonthlySchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TamMonthlyScheduleResource extends Resource
{
    protected static ?string $model = TamMonthlySchedule::class;
    protected static ?string $navigationGroup = 'Operasional Kapal';
    protected static ?string $navigationLabel = 'Paket Bulanan TAM';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('period_month')
                ->label('Periode Bulan')
                ->required()
                ->native(false)
                ->displayFormat('F Y')
                ->closeOnDateSelection(),
            Forms\Components\TextInput::make('version')
                ->default('v1.0')
                ->label('Versi'),
            Forms\Components\Textarea::make('draft_message')
                ->label('Pesan Draft WA')
                ->rows(6)
                ->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($s) => match ($s) {
                        'draft' => 'warning',
                        'sent' => 'info',
                        'feedback' => 'purple',
                        'final' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_plan')
                    ->label('Total Plan')
                    ->numeric(),
                Tables\Columns\TextColumn::make('draft_path')
                    ->label('Draft')
                    ->formatStateUsing(fn($s) => $s ? 'Unduh' : '-')
                    ->url(fn($r) => $r->draft_path ? asset('storage/' . $r->draft_path) : null, true),
                Tables\Columns\TextColumn::make('final_path')
                    ->label('Final')
                    ->formatStateUsing(fn($s) => $s ? 'Unduh' : '-')
                    ->url(fn($r) => $r->final_path ? asset('storage/' . $r->final_path) : null, true),
                Tables\Columns\TextColumn::make('generated_by_name')
                    ->label('Dibuat oleh'),
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Dibuat')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('finalized_at')
                    ->label('Final')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'feedback' => 'Feedback',
                    'final' => 'Final',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('generateDraft')
                    ->label('Generate Draft')
                    ->icon('heroicon-o-document-plus')
                    ->requiresConfirmation()
                    ->action(function (TamMonthlySchedule $record) {
                        $period = $record->period_month ?? now()->startOfMonth();
                        BuildMonthlyDraft::run(
                            (int) $period->year,
                            (int) $period->month,
                            $record->version ?? 'v1.0'
                        );
                    })
                    ->visible(fn($r) => $r->isDraft()),
                Tables\Actions\Action::make('finalizeAll')
                    ->label('Finalize Semua')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn($r) => FinalizeFromBatch::run($r, auth_user()->name ?? 'System'))
                    ->visible(fn($r) => $r->isDraft() || $r->isFeedback()),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTamMonthlySchedules::route('/'),
            'create' => Pages\CreateTamMonthlySchedule::route('/create'),
            'edit' => Pages\EditTamMonthlySchedule::route('/{record}/edit'),
        ];
    }
}
