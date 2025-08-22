<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function show(Request $request, Branch $branch)
    {
        $u = $request->user();
        if (!$u->hasRole('super_admin')) {
            $currentBranchId = (int) $request->attributes->get('currentBranchId');
            abort_if(
                (int) $branch->id !== $currentBranchId,
                403,
                'Forbidden: branch mismatch (you are scoped to your own branch only).'
            );
        }
        return response()->json($branch);
    }

    public function update(Request $request, Branch $branch)
    {
        $u = $request->user();
        if (!$u->hasRole('super_admin')) {
            $currentBranchId = (int) $request->attributes->get('currentBranchId');
            abort_if(
                (int) $branch->id !== $currentBranchId,
                403,
                'Forbidden: cannot update a different branch.'
            );
        }

        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:10', Rule::unique('branches', 'code')->ignore($branch->id)],
            'name' => ['sometimes', 'string', 'max:100'],
        ]);
        if (isset($data['code'])) $data['code'] = strtoupper($data['code']);

        $branch->update($data);
        return response()->json($branch);
    }

    public function destroy(Request $request, Branch $branch)
    {
        $branch->delete();
        return response()->json(['message' => 'deleted']);
    }
}
