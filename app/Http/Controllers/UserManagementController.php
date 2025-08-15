<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function offices()
    {
        return Office::orderBy('name')->get(['id','name','code']);
    }

    public function createAdminOffice(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required','max:255'],
            'email'     => ['required','email','unique:users,email'],
            'password'  => ['required','min:8'],
            'office_id' => ['required','exists:offices,id'],
        ]);

        $office = Office::findOrFail($data['office_id']);
        $username = Str::slug($data['name']).'-'.$office->code;

        $user = User::create($data + ['username' => $username]);
        $user->assignRole('admin-office');

        return response()->json($user->load('office'), 201);
    }

    public function createKoordinator(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required','max:255'],
            'email'     => ['required','email','unique:users,email'],
            'password'  => ['required','min:8'],
            'office_id' => ['required','exists:offices,id'],
        ]);

        $office = Office::findOrFail($data['office_id']);
        $username = Str::slug($data['name']).'-'.$office->code;

        $user = User::create($data + ['username' => $username]);
        $user->assignRole('koordinator-lapangan');

        return response()->json($user->load('office'), 201);
    }
}
