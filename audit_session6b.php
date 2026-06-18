<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Check actual columns
$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'briefing_attendances' ORDER BY ordinal_position");
echo "=== briefing_attendances COLUMNS ===\n";
foreach ($cols as $c) echo "  " . $c->column_name . "\n";

echo "\n=== SESSION #6 raw ===\n";
$s = DB::table('briefing_sessions')->find(6);
echo "mp_check_status   : " . $s->mp_check_status . "\n";
echo "summary_sufficient: " . ($s->summary_sufficient ? 'true' : 'false') . "\n";
echo "summary_headcount : " . $s->summary_headcount . "\n";
echo "pending_activity  : " . var_export($s->pending_activity, true) . "\n";
echo "date              : " . $s->date . "\n";
echo "depot_id          : " . $s->depot_id . "\n";

echo "\n=== ATTENDANCES raw ===\n";
$atts = DB::table('briefing_attendances')->where('session_id', 6)->get();
echo "Count: " . $atts->count() . "\n";
if ($atts->count() > 0) {
    $first = (array) $atts->first();
    echo "Columns: " . implode(', ', array_keys($first)) . "\n";
    echo "\nAll rows:\n";
    foreach ($atts as $a) {
        $a = (array) $a;
        echo "  id=" . $a['id']
            . " attendance_status=" . ($a['attendance_status'] ?? 'null')
            . " has_ppe=" . ($a['has_ppe'] ? 'true' : 'false')
            . " fit_status=" . ($a['fit_status'] ?? 'null')
            . " recheck_result=" . ($a['recheck_result'] ?? 'null')
            . "\n";
    }
}
