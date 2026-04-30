<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();

            $table->string('model_no', 120)->nullable();
            $table->string('reg_no', 60)->nullable();
            $table->string('chassis_no', 120)->nullable();
            $table->string('engine_no', 120)->nullable();
            $table->string('color', 60)->nullable();
            $table->string('do_number', 120)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->string('container_display', 160)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            
            $table->index('shipment_id');
            $table->index('reg_no');
            $table->index('chassis_no');
        });
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'units')) {
                $table->dropColumn('units');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
