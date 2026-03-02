<?php

namespace Database\Seeders;

use App\Models\Port;
use App\Models\Vessel;
use App\Models\ShippingLine;
use App\Models\VesselPlan;
use App\Models\VesselPlanItem;
use App\Enums\VesselPlanStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VesselPlanDummySeeder extends Seeder
{
    public function run(): void
    {
        $period = Carbon::parse('2026-03-01')->startOfMonth();

        $pol = Port::where('code', 'JKT')->first();
        $pod = Port::where('code', 'BTG')->first();

        if (!$pol || !$pod) {
            $this->command->error('Port JKT / BTG belum ada.');
            return;
        }

        $shippingLine = ShippingLine::first();
        $vessel = Vessel::first();

        if (!$shippingLine || !$vessel) {
            $this->command->error('ShippingLine / Vessel belum ada.');
            return;
        }

        $plan = VesselPlan::updateOrCreate(
            [
                'period_month' => $period,
                'route_code'   => "{$pol->code}-{$pod->code}",
            ],
            [
                'pol_id' => $pol->id,
                'pod_id' => $pod->id,
                'status' => VesselPlanStatus::Draft,
            ]
        );

        $plan->items()->delete();

        $etdDates = [
            3,
            9,
            15,
            21,
            27,
        ];

        foreach ($etdDates as $index => $day) {

            $etd = $period->copy()->day($day);
            $eta = $etd->copy()->addDays(5);

            VesselPlanItem::create([
                'vessel_plan_id'   => $plan->id,
                'shipping_line_id' => $shippingLine->id,
                'vessel_id'        => $vessel->id,
                'planned_etd'      => $etd,
                'planned_eta'      => $eta,
                'note'             => 'Dummy Schedule #' . ($index + 1),
            ]);
        }

        $plan->update([
            'status'  => VesselPlanStatus::Sent,
            'sent_at' => now(),
            'sent_by' => 1,
        ]);

        $this->command->info('Vessel Plan Dummy Created.');
    }
}
