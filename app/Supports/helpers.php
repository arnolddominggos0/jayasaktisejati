<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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

    if (! function_exists('schema_has_column')) {
        function schema_has_column(string $table, string $column): bool
        {
            try {
                return Schema::hasColumn($table, $column);
            } catch (\Throwable $e) {
                return false;
            }
        }
    }
}
