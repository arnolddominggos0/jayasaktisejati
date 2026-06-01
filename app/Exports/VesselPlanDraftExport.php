<?php

namespace App\Exports;

use App\Models\VesselPlan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VesselPlanDraftExport implements FromCollection, WithHeadings
{
    public function __construct(protected VesselPlan $plan) {}

    public function collection()
    {
        return $this->plan->items()
            ->with('shippingLine')
            ->get()
            ->map(fn($item) => [
                'Pelayaran' => $item->shippingLine->name,
                'Voyage' => $item->voyage_no,
                'ETD' => $item->planned_etd,
                'ETA' => $item->planned_eta,
                'Catatan' => $item->note,
            ]);
    }

    public function headings(): array
    {
        return ['Pelayaran', 'Voyage', 'ETD', 'ETA', 'Catatan'];
    }
}
