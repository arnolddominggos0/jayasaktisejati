<?php

namespace App\Filament\Resources;

use App\Enums\MPCheckStatus;
use App\Filament\Resources\BriefingSessionResource\Pages;
use App\Filament\Resources\BriefingSessionResource\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\BriefingSessionResource\RelationManagers\StockApdChecksRelationManager;
use App\Models\BriefingSession;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Admin panel — Briefing Session resource.
 *
 * Hak akses Super Admin: View All, Review, Approve, Audit.
 * FC YANG MEMBUAT SESSION — admin hanya melihat & menyetujui.
 *
 * Prinsip:
 *   - canCreate = false  (FC yang input, bukan admin)
 *   - getPages = [index, view]  (tidak ada create / edit)
 *   - Relation managers: read-only (Attendances + StockApdChecks)
 */
class BriefingSessionResource extends Resource
{
    protected static ?string $model = BriefingSession::class;

    protected static ?string $navigationGroup = 'Operasional';
    protected static ?string $navigationLabel = 'Briefing Session';
    protected static ?string $pluralLabel     = 'Briefing Session';
    protected static ?string $modelLabel      = 'Sesi Briefing';
    protected static ?string $navigationIcon  = 'heroicon-m-clipboard-document-check';
    protected static ?int    $navigationSort  = 1;

    // ── Permissions ──────────────────────────────────────────────────────────

    public static function shouldRegisterNavigation(): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn (): EloquentBuilder => static::getEloquentQuery())
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('depot.name')
                    ->label('Depot / Lokasi')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('coordinator.name')
                    ->label('Field Coordinator')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('attendances_count')
                    ->label('Total Peserta')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('actual_unit_handover')
                    ->label('Actual Unit Handover')
                    ->alignCenter()
                    ->suffix(' unit')
                    ->getStateUsing(fn ($record) => $record->actual_unit_masuk_yard)
                    ->sortable(false),

                TextColumn::make('mp_check_status')
                    ->label('Status MP Check')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof MPCheckStatus) {
                            return $state->label();
                        }

                        return MPCheckStatus::tryFrom((string) $state)?->label()
                            ?? (string) ($state ?? 'Draft');
                    })
                    ->color(function ($state): string {
                        $enum = $state instanceof MPCheckStatus
                            ? $state
                            : MPCheckStatus::tryFrom((string) $state);

                        return match ($enum?->value) {
                            'cleared'                  => 'success',
                            'on_check'                 => 'warning',
                            'waiting_action', 'failed' => 'danger',
                            default                    => 'gray',
                        };
                    })
                    ->sortable(),

                TextColumn::make('approved_at')
                    ->label('Persetujuan')
                    ->badge()
                    ->state(fn ($record) => $record->approved_at
                        ? \Carbon\Carbon::parse($record->approved_at)->format('d M Y')
                        : 'Belum Ditinjau')
                    ->color(fn ($record) => $record->approved_at ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('belum_approve')
                    ->label('Belum Ditinjau')
                    ->query(fn (EloquentBuilder $q) => $q->whereNull('approved_at')),

                Filter::make('sudah_approve')
                    ->label('Sudah Disetujui')
                    ->query(fn (EloquentBuilder $q) => $q->whereNotNull('approved_at')),

                Filter::make('rentang')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (EloquentBuilder $query, array $data): void {
                        if ($data['from'] ?? null) {
                            $query->whereDate('date', '>=', $data['from']);
                        }
                        if ($data['until'] ?? null) {
                            $query->whereDate('date', '<=', $data['until']);
                        }
                    }),

                SelectFilter::make('depot_id')
                    ->label('Depot')
                    ->relationship('depot', 'name'),

                SelectFilter::make('mp_check_status')
                    ->label('Status MP Check')
                    ->options(
                        collect(MPCheckStatus::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
            ])
            ->bulkActions([]);
    }

    // ── Query ─────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): EloquentBuilder
    {
        $query = static::getModel()::query()
            ->with(['depot:id,name', 'coordinator:id,name'])
            ->withCount(['attendances', 'shipments']);

        $branchId = app()->bound('currentBranchId') ? app('currentBranchId') : null;
        if ($branchId) {
            $query->whereHas('depot', fn ($q) => $q->where('branch_id', $branchId));
        }

        return $query;
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
            StockApdChecksRelationManager::class,
        ];
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBriefingSessions::route('/'),
            'view'  => Pages\ViewBriefingSession::route('/{record}'),
        ];
    }
}
