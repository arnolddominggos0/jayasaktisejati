<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ContainerReadinessBackfillSeeder
 *
 * Backfill data Container Readiness Januari–Mei 2026.
 *
 * Konsep: mirror MP Readiness — 1 record per hari, bukan shipment tracking.
 *   unit_count         = unit masuk yard hari itu
 *   container_need     = kebutuhan container
 *   container_available = container tersedia
 *   gap                = available - need
 *   summary_sufficient = available >= need
 *
 * Aman dijalankan ulang — menggunakan upsert() dengan unique key session_date.
 * Verifikasi total unit per bulan dilakukan setelah insert.
 */
class ContainerReadinessBackfillSeeder extends Seeder
{
    // ── Data historis ─────────────────────────────────────────────────────────
    // Format: [session_date, unit_count, container_need, container_available]
    // gap & summary_sufficient dihitung otomatis.

    private const RAW = [
        // ── Januari 2026 ──────────────────────────────────────────────────────
        ['2026-01-07',  1,  1,  2],
        ['2026-01-10', 36, 13, 14],
        ['2026-01-14', 32, 15, 15],
        ['2026-01-15', 29, 12, 13],
        ['2026-01-20', 21,  9, 10],
        ['2026-01-22', 18,  6,  7],
        ['2026-01-23',  6,  3,  4],
        ['2026-01-24',  4,  2,  3],
        ['2026-01-26',  1,  1,  1],
        ['2026-01-27',  4,  2,  3],
        ['2026-01-28',  7,  3,  3],
        ['2026-01-29',  1,  1,  2],
        ['2026-01-30',  4,  2,  2],
        ['2026-01-31',  4,  2,  2],

        // ── Februari 2026 ─────────────────────────────────────────────────────
        ['2026-02-02',  6,  4,  4],
        ['2026-02-06', 18,  7,  7],
        ['2026-02-07', 14,  6,  6],
        ['2026-02-10', 14,  6,  6],
        ['2026-02-11', 15,  7,  7],
        ['2026-02-12',  5,  0,  0],
        ['2026-02-13',  7,  0,  0],
        ['2026-02-15',  3,  3,  3],
        ['2026-02-18',  6,  3,  3],
        ['2026-02-20',  2,  1,  1],
        ['2026-02-21',  2,  1,  1],
        ['2026-02-25',  7,  3,  3],
        ['2026-02-26',  8,  4,  4],
        ['2026-02-27',  4,  4,  4],

        // ── Maret 2026 ───────────────────────────────────────────────────────
        ['2026-03-02',  7,  3,  3],
        ['2026-03-04', 17,  7,  7],
        ['2026-03-05',  9,  5,  5],
        ['2026-03-06',  2,  1,  1],
        ['2026-03-07',  9,  6,  6],
        ['2026-03-09',  5,  2,  3],
        ['2026-03-10', 12,  4,  4],
        ['2026-03-12',  6,  3,  3],
        ['2026-03-13', 11,  4,  4],
        ['2026-03-14',  9,  4,  4],
        ['2026-03-26', 13,  7,  7],
        ['2026-03-31',  4,  2,  2],

        // ── April 2026 ───────────────────────────────────────────────────────
        ['2026-04-01',  5,  3,  3],
        ['2026-04-04',  5,  3,  3],
        ['2026-04-08', 19,  8,  8],
        ['2026-04-09',  1,  1,  1],
        ['2026-04-10',  9,  3,  3],
        ['2026-04-11',  6,  3,  3],
        ['2026-04-14', 11,  5,  5],
        ['2026-04-16', 16,  7,  7],
        ['2026-04-17',  6,  3,  3],
        ['2026-04-18',  3,  2,  2],
        ['2026-04-21', 18,  7,  7],
        ['2026-04-24',  9,  4,  4],
        ['2026-04-25', 12,  5,  5],
        ['2026-04-27',  3,  2,  2],
        ['2026-04-28',  8,  5,  5],
        ['2026-04-29',  3,  2,  2],
        ['2026-04-30',  9,  2,  2],

        // ── Mei 2026 ─────────────────────────────────────────────────────────
        ['2026-05-04',  5,  2,  2],
        ['2026-05-07', 18,  7,  8],
        ['2026-05-09', 20,  8,  8],
        ['2026-05-13', 31, 13, 14],
        ['2026-05-15', 13,  5,  6],
        ['2026-05-18',  7,  3,  3],
        ['2026-05-19', 21,  9,  9],
        ['2026-05-20',  6,  3,  4],
        ['2026-05-21', 16,  8,  9],
        ['2026-05-22', 14,  5,  6],
        ['2026-05-25',  7,  3,  3],
        ['2026-05-26',  8,  4,  4],
        ['2026-05-28',  8,  4,  4],
        ['2026-05-29',  1,  1,  1],
        ['2026-05-30',  3,  2,  2],
    ];

