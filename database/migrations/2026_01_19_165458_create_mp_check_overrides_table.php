<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mp_check_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->cascadeOnDelete();

            $table->foreignId('depot_id')
                ->constrained('depots')
                ->cascadeOnDelete();

            $table->string('track_status', 50);

            $table->foreignId('override_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('reason');

            $table->timestamps();

            $table->index(['shipment_id', 'track_status']);
            $table->index('override_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_check_overrides');
    }
};
