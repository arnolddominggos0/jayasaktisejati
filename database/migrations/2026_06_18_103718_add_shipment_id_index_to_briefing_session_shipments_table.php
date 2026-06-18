<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_session_shipments', function (Blueprint $table) {
            // MpCheckGate::ensureApproved() queries from the shipment side:
            //   $shipment->briefingSessions()->where('mp_check_status','cleared')->exists()
            // Without this index the pivot is scanned from the shipment_id side without support.
            $table->index('shipment_id');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_session_shipments', function (Blueprint $table) {
            $table->dropIndex(['shipment_id']);
        });
    }
};
