<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Identitas pelanggan
            $table->string('code', 20)->unique();           // ex: CUST-2025-00001
            $table->string('name', 191);

            // Email: boleh NULL, tapi jika diisi harus unik (Postgres mengizinkan banyak NULL pada UNIQUE)
            $table->string('email', 191)->nullable()->unique();

            // NIK opsional (atau wajib jika bisnis mensyaratkan). Panjang 16, unik saat ada.
            $table->string('nik', 16)->nullable()->unique();

            // NPWP opsional
            $table->string('npwp', 32)->nullable();

            // Nomor telepon; gunakan string agar mudah di-validate (leading zero, tanda +62, dsb)
            $table->string('phone_number', 32)->nullable();

            // Relasi ke kantor/depo
            $table->foreignId('office_id')
                ->constrained('offices')
                ->cascadeOnDelete();

            $table->timestampsTz();

            // Index bantu pencarian
            $table->index(['name']);
            $table->index(['office_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
