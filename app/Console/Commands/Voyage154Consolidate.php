<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Voyage 154 — Backfill Consolidation
 *
 * The original import (ImportTamMay2026Units) created 1 Shipment per CSV row,
 * resulting in 17 shipments for 17 units across 9 unique SPPBs.
 *
 * This command corrects that by:
 *   1. Grouping all units by do_number (= SPPB number)
 *   2. Electing the lowest-ID shipment as master per SPPB group
 *   3. Reassigning all units in the group to the master shipment
 *   4. Deleting duplicate shipment_tracks from non-master shipments
 *      (tracks are exact duplicates — verified by pre-migration audit)
 *   5. Deleting the non-master (duplicate) shipments
 *   6. Updating doc_number on every master to the real SPPB / DO number
 *   7. Updating packages_total to reflect actual unit count per SPPB
 *
 * Expected result: 9 shipments · 17 units · 36 tracks — zero data loss.
 */
class Voyage154Consolidate extends Command
{
    protected $signature = 'voyage154:consolidate
                            {--dry-run : Show plan without writing to the database}';

    protected $description = 'Consolidate Voyage 154 shipments: group by SPPB (units.do_number), merge duplicates into master';

    private const VOYAGE_ID = 1; // voyages.id for Voyage 154 / KM Tanto Sejahtera

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->line('');
        $this->info($dryRun
            ? '[DRY-RUN] Simulating Voyage 154 consolidation — no writes will occur.'
            : 'Executing Voyage 154 consolidation...'
        );

        // ── Pre-flight snapshot ─────────────────────────────────────────────
        $this->line('');
        $this->line('──────────────────────────────────────────');
        $this->line(' PRE-MIGRATION STATE');
        $this->line('──────────────────────────────────────────');

        $before = $this->snapshot();
        $this->printSnapshot($before);

        if ($before['ships'] === 9 && $before['units'] === 17 && $before['tracks'] === 36) {
            $this->info('Already consolidated — nothing to do.');
            return self::SUCCESS;
        }

