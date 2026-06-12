<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * JanAprHistorySeeder — Januari–April 2026
 *
 * TIDAK disentuh  : briefing_sessions, stock_apd_checks
 * DIKERJAKAN      : briefing_attendances (DELETE lama → INSERT baru)
 *
 * Langkah:
 *   1. Untuk setiap tanggal di PLAN, cari session_id yang sudah ada.
 *   2. DELETE semua briefing_attendances untuk session tersebut.
 *   3. INSERT ulang dengan distribusi realistis (siklus 10 pola).
 *   4. UPDATE summary_sufficient berdasarkan FIT aktual.
 *
 * Distribusi per sesi (siklus 10):
 *   idx%10 ∈ {0,4,6,9} → 8 hadir  (~40%)
 *   idx%10 ∈ {1,2,5,7} → 7 hadir  (~40%)
 *   idx%10 ∈ {3,8}     → 6 hadir  (~20%)
 *
 * Variasi  : present | sick | absent | recheck (recheck → FIT)
 * APD      : has_ppe = true, personal_ppe_status = null
 * Jaminan  : FIT ≥ 6 ≥ 5 → summary_sufficient = true → READY
 *
 * MP (orderBy name):
 *   0=Cemen  1=Habi  2=Markus  3=Odih  4=Rustam
 *   5=Soleh Wahidin  6=Suryadi  7=Tri Mulya
 *
 * php artisan db:seed --class=JanAprHistorySeeder
 */
class JanAprHistorySeeder extends Seeder
{
    private const DEPOT_ID  = 1;
    private const COORD_UID = 2;
    private const SOP_NEED  = 5;

    private const EVIDENCE_PHOTOS = [
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.35.jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.36 (1).jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.36.jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.37.jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.38 (1).jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.38 (2).jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.38.jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.39.jpeg',
        'briefing-evidence/history/2026-05/WhatsApp Image 2026-06-10 at 11.01.42 (1).jpeg',
    ];

    private const APD_TYPES = ['helm', 'rompi', 'sepatu', 'sarung_tangan'];

    // Tanggal + unit REAL dari data yard — tidak diubah.
    private const PLAN = [

        // ── JANUARI (13 sesi, 164 unit) ──────────────────────────────────
        ['date' => '2026-01-07', 'unit' =>  1],
        ['date' => '2026-01-10', 'unit' => 36],
        ['date' => '2026-01-14', 'unit' => 32],
        ['date' => '2026-01-15', 'unit' => 29],
        ['date' => '2026-01-20', 'unit' => 21],
        ['date' => '2026-01-22', 'unit' => 18],
        ['date' => '2026-01-23', 'unit' =>  6],
        ['date' => '2026-01-24', 'unit' =>  4],
        ['date' => '2026-01-26', 'unit' =>  1],
        ['date' => '2026-01-27', 'unit' =>  4],
        ['date' => '2026-01-28', 'unit' =>  7],
        ['date' => '2026-01-29', 'unit' =>  1],
        ['date' => '2026-01-30', 'unit' =>  4],

        // ── FEBRUARI (14 sesi, 120 unit) ─────────────────────────────────
        ['date' => '2026-02-02', 'unit' =>  6],
        ['date' => '2026-02-05', 'unit' =>  7],
        ['date' => '2026-02-06', 'unit' => 18],
        ['date' => '2026-02-07', 'unit' => 12],
        ['date' => '2026-02-10', 'unit' => 16],
        ['date' => '2026-02-11', 'unit' => 15],
        ['date' => '2026-02-13', 'unit' => 12],
        ['date' => '2026-02-16', 'unit' =>  3],
        ['date' => '2026-02-18', 'unit' =>  6],
        ['date' => '2026-02-20', 'unit' =>  4],
        ['date' => '2026-02-21', 'unit' =>  2],
        ['date' => '2026-02-25', 'unit' =>  7],
        ['date' => '2026-02-26', 'unit' =>  8],
        ['date' => '2026-02-27', 'unit' =>  4],

        // ── MARET (12 sesi, 104 unit) ─────────────────────────────────────
        ['date' => '2026-03-02', 'unit' =>  7],
        ['date' => '2026-03-04', 'unit' => 17],
        ['date' => '2026-03-05', 'unit' =>  9],
        ['date' => '2026-03-07', 'unit' =>  6],
        ['date' => '2026-03-08', 'unit' =>  5],
        ['date' => '2026-03-09', 'unit' =>  5],
        ['date' => '2026-03-10', 'unit' => 12],
        ['date' => '2026-03-12', 'unit' =>  6],
        ['date' => '2026-03-14', 'unit' => 11],
        ['date' => '2026-03-16', 'unit' =>  4],
        ['date' => '2026-03-26', 'unit' => 18],
        ['date' => '2026-03-31', 'unit' =>  4],

        // ── APRIL (14 sesi, 144 unit) ─────────────────────────────────────
        ['date' => '2026-04-02', 'unit' =>  5],
        ['date' => '2026-04-04', 'unit' =>  5],
        ['date' => '2026-04-08', 'unit' => 19],
        ['date' => '2026-04-10', 'unit' => 10],
        ['date' => '2026-04-11', 'unit' =>  6],
        ['date' => '2026-04-15', 'unit' => 11],
        ['date' => '2026-04-17', 'unit' => 22],
        ['date' => '2026-04-20', 'unit' =>  3],
        ['date' => '2026-04-21', 'unit' => 18],
        ['date' => '2026-04-23', 'unit' =>  9],
        ['date' => '2026-04-25', 'unit' => 12],
        ['date' => '2026-04-27', 'unit' =>  3],
        ['date' => '2026-04-28', 'unit' =>  8],
        ['date' => '2026-04-30', 'unit' => 13],
    ];

