<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            $table->foreignId('vessel_id')
                ->nullable()
                ->after('shipping_line_id')
                ->constrained('vessels')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }


    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vessel_id');
        });
    }
};
