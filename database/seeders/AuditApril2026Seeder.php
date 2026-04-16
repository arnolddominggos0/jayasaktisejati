<?php

namespace Database\Seeders;

use App\Models\Apd;
use App\Models\Briefing;
use App\Models\Kehadiran;
use App\Models\Kesehatan;
use App\Models\Loading;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditApril2026Seeder extends Seeder
{
    /**
     * Data Manpower Aktif
     */
    private array $manpower = [
        'Tri Mulya',
        'Suryadi',
        'Odih',
        'Rustam',
        'Markus',
        'Soleh Wahidin',
        'Habi',
        'Cemen',
    ];

    /**
     * PIC
     */
    private string $pic = 'Bpk. Tri';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Memulai seeding data audit April 2026...');

        // Periode audit: 1-16 April 2026
        $startDate = Carbon::create(2026, 4, 1);
        $endDate = Carbon::create(2026, 4, 16);

        // Data briefing harian
        $this->seedBriefing($startDate, $endDate);

        // Data kehadiran
        $this->seedKehadiran($startDate, $endDate);

        // Data kesehatan
        $this->seedKesehatan($startDate, $endDate);

        // Data loading
        $this->seedLoading($startDate, $endDate);

        // Data APD
        $this->seedApd($startDate, $endDate);

        // Tampilkan dashboard summary
        $this->displayDashboard();

        $this->command->info('Seeding data audit April 2026 selesai!');
    }

    /**
     * Seed data briefing
     */
    private function seedBriefing(Carbon $startDate, Carbon $endDate): void
    {
        // Briefing dilakukan hampir setiap hari kerja (10-14 sesi)
        $briefingDays = [1, 2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 16]; // 12 sesi

        foreach ($briefingDays as $day) {
            $tanggal = Carbon::create(2026, 4, $day);

            Briefing::create([
                'tanggal' => $tanggal,
                'pic' => $this->pic,
                'topik' => $this->getRandomTopik(),
                'peserta' => rand(7, 8),
                'keterangan' => 'Briefing harian - '.$tanggal->format('d M Y'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Seed data kehadiran
     */
    private function seedKehadiran(Carbon $startDate, Carbon $endDate): void
    {
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            // Lewati weekend (Sabtu=Minggu)
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();

                continue;
            }

            // Shuffle manpower untuk variasi
            $shuffledMp = $this->manpower;
            shuffle($shuffledMp);

            // 7-8 orang hadir setiap hari
            $jumlahHadir = rand(7, 8);
            $hadirHariIni = array_slice($shuffledMp, 0, $jumlahHadir);

            foreach ($hadirHariIni as $nama) {
                Kehadiran::create([
                    'tanggal' => $currentDate->copy(),
                    'nama' => $nama,
                    'status' => 'Hadir',
                    'jam_masuk' => $this->getRandomJamMasuk(),
                    'jam_keluar' => $this->getRandomJamKeluar(),
                    'keterangan' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $currentDate->addDay();
        }
    }

    /**
     * Seed data kesehatan
     */
    private function seedKesehatan(Carbon $startDate, Carbon $endDate): void
    {
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();

                continue;
            }

            // Setiap orang yang hadir dicek kesehatannya
            foreach ($this->manpower as $nama) {
                // Cek apakah hadir hari ini
                $hadir = Kehadiran::where('tanggal', $currentDate->copy())
                    ->where('nama', $nama)
                    ->exists();

                if ($hadir) {
                    Kesehatan::create([
                        'tanggal' => $currentDate->copy(),
                        'nama' => $nama,
                        'suhu' => $this->getRandomSuhu(),
                        'sistole' => rand(115, 125),
                        'diastole' => rand(75, 85),
                        'keterangan' => 'Sehat',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $currentDate->addDay();
        }
    }

    /**
     * Seed data loading
     */
    private function seedLoading(Carbon $startDate, Carbon $endDate): void
    {
        $currentDate = $startDate->copy();
        $counter = 1;

        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();

                continue;
            }

            // 1-2 aktivitas loading per hari (total 12-20)
            $jumlahAktivitas = rand(1, 2);

            for ($i = 0; $i < $jumlahAktivitas; $i++) {
                $status = (rand(1, 100) <= 80) ? 'Selesai' : 'Proses'; // 80% selesai

                Loading::create([
                    'tanggal' => $currentDate->copy(),
                    'no_do' => 'DO/APR/'.str_pad($counter, 4, '0', STR_PAD_LEFT),
                    'customer' => $this->getRandomCustomer(),
                    'material' => $this->getRandomMaterial(),
                    'jumlah' => rand(10, 50),
                    'satuan' => 'ton',
                    'status' => $status,
                    'pic' => $this->manpower[array_rand($this->manpower)],
                    'keterangan' => 'Loading '.$currentDate->format('d M Y'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $counter++;
            }

            $currentDate->addDay();
        }
    }

    /**
     * Seed data APD
     */
    private function seedApd(Carbon $startDate, Carbon $endDate): void
    {
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            if ($currentDate->isWeekend()) {
                $currentDate->addDay();

                continue;
            }

            // Cek APD untuk setiap manpower
            foreach ($this->manpower as $nama) {
                // 7-8 orang layak pakai
                $statusHelm = (rand(1, 100) <= 95) ? 'Layak' : 'Perlu Ganti';
                $statusSepatu = (rand(1, 100) <= 95) ? 'Layak' : 'Perlu Ganti';
                $statusRompi = (rand(1, 100) <= 95) ? 'Layak' : 'Perlu Ganti';
                $statusSarungTangan = (rand(1, 100) <= 90) ? 'Layak' : 'Perlu Ganti';

                // Hitung layak pakai
                $layakCount = 0;
                if ($statusHelm === 'Layak') {
                    $layakCount++;
                }
                if ($statusSepatu === 'Layak') {
                    $layakCount++;
                }
                if ($statusRompi === 'Layak') {
                    $layakCount++;
                }
                if ($statusSarungTangan === 'Layak') {
                    $layakCount++;
                }

                Apd::create([
                    'tanggal' => $currentDate->copy(),
                    'nama' => $nama,
                    'helm' => $statusHelm,
                    'sepatu_safety' => $statusSepatu,
                    'rompi' => $statusRompi,
                    'sarung_tangan' => $statusSarungTangan,
                    'status_keseluruhan' => ($layakCount >= 3) ? 'Layak Pakai' : 'Perlu Perhatian',
                    'keterangan' => 'Pemeriksaan rutin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $currentDate->addDay();
        }
    }

    /**
     * Display dashboard summary
     */
    private function displayDashboard(): void
    {
        // Hitung statistik
        $totalBriefing = Briefing::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])->count();

        $totalKehadiran = Kehadiran::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])->count();
        $hariKerja = Kehadiran::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])
            ->distinct('tanggal')
            ->count('tanggal');
        $rataKehadiran = $hariKerja > 0 ? round($totalKehadiran / $hariKerja, 1) : 0;

        $totalLoading = Loading::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])->count();
        $loadingSelesai = Loading::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])
            ->where('status', 'Selesai')
            ->count();

        $avgSuhu = Kesehatan::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])->avg('suhu');
        $avgSistole = Kesehatan::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])->avg('sistole');
        $avgDiastole = Kesehatan::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])->avg('diastole');

        $totalApdCheck = Apd::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])->count();
        $apdLayak = Apd::whereBetween('tanggal', ['2026-04-01', '2026-04-16'])
            ->where('status_keseluruhan', 'Layak Pakai')
            ->count();

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('           DASHBOARD LAPORAN AUDIT APRIL 2026                  ');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->line('  📋 Briefing Sessions       : '.$totalBriefing.' sesi');
        $this->command->line('  👥 Kehadiran MP            : '.$rataKehadiran.'/8 (rata-rata/hari)');
        $this->command->line('  👤 Total MP Aktif          : 8 orang');
        $this->command->line('  🦺 APD Layak Pakai         : '.$apdLayak.'/'.$totalApdCheck);
        $this->command->line('  🌡️  Rata-rata Suhu          : '.number_format($avgSuhu, 1).'°C');
        $this->command->line('  💓 Rata-rata TD             : '.round($avgSistole).'/'.round($avgDiastole));
        $this->command->line('  📦 Loading Selesai         : '.$loadingSelesai);
        $this->command->line('  📦 Total Loading           : '.$totalLoading);
        $this->command->line('  📊 Persentase Selesai      : '.round(($loadingSelesai / $totalLoading) * 100).'%');
        $this->command->newLine();
        $this->command->info('  PIC: '.$this->pic);
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->newLine();

        // Detail Manpower
        $this->command->info('  DETAIL MANPOWER AKTIF:');
        foreach ($this->manpower as $index => $nama) {
            $kehadiran = Kehadiran::where('nama', $nama)
                ->whereBetween('tanggal', ['2026-04-01', '2026-04-16'])
                ->count();
            $this->command->line('  '.($index + 1).'. '.str_pad($nama, 20).' - Kehadiran: '.$kehadiran.' hari');
        }
        $this->command->newLine();
    }

    /**
     * Helper: Random topik briefing
     */
    private function getRandomTopik(): string
    {
        $topik = [
            'Keselamatan Kerja',
            'Penggunaan APD',
            'Prosedur Loading',
            'Pengecekan Kendaraan',
            'Housekeeping Area',
            'Emergency Response',
            'Pencegahan Kecelakaan',
            'Komunikasi Radio',
        ];

        return $topik[array_rand($topik)];
    }

    /**
     * Helper: Random jam masuk
     */
    private function getRandomJamMasuk(): string
    {
        $jam = ['07:00', '07:15', '07:30', '07:45', '08:00'];

        return $jam[array_rand($jam)];
    }

    /**
     * Helper: Random jam keluar
     */
    private function getRandomJamKeluar(): string
    {
        $jam = ['16:00', '16:30', '17:00', '17:30'];

        return $jam[array_rand($jam)];
    }

    /**
     * Helper: Random suhu tubuh
     */
    private function getRandomSuhu(): float
    {
        // Suhu 36.4 - 36.9
        return round(36.4 + (mt_rand() / mt_getrandmax()) * 0.5, 1);
    }

    /**
     * Helper: Random customer
     */
    private function getRandomCustomer(): string
    {
        $customers = [
            'PT Semen Indonesia',
            'PT Indocement',
            'PT Holcim',
            'PT Waskita',
            'PT Adhi Karya',
            'PT Pembangunan Perumahan',
            'PT Total Bangun Persada',
            'PT Jaya Konstruksi',
        ];

        return $customers[array_rand($customers)];
    }

    /**
     * Helper: Random material
     */
    private function getRandomMaterial(): string
    {
        $materials = [
            'Semen Portland',
            'Batu Split',
            'Pasir',
            'Besi Beton',
            'Batako',
            'Paving Block',
            'Ready Mix',
            'Batu Belah',
        ];

        return $materials[array_rand($materials)];
    }
}
