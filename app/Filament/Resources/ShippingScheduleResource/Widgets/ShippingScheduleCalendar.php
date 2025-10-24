<?php

namespace App\Filament\Widgets;

use App\Models\ShippingScheduleItem;
use App\Supports\VesselCode;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class ShippingScheduleCalendar extends Widget
{
    protected static string $view = 'filament.widgets.shipping-schedule-calendar';
    protected int | string | array $columnSpan = 'full';

    public array $days = [];
    public array $vessels = [];
    public array $weekStats = [];
    public string $periodLabel = '';
    public int $year;
    public int $month;
    public Carbon $startDate;
    public Carbon $endDate;
    public int $totalDays;
    public int $totalVessels = 0;
    public int $totalCapacity = 0;

    public function mount(): void
    {
        $filters = request('tableFilters') ?? [];
        $this->year  = (int) ($filters['year']['value']  ?? now()->year);
        $this->month = (int) ($filters['month']['value'] ?? now()->month);

        $this->startDate = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $this->endDate   = (clone $this->startDate)->endOfMonth();
        $this->totalDays = $this->startDate->daysInMonth;
        $this->periodLabel = $this->startDate->translatedFormat('F Y');

        for ($d = $this->startDate->copy(); $d->lte($this->endDate); $d->addDay()) {
            $this->days[] = [
                'date' => $d->toDateString(),
                'day' => (int) $d->format('d'),
                'dayName' => $d->translatedFormat('D'),
                'isWeekend' => $d->isWeekend(),
                'isToday' => $d->isToday(),
            ];
        }

        $this->weekStats = [
            1 => ['vessels' => 0, 'capacity' => 0, 'range' => '1-7'],
            2 => ['vessels' => 0, 'capacity' => 0, 'range' => '8-14'],
            3 => ['vessels' => 0, 'capacity' => 0, 'range' => '15-21'],
            4 => ['vessels' => 0, 'capacity' => 0, 'range' => '22-' . $this->totalDays],
        ];

        $items = ShippingScheduleItem::query()
            ->whereHas('schedule', fn($q) => $q->where('state', 'final'))
            ->where(function ($q) {
                $q->whereBetween('etd', [$this->startDate, $this->endDate])
                    ->orWhereBetween('eta', [$this->startDate, $this->endDate]);
            })
            ->with('schedule')
            ->orderBy('etd')
            ->orderBy('eta')
            ->get();

        foreach ($items as $it) {
            if (!$it->etd || !$it->eta) continue;

            $etd = $it->etd->copy();
            $eta = $it->eta->copy();

            $etdDay = $etd->lt($this->startDate) ? 1 : $etd->day;
            $etaDay = $eta->gt($this->endDate) ? $this->totalDays : $eta->day;

            if ($etd->gt($this->endDate) || $eta->lt($this->startDate)) {
                continue;
            }

            $cargo = (int) ($it->cargo_plan ?? 0);

            $this->vessels[] = [
                'code' => VesselCode::code($it->vessel_name),
                'name' => $it->vessel_name,
                'voyage' => $it->voyage_no,
                'cargo' => $cargo,
                'etd' => $etd,
                'eta' => $eta,
                'etd_day' => $etdDay,
                'eta_day' => $etaDay,
                'duration' => $etaDay - $etdDay + 1,
                'start_offset' => $etdDay - 1,
            ];

            $this->totalVessels++;
            $this->totalCapacity += $cargo;

            if ($etd->gte($this->startDate) && $etd->lte($this->endDate)) {
                $weekNum = (int) ceil($etd->day / 7);
                if ($weekNum > 4) $weekNum = 4;
                $this->weekStats[$weekNum]['vessels']++;
                $this->weekStats[$weekNum]['capacity'] += $cargo;
            }
        }
    }
}
