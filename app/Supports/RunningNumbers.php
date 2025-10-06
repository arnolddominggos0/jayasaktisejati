<?php

namespace App\Supports;

use Illuminate\Support\Facades\DB;

class RunningNumber
{
    public static function next(string $series): int
    {
        return DB::transaction(function () use ($series) {
            $row = DB::table('running_numbers')->where('series', $series)->lockForUpdate()->first();

            if (! $row) {
                DB::table('running_numbers')->insert([
                    'series' => $series,
                    'value' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 1;
            }

            $val = (int) $row->value + 1;
            DB::table('running_numbers')->where('id', $row->id)->update([
                'value' => $val,
                'updated_at' => now(),
            ]);

            return $val;
        });
    }
}
