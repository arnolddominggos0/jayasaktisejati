<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Voyage;

class SyncVoyageSchedules extends Command
{
    protected $signature = 'voyage:sync-schedules {--chunk=200}';
    protected $description = 'Sync all voyages into shipping_schedules by calling Voyage::syncSchedule()';

    public function handle()
    {
        $chunk = (int) $this->option('chunk') ?: 200;

        Voyage::chunk($chunk, function ($rows) {
            foreach ($rows as $v) {
                try {
                    $v->syncSchedule();
                    $this->line("Synced voyage #{$v->id}");
                } catch (\Throwable $e) {
                    $this->error("Failed voyage {$v->id}: " . $e->getMessage());
                }
            }
        });

        return 0;
    }
}
