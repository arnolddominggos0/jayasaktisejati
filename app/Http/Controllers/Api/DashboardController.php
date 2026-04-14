<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiResponseTrait;
use App\Models\Shipment;
use App\Models\Voyage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * API Controller for Dashboard statistics and KPIs.
 */
class DashboardController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get dashboard statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $cacheKey = 'dashboard_stats_' . ($request->user()->branch_id ?? 'all');
        
        $stats = Cache::remember($cacheKey, 300, function () use ($request) {
            $shipmentQuery = Shipment::query();
            
            if (!$request->user()->hasRole('super_admin')) {
                $shipmentQuery->where('branch_id', $request->user()->branch_id);
            }

            return [
                'shipments' => [
                    'total' => (clone $shipmentQuery)->count(),
                    'draft' => (clone $shipmentQuery)->where('status', 'Draft')->count(),
                    'active' => (clone $shipmentQuery)->whereNotIn('status', ['Draft', 'Delivered', 'Cancelled'])->count(),
                    'delivered' => (clone $shipmentQuery)->where('status', 'Delivered')->count(),
                    'cancelled' => (clone $shipmentQuery)->where('status', 'Cancelled')->count(),
                ],
                'voyages' => [
                    'upcoming' => Voyage::where('etd', '>=', now())->where('etd', '<=', now()->addDays(7))->count(),
                    'in_transit' => Voyage::where('etd', '<=', now())->where('eta', '>=', now())->count(),
                ],
            ];
        });

        return $this->successResponse($stats, 'Dashboard statistics retrieved successfully');
    }

    /**
     * Get shipment statistics by status.
     */
    public function shipmentStats(Request $request): JsonResponse
    {
        $query = Shipment::query();
        
        if (!$request->user()->hasRole('super_admin')) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $stats = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return $this->successResponse($stats, 'Shipment statistics retrieved successfully');
    }

    /**
     * Get recent shipments.
     */
    public function recentShipments(Request $request): JsonResponse
    {
        $query = Shipment::query()->with(['customer', 'receiver']);
        
        if (!$request->user()->hasRole('super_admin')) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $shipments = $query->latest()->limit(10)->get();

        return $this->successResponse(
            $shipments->map(fn ($s) => [
                'id' => $s->id,
                'code' => $s->code,
                'customer' => $s->customer?->name,
                'status' => $s->status,
                'created_at' => $s->created_at->toISOString(),
            ]),
            'Recent shipments retrieved successfully'
        );
    }

    /**
     * Get upcoming voyages.
     */
    public function upcomingVoyages(Request $request): JsonResponse
    {
        $voyages = Voyage::with('vessel')
            ->where('etd', '>=', now())
            ->where('etd', '<=', now()->addDays(14))
            ->orderBy('etd')
            ->limit(10)
            ->get();

        return $this->successResponse(
            $voyages->map(fn ($v) => [
                'id' => $v->id,
                'voyage_number' => $v->voyage_number,
                'vessel' => $v->vessel?->name,
                'pol' => $v->pol_code,
                'pod' => $v->pod_code,
                'etd' => $v->etd?->toISOString(),
                'eta' => $v->eta?->toISOString(),
            ]),
            'Upcoming voyages retrieved successfully'
        );
    }
}
