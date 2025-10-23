<?php

namespace App\Support;

final class VesselCode
{
    public static function code(?string $vesselName): string
    {
        if (!$vesselName) return '—';

        $name = mb_strtoupper(trim(self::normalize($vesselName)));

        $override = config('vessels.overrides.' . $name);
        if (is_string($override) && $override !== '') {
            return mb_strtoupper($override);
        }

        $prefix = self::prefixForLine($name);

        $token = self::secondTokenAfterLine($name);

        $suffix = self::suffixFromToken($token);

        if ($prefix === '') {
            $parts = self::significantTokens($name);
            $ini1 = mb_substr($parts[0] ?? '', 0, 1);
            $ini2 = mb_substr($parts[1] ?? '', 0, 1);
            $prefix = $ini1 . $ini2;
        }

        $lineKey = $prefix; 
        $suffixOv = config('vessels.suffix_overrides.' . $lineKey . '.' . $token);
        if (is_string($suffixOv) && $suffixOv !== '') {
            $suffix = mb_strtoupper($suffixOv);
        }

        $code = $prefix . $suffix;

        if (mb_strlen($code) > 4) {
            $code = mb_substr($code, 0, 4);
        }

        return $code;
    }

    protected static function normalize(string $v): string
    {
        $v = preg_replace('/\((.*?)\)/', ' ', $v);
        $v = str_ireplace(['.', ','], ' ', $v);
        $v = preg_replace('/\s+/', ' ', $v);
        $v = trim($v);

        // buang prefiks umum "KM"
        $v = preg_replace('/^KM\s+/iu', '', $v);
        return $v;
    }

    /** Prefix pelayaran berdasar regex mapping di config. */
    protected static function prefixForLine(string $name): string
    {
        $lines = config('vessels.lines', [
            '/\bMERATUS\b/i' => 'MR',
            '/\bTANTO\b/i'   => 'TT',
            '/\bTEMAS\b/i'   => 'TM',
            '/\bSAMUDERA\b/i' => 'SM',
        ]);

        foreach ($lines as $pattern => $abbr) {
            if (@preg_match($pattern, $name)) {
                if (preg_match($pattern, $name)) {
                    return mb_strtoupper($abbr);
                }
            }
        }
        return '';
    }

    protected static function secondTokenAfterLine(string $name): string
    {
        $tokens = self::significantTokens($name);

        if (count($tokens) >= 2) {
            return $tokens[1]; 
        }

        return $tokens[0] ?? '';
    }

    protected static function significantTokens(string $name): array
    {
        $parts = preg_split('/\s+/', $name);
        $parts = array_values(array_filter(array_map(function ($p) {
            $p = preg_replace('/[^A-Z]/iu', '', $p);
            return mb_strtoupper($p);
        }, $parts), fn($p) => $p !== ''));

        return $parts;
    }

    protected static function suffixFromToken(string $token): string
    {
        if ($token === '') return '';
        $token = mb_strtoupper($token);
        $len = mb_strlen($token);
        if ($len === 1) return $token;
        return mb_substr($token, 0, 2);
    }
}
