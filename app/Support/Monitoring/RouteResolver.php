<?php

namespace App\Support\Monitoring;

final class RouteResolver
{
    public static function default(): string
    {
        return config('monitoring.default_route', 'tam');
    }

    public static function customerIdsForRoute(?string $route): ?array
    {
        if (!$route || $route === 'all') {
            return null;
        }

        if ($route === 'tam') {
            return array_map('intval', config('jss_kpi.manado.customer_ids', []));
        }

        return null;
    }
}