        // ── Build SPPB groups ───────────────────────────────────────────────
        $groups = DB::select("
            SELECT
                u.do_number,
                MIN(s.id)                                          AS master_id,
                string_agg(s.id::text, ',' ORDER BY s.id::text)   AS all_ids,
                COUNT(DISTINCT s.id)                               AS ship_count,
                COUNT(DISTINCT u.id)                               AS unit_count
            FROM shipments s
            JOIN units u ON u.shipment_id = s.id
            WHERE s.voyage_id = ?
            GROUP BY u.do_number
            ORDER BY u.do_number
        ", [self::VOYAGE_ID]);

        $this->line('');
        $this->line('──────────────────────────────────────────');
        $this->line(' SPPB GROUPS');
        $this->line('──────────────────────────────────────────');

        $allMergeIds = [];
        $groupData   = [];

        foreach ($groups as $g) {
            $ids      = array_map('intval', explode(',', $g->all_ids));
            $master   = (int) $g->master_id;
            $mergeIds = array_values(array_diff($ids, [$master]));

            $this->line(sprintf(
                '  SPPB %-28s master=%-4d merge=[%s] units=%d',
                $g->do_number,
                $master,
                empty($mergeIds) ? 'none' : implode(',', $mergeIds),
                $g->unit_count
            ));

            $groupData[]   = [
                'do_number'  => $g->do_number,
                'master_id'  => $master,
                'merge_ids'  => $mergeIds,
                'unit_count' => (int) $g->unit_count,
            ];
            $allMergeIds = array_merge($allMergeIds, $mergeIds);
        }

        if (empty($allMergeIds)) {
            $this->warn('No merge candidates found. Voyage 154 may already be consolidated.');
            return self::SUCCESS;
        }

        $this->line('');
        $this->line('Shipments to delete: [' . implode(',', $allMergeIds) . ']');

        if ($dryRun) {
            $this->line('');
            $this->warn('[DRY-RUN] Stopping before writes. Re-run without --dry-run to apply.');
            return self::SUCCESS;
        }

        // ── Execute inside a transaction ────────────────────────────────────
        DB::transaction(function () use ($groupData, $allMergeIds) {

            // Step 1 — Reassign units to master
            $this->line('');
            $this->line('── Step 1: Reassign units ──────────────');
            foreach ($groupData as $g) {
                if (empty($g['merge_ids'])) {
                    continue;
                }
                $mergeList = implode(',', $g['merge_ids']);
                $moved = DB::update(
                    "UPDATE units SET shipment_id = ? WHERE shipment_id IN ({$mergeList})",
                    [$g['master_id']]
                );
                $this->line("  SPPB {$g['do_number']}: moved {$moved} unit(s) → ship={$g['master_id']}");
            }

            // Step 2 — Delete duplicate tracks (exact dupes, verified by audit)
            $this->line('');
            $this->line('── Step 2: Delete duplicate tracks ─────');
            $mergeList = implode(',', $allMergeIds);
            $deletedTracks = DB::delete(
                "DELETE FROM shipment_tracks WHERE shipment_id IN ({$mergeList})"
            );
            $this->line("  Deleted {$deletedTracks} duplicate track rows from ships [{$mergeList}]");

            // Step 3 — Delete duplicate shipments
            $this->line('');
            $this->line('── Step 3: Delete duplicate shipments ──');
            $deletedShips = DB::delete(
                "DELETE FROM shipments WHERE id IN ({$mergeList})"
            );
            $this->line("  Deleted {$deletedShips} shipment rows: [{$mergeList}]");

            // Step 4 — Update doc_number on all masters (was auto-generated SPPB-YmdHis)
            $this->line('');
            $this->line('── Step 4: Update doc_number ────────────');
            foreach ($groupData as $g) {
                DB::update(
                    "UPDATE shipments SET doc_number = ? WHERE id = ?",
                    [$g['do_number'], $g['master_id']]
                );
                $this->line("  ship={$g['master_id']} doc_number → {$g['do_number']}");
            }

            // Step 5 — Update packages_total to reflect actual unit count per SPPB
            $this->line('');
            $this->line('── Step 5: Update packages_total ────────');
            foreach ($groupData as $g) {
                DB::update(
                    "UPDATE shipments SET packages_total = ?, container_qty = 1 WHERE id = ?",
                    [$g['unit_count'], $g['master_id']]
                );
                $this->line("  ship={$g['master_id']} packages_total → {$g['unit_count']}");
            }
        });

        // ── Post-validation ─────────────────────────────────────────────────
        $this->line('');
        $this->line('──────────────────────────────────────────');
        $this->line(' POST-MIGRATION STATE');
        $this->line('──────────────────────────────────────────');

        $after = $this->snapshot();
        $this->printSnapshot($after);

        // Detailed checks
        $orphans = DB::selectOne("
            SELECT COUNT(*) AS cnt FROM units
            WHERE shipment_id NOT IN (SELECT id FROM shipments)
        ")->cnt;

        $badDocNumbers = DB::selectOne("
            SELECT COUNT(*) AS cnt FROM shipments
            WHERE voyage_id = ?
              AND (doc_number IS NULL OR doc_number LIKE 'SPPB-%')
        ", [self::VOYAGE_ID])->cnt;

        $this->line('');
        $this->line('── Validation ───────────────────────────');

        $checks = [
            ['Shipments = 9',            $after['ships']  === 9,     "{$after['ships']} (expected 9)"],
            ['Units = 17',               $after['units']  === 17,    "{$after['units']} (expected 17)"],
            ['Tracks = 36',              $after['tracks'] === 36,    "{$after['tracks']} (expected 36)"],
            ['No orphan units',          (int) $orphans   === 0,     "orphans: {$orphans}"],
            ['All doc_numbers are SPPB', (int) $badDocNumbers === 0, "bad doc_numbers: {$badDocNumbers}"],
        ];

        $allPassed = true;
        foreach ($checks as [$label, $pass, $detail]) {
            $icon = $pass ? '✓' : '✗';
            $method = $pass ? 'info' : 'error';
            $this->$method("  {$icon} {$label} — {$detail}");
            if (! $pass) {
                $allPassed = false;
            }
        }

        $this->line('');
        if ($allPassed) {
            $this->info('BACKFILL COMPLETE — Voyage 154 data matches manifest.');
        } else {
            $this->error('VALIDATION FAILED — Review output above. Transaction was committed; investigate before re-running.');
            return self::FAILURE;
        }

        // Final state table
        $this->line('');
        $this->line('── Final shipment state ─────────────────');
        $final = DB::select("
            SELECT s.id, s.doc_number, s.status, s.packages_total,
                   COUNT(u.id) AS unit_count,
                   COUNT(st.id) AS track_count
            FROM shipments s
            LEFT JOIN units u  ON u.shipment_id  = s.id
            LEFT JOIN shipment_tracks st ON st.shipment_id = s.id
            WHERE s.voyage_id = ?
            GROUP BY s.id, s.doc_number, s.status, s.packages_total
            ORDER BY s.id
        ", [self::VOYAGE_ID]);

        foreach ($final as $row) {
            printf("  ship=%-4d doc=%-30s status=%-12s pkg=%-3d units=%-3d tracks=%d\n",
                $row->id, $row->doc_number ?? 'NULL', $row->status,
                $row->packages_total ?? 0, $row->unit_count, $row->track_count);
        }

        // Cleanup temp audit files if still present
        foreach (['__precheck.php', '__fkcheck.php', '__mastercheck.php'] as $tmp) {
            $path = base_path($tmp);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        return self::SUCCESS;
    }

    private function snapshot(): array
    {
        $row = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM shipments WHERE voyage_id = ?)                                           AS ships,
                (SELECT COUNT(*) FROM units u JOIN shipments s ON s.id = u.shipment_id WHERE s.voyage_id = ?) AS units,
                (SELECT COUNT(*) FROM shipment_tracks st JOIN shipments s ON s.id = st.shipment_id WHERE s.voyage_id = ?) AS tracks
        ", [self::VOYAGE_ID, self::VOYAGE_ID, self::VOYAGE_ID]);

        return [
            'ships'  => (int) $row->ships,
            'units'  => (int) $row->units,
            'tracks' => (int) $row->tracks,
        ];
    }

    private function printSnapshot(array $s): void
    {
        $this->line("  Shipments : {$s['ships']}");
        $this->line("  Units     : {$s['units']}");
        $this->line("  Tracks    : {$s['tracks']}");
    }
}
