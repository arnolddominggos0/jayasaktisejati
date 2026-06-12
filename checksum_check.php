<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$result = DB::select("
    SELECT 
        u.do_number,
        s.id AS shipment_id,
        s.status AS ship_status,
        COUNT(DISTINCT u2.id) AS unit_count,
        COUNT(st.id) AS track_count,
        STRING_AGG(DISTINCT st.status, ',' ORDER BY st.status) AS statuses,
        MAX(st.tracked_at) AS last_tracked_at
    FROM shipments s
    JOIN units u ON u.shipment_id = s.id
    LEFT JOIN units u2 ON u2.shipment_id = s.id
    LEFT JOIN shipment_tracks st ON st.shipment_id = s.id
    WHERE s.voyage_id = 1
    GROUP BY u.do_number, s.id, s.status
    ORDER BY u.do_number, s.id
");

foreach ($result as $r) {
    printf(
        \"SPPB %-25s | ship=%-3d | units=%-2d | tracks=%-3d | statuses=%-40s | last=%s\n\",
        $r->do_number, $r->shipment_id, $r->unit_count, $r->track_count,
        $r->statuses ?? 'NULL', $r->last_tracked_at ?? 'NULL'
    );
}
