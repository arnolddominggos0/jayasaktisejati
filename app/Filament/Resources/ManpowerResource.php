<?php

namespace App\Filament\Resources;

use App\Enums\MPDomain;
use App\Filament\Resources\ManpowerResource\Pages;
use App\Models\Manpower;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Resources\Resource;
use Filament\Tables;

class ManpowerResource extends Resource
{
    protected static ?string $model = Manpower::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Tenaga Kerja (MP)';
    protected static ?string $pluralLabel     = 'Tenaga Kerja (MP)';
    protected static ?string $navigationIcon  = 'heroicon-m-user-group';
    protected static ?string $modelLabel      = 'Tenaga Kerja (MP)';
    protected static ?int    $navigationSort  = 10;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\Select::make('domain')->label('Domain')
                ->options(collect(MPDomain::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required(),
            CheckboxList::make('skills')->label('Keahlian')->options([
                'stuffing'   => 'Stuffing',
                'unloading'  => 'Unloading',
                'Racking'    => 'Racking',
                'loading'    => 'Loading',
                'checker'    => 'Checker',
            ])->columns(2),
            Forms\Components\TagsInput::make('certs')->label('Sertifikasi')->suggestions([
                'SIO Forklift',
                'AK3 Umum',
                'K3 Rigger',
                'Pelatihan APD',
            ])->splitKeys([',']),
            Forms\Components\TextInput::make('phone')->tel()->label('Telepon'),
            Forms\Components\TextInput::make('license_number')->label('No. SIM/License'),
            Forms\Components\Select::make('branch_id')->relationship('branch', 'name')->label('Cabang')->required(),
            Forms\Components\Select::make('depot_id')->relationship('depot', 'name')->label('Depo')->required(),
            Forms\Components\Toggle::make('active')->label('Aktif')->default(true),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
            Tables\Columns\TextColumn::make('domain')->badge()->label('Domain')
                ->state(fn($record) => $record->domain?->label() ?? (string) $record->domain)
                ->color(fn($record) => $record->domain === MPDomain::SeaFreight ? 'info' : 'success'),
            Tables\Columns\TextColumn::make('skills')->label('Keahlian')
                ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : '—')
                ->limit(40),
            Tables\Columns\TextColumn::make('depot.name')->label('Depot'),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->badge(),
            Tables\Columns\IconColumn::make('active')->label('Aktif')->boolean(),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->filters([
            Tables\Filters\SelectFilter::make('domain')->options(collect(MPDomain::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            Tables\Filters\SelectFilter::make('depot_id')->label('Depot')->relationship('depot', 'name'),
            Tables\Filters\TernaryFilter::make('active')->label('Status'),
            Tables\Filters\Filter::make('skill_contains')
                ->form([Forms\Components\TextInput::make('skill')->label('Mengandung skill')])
                ->query(fn($query, $data) => !empty($data['skill']) ? $query->whereJsonContains('skills', $data['skill']) : $query),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
            Tables\Actions\Action::make('riwayatAbsensi')
                ->label('Riwayat Absensi')
                ->icon('heroicon-m-clipboard-document-list')
                ->url(fn($record) => route('filament.admin.resources.briefing-attendances.index', ['manpower_id' => $record->id]))
                ->openUrlInNewTab(false),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListManpowers::route('/'),
            'create' => Pages\CreateManpower::route('/create'),
            'edit'   => Pages\EditManpower::route('/{record}/edit'),
        ];
    }
}
