<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Enums\VesselPlanStatus;
use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanReviewHistory;
use App\Supports\BusinessRouteResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EditVesselPlan extends EditRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected static string $view = 'filament.resources.vessel-plan-resource.pages.edit-vessel-plan';

    /** Shared workspace context; presentation state only. */
    public string $shippingLineFilter = '';

    public function updatedShippingLineFilter(): void
    {
        $this->dispatch('vpFilterShippingLine', value: $this->shippingLineFilter);
    }

    // Identitas plan adalah periodenya, bukan kata "Ubah" — satu customer
    // punya sampai 12 plan per tahun, dibedakan lewat bulan.
    public function getBreadcrumb(): string
    {
        return $this->record->period_month->translatedFormat('F Y');
    }

    // Status adalah atribut plan itu sendiri, bukan atribut Route — badge
    // status karena itu berdampingan dengan judul objek.
    public function getHeading(): string|Htmlable
    {
        $status = $this->record->status;

        $badgeClass = match ($status->color()) {
            'warning' => 'mon-badge-warning',
            'danger' => 'mon-badge-danger',
            'success' => 'mon-badge-success',
            default => 'mon-badge-neutral',
        };

        return new HtmlString(
            e('Vessel Plan — '.$this->record->period_month->translatedFormat('F Y'))
            .' <span class="mon-badge '.$badgeClass.' vp-heading-badge">'.e($status->label()).'</span>'
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        $status = $this->record->status;
        $color = $status->color();

        $rute = BusinessRouteResolver::forPlan($this->record);
        $pelanggan = $this->record->customer?->name ?? '—';

        // Guidance body — satu kalimat natural (em dash), tone hanya pada
        // accent bar kiri; teks tetap neutral supaya terbaca guidance, bukan alert.
        $guidanceTone = match ($color) {
            'warning' => 'vp-hero-next--warning',
            'danger' => 'vp-hero-next--danger',
            'success' => 'vp-hero-next--success',
            default => 'vp-hero-next--neutral',
        };

        // &nbsp; sebelum em dash: dash tidak boleh jatuh di awal baris saat wrap.
        $guidance = match ($status) {
            VesselPlanStatus::Draft => 'Susun jadwal kapal sebelum dikirim ke TAM.',
            VesselPlanStatus::Sent => 'Menunggu Final Schedule dari TAM&nbsp;— sesuaikan ETD, ETA, dan Cargo Plan sebelum finalisasi.',
            // Feedback TAM dikutip langsung di Guidance selama status Revision
            // (bukan hanya terekam di riwayat) supaya Planner tidak perlu
            // mencari instruksi aktifnya sendiri.
            VesselPlanStatus::Revision => $this->revisionGuidance(),
            VesselPlanStatus::Final => 'Vessel Plan telah difinalisasi.',
        };

        return new HtmlString(
            '<div class="vp-document-meta">'
            .'<span>'.e($pelanggan).'</span>'
            .'<span class="vp-document-meta-sep" aria-hidden="true">&bull;</span>'
            .'<span>'.e($rute).'</span>'
            .'</div>'
            // Guidance block: dipisah jelas dari identity, satu-satunya tempat
            // Planner melihat feedback TAM tanpa membuka Log Persetujuan.
            .'<div class="vp-hero-next '.$guidanceTone.'">'
            .'<span class="vp-hero-next-label">Langkah Saat Ini</span>'
            .'<span class="vp-hero-next-body">'.$guidance.'</span>'
            .'</div>'
        );
    }

    // Feedback dipotong supaya Hero tidak melebar; versi lengkap tetap
    // tersimpan permanen di Log Persetujuan setelah siklus revisi ini ditutup.
    protected function revisionGuidance(): string
    {
        $feedback = trim((string) $this->record->feedback_reason);

        if ($feedback === '') {
            return 'Revisi jadwal sesuai feedback dari TAM&nbsp;— kirim kembali setelah selesai.';
        }

        $feedback = Str::limit($feedback, 90);

        return 'TAM meminta revisi: &ldquo;'.e($feedback).'&rdquo;&nbsp;— sesuaikan lalu kirim kembali.';
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

    protected function getFooterWidgets(): array
    {
        return [
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
