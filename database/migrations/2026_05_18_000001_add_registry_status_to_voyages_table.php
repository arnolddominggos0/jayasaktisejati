<?php

use App\Enums\VoyageRegistryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->string('registry_status', 20)
                ->nullable()
                ->after('cargo_actual')
                ->index();

            $table->timestamp('archived_at')
                ->nullable()
                ->after('registry_status')
                ->index();
        });

        $this->backfillRegistryStatus();
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropIndex(['registry_status']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['registry_status', 'archived_at']);
        });
    }

    private function backfillRegistryStatus(): void
    {
        $rows = DB::table('voyages')->select([
            'id',
            'closing_at',
            'ata_at',
            'atd_at',
            'etd',
        ])->get();

        foreach ($rows as $row) {
            $status = $this->resolveStatus($row);

            DB::table('voyages')
                ->where('id', $row->id)
                ->update(['registry_status' => $status]);
        }
    }

    private function resolveStatus(\stdClass $row): string
    {
        if ($row->closing_at !== null) {
            return VoyageRegistryStatus::CLOSED->value;
        }

        if ($row->ata_at !== null) {
            return VoyageRegistryStatus::COMPLETED->value;
        }

        if ($row->atd_at !== null) {
            return VoyageRegistryStatus::ACTIVE->value;
        }

        if ($row->etd !== null && \Illuminate\Support\Carbon::parse($row->etd)->isPast()) {
            return VoyageRegistryStatus::DELAYED->value;
        }

        return VoyageRegistryStatus::PLANNED->value;
    }
};
