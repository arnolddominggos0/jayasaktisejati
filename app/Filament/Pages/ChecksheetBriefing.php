<?php

namespace App\Filament\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\PpeCondition;
use App\Enums\PpeType;
use App\Models\BriefingAttendance;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\Manpower;
use App\Models\PpeItem;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;

class ChecksheetBriefing extends Page implements HasForms, HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationGroup = 'Manajemen MP';
    protected static ?string $navigationLabel = 'Checksheet Briefing';
    protected static ?string $navigationIcon  = 'heroicon-m-clipboard-document-check';
    protected static string $view = 'filament.pages.checksheet-briefing';
    protected static ?int    $navigationSort  = 15;

    public ?int $sessionId = null;
    public ?string $date = null;
    public ?int $depot_id = null;
    public ?int $coordinator_user_id = null;
    public ?int $summary_headcount = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Sesi')
                ->schema([
                    Grid::make(12)->schema([
                        DatePicker::make('date')->label('Tanggal')->required()->closeOnDateSelection()->columnSpan(3),
                        Select::make('depot_id')
                            ->label('Depot')
                            ->options(fn() => Depot::query()->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(3),
                        Select::make('coordinator_user_id')
                            ->label('PIC')
                            ->options(function (Forms\Get $get) {
                                $depotId = $get('depot_id');
                                $branchId = $depotId ? Depot::query()->whereKey($depotId)->value('branch_id') : null;
                                return \App\Models\User::role('field_coordinator')
                                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                                    ->orderBy('name')->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(3),
                        TextInput::make('summary_headcount')->label('Jumlah MP')->numeric()->minValue(0)->default(0)->columnSpan(3),
                    ]),
                ])
                ->footerActions([
                    FormAction::make('openSession')->label($this->sessionId ? 'Muat Ulang' : 'Buka Sesi')->action('openSession'),
                ]),
        ];
    }

    public function openSession(): void
    {
        $data = $this->form->getState();
        $session = BriefingSession::query()->firstOrCreate(
            ['date' => $data['date'], 'depot_id' => $data['depot_id']],
            ['coordinator_user_id' => $data['coordinator_user_id'], 'summary_headcount' => (int) ($data['summary_headcount'] ?? 0)]
        );
        if ($session->coordinator_user_id !== (int) $data['coordinator_user_id'] || (int)$session->summary_headcount !== (int)($data['summary_headcount'] ?? 0)) {
            $session->update(['coordinator_user_id' => $data['coordinator_user_id'], 'summary_headcount' => (int) ($data['summary_headcount'] ?? 0)]);
        }
        $this->sessionId = $session->id;
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('generateAll')
                ->label('Generate dari MP Depot')
                ->icon('heroicon-m-sparkles')
                ->visible(fn() => (bool) $this->sessionId)
                ->action(function () {
                    $session = BriefingSession::find($this->sessionId);
                    if (! $session) return;
                    $mpIds = Manpower::query()->where('depot_id', $session->depot_id)->pluck('id')->all();
                    DB::transaction(function () use ($mpIds, $session) {
                        foreach ($mpIds as $mpId) {
                            BriefingAttendance::firstOrCreate(
                                ['session_id' => $session->id, 'manpower_id' => $mpId],
                                ['attendance_status' => AttendanceStatus::Present->value]
                            );
                        }
                    });
                }),
            HeaderAction::make('addOne')
                ->label('Tambah MP')
                ->icon('heroicon-m-plus')
                ->visible(fn() => (bool) $this->sessionId)
                ->form([
                    Select::make('manpower_id')
                        ->label('Manpower')
                        ->options(function () {
                            $session = BriefingSession::find($this->sessionId);
                            if (! $session) return [];
                            return Manpower::query()->where('depot_id', $session->depot_id)->orderBy('name')->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data) {
                    if (! $this->sessionId) return;
                    BriefingAttendance::firstOrCreate(
                        ['session_id' => $this->sessionId, 'manpower_id' => $data['manpower_id']],
                        ['attendance_status' => AttendanceStatus::Present->value]
                    );
                }),
        ];
    }

    protected function getTableQuery(): ?EloquentBuilder
    {
        if (! $this->sessionId) return BriefingAttendance::query()->whereRaw('1=0');
        return BriefingAttendance::query()
            ->where('session_id', $this->sessionId)
            ->with(['manpower:id,name', 'ppeInspections']);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('manpower.name')->label('Nama Karyawan')->searchable()->sortable(),
            TextColumn::make('temperature')->label('Suhu (°C)')
                ->state(fn($record) => $record->temperature ? number_format((float)$record->temperature, 1) : '—')
                ->color(fn($record) => ($record->temperature && ($record->temperature < 36.5 || $record->temperature > 37.6)) ? 'danger' : null),
            TextColumn::make('bp')->label('Tekanan Darah')
                ->state(fn($record) => ($record->bp_systolic && $record->bp_diastolic) ? "{$record->bp_systolic}/{$record->bp_diastolic}" : '—')
                ->color(function ($record) {
                    if (! $record->bp_systolic || ! $record->bp_diastolic) return null;
                    $ok = ($record->bp_systolic >= 90 && $record->bp_systolic <= 120) && ($record->bp_diastolic >= 60 && $record->bp_diastolic <= 80);
                    return $ok ? null : 'danger';
                }),
            TextColumn::make('health_complaint')->label('Keluhan')->limit(24)->tooltip(fn($record) => $record->health_complaint),
            TextColumn::make('remark')->label('Catatan')->limit(24)->tooltip(fn($record) => $record->remark),
            TextColumn::make('ppe_summary')->label('APD')->state(function ($record) {
                $list = $record->ppeInspections->map(function ($i) {
                    $t = method_exists(PpeType::class, 'tryFrom') ? PpeType::tryFrom($i->type)?->label() : $i->type;
                    $c = method_exists(PpeCondition::class, 'tryFrom') ? PpeCondition::tryFrom($i->condition)?->label() : $i->condition;
                    return ($t ?: $i->type) . ': ' . ($c ?: $i->condition);
                })->filter();
                return $list->isNotEmpty() ? $list->implode('; ') : '—';
            })->wrap(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Filter::make('onlyPresent')->label('Hadir')->query(fn(EloquentBuilder $q) => $q->where('attendance_status', AttendanceStatus::Present->value)),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            TableAction::make('edit')
                ->label('Ubah')
                ->icon('heroicon-m-pencil-square')
                ->form([
                    Select::make('attendance_status')->label('Status')->options(collect(AttendanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required()->live(),
                    TextInput::make('temperature')->label('Suhu (°C)')->numeric()->minValue(35)->maxValue(42)
                        ->visible(fn(Forms\Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),
                    TextInput::make('bp')->label('Tekanan Darah')->placeholder('120/80')
                        ->rules(['nullable', 'regex:/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/'])
                        ->visible(fn(Forms\Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),
                    \Filament\Forms\Components\Textarea::make('health_complaint')->label('Keluhan')->rows(2)->maxLength(500)
                        ->visible(fn(Forms\Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),
                    \Filament\Forms\Components\Textarea::make('remark')->label('Catatan')->rows(2)->maxLength(500),
                ])
                ->action(function (BriefingAttendance $record, array $data) {
                    $bp = $data['bp'] ?? null;
                    $sys = $dia = null;
                    if (is_string($bp) && preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $bp, $m)) {
                        $sys = (int) $m[1];
                        $dia = (int) $m[2];
                    }
                    $record->update([
                        'attendance_status' => $data['attendance_status'],
                        'temperature' => $data['temperature'] ?? null,
                        'bp_systolic' => $sys,
                        'bp_diastolic' => $dia,
                        'health_complaint' => $data['health_complaint'] ?? null,
                        'remark' => $data['remark'] ?? null,
                    ]);
                }),
            TableAction::make('quickAssign')
                ->label('Quick Assign APD')
                ->icon('heroicon-m-hand-raised')
                ->visible(fn(BriefingAttendance $record) => $record->attendance_status === AttendanceStatus::Present->value)
                ->form(function (BriefingAttendance $record) {
                    $required = collect([PpeType::Helm->value, PpeType::Rompi->value, PpeType::SarungTangan->value, PpeType::Sepatu->value]);
                    $linked = $record->ppeInspections()->pluck('type')->unique();
                    $missing = $required->diff($linked)->values();
                    return [
                        \Filament\Forms\Components\Repeater::make('assignments')
                            ->label('Penugasan')
                            ->default($missing->map(fn($t) => ['type' => $t, 'ppe_item_id' => null])->all())
                            ->addable(false)->deletable(false)->reorderable(false)
                            ->schema([
                                Select::make('type')->label('Jenis')->options(collect(PpeType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->disabled()->dehydrated(),
                                Select::make('ppe_item_id')->label('Pilih Item')->searchable()->preload()->options(function (\Closure $get) {
                                    $type = $get('type');
                                    return PpeItem::query()->where('status', 'in_stock')->whereHas('sku', fn($q) => $q->where('type', $type))
                                        ->with('sku:id,name,code')
                                        ->get()->mapWithKeys(fn($it) => [$it->id => $it->sku->name . ' (' . $it->sku->code . ')' . ($it->serial ? ' — ' . $it->serial : '')])->all();
                                })->required(),
                            ]),
                    ];
                })
                ->action(function (BriefingAttendance $record, array $data) {
                    DB::transaction(function () use ($record, $data) {
                        foreach (($data['assignments'] ?? []) as $row) {
                            $itemId = (int) ($row['ppe_item_id'] ?? 0);
                            $type   = (string) ($row['type'] ?? '');
                            if (! $itemId || $type === '') continue;
                            $as = \App\Models\PpeAssignment::create([
                                'ppe_item_id' => $itemId,
                                'manpower_id' => $record->manpower_id,
                                'assigned_at' => now(),
                            ]);
                            PpeItem::whereKey($itemId)->update([
                                'status' => 'assigned',
                                'current_manpower_id' => $record->manpower_id,
                                'assigned_at' => $as->assigned_at,
                            ]);
                            $insp = $record->ppeInspections()->firstOrNew(['type' => $type]);
                            $insp->fill(['ppe_item_id' => $itemId, 'condition' => PpeCondition::Baik->value])->save();
                        }
                    });
                }),
            TableAction::make('quickReplace')
                ->label('Ganti APD')
                ->icon('heroicon-m-arrow-path')
                ->visible(fn(BriefingAttendance $record) => $record->attendance_status === AttendanceStatus::Present->value)
                ->form([
                    Select::make('type')->label('Jenis')->options(collect(PpeType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required()->live(),
                    Select::make('new_item_id')->label('Item Baru')->searchable()->preload()->options(function (Forms\Get $get) {
                        $type = $get('type');
                        return PpeItem::query()->where('status', 'in_stock')->whereHas('sku', fn($q) => $q->where('type', $type))
                            ->with('sku:id,name,code')
                            ->get()->mapWithKeys(fn($it) => [$it->id => $it->sku->name . ' (' . $it->sku->code . ')' . ($it->serial ? ' — ' . $it->serial : '')])->all();
                    })->required(),
                ])
                ->action(function (BriefingAttendance $record, array $data) {
                    DB::transaction(function () use ($record, $data) {
                        $insp = $record->ppeInspections()->firstOrCreate(['type' => $data['type']], ['condition' => PpeCondition::Baik->value]);
                        if ($insp->ppe_item_id) {
                            \App\Models\PpeAssignment::query()->where('ppe_item_id', $insp->ppe_item_id)->whereNull('returned_at')->latest()->first()?->update(['returned_at' => now()]);
                            PpeItem::whereKey($insp->ppe_item_id)->update(['status' => 'in_stock', 'current_manpower_id' => null, 'assigned_at' => null]);
                        }
                        $as = \App\Models\PpeAssignment::create(['ppe_item_id' => $data['new_item_id'], 'manpower_id' => $record->manpower_id, 'assigned_at' => now()]);
                        PpeItem::whereKey($data['new_item_id'])->update(['status' => 'assigned', 'current_manpower_id' => $record->manpower_id, 'assigned_at' => $as->assigned_at]);
                        $insp->update(['ppe_item_id' => $data['new_item_id'], 'condition' => PpeCondition::Baik->value]);
                    });
                }),
            TableAction::make('delete')->label('Hapus')->icon('heroicon-m-trash')->requiresConfirmation()->action(fn(BriefingAttendance $record) => $record->delete()),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [];
    }
}