    // ── Ekspektasi per bulan — verifikasi setelah insert ──────────────────────
    private const EXPECTED_UNITS = [
        '2026-01' => 168,
        '2026-02' => 111,
        '2026-03' => 104,
        '2026-04' => 143,
        '2026-05' => 178,
    ];

    public function run(): void
    {
        $now  = now();
        $rows = [];

        foreach (self::RAW as [$date, $unit, $need, $avail]) {
            $gap = $avail - $need;
            $rows[] = [
                'session_date'        => $date,
                'unit_count'          => $unit,
                'container_need'      => $need,
                'container_available' => $avail,
                'gap'                 => $gap,
                'summary_sufficient'  => $gap >= 0,
                'notes'               => 'Backfill historis Jan–Mei 2026',
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }

        // ── Upsert — aman dijalankan ulang ────────────────────────────────────
        DB::table('container_readiness_sessions')->upsert(
            $rows,
            uniqueBy: ['session_date'],
            update: ['unit_count', 'container_need', 'container_available', 'gap', 'summary_sufficient', 'notes', 'updated_at'],
        );

        // ── Verifikasi total unit per bulan ───────────────────────────────────
        $this->verify();
    }

    private function verify(): void
    {
        $actual = DB::table('container_readiness_sessions')
            ->selectRaw("TO_CHAR(session_date, 'YYYY-MM') AS month, SUM(unit_count)::int AS total_units, COUNT(*)::int AS records")
            ->whereRaw("session_date BETWEEN '2026-01-01' AND '2026-05-31'")
            ->groupByRaw("TO_CHAR(session_date, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(session_date, 'YYYY-MM')")
            ->get()
            ->keyBy('month');

        $allOk    = true;
        $report   = [];
        $months   = ['2026-01' => 'Januari', '2026-02' => 'Februari', '2026-03' => 'Maret', '2026-04' => 'April', '2026-05' => 'Mei'];

        foreach (self::EXPECTED_UNITS as $month => $expected) {
            $row     = $actual->get($month);
            $got     = $row ? (int) $row->total_units : 0;
            $records = $row ? (int) $row->records     : 0;
            $ok      = $got === $expected;

            if (! $ok) {
                $allOk = false;
            }

            $report[] = sprintf(
                '  %s (%s): %d records | unit_count=%d %s %d %s',
                $months[$month],
                $month,
                $records,
                $got,
                $ok ? '==' : '!=',
                $expected,
                $ok ? '✓' : '✗ MISMATCH'
            );
        }

        $this->command->newLine();
        $this->command->info('── Container Readiness Backfill ─────────────────');
        foreach ($report as $line) {
            $this->command->line($line);
        }

        // Readiness per bulan
        $readiness = DB::table('container_readiness_sessions')
            ->selectRaw("
                TO_CHAR(session_date, 'YYYY-MM') AS month,
                COUNT(*)::int AS total,
                SUM(CASE WHEN summary_sufficient = true THEN 1 ELSE 0 END)::int AS ready_count
            ")
            ->whereRaw("session_date BETWEEN '2026-01-01' AND '2026-05-31'")
            ->groupByRaw("TO_CHAR(session_date, 'YYYY-MM')")
            ->orderByRaw("TO_CHAR(session_date, 'YYYY-MM')")
            ->get();

        $this->command->newLine();
        $this->command->info('── Readiness Rate per Bulan ─────────────────────');
        foreach ($readiness as $r) {
            $rate    = $r->total > 0 ? round(($r->ready_count / $r->total) * 100) : 0;
            $ng      = $r->total - $r->ready_count;
            $this->command->line(sprintf(
                '  %s: %d/%d READY (%d%%) | %d NG',
                $months[$r->month] ?? $r->month,
                $r->ready_count,
                $r->total,
                $rate,
                $ng
            ));
        }

        $total = DB::table('container_readiness_sessions')
            ->whereRaw("session_date BETWEEN '2026-01-01' AND '2026-05-31'")
            ->count();

        $this->command->newLine();
        $this->command->line("  Total records Jan–Mei: {$total}");
        $this->command->newLine();

        if (! $allOk) {
            $this->command->error('MISMATCH DETECTED — periksa data sebelum commit!');
        } else {
            $this->command->info('Semua verifikasi unit_count PASSED ✓');
        }
    }
}
