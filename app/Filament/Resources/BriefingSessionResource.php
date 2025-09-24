<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BriefingSessionResource\Pages;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rule;

class BriefingSessionResource extends Resource
{
    protected static ?string $model = BriefingSession::class;

    protected static ?string $navigationGroup = 'Manajemen MP';
    protected static ?string $navigationLabel = 'Sesi Briefing';
    protected static ?string $pluralLabel     = 'Sesi Briefing';
    protected static ?string $modelLabel      = 'Sesi Briefing';
    protected static ?string $navigationIcon  = 'heroicon-m-clipboard-document-check';
    protected static ?int    $navigationSort  = 20;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')
                ->label('Tanggal')
                ->required()
                ->rule(function (Get $get, ?\App\Models\BriefingSession $record) {
                    if (! $record?->id) return null;
                    return Rule::unique('briefing_sessions', 'date')
                        ->where(fn($q) => $q->where('depot_id', (int) $get('depot_id')))
                        ->ignore($record->id);
                }),

            Forms\Components\Select::make('depot_id')
                ->relationship('depot', 'name')
                ->label('Depot')
                ->required()
                ->reactive(),
            Forms\Components\Select::make('coordinator_user_id')
                ->label('Koordinator (PIC)')
                ->options(function (Get $get) {
                    $depotId = $get('depot_id');
                    $branchId = $depotId ? Depot::query()->whereKey($depotId)->value('branch_id') : null;

                    return User::role('field_coordinator')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->orderBy('name')->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->required()
                ->rule(function (Get $get) {
                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                        if (! $value) return;
                        $depotId  = (int) $get('depot_id');
                        $branchId = $depotId ? Depot::query()->whereKey($depotId)->value('branch_id') : null;

                        $ok = User::role('field_coordinator')
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                            ->whereKey($value)
                            ->exists();
                        if (! $ok) $fail('PIC harus koordinator lapangan di cabang yang sama.');
                    };
                }),
            Forms\Components\TextInput::make('summary_headcount')->label('Jumlah MP')->numeric(),
            Forms\Components\Toggle::make('summary_sufficient')->label('Cukup'),
            Forms\Components\Textarea::make('summary_solution')->label('Solusi/Keterangan')->columnSpanFull(),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('date')->date()->label('Tanggal'),
            TextColumn::make('depot.name')->label('Depot'),
            TextColumn::make('coordinator.name')->label('Koordinator'),
            TextColumn::make('attendances_count')->counts('attendances')->label('Absensi (total)'),
            TextColumn::make('present_attendances_count')
                ->counts('presentAttendances')
                ->label('Hadir')
        ])->actions([
            Tables\Actions\Action::make('kelolaAbsensi')->label('Kelola Absensi')
                ->icon('heroicon-m-clipboard-document-list')
                ->url(fn($record) => route('filament.admin.resources.briefing-attendances.index', ['session_id' => $record->id])),
            Tables\Actions\EditAction::make()->label('Ubah'),
            Tables\Actions\DeleteAction::make()->label('Hapus'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBriefingSessions::route('/'),
            'create' => Pages\CreateBriefingSession::route('/create'),
            'edit'   => Pages\EditBriefingSession::route('/{record}/edit'),
        ];
    }
}
