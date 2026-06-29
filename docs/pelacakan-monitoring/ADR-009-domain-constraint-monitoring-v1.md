# ADR-009 — Domain Constraint: Pelacakan & Monitoring v1

**Status:** ACCEPTED  
**Date:** 2026-06-27  
**Sprint:** Post-6.2C (Performance Audit)  
**Supersedes:** —  
**Related ADRs:** ADR-001 through ADR-008 (Sprint 5 Technical Architecture)

---

## Context

The Pelacakan & Monitoring workspace was built from Sprint 5 onward with query branches that conditionally support both `mode='sea'` and `mode='land'`, and with services that accept a `$mode` parameter even when that parameter is never used.

During Sprint 6.2 implementation and the subsequent performance audit, three facts became clear:

1. **All current monitoring operations are TAM sea freight.** Every active unit in the system that the monitoring workspace is meant to track is a sea-mode shipment from a TAM customer on the Manado route. No land shipments have ever been viewed through this workspace.

2. **The land-mode branches carry zero coverage.** `TrackStatus::orderForMode($mode)` returned the land order only under a code path that no real request has ever reached. `AgeCalculator::calculate($lastTrackedAt, $requestedAt, $mode)` accepted `$mode` but never read it. These are dead parameters.

3. **Sea-specific exceptions are semantically wrong for land mode.** Demurrage (cargo at port for >N days) and Missing Voyage (sea shipment without a voyage ID) are sea-mode concepts. Applying them to land shipments would produce incorrect exception counts and misleading UI.

---

## Decision

**Pelacakan & Monitoring v1 is hard-pinned to the TAM operational domain:**

| Dimension | v1 Constraint |
|---|---|
| Shipment mode | `sea` only |
| Customer scope | TAM (via `route='tam'` default, enforced by `RouteResolver`) |
| Route default | `tam` (configurable via `monitoring.default_route`) |
| Exception semantics | Sea-freight: Demurrage, Missing Voyage, Delay (ETA), Stuck, NG, Hold |

This constraint is expressed as a **single class** — `App\Support\Monitoring\MonitoringDomain` — and applied at every query-builder boundary. No mode branching exists downstream.

---

## Implementation

### New class: `MonitoringDomain`

```
app/Support/Monitoring/MonitoringDomain.php
```

Single application point. All three query builders (`UnitMonitoringQueryBuilder`, `ExceptionCountQueryBuilder`, `WorkspaceSummaryQueryBuilder`) call `MonitoringDomain::applyTo($query)` instead of branching on `$filter->mode`.

### Removed branches

| File | Before | After |
|---|---|---|
| `UnitMonitoringQueryBuilder` | `if ($f->mode) { where(mode, $f->mode) }` | `MonitoringDomain::applyTo($q)` |
| `ExceptionCountQueryBuilder` | `if ($filter->mode) { where(mode, $filter->mode) }` | `MonitoringDomain::applyTo($query)` |
| `WorkspaceSummaryQueryBuilder` | `if ($filter->mode) { where(mode, $filter->mode) }` | `MonitoringDomain::applyTo($query)` |
| `StageResolver` | `TrackStatus::orderForMode($mode)` | `TrackStatus::orderSea()` |
| `AgeCalculator` | `calculate(?Carbon, ?Carbon, string $mode = 'sea')` | `calculate(?Carbon, ?Carbon)` |
| `MonitoringFilter::cacheKey()` | includes `$this->mode` | `mode` excluded (always sea) |
| `PelacakanMonitoring` form | Mode select with Land option | Mode select removed entirely |

### Retained extension points

The following artifacts are **kept** to make v2 extension additive rather than reconstructive:

| Artifact | Why kept |
|---|---|
| `MonitoringFilter::$mode` property | DTO field — v2 re-enables by restoring query-builder logic |
| `MonitoringDomain::SHIPMENT_MODE` constant | Single place to change when multi-mode is added |
| `applyModeFilter(Builder, MonitoringFilter)` method signature in query builders | Parameter list unchanged — body can be restored |
| `TrackStatus::orderForMode()` on the enum | Not touched — used elsewhere in the app |

---

## Consequences

### Positive

- The partial index `shipments_sea_missing_voyage_idx` (`WHERE mode='sea' AND voyage_id IS NULL`) is now hit on every monitoring query, since `mode='sea'` is always in the WHERE clause.
- Query builders are unconditional — no runtime branching on user-supplied mode values.
- `StageResolver` is simpler and reads correctly: "TAM follows the sea-freight stage sequence."
- `AgeCalculator` has a clean signature — no dead parameter.
- The exception band and per-row exceptions correctly assume sea-mode semantics without defensive conditionals.

### Neutral

- `MonitoringFilter::$mode` is a vestigial field in v1. It is accepted by the form and stored in state, but ignored by all query builders. This is intentional — removing it from the DTO would be a breaking change with no runtime benefit.

### Risk

- If a super-admin sets `route='all'`, land-mode shipments will be excluded by `MonitoringDomain::applyTo()` and silently not appear. This is the correct behavior for v1: the workspace is sea-only. The UI no longer offers a mode selector, so there is no user-visible inconsistency.

---

## Extension Protocol (v2 — Adding Land Mode)

When land-mode monitoring is required:

1. **`MonitoringDomain`** — extend `applyTo()` to accept a `?string $mode` parameter, or replace `SHIPMENT_MODE` constant with a config-driven whitelist.

2. **Query builders** — restore `applyModeFilter(Builder, MonitoringFilter)` to read `$f->mode` and pass it through `MonitoringDomain`.

3. **`StageResolver`** — restore `TrackStatus::orderForMode($mode)` branching. Consider extracting land-specific flow zones.

4. **`ExceptionEvaluator`** — gate Demurrage and Missing Voyage behind `$shipment->mode === ShipmentMode::Sea`. Land mode needs its own exception set.

5. **`AgeCalculator`** — re-add `$mode` parameter if land-mode age semantics differ.

6. **`MonitoringFilter::cacheKey()`** — re-include `$this->mode` so cache keys vary by mode.

7. **`PelacakanMonitoring` form schema** — restore the mode select with Land option.

The extension is **additive** — all sea-mode logic is unchanged.

---

## Audit Trail

| Date | Author | Note |
|---|---|---|
| 2026-06-27 | Principal Software Architect | ADR authored and implemented post-6.2C audit |
