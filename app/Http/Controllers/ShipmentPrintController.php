<?php
// app/Http/Controllers/ShipmentPrintController.php
namespace App\Http\Controllers;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Milon\Barcode\DNS1D;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ShipmentPrintController extends Controller
{
    /**
     * Log document print action for audit trail (FR-10)
     */
    protected function logPrintAction(Shipment $shipment, string $docType): void
    {
        DB::table('document_print_logs')->insert([
            'user_id'      => Auth::id(),
            'shipment_id'  => $shipment->id,
            'doc_type'     => $docType,
            'printed_at'   => now(),
            'ip_address'   => request()->ip(),
            'user_agent'   => substr(request()->userAgent() ?? '', 0, 255),
            'created_at'   => now(),
        ]);
    }

    /**
     * Validate shipment status allows printing
     * Block Draft shipments
     */
    protected function validatePrintStatus(Shipment $shipment): void
    {
        if ($shipment->status === ShipmentStatus::Draft) {
            abort(403, 'Dokumen tidak dapat dicetak untuk shipment dengan status Draft.');
        }
    }

    /**
     * Validate sea shipment data consistency
     */
    protected function validateSeaDataConsistency(Shipment $shipment): array
    {
        $warnings = [];

        // Check vessel/voyage consistency with shipping schedule
        if ($shipment->shippingSchedule) {
            $scheduleVessel = $shipment->shippingSchedule->vessel_name;
            $scheduleVoyage = $shipment->shippingSchedule->voyage;

            if ($shipment->vessel_name && $shipment->vessel_name !== $scheduleVessel) {
                $warnings[] = "Nama vessel shipment ({$shipment->vessel_name}) berbeda dengan jadwal ({$scheduleVessel})";
            }
            if ($shipment->voyage && $shipment->voyage !== $scheduleVoyage) {
                $warnings[] = "Voyage shipment ({$shipment->voyage}) berbeda dengan jadwal ({$scheduleVoyage})";
            }
        }

        // Check critical sea fields
        if (empty($shipment->vessel_name) && empty($shipment->shippingSchedule?->vessel_name)) {
            $warnings[] = 'Nama vessel belum diisi';
        }
        if (empty($shipment->pol_id) && empty($shipment->pol)) {
            $warnings[] = 'Port of Loading (POL) belum diisi';
        }
        if (empty($shipment->pod_id) && empty($shipment->pod)) {
            $warnings[] = 'Port of Discharge (POD) belum diisi';
        }

        // Service option validation
        if ($shipment->service_option === 'fcl' && empty($shipment->container_no)) {
            $warnings[] = 'FCL shipment memerlukan nomor container';
        }

        return $warnings;
    }

    /**
     * Print Waybill for Sea Shipments (FR-06)
     * Includes vessel, voyage, container, POL/POD details
     */
    public function waybill(Request $request, Shipment $shipment)
    {
        // Policy check: Ensure user can view this shipment
        Gate::authorize('view', $shipment);

        // Validate shipment status
        $this->validatePrintStatus($shipment);

        // Only for sea shipments
        $isSea = ($shipment->mode instanceof ShipmentMode && $shipment->mode === ShipmentMode::Sea)
            || (is_string($shipment->mode) && $shipment->mode === 'sea');

        if (!$isSea) {
            abort(404, 'Waybill hanya tersedia untuk shipment moda laut.');
        }

        // Validate sea data consistency
        $warnings = $this->validateSeaDataConsistency($shipment);

        // Load necessary relations for sea shipment
        $shipment->load([
            'branch',
            'customer',
            'receiver',
            'originCity',
            'destinationCity',
            'pol',
            'pod',
            'shippingSchedule',
            'tracks' => fn($q) => $q->orderBy('tracked_at', 'desc')->limit(5),
        ]);

        // Prepare branch info
        $branchModel = $shipment->branch;
        $branchInfo = [
            'name'    => $branchModel?->name ?? 'PT Jaya Sakti Sejati',
            'address' => $branchModel?->address ?? 'Komplek Lodan Center',
            'phone'   => $branchModel?->phone ?? config('company.phone', '+6221 693 0565'),
            'city'    => $branchModel?->city?->name ?? 'Jakarta',
        ];

        // Sea-specific data
        $seaInfo = [
            'vessel_name'    => $shipment->vessel_name ?? $shipment->shippingSchedule?->vessel_name ?? '—',
            'voyage'         => $shipment->voyage ?? $shipment->shippingSchedule?->voyage ?? '—',
            'container_no'   => $shipment->container_no ?? '—',
            'seal_no'        => $shipment->seal_no ?? '—',
            'pol'            => $shipment->pol?->name ?? $shipment->pol ?? '—',
            'pod'            => $shipment->pod?->name ?? $shipment->pod ?? '—',
            'etd'            => $shipment->etd?->format('d M Y H:i') ?? '—',
            'eta'            => $shipment->eta?->format('d M Y H:i') ?? '—',
            'service_option' => $shipment->service_option ?? 'FCL',
            'container_size' => $shipment->container_size ?? '—',
            'container_qty'  => $shipment->container_qty ?? 1,
        ];

        // Generate QR and Barcode
        $trackUrl = route('tracking.show', $shipment->code);
        $qrSvg = QrCode::format('svg')->size(100)->margin(1)->generate($trackUrl);

        $barcode = new DNS1D();
        $barcodeBase64 = $barcode->getBarcodePNG($shipment->code, 'C128', 2, 40);

        // Brand info
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
            'site'  => env('APP_BRAND_SITE', 'https://jayasaktisejati.com'),
            'phone' => env('COMPANY_PHONE', config('company.phone', '+6221 693 0565')),
        ];

