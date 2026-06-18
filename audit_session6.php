<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BriefingSession;
use Illuminate\Support\Facades\DB;

$s = BriefingSession::with('attendances')->find(6);
if (!$s) { echo "Session #6 not found\n"; exit(1); }

echo "=== SESSION #6 ===\n";
echo "mp_check_status   : " . (is_object($s->mp_check_status) ? $s->mp_check_status->value : $s->mp_check_status) . "\n";
echo "summary_sufficient: " . ($s->summary_sufficient ? 'true' : 'false') . "\n";
echo "summary_headcount : " . $s->summary_headcount . "\n";
echo "readyManpowerCount: " . $s->readyManpowerCount() . "\n";
echo "pending_activity  : " . (($s->pending_activity ?? false) ? 'true' : 'false') . "\n";
echo "pending_activity_raw: " . var_export($s->getRawOriginal('pending_activity'), true) . "\n";
echo "isOperationallyReady: " . ($s->isOperationallyReady() ? 'true' : 'false') . "\n";
echo "date: " . $s->date . "\n";
echo "depot_id: " . $s->depot_id . "\n";
echo "\n";

$atts = $s->attendances;
echo "=== ATTENDANCES ===\n";
echo "Total             : " . $atts->count() . "\n";
echo "Present           : " . $atts->where('attendance_status', 'present')->count() . "\n";
$atts->where('attendance_status', function($v) { return true; });

// Count by final_mp_status using raw DB
$statusCounts = DB::table('briefing_attendances')
    ->where('session_id', 6)
    ->select('final_mp_status', DB::raw('count(*) as cnt'))
    ->groupBy('final_mp_status')
    ->get();
echo "\nfinal_mp_status breakdown:\n";
foreach ($statusCounts as $row) {
    echo "  '" . ($row->final_mp_status ?? 'NULL') . "': " . $row->cnt . "\n";
}

// Raw row detail
echo "\n=== FIT DETAIL (raw DB) ===\n";
$rows = DB::table('briefing_attendances')
    ->where('session_id', 6)
    ->select('id','attendance_status','has_ppe','fit_status','recheck_result','final_mp_status','recheck_required')
    ->get();
foreach ($rows as $r) {
    $passes = $r->attendance_status === 'present'
        && $r->has_ppe
        && ($r->recheck_result === 'FIT' || ($r->fit_status === 'FIT' && $r->recheck_result === null));
    echo sprintf("  id=%d present=%s ppe=%s fit=%s recheck=%s final=%s PASSES=%s\n",
        $r->id,
        $r->attendance_status,
        $r->has_ppe ? 'true' : 'false',
        $r->fit_status ?? 'null',
        $r->recheck_result ?? 'null',
        $r->final_mp_status ?? 'null',
        $passes ? 'YES' : 'NO'
    );
}

// Check if AppSheet evaluateBriefingSession was ever called for this session
echo "\n=== AppSheet evaluateBriefingSession conditions ===\n";
echo "pending_activity raw: " . var_export($s->getRawOriginal('pending_activity'), true) . "\n";
echo "Condition for Cleared: pending_activity=false AND readyCount >= required\n";
echo "  pending_activity=false? " . ((!($s->pending_activity ?? false)) ? 'YES' : 'NO') . "\n";
echo "  readyCount(" . $s->readyManpowerCount() . ") >= required(" . $s->summary_headcount . ")? " . ($s->readyManpowerCount() >= (int)$s->summary_headcount ? 'YES' : 'NO') . "\n";
echo "  => Expected mp_check_status: CLEARED\n";
echo "  => Actual   mp_check_status: " . (is_object($s->mp_check_status) ? $s->mp_check_status->value : $s->mp_check_status) . "\n";

// Check what writes happen on BriefingSession saving
echo "\n=== SAVING HOOK CHECK ===\n";
echo "BriefingSession::saving() writes: summary_sufficient via isOperationallyReady()\n";
echo "BriefingSession::saving() writes mp_check_status? NO\n";
echo "\nConclusion: mp_check_status can only be set to 'cleared' by AppSheetService::evaluateBriefingSession()\n";
echo "If AppSheet webhook was never called for session #6, mp_check_status stays at its initial value.\n";

// What is the initial value?
echo "\nInitial default: migration sets default='draft'\n";
echo "Shipment auto-create also uses 'mp_check_status' => 'draft'\n";
