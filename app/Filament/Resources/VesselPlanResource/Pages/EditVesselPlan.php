<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Enums\VesselPlanStatus;
use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanScheduleAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanReviewHistory;
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

    // Sprint 4.x — Vessel Plan Workflow Language Alignment: badge status
    // persisten di header, memakai warna dari VesselPlanStatus::color() lewat
    // class mon-badge-* yang sudah ada (tidak membuat style baru), sebagai
    // penguat konteks fase Draft/Sent/Final.
    public function getSubheading(): string | Htmlable | null
    {
        $status = $this->record->status;

        $colorClass = match ($status->color()) {
            'warning' => 'mon-badge-warning',
            'danger' => 'mon-badge-danger',
            'success' => 'mon-badge-success',
            default => 'mon-badge-neutral',
        };

        return new HtmlString(
            '<span class="mon-badge ' . $colorClass . '">' . e($status->label()) . '</span>'
        );
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Eager-load semua relasi yang dibutuhkan Tab 2 (Analysis) dan Tab 3 (History)
        $this->record->loadMissing([
            'items.vessel',
            'items.shippingLine',
            'items.voyage.scheduleHistories',
            'snapshots',
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
        return [VesselPlanScheduleAnalysis::class, VesselPlanReviewHistory::class];
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
                ->visible(fn() => $this->record->isDraft())
                ->disabled(fn() => ! $this->record->canSubmitDraft())
                ->tooltip(fn() => $this->submitDraftDisabledReason())
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
                ->visible(fn() => $this->record->isSent())
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
                ->visible(fn() => $this->record->isSent())
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
                ->visible(fn() => $this->record->isDraft())
                ->requiresConfirmation()
                ->action(fn() => $this->record->delete()),
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
            $data['status']          = VesselPlanStatus::Draft;
            $data['feedback_reason'] = null;
            $data['feedback_by']     = null;
            $data['feedback_at']     = null;
        }

        return $data;
    }
}
