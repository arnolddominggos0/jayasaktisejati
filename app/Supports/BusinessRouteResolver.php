<?php

namespace App\Supports;

use App\Models\VesselPlan;
use App\Models\Voyage;

/**
 * Model-aware adapter for human-readable route display.
 *
 * This class is responsible for:
 *   1. Delegating route lookup to RouteCode (single source of truth)
 *   2. Falling back to port.city / port.name when the route code is unknown
 *
 * DO NOT add route maps here — they belong in RouteCode.
 * DO NOT use this for: SLA queries, port FK resolution, code generation.
 */
class BusinessRouteResolver
{
    /**
     * Display label for a Voyage.
     * Resolution: voyage.route_code → RouteCode → port city/name fallback
     */
    public static function forVoyage(Voyage $voyage): string
    {
        if (! empty($voyage->route_code)) {
            $label = RouteCode::displayFromVoyage($voyage->route_code);
            if ($label !== null) {
                return $label;
            }
        }

        $voyage->loadMissing(['pol', 'pod']);

        $pol = $voyage->pol?->city ?? $voyage->pol?->name ?? null;
        $pod = $voyage->pod?->city ?? $voyage->pod?->name ?? null;

        if ($pol && $pod) {
            return "{$pol} → {$pod}";
        }

        return $voyage->route_code ?? '—';
    }

    /**
     * Display label for a VesselPlan.
     * Resolution: plan.route_code → RouteCode → port city/name fallback
     */
    public static function forPlan(VesselPlan $plan): string
    {
        if (! empty($plan->route_code)) {
            $label = RouteCode::display($plan->route_code);
            if ($label !== null) {
                return $label;
            }
        }

        $plan->loadMissing(['pol', 'pod']);

        $pol = $plan->pol?->city ?? $plan->pol?->name ?? null;
        $pod = $plan->pod?->city ?? $plan->pod?->name ?? null;

        if ($pol && $pod) {
            return "{$pol} → {$pod}";
        }

        return $plan->route_code ?? '—';
    }
}
