<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_medical_actions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mp_check_id')
                ->constrained('mp_checks')
                ->cascadeOnDelete();

            // Istirahat 30 menit | Berobat | Pulang
            $table->string('action');

            $table->text('notes')->nullable();

            $table->timestamp('performed_at');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_medical_actions');
    }
};
