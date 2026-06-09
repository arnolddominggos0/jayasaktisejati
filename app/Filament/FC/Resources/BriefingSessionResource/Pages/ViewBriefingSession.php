<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource;
use Filament\Actions\EditAction;
use Filament\Infolists;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBriefingSession extends ViewRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected static ?string $title = 'Detail Briefing';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([

                // ─────────────────────────────────────────────────────────────
                // Section 1 — Session header
                // ─────────────────────────────────────────────────────────────
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
                            ->label('Target MP')
                            ->icon('heroicon-o-users'),

                        TextEntry::make('notes')
                            ->label('Catatan / Topik')
                            ->columnSpan(2)
                            ->placeholder('-'),
                    ]),

                // ─────────────────────────────────────────────────────────────
                // Section 2 — MP Check status
                // ─────────────────────────────────────────────────────────────
                Section::make('Status MP Check')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('mp_check_status')
                            ->label('Status MP Check')
                            ->formatStateUsing(function ($state): string {
                                if ($state instanceof MPCheckStatus) {
                                    return $state->label();
                                }
                                return MPCheckStatus::tryFrom((string) $state)?->label() ?? (string) ($state ?? 'Draft');
                            })
                            ->badge()
                            ->color(function ($state): string {
                                $enum = $state instanceof MPCheckStatus
                                    ? $state
                                    : MPCheckStatus::tryFrom((string) $state);
                                return match ($enum?->value) {
                                    'cleared'             => 'success',
                                    'on_check'            => 'warning',
                                    'waiting_action',
                                    'failed'              => 'danger',
                                    default               => 'gray',
                                };
                            }),
                    ]),

                // ─────────────────────────────────────────────────────────────
                // Section 3 — Manpower summary
                // All counts derived from attendances collection in PHP
                // (final_mp_status is a computed accessor, not a DB column)
                // ─────────────────────────────────────────────────────────────
                Section::make('Ringkasan Manpower')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('mp_hadir')
                            ->label('Hadir')
                            ->state(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->count())
                            ->suffix(fn ($record) => ' / ' . (int) $record->summary_headcount)
                            ->color('success')
                            ->icon('heroicon-o-check-circle'),

                        TextEntry::make('mp_tidak_hadir')
                            ->label('Tidak Hadir / Sakit')
                            ->state(fn ($record) => $record->attendances()
                                ->where('attendance_status', '!=', 'present')
                                ->count())
                            ->color(fn ($record) => $record->attendances()->where('attendance_status', '!=', 'present')->count() > 0
                                ? 'danger'
                                : 'gray')
                            ->icon('heroicon-o-x-circle'),

                        TextEntry::make('mp_apd_lengkap')
                            ->label('APD Lengkap')
                            ->state(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->where('has_ppe', true)
                                ->count())
                            ->color('success')
                            ->icon('heroicon-o-shield-check'),

                        TextEntry::make('mp_siap_kerja')
                            ->label('Siap Kerja')
                            ->state(function ($record) {
                                return $record->attendances
                                    ->filter(fn ($a) => $a->final_mp_status === 'Siap Kerja')
                                    ->count();
                            })
                            ->color('success')
                            ->icon('heroicon-o-check-badge'),

                        TextEntry::make('mp_recheck')
                            ->label('Perlu Recheck')
                            ->state(function ($record) {
                                return $record->attendances
                                    ->filter(fn ($a) => in_array($a->final_mp_status, [
                                        'Perlu Pemeriksaan Ulang',
                                        'APD Tidak Lengkap',
                                        'Istirahat 30 Menit',
                                    ], true))
                                    ->count();
                            })
                            ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('mp_tidak_fit')
                            ->label('Tidak Fit')
                            ->state(function ($record) {
                                return $record->attendances
                                    ->filter(fn ($a) => $a->final_mp_status === 'Tidak Fit')
                                    ->count();
                            })
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                            ->icon('heroicon-o-exclamation-triangle'),
                    ]),

                // ─────────────────────────────────────────────────────────────
                // Section 4 — Health vitals summary (from attendance records)
                // ─────────────────────────────────────────────────────────────
                Section::make('Ringkasan Kesehatan')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('avg_temperature')
                            ->label('Rata-rata Suhu')
                            ->state(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->whereNotNull('temperature')
                                ->avg('temperature')
                                ? round($record->attendances()
                                    ->where('attendance_status', 'present')
                                    ->whereNotNull('temperature')
                                    ->avg('temperature'), 1) . '°C'
                                : '—')
                            ->icon('heroicon-o-fire'),

                        TextEntry::make('avg_bp')
                            ->label('Rata-rata Tensi')
                            ->state(function ($record) {
                                $q = $record->attendances()->where('attendance_status', 'present');
                                $sys = round((float) $q->avg('bp_systolic'));
                                $dia = round((float) $q->avg('bp_diastolic'));
                                return ($sys && $dia) ? "{$sys}/{$dia} mmHg" : '—';
                            })
                            ->icon('heroicon-o-heart'),

                        TextEntry::make('health_complaint_count')
                            ->label('Keluhan Kesehatan')
                            ->state(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->whereNotNull('health_complaint')
                                ->count() . ' orang')
                            ->color(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->whereNotNull('health_complaint')
                                ->count() > 0 ? 'warning' : 'gray'),

                        TextEntry::make('recheck_count')
                            ->label('Proses Recheck')
                            ->state(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->where('recheck_required', true)
                                ->count() . ' orang')
                            ->color(fn ($record) => $record->attendances()
                                ->where('attendance_status', 'present')
                                ->where('recheck_required', true)
                                ->count() > 0 ? 'warning' : 'gray'),
                    ]),

                // ─────────────────────────────────────────────────────────────
                // Section 5 — Stock APD
                // Reuses stockApdChecks() HasMany relation on BriefingSession
                // ─────────────────────────────────────────────────────────────
                Section::make('Stok APD')
                    ->schema([
                        RepeatableEntry::make('stockApdChecks')
                            ->label('')
                            ->schema([
                                TextEntry::make('ppe_type')
                                    ->label('Jenis APD')
                                    ->weight('bold'),

                                TextEntry::make('stock_available')
                                    ->label('Stok Tersedia')
                                    ->placeholder('—')
                                    ->color(fn ($state, $record) => (
                                        $state !== null
                                        && $record->required_quantity !== null
                                        && $state < $record->required_quantity
                                    ) ? 'danger' : 'success'),

                                TextEntry::make('required_quantity')
                                    ->label('Kebutuhan')
                                    ->placeholder('—'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match (strtolower((string) $state)) {
                                        'cukup'  => 'Cukup',
                                        'kurang' => 'Kurang',
                                        default  => $state ?? '—',
                                    })
                                    ->color(fn ($state) => match (strtolower((string) $state)) {
                                        'cukup'  => 'success',
                                        'kurang' => 'danger',
                                        default  => 'gray',
                                    }),

                                TextEntry::make('remark')
                                    ->label('Catatan')
                                    ->placeholder('—')
                                    ->columnSpan(2),
                            ])
                            ->columns(5)
                            ->placeholder('Belum ada data stok APD dicatat.'),
                    ])
                    ->visible(fn ($record) => $record->stockApdChecks()->exists()),

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah'),
        ];
    }
}