    // ═════════════════════════════════════════════════════════════════════════

    public function run(): void
    {
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║  Jan–Apr 2026  —  Reimport Attendance            ║');
        $this->command->info('║  briefing_sessions & stock_apd_checks TIDAK      ║');
        $this->command->info('║  disentuh. Hanya briefing_attendances.           ║');
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->newLine();

        $mpIds = DB::table('manpowers')
            ->where('depot_id', self::DEPOT_ID)
            ->where('active', true)
            ->orderBy('name')
            ->pluck('id')
            ->toArray();

        if (empty($mpIds)) {
            $this->command->error('Tidak ada MP aktif. Abort.');
            return;
        }

        $this->command->line('MP aktif  : ' . count($mpIds) . ' orang');
        $this->command->line('SOP need  : ' . self::SOP_NEED);
        $this->command->line('Total sesi: ' . count(self::PLAN));
        $this->command->newLine();

        $this->reimportAttendances($mpIds);

        $this->command->newLine();
        $this->command->info('Selesai.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pola distribusi siklus-10 berdasarkan index sesi.
     *
     *   absent  = idx % n              (offset 0)
     *   sick    = (idx+3) % n          (offset 3 — tidak bertabrakan)
     *   absent2 = (idx+5) % n          (offset 5 — tidak bertabrakan)
     *   recheck = (idx+6) % n          (offset 6 — tidak bertabrakan)
     *
     * FIT minimum per sesi = 6 ≥ SOP_NEED(5) → READY terjamin.
     */
    private function resolvePattern(int $idx, int $n): array
    {
        $a  = $idx % $n;
        $s  = ($idx + 3) % $n;
        $a2 = ($idx + 5) % $n;
        $r  = ($idx + 6) % $n;

        return match ($idx % 10) {
            0       => ['absent' => [],       'sick' => [],   'recheck' => []],    // 8P normal
            1       => ['absent' => [$a],     'sick' => [],   'recheck' => []],    // 7P 1 absent
            2       => ['absent' => [],       'sick' => [$s], 'recheck' => []],    // 7P 1 sick
            3       => ['absent' => [$a],     'sick' => [$s], 'recheck' => []],    // 6P 1 absent+1 sick
            4       => ['absent' => [],       'sick' => [],   'recheck' => [$r]],  // 8P 1 recheck
            5       => ['absent' => [$a],     'sick' => [],   'recheck' => []],    // 7P 1 absent
            6       => ['absent' => [],       'sick' => [],   'recheck' => []],    // 8P normal
            7       => ['absent' => [],       'sick' => [$s], 'recheck' => [$r]],  // 7P 1 sick+1 recheck
            8       => ['absent' => [$a,$a2], 'sick' => [],   'recheck' => []],    // 6P 2 absent
            default => ['absent' => [],       'sick' => [],   'recheck' => [$r]],  // 8P 1 recheck (idx%10=9)
        };
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function reimportAttendances(array $mpIds): void
    {
        $mpCount = count($mpIds);

        $healthPool = [
            ['temp' => 36.5, 'sys' => 115, 'dia' => 75],
            ['temp' => 36.7, 'sys' => 120, 'dia' => 78],
            ['temp' => 36.8, 'sys' => 118, 'dia' => 76],
            ['temp' => 36.9, 'sys' => 125, 'dia' => 80],
            ['temp' => 37.0, 'sys' => 122, 'dia' => 79],
            ['temp' => 36.6, 'sys' => 117, 'dia' => 75],
            ['temp' => 36.8, 'sys' => 119, 'dia' => 77],
            ['temp' => 37.1, 'sys' => 128, 'dia' => 82],
        ];
        $recheckPool = [
            ['temp' => 37.3, 'sys' => 132, 'dia' => 86, 'complaint' => 'Kepala pusing ringan'],
            ['temp' => 37.4, 'sys' => 135, 'dia' => 88, 'complaint' => 'Badan sedikit tidak enak'],
            ['temp' => 37.2, 'sys' => 130, 'dia' => 85, 'complaint' => 'Merasa agak lelah'],
        ];

        $auditRows    = [];
        $totalDeleted = 0;
        $skipped      = 0;

        foreach (self::PLAN as $idx => $entry) {
            $date = $entry['date'];
            $unit = $entry['unit'];

            // Cari session yang sudah ada — JANGAN buat baru
            $sessionId = DB::table('briefing_sessions')
                ->where('depot_id', self::DEPOT_ID)
                ->where('date', $date)
                ->value('id');

            if (! $sessionId) {
                $this->command->warn("  ! {$date}: session tidak ditemukan, skip.");
                $skipped++;
                continue;
            }

            // ── DELETE attendance lama ────────────────────────────────────

            $deleted       = DB::table('briefing_attendances')
                ->where('session_id', $sessionId)
                ->delete();
            $totalDeleted += $deleted;

            // ── Tentukan pola distribusi ──────────────────────────────────

            $pattern     = $this->resolvePattern($idx, $mpCount);
            $absentIdxs  = $pattern['absent'];
            $sickIdxs    = $pattern['sick'];
            $recheckIdxs = $pattern['recheck'];

            // ── INSERT attendance baru ────────────────────────────────────

            foreach ($mpIds as $mpIdx => $mpId) {
                $minute    = 30 + ($mpIdx * 3);
                $createdAt = $date . ' 07:' . str_pad((string) $minute, 2, '0', STR_PAD_LEFT) . ':00';
                $h         = $healthPool[$mpIdx % count($healthPool)];

                $isAbsent  = in_array($mpIdx, $absentIdxs, true);
                $isSick    = in_array($mpIdx, $sickIdxs,   true);
                $isRecheck = in_array($mpIdx, $recheckIdxs, true);

                if ($isAbsent) {
                    DB::table('briefing_attendances')->insert([
                        'session_id'          => $sessionId,
                        'manpower_id'         => $mpId,
                        'mp_type'             => 'regular',
                        'backup_name'         => null,
                        'attendance_status'   => 'absent',
                        'temperature'         => null,
                        'bp_systolic'         => null,
                        'bp_diastolic'        => null,
                        'health_complaint'    => null,
                        'fit_status'          => null,
                        'recheck_required'    => false,
                        'rest_started_at'     => null,
                        'recheck_result'      => null,
                        'medical_action'      => null,
                        'has_ppe'             => false,
                        'personal_ppe_status' => null,
                        'remark'              => null,
                        'signature_path'      => null,
                        'created_by'          => self::COORD_UID,
                        'created_at'          => $createdAt,
                        'updated_at'          => $createdAt,
                    ]);

                } elseif ($isSick) {
                    DB::table('briefing_attendances')->insert([
                        'session_id'          => $sessionId,
                        'manpower_id'         => $mpId,
                        'mp_type'             => 'regular',
                        'backup_name'         => null,
                        'attendance_status'   => 'sick',
                        'temperature'         => null,
                        'bp_systolic'         => null,
                        'bp_diastolic'        => null,
                        'health_complaint'    => null,
                        'fit_status'          => null,
                        'recheck_required'    => false,
                        'rest_started_at'     => null,
                        'recheck_result'      => null,
                        'medical_action'      => null,
                        'has_ppe'             => false,
                        'personal_ppe_status' => null,
                        'remark'              => null,
                        'signature_path'      => null,
                        'created_by'          => self::COORD_UID,
                        'created_at'          => $createdAt,
                        'updated_at'          => $createdAt,
                    ]);

                } elseif ($isRecheck) {
                    $rh = $recheckPool[$mpIdx % count($recheckPool)];

                    DB::table('briefing_attendances')->insert([
                        'session_id'          => $sessionId,
                        'manpower_id'         => $mpId,
                        'mp_type'             => 'regular',
                        'backup_name'         => null,
                        'attendance_status'   => 'present',
                        'temperature'         => $rh['temp'],
                        'bp_systolic'         => $rh['sys'],
                        'bp_diastolic'        => $rh['dia'],
                        'health_complaint'    => $rh['complaint'],
                        'fit_status'          => 'FIT',
                        'recheck_required'    => true,
                        'rest_started_at'     => $date . ' 07:45:00',
                        'recheck_result'      => 'Normal setelah istirahat 15 menit',
                        'medical_action'      => null,
                        'has_ppe'             => true,
                        'personal_ppe_status' => null,
                        'remark'              => null,
                        'signature_path'      => null,
                        'created_by'          => self::COORD_UID,
                        'created_at'          => $createdAt,
                        'updated_at'          => $date . ' 08:00:00',
                    ]);

                } else {
                    DB::table('briefing_attendances')->insert([
                        'session_id'          => $sessionId,
                        'manpower_id'         => $mpId,
                        'mp_type'             => 'regular',
                        'backup_name'         => null,
                        'attendance_status'   => 'present',
                        'temperature'         => $h['temp'],
                        'bp_systolic'         => $h['sys'],
                        'bp_diastolic'        => $h['dia'],
                        'health_complaint'    => null,
                        'fit_status'          => 'FIT',
                        'recheck_required'    => false,
                        'rest_started_at'     => null,
                        'recheck_result'      => null,
                        'medical_action'      => null,
                        'has_ppe'             => true,
                        'personal_ppe_status' => null,
                        'remark'              => null,
                        'signature_path'      => null,
                        'created_by'          => self::COORD_UID,
                        'created_at'          => $createdAt,
                        'updated_at'          => $createdAt,
                    ]);
                }
            }

            // ── Hitung FIT aktual & update summary_sufficient ────────────

            $stats = DB::table('briefing_attendances')
                ->where('session_id', $sessionId)
                ->selectRaw("
                    SUM(CASE WHEN attendance_status = 'present'    THEN 1 ELSE 0 END) AS present_cnt,
                    SUM(CASE WHEN attendance_status = 'sick'       THEN 1 ELSE 0 END) AS sick_cnt,
                    SUM(CASE WHEN attendance_status = 'absent'     THEN 1 ELSE 0 END) AS absent_cnt,
                    SUM(CASE WHEN recheck_required  = true         THEN 1 ELSE 0 END) AS recheck_cnt,
                    SUM(CASE WHEN fit_status        = 'FIT'        THEN 1 ELSE 0 END) AS fit_cnt
                ")
                ->first();

            $fit   = (int) $stats->fit_cnt;
            $ready = $fit >= self::SOP_NEED;

            DB::table('briefing_sessions')
                ->where('id', $sessionId)
                ->update(['summary_sufficient' => $ready]);

            $auditRows[] = [
                'date'      => $date,
                'unit'      => $unit,
                'session_id'=> $sessionId,
                'present'   => (int) $stats->present_cnt,
                'sick'      => (int) $stats->sick_cnt,
                'absent'    => (int) $stats->absent_cnt,
                'recheck'   => (int) $stats->recheck_cnt,
                'fit'       => $fit,
                'ready'     => $ready,
            ];
        }

        // ══════════════════════════════════════════════════════════════════
        // OUTPUT
        // ══════════════════════════════════════════════════════════════════

        $this->command->line("Attendance lama dihapus : {$totalDeleted} baris");
        if ($skipped) {
            $this->command->warn("Session tidak ditemukan  : {$skipped} tanggal");
        }

        // ── Audit per sesi ────────────────────────────────────────────────

        $this->command->newLine();
        $this->command->info('── Audit Attendance ────────────────────────────────────────────────────');
        $this->command->line(sprintf(
            '  %-12s | %-7s | %-9s | %-6s | %-8s | %-6s | %s',
            'Tanggal', 'Unit', 'Present', 'Sick', 'Absent', 'FIT', 'Status'
        ));
        $this->command->line('  ' . str_repeat('-', 68));

        foreach ($auditRows as $r) {
            $status = $r['ready'] ? 'READY' : '!! NOT READY';
            $this->command->line(sprintf(
                '  %s | %3d unit | %2d present | %2d sick | %2d absent | %2d FIT | %s',
                $r['date'], $r['unit'],
                $r['present'], $r['sick'], $r['absent'], $r['fit'],
                $status
            ));
        }

        // ── Summary ───────────────────────────────────────────────────────

        $totPres   = array_sum(array_column($auditRows, 'present'));
        $totSick   = array_sum(array_column($auditRows, 'sick'));
        $totAbsent = array_sum(array_column($auditRows, 'absent'));
        $readyCnt  = count(array_filter($auditRows, fn($r) => $r['ready']));
        $notReady  = count($auditRows) - $readyCnt;
        $totalSess = count($auditRows);

        $this->command->newLine();
        $this->command->info('── Summary ─────────────────────────────────────────');
        $this->command->line(sprintf('  Total Sessions   : %d', $totalSess));
        $this->command->line(sprintf('  Total Attendance : %d', $totalSess * $mpCount));
        $this->command->line(sprintf('  Total Present    : %d', $totPres));
        $this->command->line(sprintf('  Total Sick       : %d', $totSick));
        $this->command->line(sprintf('  Total Absent     : %d', $totAbsent));

        if ($readyCnt === $totalSess) {
            $this->command->info(sprintf('  Ready Sessions   : %d  ✓ Semua READY', $readyCnt));
            $this->command->info('  Not Ready        : 0  ✓');
        } else {
            $this->command->warn(sprintf('  Ready Sessions   : %d / %d', $readyCnt, $totalSess));
            $this->command->error(sprintf('  Not Ready        : %d', $notReady));
        }

        // ── Verifikasi: present_count per session_id ──────────────────────

        $this->command->newLine();
        $this->command->info('── Verifikasi: present_count per session (Jan–Apr) ─────────────────────────────');
        $this->command->line(sprintf('  %-12s | %-10s | %s', 'Tanggal', 'Session ID', 'present_count'));
        $this->command->line('  ' . str_repeat('-', 42));

        foreach ($auditRows as $r) {
            $this->command->line(sprintf(
                '  %s | sid=%-6d | %d',
                $r['date'], $r['session_id'], $r['present']
            ));
        }

        // Distribusi headcount
        $dist = array_count_values(array_column($auditRows, 'present'));
        ksort($dist);

        $this->command->newLine();
        $this->command->info('── Distribusi hadir per sesi ───────────────────────');
        foreach ($dist as $cnt => $sesiCount) {
            $this->command->line(sprintf('  %d hadir : %d sesi', $cnt, $sesiCount));
        }
    }
}
