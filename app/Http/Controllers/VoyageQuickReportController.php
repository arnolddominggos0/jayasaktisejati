<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Models\Voyage;
use App\Services\ShipmentKpiEvaluator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VoyageQuickReportController extends Controller
{
    public function generate(Request $request, int $voyageId, ShipmentKpiEvaluator $evaluator)
    {
        $voyage = Voyage::with('vessel')->findOrFail($voyageId);

        $shipments = Shipment::where('voyage_id', $voyageId)
            ->whereNotNull('delivered_at')
            ->with([
                'tracks:id,shipment_id,status,tracked_at',
                'units:id,shipment_id,chassis_no,engine_no,model_no,color',
            ])
            ->get();

        // ── KPI evaluation ─────────────────────────────────────────────────
        $kpiRows = [];
        $sumDw = $sumSa = $sumDo = $sumTt = 0.0;
        $nKpi  = $dwOk = $dwNg = $saOk = $saNg = $doOk = $doNg = $ovOk = $ovNg = 0;

        foreach ($shipments as $shipment) {
            $ev = $evaluator->evaluateManadoKpi($shipment);
            if (! ($ev['applies'] ?? false)) {
                continue;
            }

            $s    = $ev['summary'] ?? [];
            $dw   = $s['dwelling']['actual'] ?? null;
            $sa   = $s['sailing']['actual'] ?? null;
            $do   = $s['dooring']['actual'] ?? null;
            $tt   = $s['total']['actual'] ?? null;
            $dwSt = $s['dwelling']['status'] ?? 'PENDING';
            $saSt = $s['sailing']['status'] ?? 'PENDING';
            $doSt = $s['dooring']['status'] ?? 'PENDING';
            $ttSt = $s['total']['status'] ?? 'PENDING';

            if ($dwSt === 'OK')   $dwOk++; elseif ($dwSt === 'LATE') $dwNg++;
            if ($saSt === 'OK')   $saOk++; elseif ($saSt === 'LATE') $saNg++;
            if ($doSt === 'OK')   $doOk++; elseif ($doSt === 'LATE') $doNg++;
            if ($ttSt === 'OK')   $ovOk++; elseif ($ttSt === 'LATE') $ovNg++;

            if ($dw !== null && $sa !== null && $do !== null && $tt !== null) {
                $sumDw += $dw; $sumSa += $sa; $sumDo += $do; $sumTt += $tt;
                $nKpi++;
            }

            $units = $shipment->units ?? collect();
            if ($units->isEmpty()) {
                $kpiRows[] = [
                    'identifier'  => $shipment->code ?? '-',
                    'engine_no'   => '-',
                    'model'       => '-',
                    'dwelling'    => $dw,   'dwelling_st' => $dwSt,
                    'sailing'     => $sa,   'sailing_st'  => $saSt,
                    'dooring'     => $do,   'dooring_st'  => $doSt,
                    'lt_total'    => $tt,   'lt_status'   => $ttSt,
                ];
            } else {
                foreach ($units as $unit) {
                    $kpiRows[] = [
                        'identifier'  => $unit->chassis_no ?? $shipment->code ?? '-',
                        'engine_no'   => $unit->engine_no ?? '-',
                        'model'       => $unit->model_no ?? '-',
                        'dwelling'    => $dw,   'dwelling_st' => $dwSt,
                        'sailing'     => $sa,   'sailing_st'  => $saSt,
                        'dooring'     => $do,   'dooring_st'  => $doSt,
                        'lt_total'    => $tt,   'lt_status'   => $ttSt,
                    ];
                }
            }
        }

        // ── Aggregates & achievement ────────────────────────────────────────
        $thresholds = $evaluator->getManadoThresholds();
        $avgDw = $nKpi > 0 ? round($sumDw / $nKpi, 2) : null;
        $avgSa = $nKpi > 0 ? round($sumSa / $nKpi, 2) : null;
        $avgDo = $nKpi > 0 ? round($sumDo / $nKpi, 2) : null;
        $avgTt = $nKpi > 0 ? round($sumTt / $nKpi, 2) : null;

        $totalDw = $dwOk + $dwNg;
        $totalSa = $saOk + $saNg;
        $totalDo = $doOk + $doNg;
        $totalOv = $ovOk + $ovNg;

        $achieveDw = $totalDw > 0 ? round($dwOk / $totalDw * 100, 1) : 0;
        $achieveSa = $totalSa > 0 ? round($saOk / $totalSa * 100, 1) : 0;
        $achieveDo = $totalDo > 0 ? round($doOk / $totalDo * 100, 1) : 0;
        $achieveOv = $totalOv > 0 ? round($ovOk / $totalOv * 100, 1) : 0;

        // ── KPI impact (avg actual − target) ───────────────────────────────
        $dwTarget   = $thresholds['dwelling_days'] ?? 6;
        $saTarget   = $thresholds['sailing_days']  ?? 10;
        $doTarget   = $thresholds['dooring_days']  ?? 3;

        $dwDelay    = $avgDw !== null ? round($avgDw - $dwTarget, 2) : null;
        $saDelay    = $avgSa !== null ? round($avgSa - $saTarget, 2) : null;
        $doDelay    = $avgDo !== null ? round($avgDo - $doTarget, 2) : null;
        $totalDelay = ($dwDelay !== null && $saDelay !== null && $doDelay !== null)
            ? round($dwDelay + $saDelay + $doDelay, 2)
            : null;

        // ── Root cause ─────────────────────────────────────────────────────
        $rootCauses = [];
        if ($avgDw !== null && $avgDw > $dwTarget) {
            $rootCauses['dwelling'] = sprintf(
                'Terjadi keterlambatan pada fase Dwelling (Gate In → Kapal Berangkat). '
                . 'Rata-rata %.1f hari dari target %d hari (+%.1f hari). '
                . 'Terdapat %d dari %d unit berstatus NG. '
                . 'Indikasi penyebab: antrian port loading, keterlambatan stuffing container, atau menunggu readiness kapal.',
                $avgDw, $dwTarget, $dwDelay, $dwNg, $totalDw
            );
        }
        if ($avgSa !== null && $avgSa > $saTarget) {
            $rootCauses['sailing'] = sprintf(
                'Terjadi keterlambatan pada fase Sailing (Kapal Berangkat → Kapal Tiba). '
                . 'Rata-rata %.1f hari dari target %d hari (+%.1f hari). '
                . 'Terdapat %d dari %d unit berstatus NG. '
                . 'Indikasi penyebab: penundaan keberangkatan kapal, cuaca buruk di jalur pelayaran, atau rute tidak langsung.',
                $avgSa, $saTarget, $saDelay, $saNg, $totalSa
            );
        }
        if ($avgDo !== null && $avgDo > $doTarget) {
            $rootCauses['dooring'] = sprintf(
                'Terjadi keterlambatan pada fase Dooring (Kapal Tiba → Pengiriman ke Tujuan Akhir). '
                . 'Rata-rata %.1f hari dari target %d hari (+%.1f hari). '
                . 'Terdapat %d dari %d unit berstatus NG. '
                . 'Indikasi penyebab: kapasitas armada distribusi tidak mencukupi, antrian unloading pelabuhan, atau jarak distributor ke tujuan akhir.',
                $avgDo, $doTarget, $doDelay, $doNg, $totalDo
            );
        }

        // ── Countermeasures ────────────────────────────────────────────────
        $countermeasures = [];
        if (isset($rootCauses['dwelling'])) {
            $countermeasures[] = [
                'phase' => 'Dwelling',
                'action' => 'Koordinasi dengan pihak pelabuhan untuk mempercepat proses stuffing dan booking slot kapal. '
                    . 'Evaluasi ulang SOP pre-loading dan cut-off time pengiriman ke depot. '
                    . 'Monitoring jadwal peti kemas dan port availability secara real-time.',
            ];
        }
        if (isset($rootCauses['sailing'])) {
            $countermeasures[] = [
                'phase' => 'Sailing',
                'action' => 'Review jadwal keberangkatan kapal bersama shipping line, pastikan kesesuaian ETD vs ATD. '
                    . 'Monitor kondisi cuaca dan kesiapan teknis armada. '
                    . 'Koordinasi eskalasi ke tim shipping apabila terjadi port congestion.',
            ];
        }
        if (isset($rootCauses['dooring'])) {
            $countermeasures[] = [
                'phase' => 'Dooring',
                'action' => 'Optimalkan kapasitas armada distribusi dan penjadwalan driver di pelabuhan tujuan. '
                    . 'Koordinasi lebih awal dengan dealer/penerima untuk kesiapan penerimaan unit. '
                    . 'Prioritaskan pengiriman unit yang berstatus NG dalam scheduling armada.',
            ];
        }
        if (empty($countermeasures)) {
            $countermeasures[] = [
                'phase' => 'Semua Fase',
                'action' => 'Seluruh fase lead time berjalan sesuai target KPI. '
                    . 'Pertahankan koordinasi antar divisi dan lakukan monitoring berkala untuk menjaga konsistensi kinerja.',
            ];
        }

        // ── Evidence files ─────────────────────────────────────────────────
        $evidenceBasePath = is_dir(storage_path('app/evidence_qr'))
            ? storage_path('app/evidence_qr')
            : storage_path('app/public/evidence_qr');

        $evidenceItems = [];
        if (is_dir($evidenceBasePath)) {
            $voyageNo   = (string) $voyage->voyage_no;
            $vesselName = strtoupper($voyage->vessel?->name ?? '');

            // 1. Prefer dedicated subdirectory (voyageNo or voyageId)
            $pool = [];
            foreach ([$evidenceBasePath . DIRECTORY_SEPARATOR . $voyageNo,
                      $evidenceBasePath . DIRECTORY_SEPARATOR . $voyageId] as $subDir) {
                if (is_dir($subDir)) {
                    foreach (glob($subDir . DIRECTORY_SEPARATOR . '*') as $f) {
                        if (is_file($f)) {
                            $pool[] = $f;
                        }
                    }
                    if (! empty($pool)) {
                        break;
                    }
                }
            }

            // 2. Fallback: root dir filtered by voyage number in filename
            if (empty($pool)) {
                foreach (glob($evidenceBasePath . DIRECTORY_SEPARATOR . '*') as $f) {
                    if (! is_file($f)) {
                        continue;
                    }
                    $bn = strtoupper(basename($f));
                    if (
                        str_contains($bn, 'V.' . $voyageNo)
                        || str_contains($bn, 'V-' . $voyageNo)
                        || preg_match('/\b' . preg_quote($voyageNo, '/') . '\b/', $bn)
                    ) {
                        $pool[] = $f;
                    }
                }
            }

            foreach ($pool as $filePath) {
                $ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $basename = basename($filePath);
                $fileSize = filesize($filePath);

                if (in_array($ext, ['jpg', 'jpeg', 'png']) && $fileSize < 5 * 1024 * 1024) {
                    $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                    $evidenceItems[] = [
                        'type' => 'image',
                        'name' => $basename,
                        'src'  => 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($filePath)),
                        'size' => $fileSize,
                    ];
                } elseif ($ext === 'pdf') {
                    $evidenceItems[] = [
                        'type' => 'pdf',
                        'name' => $basename,
                        'size' => $fileSize,
                    ];
                } else {
                    $evidenceItems[] = [
                        'type' => 'other',
                        'name' => $basename,
                        'size' => $fileSize,
                    ];
                }
            }
        }

        // ── Logo ───────────────────────────────────────────────────────────
        $logoDataUri = null;
        foreach (['images/logo.png', 'images/logo.jpg', 'favicon.ico'] as $logoFile) {
            $logoPath = public_path($logoFile);
            if (file_exists($logoPath)) {
                $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                $mime = match ($ext) {
                    'png' => 'image/png',
                    'jpg', 'jpeg' => 'image/jpeg',
                    default => 'image/png',
                };
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                break;
            }
        }

        // ── QR reference ───────────────────────────────────────────────────
        $qrRef = 'QR-V' . $voyage->voyage_no . '-' . now()->format('Ymd') . '-' . strtoupper(substr(md5((string) $voyageId), 0, 6));
        $qrSvg = QrCode::format('svg')->size(80)->margin(0)->generate($qrRef);

        // ── Build data bag ─────────────────────────────────────────────────
        $pdfData = [
            'brand'           => [
                'name'  => config('app.name', 'PT Jaya Sakti Sejati'),
                'color' => '#0137A1',
                'logo'  => $logoDataUri,
            ],
            'qr_ref'          => $qrRef,
            'qr_svg'          => $qrSvg,
            'voyage'          => $voyage,
            'period'          => $voyage->period_month
                ? Carbon::parse($voyage->period_month)->translatedFormat('F Y')
                : '-',
            'kpi_rows'        => $kpiRows,
            'thresholds'      => $thresholds,
            'avg_dwelling'    => $avgDw,
            'avg_sailing'     => $avgSa,
            'avg_dooring'     => $avgDo,
            'avg_lt'          => $avgTt,
            'dw_ok'  => $dwOk,  'dw_ng'  => $dwNg,  'achieve_dw' => $achieveDw,
            'sa_ok'  => $saOk,  'sa_ng'  => $saNg,  'achieve_sa' => $achieveSa,
            'do_ok'  => $doOk,  'do_ng'  => $doNg,  'achieve_do' => $achieveDo,
            'ov_ok'  => $ovOk,  'ov_ng'  => $ovNg,  'achieve_ov' => $achieveOv,
            'dw_delay'        => $dwDelay,
            'sa_delay'        => $saDelay,
            'do_delay'        => $doDelay,
            'total_delay'     => $totalDelay,
            'root_causes'     => $rootCauses,
            'countermeasures' => $countermeasures,
            'evidence_items'  => $evidenceItems,
            'generated_at'    => now()->format('d M Y H:i'),
            'total_units'     => count($kpiRows),
            'total_shipments' => $shipments->count(),
        ];

        $vesselSlug = str_replace(' ', '_', $voyage->vessel?->name ?? 'Vessel');
        $filename   = 'QR-' . $vesselSlug . '-V' . $voyage->voyage_no . '.pdf';

        return Pdf::loadView('pdf.voyage-quick-report', $pdfData)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'dpi'                  => 110,
                'defaultFont'          => 'DejaVu Sans',
            ])
            ->stream($filename);
    }
}
