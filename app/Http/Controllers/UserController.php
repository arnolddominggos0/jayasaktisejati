<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserIndexRequest;
use App\Http\Resources\UserApiResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(UserIndexRequest $request)
    {
        $auth = $request->user();
        if (! $auth) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        if ($auth->hasRole('customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q = User::query()
            ->with(['branch:id,code,name'])
            ->select(['id', 'name', 'email', 'branch_id', 'created_at', 'updated_at']);

        if (! $auth->hasRole('super_admin')) {
            $currentBranchId = $request->attributes->get('currentBranchId');
            if (empty($currentBranchId)) {
                return response()->json(['message' => 'Branch scope not set'], 422);
            }
            $q->where(fn ($w) => $w->where('scope_branch_id', $currentBranchId)->orWhere(fn ($w2) => $w2->whereNull('scope_branch_id')->where('branch_id', $currentBranchId)));
        } else {
            if ($request->filled('branch_id')) {
                $q->where('branch_id', $request->integer('branch_id'));
            }
        }

        $search = $request->input('search', $request->input('q'));
        if (! empty($search)) {
            $term = '%'.str_replace('%', '\\%', $search).'%';
            $q->where(function ($w) use ($term) {
                $w->where(DB::raw('LOWER(name)'), 'LIKE', strtolower($term))
                    ->orWhere(DB::raw('LOWER(email)'), 'LIKE', strtolower($term));
            });
        }

        if ($role = $request->input('role')) {
            $q->role($role);
        }

        $sortBy = $request->sortBy();
        $sortDir = $request->sortDir();
        $q->orderBy($sortBy, $sortDir)
            ->orderBy('id', 'asc');

        $perPage = $request->perPage();
        $paginator = $q->paginate($perPage)->appends($request->validated());

        return UserApiResource::collection($paginator)->additional([
            'context' => [
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'filters' => [
                    'search' => $search,
                    'role' => $request->input('role'),
                    'branch_id' => $request->input('branch_id'),
                ],
            ],
        ]);
    }
}
