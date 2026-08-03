<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Enums\VesselPlanStatus;
use App\Filament\Resources\VesselPlanResource;
use App\Supports\BusinessRouteResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class EditVesselPlan extends EditRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected static string $view = 'filament.resources.vessel-plan-resource.pages.edit-vessel-plan';

    public string $shippingLineFilter = '';

    public function updatedShippingLineFilter(): void
    {
        $this->dispatch('vpFilterShippingLine', value: $this->shippingLineFilter);
    }

    public function getBreadcrumb(): string
    {
        return $this->record->period_month->translatedFormat('F Y');
    }

    public function getHeading(): string|Htmlable
    {
        $status = $this->record->status;

        $badge = Blade::render(
            '<x-filament::badge :color="$color" size="sm" class="vp-heading-badge">{{ $label }}</x-filament::badge>',
            ['color' => $status->color(), 'label' => $status->label()]
        );

        return new HtmlString(
            e('Vessel Plan — '.$this->record->period_month->translatedFormat('F Y')).' '.$badge
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        $rute = BusinessRouteResolver::forPlan($this->record);
        $periode = $this->record->period_month->translatedFormat('F Y');

        return new HtmlString(
            '<div class="vp-document-meta">'
            .'<span>'.e($rute).'</span>'
            .'<span class="vp-document-meta-sep" aria-hidden="true">&bull;</span>'
            .'<span>'.e($periode).'</span>'
            .'</div>'
        );
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

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

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function distributionStatus(): array
    {
        return match (true) {
            $this->record->isSent()     => ['Menunggu Review', 'warning'],
            $this->record->isRevision() => ['Perlu Revisi', 'danger'],
            $this->record->isFinal()    => ['Disetujui Customer', 'success'],
            default                     => ['Belum Dikirim', 'gray'],
        };
    }

    public function submitDraftAction(): Action
    {
        return Action::make('submitDraft')
            ->label(fn () => filled($this->record->sent_at) ? 'Kirim Draft Lagi' : 'Kirim Draft')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            // Tampil pada Draft dan Revision. Pada Revision tombol sengaja
            // tampil TAPI nonaktif (lihat submitDraftDisabledReason): operator
            // perlu tahu langkah berikutnya, sementara service tetap hanya
            // menerima status Draft — workflow tidak diubah.
            ->visible(fn () => $this->record->isDraft() || $this->record->isRevision())
            ->disabled(fn () => ! $this->record->canSubmitDraft())
            ->tooltip(fn () => $this->submitDraftDisabledReason())
            ->requiresConfirmation()
            ->modalHeading('Kirim Draft Vessel Plan')
            ->modalDescription(fn () => sprintf(
                'Draft akan dikirim via WhatsApp ke customer yang terhubung dengan vessel plan ini: %s.',
                $this->record->customer?->name ?? 'belum ada customer terhubung'
            ))
            ->modalSubmitActionLabel('Kirim Draft')
            ->action(function () {
                $this->record->submitDraft(auth()->id());

                Notification::make()
                    ->title('Draft terkirim')
                    ->body('Snapshot draft berhasil disimpan.')
                    ->success()
                    ->send();

                $waUrl = $this->record->waUrl();
                if ($waUrl) {
                    $this->redirect($waUrl);
                }
            });
    }

    /**
     * Header hanya memuat lifecycle JADWAL: finalisasi, revisi, hapus.
     */
    protected function getHeaderActions(): array
    {
        return [
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
                ->modalHeading('Hapus Vessel Plan')
                ->modalDescription('Vessel plan beserta jadwal kapal di dalamnya akan dihapus. Tindakan ini tidak dapat dibatalkan.')
                ->action(function () {
                    $this->record->delete();

                    Notification::make()
                        ->title('Vessel Plan dihapus')
                        ->success()
                        ->send();

                    // Wajib redirect: halaman Edit beserta Relation Manager-nya
                    // masih ter-mount setelah action selesai, dan akan mencoba
                    // render ulang terhadap record yang sudah tidak ada.
                    $this->redirect(VesselPlanResource::getUrl('index'), navigate: false);
                }),
        ];
    }

    protected function submitDraftDisabledReason(): string
    {
        if ($this->record->isRevision()) {
            return 'Simpan perbaikan jadwal terlebih dahulu, lalu draft dapat dikirim ulang.';
        }

        if ($this->record->items()->count() === 0) {
            return 'Tambahkan rencana kapal terlebih dahulu.';
        }

        if (! $this->record->customer_id) {
            return 'Hubungkan customer ke vessel plan terlebih dahulu.';
        }

        if (! $this->record->hasWhatsappRecipient()) {
            return 'Nomor WhatsApp customer belum tersedia.';
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
