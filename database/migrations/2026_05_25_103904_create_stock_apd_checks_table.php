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
        Schema::create('stock_apd_checks', function (Blueprint $table) {
		 $table->id();

   		 $table->foreignId('session_id')
        		->constrained('briefing_sessions')
        		->cascadeOnDelete();

   		 $table->string('ppe_type');

   		 $table->integer('stock_available')->nullable();

   		 $table->integer('required_quantity')->nullable();

   		 $table->text('remark')->nullable();

   		 $table->timestamps();

   		 $table->unique(['session_id', 'ppe_type']);
		
	});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_apd_checks');
    }
};
