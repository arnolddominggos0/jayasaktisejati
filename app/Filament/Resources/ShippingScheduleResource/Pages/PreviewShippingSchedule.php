<?php

namespace App\Filament\Resources\ShippingScheduleResource\Pages;

use App\Filament\Resources\ShippingScheduleResource;
use App\Models\ShippingSchedule;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class PreviewShippingSchedule extends Page
{
    protected static string $resource = ShippingScheduleResource::class;
    protected static string $view = 'exports.schedule_draft';

    public ShippingSchedule $schedule;
    public array $rows = [];

    public function mount($record): void
    {
        $this->schedule = ShippingSchedule::with([
            'customer',
            'pol',
            'pod',
            'items.vessel',
        ])->findOrFail($record);

        $items = $this->schedule->items()->orderBy('etd')->get();

        $this->rows = $items->filter(function ($it) {
            $x = is_array($it->extra) ? $it->extra : [];
            return !(!empty($x['lts'] ?? null) && empty($x['jss'] ?? null));
        })->map(function ($it) {
            $x = is_array($it->extra) ? $it->extra : [];

            $vesselName = $it->vessel->name ?? '';
            $vesselName = preg_replace('~/.*$~', '', (string)$vesselName);
            $vesselName = trim($vesselName);

            $fmt = 'd-M';
            $etd = optional($it->etd)->format($fmt);
            $eta = optional($it->eta)->format($fmt);

            $cap = $x['vessel_capacity'] ?? ($x['capacity'] ?? '');
            $cap = preg_replace('/[^0-9]/', '', (string)$cap);

            $voy = trim((string)($it->voyage_no ?? ''));
            if ($voy === '' || !preg_match('/^\d+$/', str_replace('.', '', $voy))) {
                if (!empty($x['jss']) && preg_match('/VOY\s*([0-9]+)/i', (string)$x['jss'], $m)) {
                    $voy = $m[1];
                } else {
                    $voy = '';
                }
            } else {
                $voy = preg_replace('/[^0-9]/', '', $voy);
            }

            $cargo = trim((string)($x['cargo_plan'] ?? ''));
            $cargo = $cargo === '' ? '' : (string)intval($cargo);
            $jss   = trim((string)($x['jss'] ?? ''));

            return [
                'etd'             => $etd ?: '-',
                'eta'             => $eta ?: '-',
                'cargo_plan'      => $cargo,
                'vessel'          => $vesselName ?: '-',
                'vessel_capacity' => $cap ?: '',
                'voyage_no'       => $voy ?: '',
                'jss'             => $jss ?: '',
                'dwelling'        => (string)($x['dwelling'] ?? ''),
            ];
        })->values()->all();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Preview Draft Jadwal Kapal';
    }

    protected function getViewData(): array
    {
        return [
            'schedule' => $this->schedule,
            'rows' => $this->rows,
        ];
    }
}
