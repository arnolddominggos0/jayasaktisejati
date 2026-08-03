<?php

namespace Tests\Feature;

use App\Filament\Resources\VesselPlanResource\Pages\ListVesselPlans;
use App\Models\User;
use App\Models\VesselPlan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * DATA-03 — dua aturan pada halaman List:
 *  1. Dropdown Tahun tidak boleh kosong walaupun seluruh vessel plan dihapus.
 *  2. CTA "Tambah Vessel Plan" hanya boleh muncul di satu tempat.
 *
 * Dijalankan di dalam transaksi yang di-rollback, sehingga data asli aman.
 */
class VesselPlanListEmptyStateTest extends TestCase
{
    public function test_year_dropdown_and_cta_behave_when_no_plans_exist(): void
    {
        $admin = User::query()->where('email', 'admin@jss.local')->first();

        if (! $admin) {
            $this->markTestSkipped('super admin seed tidak tersedia.');
        }

        $this->actingAs($admin);

        DB::beginTransaction();

        try {
            VesselPlan::query()->delete();
            $this->assertSame(0, VesselPlan::query()->count(), 'prasyarat: tabel harus kosong');

            $page = Livewire::test(ListVesselPlans::class)->instance();

            // 1 — dropdown tetap punya minimal tahun berjalan
            $options = $page->getYearOptions();
            $this->assertNotEmpty($options, 'dropdown Tahun tidak boleh kosong');
            $this->assertArrayHasKey(
                (string) now()->year,
                $options,
                'tahun berjalan harus selalu tersedia'
            );

            // 2 — tanpa data, CTA header disembunyikan supaya CTA tunggal
            //     berada di empty state tabel (tidak ada tombol ganda)
            $headerCta = collect($page->getCachedHeaderActions())
                ->first(fn ($action) => $action->getName() === 'create');

            $this->assertNotNull($headerCta, 'action create harus terdaftar');
            $this->assertFalse(
                $headerCta->isVisible(),
                'CTA header harus tersembunyi saat tabel kosong'
            );
        } finally {
            DB::rollBack();
        }

        // data asli utuh kembali
        $this->assertGreaterThan(0, VesselPlan::query()->count());
    }
}
