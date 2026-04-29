<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\{User, Branch};

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $request->merge([
            'branch_code' => $request->filled('branch_code')
                ? strtoupper(trim((string) $request->branch_code))
                : null,
        ]);


        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(['super_admin', 'office_admin', 'field_coordinator', 'customer'])],
            'branch_code' => [
                'nullable',
                'string',
                'max:10',
                'required_if:role,super_admin,office_admin,field_coordinator',
                'prohibited_if:role,customer',
                'exists:branches,code',
            ],
        ]);

        $isInternal = in_array($data['role'], ['super_admin', 'office_admin', 'field_coordinator'], true);
        $branchId = $isInternal
            ? Branch::where('code', $data['branch_code'])->value('id')
            : null;


        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'branch_id' => $branchId,
        ]);

        $user->syncRoles([$data['role']]);

        $user->tokens()->delete();
        $token = $user->createToken('jss-token')->plainTextToken;


        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
            ],
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'code' => $user->branch->code,
                'name' => $user->branch->name,
            ] : null,
            'roles' => $user->getRoleNames(),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $payload = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'as' => ['nullable', Rule::in(['super_admin', 'office_admin', 'field_coordinator', 'customer'])],
        ]);


        $user = User::where('email', $payload['email'])->first();
        if (!$user || !Hash::check($payload['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }


        if (!empty($payload['as']) && !$user->hasRole($payload['as'])) {
            return response()->json(['message' => 'Role mismatch: user is not ' . $payload['as']], 403);
        }


        $user->tokens()->delete();
        $token = $user->createToken('jss-token')->plainTextToken;


        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
            ],
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'code' => $user->branch->code,
                'name' => $user->branch->name,
            ] : null,
            'roles' => $user->getRoleNames(),
            'is_super_admin' => $user->hasRole('super_admin'),
            'is_office_admin' => $user->hasRole('office_admin'),
            'is_field_coordinator' => $user->hasRole('field_coordinator'),
            'is_customer' => $user->hasRole('customer'),
            'token' => $token,
        ], 200);
    }

    public function me(Request $request)
    {
        $u = $request->user()->loadMissing('branch');


        return response()->json([
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'branch_id' => $u->branch_id,
            ],
            'branch' => $u->branch ? [
                'id' => $u->branch->id,
                'code' => $u->branch->code,
                'name' => $u->branch->name,
            ] : null,
            'roles' => $u->getRoleNames(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'logged out']);
    }
}
