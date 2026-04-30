<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyage_milestones', function (Blueprint $table) {

            if (Schema::hasColumn('voyage_milestones', 'position')) {
                $table->dropColumn('position');
            }

            if (Schema::hasColumn('voyage_milestones', 'location_note')) {
                $table->dropColumn('location_note');
            }

            if (!Schema::hasColumn('voyage_milestones', 'port_id')) {
                $table->foreignId('port_id')
                    ->nullable()
                    ->after('actual_date')
                    ->constrained('ports')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('voyage_milestones', function (Blueprint $table) {

            if (Schema::hasColumn('voyage_milestones', 'port_id')) {
                $table->dropForeign(['port_id']);
                $table->dropColumn('port_id');
            }

            $table->string('position')->nullable();
            $table->string('location_note')->nullable();
        });
    }
};
