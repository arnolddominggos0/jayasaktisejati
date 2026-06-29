<?php

namespace App\Support\Monitoring;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Reusable DISTINCT ON subquery that returns one row per shipment —
 * the most recent track event by id.
 *
 * Used wherever a LEFT JOIN to the latest shipment_track is required:
 *   • ExceptionCountQueryBuilder (aggregate band counts)
 *   • UnitMonitoringQueryBuilder (exception sort + filter)
 *
 * SQL pattern:
 *   SELECT DISTINCT ON (shipment_id)
 *          shipment_id, status AS {statusAlias}, tracked_at AS {trackedAtAlias}
 *   FROM   shipment_tracks
 *   ORDER  BY shipment_id, id DESC
 *
 * The composite index (shipment_id, id DESC) on shipment_tracks eliminates
 * the incremental sort and makes this subquery O(n) rather than O(n log n).
 */
final class LatestTrackSubquery
{
    /**
     * Return the DISTINCT ON subquery builder ready for joinSub / leftJoinSub.
     *
     * @param string $statusAlias    Column alias for the status value
     * @param string $trackedAtAlias Column alias for the tracked_at timestamp
     */
    public static function build(
        string $statusAlias    = 'lt_status',
        string $trackedAtAlias = 'lt_tracked_at',
    ): QueryBuilder {
        return DB::table('shipment_tracks')
            ->selectRaw(
                "DISTINCT ON (shipment_id) shipment_id,"
                . " status        AS {$statusAlias},"
                . " tracked_at    AS {$trackedAtAlias}"
            )
            ->orderByRaw('shipment_id, id DESC');
    }
}
