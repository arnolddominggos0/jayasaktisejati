<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\{ShipmentMode, ShipmentStatus};
use App\Filament\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\{Select, DatePicker};
use Illuminate\Support\Carbon;

class ListShipments extends ListRecords
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ShipmentResource\Widgets\ShipmentStats::class,
            ShipmentResource\Widgets\RecentShipmentActivities::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
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

                        // Header CSV
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
                            $prio   = $r->priority ? ucfirst($r->priority) : '-';
                            $cargo  = $r->cargo_type?->label()    ?? (string) $r->cargo_type;
                            $status = $r->status?->label()        ?? (string) $r->status;

                            // Format angka & tanggal
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
                }),

            Actions\CreateAction::make()->label('Buat Permintaan'),
        ];
    }
}
