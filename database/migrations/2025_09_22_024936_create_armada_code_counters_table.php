<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('armada_code_counters')) {
            Schema::create('armada_code_counters', function (Blueprint $table) {
                $table->id();
                $table->string('prefix', 10)->unique();
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasColumn('armadas', 'code')) {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS armadas_code_unique ON armadas (code)");
        }
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS armadas_code_unique");

        Schema::dropIfExists('armada_code_counters');
    }
};
