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
            'items',
            'shippingLine',
        ])->findOrFail($record);

        $items = $this->schedule->items()->orderBy('etd')->get();

        $this->rows = $items->map(function ($it) {
            $fmt = 'd-M';
            $etd = optional($it->etd)->format($fmt);
            $eta = optional($it->eta)->format($fmt);

            return [
                'etd'             => $etd ?: '-',
                'eta'             => $eta ?: '-',
                'cargo_plan'      => (string) ((int) ($it->cargo_plan ?? 0)),
                'vessel'          => trim((string) $it->vessel_name) ?: '-',
                'vessel_capacity' => (string) ((int) ($it->vessel_capacity ?? 0)),
                'voyage_no'       => trim((string) $it->voyage_no ?? ''),
                'jss'             => trim((string) $it->jss ?? ''),
                'dwelling'        => (string) ($it->dwelling ?? ''),
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
