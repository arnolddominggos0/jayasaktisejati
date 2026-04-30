<?php

        use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branch_mode_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['sea', 'land']);
            $table->foreignId('outbound_depot_id')->constrained('depots')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['branch_id', 'mode']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('branch_mode_defaults');
    }
};
