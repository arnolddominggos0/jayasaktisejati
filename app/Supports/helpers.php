<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('auth_user')) {
    /**
     * @return \App\Models\User|null
     */
    function auth_user(): ?\App\Models\User
    {
        /** @var \App\Models\User|null $u */
        $u = Auth::user();
        return $u;
    }
}
