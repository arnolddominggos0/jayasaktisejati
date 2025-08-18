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
        $data = $request->validate([
            '   '         => ['required','string','max:255'],
            'email'        => ['required','email','max:255','unique:users'],
            'password'     => ['required','string','min:8','confirmed'],
            'role'         => ['required', Rule::in(['super_admin','office_admin','field_coordinator','customer'])],
            'branch_code'  => ['nullable','string','max:10'],
        ]);

        $branchId = null;
        if (!empty($data['branch_code'])) {
            $branchId = Branch::firstOrCreate(
                ['code'=>strtoupper($data['branch_code'])],
                ['name'=>strtoupper($data['branch_code'])]
            )->id;
        }

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'branch_id' => $branchId,
        ]);
        $user->syncRoles([$data['role']]);

        $user->tokens()->delete();
        $token = $user->createToken('jss-token')->plainTextToken;

        return response()->json([
            'user'  => $user->only(['id','name','email','branch_id']),
            'roles' => $user->getRoleNames(),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $payload = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string','min:8'],
            'as'       => ['nullable', Rule::in(['super_admin','office_admin','field_coordinator','customer'])],
        ]);

        $user = User::where('email', $payload['email'])->first();
        if (!$user || !Hash::check($payload['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        if (!empty($payload['as']) && ! $user->hasRole($payload['as'])) {
            return response()->json(['message' => 'Role mismatch: user is not '.$payload['as']], 403);
        }

        $user->tokens()->delete();
        $token = $user->createToken('jss-token')->plainTextToken;

        return response()->json([
            'user'        => $user->only(['id','name','email','branch_id']),
            'branch'      => optional($user->branch)->only(['id','code','name']),
            'roles'       => $user->getRoleNames(),
            'is_super_admin'       => $user->hasRole('super_admin'),
            'is_office_admin'      => $user->hasRole('office_admin'),
            'is_field_coordinator' => $user->hasRole('field_coordinator'),
            'is_customer'          => $user->hasRole('customer'),
            'token'       => $token,
        ], 200);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'user'   => $u->only(['id','name','email','branch_id']),
            'branch' => optional($u->branch)->only(['id','code','name']),
            'roles'  => $u->getRoleNames(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return response()->json(['message' => 'logged out']);
    }
}
