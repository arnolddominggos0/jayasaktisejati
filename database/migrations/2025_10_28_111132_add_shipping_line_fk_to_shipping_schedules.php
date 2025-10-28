<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $t) {
            $t->foreignId('shipping_line_id')->nullable()->after('id');
        });

        $codeIsNotNull = false;
        if (Schema::hasColumn('shipping_lines', 'code')) {
            $col = DB::selectOne("
                SELECT is_nullable
                FROM information_schema.columns
                WHERE table_name = 'shipping_lines' AND column_name = 'code'
            ");
            $codeIsNotNull = isset($col->is_nullable) && strtoupper($col->is_nullable) === 'NO';
        }

        $genCode = function (string $prefix) {
            $prefix = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', $prefix));
            if ($prefix === '') $prefix = 'UNK';
            $base = $prefix;
            $n = DB::table('shipping_lines')->where('code', 'like', $base . '%')->count() + 1;
            return $base . '-' . $n;
        };

        $unknownId = DB::table('shipping_lines')->where('name', 'Unknown Line')->value('id');
        if (!$unknownId) {
            $insert = [
                'name'       => 'Unknown Line',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('shipping_lines', 'code')) {
                $insert['code'] = $codeIsNotNull ? $genCode('UNKNOWN') : null;
            }
            if (Schema::hasColumn('shipping_lines', 'contact')) $insert['contact'] = null;
            if (Schema::hasColumn('shipping_lines', 'phone'))   $insert['phone']   = null;
            if (Schema::hasColumn('shipping_lines', 'email'))   $insert['email']   = null;

            $unknownId = DB::table('shipping_lines')->insertGetId($insert);
        }

        $maps = [
            'meratus' => 'Meratus Line',
            'tanto'   => 'Tanto Intim Line',
        ];

        foreach ($maps as $needle => $lineName) {
            $lineId = DB::table('shipping_lines')->where('name', $lineName)->value('id');
            if (!$lineId) {
                $insert = [
                    'name'       => $lineName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (Schema::hasColumn('shipping_lines', 'code')) {
                    // Kalau code NOT NULL, bikin dari nama
                    $insert['code'] = $codeIsNotNull ? $genCode($lineName) : null;
                }
                if (Schema::hasColumn('shipping_lines', 'contact')) $insert['contact'] = null;
                if (Schema::hasColumn('shipping_lines', 'phone'))   $insert['phone']   = null;
                if (Schema::hasColumn('shipping_lines', 'email'))   $insert['email']   = null;

                $lineId = DB::table('shipping_lines')->insertGetId($insert);
            }

            DB::table('shipping_schedules')
                ->whereNull('shipping_line_id')
                ->where('vessel_name', 'ilike', "%{$needle}%")
                ->update(['shipping_line_id' => $lineId]);
        }

        DB::table('shipping_schedules')
            ->whereNull('shipping_line_id')
            ->update(['shipping_line_id' => $unknownId]);

        Schema::table('shipping_schedules', function (Blueprint $t) {
            $t->foreignId('shipping_line_id')->nullable(false)->change();
            $t->foreign('shipping_line_id')
                ->references('id')->on('shipping_lines')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $t) {
            $t->dropForeign(['shipping_line_id']);
            $t->dropColumn('shipping_line_id');
        });
    }
};
