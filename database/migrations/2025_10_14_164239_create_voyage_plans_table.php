<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voyage_plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $t->enum('state', ['draft', 'feedback', 'final'])->index();
            $t->json('payload')->nullable();        
            $t->text('notes')->nullable();          
            $t->enum('source', ['manual', 'email', 'upload'])->default('manual');
            $t->timestamp('finalized_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->index(['voyage_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_plans');
    }
};
