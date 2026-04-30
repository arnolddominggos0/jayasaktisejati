<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\BriefingSession;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class BriefingProgress extends BaseWidget
{
    protected int|string|array $columnSpan = 2;

    protected function getTableQuery(): Builder
    {
        return BriefingSession::query()
            ->withCount([
                'attendances as hadir_count' => fn($q)=>$q->where('attendance_status', AttendanceStatus::Present->value),
                'attendances as telat_count' => fn($q)=>$q->where('attendance_status', AttendanceStatus::Late->value),
                'attendances as alfa_count'  => fn($q)=>$q->where('attendance_status', AttendanceStatus::Absent->value),
                'attendances as sakit_count' => fn($q)=>$q->where('attendance_status', AttendanceStatus::Sick->value),
            ])
            ->latest('date')->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('date')->date()->label('Tanggal'),
            Tables\Columns\TextColumn::make('depot.name')->label('Depot'),
            Tables\Columns\TextColumn::make('hadir_count')->label('Hadir')->badge()->color('success'),
            Tables\Columns\TextColumn::make('telat_count')->label('Telat')->badge()->color('warning'),
            Tables\Columns\TextColumn::make('alfa_count')->label('Alfa')->badge()->color('danger'),
            Tables\Columns\TextColumn::make('sakit_count')->label('Sakit')->badge()->color('gray'),
        ];
    }
}
