<?php
// app/Http/Controllers/ShipmentPrintController.php
namespace App\Http\Controllers;

use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Milon\Barcode\DNS1D;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ShipmentPrintController extends Controller
{
    public function resi(Request $request, Shipment $shipment)
    {
        $rels = [];
        foreach (['branch', 'originBranch', 'assigned_depot', 'customer', 'receiver', 'originCity', 'destinationCity'] as $rel) {
            if (method_exists($shipment, $rel)) $rels[] = $rel;
        }
        if ($rels) $shipment->load($rels);

        $trackUrl = route('tracking.show', $shipment->code);
        $qrSvg    = QrCode::format('svg')->size(128)->margin(0)->generate($trackUrl);

        $barcode = new DNS1D();
        $barcodeBase64 = $barcode->getBarcodePNG($shipment->code, 'C128', 1.9, 44);

        $branchModel = $shipment->branch ?? $shipment->originBranch ?? null;
        $branchInfo = null;
        if ($branchModel) {
            $branchInfo = [
                'name'    => $branchModel->name ?? null,
                'address' => $branchModel->address ?? null,
                'phone'   => $branchModel->phone ?? config('company.phone'),
                'city'    => $branchModel->city->name ?? ($branchModel->city_name ?? ''),
            ];
        }

        $depotInfo = null;
        $modeValue = is_string($shipment->mode) ? $shipment->mode : ($shipment->mode?->value ?? null);
        if ($modeValue === 'sea' && isset($shipment->assigned_depot)) {
            $depotInfo = [
                'name'    => $shipment->assigned_depot->name ?? null,
                'address' => $shipment->assigned_depot->address ?? null,
                'phone'   => $shipment->assigned_depot->phone ?? null,
            ];
        }

        $logoPath = public_path('images/logo.png');
        if (!file_exists($logoPath)) $logoPath = public_path('favicon.ico');

        $logoDataUri = null;
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $logoMime = match ($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'ico' => 'image/x-icon',
                'svg' => 'image/svg+xml',
                default => mime_content_type($logoPath),
            };
            $logoDataUri = "data:{$logoMime};base64,{$logoData}";
        }

        $brand = [
            'name'  => config('app.name', 'PT Jaya Sakti Sejati'),
            'abbr'  => 'JSS',
            'color' => '#0137A1',
            'logo'  => $logoDataUri,
            'site'  => env('APP_BRAND_SITE', env('COMPANY_URL', 'https://jayasaktisejati.com')),
            'phone' => env('COMPANY_PHONE', config('company.phone', '+6221 693 0565')),
            'addr'  => (function () use ($branchInfo) {
                if (empty($branchInfo)) {
                    return 'KOMPLEK LODAN CENTER, Blok T1 & T2
                JI. Lodan Raya No.0.2 Jakarta Utara - 14430';
                }
                $parts = [];
                if (!empty($branchInfo['name']))    $parts[] = trim($branchInfo['name']);
                if (!empty($branchInfo['address'])) $parts[] = trim($branchInfo['address']);
                if (!empty($branchInfo['city']))    $parts[] = trim($branchInfo['city']);
                return implode(', ', array_filter($parts));
            })(),
        ];
        if (strtolower($brand['name']) === 'laravel') $brand['name'] = 'PT Jaya Sakti Sejati';

        $data = [
            'shipment'       => $shipment,
            'qrSvg'          => $qrSvg,
            'barcodeDataUri' => 'data:image/png;base64,' . $barcodeBase64,
            'trackUrl'       => $trackUrl,
            'branchInfo'     => $branchInfo,
            'depotInfo'      => $depotInfo,
            'printedAt'      => now()->timezone(config('app.timezone'))->format('d M Y H:i'),
            'brand'          => $brand,
        ];

        $pdf = Pdf::loadView('shipments.resi', $data)
            ->setPaper('A5', 'portrait')
            ->setOptions([
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'dpi'                  => 110,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        $filename = 'RESI-' . strtoupper($shipment->code) . '.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
