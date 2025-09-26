<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PpeAssignmentResource\Pages;
use App\Models\Manpower;
use App\Models\PpeAssignment;
use App\Models\PpeItem;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PpeAssignmentResource extends Resource
{
    protected static ?string $model = PpeAssignment::class;

    protected static ?string $navigationGroup = 'APD & K3';
    protected static ?string $navigationLabel = 'Penugasan APD';
    protected static ?string $pluralLabel     = 'Penugasan APD';
    protected static ?string $modelLabel      = 'Penugasan APD';
    protected static ?string $navigationIcon  = 'heroicon-m-hand-raised';
    protected static ?int    $navigationSort  = 12;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('ppe_item_id')
                ->label('Item APD')
                ->options(function () {
                    return PpeItem::query()
                        ->with('sku:id,name,code,model')
                        ->whereDoesntHave('assignments', fn(EloquentBuilder $q) => $q->whereNull('returned_at'))
                        ->get()
                        ->mapWithKeys(function ($it) {
                            $label = $it->sku->name.' ('.$it->sku->code.')'.($it->serial ? ' — '.$it->serial : '');
                            return [$it->id => $label];
                        })->all();
                })
                ->searchable()
                ->preload()
                ->required()
                ->rule(function (?PpeAssignment $record) {
                    return Rule::unique('ppe_assignments','ppe_item_id')
                        ->where(fn($q) => $q->whereNull('returned_at'))
                        ->ignore($record?->id);
                }),
            Select::make('manpower_id')
                ->label('Manpower')
                ->options(fn() => Manpower::query()->orderBy('name')->pluck('name','id'))
                ->searchable()
                ->preload()
                ->required(),
            DateTimePicker::make('assigned_at')->label('Tgl. Penugasan')->seconds(false)->default(now())->required(),
            DateTimePicker::make('returned_at')->label('Tgl. Kembali')->seconds(false),
            Textarea::make('note')->label('Catatan')->rows(2)->maxLength(500),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn(): EloquentBuilder => static::getEloquentQuery())
            ->defaultSort('assigned_at','desc')
            ->columns([
                TextColumn::make('item.sku.code')->label('Kode SKU')->sortable()->toggleable(),
                TextColumn::make('item.sku.name')->label('Nama SKU')->sortable()->searchable(),
                TextColumn::make('item.serial')->label('Serial')->searchable()->toggleable(),
                TextColumn::make('manpower.name')->label('Manpower')->sortable()->searchable(),
                TextColumn::make('assigned_at')->label('Mulai')->dateTime()->sortable(),
                TextColumn::make('returned_at')->label('Kembali')->dateTime()->sortable(),
                TextColumn::make('status')->label('Status')->state(fn($r) => $r->returned_at ? 'Returned' : 'Active')->badge()->color(fn($r) => $r->returned_at ? 'gray' : 'warning'),
            ])
            ->filters([
                Filter::make('active')->label('Aktif')->query(fn(EloquentBuilder $q) => $q->whereNull('returned_at')),
                Filter::make('returned')->label('Selesai')->query(fn(EloquentBuilder $q) => $q->whereNotNull('returned_at')),
            ])
            ->actions([
                Action::make('return')
                    ->label('Kembalikan')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->visible(fn($record) => is_null($record->returned_at))
                    ->requiresConfirmation()
                    ->action(function (PpeAssignment $record) {
                        DB::transaction(function () use ($record) {
                            $record->update(['returned_at' => now()]);
                            $record->item()->update(['status' => 'in_stock','current_manpower_id' => null,'assigned_at' => null]);
                        });
                    }),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih')]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return static::getModel()::query()->with(['item:id,ppe_sku_id,serial','item.sku:id,code,name','manpower:id,name']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPpeAssignments::route('/'),
            'create' => Pages\CreatePpeAssignment::route('/create'),
            'edit'   => Pages\EditPpeAssignment::route('/{record}/edit'),
        ];
    }
}
