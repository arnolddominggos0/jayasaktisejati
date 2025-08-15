<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email'=>['required','email'],'password'=>['required']]);

        if (! Auth::attempt($request->only('email','password'), true)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $request->session()->regenerate();
        return response()->json(['message' => 'Logged in']);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return $request->user();
    }
}
