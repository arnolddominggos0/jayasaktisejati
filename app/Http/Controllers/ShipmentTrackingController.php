<?php

namespace App\Http\Controllers;

use App\Models\Shipment;

class ShipmentTrackingController extends Controller
{
    public function show(string $code)
    {
        $shipment = Shipment::with(['originCity', 'destinationCity', 'assignedDepot'])->where('code', $code)->firstOrFail();
        return view('tracking.show', compact('shipment'));
    }
}
