<?php

namespace App\Filament\Pages\Dashboard\Widgets;

use App\Models\Shipment;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LeadTimeCustomerWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static ?string $heading = 'Lead Time Evaluation (Sea) per Customer';

    protected static string $view = 'filament.pages.dashboard.widgets.lead-time-customer';

    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    private const ST = [
        'handover_any'   => ['handover', 'handover_depo', 'handover_depo_jss', 'handover_port'],
        'depart_any'     => ['vessel_depart', 'vessel_loading_complete', 'onship', 'on_ship', 'dimuat_kapal', 'dimuat_di_kapal'],
        'arrive_any'     => ['vessel_arrive', 'arrival', 'kapal_tiba', 'vessel_discharge_start', 'discharge_start'],
        'delivered_any'  => ['delivery_to_customer', 'delivered', 'done', 'completed', 'dooring_done'],
    ];

    private const SLA = [
        'dwelling' => 2,
        'sailing'  => 14,
        'dooring'  => 3,
        'total'    => 20,
    ];

    public ?int $customer_id = null;
    public ?string $from = null;
    public ?string $to   = null;

    public array $stats = [
        'dwelling' => ['ok' => 0, 'ng' => 0],
        'sailing'  => ['ok' => 0, 'ng' => 0],
        'dooring'  => ['ok' => 0, 'ng' => 0],
        'total'    => ['ok' => 0, 'ng' => 0],
    ];

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->to   = Carbon::now()->endOfMonth()->toDateString();
        $this->refreshStats();

        $this->form->fill([
            'customer_id' => $this->customer_id,
            'from' => $this->from,
            'to'   => $this->to,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(['default' => 3])->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn() => Customer::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->preload()->live()
                    ->afterStateUpdated(fn() => $this->applyForm()),
                Forms\Components\DatePicker::make('from')->label('Dari')->native(false)->closeOnDateSelection()
                    ->live()->afterStateUpdated(fn() => $this->applyForm()),
                Forms\Components\DatePicker::make('to')->label('Sampai')->native(false)->closeOnDateSelection()
                    ->live()->afterStateUpdated(fn() => $this->applyForm()),
            ]),
        ];
    }

    public function applyForm(): void
    {
        $s = $this->form->getState();
        $this->customer_id = $s['customer_id'] ?? null;
        $this->from = $s['from'] ?? $this->from;
        $this->to   = $s['to']   ?? $this->to;
        $this->refreshStats();
    }

    private function quoteList(array $values): string
    {
        return collect($values)
            ->map(fn($v) => str_replace("'", "''", (string) $v))
            ->map(fn($v) => "'{$v}'")
            ->implode(',');
    }


    public function refreshStats(): void
    {
        $from = $this->from ? Carbon::parse($this->from)->startOfDay() : null;
        $to   = $this->to   ? Carbon::parse($this->to)->endOfDay()   : null;

        $handoverList  = $this->quoteList(self::ST['handover_any']);
        $departList    = $this->quoteList(self::ST['depart_any']);
        $arriveList    = $this->quoteList(self::ST['arrive_any']);
        $deliveredList = $this->quoteList(self::ST['delivered_any']);

        $times = DB::table('shipment_tracks')
            ->selectRaw("
            shipment_id,
            MAX(tracked_at) FILTER (WHERE status IN ($handoverList))   AS handover_at,
            MAX(tracked_at) FILTER (WHERE status IN ($departList))     AS depart_at,
            MAX(tracked_at) FILTER (WHERE status IN ($arriveList))     AS arrive_at,
            MAX(tracked_at) FILTER (WHERE status IN ($deliveredList))  AS delivered_at
        ")
            ->when($from && $to, fn($q) => $q->whereBetween('tracked_at', [$from, $to]))
            ->groupBy('shipment_id');

        $ship = Shipment::query()
            ->when(Schema::hasColumn('shipments', 'mode'), fn($q) => $q->where('mode', 'sea'))
            ->when($this->customer_id, fn($q) => $q->where('customer_id', $this->customer_id));

        $rows = $ship
            ->joinSub($times, 't', 't.shipment_id', '=', 'shipments.id')
            ->get(['t.handover_at', 't.depart_at', 't.arrive_at', 't.delivered_at']);

        $acc = [
            'dwelling' => ['ok' => 0, 'ng' => 0],
            'sailing'  => ['ok' => 0, 'ng' => 0],
            'dooring'  => ['ok' => 0, 'ng' => 0],
            'total'    => ['ok' => 0, 'ng' => 0],
        ];

        foreach ($rows as $r) {
            $dw = ($r->handover_at && $r->depart_at)    ? Carbon::parse($r->handover_at)->diffInHours($r->depart_at, false)    : null;
            $sa = ($r->depart_at && $r->arrive_at)      ? Carbon::parse($r->depart_at)->diffInHours($r->arrive_at, false)      : null;
            $dr = ($r->arrive_at && $r->delivered_at)   ? Carbon::parse($r->arrive_at)->diffInHours($r->delivered_at, false)   : null;
            $tt = ($r->handover_at && $r->delivered_at) ? Carbon::parse($r->handover_at)->diffInHours($r->delivered_at, false) : null;

            $this->bump($acc['dwelling'], $dw, self::SLA['dwelling']);
            $this->bump($acc['sailing'],  $sa, self::SLA['sailing']);
            $this->bump($acc['dooring'],  $dr, self::SLA['dooring']);
            $this->bump($acc['total'],    $tt, self::SLA['total']);
        }

        $this->stats = $acc;
    }


    private function bump(array &$bucket, ?int $hours, int $slaDays): void
    {
        if ($hours === null) return;
        $limit = $slaDays * 24;
        $hours <= $limit ? $bucket['ok']++ : $bucket['ng']++;
    }
}
