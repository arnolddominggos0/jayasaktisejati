<?php

namespace App\Filament\Resources;

use App\Models\VesselPlan;
use App\Supports\MonthParam;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Textarea;
use App\Filament\Resources\VesselPlanResource\Pages;
use App\Filament\Resources\VesselPlanResource\RelationManagers\VesselPlanItemRelationManager;

class VesselPlanResource extends Resource
{
    protected static ?string $model = VesselPlan::class;

    protected static ?string $navigationGroup = 'Manajemen Kapal';
    protected static ?string $navigationLabel = 'Perencanaan Kapal';
    protected static ?string $pluralLabel     = 'Perencanaan Kapal';
    protected static ?string $modelLabel      = 'Perencanaan Kapal';
    protected static ?int    $navigationSort  = 2;
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';

    // ── Authorization ─────────────────────────────────────────────────────────
    // VesselPlan is global (no branch). office_admin: read-only reference.
    // super_admin: full CRUD.

    public static function shouldRegisterNavigation(): bool
    {
        return auth_user()?->isOfficeUser() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth_user()?->isOfficeUser() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('F Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->color(fn($state) => $state?->color())
                    ->sortable(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Jadwal'),

                TextColumn::make('avg_sailing')
                    ->label('Avg Sailing')
                    ->getStateUsing(function ($record) {
                        if (!$record) return '-';

                        $avg = $record->analyze()['sailing_avg'] ?? 0;

                        return $avg ? $avg . ' hari' : '-';
                    }),

                TextColumn::make('max_gap')
                    ->label('Max Gap')
                    ->getStateUsing(function ($record) {
                        if (!$record) return '-';

                        $gap = $record->analyze()['max_gap'] ?? 0;

                        return $gap . ' hari';
                    })
                    ->color(function ($record) {
                        $gap = $record?->analyze()['max_gap'] ?? 0;
                        return match (true) {
                            $gap > 10 => 'danger',
                            $gap > 6  => 'warning',
                            default   => 'success',
                        };
                    }),

                TextColumn::make('status_sop')
                    ->label('Risiko Jadwal')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        $record?->sopStatus()['label'] ?? '-'
                    )
                    ->color(
                        fn($record) =>
                        $record?->sopStatus()['color'] ?? 'gray'
                    )
                    ->tooltip(fn($record) => $record?->sopStatus()['reason'] ?? null),

                TextColumn::make('feedback_reason')
                    ->label('Alasan Revisi')
                    ->limit(40)
                    ->toggleable()
                    ->visible(fn($record) => $record?->isRevision()),
            ])

            // Tahun BUKAN advanced filter — hanya ada satu, dan ia adalah
            // context halaman ("bulan-bulan tahun berapa yang sedang saya
            // lihat"), bukan penyaring tambahan. Karena itu tidak memakai
            // Filament ->filters() (yang otomatis membawa panel "Filter",
            // "Filter Aktif", dan "Reset" — furniture yang tidak perlu untuk
            // satu context selector). Dropdown Tahun + query whereYear-nya
            // sekarang hidup di ListVesselPlans (getYearOptions()/
            // getTableQuery()), dirender sebagai elemen halaman biasa
            // tepat di bawah judul — lihat list-vessel-plans.blade.php.

            // Belum ada Vessel Plan sama sekali (bukan cuma filter tahun
            // kosong) — arahkan langsung ke aksi generate untuk bulan
            // berjalan, konsisten dengan header action "Generate Vessel
            // Plan {bulan}" di ListVesselPlans.
            ->emptyStateHeading('Belum ada Vessel Plan')
            ->emptyStateDescription('Mulai buat planning untuk periode pertama.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateActions([
                Tables\Actions\Action::make('emptyGenerate')
                    ->label('Buat Vessel Plan')
                    ->icon('heroicon-o-plus')
                    ->visible(fn () => auth_user()?->isSuperAdmin() ?? false)
                    ->action(function () {
                        $month = MonthParam::resolve(request('month'));

                        VesselPlan::generateForMonth($month['start']);
                    }),
            ])

            ->actions([

                // Satu aksi navigasi utama per baris ("Buka →"), bukan
                // aksi workflow. Sebelumnya EditAction hanya tampil saat
                // isEditable() (bukan Final) — akibatnya baris Final tidak
                // punya aksi apa pun di index, padahal edit page sudah
                // mendukung membuka plan Final (read-only). Selalu tampil;
                // otorisasi tetap dijaga oleh VesselPlanResource::canEdit().
                Tables\Actions\EditAction::make()
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-right')
                    ->iconPosition(IconPosition::After),

                Tables\Actions\Action::make('submitDraft')
                    ->label('Kirim Draft')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function ($record, $livewire) {

                        $record->submitDraft(auth()->id());

                        $url = $record->fresh()->waUrl();

                        if ($url) {
                            $livewire->js(
                                "window.open('{$url}', '_blank');"
                            );
                        }
                    })
                    ->visible(fn($record) => $record?->isDraft()),  

                Tables\Actions\Action::make('finalize')
                    ->label('Finalisasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn($record) => $record?->finalizeSchedule(auth()->id()))
                    ->visible(fn($record) => $record?->isSent()),

                Tables\Actions\Action::make('feedback')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')
                            ->label('Alasan Revisi')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(
                        fn($record, $data) =>
                        $record?->reject($data['reason'], auth()->id())
                    )
                    ->visible(fn($record) => $record?->isSent()),
            ])
            ->defaultSort('period_month', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            VesselPlanItemRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVesselPlans::route('/'),
            'create' => Pages\CreateVesselPlan::route('/create'),
            'edit'   => Pages\EditVesselPlan::route('/{record}/edit'),
        ];
    }
}
