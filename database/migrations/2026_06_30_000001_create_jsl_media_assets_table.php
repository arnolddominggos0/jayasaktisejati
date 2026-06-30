<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 20)->default('public');
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('variant_thumbnail_path', 500)->nullable();
            $table->string('variant_medium_path', 500)->nullable();
            $table->string('variant_large_path', 500)->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->index('disk');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_media_assets');
    }
};
