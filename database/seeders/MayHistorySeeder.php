<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Import historis Briefing Harian + Attendance Mei 2026
 * Depot: Depo PDI Jakarta (id=1, branch_id=1, coordinator_user_id=2)
 *
 * Distribusi attendance:
 *   absent[]  → sick (mpIdx genap) atau absent (mpIdx ganjil)
 *   recheck[] → hadir, vital borderline, recheck done → FIT
 *   apd[]     → hadir + FIT tapi APD personal tidak lengkap
 *   default   → hadir + FIT + APD lengkap
 *
 * MP order (urutan insert seedManpower):
 *   0=Tri Mulya  1=Suryadi  2=Odih  3=Rustam  4=Soleh Wahidin
 *   5=Markus  6=Cemen  7=Habi
 *
 * Idempotent: aman dijalankan ulang — cek duplikat sebelum insert.
 */
class MayHistorySeeder extends Seeder
{
    private const DEPOT_ID    = 1;
    private const BRANCH_ID   = 1;
    private const COORD_UID   = 2;
    private const SOP_NEED    = 5;

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

    public function run(): void
    {
        $this->command->info('=== May 2026 History Seeder ===');

        $mpIds = $this->seedManpower();

        $this->command->newLine();

        $this->seedSessions($mpIds);

        $this->command->newLine();
        $this->command->info('Selesai.');
    }

    /*
    |--------------------------------------------------------------------------
    | Step 1: Manpower
    |--------------------------------------------------------------------------
    */

