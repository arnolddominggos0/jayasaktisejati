<?php

namespace App\Supports;

/**
 * Single source of truth for all route format conversions.
 *
 * Each registry entry is a 7-tuple:
 *   [business_code, voyage_code, display_label, pol_part, pod_part, pol_unlocode, pod_unlocode]
 *
 * To add a new route: append one array entry. Nothing else changes.
 *
 * Scope: pure value object — no DB access, no model imports.
 * DO NOT use for: port FK lookups, SLA queries, depot resolution.
 */
final class RouteCode
{
    // ── Registry ─────────────────────────────────────────────────────────────
    // idx: 0=business_code 1=voyage_code 2=display 3=pol_part 4=pod_part 5=pol_unlocode 6=pod_unlocode
    private const REGISTRY = [
        ['JKT-MND', 'JKTMND', 'Jakarta → Manado', 'JKT', 'MND', 'IDJKT', 'IDBTG'],
        ['JKT-BTG', 'JKTBTG', 'Jakarta → Bitung', 'JKT', 'BTG', 'IDJKT', 'IDBTG'],
        // Future routes — append here:
        // ['JKT-MKS', 'JKTMKS', 'Jakarta → Makassar', 'JKT', 'MKS', 'IDJKT', 'IDMKS'],
        // ['JKT-SBY', 'JKTSBY', 'Jakarta → Surabaya', 'JKT', 'SBY', 'IDJKT', 'IDSUB'],
    ];

    // ── Derived lookup maps (built once via boot) ─────────────────────────────
    /** @var array<string,array> keyed by UPPERCASE business_code */
    private static array $byBusiness = [];

    /** @var array<string,array> keyed by UPPERCASE voyage_code */
    private static array $byVoyage = [];

    /** @var array<string,string> keyed by 'POL_UNLOCODE|POD_UNLOCODE' → voyage_code */
    private static array $byUNLOCODE = [];

    private static bool $booted = false;

    private static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        foreach (self::REGISTRY as [$business, $voyage, $display, $pol, $pod, $polU, $podU]) {
            $entry = compact('business', 'voyage', 'display', 'pol', 'pod', 'polU', 'podU');
            self::$byBusiness[strtoupper($business)] = $entry;
            self::$byVoyage[strtoupper($voyage)]     = $entry;
            // Only register the first match for a given UNLOCODE pair (primary route)
            $key = strtoupper($polU) . '|' . strtoupper($podU);
            self::$byUNLOCODE[$key] ??= $voyage;
        }

        self::$booted = true;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Default business route code for new vessel plans.
     */
    public static function default(): string
    {
        return 'JKT-MND';
    }

    /**
     * Convert vessel_plans.route_code → voyages.route_code (no separator).
     *
     * RouteCode::voyage('JKT-MND') => 'JKTMND'
     * Returns null for unknown codes.
     */
    public static function voyage(string $businessCode): ?string
    {
        self::boot();
        return self::$byBusiness[strtoupper(trim($businessCode))]['voyage'] ?? null;
    }

    /**
     * Convert vessel_plans.route_code → human-readable display label.
     *
     * RouteCode::display('JKT-MND') => 'Jakarta → Manado'
     * Returns null for unknown codes.
     */
    public static function display(string $businessCode): ?string
    {
        self::boot();
        return self::$byBusiness[strtoupper(trim($businessCode))]['display'] ?? null;
    }

    /**
     * Convert voyages.route_code → human-readable display label.
     *
     * RouteCode::displayFromVoyage('JKTMND') => 'Jakarta → Manado'
     * Returns null for unknown codes.
     */
    public static function displayFromVoyage(string $voyageCode): ?string
    {
        self::boot();
        return self::$byVoyage[strtoupper(trim($voyageCode))]['display'] ?? null;
    }

    /**
     * Split vessel_plans.route_code into [pol_part, pod_part] for port lookup.
     *
     * RouteCode::parts('JKT-MND') => ['JKT', 'MND']
     *
     * Falls back to splitting on '-' for unknown codes, preserving the
     * previous resolveRoutePortIds() behaviour for unregistered routes.
     */
    public static function parts(string $businessCode): array
    {
        self::boot();
        $entry = self::$byBusiness[strtoupper(trim($businessCode))] ?? null;

        if ($entry) {
            return [$entry['pol'], $entry['pod']];
        }

        // Graceful fallback for unregistered codes (identical to old explode logic)
        return array_pad(explode('-', $businessCode, 2), 2, null);
    }

    /**
     * Resolve voyages.route_code from a known POL/POD UNLOCODE pair.
     * Used by BackfillVoyageCode to map port identity → voyage route code
     * without hardcoding the mapping outside this registry.
     *
     * RouteCode::voyageFromPortCodes('IDJKT', 'IDBTG') => 'JKTMND'
     * Returns null when no registry entry matches.
     */
    public static function voyageFromPortCodes(string $polCode, string $podCode): ?string
    {
        self::boot();
        $key = strtoupper(trim($polCode)) . '|' . strtoupper(trim($podCode));
        return self::$byUNLOCODE[$key] ?? null;
    }

    /**
     * Return the raw registry for iteration (e.g. seeding, testing).
     * Each element is a 7-element indexed array matching the REGISTRY format.
     *
     * @return array<int,array>
     */
    public static function all(): array
    {
        return self::REGISTRY;
    }
}
