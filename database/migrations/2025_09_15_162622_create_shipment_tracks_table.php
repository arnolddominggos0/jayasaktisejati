<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipment_tracks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $t->timestamp('tracked_at');
            $t->string('status')->nullable();
            $t->string('location')->nullable();
            $t->string('checkpoint')->nullable();
            $t->text('note')->nullable();
            $t->json('meta')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['shipment_id', 'tracked_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('shipment_tracks');
    }
};
