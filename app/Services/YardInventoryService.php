<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for all Yard Inventory KPIs.
 *
 * All methods are scoped to (date, depot_id).
 * Source of truth: shipment_tracks + unit_inspections + units + shipments.
 * briefing_sessions is NEVER used here.
 *
 * Exit-track rules (from SC.5D.0 audit):
 *   Rack / Flat Rack (sea mode, vehicle_loading IN rack, flat_rack) → delivery_to_port
 *   Non-rack (all other)                                            → stuffing
 */
class YardInventoryService
{
    // Rack = sea/sea_freight mode AND vehicle_loading IN (rack, flat_rack).
    private const RACK_SQL = "(s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack'))";

    // Exit track condition used in correlated NOT EXISTS — references outer `s` alias.
    private const EXIT_SQL = "
        (
            (" . self::RACK_SQL . " AND st_exit.status = 'delivery_to_port')
            OR
            (NOT " . self::RACK_SQL . " AND st_exit.status = 'stuffing')
        )
    ";

    // ──────────────────────────────────────────────────────────────────────────
    // Unit Masuk Yard
    //
    // Unit whose shipment has a 'handover' track with tracked_at::date = $date
    // and shipment is assigned to $depotId.
    // ──────────────────────────────────────────────────────────────────────────

    public function getUnitMasukYard(Carbon $date, int $depotId): int
    {
        return (int) DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st')
                ->whereColumn('st.shipment_id', 's.id')
                ->where('st.status', 'handover')
                ->whereNotNull('st.tracked_at')
                ->whereDate('st.tracked_at', $date->toDateString())
            )
            ->count('u.id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Unit Keluar Yard
    //
    // Unit that entered yard on $date AND has the appropriate exit track:
    //   Non-rack → stuffing track with tracked_at NOT NULL
    //   Rack     → delivery_to_port track with tracked_at NOT NULL
    //
    // NEVER count both stuffing AND delivery_to_port to avoid double-counting.
    // ──────────────────────────────────────────────────────────────────────────

    public function getUnitKeluarYard(Carbon $date, int $depotId): int
    {
        $dateStr = $date->toDateString();

        $nonRack = (int) DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->whereRaw('NOT ' . self::RACK_SQL)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st')
                ->whereColumn('st.shipment_id', 's.id')
                ->where('st.status', 'handover')
                ->whereNotNull('st.tracked_at')
                ->whereDate('st.tracked_at', $dateStr)
            )
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st2')
                ->whereColumn('st2.shipment_id', 's.id')
                ->where('st2.status', 'stuffing')
                ->whereNotNull('st2.tracked_at')
            )
            ->count('u.id');

        $rack = (int) DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->whereRaw(self::RACK_SQL)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st')
                ->whereColumn('st.shipment_id', 's.id')
                ->where('st.status', 'handover')
                ->whereNotNull('st.tracked_at')
                ->whereDate('st.tracked_at', $dateStr)
            )
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st2')
                ->whereColumn('st2.shipment_id', 's.id')
                ->where('st2.status', 'delivery_to_port')
                ->whereNotNull('st2.tracked_at')
            )
            ->count('u.id');

        return $nonRack + $rack;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Unit Dalam Yard
    //
    // = max(0, masuk - keluar)
    // Invariant: never negative.
    // ──────────────────────────────────────────────────────────────────────────

    public function getUnitDalamYard(Carbon $date, int $depotId): int
    {
        return max(0,
            $this->getUnitMasukYard($date, $depotId) -
            $this->getUnitKeluarYard($date, $depotId)
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Unit Siap Loading
    //
    // Subset of Dalam Yard:
    //   - Entered yard on $date
    //   - NOT yet exited (no exit track)
    //   - Has handover_depot inspection with gate_decision IN (accept, allow_with_remark)
    //     AND submitted_at IS NOT NULL
    //
    // Invariant: Siap Loading <= Dalam Yard.
    // ──────────────────────────────────────────────────────────────────────────

    public function getUnitSiapLoading(Carbon $date, int $depotId): int
    {
        return (int) DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st')
                ->whereColumn('st.shipment_id', 's.id')
                ->where('st.status', 'handover')
                ->whereNotNull('st.tracked_at')
                ->whereDate('st.tracked_at', $date->toDateString())
            )
            ->whereNotExists(fn ($q) => $q
                ->from('shipment_tracks as st_exit')
                ->whereColumn('st_exit.shipment_id', 's.id')
                ->whereNotNull('st_exit.tracked_at')
                ->whereRaw(self::EXIT_SQL)
            )
            ->whereExists(fn ($q) => $q
                ->from('unit_inspections as ui')
                ->whereColumn('ui.unit_id', 'u.id')
                ->where('ui.stage', 'handover_depot')
                ->whereIn('ui.gate_decision', ['accept', 'allow_with_remark'])
                ->whereNotNull('ui.submitted_at')
            )
            ->count('u.id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Unit Bermasalah
    //
    // Subset of Dalam Yard:
    //   - Entered yard on $date
    //   - NOT yet exited
    //   - Has handover_depot inspection with gate_decision = return_to_pdc
    //
    // Invariant: Bermasalah <= Dalam Yard.
    // ──────────────────────────────────────────────────────────────────────────

    public function getUnitBermasalah(Carbon $date, int $depotId): int
    {
        return (int) DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st')
                ->whereColumn('st.shipment_id', 's.id')
                ->where('st.status', 'handover')
                ->whereNotNull('st.tracked_at')
                ->whereDate('st.tracked_at', $date->toDateString())
            )
            ->whereNotExists(fn ($q) => $q
                ->from('shipment_tracks as st_exit')
                ->whereColumn('st_exit.shipment_id', 's.id')
                ->whereNotNull('st_exit.tracked_at')
                ->whereRaw(self::EXIT_SQL)
            )
            ->whereExists(fn ($q) => $q
                ->from('unit_inspections as ui')
                ->whereColumn('ui.unit_id', 'u.id')
                ->where('ui.stage', 'handover_depot')
                ->where('ui.gate_decision', 'return_to_pdc')
            )
            ->count('u.id');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Snapshot — all 5 KPIs in a single call (3 DB queries).
    // Masuk and Keluar are fetched independently; Dalam = max(0, M-K).
    // Siap and Bermasalah each require one query.
    // ──────────────────────────────────────────────────────────────────────────

    public function snapshot(Carbon $date, int $depotId): array
    {
        $masuk   = $this->getUnitMasukYard($date, $depotId);
        $keluar  = $this->getUnitKeluarYard($date, $depotId);
        $dalam   = max(0, $masuk - $keluar);
        $siap    = $this->getUnitSiapLoading($date, $depotId);
        $masalah = $this->getUnitBermasalah($date, $depotId);

        return compact('masuk', 'keluar', 'dalam', 'siap', 'masalah');
    }
}
