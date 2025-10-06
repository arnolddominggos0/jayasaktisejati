<?php

namespace App\Supports;

class Code
{
    public static function customer(): string
    {
        $cfg    = config('codes.customer', []);
        $prefix = strtoupper((string) ($cfg['prefix'] ?? 'CTM'));
        $pad    = (int) ($cfg['pad'] ?? 4);

        $n = \App\Supports\RunningNumber::next($prefix);
        return $prefix . '-' . str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}
