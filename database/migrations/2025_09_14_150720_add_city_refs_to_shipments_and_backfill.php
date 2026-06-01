<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('offices') && Schema::hasTable('cities')) {
            $names = DB::table('offices')->select('city')->whereNotNull('city')->distinct()->pluck('city');
            foreach ($names as $name) {
                $slug = Str::slug($name);
                DB::table('cities')->updateOrInsert(
                    ['slug' => $slug],
                    ['name' => $name, 'country' => 'Indonesia', 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'origin_city_id')) {
                $table->foreignId('origin_city_id')->nullable();
            }
            if (!Schema::hasColumn('shipments', 'destination_city_id')) {
                $table->foreignId('destination_city_id')->nullable();
            }
        });

        if (Schema::hasTable('offices') && Schema::hasTable('cities')) {
            DB::statement("
                UPDATE shipments s
                SET origin_city_id = c.id
                FROM offices o
                JOIN cities c ON LOWER(c.name) = LOWER(o.city)
                WHERE s.origin_office_id = o.id
                  AND s.origin_city_id IS NULL
            ");
            DB::statement("
                UPDATE shipments s
                SET destination_city_id = c.id
                FROM offices o
                JOIN cities c ON LOWER(c.name) = LOWER(o.city)
                WHERE s.destination_office_id = o.id
                  AND s.destination_city_id IS NULL
            ");
        }

        if (Schema::hasTable('cities')) {
            DB::statement("
                DO $$
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'shipments_origin_city_id_foreign') THEN
                        ALTER TABLE shipments
                        ADD CONSTRAINT shipments_origin_city_id_foreign
                        FOREIGN KEY (origin_city_id) REFERENCES cities(id) ON DELETE SET NULL;
                    END IF;
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'shipments_destination_city_id_foreign') THEN
                        ALTER TABLE shipments
                        ADD CONSTRAINT shipments_destination_city_id_foreign
                        FOREIGN KEY (destination_city_id) REFERENCES cities(id) ON DELETE SET NULL;
                    END IF;
                END$$;
            ");
        }
    }

    public function down(): void
    {
        foreach ([
            'shipments_origin_city_id_foreign',
            'shipments_destination_city_id_foreign',
        ] as $fk) {
            try { DB::statement("ALTER TABLE shipments DROP CONSTRAINT IF EXISTS {$fk};"); } catch (\Throwable $e) {}
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments','origin_city_id')) $table->dropColumn('origin_city_id');
            if (Schema::hasColumn('shipments','destination_city_id')) $table->dropColumn('destination_city_id');
        });
    }
};
