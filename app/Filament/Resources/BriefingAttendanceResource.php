<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\PpeCondition;
use App\Enums\PpeType;
use App\Filament\Resources\BriefingAttendanceResource\Pages;
use App\Models\BriefingAttendance;
use App\Models\BriefingSession;
use App\Models\Manpower;
use App\Models\PpeAssignment;
use App\Models\PpeItem;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BriefingAttendanceResource extends Resource
{
    protected static ?string $model = BriefingAttendance::class;

    protected static ?string $navigationGroup = 'Manajemen MP';
    protected static ?string $navigationLabel = 'Absensi Briefing';
    protected static ?string $pluralLabel     = 'Absensi Briefing';
    protected static ?string $modelLabel      = 'Absensi Briefing';
    protected static ?string $navigationIcon  = 'heroicon-m-clipboard-document-list';
    protected static ?int    $navigationSort  = 30;

    public static function form(Forms\Form $form): Forms\Form
    {
        $sidFromQuery = request()->integer('session_id');
        $midFromQuery = request()->integer('manpower_id');

        $parseBp = function (?string $value): array {
            if (! is_string($value)) return [null, null];
            return preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $value, $m) ? [(int) $m[1], (int) $m[2]] : [null, null];
        };

        return $form->schema([
            Section::make('Header Sesi')
                ->columns(4)
                ->schema([
                    Select::make('session_id')
                        ->label('Sesi Briefing')
                        ->relationship('session', 'id', fn(EloquentBuilder $query) => $query->orderByDesc('date'))
                        ->getOptionLabelFromRecordUsing(fn($record) => $record->display_label)
                        ->default($sidFromQuery)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->disabled(fn($record) => filled($record)),
                    Select::make('manpower_id')
                        ->label('Nama MP')
                        ->options(function (Get $get) {
                            $sid = (int) ($get('session_id') ?: request()->integer('session_id'));
                            $query = Manpower::query()->orderBy('name');
                            if ($sid) {
                                $depotId = BriefingSession::whereKey($sid)->value('depot_id');
                                if ($depotId) $query->where('depot_id', $depotId);
                            }
                            return $query->pluck('name','id');
                        })
                        ->default($midFromQuery)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->rules([
                            function (Get $get, ?BriefingAttendance $record) {
                                $sid = (int) ($get('session_id') ?: request()->integer('session_id'));
                                if (! $sid) return null;
                                return Rule::unique('briefing_attendances','manpower_id')
                                    ->where(fn($query) => $query->where('session_id',$sid))
                                    ->ignore($record?->getKey());
                            },
                        ]),
                ]),
            Grid::make(12)->schema([
                Section::make('Pemeriksaan')
                    ->columnSpan(5)
                    ->columns(2)
                    ->schema([
                        Select::make('attendance_status')
                            ->label('Status')
                            ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))
                            ->default(AttendanceStatus::Present->value)
                            ->required()
                            ->live(),
                        TextInput::make('temperature')
                            ->label('Suhu (°C)')
                            ->numeric()
                            ->minValue(35)
                            ->maxValue(42)
                            ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->required(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),
                        TextInput::make('bp')
                            ->label('Tekanan Darah (mmHg)')
                            ->placeholder('120/80')
                            ->rules(['nullable','regex:/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/'])
                            ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->required(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->dehydrated()
                            ->afterStateHydrated(function ($set, $state, ?BriefingAttendance $record) {
                                if (! $record) return;
                                $sys = $record->bp_systolic;
                                $dia = $record->bp_diastolic;
                                $set('bp', ($sys && $dia) ? ($sys.'/'.$dia) : null);
                            })
                            ->afterStateUpdated(function ($set, $state) use ($parseBp) {
                                [$sys, $dia] = $parseBp($state);
                                $set('bp_systolic', $sys);
                                $set('bp_diastolic', $dia);
                            })
                            ->columnSpan(2),
                        Hidden::make('bp_systolic')->dehydrated()->rules(['nullable','integer','min:80','max:200']),
                        Hidden::make('bp_diastolic')->dehydrated()->rules(['nullable','integer','min:40','max:130']),
                        Textarea::make('health_complaint')
                            ->label('Keluhan Kesehatan')
                            ->rows(2)
                            ->maxLength(500)
                            ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),
                        Textarea::make('remark')
                            ->label('Catatan')
                            ->rows(2)
                            ->maxLength(500),
                    ]),
                Section::make('Inspeksi APD')
                    ->columnSpan(7)
                    ->schema([
                        Repeater::make('ppeInspections')
                            ->label('Inspeksi APD')
                            ->relationship('ppeInspections')
                            ->grid(2)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->schema([
                                Select::make('type')
                                    ->label('Jenis APD')
                                    ->options(collect(PpeType::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))
                                    ->disabled()
                                    ->dehydrated(),
                                Select::make('ppe_item_id')
                                    ->label('Item')
                                    ->searchable()
                                    ->preload()
                                    ->options(function (callable $get) {
                                        $mpId = $get('../../manpower_id') ?: request()->integer('manpower_id');
                                        if (! $mpId) return [];
                                        return PpeAssignment::query()
                                            ->with(['item.sku'])
                                            ->where('manpower_id',$mpId)
                                            ->whereNull('returned_at')
                                            ->get()
                                            ->mapWithKeys(fn($a) => [$a->ppe_item_id => ($a->item->sku->name.($a->item->serial ? ' — '.$a->item->serial : ''))])
                                            ->all();
                                    }),
                                Select::make('condition')
                                    ->label('Kondisi')
                                    ->options(collect(PpeCondition::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))
                                    ->default(PpeCondition::Baik->value)
                                    ->required(),
                                TextInput::make('remark')->label('Catatan')->maxLength(100),
                            ])
                            ->default(function (?BriefingAttendance $record, callable $get) {
                                $mpId = $get('manpower_id') ?: request()->integer('manpower_id');
                                if ($record && $record->ppeInspections()->exists()) return null;
                                if (! $mpId) return [];
                                $assign = PpeAssignment::query()->with(['item.sku'])->where('manpower_id',$mpId)->whereNull('returned_at')->get();
                                $required = collect([PpeType::Helm->value,PpeType::Rompi->value,PpeType::SarungTangan->value,PpeType::Sepatu->value]);
                                $byType = $assign->mapWithKeys(fn($a) => [$a->item->sku->type => $a->ppe_item_id]);
                                return $required->map(fn($t) => ['type'=>$t,'ppe_item_id'=>$byType[$t] ?? null,'condition'=>PpeCondition::Baik->value,'remark'=>null])->all();
                            }),
                    ]),
            ]),
        ])->columns(1);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn(): EloquentBuilder => static::getEloquentQuery())
            ->defaultSort('created_at','desc')
            ->columns([
                TextColumn::make('session.date')->label('Hari / Tanggal')->date()->sortable(),
                TextColumn::make('manpower.name')->label('Nama Karyawan')->searchable()->sortable(),
                TextColumn::make('temperature')
                    ->label('Suhu Tubuh')
                    ->state(fn($record) => $record->temperature ? number_format((float)$record->temperature,1).' °C' : '—')
                    ->color(fn($record) => ($record->temperature && ($record->temperature < 36.5 || $record->temperature > 37.6)) ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('bp_display')
                    ->label('Tekanan Darah')
                    ->state(fn($record) => ($record->bp_systolic && $record->bp_diastolic) ? "{$record->bp_systolic}/{$record->bp_diastolic} mmHg" : '—')
                    ->color(function ($record) {
                        if (! $record->bp_systolic || ! $record->bp_diastolic) return null;
                        $ok = ($record->bp_systolic >= 90 && $record->bp_systolic <= 120) && ($record->bp_diastolic >= 60 && $record->bp_diastolic <= 80);
                        return $ok ? null : 'danger';
                    }),
                TextColumn::make('health_complaint')->label('Keluhan Kesehatan')->limit(30)->tooltip(fn($record)=>$record->health_complaint),
                TextColumn::make('remark')->label('Catatan')->limit(30)->tooltip(fn($record)=>$record->remark),
                TextColumn::make('ppe_summary')
                    ->label('APD')
                    ->state(function ($record) {
                        $list = $record->ppeInspections->map(function ($i) {
                            $t = method_exists(PpeType::class,'tryFrom') ? PpeType::tryFrom($i->type)?->label() : $i->type;
                            $c = method_exists(PpeCondition::class,'tryFrom') ? PpeCondition::tryFrom($i->condition)?->label() : $i->condition;
                            return ($t ?: $i->type).': '.($c ?: $i->condition);
                        })->filter()->values();
                        return $list->isNotEmpty() ? $list->implode('; ') : '—';
                    })
                    ->wrap(),
                TextColumn::make('created_at')->label('Dibuat')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([DatePicker::make('from')->label('Dari'), DatePicker::make('to')->label('Sampai')])
                    ->query(function (EloquentBuilder $query, array $data) {
                        if ($data['from'] ?? null) $query->whereHas('session', fn($state)=>$state->whereDate('date','>=',$data['from']));
                        if ($data['to'] ?? null)   $query->whereHas('session', fn($state)=>$state->whereDate('date','<=',$data['to']));
                    }),
                SelectFilter::make('session_id')->label('Sesi')->relationship('session','id')->getOptionLabelFromRecordUsing(fn($record)=>$record->display_label)->searchable()->preload(),
                SelectFilter::make('session.depot')->label('Depot')->relationship('session.depot','name')->searchable()->preload(),
                SelectFilter::make('session.coordinator')->label('PIC')->relationship('session.coordinator','name')->searchable()->preload(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('goCreate')->label('Tambah Absensi')->icon('heroicon-m-plus')->url(function () { $sid = request()->integer('session_id'); return static::getUrl('create', array_filter(['session_id'=>$sid])); }),
            ])
            ->actions([
                Action::make('quickAssign')
                    ->label('Quick Assign APD')
                    ->icon('heroicon-m-hand-raised')
                    ->visible(fn($record) => $record->attendance_status === AttendanceStatus::Present->value)
                    ->form(function ($record) {
                        $required = collect([PpeType::Helm->value,PpeType::Rompi->value,PpeType::SarungTangan->value,PpeType::Sepatu->value]);
                        $linked = $record->ppeInspections()->pluck('type')->unique();
                        $missing = $required->diff($linked)->values();
                        return [
                            Repeater::make('assignments')->label('Penugasan')->default($missing->map(fn($t)=>['type'=>$t,'ppe_item_id'=>null])->all())->addable(false)->deletable(false)->reorderable(false)->schema([
                                Select::make('type')->label('Jenis')->options(collect(PpeType::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))->disabled()->dehydrated(),
                                Select::make('ppe_item_id')->label('Pilih Item')->searchable()->preload()->options(function (callable $get) {
                                    $type = $get('type');
                                    return PpeItem::query()->where('status','in_stock')->whereHas('sku', fn($query)=>$query->where('type',$type))->with('sku:id,name,code')->get()->mapWithKeys(fn($it)=>[$it->id=>$it->sku->name.' ('.$it->sku->code.')'.($it->serial?' — '.$it->serial:'')])->all();
                                })->required(),
                            ]),
                        ];
                    })
                    ->action(function ($record, array $data) {
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
                                $insp->fill(['ppe_item_id' => $itemId,'condition' => PpeCondition::Baik->value])->save();
                            }
                        });
                    }),
                Action::make('quickReplace')
                    ->label('Ganti APD')
                    ->icon('heroicon-m-arrow-path')
                    ->visible(fn($record) => $record->attendance_status === AttendanceStatus::Present->value)
                    ->form([
                        Select::make('type')->label('Jenis')->options(collect(PpeType::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))->required()->live(),
                        Select::make('new_item_id')->label('Item Baru')->searchable()->preload()->options(function (Get $get) {
                            $type = $get('type');
                            return PpeItem::query()->where('status','in_stock')->whereHas('sku', fn($query)=>$query->where('type',$type))->with('sku:id,name,code')->get()->mapWithKeys(fn($it)=>[$it->id=>$it->sku->name.' ('.$it->sku->code.')'.($it->serial?' — '.$it->serial:'')])->all();
                        })->required(),
                    ])
                    ->action(function ($record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $insp = $record->ppeInspections()->firstOrCreate(['type'=>$data['type']], ['condition'=>PpeCondition::Baik->value]);
                            if ($insp->ppe_item_id) {
                                \App\Models\PpeAssignment::query()->where('ppe_item_id',$insp->ppe_item_id)->whereNull('returned_at')->latest()->first()?->update(['returned_at'=>now()]);
                                PpeItem::whereKey($insp->ppe_item_id)->update(['status'=>'in_stock','current_manpower_id'=>null,'assigned_at'=>null]);
                            }
                            $as = \App\Models\PpeAssignment::create(['ppe_item_id'=>$data['new_item_id'],'manpower_id'=>$record->manpower_id,'assigned_at'=>now()]);
                            PpeItem::whereKey($data['new_item_id'])->update(['status'=>'assigned','current_manpower_id'=>$record->manpower_id,'assigned_at'=>$as->assigned_at]);
                            $insp->update(['ppe_item_id'=>$data['new_item_id'],'condition'=>PpeCondition::Baik->value]);
                        });
                    }),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih')]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return static::getModel()::query()->with([
            'session:id,date,depot_id,coordinator_user_id',
            'session.depot:id,name',
            'session.coordinator:id,name',
            'manpower:id,name,domain',
            'ppeInspections',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBriefingAttendances::route('/'),
            'create' => Pages\CreateBriefingAttendance::route('/create'),
            'edit'   => Pages\EditBriefingAttendance::route('/{record}/edit'),
        ];
    }
}
