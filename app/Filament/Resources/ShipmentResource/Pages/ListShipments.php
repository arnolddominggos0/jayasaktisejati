<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\{ShipmentMode, ShipmentStatus};
use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\{Select, DatePicker};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    /**
     * UX-LIST-02 — tab memakai mesin native ListRecords (activeTab +
     * modifyQueryWithActiveTab) sehingga benar-benar menyaring query.
     * Redeklarasi dengan #[Url] agar state bertahan di URL dengan nama
     * param lama (?tab=) dari shell WS-01A.
     */
    #[Url(as: 'tab', except: 'semua')]
    public ?string $activeTab = null;

    // WS-01A — Administrative Workspace shell: custom view membungkus
    // tab navigasi + workspace box (toolbar → table) di satu surface.
    protected static string $view = 'filament.resources.shipment-resource.pages.list-shipments';

    public function getTitle(): string
    {
        return 'Permintaan Pengiriman';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola seluruh permintaan pengiriman sebelum diproses operasional.';
    }

    /**
     * UX-LIST-02 — definisi bisnis tab (hasil audit yang disetujui).
     * Semua turunan ShipmentStatus yang ada; tanpa status baru:
     *
     *   Semua                → tanpa filter (baseline lookup)
     *   Menunggu Penjemputan → status = pending DAN belum ada track nyata
     *                          (belum disentuh FC via startPickup — skeleton
     *                          track ber-tracked_at NULL tidak dihitung)
     *   Perlu Tindakan       → status = hold. Cancelled SENGAJA belum masuk:
     *                          belum ada penanda follow-up di skema untuk
     *                          membedakan "batal perlu tindak lanjut" vs
     *                          "batal selesai" (catatan audit UX-LIST-02).
     */
    public function getTabs(): array
    {
        $base = fn (): Builder => ShipmentResource::getEloquentQuery();

        return [
            'semua' => Tab::make('Semua'),

            'menunggu-penjemputan' => Tab::make('Menunggu Penjemputan')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', ShipmentStatus::Pending->value)
                    ->whereDoesntHave('tracks', fn (Builder $trackQuery) => $trackQuery->whereNotNull('tracked_at')))
                ->badge(fn () => $base()
                    ->where('status', ShipmentStatus::Pending->value)
                    ->whereDoesntHave('tracks', fn (Builder $trackQuery) => $trackQuery->whereNotNull('tracked_at'))
                    ->count() ?: null),

            'perlu-tindakan' => Tab::make('Perlu Tindakan')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', ShipmentStatus::Hold->value))
                ->badge(fn () => $base()->where('status', ShipmentStatus::Hold->value)->count() ?: null),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Permintaan')
                ->icon('heroicon-m-plus'),
        ];
    }

    /**
     * Export CSV — dipindah dari page header (header hanya boleh memuat satu
     * primary action per spec WS-01) ke toolbar workspace; dirender di view
     * via {{ $this->exportAction }}. Logic tidak berubah.
     */
    public function exportAction(): Action
    {
        return Action::make('export')
            ->label('Export CSV')
            ->icon('heroicon-m-arrow-down-tray')
            ->color('gray')
            ->form([
                Select::make('mode')
                    ->label('Moda')
                    ->options([
                        ShipmentMode::Sea->value  => 'Laut',
                        ShipmentMode::Land->value => 'Darat',
                    ])
                    ->native(false)
                    ->placeholder('Semua'),

                Select::make('status')
                    ->label('Status')
                    ->options(collect(ShipmentStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                    ->native(false)
                    ->placeholder('Semua'),

                DatePicker::make('from')->label('Dari Tanggal Dibuat')->native(false),
                DatePicker::make('to')->label('Sampai Tanggal Dibuat')->native(false),
            ])
            ->action(function (array $data) {
                $q = Shipment::query()
                    ->with(['customer', 'receiver', 'originCity', 'destinationCity']);

                if (!empty($data['mode'])) {
                    $q->where('mode', $data['mode']);
                }
                if (!empty($data['status'])) {
                    $q->where('status', $data['status']);
                }
                if (!empty($data['from'])) {
                    $q->whereDate('created_at', '>=', $data['from']);
                }
                if (!empty($data['to'])) {
                    $q->whereDate('created_at', '<=', $data['to']);
                }

                $rows = $q->orderByDesc('created_at')->limit(10000)->get();

                if ($rows->isEmpty()) {
                    Notification::make()
                        ->title('Tidak ada data untuk diekspor')
                        ->warning()
                        ->send();
                    return;
                }

                $filename = 'shipments-' . now()->format('Ymd-His') . '.csv';

                return response()->streamDownload(function () use ($rows) {
                    $out = fopen('php://output', 'w');

                    fputcsv($out, [
                        'Kode',
                        'Pengirim',
                        'Penerima',
                        'Asal',
                        'Tujuan',
                        'Moda',
                        'Layanan',
                        'Opsi',
                        'Cakupan',
                        'Prioritas',
                        'Muatan',
                        'Koli',
                        'CBM',
                        'Berat (kg)',
                        'Status',
                        'ETD',
                        'ETA',
                        'Dibuat'
                    ]);

                    foreach ($rows as $r) {
                        $mode   = $r->mode?->label()         ?? (string) $r->mode;
                        $stype  = $r->service_type?->label()  ?? (string) $r->service_type;
                        $opt    = (string) $r->service_option ?: '-';
                        $scope  = $r->delivery_scope?->label() ?? (string) $r->delivery_scope ?: '-';
                        $prioMap = [
                            'high'   => 'Tinggi',
                            'normal' => 'Normal',
                            'low'    => 'Rendah',
                            'urgent' => 'Mendesak',
                        ];
                        $prio   = $r->priority ? ($prioMap[strtolower($r->priority)] ?? ucfirst($r->priority)) : '-';
                        $cargo  = $r->cargo_type?->label()    ?? (string) $r->cargo_type;
                        $status = $r->status?->label()        ?? (string) $r->status;

                        $cbm   = is_null($r->cbm_total)   ? null : number_format((float) $r->cbm_total, 3, '.', '');
                        $wkg   = is_null($r->weight_total) ? null : number_format((float) $r->weight_total, 2, '.', '');
                        $etd   = $r->etd ? Carbon::parse($r->etd)->format('d M Y H:i') : null;
                        $eta   = $r->eta ? Carbon::parse($r->eta)->format('d M Y H:i') : null;
                        $cdate = $r->created_at ? Carbon::parse($r->created_at)->format('d M Y H:i') : null;

                        fputcsv($out, [
                            $r->code,
                            $r->customer->name    ?? '-',
                            $r->receiver->name    ?? '-',
                            $r->originCity->name  ?? '-',
                            $r->destinationCity->name ?? '-',
                            $mode,
                            $stype,
                            $opt,
                            $scope,
                            $prio,
                            $cargo,
                            $r->packages_total,
                            $cbm,
                            $wkg,
                            $status,
                            $etd,
                            $eta,
                            $cdate,
                        ]);
                    }

                    fclose($out);
                }, $filename, ['Content-Type' => 'text/csv']);
            });
    }
}
