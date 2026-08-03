<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DATA-04 — JKT-MND ditetapkan sebagai rute kanonik.
 *
 * Latar belakang:
 * Registry RouteCode memiliki dua business route yang memakai pasangan
 * pelabuhan identik (IDJKT → IDBTG): JKT-MND ("Jakarta → Manado") dan
 * JKT-BTG ("Jakarta → Bitung"). Bitung adalah pelabuhan yang melayani Manado,
 * jadi keduanya merepresentasikan satu rute yang sama.
 *
 * Duplikasi ini membuat POL/POD tidak dapat menentukan route_code secara unik,
 * sehingga POL/POD tidak bisa menjadi source of truth. Keputusan produk:
 * JKT-MND kanonik, JKT-BTG dihapus.
 *
 * Karena route_code ikut membentuk identitas voyage
 * (voyages.code = VOY{voyage_no}{vessel.code}{route_code}), perubahan ini
 * harus mengubah data yang sudah ada agar identitas tetap konsisten.
 *
 * Diverifikasi sebelum ditulis: 0 tabrakan pada voyages.code (unique index).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1 — Business route pada vessel plan
            DB::table('vessel_plans')
                ->where('route_code', 'JKT-BTG')
                ->update(['route_code' => 'JKT-MND']);

            // 2 — Voyage route code + canonical code.
            //     Diproses per baris supaya code diregenerasi konsisten dengan
            //     format Voyage::generateCode() tanpa memicu model events.
            $voyages = DB::table('voyages')
                ->where('route_code', 'JKTBTG')
                ->get(['id', 'code']);

            foreach ($voyages as $voyage) {
                DB::table('voyages')
                    ->where('id', $voyage->id)
                    ->update([
                        'route_code' => 'JKTMND',
                        'code'       => $voyage->code
                            ? str_replace('JKTBTG', 'JKTMND', $voyage->code)
                            : null,
                    ]);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            DB::table('vessel_plans')
                ->where('route_code', 'JKT-MND')
                ->update(['route_code' => 'JKT-BTG']);

            $voyages = DB::table('voyages')
                ->where('route_code', 'JKTMND')
                ->get(['id', 'code']);

            foreach ($voyages as $voyage) {
                DB::table('voyages')
                    ->where('id', $voyage->id)
                    ->update([
                        'route_code' => 'JKTBTG',
                        'code'       => $voyage->code
                            ? str_replace('JKTMND', 'JKTBTG', $voyage->code)
                            : null,
                    ]);
            }
        });
    }
};
