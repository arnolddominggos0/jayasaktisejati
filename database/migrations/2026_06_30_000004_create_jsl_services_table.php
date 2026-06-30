<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('title_en')->nullable();
            $table->longText('description_en')->nullable();
            $table->foreignId('media_asset_id')
                ->nullable()
                ->constrained('jsl_media_assets')
                ->nullOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->index('is_visible');
            $table->index('sort_order');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_services');
    }
};
