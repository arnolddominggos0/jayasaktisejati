<?php

namespace App\Http\Controllers\Api;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiResponseTrait;
use App\Http\Requests\Api\ShipmentIndexRequest;
use App\Http\Requests\Api\ShipmentStoreRequest;
use App\Http\Requests\Api\ShipmentUpdateRequest;
use App\Http\Resources\Api\ShipmentResource;
use App\Models\Shipment;
use App\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * API Controller for Shipment management.
 */
class ShipmentController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of shipments.
     */
    public function index(ShipmentIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $query = Shipment::query()
            ->with(['customer', 'receiver', 'voyageRecord.vessel', 'branch', 'tracks']);

        // Apply branch scoping for non-super-admins
        if (!$request->user()->hasRole('super_admin')) {
            $query->where(fn ($w) => $w->where('branch_id', $request->user()->effectiveBranchId())->orWhereNull('branch_id'));
        }

        // Apply filters
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['service_type'])) {
            $query->where('service_type', $validated['service_type']);
        }

        if (!empty($validated['mode'])) {
            $query->where('mode', $validated['mode']);
        }

        if (!empty($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }

        if (!empty($validated['voyage_id'])) {
            $query->where('voyage_id', $validated['voyage_id']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $validated['per_page'] ?? 15;
        $shipments = $query->paginate($perPage);

        return $this->paginatedResponse(
            $shipments->setCollection(ShipmentResource::collection($shipments->getCollection())->collection),
            'Shipments retrieved successfully'
        );
    }

    /**
     * Store a newly created shipment.
     */
    public function store(ShipmentStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $shipment = DB::transaction(function () use ($validated, $request) {
                if (!$request->user()->hasRole('super_admin')) {
                    $validated['branch_id'] = $request->user()->effectiveBranchId();
                }
                $validated['status'] = ShipmentStatus::Draft->value;
                return Shipment::create($validated);
            });

            return $this->createdResponse(
                new ShipmentResource($shipment->load(['customer', 'receiver', 'voyageRecord.vessel', 'branch'])),
                'Shipment created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create shipment: ' . $e->getMessage(),
                'CREATION_FAILED',
                500
            );
        }
    }

    /**
     * Display the specified shipment.
     */
    public function show(int $id): JsonResponse
    {
        $shipment = Shipment::with([
            'customer', 
            'receiver', 
            'voyageRecord.vessel', 
            'branch', 
            'tracks'
        ])->find($id);

        if (!$shipment) {
            throw new NotFoundException('Shipment', (string) $id);
        }

        return $this->successResponse(
            new ShipmentResource($shipment),
            'Shipment retrieved successfully'
        );
    }

    /**
     * Update the specified shipment.
     */
    public function update(ShipmentUpdateRequest $request, int $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            throw new NotFoundException('Shipment', (string) $id);
        }

        try {
            $shipment->update($request->validated());

            return $this->successResponse(
                new ShipmentResource($shipment->fresh()->load(['customer', 'receiver', 'voyageRecord.vessel', 'branch'])),
                'Shipment updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update shipment: ' . $e->getMessage(),
                'UPDATE_FAILED',
                500
            );
        }
    }

    /**
     * Remove the specified shipment.
     */
    public function destroy(int $id): JsonResponse
    {
        $shipment = Shipment::find($id);

        if (!$shipment) {
            throw new NotFoundException('Shipment', (string) $id);
        }

        if ($shipment->status?->value !== ShipmentStatus::Draft->value) {
            return $this->errorResponse(
                'Only draft shipments can be deleted',
                'CANNOT_DELETE',
                422
            );
        }

        try {
            $shipment->delete();
            return $this->noContentResponse();
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete shipment: ' . $e->getMessage(),
                'DELETION_FAILED',
                500
            );
        }
    }

    /**
     * Get shipment tracking history.
     */
    public function tracking(int $id): JsonResponse
    {
        $shipment = Shipment::with(['tracks' => function ($query) {
            $query->orderBy('occurred_at', 'desc');
        }])->find($id);

        if (!$shipment) {
            throw new NotFoundException('Shipment', (string) $id);
        }

        return $this->successResponse(
            [
                'shipment_code' => $shipment->code,
                'current_status' => $shipment->status,
                'tracking_history' => $shipment->tracks->map(fn ($track) => [
                    'status' => $track->status,
                    'location' => $track->location,
                    'notes' => $track->notes,
                    'occurred_at' => $track->occurred_at?->toISOString(),
                ]),
            ],
            'Tracking history retrieved successfully'
        );
    }
}
