<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiResponseTrait;
use App\Http\Resources\Api\VoyageResource;
use App\Models\Voyage;
use App\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Voyage management.
 */
class VoyageController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of voyages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Voyage::query()->with('vessel');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('voyage_number', 'like', "%{$search}%");
        }

        if ($request->has('vessel_id')) {
            $query->where('vessel_id', $request->get('vessel_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('etd', '>=', $request->get('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('etd', '<=', $request->get('date_to'));
        }

        $perPage = $request->get('per_page', 15);
        $voyages = $query->orderBy('etd', 'desc')->paginate($perPage);

        return $this->paginatedResponse(
            $voyages->setCollection(VoyageResource::collection($voyages->getCollection())->collection),
            'Voyages retrieved successfully'
        );
    }

    /**
     * Display the specified voyage.
     */
    public function show(int $id): JsonResponse
    {
        $voyage = Voyage::with(['vessel', 'shipments'])->find($id);

        if (!$voyage) {
            throw new NotFoundException('Voyage', (string) $id);
        }

        return $this->successResponse(
            new VoyageResource($voyage),
            'Voyage retrieved successfully'
        );
    }
}
