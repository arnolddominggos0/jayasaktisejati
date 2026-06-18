<?php

namespace App\Filament\Resources\BriefingSessionResource\Pages;

use App\Enums\MPCheckStatus;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\BriefingSessionResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

/**
 * Admin: halaman detail sesi briefing.
 *
 * Header actions:
 *   - Setujui Briefing  (visible jika approved_at = null)
 *   - Batalkan Persetujuan (visible jika approved_at != null)
 *
 * Infolist sections (sama dengan FC — single source of truth):
 *   0. Status Persetujuan   (Admin only)
 *   1. Informasi Briefing
 *   2. Status Operasional
 *   3. Status MP Check
 *   4. Ringkasan Manpower
 *   5. Ringkasan Kesehatan
 *   6. Stok APD
 *   7. Bukti Briefing
 */
class ViewBriefingSession extends ViewRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected static ?string $title = 'Detail Briefing';

    // ── Infolist ──────────────────────────────────────────────────────────────

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ─────────────────────────────────────────────────────────────────
            // Section 0 — Approval & Audit (admin-only)
            // ─────────────────────────────────────────────────────────────────
            Section::make('Status Persetujuan')
                ->icon('heroicon-o-check-badge')
                ->columns(3)
                ->schema([
                    TextEntry::make('approval_status_label')
                        ->label('Status')
                        ->badge()
                        ->state(fn ($record) => $record->approved_at
                            ? 'Sudah Disetujui'
                            : 'Menunggu Persetujuan')
                        ->color(fn ($record) => $record->approved_at ? 'success' : 'warning'),

                    TextEntry::make('approved_at')
                        ->label('Tanggal Disetujui')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('—'),

                    TextEntry::make('approved_by_name')
                        ->label('Disetujui Oleh')
                        ->state(function ($record) {
                            if (! $record->approved_by) {
                                return '—';
                            }

                            return DB::table('users')
                                ->where('id', $record->approved_by)
                                ->value('name') ?? '—';
                        }),
                ]),

            // ─────────────────────────────────────────────────────────────────
            // Section 1 — Session header
            // ─────────────────────────────────────────────────────────────────
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

                    TextEntry::make('actual_unit_handover')
                        ->label('Actual Unit Handover')
                        ->icon('heroicon-o-cube')
                        ->suffix(' unit')
                        ->getStateUsing(fn ($record) => $record->actual_unit_masuk_yard),

                    TextEntry::make('notes')
                        ->label('Catatan / Topik')
                        ->columnSpan(2)
                        ->placeholder('-'),
                ]),

            // ─────────────────────────────────────────────────────────────────
            // Section 1B — Ringkasan Beban Kerja
            // ─────────────────────────────────────────────────────────────────
            Section::make('Ringkasan Beban Kerja')
                ->icon('heroicon-o-truck')
                ->columns(5)
                ->schema([
                    TextEntry::make('shipment_count')
                        ->label('Shipment Terpilih')
                        ->icon('heroicon-o-document-text')
                        ->getStateUsing(fn ($record) => $record->shipments->count() . ' SPPB'),

                    TextEntry::make('expected_unit_wl')
                        ->label('Expected Unit')
                        ->icon('heroicon-o-cube-transparent')
                        ->suffix(' unit')
                        ->getStateUsing(fn ($record) => $record->expected_unit),

                    TextEntry::make('actual_unit_handover_wl')
                        ->label('Actual Unit Handover')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->suffix(' unit')
                        ->getStateUsing(fn ($record) => $record->actual_unit_masuk_yard),

                    TextEntry::make('unit_gap_wl')
                        ->label('Gap Unit')
                        ->icon('heroicon-o-arrows-up-down')
                        ->suffix(' unit')
                        ->getStateUsing(fn ($record) => $record->unit_gap)
                        ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                        ->badge(),

                    TextEntry::make('workload_status')
                        ->label('Status Beban Kerja')
                        ->badge()
                        ->weight('bold')
                        ->getStateUsing(fn ($record) => $record->unit_gap > 0 ? 'belum' : 'siap')
                        ->formatStateUsing(fn ($state) => $state === 'siap'
                            ? 'Siap Operasional'
                            : 'Belum Semua Unit Masuk')
                        ->color(fn ($state) => $state === 'siap' ? 'success' : 'warning'),
                ]),

            // ─────────────────────────────────────────────────────────────────
            // Section 1C — Shipment Terpilih
            // ─────────────────────────────────────────────────────────────────
            Section::make('Shipment Terpilih')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    RepeatableEntry::make('shipments')
                        ->label('')
                        ->schema([
                            TextEntry::make('code')
                                ->label('SPPB / Kode')
                                ->weight('bold')
                                ->icon('heroicon-o-document'),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(function ($state): string {
                                    $enum = $state instanceof ShipmentStatus
                                        ? $state
                                        : ShipmentStatus::tryFrom((string) $state);
                                    return $enum?->label() ?? ucwords(str_replace('_', ' ', (string) $state));
                                })
                                ->color(function ($state): string {
                                    $enum = $state instanceof ShipmentStatus
                                        ? $state
                                        : ShipmentStatus::tryFrom((string) $state);
                                    return $enum?->color() ?? 'gray';
                                }),

                            TextEntry::make('customer.name')
                                ->label('Customer')
                                ->icon('heroicon-o-building-office-2')
                                ->placeholder('—'),

                            TextEntry::make('units_count')
                                ->label('Unit')
                                ->suffix(' unit'),
                        ])
                        ->columns(4)
                        ->placeholder('Belum ada shipment yang di-attach ke sesi ini.'),
                ])
                ->visible(fn ($record) => $record->shipments->isNotEmpty()),

            // ─────────────────────────────────────────────────────────────────
            // Section 2 — STATUS OPERASIONAL
            // ─────────────────────────────────────────────────────────────────
            Section::make('Status Operasional')
                ->icon('heroicon-o-signal')
                ->columns(4)
                ->schema([

                    // ── Row 1 — numbers ──────────────────────────────────────

                    TextEntry::make('actual_unit_handover_row')
                        ->label('Actual Unit Handover')
                        ->icon('heroicon-o-cube')
                        ->suffix(' unit')
                        ->getStateUsing(fn ($record) => $record->actual_unit_masuk_yard),

                    TextEntry::make('summary_headcount')
                        ->label('Need Tim SOP')
                        ->icon('heroicon-o-users')
                        ->suffix(' MP'),

                    TextEntry::make('mp_attend_summary')
                        ->label('MP Attend')
                        ->icon('heroicon-o-user-group')
                        ->state(fn ($record) => $record->attendances
                            ->filter(fn ($a) => (
                                $a->attendance_status instanceof \App\Enums\AttendanceStatus
                                    ? $a->attendance_status->value
                                    : (string) $a->attendance_status
                            ) === 'present')
                            ->count()
                        )
                        ->suffix(' MP')
                        ->color(function ($state, $record) {
                            $need = (int) ($record->summary_headcount ?? 0);

                            return $need > 0
                                ? ($state >= $need ? 'success' : 'danger')
                                : 'gray';
                        }),

                    TextEntry::make('gap_summary')
                        ->label('Gap')
                        ->icon('heroicon-o-arrows-up-down')
                        ->state(function ($record) {
                            $attend = $record->attendances
                                ->filter(fn ($a) => (
                                    $a->attendance_status instanceof \App\Enums\AttendanceStatus
                                        ? $a->attendance_status->value
                                        : (string) $a->attendance_status
                                ) === 'present')
                                ->count();

                            return $attend - (int) ($record->summary_headcount ?? 0);
                        })
                        ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state} MP" : "{$state} MP")
                        ->color(fn ($state) => match (true) {
                            $state > 0  => 'success',
                            $state === 0 => 'gray',
                            default     => 'danger',
                        }),

                    // ── Row 2 — readiness breakdown + APD ────────────────────

                    TextEntry::make('siap_kerja_summary')
                        ->label('Siap Kerja')
                        ->icon('heroicon-o-check-badge')
                        ->state(fn ($record) => $record->attendances
                            ->filter(fn ($a) => $a->final_mp_status === 'Siap Kerja')
                            ->count()
                        )
                        ->suffix(' MP')
                        ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),

                    TextEntry::make('recheck_summary')
                        ->label('Perlu Recheck')
                        ->icon('heroicon-o-clock')
                        ->state(fn ($record) => $record->attendances
                            ->filter(fn ($a) => in_array($a->final_mp_status, [
                                'Perlu Pemeriksaan Ulang',
                                'Istirahat 30 Menit',
                                'APD Tidak Lengkap',
                            ], true))
                            ->count()
                        )
                        ->suffix(' MP')
                        ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),

                    TextEntry::make('tidak_fit_summary')
                        ->label('Tidak Fit')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->state(fn ($record) => $record->attendances
                            ->filter(fn ($a) => $a->final_mp_status === 'Tidak Fit')
                            ->count()
                        )
                        ->suffix(' MP')
                        ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),

                    TextEntry::make('apd_ringkasan_status')
                        ->label('Status APD')
                        ->icon('heroicon-o-shield-check')
                        ->badge()
                        ->state(function ($record) {
                            $checks = $record->stockApdChecks;
                            if ($checks->isEmpty()) {
                                return 'belum_dicek';
                            }

                            return $checks->contains(fn ($c) => $c->computed_status === 'kurang')
                                ? 'kurang'
                                : 'cukup';
                        })
                        ->formatStateUsing(function ($state, $record) {
                            if ($state === 'kurang') {
                                $n = $record->stockApdChecks
                                    ->where('computed_status', 'kurang')
                                    ->count();

                                return "{$n} Jenis Kurang";
                            }

                            return match ($state) {
                                'cukup'       => 'Cukup',
                                'belum_dicek' => 'Belum Dicek',
                                default       => $state,
                            };
                        })
                        ->color(fn ($state) => match ($state) {
                            'cukup'  => 'success',
                            'kurang' => 'danger',
                            default  => 'gray',
                        }),

                    // ── Row 3 — final STATUS OPERASIONAL badge ────────────────

                    TextEntry::make('status_operasional_badge')
                        ->label('Status Operasional')
                        ->badge()
                        ->weight('bold')
                        ->columnSpanFull()
                        ->state(function ($record) {
                            $need = (int) ($record->summary_headcount ?? 0);
                            if ($need === 0) {
                                return 'no_data';
                            }

                            $attend = $record->attendances
                                ->filter(fn ($a) => (
                                    $a->attendance_status instanceof \App\Enums\AttendanceStatus
                                        ? $a->attendance_status->value
                                        : (string) $a->attendance_status
                                ) === 'present')
                                ->count();

                            $fit = $record->attendances
                                ->filter(fn ($a) => $a->final_mp_status === 'Siap Kerja')
                                ->count();

                            $apdShortage = $record->stockApdChecks
                                ->contains(fn ($c) => $c->computed_status === 'kurang');

                            if ($attend >= $need && $fit >= $need && ! $apdShortage) {
                                return 'siap';
                            }

                            return 'belum_siap';
                        })
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'siap'       => '✓ SIAP OPERASIONAL',
                            'belum_siap' => '✗ BELUM SIAP',
                            default      => '— Data Tidak Cukup',
                        })
                        ->color(fn ($state) => match ($state) {
                            'siap'       => 'success',
                            'belum_siap' => 'danger',
                            default      => 'gray',
                        }),
                ]),

            // ─────────────────────────────────────────────────────────────────
            // Section 3 — MP Check status
            // ─────────────────────────────────────────────────────────────────
            Section::make('Status MP Check')
                ->columns(3)
                ->schema([
                    TextEntry::make('mp_check_status')
                        ->label('Status MP Check')
                        ->formatStateUsing(function ($state): string {
                            if ($state instanceof MPCheckStatus) {
                                return $state->label();
                            }

                            return MPCheckStatus::tryFrom((string) $state)?->label()
                                ?? (string) ($state ?? 'Draft');
                        })
                        ->badge()
                        ->color(function ($state): string {
                            $enum = $state instanceof MPCheckStatus
                                ? $state
                                : MPCheckStatus::tryFrom((string) $state);

                            return match ($enum?->value) {
                                'cleared'                  => 'success',
                                'on_check'                 => 'warning',
                                'waiting_action', 'failed' => 'danger',
                                default                    => 'gray',
                            };
                        }),
                ]),

            // ─────────────────────────────────────────────────────────────────
            // Section 4 — Ringkasan Manpower
            // ─────────────────────────────────────────────────────────────────
            Section::make('Ringkasan Manpower')
                ->columns(3)
                ->schema([
                    TextEntry::make('mp_hadir')
                        ->label('Hadir')
                        ->state(fn ($record) => $record->attendances
                            ->where('attendance_status', 'present')->count())
                        ->suffix(fn ($record) => ' / ' . (int) $record->summary_headcount)
                        ->color('success')
                        ->icon('heroicon-o-check-circle'),

                    TextEntry::make('mp_tidak_hadir')
                        ->label('Tidak Hadir / Sakit')
                        ->state(fn ($record) => $record->attendances
                            ->where('attendance_status', '!=', 'present')->count())
                        ->color(fn ($record) => $record->attendances
                            ->where('attendance_status', '!=', 'present')->count() > 0
                            ? 'danger'
                            : 'gray')
                        ->icon('heroicon-o-x-circle'),

                    TextEntry::make('mp_apd_lengkap')
                        ->label('APD Lengkap')
                        ->state(fn ($record) => $record->attendances
                            ->where('attendance_status', 'present')
                            ->where('has_ppe', true)->count())
                        ->color('success')
                        ->icon('heroicon-o-shield-check'),

                    TextEntry::make('mp_siap_kerja')
                        ->label('Siap Kerja')
                        ->state(fn ($record) => $record->attendances
                            ->filter(fn ($a) => $a->final_mp_status === 'Siap Kerja')
                            ->count())
                        ->color('success')
                        ->icon('heroicon-o-check-badge'),

                    TextEntry::make('mp_recheck')
                        ->label('Perlu Recheck')
                        ->state(fn ($record) => $record->attendances
                            ->filter(fn ($a) => in_array($a->final_mp_status, [
                                'Perlu Pemeriksaan Ulang',
                                'APD Tidak Lengkap',
                                'Istirahat 30 Menit',
                            ], true))
                            ->count())
                        ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                        ->icon('heroicon-o-clock'),

                    TextEntry::make('mp_tidak_fit')
                        ->label('Tidak Fit')
                        ->state(fn ($record) => $record->attendances
                            ->filter(fn ($a) => $a->final_mp_status === 'Tidak Fit')
                            ->count())
                        ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                        ->icon('heroicon-o-exclamation-triangle'),
                ]),

            // ─────────────────────────────────────────────────────────────────
            // Section 5 — Ringkasan Kesehatan
            // ─────────────────────────────────────────────────────────────────
            Section::make('Ringkasan Kesehatan')
                ->columns(4)
                ->collapsible()
                ->schema([
                    TextEntry::make('avg_temperature')
                        ->label('Rata-rata Suhu')
                        ->state(function ($record) {
                            $avg = $record->attendances()
                                ->where('attendance_status', 'present')
                                ->whereNotNull('temperature')
                                ->avg('temperature');

                            return $avg ? round($avg, 1) . '°C' : '—';
                        })
                        ->icon('heroicon-o-fire'),

                    TextEntry::make('avg_bp')
                        ->label('Rata-rata Tensi')
                        ->state(function ($record) {
                            $q   = $record->attendances()->where('attendance_status', 'present');
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

            // ─────────────────────────────────────────────────────────────────
            // Section 6 — Stok APD
            // ─────────────────────────────────────────────────────────────────
            Section::make('Stok APD')
                ->schema([
                    RepeatableEntry::make('stockApdChecks')
                        ->label('')
                        ->schema([
                            TextEntry::make('ppe_type')
                                ->label('Jenis APD')
                                ->formatStateUsing(fn ($state) => match (strtolower((string) $state)) {
                                    'helm'          => 'Helm Safety',
                                    'rompi'         => 'Rompi',
                                    'sepatu'        => 'Sepatu Safety',
                                    'sarung_tangan' => 'Sarung Tangan',
                                    default         => (string) $state,
                                })
                                ->weight('bold')
                                ->icon('heroicon-o-shield-check'),

                            TextEntry::make('apd_detail')
                                ->label('Stok · Butuh · Gap')
                                ->state(function ($record) {
                                    $stok  = $record->stock_available  !== null ? $record->stock_available  : '—';
                                    $butuh = $record->required_quantity !== null ? $record->required_quantity : '—';
                                    $gap   = $record->gap !== null
                                        ? ($record->gap > 0 ? "+{$record->gap}" : (string) $record->gap)
                                        : '—';

                                    return "Stok: {$stok}  ·  Butuh: {$butuh}  ·  Gap: {$gap}";
                                })
                                ->color(fn ($state, $record) => match (true) {
                                    $record->gap === null => 'gray',
                                    $record->gap >= 0    => 'success',
                                    default              => 'danger',
                                }),

                            TextEntry::make('computed_status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'cukup'       => 'Cukup',
                                    'kurang'      => 'Kurang',
                                    'belum_diisi' => 'Belum Diisi',
                                    default       => $state ?? '—',
                                })
                                ->color(fn ($state) => match ($state) {
                                    'cukup'  => 'success',
                                    'kurang' => 'danger',
                                    default  => 'gray',
                                }),

                            TextEntry::make('remark')
                                ->label('Catatan')
                                ->placeholder('—'),
                        ])
                        ->columns(4)
                        ->placeholder('Belum ada data stok APD dicatat.'),
                ])
                ->visible(fn ($record) => $record->stockApdChecks->isNotEmpty()),

            // ─────────────────────────────────────────────────────────────────
            // Section 7 — Bukti Briefing
            // ─────────────────────────────────────────────────────────────────
            Section::make('Bukti Briefing')
                ->icon('heroicon-o-photo')
                ->visible(fn ($record) => filled($record?->briefing_evidence_path))
                ->schema([
                    ViewEntry::make('briefing_evidence_path')
                        ->view('components.briefing-evidence'),
                ]),

        ]);
    }

    protected function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);
        $record->load(['attendances', 'stockApdChecks']);
        $record->setRelation(
            'shipments',
            $record->shipments()->with('customer')->withCount('units')->get()
        );
        return $record;
    }

    // ── Header Actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            // Approve — hanya tampil jika belum disetujui
            Action::make('approve')
                ->label('Setujui Briefing')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Setujui Sesi Briefing?')
                ->modalDescription('Tandai sesi ini sebagai sudah ditinjau dan disetujui oleh Super Admin.')
                ->modalSubmitActionLabel('Ya, Setujui')
                ->visible(fn () => $this->record->approved_at === null)
                ->action(function (): void {
                    $this->record->update([
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Sesi briefing berhasil disetujui')
                        ->success()
                        ->send();
                }),

            // Batalkan Persetujuan — hanya tampil jika sudah disetujui
            Action::make('cancel_approve')
                ->label('Batalkan Persetujuan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Batalkan Persetujuan?')
                ->modalDescription('Persetujuan sesi ini akan dibatalkan dan kembali ke status menunggu tinjauan.')
                ->modalSubmitActionLabel('Ya, Batalkan')
                ->visible(fn () => $this->record->approved_at !== null)
                ->action(function (): void {
                    $this->record->update([
                        'approved_at' => null,
                        'approved_by' => null,
                    ]);
                    $this->record->refresh();

                    Notification::make()
                        ->title('Persetujuan sesi briefing dibatalkan')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
