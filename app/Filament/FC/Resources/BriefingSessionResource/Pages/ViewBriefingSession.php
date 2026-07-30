<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Filament\FC\Resources\BriefingSessionResource;
use App\Filament\FC\Widgets\FcOperationalReadiness;
use Filament\Actions\EditAction;
use Filament\Infolists;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewBriefingSession extends ViewRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected static ?string $title = 'Detail Briefing';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                Section::make()
                    ->schema([
                        TextEntry::make('mp_readiness_badge')
                            ->label('Status Kesiapan Operasional')
                            ->badge()
                            ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold')
                            ->columnSpanFull()
                            ->getStateUsing(fn ($record) => $record->isOperationallyReady() ? 'ready' : 'not_ready')
                            ->formatStateUsing(fn ($state) => $state === 'ready'
                                ? '✓ READY — Operasional Dapat Dimulai'
                                : '✗ NOT READY')
                            ->color(fn ($state) => $state === 'ready' ? 'success' : 'danger'),
                    ]),

                Section::make('Informasi Briefing')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('date')
                            ->label('Tanggal')
                            ->date('d M Y')
                            ->weight('bold')
                            ->icon('heroicon-o-calendar'),

                        TextEntry::make('depot.name')
                            ->label('Depot')
                            ->icon('heroicon-o-building-office'),

                        TextEntry::make('coordinator.name')
                            ->label('PIC (Koordinator)')
                            ->icon('heroicon-o-user'),

                        TextEntry::make('summary_headcount')
                            ->label('Kebutuhan Tim SOP')
                            ->icon('heroicon-o-users'),

                        // TextEntry::make('actual_unit_handover_header')
                        //     ->label('Actual Unit Handover')
                        //     ->icon('heroicon-o-cube')
                        //     ->suffix(' unit')
                        //     ->getStateUsing(fn ($record) => $record->actual_unit_masuk_yard),

                        TextEntry::make('notes')
                            ->label('Catatan / Topik')
                            ->columnSpan(2)
                            ->placeholder('-'),

                        ImageEntry::make('briefing_evidence_path')
                            ->label('Foto Briefing')
                            ->disk('public')
                            ->visibility('public')
                            ->height(200)
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => filled($record?->briefing_evidence_path)),

                        TextEntry::make('evidence_empty_state')
                            ->label('Foto Briefing')
                            ->state('Belum ada dokumentasi briefing.')
                            ->color('gray')
                            ->icon('heroicon-o-photo')
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => ! filled($record?->briefing_evidence_path)),
                    ]),

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Kelola Briefing')
                ->visible(fn () => ! $this->getRecord()->isTerminal()),
        ];
    }

    /**
     * FcOperationalReadiness always resolves "today's session for the current
     * user's depot" — it has no $record binding. Mounting it unconditionally
     * would show another session's figures when viewing a past briefing, so it
     * is only rendered when the viewed session IS today's.
     */
    protected function getHeaderWidgets(): array
    {
        $date = $this->getRecord()->date;

        $isToday = $date
            && \Illuminate\Support\Carbon::parse($date)->isSameDay(\Illuminate\Support\Carbon::today());

        return $isToday ? [FcOperationalReadiness::class] : [];
    }

    public function getHeaderWidgetsColumns(): int | string | array
    {
        return 1;
    }
}
