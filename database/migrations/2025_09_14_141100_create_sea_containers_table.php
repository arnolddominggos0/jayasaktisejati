<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sea_container_cargos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('container_id')->nullable()->index();
            $table->string('group_type', 20)->nullable(); 
            $table->string('description', 120)->nullable();
            $table->string('unit_ref', 60)->nullable();
            $table->integer('qty')->default(1);
            $table->decimal('cbm', 10, 3)->nullable();
            $table->decimal('weight_kg', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('container_id', 'sea_container_cargos_container_fk')
                ->references('id')
                ->on('sea_containers')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sea_container_cargos');
    }
};
