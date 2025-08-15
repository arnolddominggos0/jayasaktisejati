<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RegisterCustomerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email'=> ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed','min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email'=> $data['email'],
            'password' => $data['password'],
        ]);

        $user->assignRole('customer');

        return response()->json(['message' => 'Customer registered'], 201);
    }
}
