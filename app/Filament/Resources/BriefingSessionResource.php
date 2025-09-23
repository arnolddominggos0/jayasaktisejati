<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\ChecklistStatus;
use App\Filament\Resources\BriefingSessionResource\Pages;
use App\Models\BriefingSession;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class BriefingSessionResource extends Resource
{
    protected static ?string $model = BriefingSession::class;

    protected static ?string $navigationGroup = 'Manajemen Armada & MP';
    protected static ?string $navigationLabel = 'Sesi Briefing';
    protected static ?string $pluralLabel = 'Sesi Briefing';
    protected static ?string $navigationIcon = 'heroicon-m-clipboard-document-check';
    protected static ?string $modelLabel = 'Sesi Briefing';
    protected static ?int    $navigationSort  = 30;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->label('Tanggal')->required(),
            Forms\Components\Select::make('depot_id')->relationship('depot', 'name')->label('Depot')->required(),
            Forms\Components\Select::make('coordinator_user_id')->label('Koordinator (User)')
                ->options(User::role('field_coordinator')->orderBy('name')->pluck('name', 'id'))
                ->searchable(),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('date')->date()->label('Tanggal'),
            Tables\Columns\TextColumn::make('depot.name')->label('Depot'),
            Tables\Columns\TextColumn::make('coordinator.name')->label('Koordinator'),
            Tables\Columns\TextColumn::make('attendances_count')->counts('attendances')->label('Absensi'),
            Tables\Columns\TextColumn::make('checklists_count')->counts('checklists')->label('Checklist'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //     \App\Filament\Resources\BriefingSessionResource\RelationManagers\ChecklistsRelationManager::class,
            //     \App\Filament\Resources\BriefingSessionResource\RelationManagers\AttendancesRelationManager::class,
        ];
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
