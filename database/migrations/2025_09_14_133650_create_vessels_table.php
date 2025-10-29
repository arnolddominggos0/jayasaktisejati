<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_line_id')
                ->constrained('shipping_lines')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shipping_line_id', 'name']); 
        });


        DB::statement('CREATE UNIQUE INDEX vessels_line_name_unique_ci ON vessels (shipping_line_id, LOWER(name));');
    }


    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS vessels_line_name_unique_ci;');
        Schema::dropIfExists('vessels');
    }
};
