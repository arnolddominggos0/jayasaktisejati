<?php

namespace App\Filament\Widgets;

use App\Models\ShippingScheduleItem;
use App\Support\VesselCode;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class ShippingScheduleCalendar extends Widget
{
    protected static string $view = 'filament.widgets.shipping-schedule-calendar';

    public array $days = [];
    public array $rows = [];

    public function mount(): void
    {
        $filters = request('tableFilters') ?? [];
        $year  = (int) ($filters['year']['value']  ?? now()->year);
        $month = (int) ($filters['month']['value'] ?? now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $this->days[] = $d->toDateString();
        }

        $this->rows = [
            'Origin'               => array_fill_keys($this->days, ''),
            'ETD/Rev'              => array_fill_keys($this->days, ''),
            'ATD'                  => array_fill_keys($this->days, ''),
            'ETA'                  => array_fill_keys($this->days, ''),
            'ATA'                  => array_fill_keys($this->days, ''),
            'JSS Vol. ATD'         => array_fill_keys($this->days, 0),
            'Vol. in RVDC/Branch'  => array_fill_keys($this->days, 0),
        ];

        $items = ShippingScheduleItem::query()
            ->whereHas('schedule', fn($q) => $q->where('state', 'final'))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('etd', [$start, $end])
                    ->orWhereBetween('eta', [$start, $end]);

                if (schema()->hasColumn('shipping_schedule_items', 'atd')) {
                    $q->orWhereBetween('atd', [$start, $end]);
                }
                if (schema()->hasColumn('shipping_schedule_items', 'ata')) {
                    $q->orWhereBetween('ata', [$start, $end]);
                }
            })
            ->orderBy('etd')
            ->get();

        foreach ($items as $it) {
            $code = VesselCode::code($it->vessel_name);
            $voy  = $it->voyage_no ? ' ' . $it->voyage_no : '';
            $chip = "<span class='chip'>{$code}{$voy}</span>";

            if ($it->etd) {
                $d = $it->etd->toDateString();
                if (isset($this->rows['ETD/Rev'][$d])) $this->rows['ETD/Rev'][$d] .= $chip;
            }
            if ($it->eta) {
                $d = $it->eta->toDateString();
                if (isset($this->rows['ETA'][$d])) $this->rows['ETA'][$d] .= $chip;
            }

            $atd = $it->getAttribute('atd');
            if ($atd) {
                $d = ($atd instanceof \Carbon\CarbonInterface) ? $atd->toDateString() : Carbon::parse($atd)->toDateString();
                if (isset($this->rows['ATD'][$d])) $this->rows['ATD'][$d] .= $chip;
                if (isset($this->rows['JSS Vol. ATD'][$d])) $this->rows['JSS Vol. ATD'][$d] += (int) ($it->cargo_plan ?? 0);
            }

            $ata = $it->getAttribute('ata');
            if ($ata) {
                $d = ($ata instanceof \Carbon\CarbonInterface) ? $ata->toDateString() : Carbon::parse($ata)->toDateString();
                if (isset($this->rows['ATA'][$d])) $this->rows['ATA'][$d] .= $chip;
                if (isset($this->rows['Vol. in RVDC/Branch'][$d])) $this->rows['Vol. in RVDC/Branch'][$d] += (int) ($it->cargo_plan ?? 0);
            }
        }
    }
}

if (!function_exists('schema')) {
    function schema()
    {
        return app('db.schema');
    }
}
