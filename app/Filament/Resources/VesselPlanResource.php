<?php

namespace App\Filament\Resources;

use App\Models\Port;
use App\Models\VesselPlan;
use App\Supports\RouteCode;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Illuminate\Support\Carbon;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Perencanaan')
                    ->description('Periode serta pelabuhan muat dan bongkar untuk vessel plan ini.')
                    ->columns(2)
                    ->schema([

                        Select::make('period_month')
                            ->label('Periode')
                            ->helperText('Bulan perencanaan jadwal kapal.')
                            ->columnSpanFull()
                            ->options(function (?VesselPlan $record): array {
                                $options = collect(range(0, 12))
                                    ->mapWithKeys(function (int $i) {
                                        $date = now()->startOfMonth()->addMonths($i);

                                        return [$date->toDateString() => $date->translatedFormat('F Y')];
                                    });

                                if ($record?->period_month) {
                                    $own = $record->period_month->copy()->startOfMonth();
                                    $options->put($own->toDateString(), $own->translatedFormat('F Y'));
                                }

                                return $options->sortKeys()->all();
                            })
                            ->formatStateUsing(fn ($state) => filled($state)
                                ? Carbon::parse($state)
                                    ->timezone(config('app.timezone'))
                                    ->startOfMonth()
                                    ->toDateString()
                                : null)
                            ->searchable()
                            ->required(),
                            
                        Select::make('pol_id')
                            ->label('POL — Pelabuhan Muat')
                            ->helperText('Pelabuhan tempat muatan dinaikkan.')
                            ->relationship('pol', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Port $record) => $record->city ?: $record->name)
                            ->default(fn () => Port::query()->where('code', RouteCode::polUnlocode(RouteCode::default()))->value('id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('pod_id')
                            ->label('POD — Pelabuhan Bongkar')
                            ->helperText('Pelabuhan tujuan pembongkaran.')
                            ->relationship('pod', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Port $record) => $record->city ?: $record->name)
                            ->default(fn () => Port::query()->where('code', RouteCode::podUnlocode(RouteCode::default()))->value('id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                    ]),
            ]);
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

            ->emptyStateHeading('Belum ada Vessel Plan')
            ->emptyStateDescription('Buat vessel plan untuk mulai menyusun jadwal pelayaran pada periode ini.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Tambah Vessel Plan')
                    ->icon('heroicon-o-plus')
                    ->url(static::getUrl('create'))
                    ->visible(fn() => auth_user()?->isSuperAdmin() ?? false),
            ])

            ->actions([
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
