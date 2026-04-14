<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiResponseTrait;
use App\Http\Resources\Api\CustomerResource;
use App\Models\Customer;
use App\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for Customer management.
 */
class CustomerController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Apply search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply branch filter for non-super-admins
        if (!$request->user()->hasRole('super_admin')) {
            $query->where('branch_id', $request->user()->branch_id);
        }

        $perPage = $request->get('per_page', 15);
        $customers = $query->orderBy('name')->paginate($perPage);

        return $this->paginatedResponse(
            $customers->setCollection(CustomerResource::collection($customers->getCollection())->collection),
            'Customers retrieved successfully'
        );
    }

    /**
     * Display the specified customer.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::with(['shipments' => function ($q) {
            $q->latest()->limit(10);
        }])->find($id);

        if (!$customer) {
            throw new NotFoundException('Customer', (string) $id);
        }

        return $this->successResponse(
            new CustomerResource($customer),
            'Customer retrieved successfully'
        );
    }
}
