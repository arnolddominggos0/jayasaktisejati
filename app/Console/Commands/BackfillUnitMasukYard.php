<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time backfill: migrates "Unit Masuk Yard/PDC: X unit" from notes
 * into the dedicated unit_masuk_yard column, then cleans the notes field.
 *
 * Usage:
 *   php artisan briefing:backfill-unit-masuk-yard            # apply changes
 *   php artisan briefing:backfill-unit-masuk-yard --dry-run  # preview only
 */
class BackfillUnitMasukYard extends Command
{
    protected $signature = 'briefing:backfill-unit-masuk-yard
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Backfill unit_masuk_yard from notes field (one-time migration)';

    /** Pattern to detect the unit line anywhere in notes (case-insensitive). */
    private const DETECT  = '/Unit Masuk Yard\/PDC:\s*(\d+)/i';

    /** Pattern to strip the entire unit line (including trailing newline). */
    private const STRIP   = '/Unit Masuk Yard\/PDC:[^\n]*\n?/i';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Only process rows that still have null unit_masuk_yard AND contain
        // the legacy pattern somewhere in their notes.
        $records = DB::table('briefing_sessions')
            ->whereNull('unit_masuk_yard')
            ->whereNotNull('notes')
            ->where('notes', 'like', '%Unit Masuk Yard/PDC:%')
            ->orderBy('date')
            ->get(['id', 'date', 'notes']);

        if ($records->isEmpty()) {
            $this->info('Tidak ada record yang perlu dimigrasi — sudah bersih.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Ditemukan %d record untuk dimigrasi.', $records->count()));

        if ($dryRun) {
            $this->warn('Mode DRY RUN — tidak ada perubahan yang akan disimpan.');
        }

        $this->newLine();

        $headers = ['ID', 'Tanggal', 'Unit', 'Notes Baru'];
        $rows    = [];
        $updated = 0;

        foreach ($records as $record) {
            if (! preg_match(self::DETECT, $record->notes, $m)) {
                // Pattern detected by LIKE but not by regex — skip safely.
                $this->warn("  ID {$record->id}: pattern tidak cocok setelah regex — dilewati.");
                continue;
            }

            $unit = (int) $m[1];

            // Remove the unit line and trim whitespace.
            $cleanNotes = preg_replace(self::STRIP, '', $record->notes);
            $cleanNotes = trim((string) $cleanNotes);
            $cleanNotes = $cleanNotes !== '' ? $cleanNotes : null;

            $rows[] = [
                $record->id,
                $record->date,
                $unit,
                mb_strimwidth($cleanNotes ?? '(null)', 0, 60, '…'),
            ];

            if (! $dryRun) {
                DB::table('briefing_sessions')
                    ->where('id', $record->id)
                    ->update([
                        'unit_masuk_yard' => $unit,
                        'notes'           => $cleanNotes,
                        'updated_at'      => now(),
                    ]);
            }

            $updated++;
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($dryRun) {
            $this->warn("DRY RUN selesai. {$updated} record AKAN diupdate (jalankan tanpa --dry-run untuk apply).");
        } else {
            $this->info("Selesai. {$updated} record berhasil dimigrasi.");
        }

        return self::SUCCESS;
    }
}
