<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_vessel_listings', function (Blueprint $table) {
            $table->id();
            $table->string('public_ref_code', 50)->unique();
            $table->string('vessel_type', 50);
            $table->integer('year_built')->nullable();
            $table->string('flag_registry', 100)->nullable();
            $table->decimal('gross_tonnage', 10, 2)->nullable();
            $table->decimal('deadweight', 10, 2)->nullable();
            $table->decimal('loa_length', 8, 2)->nullable();
            $table->decimal('beam', 8, 2)->nullable();
            $table->decimal('draft', 8, 2)->nullable();
            $table->string('engine_power', 100)->nullable();
            $table->string('trading_area', 255)->nullable();
            $table->longText('marketing_description')->nullable();
            $table->longText('marketing_description_en')->nullable();

            $table->string('real_vessel_name', 255)->nullable();
            $table->string('imo_number', 20)->nullable();
            $table->text('owner_details')->nullable();
            $table->text('certificates')->nullable();
            $table->text('price_commercial_terms')->nullable();

            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->index('status');
            $table->index('vessel_type');
            $table->index('deleted_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_vessel_listings');
    }
};
