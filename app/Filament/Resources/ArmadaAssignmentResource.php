<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArmadaAssignmentResource\Pages;
use App\Models\ArmadaAssignment;
use App\Models\Armada;
use Filament\Forms;
use Filament\Forms\Components\{DatePicker, Select, Textarea, Hidden};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};

class ArmadaAssignmentResource extends Resource
{
    protected static ?string $model = ArmadaAssignment::class;
    protected static ?string $navigationGroup = 'Manajemen Armada';
    protected static ?string $navigationLabel = 'Penugasan Armada';
    protected static ?string $navigationIcon = 'heroicon-m-clipboard-document-check';
    protected static ?int    $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Hidden::make('shipment_id'),
            DatePicker::make('date')
                ->label('Tanggal')
                ->native(false)
                ->required()
                ->default(fn() => now()->toDateString())
                ->reactive(),

            Hidden::make('branch_id')->dehydrated(false),
            Hidden::make('depot_id')->dehydrated(false),

            Select::make('armada_id')
                ->label('Armada')
                ->searchable()
                ->preload()
                ->required()
                ->options(function (callable $get) {
                    $date     = $get('date') ?: now()->toDateString();
                    $branchId = $get('branch_id');
                    return Armada::assignable($date, $branchId)
                        ->orderBy('code')
                        ->limit(200)
                        ->get()
                        ->mapWithKeys(fn($a) => [$a->id => $a->display_name ?? ($a->code . ' - ' . $a->plate_number)])
                        ->toArray();
                })
                ->getSearchResultsUsing(function (string $search, callable $get) {
                    $date     = $get('date') ?: now()->toDateString();
                    $branchId = $get('branch_id');
                    return Armada::assignable($date, $branchId)
                        ->where(function ($qq) use ($search) {
                            $qq->where('code', 'ilike', "%{$search}%")
                                ->orWhere('plate_number', 'ilike', "%{$search}%");
                        })
                        ->orderBy('code')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn($a) => [$a->id => $a->display_name ?? ($a->code . ' - ' . $a->plate_number)])
                        ->toArray();
                }),

            Textarea::make('notes')->label('Catatan')->rows(3),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('date')->label('Tanggal')->date(),
            BadgeColumn::make('armada.code')->label('Kode Armada'),
            TextColumn::make('armada.plate_number')->label('No. Polisi'),
            BadgeColumn::make('shipment.code')->label('Shipment'),
            TextColumn::make('created_at')->since()->label('Dibuat'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
            Tables\Actions\DeleteAction::make()->label('Hapus'),
        ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Tambah')]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArmadaAssignments::route('/'),
            'create' => Pages\CreateArmadaAssignment::route('/create'),
            'edit'   => Pages\EditArmadaAssignment::route('/{record}/edit'),
        ];
    }
}
