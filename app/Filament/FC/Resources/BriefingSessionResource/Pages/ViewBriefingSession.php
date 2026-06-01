<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBriefingSession extends ViewRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected static ?string $title = 'Detail Briefing';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Briefing')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('date')
                            ->label('Tanggal')
                            ->date('d M Y')
                            ->weight('bold')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('depot.name')
                            ->label('Depot')
                            ->icon('heroicon-o-building-office'),

                        Infolists\Components\TextEntry::make('coordinator.name')
                            ->label('PIC (Koordinator)')
                            ->icon('heroicon-o-user'),

                        Infolists\Components\TextEntry::make('summary_headcount')
                            ->label('Target MP')
                            ->icon('heroicon-o-users'),

                        Infolists\Components\TextEntry::make('present_attendances_count')
                            ->label('Hadir')
                            ->state(fn ($record) => $record->presentAttendances()->count())
                            ->icon('heroicon-o-check-circle')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('mp_check_status')
                            ->label('Status MP Check')
                            ->formatStateUsing(fn ($state) => MPCheckStatus::tryFrom($state)?->label() ?? $state)
                            ->badge()
                            ->color(fn ($state) => MPCheckStatus::tryFrom($state)?->color() ?? 'gray'),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan / Topik')
                            ->columnSpanFull()
                            ->placeholder('-'),
                    ]),

                Infolists\Components\Section::make('Ringkasan Kesehatan')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('avg_temperature')
                            ->label('Rata-rata Suhu')
                            ->state(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->whereNotNull('temperature')
                                ->avg('temperature')
                                ? round($record->attendances()->where('attendance_status', 'present')->whereNotNull('temperature')->avg('temperature'), 1).'°C'
                                : '-')
                            ->icon('heroicon-o-thermometer'),

                        Infolists\Components\TextEntry::make('avg_bp')
                            ->label('Rata-rata Tensi')
                            ->state(function ($record) {
                                $present = $record->attendances()->where('attendance_status', 'present');
                                $sys = round($present->avg('bp_systolic')) ?? '-';
                                $dia = round($present->avg('bp_diastolic')) ?? '-';

                                return $sys && $dia ? "{$sys}/{$dia} mmHg" : '-';
                            })
                            ->icon('heroicon-o-heart-pulse'),

                        Infolists\Components\TextEntry::make('mp_fit')
                            ->label('MP Fit')
                            ->state(fn ($record) => $record->attendances()->where('attendance_status', 'present')->count())
                            ->color('success'),

                        Infolists\Components\TextEntry::make('mp_unfit')
                            ->label('Tidak Hadir')
                            ->state(fn ($record) => $record->attendances()->where('attendance_status', '!=', 'present')->count())
                            ->color('danger'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Briefing Session')
                ->modalDescription('Setujui sesi briefing ini? Ini akan membuka gate untuk loading.')
                ->visible(fn () => in_array($this->record->mp_check_status, [
                    MPCheckStatus::Draft->value,
                    MPCheckStatus::OnCheck->value,
                    MPCheckStatus::WaitingAction->value,
                ]))
                ->action(function () {
                    $this->record->update([
                        'mp_check_status' => MPCheckStatus::Approved->value,
                        'approved_at' => now(),
                        'approved_by' => Filament::auth()->user()?->id,
                    ]);
                    $this->record->refreshSufficientFlag();
                    Notification::make()
                        ->title('Briefing Disetujui')
                        ->success()
                        ->send();
                    $this->refreshData();
                }),

            EditAction::make()
                ->label('Ubah'),
        ];
    }
}
