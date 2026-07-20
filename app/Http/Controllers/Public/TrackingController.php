<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

/**
 * Tracking Controller
 * 
 * Handles public shipment tracking functionality
 */
class TrackingController extends Controller
{
    /**
     * Display tracking search page
     */
    public function index()
    {
        return view('public.tracking.index');
    }

    /**
     * Search shipment by tracking number
     */
    public function search(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string|min:3|max:50'
        ], [
            'tracking_number.required' => 'Nomor resi harus diisi',
            'tracking_number.min' => 'Nomor resi minimal 3 karakter',
        ]);

        $trackingNumber = $request->input('tracking_number');

        // Search shipment with relations
        $shipment = Shipment::with(['customer', 'receiver', 'tracks', 'voyageRecord.vessel'])
            ->where('code', $trackingNumber)
            ->first();

        if (!$shipment) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nomor resi tidak ditemukan. Silakan periksa kembali nomor resi Anda.'
                ], 404);
            }

            return redirect()->route('tracking')
                ->with('error', 'Nomor resi tidak ditemukan. Silakan periksa kembali nomor resi Anda.');
        }

        $data = [
            'success' => true,
            'shipment' => [
                'code' => $shipment->code,
                'status' => $shipment->status?->value ?? $shipment->status,
                'service_type' => $shipment->service_type?->value ?? $shipment->service_type,
                'mode' => $shipment->mode?->value ?? $shipment->mode,
                'sender' => $shipment->customer?->name ?? 'N/A',
                'receiver' => $shipment->receiver?->name ?? 'N/A',
                'destination' => $shipment->destination_city ?? 'N/A',
                'origin' => $shipment->origin_city ?? 'N/A',
                'eta' => $shipment->eta?->format('d M Y') ?? null,
                'pickup_date' => $shipment->pickup_date?->format('d M Y') ?? null,
                'vessel' => $shipment->voyageRecord?->vessel?->name ?? null,
                'voyage_number' => $shipment->voyageRecord?->voyage_no ?? null,
                'total_colli' => $shipment->total_colli,
                'weight_total' => $shipment->weight_total,
                'tracks' => $shipment->tracks->sortByDesc('occurred_at')->map(function ($track) {
                    return [
                        'status' => $track->status,
                        'location' => $track->location,
                        'occurred_at' => $track->occurred_at?->format('d M Y H:i'),
                        'notes' => $track->notes,
                    ];
                })->values(),
            ]
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($data);
        }

        return view('public.tracking.index', ['result' => $data['shipment']]);
    }
}
