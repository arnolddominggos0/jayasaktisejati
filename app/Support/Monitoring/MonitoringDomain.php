<?php

namespace App\Support\Monitoring;

use Illuminate\Database\Eloquent\Builder;

/**
 * Encodes the v1 domain constraint for Pelacakan & Monitoring.
 *
 * ADR-009 decision: v1 covers sea-mode TAM shipments only.
 * All query builders call applyTo() at the boundary; no individual
 * mode branching is needed downstream.
 *
 * Extension protocol (v2 — when land mode is added):
 *   1. Change SHIPMENT_MODE or accept mode as a parameter here
 *   2. Restore mode branching in StageResolver (orderLand vs orderSea)
 *   3. Add land-specific exception types in ExceptionEvaluator
 *   4. Re-expose the mode select in PelacakanMonitoring form schema
 *   5. Re-include mode in MonitoringFilter::cacheKey()
 */
final class MonitoringDomain
{
    /** v1 hard-pin: sea freight only. See ADR-009. */
    public const SHIPMENT_MODE = 'sea';

    /**
     * Apply the v1 domain scope to a shipments query.
     * Must be the first scope applied — exception filters and sort
     * expressions assume sea-mode semantics.
     *
     * The $filter parameter is intentionally not accepted here so this
     * method cannot be bypassed by passing a different mode value.
     */
    public static function applyTo(Builder $q): void
    {
        $q->where('shipments.mode', self::SHIPMENT_MODE);
    }
}