    private function seedManpower(): array
    {
        $this->command->info('--- Manpower ---');

        $names = [
            'Tri Mulya',
            'Suryadi',
            'Odih',
            'Rustam',
            'Soleh Wahidin',
            'Markus',
            'Cemen',
            'Habi',
        ];

        $now   = now()->toDateTimeString();
        $mpIds = [];

        foreach ($names as $name) {
            $existing = DB::table('manpowers')
                ->where('name', $name)
                ->where('depot_id', self::DEPOT_ID)
                ->value('id');

            if ($existing) {
                $mpIds[] = (int) $existing;
                $this->command->line("  ~ MP sudah ada: {$name} (id={$existing})");
            } else {
                $id = DB::table('manpowers')->insertGetId([
                    'name'       => $name,
                    'domain'     => 'sea_freight',
                    'branch_id'  => self::BRANCH_ID,
                    'depot_id'   => self::DEPOT_ID,
                    'active'     => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $mpIds[] = $id;
                $this->command->line("  + MP baru: {$name} (id={$id})");
            }
        }

        return $mpIds;
    }

    /*
    |--------------------------------------------------------------------------
    | Step 2: Briefing Sessions + Attendance + Stock APD
    |--------------------------------------------------------------------------
    */

    private function seedSessions(array $mpIds): void
    {
        $this->command->info('--- Briefing Sessions, Attendance & Stock APD ---');

        $topics = [
            'SOP Loading & Unloading Unit',
            'Pemeriksaan Unit Sebelum PDI',
            'SOP Penataan Unit di Rack',
            'Keselamatan Kerja Area Yard',
            'SOP Handover Unit ke PDI',
            'Penggunaan APD dan Keselamatan Kerja',
            'Quality Inspection Unit',
            'SOP Stuffing dan Pengamanan Muatan',
            'Pengendalian Risiko Kerusakan Unit',
            'Koordinasi Operasional Harian',
            'Evaluasi Operasional Yard',
            'SOP Loading & Unloading Unit',
            'Pemeriksaan Unit Sebelum PDI',
            'Keselamatan Kerja Area Yard',
        ];

        /*
         * absent[]  → mpIdx yang tidak hadir (genap=sick, ganjil=absent)
         * recheck[] → hadir tapi vital borderline; recheck done → FIT
         * apd[]     → hadir + FIT tapi APD personal tidak lengkap
         *
         * MP: 0=Tri Mulya 1=Suryadi 2=Odih 3=Rustam 4=Soleh 5=Markus 6=Cemen 7=Habi
         */
        $plan = [
            ['date' => '2026-05-04', 'unit' =>  5, 'absent' => [],       'recheck' => [3], 'apd' => [6]],    // Rustam recheck, Cemen APD
            ['date' => '2026-05-07', 'unit' => 18, 'absent' => [6],      'recheck' => [],  'apd' => []],      // Cemen sick
            ['date' => '2026-05-09', 'unit' => 20, 'absent' => [],       'recheck' => [1], 'apd' => []],      // Suryadi recheck
            ['date' => '2026-05-13', 'unit' => 31, 'absent' => [5, 6],   'recheck' => [],  'apd' => []],      // Markus absent, Cemen sick
            ['date' => '2026-05-16', 'unit' => 13, 'absent' => [7],      'recheck' => [],  'apd' => []],      // Habi absent
            ['date' => '2026-05-17', 'unit' =>  7, 'absent' => [],       'recheck' => [],  'apd' => [4]],     // Soleh Wahidin APD
            ['date' => '2026-05-19', 'unit' => 21, 'absent' => [1],      'recheck' => [],  'apd' => []],      // Suryadi absent
            ['date' => '2026-05-20', 'unit' =>  6, 'absent' => [2, 7],   'recheck' => [],  'apd' => []],      // Odih sick, Habi absent
            ['date' => '2026-05-22', 'unit' => 16, 'absent' => [],       'recheck' => [7], 'apd' => [2]],     // Habi recheck, Odih APD
            ['date' => '2026-05-23', 'unit' => 14, 'absent' => [3],      'recheck' => [],  'apd' => []],      // Rustam absent
            ['date' => '2026-05-25', 'unit' =>  7, 'absent' => [4, 7],   'recheck' => [],  'apd' => []],      // Soleh sick, Habi absent
            ['date' => '2026-05-26', 'unit' =>  8, 'absent' => [],       'recheck' => [],  'apd' => [5]],     // Markus APD
            ['date' => '2026-05-28', 'unit' =>  7, 'absent' => [4],      'recheck' => [],  'apd' => []],      // Soleh sick
            ['date' => '2026-05-29', 'unit' =>  2, 'absent' => [],       'recheck' => [0], 'apd' => []],      // Tri Mulya recheck
        ];

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
        $apdPool = [
            'Helm personal tidak dibawa',
            'Rompi tidak sesuai standar',
            'Sepatu safety tidak dibawa',
        ];

        $photoCount = count(self::EVIDENCE_PHOTOS);

        foreach ($plan as $idx => $entry) {
            $date       = $entry['date'];
            $unit       = $entry['unit'];
            $absentIdx  = $entry['absent'];
            $recheckIdx = $entry['recheck'];
            $apdIdx     = $entry['apd'];
            $attend     = count($mpIds) - count($absentIdx);
            $topic      = $topics[$idx];
            $photo      = self::EVIDENCE_PHOTOS[$idx % $photoCount];

            // ── Session ──────────────────────────────────────────────────

            $sessionId = DB::table('briefing_sessions')
                ->where('date', $date)
                ->where('depot_id', self::DEPOT_ID)
                ->value('id');

            if ($sessionId) {
                $this->command->line("  ~ {$date}: session sudah ada (id={$sessionId}), skip session.");
            } else {
                $sessionId = DB::table('briefing_sessions')->insertGetId([
                    'date'                   => $date,
                    'depot_id'               => self::DEPOT_ID,
                    'coordinator_user_id'    => self::COORD_UID,
                    'notes'                  => $topic,
                    'unit_masuk_yard'        => $unit,
                    'summary_headcount'      => self::SOP_NEED,
                    'summary_sufficient'     => false,
                    'summary_solution'       => null,
                    'mp_check_status'        => 'cleared',
                    'approved_at'            => null,
                    'approved_by'            => null,
                    'backup_required'        => false,
                    'backup_type'            => null,
                    'backup_notes'           => null,
                    'pending_activity'       => false,
                    'pending_reason'         => null,
                    'apd_request_status'     => null,
                    'apd_request_note'       => null,
                    'briefing_evidence_path' => $photo,
                    'created_at'             => $date . ' 07:30:00',
                    'updated_at'             => $date . ' 07:30:00',
                ]);

                $this->command->line("  + {$date}: session baru (id={$sessionId}) attend={$attend}");
            }

            // ── Attendance ───────────────────────────────────────────────

            foreach ($mpIds as $mpIdx => $mpId) {
                $exists = DB::table('briefing_attendances')
                    ->where('session_id', $sessionId)
                    ->where('manpower_id', $mpId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $h          = $healthPool[$mpIdx % count($healthPool)];
                $minuteOff  = 30 + ($mpIdx * 3);
                $createdAt  = $date . ' 07:' . str_pad((string) $minuteOff, 2, '0', STR_PAD_LEFT) . ':00';

                $isAbsent  = in_array($mpIdx, $absentIdx, true);
                $isRecheck = in_array($mpIdx, $recheckIdx, true);
                $isApd     = in_array($mpIdx, $apdIdx, true);

                if ($isAbsent) {
                    $absentStatus = ($mpIdx % 2 === 0) ? 'sick' : 'absent';

                    DB::table('briefing_attendances')->insert([
                        'session_id'          => $sessionId,
                        'manpower_id'         => $mpId,
                        'mp_type'             => 'regular',
                        'backup_name'         => null,
                        'attendance_status'   => $absentStatus,
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

                } elseif ($isApd) {
                    $apdNote = $apdPool[$mpIdx % count($apdPool)];

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
                        'has_ppe'             => false,
                        'personal_ppe_status' => $apdNote,
                        'remark'              => null,
                        'signature_path'      => null,
                        'created_by'          => self::COORD_UID,
                        'created_at'          => $createdAt,
                        'updated_at'          => $createdAt,
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

            // ── Stock APD — 4 jenis × 10 unit per sesi ───────────────────

            foreach (self::APD_TYPES as $apdType) {
                $apdExists = DB::table('stock_apd_checks')
                    ->where('session_id', $sessionId)
                    ->where('ppe_type', $apdType)
                    ->exists();

                if ($apdExists) {
                    continue;
                }

                DB::table('stock_apd_checks')->insert([
                    'session_id'        => $sessionId,
                    'ppe_type'          => $apdType,
                    'stock_available'   => 10,
                    'required_quantity' => self::SOP_NEED,
                    'remark'            => 'Backfill histori',
                    'status'            => null,
                    'created_at'        => $date . ' 07:30:00',
                    'updated_at'        => $date . ' 07:30:00',
                ]);
            }

            // ── Update summary_sufficient ─────────────────────────────────

            $actualFit = DB::table('briefing_attendances')
                ->where('session_id', $sessionId)
                ->where('fit_status', 'FIT')
                ->count();

            DB::table('briefing_sessions')
                ->where('id', $sessionId)
                ->update([
                    'summary_sufficient' => $actualFit >= self::SOP_NEED,
                    'updated_at'         => $date . ' 09:00:00',
                ]);
        }
    }
}