        $data = [
            'shipment'       => $shipment,
            'branchInfo'     => $branchInfo,
            'seaInfo'        => $seaInfo,
            'qrSvg'          => $qrSvg,
            'barcodeDataUri' => 'data:image/png;base64,' . $barcodeBase64,
            'trackUrl'       => $trackUrl,
            'printedAt'      => now()->timezone(config('app.timezone'))->format('d M Y H:i'),
            'brand'          => $brand,
        ];

        $pdf = Pdf::loadView('shipments.waybill-sea', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'dpi'                  => 110,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        // Log audit trail
        $this->logPrintAction($shipment, 'waybill');

        $filename = 'WAYBILL-' . strtoupper($shipment->code) . '.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    /**
     * Print Packing List for Sea Shipments (FR-06)
     * Includes cargo details, vessel info, and container details
     */
    public function packingList(Request $request, Shipment $shipment)
    {
        // Policy check: Ensure user can view this shipment
        Gate::authorize('view', $shipment);

        // Validate shipment status
        $this->validatePrintStatus($shipment);

        // Only for sea shipments
        $isSea = ($shipment->mode instanceof ShipmentMode && $shipment->mode === ShipmentMode::Sea)
            || (is_string($shipment->mode) && $shipment->mode === 'sea');

        if (!$isSea) {
            abort(404, 'Packing List hanya tersedia untuk shipment moda laut.');
        }

        // Validate sea data consistency
        $warnings = $this->validateSeaDataConsistency($shipment);

        // Load necessary relations for sea shipment
        $shipment->load([
            'branch',
            'customer',
            'receiver',
            'originCity',
            'destinationCity',
            'pol',
            'pod',
            'shippingSchedule',
            'units',
        ]);

        // Prepare branch info
        $branchModel = $shipment->branch;
        $branchInfo = [
            'name'    => $branchModel?->name ?? 'PT Jaya Sakti Sejati',
            'address' => $branchModel?->address ?? 'Komplek Lodan Center',
            'phone'   => $branchModel?->phone ?? config('company.phone', '+6221 693 0565'),
            'city'    => $branchModel?->city?->name ?? 'Jakarta',
        ];

        // Sea-specific data
        $seaInfo = [
            'vessel_name'    => $shipment->vessel_name ?? $shipment->shippingSchedule?->vessel_name ?? '—',
            'voyage'         => $shipment->voyage ?? $shipment->shippingSchedule?->voyage ?? '—',
            'container_no'   => $shipment->container_no ?? '—',
            'seal_no'        => $shipment->seal_no ?? '—',
            'pol'            => $shipment->pol?->name ?? $shipment->pol ?? '—',
            'pod'            => $shipment->pod?->name ?? $shipment->pod ?? '—',
            'etd'            => $shipment->etd?->format('d M Y H:i') ?? '—',
            'eta'            => $shipment->eta?->format('d M Y H:i') ?? '—',
            'service_option' => $shipment->service_option ?? 'FCL',
            'container_size' => $shipment->container_size ?? '—',
            'container_qty'  => $shipment->container_qty ?? 1,
        ];

        // Generate QR and Barcode
        $trackUrl = route('tracking.show', $shipment->code);
        $qrSvg = QrCode::format('svg')->size(80)->margin(1)->generate($trackUrl);

        $barcode = new DNS1D();
        $barcodeBase64 = $barcode->getBarcodePNG($shipment->code, 'C128', 2, 36);

        // Brand info
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
            'site'  => env('APP_BRAND_SITE', 'https://jayasaktisejati.com'),
            'phone' => env('COMPANY_PHONE', config('company.phone', '+6221 693 0565')),
        ];

        $data = [
            'shipment'       => $shipment,
            'branchInfo'     => $branchInfo,
            'seaInfo'        => $seaInfo,
            'qrSvg'          => $qrSvg,
            'barcodeDataUri' => 'data:image/png;base64,' . $barcodeBase64,
            'trackUrl'       => $trackUrl,
            'printedAt'      => now()->timezone(config('app.timezone'))->format('d M Y H:i'),
            'brand'          => $brand,
        ];

        $pdf = Pdf::loadView('shipments.packing-list-sea', $data)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isRemoteEnabled'      => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled'         => false,
                'dpi'                  => 110,
                'defaultFont'          => 'DejaVu Sans',
            ]);

        // Log audit trail
        $this->logPrintAction($shipment, 'packing_list');

        $filename = 'PACKING-LIST-' . strtoupper($shipment->code) . '.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }

    public function resi(Request $request, Shipment $shipment)
    {
        // Policy check: Ensure user can view this shipment
        Gate::authorize('view', $shipment);

        // Validate shipment status
        $this->validatePrintStatus($shipment);

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

        // Log audit trail
        $this->logPrintAction($shipment, 'resi');

        $filename = 'RESI-' . strtoupper($shipment->code) . '.pdf';
        return $request->boolean('download') ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
