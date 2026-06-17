<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SC.3B.20 Phase 1 — Pivot table: BriefingSession ↔ Shipment (many-to-many).
 *
 * One BriefingSession can cover many Shipments/SPPBs in a single operational day.
 * This table is the foundation; workflow, gate, and sendToFc() changes come later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briefing_session_shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('briefing_session_id')
                ->constrained('briefing_sessions')
                ->cascadeOnDelete();

            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['briefing_session_id', 'shipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briefing_session_shipments');
    }
};
