<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('shipment_tracks', 'user_id') && ! Schema::hasColumn('shipment_tracks', 'created_by')) {
            Schema::table('shipment_tracks', function (Blueprint $t) {
                $t->renameColumn('user_id', 'created_by'); // butuh doctrine/dbal jika kolom dipakai FK
            });
        }

        Schema::table('shipment_tracks', function (Blueprint $t) {
            if (! Schema::hasColumn('shipment_tracks', 'created_by')) {
                $t->foreignId('created_by')->nullable()->after('note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('shipment_tracks', 'tracked_at')) {
                $t->timestampTz('tracked_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('shipment_tracks', 'status')) {
                $t->string('status')->after('shipment_id');
            }
            if (! Schema::hasColumn('shipment_tracks', 'location')) {
                $t->string('location')->nullable()->after('tracked_at');
            }
            if (! Schema::hasColumn('shipment_tracks', 'note')) {
                $t->text('note')->nullable()->after('location');
            }
        });

        DB::statement("UPDATE shipment_tracks SET tracked_at = COALESCE(tracked_at, created_at)");
        DB::statement("UPDATE shipment_tracks SET status = COALESCE(NULLIF(status, ''), 'pickup')");

        DB::statement("ALTER TABLE shipment_tracks ALTER COLUMN tracked_at SET DEFAULT now()");
        DB::statement("ALTER TABLE shipment_tracks ALTER COLUMN tracked_at SET NOT NULL");

        $idxExists = DB::selectOne("
            SELECT 1 FROM pg_indexes
            WHERE tablename = 'shipment_tracks'
              AND indexname = 'shipment_tracks_shipment_id_tracked_at_index'
        ");
        if (! $idxExists) {
            Schema::table('shipment_tracks', function (Blueprint $t) {
                $t->index(['shipment_id', 'tracked_at']);
            });
        }

        if (Schema::hasColumn('shipment_tracks', 'checkpoint')) {
            Schema::table('shipment_tracks', function (Blueprint $t) {
                $t->dropColumn('checkpoint');
            });
        }
        if (Schema::hasColumn('shipment_tracks', 'meta')) {
            Schema::table('shipment_tracks', function (Blueprint $t) {
                $t->dropColumn('meta');
            });
        }
    }

    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $t) {
            if (! Schema::hasColumn('shipment_tracks', 'checkpoint')) {
                $t->string('checkpoint')->nullable();
            }
            if (! Schema::hasColumn('shipment_tracks', 'meta')) {
                $t->json('meta')->nullable();
            }
            if (Schema::hasColumn('shipment_tracks', 'created_by')) {
                $t->dropConstrainedForeignId('created_by'); 
            }
            });
    }
};
