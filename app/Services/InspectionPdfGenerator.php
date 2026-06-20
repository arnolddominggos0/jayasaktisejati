<?php

namespace App\Services;

use App\Models\UnitInspection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InspectionPdfGenerator
{
    private const STAGE_DIR = [
        'pickup'         => 'pickup',
        'handover_depot' => 'handover',
        'loading'        => 'loading',
        'unloading'      => 'unloading',
        'selfdrive'      => 'selfdrive',
        'dooring'        => 'dooring',
    ];

    /**
     * Generate a PDF evidence file for a completed inspection.
     *
     * Immutability: caller is responsible for only invoking this once.
     * The submit() guard on InspectUnitPage (isReadOnly check) naturally
     * prevents regeneration — a submitted inspection cannot be re-submitted.
     *
     * @return string Storage-relative path within the public disk.
     */
    public function generate(UnitInspection $inspection): string
    {
        $inspection->loadMissing([
            'unit.shipment.customer',
            'items',
            'checkedBy',
        ]);

        $stageDir     = self::STAGE_DIR[$inspection->stage] ?? $inspection->stage;
        $relativePath = "inspection-pdfs/{$stageDir}/inspection-{$inspection->id}.pdf";

        $signatureDataUri = $this->buildSignatureDataUri($inspection->signature_path);

        $pdf = Pdf::loadView('pdf.inspection-report', [
            'inspection'       => $inspection,
            'unit'             => $inspection->unit,
            'shipment'         => $inspection->unit->shipment,
            'items'            => $inspection->items->groupBy('category'),
            'signatureDataUri' => $signatureDataUri,
        ])->setPaper('a4', 'portrait');

        Storage::disk('public')->put($relativePath, $pdf->output());

        return $relativePath;
    }

    private function buildSignatureDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $raw      = Storage::disk('public')->get($path);
        $mime     = Storage::disk('public')->mimeType($path);
        $mimeType = is_string($mime) ? $mime : 'image/jpeg';

        return 'data:' . $mimeType . ';base64,' . base64_encode($raw);
    }
}
