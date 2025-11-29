<?php

namespace Database\Seeders;

use App\Models\TamMonthlySchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TamMonthlyScheduleSeeder extends Seeder
{
    public function run(): void
    {
        TamMonthlySchedule::firstOrCreate(
            ['period_month' => Carbon::create(2025, 11, 1)],
            [
                'version'           => 'v1.0',
                'status'            => 'draft',
                'total_plan'        => 0,
                'draft_message'     => null,
                'generated_by_name' => null,
            ]
        );
    }
}
