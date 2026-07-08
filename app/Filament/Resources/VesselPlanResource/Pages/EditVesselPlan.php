<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Enums\VesselPlanStatus;
use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanReviewHistory;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanScheduleAnalysis;
use App\Supports\BusinessRouteResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditVesselPlan extends EditRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected static string $view = 'filament.resources.vessel-plan-resource.pages.edit-vessel-plan';

    // Sprint 4.x — Vessel Plan Workspace UX Refinement: header kontekstual.
    // Identitas plan adalah periodenya, bukan kata "Ubah" — Planner mengelola
    // 12 plan per tahun dan membedakannya lewat bulan.

    public function getBreadcrumb(): string
    {
        return $this->record->period_month->translatedFormat('F Y');
    }

    public function getHeading(): string|Htmlable
    {
        return 'Vessel Plan — '.$this->record->period_month->translatedFormat('F Y');
    }

    // Sprint 13.7 — Hero DESIGN FREEZE (final).
    // Struktur ini tidak boleh didesain ulang kecuali ada usability issue nyata.
    // Heading Filament ("Vessel Plan — Juli 2026") tetap primary headline.
    // Subheading = 2 zona:
    //   Identitas : Route + badge status inline (1 baris) → Customer · N Jadwal.
    //               Route sekunder terhadap heading (15px), badge menempel ke
    //               route sehingga status terbaca sebagai atribut identitas.
    //   Guidance  : "Langkah Saat Ini" — accent bar kiri 2px bertint status,
    //               teks neutral (guidance, bukan alert), satu kalimat em dash.
    // Layout 2-column & posisi Header Actions = native Filament, tidak diubah.
    // Styling: theme.css blok "Sprint 13.7 - Hero Composition (DESIGN FREEZE)".
    public function getSubheading(): string|Htmlable|null
    {
        $status = $this->record->status;
        $color = $status->color();

        $badgeClass = match ($color) {
            'warning' => 'mon-badge-warning',
            'danger' => 'mon-badge-danger',
            'success' => 'mon-badge-success',
            default => 'mon-badge-neutral',
        };

        $rute = BusinessRouteResolver::forPlan($this->record);
        $pelanggan = $this->record->customer?->name ?? '—';
        $count = $this->record->items->count();

        // Guidance body — satu kalimat natural (em dash), tone hanya pada
        // accent bar kiri; teks tetap neutral supaya terbaca guidance, bukan alert.
        $guidanceTone = match ($color) {
            'warning' => 'vp-hero-next--warning',
            'danger' => 'vp-hero-next--danger',
            'success' => 'vp-hero-next--success',
            default => 'vp-hero-next--neutral',
        };

        $guidance = match ($status) {
            VesselPlanStatus::Draft => 'Susun jadwal kapal sebelum dikirim ke TAM.',
            // &nbsp; sebelum em dash: dash tidak boleh jatuh di awal baris saat wrap.
            VesselPlanStatus::Sent => 'Menunggu Final Schedule dari TAM&nbsp;— sesuaikan ETD, ETA, dan Cargo Plan sebelum finalisasi.',
            VesselPlanStatus::Revision => 'Revisi jadwal sesuai feedback dari TAM&nbsp;— kirim kembali setelah selesai.',
            VesselPlanStatus::Final => 'Vessel Plan telah difinalisasi.',
        };

        return new HtmlString(
            '<div class="vp-hero">'
            // Identity block: Route + badge inline, lalu Customer•Jadwal (1 blok rapat)
            .'<div class="vp-hero-identity">'
            .'<div class="vp-hero-title-row">'
            .'<span class="vp-hero-route">'.e($rute).'</span>'
            .'<span class="mon-badge '.$badgeClass.'">'.e($status->label()).'</span>'
            .'</div>'
            .'<div class="vp-hero-meta">'
            .'<span class="vp-hero-meta-customer">'.e($pelanggan).'</span>'
            .' <span class="vp-hero-meta-sep" aria-hidden="true">&middot;</span> '
            .'<span class="vp-hero-meta-count">'.e($count.' Jadwal').'</span>'
            .'</div>'
            .'</div>'
            // Guidance block: dipisah jelas dari identity
            .'<div class="vp-hero-next '.$guidanceTone.'">'
            .'<span class="vp-hero-next-label">Langkah Saat Ini</span>'
            .'<span class="vp-hero-next-body">'.$guidance.'</span>'
            .'</div>'
            .'</div>'
        );
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Eager-load relasi Tab 2/3 + identitas Hero (pol/pod/customer)
        // agar metadata rail tidak trigger lazy-load saat render subheading.
        $this->record->loadMissing([
            'items.vessel',
            'items.shippingLine',
            'items.voyage.scheduleHistories',
            'snapshots',
            'pol',
            'pod',
            'customer',
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [VesselPlanAnalysis::class];
    }

    public function getHeaderWidgetsColumns(): int
    {
        return 1;
    }

    // 3. Analisa Jadwal Kapal  4. Riwayat Review — below the form
    protected function getFooterWidgets(): array
    {
        return [
            // VesselPlanScheduleAnalysis::class,
            VesselPlanReviewHistory::class,
        ];
    }

    public function getFooterWidgetsColumns(): int
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submitDraft')
                ->label('Kirim ke TAM (WhatsApp)')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->isDraft())
                ->disabled(fn () => ! $this->record->canSubmitDraft())
                ->tooltip(fn () => $this->submitDraftDisabledReason())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->submitDraft(auth()->id());

                    Notification::make()
                        ->title('Draft Dikirim ke TAM')
                        ->body('Snapshot draft berhasil disimpan.')
                        ->success()
                        ->send();

                    $waUrl = $this->record->waUrl();
                    if ($waUrl) {
                        $this->redirect($waUrl);
                    }
                }),

            Action::make('finalize')
                ->label('Setujui & Finalisasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->isSent())
                ->requiresConfirmation()
                ->action(function () {
                    $count = $this->record->finalizeSchedule(auth()->id());

                    Notification::make()
                        ->title('Vessel Plan Disetujui')
                        ->body("Snapshot final disimpan dan {$count} voyage disinkronkan.")
                        ->success()
                        ->send();
                }),

            Action::make('reject')
                ->label('Tolak / Kembalikan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->outlined()
                ->visible(fn () => $this->record->isSent())
                ->form([
                    Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(4),
                ])
                ->requiresConfirmation()
                ->action(function ($record, array $data) {
                    $record->reject($data['reason'], auth()->id());

                    Notification::make()
                        ->title('Vessel Plan Ditolak')
                        ->warning()
                        ->send();
                }),

            Action::make('hapus')
                ->label('Hapus Vessel Plan')
                ->color('danger')
                ->outlined()
                ->visible(fn () => $this->record->isDraft())
                ->requiresConfirmation()
                ->action(fn () => $this->record->delete()),
        ];
    }

    protected function submitDraftDisabledReason(): string
    {
        if ($this->record->items()->count() === 0) {
            return 'Tambahkan rencana kapal terlebih dahulu.';
        }

        if (! $this->record->customer_id) {
            return 'Customer TAM belum terhubung ke vessel plan.';
        }

        if (! $this->record->hasWhatsappRecipient()) {
            return 'Nomor WhatsApp customer TAM belum tersedia.';
        }

        return '';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->isRevision()) {
            $data['status'] = VesselPlanStatus::Draft;
            $data['feedback_reason'] = null;
            $data['feedback_by'] = null;
            $data['feedback_at'] = null;
        }

        return $data;
    }
}
