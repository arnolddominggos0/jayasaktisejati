# Sprint 5 — Technical Architecture (Architecture Handbook)

## Module: Pelacakan & Monitoring — Operational Control Tower

**Status:** FINAL
**Date:** 27 June 2026
**Author:** Principal Software Architect
**Prerequisite:** Sprint 0–4 FINAL & LOCKED
**Revision:** 5.1 — Architecture Hardening (ADR, Dependency Rules, Domain Boundary, Extension Strategy, God Service Prevention, Evolution, Governance)

---

## Table of Contents

1. [Overall System Architecture](#1-overall-system-architecture)
2. [Module Boundary](#2-module-boundary)
3. [Data Flow](#3-data-flow)
4. [State Management](#4-state-management)
5. [Query Architecture](#5-query-architecture)
6. [Domain Service Architecture](#6-domain-service-architecture)
7. [View Model Architecture](#7-view-model-architecture)
8. [Component Architecture](#8-component-architecture)
9. [Performance Strategy](#9-performance-strategy)
10. [Authorization Strategy](#10-authorization-strategy)
11. [Error Handling Strategy](#11-error-handling-strategy)
12. [Folder & Namespace Architecture](#12-folder--namespace-architecture)
13. [Engineering Guidelines](#13-engineering-guidelines)
14. [Scalability Review](#14-scalability-review)
15. [Architecture Risk Assessment](#15-architecture-risk-assessment)
16. [Final Architecture Audit](#16-final-architecture-audit)
17. [Architecture Decision Records](#17-architecture-decision-records)
18. [Layer Dependency Rules](#18-layer-dependency-rules)
19. [Stable Domain Boundary](#19-stable-domain-boundary)
20. [Future Extension Strategy](#20-future-extension-strategy)
21. [God Service Prevention](#21-god-service-prevention)
22. [Architecture Evolution](#22-architecture-evolution)
23. [Engineering Governance](#23-engineering-governance)

---

## 1. Overall System Architecture

### 1.1 Design Philosophy

Workspace Pelacakan & Monitoring adalah **READ MODEL** — bukan tempat CRUD. Arsitektur mengikuti prinsip **CQRS-lite**: write path (FC tracking) terpisah dari read path (monitoring workspace). Ada satu arah data: database → query layer → service → view model → UI. Tidak ada arah balik dari UI ke database.

### 1.2 Layered Architecture

```
┌─────────────────────────────────────────────────────┐
│                     Presentation Layer                   │
│  ┌─────────────────────────────────────────────────┐  │
│  │  Filament Page (MonitoringWorkspace)            │  │
│  │  ├── Header (blade section)                     │  │
│  │  ├── Exception Band (blade section)              │  │
│  │  ├── Toolbar (Filament Forms)                    │  │
│  │  ├── Table (custom Livewire component)           │  │
│  │  ├── Footer (blade section)                      │  │
│  │  └── Detail SlideOver (Filament Action)         │  │
│  └─────────────────────────────────────────────────┘  │
└───────────────────────┬─────────────────────────────────┘
                        │  View Models (read-only DTOs)
┌───────────────────────▼─────────────────────────────────┐
│                   Application Layer                       │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────┐ │
│  │ Monitoring  │  │ Detail Unit  │  │ Exception       │ │
│  │ Query       │  │ Provider     │  │ Evaluator       │ │
│  │ Service     │  │              │  │                 │ │
│  └──────┬──────┘  └──────┬───────┘  └────────┬────────┘ │
│         │                │                   │          │
│         │  ┌─────────────▼────────────┐      │          │
│         │  │ Domain Services          │      │          │
│         │  │ ├── StageResolver        │      │          │
│         │  │ ├── ProgressCalculator   │      │          │
│         │  │ ├── TimelineBuilder      │      │          │
│         │  │ ├── InspectionSummary   │      │          │
│         │  │ ├── LeadTimeBuilder     │      │          │
│         │  │ └── AgeCalculator       │      │          │
│         │  └─────────────────────────┘      │          │
└─────────┼──────────────────────────────────┼──────────┘
          │                                  │
┌─────────▼──────────────────────────────────▼──────────┐
│                    Query Layer                           │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │ Unit        │  │ Shipment     │  │ Exception    │  │
│  │ Monitoring  │  │ Detail       │  │ Count        │  │
│  │ Query       │  │ Query        │  │ Query        │  │
│  │ Builder     │  │ Builder      │  │ Builder      │  │
│  └──────┬──────┘  └──────┬───────┘  └──────┬───────┘  │
│         │                │                 │          │
│  ┌──────▼────────────────▼─────────────────▼───────┐  │
│  │          Eloquent Models (Shipment, Unit,        │  │
│  │          ShipmentTrack, UnitInspection,          │  │
│  │          Voyage, Customer, Branch)               │  │
│  └─────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────┘
```

### 1.3 Layer Responsibilities

| Layer | Responsibility | Knows About | Does NOT Know About |
|-------|---------------|-------------|-------------------|
| Presentation | Render View Models, manage UI state, dispatch user intent | View Models, Livewire state | Eloquent, DB, business rules |
| Application | Orchestrate queries → services → view models for a use-case | Query Builders, Domain Services | Blade, Filament internals |
| Domain Service | Pure business logic (stage resolution, progress, timeline, exceptions) | Enums, Models (read-only), Config | DB queries, Eloquent Builder, Blade |
| Query Layer | Build and execute efficient Eloquent queries | Eloquent, DB schema, relations | Business rules, View Models |
| View Model | Immutable data carrier shaped for the UI | Nothing | Everything except its own data |

### 1.4 Dependency Rules

- **Presentation → Application → Domain Service + Query Layer** (allowed)
- **Application → Domain Service** (allowed)
- **Domain Service → Enums + Config + Model attributes** (allowed, read-only)
- **Query Layer → Eloquent Models** (allowed)
- **View Model ← Application** (constructed by Application, consumed by Presentation)
- **Domain Service → Query Layer** (FORBIDDEN — service receives data, not queries)
- **View Model → anything** (FORBIDDEN — pure data carrier)
- **Blade → Domain Service** (FORBIDDEN — blade only reads View Model)
- **Livewire → Eloquent** (FORBIDDEN — Livewire delegates to Application layer)

### 1.5 Key Architectural Decision: Custom Page, Not Filament Resource

Cerita Filament Resource dirancang untuk CRUD operations dengan FilamentTable. Workspace ini adalah read-only monitoring surface dengan hybrid row, group mode, slide-over, exception-first sort, dan keyboard navigation — semua tidak didukung oleh FilamentTable.

**Keputusan D19 (LOCKED):** Custom Filament Page (`Filament\Pages\Page`) + custom Livewire table component.

Filament Page menyediakan: authentication, navigation integration, panel middleware (ScopeByBranch, EnsurePanelRole), render hooks, dan `maxContentWidth = 'full'`. Custom Livewire component menyediakan: hybrid row rendering, reactive state, polling, keyboard navigation.

---

## 2. Module Boundary

### 2.1 Module Map

```
MonitoringWorkspace (Filament Page)
│
├── calls → MonitoringQueryService
│   ├── calls → UnitMonitoringQueryBuilder
│   ├── calls → ExceptionCountQueryBuilder
│   └── returns → MonitoringRowData[] (paginated)
│
├── calls → ExceptionEvaluator
│   ├── calls → StageResolver
│   └── returns → ExceptionChipData[]
│
├── calls → DetailUnitProvider
│   ├── calls → ShipmentDetailQueryBuilder
│   ├── calls → TimelineBuilder
│   ├── calls → InspectionSummaryBuilder
│   ├── calls → LeadTimeBuilder
│   └── returns → UnitDetailData
│
└── calls → WorkspaceSummaryBuilder
    └── returns → WorkspaceSummaryData
```

### 2.2 Module Contracts

#### MonitoringQueryService

**Responsibility:** Menerima filter + state from Livewire, meng-executed query, meng-transform hasil ke MonitoringRowData collection.

**Input:** `MonitoringFilter` (DTO — branch_id, mode, route, exception_type, search, group_mode, show_finished, sort, page)

**Output:** `LengthAwarePaginator<MonitoringRowData>`

**May call:** UnitMonitoringQueryBuilder, StageResolver, AgeCalculator, ExceptionEvaluator

**May NOT call:** Blade, Livewire, Filament components, TimelineBuilder, InspectionSummaryBuilder

#### ExceptionEvaluator

**Responsibility:** Mengevaluasi 6 exception types untuk satu shipment/unit berdasarkan track status, voyage, inspection, dan age.

**Input:** `Shipment` (with loaded relations: tracks, voyageRecord, units.inspections)

**Output:** `ExceptionChipData[]` (array of exception chips with type, severity, label, metadata)

**May call:** StageResolver, ExceptionCountQueryBuilder (for count aggregates only)

**May NOT call:** MonitoringQueryService, DetailUnitProvider, Blade, TimelineBuilder

#### DetailUnitProvider

**Responsibility:** Orchestrate semua data yang dibutuhkan slide-over Detail Unit.

**Input:** `int $unitId`, `MonitoringFilter` (for branch scoping)

**Output:** `UnitDetailData` (containing UnitTimeline, InspectionSummary, CurrentState, SiblingUnits, AdministrativeInfo)

**May call:** ShipmentDetailQueryBuilder, TimelineBuilder, InspectionSummaryBuilder, LeadTimeBuilder, StageResolver, ProgressCalculator

**May NOT call:** MonitoringQueryService, ExceptionEvaluator, ExceptionCountQueryBuilder

#### TimelineBuilder

**Responsibility:** Membangun ordered timeline dari ShipmentTrack collection untuk mode tertentu (sea/land).

**Input:** `Shipment` (with `tracks` loaded), `ShipmentMode`

**Output:** `UnitTimeline` (ordered stages with status: completed | current | pending | skeleton, tracker info, timestamp)

**May call:** TrackStatus enum methods only

**May NOT call:** Query Builder, Eloquent relations (lazy), ShipmentKpiEvaluator, Blade

#### StageResolver

**Responsibility:** Menentukan current stage, next stage, dan progress percentage dari shipment berdasarkan tracks.

**Input:** `Shipment` (with `tracks` dan `latestTrack` loaded), `ShipmentMode`

**Output:** `CurrentStageData` (stage enum, stage label, progress_pct, next_stage, is_held, is_cancelled)

**May call:** TrackStatus enum methods

**May NOT call:** DB, relations, KPI evaluator

#### ProgressCalculator

**Responsibility:** Menghitung progress percentage untuk progress bar.

**Input:** `TrackStatus $currentStage`, `ShipmentMode`, `bool $isHeld`, `bool $isCancelled`

**Output:** `int` (0–100)

**May call:** TrackStatus enum methods

#### InspectionSummaryBuilder

**Responsibility:** Merangkum data inspeksi untuk satu unit.

**Input:** `Unit` (with `inspections.items` loaded)

**Output:** `InspectionSummary` (per-stage: status, gate_decision, ng_count, is_submitted, finding_summary)

**May call:** UnitInspection model constants, UnitInspectionItem model constants

**May NOT call:** Voyage, Shipment, Timeline, KPI

#### LeadTimeBuilder

**Responsibility:** Membangun lead time summary untuk shipment (Manado KPI only).

**Input:** `Shipment` (with `tracks` loaded)

**Output:** `LeadTimeData` (per-stage actual vs target, total badge, summary text) or `null` if not Manado KPI target

**May call:** ShipmentKpiEvaluator (existing service)

**May NOT call:** Timeline, Inspection, Voyage

#### AgeCalculator

**Responsibility:** Menghitung age (hari sejak tracking terakhir atau fallback).

**Input:** `?Carbon $lastTrackedAt`, `?Carbon $requestedAt`, `ShipmentMode`

**Output:** `AgeData` (days, label, is_stuck, fallback_used)

**May call:** config('jss_kpi.thresholds.stuck_days')

#### ShipmentDetailQueryBuilder

**Responsibility:** Build single-shipment query with all relations untuk slide-over.

**Input:** `int $shipmentId`, `?int $branchId`

**Output:** `?Shipment` (with: tracks, units.inspections.items, voyageRecord, customer, branch, originCity, destinationCity, pol, pod, driver, shippingSchedule)

### 2.3 Forbidden Knowledge Matrix

| Module | Shipment | Unit | Track | Inspection | Voyage | KPI | Blade | Livewire |
|--------|---------|------|-------|-----------|--------|-----|-------|----------|
| MonitoringQueryService | R | R | R | R | R | — | — | — |
| ExceptionEvaluator | R | R | R | R | R | — | — | — |
| DetailUnitProvider | R | R | R | R | R | R | — | — |
| TimelineBuilder | R | — | R | — | — | — | — | — |
| StageResolver | R | — | R | — | — | — | — | — |
| InspectionSummaryBuilder | — | R | — | R | — | — | — | — |
| LeadTimeBuilder | R | — | R | — | — | R | — | — |
| MonitoringWorkspace (Page) | — | — | — | — | — | — | W | R |
| Table (Livewire) | — | — | — | — | — | — | W | W |

*R = read, W = write/own, — = must not know*

---

## 3. Data Flow

### 3.1 Initial Page Load

```
User → GET /admin/monitoring-workspace
  │
  ├─ Middleware: Authenticate → EnsurePanelRole → ScopeByBranch
  │    └─ app()->instance('currentBranchId', $branchId|null)
  │
  ├─ MonitoringWorkspace::mount()
  │    ├─ resolveBranchScope() → from user (office_admin) or null (super_admin)
  │    ├─ resolveDefaultRoute() → 'tam' (from RouteCode::default())
  │    ├─ initialize state (filter, sort, group_mode, page)
  │    └─ generateData()
  │         │
  │         ├─ MonitoringQueryService::paginate(MonitoringFilter)
  │         │    ├─ UnitMonitoringQueryBuilder::build($filter)
  │         │    │    ├─ base scope: active shipments, branch, mode, route
  │         │    │    ├─ exclude pre-pickup (D3): status NOT IN [draft, pending]
  │         │    │    ├─ exclude finished by default (D2): status NOT IN [delivered, cancelled]
  │         │    │    ├─ eager load: tracks, latestTrack, units, units.inspections,
  │         │    │    │              voyageRecord, customer, branch, originCity, destinationCity
  │         │    │    ├─ sort: exception-first, then age desc
  │         │    │    └─ paginate(50)
  │         │    │
  │         │    ├─ transform each Shipment → MonitoringRowData
  │         │    │    ├─ StageResolver::resolve($shipment, $mode) → CurrentStageData
  │         │    │    ├─ AgeCalculator::calculate($lastTrackedAt, $requestedAt, $mode)
  │         │    │    ├─ ProgressCalculator::calculate($stage, $mode, $held, $cancelled)
  │         │    │    ├─ ExceptionEvaluator::evaluate($shipment) → ExceptionChipData[]
  │         │    │    └─ assemble MonitoringRowData (immutable)
  │         │    │
  │         │    └─ return LengthAwarePaginator<MonitoringRowData>
  │         │
  │         ├─ ExceptionCountQueryBuilder::count($filter) → ExceptionBandData
  │         │    (6 counts: delay, ng, hold, demurrage, missing_voyage, pdi_pending)
  │         │
  │         ├─ WorkspaceSummaryBuilder::build($filter) → WorkspaceSummaryData
  │         │    (total_units, active_units, in_transit, at_port, delivered_today)
  │         │
  │         └─ set view data → render blade
  │
  └─ HTTP Response → HTML
```

### 3.2 Search (Jump-to + Highlight)

```
User types in search box
  │
  ├─ Livewire: wire:model.live="search" (debounce 300ms)
  │
  ├─ Livewire updatedSearch()
  │    ├─ reset page to 1
  │    └─ generateData() → re-query with search term
  │         │
  │         ├─ UnitMonitoringQueryBuilder::build($filter)
  │         │    └─ search scope: WHERE (code ILIKE %term% OR doc_number ILIKE %term%
  │         │          OR EXISTS (SELECT 1 FROM units WHERE shipment_id = shipments.id
  │         │          AND (reg_no ILIKE %term% OR model_no ILIKE %term% OR chassis_no ILIKE %term%)))
  │         │
  │         ├─ Rows that match are highlighted (is_search_match = true in MonitoringRowData)
  │         └─ Rows that don't match are still visible (D14: jump-to + highlight, not filter)
  │              └─ Match units within shipment are marked; non-match units in same shipment are dimmed
  │
  └─ Re-render table section only (Livewire diff)
```

### 3.3 Filter Application

```
User selects filter (exception type, mode, route, show_finished toggle)
  │
  ├─ Livewire: wire:click or wire:change
  │
  ├─ Livewire applyFilter()
  │    ├─ update MonitoringFilter DTO
  │    ├─ reset page to 1
  │    ├─ update URL query params (URL State sync)
  │    └─ generateData()
  │         │
  │         ├─ Exception type filter: WHERE EXISTS track with matching exception condition
  │         ├─ Mode filter: WHERE mode = $mode
  │         ├─ Route filter: WHERE route_code = $route (or customer_id IN manado_ids for TAM)
  │         ├─ Show finished (D2): if true, remove delivered/cancelled exclusion
  │         └─ Exception Band counts re-calculated with same filter
  │
  └─ Re-render Exception Band + Table + Summary
```

### 3.4 Group Mode Toggle

```
User toggles group_mode (flat → SPPB → Voyage)
  │
  ├─ Livewire: toggleGroupMode($mode)
  │    ├─ $this->group_mode = $mode  // 'flat' | 'sppb' | 'voyage'
  │    ├─ persist to session
  │    └─ generateData()
  │         │
  │         ├─ flat: render each unit as own row (default, D4)
  │         ├─ sppb: group by shipment_id, show unit-count summary
  │         │    └─ query: GROUP BY shipment_id, aggregate unit_count via withCount
  │         └─ voyage: group by voyage_id, show shipment-count + unit-count summary
  │              └─ query: GROUP BY voyage_id, WHERE mode = 'sea' AND voyage_id IS NOT NULL
  │
  └─ Table re-renders with group headers
```

### 3.5 Refresh (Manual + Polling)

```
Manual Refresh:
  User clicks "Refresh" button
  │
  ├─ Livewire: refresh()
  │    ├─ clear cached query results for this filter
  │    └─ generateData()
  │
  └─ Re-render all sections

Polling (wire:poll):
  Every 60 seconds (configurable)
  │
  ├─ Livewire: pollRefresh()
  │    ├─ poll only Exception Band counts (lightweight query)
  │    ├─ poll WorkspaceSummary counters
  │    └─ if counts changed → dispatch browser event → update badge
  │         (table data NOT refreshed on poll — only on manual refresh)
  │
  └─ Only Exception Band + Summary badges update via Alpine.js diff
```

### 3.6 Open Detail Unit (Slide-over)

```
User clicks unit row
  │
  ├─ Livewire: openDetail($unitId)
  │    ├─ $this->selected_unit_id = $unitId
  │    ├─ $this->dispatch('open-unit-detail', unitId: $unitId)
  │    └─ DetailUnitProvider::provide($unitId, $branchScope)
  │         │
  │         ├─ ShipmentDetailQueryBuilder::build($shipmentId, $branchId)
  │         │    └─ eager load: tracks (ordered), units.inspections.items,
  │         │       voyageRecord, customer, branch, originCity, destinationCity,
  │         │       pol, pod, driver, shippingSchedule
  │         │
  │         ├─ StageResolver::resolve($shipment, $mode) → CurrentStageData
  │         ├─ ProgressCalculator::calculate(...) → int
  │         ├─ TimelineBuilder::build($shipment, $mode) → UnitTimeline
  │         ├─ InspectionSummaryBuilder::build($unit) → InspectionSummary
  │         ├─ LeadTimeBuilder::build($shipment) → LeadTimeData|null
  │         ├─ AgeCalculator::calculate(...) → AgeData
  │         └─ assemble UnitDetailData (immutable)
  │
  ├─ Filament Action::slideOver opens with UnitDetailData
  │    └─ Infolist + custom Timeline blade renders UnitDetailData
  │
  └─ Deep-links rendered as buttons (no inline edit)
```

### 3.7 Deep Link Navigation

```
User clicks deep-link button in slide-over (e.g., "Update Tracking")
  │
  ├─ Button is <a href> to existing Filament Resource page
  │    ├─ ShipmentTracking: /admin/shipment-trackings/{id}/manage
  │    ├─ Shipment View: /admin/shipments/{id}
  │    ├─ Voyage Detail: /admin/voyages/{id}
  │    ├─ Inspection: /admin/units/{id}/inspections
  │    └─ Customer: /admin/customers/{id}
  │
  └─ Browser navigates (full page load, Filament Resource)
  │    └─ Slide-over state lost (intended — D1: workspace is read-only)
  │
  Back button → returns to workspace with URL state restored
```

### 3.8 Keyboard Navigation Flow

```
User presses keyboard (j/k/Enter/Esc)
  │
  ├─ Livewire: handleKeyboard($event)
  │    ├─ j/↓: move focus down (dispatch('focus-row', index: idx+1))
  │    ├─ k/↑: move focus up (dispatch('focus-row', index: idx-1))
  │    ├─ Enter: openDetail($currentRowUnitId)
  │    └─ Esc: closeDetail() / clear search
  │
  └─ Alpine.js handles focus shift on client side (no server round-trip for j/k)
```

---

## 4. State Management

### 4.1 State Taxonomy

#### Persistent State (survives page reload via URL)

| Property | Type | Default | URL Key | Notes |
|----------|------|---------|---------|-------|
| `branch_id` | `?int` | user's effectiveBranchId or null | `branch` | super_admin can change; office_admin locked |
| `mode` | `?string` | `null` (all modes) | `mode` | 'sea' | 'land' |
| `route` | `?string` | `'tam'` (default route) | `route` | route code |
| `exception_filter` | `?string` | `null` | `exception` | one of 6 types or null |
| `show_finished` | `bool` | `false` (D2) | `finished` | toggle delivered/cancelled visibility |
| `group_mode` | `string` | `'flat'` (D4) | `group` | 'flat' | 'sppb' | 'voyage' |
| `sort` | `string` | `'exception-first'` | `sort` | see sort options |
| `page` | `int` | `1` | `page` | pagination cursor |
| `search` | `string` | `''` | `q` | search term (D14: jump-to, not filter) |

**Implementation:** `Filament\Pages\Concerns\InteractsWithUrlState` or manual `$this->fillFromUrl()` in `mount()` + `updatedXxx()` hooks calling `$this->updateUrl()`.

#### Transient State (Livewire component, not persisted)

| Property | Type | Default | Notes |
|----------|------|---------|-------|
| `selected_unit_id` | `?int` | `null` | currently selected unit for slide-over |
| `is_loading` | `bool` | `false` | loading skeleton state |
| `is_refreshing` | `bool` | `false` | manual refresh spinner |
| `last_poll_at` | `?Carbon` | `null` | timestamp of last poll refresh |
| `poll_counts` | `?ExceptionBandData` | `null` | cached counts from last poll |
| `poll_summary` | `?WorkspaceSummaryData` | `null` | cached summary from last poll |

#### Computed State (derived, cached per request)

| Property | Type | Computed By | Cache TTL | Notes |
|----------|------|-------------|-----------|-------|
| `rows` | `Paginator<MonitoringRowData>` | MonitoringQueryService | per render | invalidated on filter change |
| `exception_band` | `ExceptionBandData` | ExceptionCountQueryBuilder | 30s cache | cache key includes filter hash |
| `workspace_summary` | `WorkspaceSummaryData` | WorkspaceSummaryBuilder | 30s cache | cache key includes filter hash |
| `unit_detail` | `?UnitDetailData` | DetailUnitProvider | per slide-over open | not cached (on-demand) |

#### Session State (persisted across visits)

| Property | Key | Default | Notes |
|----------|-----|---------|-------|
| `group_mode` | `monitoring.group_mode` | `'flat'` | also in URL, but session is fallback |
| `collapsed_exceptions` | `monitoring.collapsed_exceptions` | `[]` | which exception chips user collapsed |
| `acknowledged_exceptions` | `monitoring.acknowledged_{id}` | `[]` | dismissed exception alerts |

#### URL State (shareable, bookmarkable)

All Persistent State properties are serialized to URL query string. This enables:
- Bookmark a specific filtered view
- Share URL with colleague
- Browser back button restores exact workspace state
- Deep-link into workspace with pre-applied filter (e.g., from dashboard alert)

### 4.2 State Lifecycle

```
mount()
  ├─ fillFromUrl() → restore state from query params
  ├─ applyBranchScope() → enforce office_admin branch lock
  ├─ resolveDefaultRoute() → set 'tam' if no route in URL
  ├─ generateData() → initial load
  └─ render()

updated{Property}($value)
  ├─ validate (e.g., mode must be 'sea'|'land'|null)
  ├─ reset page to 1 (for filter changes)
  ├─ updateUrl() → push new query params
  ├─ generateData() → re-query
  └─ render() → Livewire diff

pollRefresh() (wire:poll.60s)
  ├─ refresh only exception_band + workspace_summary
  ├─ compare with cached values
  └─ dispatch browser event if changed

refresh() (manual)
  ├─ clear caches
  ├─ generateData() → full re-query
  └─ render()
```

---

## 5. Query Architecture

### 5.1 Query Strategy Overview

The workspace must remain responsive with thousands of active units. The core strategy is:

1. **One paginated query** for the table (50 rows per page)
2. **One aggregate query** for exception band counts
3. **One aggregate query** for workspace summary
4. **One on-demand query** for slide-over detail (per unit click)
5. **Polling touches only aggregate queries** (never the table query)

### 5.2 Unit Monitoring Query (Main Table Query)

```
UnitMonitoringQueryBuilder::build(MonitoringFilter $filter): Builder
```

**Base scope:**
```
shipment_status NOT IN [draft]                     -- D3: exclude pre-pickup
  AND (show_finished = false → status NOT IN [delivered, cancelled])  -- D2
  AND branch_id = $branchId (if set)               -- scope
  AND mode = $mode (if set)                        -- filter
  AND route matches $route (if set)                -- filter
```

**Search (D14: jump-to + highlight, NOT filter):**
Search does NOT add WHERE clauses that exclude rows. Instead, it adds a `is_search_match` boolean flag via LEFT JOIN subquery:

```
SELECT shipments.*,
  CASE WHEN EXISTS (
    SELECT 1 FROM units WHERE shipment_id = shipments.id
    AND (reg_no ILIKE :term OR model_no ILIKE :term OR chassis_no ILIKE :term)
  ) OR shipments.code ILIKE :term OR shipments.doc_number ILIKE :term
  THEN true ELSE false END AS is_search_match
```

All rows are returned. Frontend highlights matches and dims non-matches.

**Exception-first sort (default):**
```
ORDER BY
  has_exception DESC,           -- computed via subquery
  age_days DESC,                -- days since last tracked_at (or requested_at fallback)
  shipments.created_at DESC
```

`has_exception` is computed via a `CASE WHEN EXISTS` subquery covering all 6 exception types.

**Eager loading (N+1 prevention):**
```
->with([
  'tracks' => fn($q) => $q->orderBy('tracked_at', 'asc')->select([
    'id', 'shipment_id', 'status', 'tracked_at', 'note', 'location',
    'plan_loading_time_at', 'plan_closing_time_at',
    'actual_loading_time_at', 'actual_closing_time_at',
  ]),
  'latestTrack' => fn($q) => $q->select(['id', 'shipment_id', 'status', 'tracked_at']),
  'units' => fn($q) => $q->select([
    'id', 'shipment_id', 'model_no', 'reg_no', 'chassis_no',
    'color', 'container_display', 'qty',
  ])->with([
    'inspections' => fn($q) => $q->select([
      'id', 'unit_id', 'stage', 'status', 'gate_decision',
      'submitted_at', 'checked_at',
    ])->withCount(['items as ng_count' => fn($q) => $q->where('result', 'ng')]),
  ]),
  'voyageRecord:id,voyage_no,vessel_id,etd,eta,atd_at,ata_at,pol_id,pod_id',
  'voyageRecord.vessel:id,name,code',
  'customer:id,name',
  'branch:id,name',
  'originCity:id,name',
  'destinationCity:id,name',
  'pol:id,name',
  'pod:id,name',
])
```

This produces exactly **3 queries** for the entire page:
1. Main paginated query (with eager-loaded relations = 1 query with joins/subqueries for counts)
2. Exception band counts (1 query)
3. Workspace summary (1 query)

### 5.3 Exception Count Query

```
ExceptionCountQueryBuilder::count(MonitoringFilter $filter): ExceptionBandData
```

Single query using conditional aggregate:

```
SELECT
  COUNT(*) FILTER (WHERE delay_condition) AS delay_count,
  COUNT(*) FILTER (WHERE ng_condition) AS ng_count,
  COUNT(*) FILTER (WHERE hold_condition) AS hold_count,
  COUNT(*) FILTER (WHERE demurrage_condition) AS demurrage_count,
  COUNT(*) FILTER (WHERE missing_voyage_condition) AS missing_voyage_count,
  COUNT(*) FILTER (WHERE pdi_pending_condition) AS pdi_pending_count,
  COUNT(*) AS total
FROM shipments
WHERE [same base scope as main query]
```

Exception conditions (each is a subquery EXISTS):

| Exception | Condition |
|-----------|-----------|
| Delay | `EXISTS (SELECT 1 FROM shipment_tracks WHERE shipment_id = shipments.id AND status = 'hold' AND tracked_at IS NOT NULL)` OR voyageRecord.is_delayed |
| NG | `EXISTS (SELECT 1 FROM units JOIN unit_inspections ON ... WHERE unit_inspection_items.result = 'ng' AND finding_type = 'major_damage')` |
| Hold | `EXISTS latestTrack.status = 'hold'` |
| Demurrage | `voyageRecord.ata_at IS NOT NULL AND delivered_at IS NULL AND age_at_port > N` |
| Missing Voyage | `mode = 'sea' AND voyage_id IS NULL` |
| PDI Pending | `EXISTS unit with inspection stage = 'handover_depot' AND submitted_at IS NULL` |

**Note:** PostgreSQL supports `COUNT(*) FILTER (WHERE ...)` syntax directly. For MySQL compatibility, use `SUM(CASE WHEN ... THEN 1 ELSE 0 END)`.

### 5.4 Detail Unit Query (On-Demand)

```
ShipmentDetailQueryBuilder::build(int $shipmentId, ?int $branchId): ?Shipment
```

Single query with full eager loading for one shipment:

```
->with([
  'tracks' => ordered, with all columns,
  'units' => with inspections.items (full),
  'units.inspections',
  'units.inspections.items',
  'units.inspections.checkedBy:id,name',
  'voyageRecord', 'voyageRecord.vessel', 'voyageRecord.pol', 'voyageRecord.pod',
  'customer', 'branch', 'originOffice', 'destinationOffice',
  'originCity', 'destinationCity', 'pol', 'pod',
  'driver', 'shippingSchedule',
  'assignedDepot',
  'cancelledBy:id,name', 'lastEditor:id,name',
])
```

**Branch scope guard:** If `$branchId` is set and shipment's `branch_id !== $branchId`, return `null`.

### 5.5 Workspace Summary Query

```
WorkspaceSummaryBuilder::build(MonitoringFilter $filter): WorkspaceSummaryData
```

Single aggregate query:

```
SELECT
  COUNT(DISTINCT s.id) AS total_shipments,
  COUNT(DISTINCT u.id) AS total_units,           -- via LEFT JOIN units
  COUNT(DISTINCT u.id) FILTER (WHERE s.status = 'transit') AS in_transit_units,
  COUNT(DISTINCT u.id) FILTER (WHERE latest_track.status IN ('delivery_to_port','stacking')) AS at_port_units,
  COUNT(DISTINCT s.id) FILTER (WHERE s.status = 'delivered' AND s.delivered_at::date = today) AS delivered_today
FROM shipments s
LEFT JOIN units u ON u.shipment_id = s.id
LEFT JOIN LATERAL (
  SELECT status FROM shipment_tracks
  WHERE shipment_id = s.id AND tracked_at IS NOT NULL
  ORDER BY tracked_at DESC LIMIT 1
) latest_track ON true
WHERE [same base scope]
```

### 5.6 N+1 Prevention Checklist

| Operation | Risk | Mitigation |
|-----------|------|-----------|
| Loading tracks per shipment | N+1 | Eager load `tracks` with select |
| Loading latest track per shipment | N+1 | `latestTrack` relation = `latestOfMany` = 1 subquery |
| Loading units per shipment | N+1 | Eager load `units` |
| Loading inspections per unit | N+1 | Eager load `units.inspections` |
| Counting NG items per inspection | N+1 | `withCount('items as ng_count')` — aggregate in SQL |
| Resolving voyage per shipment | N+1 | Eager load `voyageRecord.vessel` |
| Exception evaluation per row | N+1 | Pre-loaded relations; evaluator is pure function |
| Timeline construction per unit | N+1 | Tracks already loaded; builder is pure function |
| Detail slide-over load | N+1 | Single query with full eager load |

### 5.7 Pagination Strategy

- **Page size:** 50 rows (configurable via `config('jss_kpi.monitoring.page_size', 50)`)
- **Pagination type:** `LengthAwarePaginator` (total count needed for footer)
- **Total count query:** same base scope with `COUNT(*)` — runs once per page load
- **Cursor pagination** not used because exception-first sort requires global ordering, not cursor-friendly
- **Infinite scroll** not used (Sprint 2 UX decision: standard pagination for ERP feel)

### 5.8 Polling Query Strategy

Polling (`wire:poll.60s`) touches **only**:
1. Exception count query (1 aggregate query)
2. Workspace summary query (1 aggregate query)

Table rows are **NOT** re-queried on poll. User must click "Refresh" to update the table. This prevents:
- UI flicker from row reordering
- Lost scroll position
- CPU cost of re-transforming hundreds of rows

### 5.9 Caching Strategy

| Data | Cache Key | TTL | Invalidation |
|------|-----------|-----|---------------|
| Exception band counts | `monitoring:exceptions:{filter_hash}` | 30s | Time-based |
| Workspace summary | `monitoring:summary:{filter_hash}` | 30s | Time-based |
| Table rows | **NOT cached** | — | Re-queried on every generateData() |
| Unit detail | **NOT cached** | — | On-demand per slide-over open |
| Stuck threshold config | `config('jss_kpi.thresholds.stuck_days')` | Process-lifetime | Config file |

**Cache key hash:** `md5(serialize([$branchId, $mode, $route, $showFinished]))`

Exception filter is excluded from cache key because counts should reflect all exception types regardless of which is filtered.

### 5.10 Sort Options

| Sort | Column(s) | Notes |
|------|-----------|-------|
| `exception-first` (default) | `has_exception DESC, age_days DESC, created_at DESC` | Sprint 1B decision |
| `age-desc` | `age_days DESC, created_at DESC` | Oldest first |
| `age-asc` | `age_days ASC, created_at DESC` | Newest first |
| `stage-asc` | `stage_order ASC, created_at DESC` | Early-stage first |
| `stage-desc` | `stage_order DESC, created_at DESC` | Late-stage first |

`age_days` and `stage_order` are computed via subquery and aliased for sort.

---

## 6. Domain Service Architecture

### 6.1 Service Catalog

```
App\Services\Monitoring\
├── MonitoringQueryService          — orchestrator for table data
├── ExceptionEvaluator              — 6 exception type evaluation
├── StageResolver                   — current/next stage + progress
├── ProgressCalculator              — percentage calculation
├── TimelineBuilder                 — ordered timeline construction
├── InspectionSummaryBuilder        — inspection per-unit summary
├── LeadTimeBuilder                 — Manado KPI lead time
├── AgeCalculator                   — age + stuck detection
├── DetailUnitProvider              — slide-over data orchestrator
└── WorkspaceSummaryBuilder         — header summary numbers
```

```
App\Queries\Monitoring\
├── UnitMonitoringQueryBuilder      — main table query
├── ExceptionCountQueryBuilder      — exception band counts
├── ShipmentDetailQueryBuilder      — single shipment for slide-over
└── WorkspaceSummaryQueryBuilder    — summary aggregate
```

### 6.2 Service Details

#### MonitoringQueryService

```
Responsibility:  Transform a MonitoringFilter DTO into a paginated
                  collection of MonitoringRowData objects.
Input:           MonitoringFilter
Output:          LengthAwarePaginator<MonitoringRowData>
Dependencies:    UnitMonitoringQueryBuilder, StageResolver,
                 AgeCalculator, ProgressCalculator, ExceptionEvaluator
State:           Stateless (new instance per request)
```

**Algorithm:**
1. Build query via `UnitMonitoringQueryBuilder::build($filter)`
2. Paginate (50 per page)
3. For each `Shipment` in page:
   a. Resolve stage via `StageResolver` (uses already-loaded `latestTrack`)
   b. Calculate age via `AgeCalculator` (uses `latestTrack.tracked_at` or fallback)
   c. Calculate progress via `ProgressCalculator`
   d. Evaluate exceptions via `ExceptionEvaluator` (uses loaded relations)
   e. Construct `MonitoringRowData` (immutable, readonly)
4. Return paginator with `MonitoringRowData` items

#### ExceptionEvaluator

```
Responsibility:  Evaluate 6 exception types for a single shipment.
Input:           Shipment (with: tracks, latestTrack, units.inspections,
                 voyageRecord)
Output:          ExceptionChipData[] (array of active exceptions)
Dependencies:    StageResolver, config('jss_kpi.thresholds.stuck_days')
State:           Stateless
```

**6 Exception Types (D8 LOCKED):**

1. **Delay** — `latestTrack.status = 'hold'` OR voyageRecord.is_delayed OR age > stuck_threshold
2. **NG** — Any unit has inspection with `items.result = 'ng'` AND `finding_type = 'major_damage'`
3. **Hold** — `latestTrack.status = 'hold'` AND `tracked_at IS NOT NULL`
4. **Demurrage** — `voyageRecord.ata_at IS NOT NULL` (vessel arrived) AND shipment NOT delivered AND `age_at_port > demurrage_threshold`
5. **Missing Voyage** — `mode = 'sea'` AND `voyage_id IS NULL`
6. **PDI Pending** — Any unit has inspection `stage = 'handover_depot'` with `submitted_at IS NULL`

**Return:** Array of `ExceptionChipData` (only active exceptions, not all 6)

#### StageResolver

```
Responsibility:  Determine current stage, next stage, and whether
                 shipment is held or cancelled.
Input:           Shipment (with latestTrack loaded), ShipmentMode
Output:          CurrentStageData
Dependencies:    TrackStatus enum methods only
State:           Stateless
```

**Algorithm:**
1. Get `latestTrack` → if null, stage = first in `orderForMode`
2. If `latestTrack.status = Hold` → look back to last non-terminal tracked status
3. If `latestTrack.status = Cancelled` → stage = cancelled
4. `nextStage` = next in `TrackStatus::orderForMode()` after current
5. `stageOrder` = `TrackStatus::toNormalizedValue()` for sort

#### TimelineBuilder

```
Responsibility:  Build ordered timeline from ShipmentTrack collection.
Input:           Shipment (with tracks loaded), ShipmentMode
Output:          UnitTimeline (array of TimelineStage)
Dependencies:    TrackStatus enum methods (orderForMode, label, icon, color)
State:           Stateless
```

**Algorithm:**
1. Get ordered stages: `TrackStatus::orderForMode($mode)`
2. For each stage in order:
   a. Find matching track from `$shipment->tracks`
   b. If track exists AND `tracked_at IS NOT NULL` → status = 'completed', include timestamp, note, location
   c. If track exists AND `tracked_at IS NULL` → status = 'skeleton' (future stage placeholder)
   d. If track does not exist → status = 'pending' (never created, gap in data)
3. Mark the last completed stage as 'current'
4. Hold/Cancelled tracks are appended as special stages outside the main flow

**Important:** Timeline includes skeleton tracks (`tracked_at = null`) per Sprint 1C requirement. These represent future stages that have been pre-created by `ensureTrackSkeleton()`.

#### InspectionSummaryBuilder

```
Responsibility:  Summarize inspection status for one unit.
Input:           Unit (with inspections.items loaded)
Output:          InspectionSummary
Dependencies:    UnitInspection constants, UnitInspectionItem constants
State:           Stateless
```

**Algorithm:**
1. For each stage in `UnitInspection::STAGES`:
   a. Find inspection for this stage (if exists)
   b. If submitted → gate_decision, ng_count (from loaded items), finding summary
   c. If not submitted → status = 'pending', "Menunggu inspeksi"
   d. If no inspection record → status = 'none', "Belum ada inspeksi"
2. Per D16: If gate_decision !== 'accept' AND gate_decision is set → show 1-line summary without expand

#### LeadTimeBuilder

```
Responsibility:  Build Manado KPI lead time summary for shipment.
Input:           Shipment (with tracks loaded)
Output:          LeadTimeData or null (if not Manado KPI target)
Dependencies:    ShipmentKpiEvaluator (existing service)
State:           Stateless
```

**Algorithm:**
1. Check `Shipment::isManadoKpiTarget()` → if false, return null
2. Call `Shipment::evaluateKpiForManado()` → get summary array
3. Transform to `LeadTimeData` DTO (per-stage: actual, limit, status; total: badge, summary text)
4. Use `Shipment::kpiManadoSummaryText()` for compact text representation

#### AgeCalculator

```
Responsibility:  Calculate age (days since last activity) with fallback.
Input:           ?Carbon lastTrackedAt, ?Carbon requestedAt, ShipmentMode
Output:          AgeData (days, label, is_stuck, fallback_used)
Dependencies:    config('jss_kpi.thresholds.stuck_days', 3)
State:           Stateless
```

**Algorithm (D18 — PENDING, recommend):**
1. If `lastTrackedAt` is not null → `days = now() - lastTrackedAt` (in days)
   - `label = "{$days} hari"`
   - `fallback_used = false`
2. If `lastTrackedAt` is null AND `requestedAt` is not null → D18 fallback
   - `days = now() - requestedAt` (in days)
   - `label = "Menunggu Pickup ({$days}h)"`
   - `fallback_used = true`
3. If both null → `days = null`, `label = "—"`, `fallback_used = true`
4. `is_stuck = days !== null AND days >= config('jss_kpi.thresholds.stuck_days', 3)` (D10)

#### DetailUnitProvider

```
Responsibility:  Orchestrate all data needed for slide-over Detail Unit.
Input:           int unitId, ?int branchId
Output:          UnitDetailData
Dependencies:    ShipmentDetailQueryBuilder, StageResolver, ProgressCalculator,
                 TimelineBuilder, InspectionSummaryBuilder, LeadTimeBuilder,
                 AgeCalculator
State:           Stateless
```

**Algorithm:**
1. Find unit's shipment_id via `Unit::find($unitId)` (single query)
2. `ShipmentDetailQueryBuilder::build($shipmentId, $branchId)` → Shipment or null
3. If null → throw `ShipmentNotFoundException` (caught by error handler)
4. Build all sub-data:
   - `CurrentStageData` via StageResolver
   - `UnitTimeline` via TimelineBuilder
   - `InspectionSummary` via InspectionSummaryBuilder (for this specific unit)
   - `LeadTimeData|null` via LeadTimeBuilder
   - `AgeData` via AgeCalculator
5. Find sibling units (same shipment, excluding current unit)
6. Assemble `UnitDetailData` (immutable)

#### WorkspaceSummaryBuilder

```
Responsibility:  Compute summary numbers for workspace header.
Input:           MonitoringFilter
Output:          WorkspaceSummaryData
Dependencies:    WorkspaceSummaryQueryBuilder
State:           Stateless
```

**Returns:** total_units, active_shipments, in_transit_units, at_port_units, delivered_today.

---

## 7. View Model Architecture

### 7.1 Design Principle

View Models are **immutable, readonly PHP classes** (using `readonly` properties or constructor property promotion with `public readonly`). They contain **no methods** except `__construct` and optionally `toArray()` for serialization. They are constructed by the Application/Service layer and consumed by Blade.

### 7.2 View Model Catalog

```
App\ViewModels\Monitoring\
├── MonitoringRowData          — one row in the table
├── ExceptionChipData          — one exception chip
├── ExceptionBandData          — all 6 exception counts
├── WorkspaceSummaryData       — header summary
├── CurrentStageData           — stage info for a row/header
├── AgeData                    — age info for a row
├── UnitDetailData             — full slide-over payload
├── UnitTimeline               — timeline collection
├── TimelineStage              — one stage in timeline
├── InspectionSummary          — per-unit inspection summary
├── InspectionStageSummary     — one stage in inspection summary
├── LeadTimeData               — Manado KPI lead time
├── LeadTimeStageData          — one stage in lead time
├── SiblingUnitData            — unit sibling for slide-over
└── AdministrativeInfo        — shipment administrative metadata
```

### 7.3 View Model Definitions

#### MonitoringRowData

```
readonly class MonitoringRowData {
  public function __construct(
    public readonly int $shipment_id,
    public readonly string $shipment_code,
    public readonly string $doc_number,
    public readonly ?int $unit_id,                    // null in sppb/voyage group mode
    public readonly ?string $unit_reg_no,
    public readonly ?string $unit_model_no,
    public readonly ?string $unit_chassis_no,
    public readonly ?string $unit_color,
    public readonly ?string $container_display,
    public readonly string $customer_name,
    public readonly ?string $branch_name,
    public readonly string $route_label,              // "Jakarta → Manado"
    public readonly ShipmentMode $mode,
    public readonly ?string $voyage_no,
    public readonly ?string $vessel_name,
    public readonly CurrentStageData $stage,
    public readonly AgeData $age,
    public readonly int $progress_pct,                // 0-100
    public readonly array $exceptions,                // ExceptionChipData[]
    public readonly ?string $eta_label,               // "ETA 15 Jul" or "Estimasi (Darat) 15 Jul"
    public readonly ?string $lead_time_summary,       // "Total 18/19 | Dw 5/6 | ..." or null
    public readonly bool $is_search_match,            // D14: highlight
    public readonly bool $is_finished,
    public readonly int $unit_count,                  // for group modes
  ) {}
}
```

#### ExceptionChipData

```
readonly class ExceptionChipData {
  public function __construct(
    public readonly string $type,          // 'delay' | 'ng' | 'hold' | 'demurrage' | 'missing_voyage' | 'pdi_pending'
    public readonly string $label,         // "Delay" | "NG" | etc.
    public readonly string $severity,      // 'critical' | 'warning'
    public readonly ?string $detail,        // "3 hari" | "2 unit NG" | etc.
    public readonly ?string $icon,          // heroicon name
    public readonly ?int $count,            // for aggregated counts
  ) {}
}
```

#### ExceptionBandData

```
readonly class ExceptionBandData {
  public function __construct(
    public readonly int $delay_count,
    public readonly int $ng_count,
    public readonly int $hold_count,
    public readonly int $demurrage_count,
    public readonly int $missing_voyage_count,
    public readonly int $pdi_pending_count,
    public readonly int $total,
  ) {}
}
```

#### WorkspaceSummaryData

```
readonly class WorkspaceSummaryData {
  public function __construct(
    public readonly int $total_units,
    public readonly int $active_shipments,
    public readonly int $in_transit_units,
    public readonly int $at_port_units,
    public readonly int $delivered_today,
  ) {}
}
```

#### CurrentStageData

```
readonly class CurrentStageData {
  public function __construct(
    public readonly TrackStatus $current_stage,
    public readonly ?TrackStatus $next_stage,
    public readonly string $stage_label,
    public readonly int $stage_order,       // toNormalizedValue()
    public readonly bool $is_held,
    public readonly bool $is_cancelled,
    public readonly bool $is_delivered,
    public readonly string $flow_zone,      // D9: 'pickup' | 'pre_transit' | 'sailing' | 'arrival' | 'dooring' | 'terminal'
  ) {}
}
```

#### AgeData

```
readonly class AgeData {
  public function __construct(
    public readonly ?int $days,
    public readonly string $label,          // "3 hari" | "Menunggu Pickup (5h)" | "—"
    public readonly bool $is_stuck,         // days >= stuck_days threshold
    public readonly bool $fallback_used,    // D18: requested_at fallback
  ) {}
}
```

#### UnitDetailData

```
readonly class UnitDetailData {
  public function __construct(
    public readonly int $unit_id,
    public readonly string $unit_reg_no,
    public readonly ?string $unit_model_no,
    public readonly ?string $unit_chassis_no,
    public readonly string $unit_color,
    public readonly ?string $container_display,
    public readonly int $shipment_id,
    public readonly string $shipment_code,
    public readonly string $doc_number,
    public readonly string $customer_name,
    public readonly string $route_label,
    public readonly ShipmentMode $mode,
    public readonly CurrentStageData $stage,
    public readonly AgeData $age,
    public readonly int $progress_pct,
    public readonly UnitTimeline $timeline,
    public readonly InspectionSummary $inspection,
    public readonly ?LeadTimeData $lead_time,        // null if not Manado
    public readonly AdministrativeInfo $admin,
    public readonly array $sibling_units,            // SiblingUnitData[]
    public readonly array $deep_links,               // DeepLinkData[]
    public readonly array $exceptions,               // ExceptionChipData[]
  ) {}
}
```

#### UnitTimeline

```
readonly class UnitTimeline {
  public function __construct(
    public readonly array $stages,    // TimelineStage[]
    public readonly int $completed_count,
    public readonly int $total_count,
  ) {}
}
```

#### TimelineStage

```
readonly class TimelineStage {
  public function __construct(
    public readonly TrackStatus $status,
    public readonly string $label,
    public readonly string $icon,
    public readonly string $color_zone,      // D9: one of 6 flow zones
    public readonly string $state,           // 'completed' | 'current' | 'skeleton' | 'pending'
    public readonly ?Carbon $tracked_at,
    public readonly ?string $note,
    public readonly ?string $location,
    public readonly ?Carbon $plan_loading_time_at,
    public readonly ?Carbon $plan_closing_time_at,
    public readonly ?Carbon $actual_loading_time_at,
    public readonly ?Carbon $actual_closing_time_at,
  ) {}
}
```

#### InspectionSummary

```
readonly class InspectionSummary {
  public function __construct(
    public readonly array $stages,    // InspectionStageSummary[]
    public readonly int $total_stages,
    public readonly int $submitted_stages,
    public readonly int $pending_stages,
    public readonly int $ng_item_count,
    public readonly ?string $overall_gate_decision,  // worst-case gate
  ) {}
}
```

#### InspectionStageSummary

```
readonly class InspectionStageSummary {
  public function __construct(
    public readonly string $stage,              // 'pickup' | 'handover_depot' | ...
    public readonly string $stage_label,
    public readonly string $status,              // 'passed' | 'failed' | 'pending' | 'none'
    public readonly ?string $gate_decision,     // 'accept' | 'allow_with_remark' | 'return_to_pdc'
    public readonly int $ng_count,
    public readonly bool $is_submitted,
    public readonly ?string $summary_1line,      // D16: 1-line summary if non-accept
    public readonly ?Carbon $checked_at,
    public readonly ?string $inspector_name,
  ) {}
}
```

#### LeadTimeData

```
readonly class LeadTimeData {
  public function __construct(
    public readonly array $stages,    // LeadTimeStageData[]
    public readonly string $total_badge,     // 'On Time' | 'Late' | 'Pending'
    public readonly ?string $summary_text,   // "Total 18/19 | Dw 5/6 | Sai 10/10 | Dor 3/3"
  ) {}
}
```

#### SiblingUnitData

```
readonly class SiblingUnitData {
  public function __construct(
    public readonly int $unit_id,
    public readonly ?string $reg_no,
    public readonly ?string $model_no,
    public readonly ?string $color,
    public readonly ?string $container_display,
    public readonly bool $has_ng,
    public readonly ?string $inspection_status,
  ) {}
}
```

#### AdministrativeInfo

```
readonly class AdministrativeInfo {
  public function __construct(
    public readonly ?string $vessel_name,
    public readonly ?string $voyage_no,
    public readonly ?Carbon $etd,
    public readonly ?Carbon $eta,
    public readonly ?string $pol_name,
    public readonly ?string $pod_name,
    public readonly ?string $driver_name,
    public readonly ?string $vehicle_plate,
    public readonly ?string $priority,         // 'normal' | 'urgent'
    public readonly ?Carbon $requested_at,
    public readonly ?Carbon $delivered_at,
    public readonly ?string $pic_name,
    public readonly ?string $pic_phone,
  ) {}
}
```

---

## 8. Component Architecture

### 8.1 Component Tree

```
MonitoringWorkspace (Filament Page)
│
├── Header Section (blade partial)
│   └── renders: WorkspaceSummaryData + breadcrumbs
│
├── Exception Band Section (blade partial)
│   └── renders: ExceptionBandData (6 chips)
│   └── wire:poll.60s → pollRefresh()
│
├── Toolbar Section (Filament Form)
│   ├── Toggle: show_finished (D2)
│   ├── Select: exception_filter
│   ├── Select: mode
│   ├── Select: route
│   ├── ToggleGroup: group_mode (flat/sppb/voyage)
│   ├── TextInput: search (D14)
│   └── Button: refresh
│
├── Table Section (custom Livewire component: monitoring-table)
│   ├── Header Row (sortable column headers)
│   ├── Body Rows (MonitoringRowData[])
│   │   └── click → openDetail($unitId)
│   ├── Group Headers (if group_mode != flat)
│   ├── Search Match Highlight (D14)
│   ├── Empty State
│   └── Pagination
│
├── Footer Section (blade partial)
│   └── renders: metadata (last updated, total count, page info)
│
└── Detail SlideOver (Filament Action::slideOver)
    ├── ProgressSection (progress bar + stage + age)
    ├── TimelineSection (custom blade: unit-timeline)
    │   └── renders: UnitTimeline
    ├── InspectionSection (custom blade: unit-inspection)
    │   └── renders: InspectionSummary
    ├── LeadTimeSection (Infolist or blade)
    │   └── renders: LeadTimeData|null
    ├── SiblingUnitsSection (blade)
    │   └── renders: SiblingUnitData[]
    ├── AdministrativeSection (Infolist)
    │   └── renders: AdministrativeInfo
    └── DeepLinksSection (blade partial)
        └── renders: buttons linking to Filament Resources
```

### 8.2 Component Ownership

| Component | Type | Owner | Data Source | Reactive? |
|----------|------|-------|-------------|-----------|
| Header | Blade partial | MonitoringWorkspace page | WorkspaceSummaryData | Yes (poll + filter) |
| Exception Band | Blade partial | MonitoringWorkspace page | ExceptionBandData | Yes (poll 60s) |
| Toolbar | Filament Form | MonitoringWorkspace page | Livewire state | Yes (user input) |
| Table | Custom Livewire | monitoring-table component | MonitoringRowData[] (paginator) | Yes (filter, search, sort) |
| Footer | Blade partial | MonitoringWorkspace page | Paginator metadata | Yes (page change) |
| SlideOver | Filament Action | MonitoringWorkspace page | UnitDetailData | On-demand (open) |
| Timeline | Custom blade partial | SlideOver | UnitTimeline | No (static per open) |
| Inspection | Custom blade partial | SlideOver | InspectionSummary | No (static per open) |
| LeadTime | Infolist or blade | SlideOver | LeadTimeData | No (static per open) |
| Administrative | Infolist | SlideOver | AdministrativeInfo | No (static per open) |
| DeepLinks | Blade partial | SlideOver | DeepLinkData[] | No (static) |
| SiblingUnits | Blade partial | SlideOver | SiblingUnitData[] | No (static per open) |

### 8.3 Livewire Component: monitoring-table

```
class MonitoringTable extends Component
{
  // Receives rows via public property
  public LengthAwarePaginator $rows;
  public string $group_mode = 'flat';
  public ?int $selected_unit_id = null;
  public ?int $focused_row_index = 0;

  // Events from parent
  #[On('rows-updated')]
  public function updateRows($rows) { ... }

  #[On('open-unit-detail')]
  public function openDetail(int $unitId) { ... }

  // Keyboard navigation
  public function handleKeyboard($event) { ... }

  // Emits to parent
  // → 'open-detail' (when row clicked)
}
```

**Why custom Livewire and not Filament Table:**
- Hybrid row (unit identity + inherited SPPB state) — not supported
- Group mode with collapsible headers — not supported
- Exception-first sort with computed columns — not supported
- Row click → slide-over (not edit page) — not supported
- Keyboard navigation (j/k/Enter/Esc) — not supported
- Search as highlight (not filter) — not supported

### 8.4 SlideOver Implementation

Per D20 (LOCKED): Use Filament `Action::slideOver` (mounted from Livewire).

```
// In MonitoringWorkspace page:
public function openDetail(int $unitId): void
{
  $this->selected_unit_id = $unitId;
  $this->dispatch('open-slide-over')->to('monitoring-detail-slide');
}

// In monitoring-detail-slide Livewire component:
#[On('open-slide-over')]
public function load(int $unitId): void
{
  $this->unit_detail = app(DetailUnitProvider::class)
    ->provide($unitId, $this->branchScope);
  // Trigger Filament slideOver mount
  $this->mountAction('viewDetail');
}

public function viewDetail(): Action
{
  return Action::make('viewDetail')
    ->slideOver()
    ->modalWidth('max-w-3xl')
    ->schema([
      // Infolist components + custom blade includes
    ])
    ->modalHeading(fn() => $this->unit_detail->unit_reg_no ?? 'Detail Unit')
    ->modalSubmitAction(false)  // read-only, no save button
    ->modalCancelActionLabel('Tutup');
}
```

---

## 9. Performance Strategy

### 9.1 Performance Budget

| Operation | Target | Strategy |
|-----------|--------|----------|
| Initial page load (50 rows) | < 500ms | 3 queries (table, exceptions, summary) + eager load |
| Filter change | < 300ms | Same 3 queries, cache exceptions + summary (30s) |
| Search (debounce 300ms) | < 200ms after debounce | Same query with search match flag; no additional query |
| Poll refresh (60s) | < 100ms | 2 aggregate queries only (no table) |
| Slide-over open | < 300ms | 1 query (full eager load) + pure-function transforms |
| Manual refresh | < 500ms | Clear cache, re-run 3 queries |

### 9.2 Data Freshness Matrix

| Data | Freshness | Mechanism | Notes |
|------|-----------|-----------|-------|
| Exception band counts | Near-realtime (poll 60s) | wire:poll → 2 aggregate queries | Cached 30s between polls |
| Workspace summary | Near-realtime (poll 60s) | wire:poll → 1 aggregate query | Cached 30s |
| Table rows | Manual refresh only | User clicks refresh | No auto-refresh to prevent UI disruption |
| Unit detail (slide-over) | On-demand | Per open action | Always fresh — no cache |
| Group mode | Session-persisted | session() | Survives page reload |
| Filter state | URL-persisted | URL query params | Bookmarkable, shareable |

### 9.3 Polling Architecture

```
wire:poll.60s → pollRefresh()
  │
  ├─ Check cache: monitoring:exceptions:{hash}
  │   └─ if cache miss → ExceptionCountQueryBuilder::count()
  │       └─ store cache (30s TTL)
  │
  ├─ Check cache: monitoring:summary:{hash}
  │   └─ if cache miss → WorkspaceSummaryQueryBuilder::build()
  │       └─ store cache (30s TTL)
  │
  ├─ Compare new values with current $this->exception_band
  │   └─ if changed → update public property → Livewire re-renders band
  │
  └─ Compare new values with current $this->workspace_summary
      └─ if changed → update public property → Livewire re-renders summary
```

**Why not poll the table:** Polling the table would cause:
- Row reordering when exception-first sort changes
- Lost scroll position
- Visual flicker
- CPU cost of re-transforming 50 rows × ~10 services each
- Potential slide-over content drift

Users who want fresh table data click "Refresh" — this is the ERP convention (SAP Fiori, Stripe).

### 9.4 Memory Strategy

| Component | Memory Cost | Mitigation |
|-----------|-------------|------------|
| 50 MonitoringRowData objects | ~50 × 2KB = 100KB | Negligible |
| Eager-loaded relations per shipment | ~5KB × 50 = 250KB | Select only needed columns |
| UnitDetailData (slide-over) | ~20KB per open | Freed on close |
| Cached exception/summary | ~1KB each | 30s TTL, auto-expire |
| Paginator metadata | ~1KB | Negligible |

**Column selection is critical:** `select(['id', 'code', 'doc_number', ...])` on every eager load to avoid pulling `notes`, `attachments`, `lcl_items`, `containers` (large JSON columns) for the table. Only the slide-over query loads full columns.

### 9.5 Database Index Recommendations

These indexes should be verified/created for monitoring queries:

```
-- shipment_tracks: latest track lookup per shipment
CREATE INDEX IF NOT EXISTS idx_tracks_shipment_tracked_at
  ON shipment_tracks (shipment_id, tracked_at DESC)
  WHERE tracked_at IS NOT NULL;

-- shipments: monitoring base scope
CREATE INDEX IF NOT EXISTS idx_shipments_monitoring
  ON shipments (branch_id, mode, status, created_at DESC);

-- units: shipment lookup with container_display
CREATE INDEX IF NOT EXISTS idx_units_shipment
  ON units (shipment_id);

-- unit_inspections: per-unit lookup
CREATE INDEX IF NOT EXISTS idx_inspections_unit_stage
  ON unit_inspections (unit_id, stage);

-- unit_inspection_items: NG count
CREATE INDEX IF NOT EXISTS idx_inspection_items_result
  ON unit_inspection_items (unit_inspection_id, result);
```

### 9.6 Query Count Audit

| Scenario | Query Count | Detail |
|----------|------------|--------|
| Initial page load | 3 | table (eager-loaded) + exceptions + summary |
| Filter change | 3 | same (cache may reduce to 1 if band+summary cached) |
| Search | 3 | same query with search match subquery |
| Poll refresh | 0–2 | cache hit → 0 queries; cache miss → 2 aggregate |
| Slide-over open | 1 | single shipment with full eager load |
| Manual refresh | 3 | clear cache + re-query |

---

## 10. Authorization Strategy

### 10.1 Role Access Matrix

| Role | Workspace Access | Branch Scope | Deep Link | Edit Authority | Polling |
|------|-----------------|-------------|-----------|---------------|---------|
| Office Admin | Yes (read-only) | Fixed to `effectiveBranchId()` | Full (same branch) | None | Yes |
| Super Admin | Yes (read-only) | All branches or filterable | Full (all) | None | Yes |
| Manager | Yes (read-only) | Branch-scoped (if assigned) | Full (same branch) | None | Yes |
| Coordinator | Yes (read-only) | Branch-scoped | Full (same branch) | None | Yes |
| Field Coordinator | No (FC panel only) | — | — | — | — |
| Customer | No (customer portal) | — | — | — | — |

### 10.2 Policy Architecture

New policy: `App\Policies\MonitoringWorkspacePolicy`

```
class MonitoringWorkspacePolicy
{
  public function before(User $user, $ability): ?bool
  {
    if ($user->isSuperAdmin()) return true;
    if (!$user->isOfficeUser()) return false; // only office users
    return null;
  }

  public function viewAny(User $user): bool
  {
    return $user->isOfficeUser();  // super_admin or office_admin
  }

  public function viewDetail(User $user, Shipment $shipment): bool
  {
    // Branch scope check: office_admin can only see their branch's shipments
    if ($user->isOfficeAdmin()) {
      $branchId = $user->effectiveBranchId();
      if ($branchId && $shipment->branch_id !== null) {
        return (int) $shipment->branch_id === (int) $branchId;
      }
    }
    return true; // super_admin sees all
  }
}
```

### 10.3 Branch Scope Enforcement

```
MonitoringFilter resolves branch_id:
  ├─ Office Admin: $user->effectiveBranchId() (FORCE, regardless of URL)
  ├─ Manager/Coordinator: $user->effectiveBranchId() (FORCE)
  └─ Super Admin: from URL param or null (all branches)

Enforced in:
  ├─ UnitMonitoringQueryBuilder::build() — WHERE branch_id = $branchId
  ├─ ExceptionCountQueryBuilder::count() — same WHERE
  ├─ ShipmentDetailQueryBuilder::build() — same WHERE + null check
  └─ WorkspaceSummaryQueryBuilder::build() — same WHERE
```

This follows the existing pattern from `AdminDashboard::resolvedBranchId()` and `ScopeByBranch` middleware.

### 10.4 Deep Link Authorization

Deep links are rendered as plain `<a href>` tags. The target Filament Resource enforces its own authorization via existing policies (`ShipmentPolicy`, `ShipmentTrackPolicy`). The workspace does NOT pre-validate deep-link targets — it just renders them. If a user lacks access to the target, Filament will deny at the Resource level.

This is intentional: **workspace is read-only and shows links, but does not control link authorization**. Authorization is delegated to the target Resource's Policy.

### 10.5 Route Access (D5)

```
resolveDefaultRoute():
  ├─ All users default to 'tam' (RouteCode::default())
  ├─ Super Admin: can change route via toolbar Select
  └─ Office Admin/Manager: route select is disabled (fixed to 'tam')
```

Route filtering maps to customer scope:
- `tam` → `whereIn('customer_id', config('jss_kpi.manado.customer_ids'))`
- `other` → `whereNotIn('customer_id', config('jss_kpi.manado.customer_ids'))`
- `all` → no customer filter

---

## 11. Error Handling Strategy

### 11.1 Error Categories and Fallbacks

| Scenario | Detection | User-facing | System Action | Fallback |
|----------|-----------|-------------|---------------|----------|
| Missing Voyage | `mode = 'sea'` AND `voyage_id IS NULL` | Exception chip "Missing Voyage" | Highlight in table | Deep-link to Voyage assignment page |
| Broken Timeline (gap in tracks) | Track for stage in `orderForMode` is missing from `shipment.tracks` | Timeline stage shows "pending" (dotted empty state) | Log warning | Show gap visually; no crash |
| Missing Inspection | Unit has no inspection record for expected stage | "Belum ada inspeksi" label | Nothing | Deep-link to Inspection page |
| Deleted Shipment | `Shipment::find()` returns null in DetailUnitProvider | Slide-over shows "Data tidak ditemukan" | Log info | Close slide-over on user click |
| Permission Denied | Policy check fails on branch scope | Row not shown in table (filtered out by query) | Nothing | User never sees inaccessible data |
| Broken Relationship | `voyageRecord` is null (no Voyage model) | "—" for vessel/voyage fields | Nothing | Timeline still works (tracks are loaded) |
| Stale Data | Poll returns different counts than last render | Exception badge updates silently | Livewire diff | No user action needed |
| Workspace Error | Exception thrown during `generateData()` | "Terjadi kesalahan. Klik Refresh." | Log error | Show empty state with retry button |
| Shipment with no units | `units` relation is empty | Unit count = 0, row still shows | Nothing | Group modes skip this shipment |
| Track with unknown status | `TrackStatus::tryFrom()` returns null | Skip stage in timeline | Log warning | Timeline shows all valid stages |

### 11.2 Error Boundary in Livewire

```
MonitoringWorkspace::generateData():
  try {
    $this->rows = app(MonitoringQueryService::class)->paginate($filter);
    $this->exception_band = app(ExceptionCountQueryBuilder::class)->count($filter);
    $this->workspace_summary = app(WorkspaceSummaryBuilder::class)->build($filter);
  } catch (\Throwable $e) {
    logger()->error('[MONITORING_WORKSPACE] data generation failed', [
      'filter' => $filter->toArray(),
      'error' => $e->getMessage(),
    ]);
    $this->error_state = 'workspace_error';
    $this->rows = new LengthAwarePaginator([], 0, 50);
    $this->exception_band = ExceptionBandData::empty();
    $this->workspace_summary = WorkspaceSummaryData::empty();
  }
```

### 11.3 Slide-over Error Boundary

```
DetailUnitProvider::provide():
  $unit = Unit::find($unitId);
  if (!$unit) throw new UnitNotFoundException($unitId);

  $shipment = ShipmentDetailQueryBuilder::build($unit->shipment_id, $branchId);
  if (!$shipment) throw new ShipmentNotFoundException($unit->shipment_id);

  // If any sub-service throws, bubble up and show error in slide-over
```

Slide-over catches:
```
#[On('open-slide-over')]
public function load(int $unitId): void
{
  try {
    $this->unit_detail = app(DetailUnitProvider::class)->provide($unitId, $this->branchScope);
    $this->mountAction('viewDetail');
  } catch (UnitNotFoundException | ShipmentNotFoundException $e) {
    Notification::make()->danger()->title('Data tidak ditemukan')->send();
    $this->selected_unit_id = null;
  } catch (\Throwable $e) {
    logger()->error('[MONITORING_DETAIL] load failed', ['unit_id' => $unitId, 'error' => $e->getMessage()]);
    Notification::make()->danger()->title('Gagal memuat detail')->send();
  }
}
```

### 11.4 Graceful Degradation

If a specific relation fails to load (e.g., voyageRecord returns null), the View Model should use null-coalescing to show "—" rather than throwing. All View Model constructors accept nullable for optional fields.

Timeline gaps (missing track records) are shown as "pending" stages, not as errors.

---

## 12. Folder & Namespace Architecture

### 12.1 Proposed Structure

```
app/
├── Filament/
│   └── Pages/
│       └── MonitoringWorkspace.php               — Filament Page (controller)
│
├── Livewire/
│   └── Monitoring/
│       ├── MonitoringTable.php                    — custom table component
│       └── MonitoringDetailSlide.php              — slide-over Livewire component
│
├── Services/
│   └── Monitoring/
│       ├── MonitoringQueryService.php             — table data orchestrator
│       ├── ExceptionEvaluator.php                 — 6 exception types
│       ├── StageResolver.php                       — current/next stage
│       ├── ProgressCalculator.php                  — progress percentage
│       ├── TimelineBuilder.php                     — ordered timeline
│       ├── InspectionSummaryBuilder.php            — inspection summary
│       ├── LeadTimeBuilder.php                     — Manado KPI lead time
│       ├── AgeCalculator.php                       — age + stuck detection
│       ├── DetailUnitProvider.php                  — slide-over orchestrator
│       └── WorkspaceSummaryBuilder.php              — header summary
│
├── Queries/
│   └── Monitoring/
│       ├── UnitMonitoringQueryBuilder.php          — main table query
│       ├── ExceptionCountQueryBuilder.php          — exception band counts
│       ├── ShipmentDetailQueryBuilder.php          — slide-over query
│       └── WorkspaceSummaryQueryBuilder.php         — summary aggregate
│
├── ViewModels/
│   └── Monitoring/
│       ├── MonitoringRowData.php
│       ├── ExceptionChipData.php
│       ├── ExceptionBandData.php
│       ├── WorkspaceSummaryData.php
│       ├── CurrentStageData.php
│       ├── AgeData.php
│       ├── UnitDetailData.php
│       ├── UnitTimeline.php
│       ├── TimelineStage.php
│       ├── InspectionSummary.php
│       ├── InspectionStageSummary.php
│       ├── LeadTimeData.php
│       ├── LeadTimeStageData.php
│       ├── SiblingUnitData.php
│       ├── AdministrativeInfo.php
│       └── DeepLinkData.php
│
├── DTO/
│   └── Monitoring/
│       └── MonitoringFilter.php                    — filter DTO (input)
│
├── Policies/
│   └── MonitoringWorkspacePolicy.php               — authorization
│
└── Support/
    └── Monitoring/
        └── RouteResolver.php                       — route code mapping

resources/views/
├── filament/pages/
│   └── monitoring-workspace.blade.php              — main page template
│
└── livewire/monitoring/
    ├── monitoring-table.blade.php                  — table partial
    ├── monitoring-detail-slide.blade.php            — slide-over partial
    ├── unit-timeline.blade.php                      — timeline component
    └── unit-inspection.blade.php                    — inspection component
```

### 12.2 Naming Conventions

- **Services:** `*Builder`, `*Provider`, `*Evaluator`, `*Resolver`, `*Calculator`, `*Service`
  - Builder = constructs View Model from data
  - Provider = orchestrates multiple builders
  - Evaluator = evaluates business rules
  - Resolver = determines a single value from context
  - Calculator = pure computation
- **Queries:** `*QueryBuilder` — returns Eloquent Builder
- **ViewModels:** `*Data`, `*Summary` — readonly DTOs
- **DTO:** `*Filter` — mutable input carrier
- **Blade:** snake_case filenames matching component name

### 12.3 Registration

```
AdminPanelProvider.php:
  ->pages([
    AdminDashboard::class,
    MonitoringWorkspace::class,   // ← add
  ])

  ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
  // MonitoringWorkspace auto-discovered by namespace convention
```

Livewire components:
```
// app/Livewire/Monitoring/MonitoringTable.php
#[Livewire('monitoring-table')]
class MonitoringTable extends Component { ... }
```

Or register manually if auto-discovery doesn't cover `app/Livewire`:
```
// config/livewire.php
'class_namespace' => 'App\\Livewire',
```

---

## 13. Engineering Guidelines

### 13.1 Layer Separation Rules

1. **Blade hanya merender View Model.** Tidak ada Eloquent call, tidak ada `auth()`, tidak ada `config()`, tidak ada `now()`. Blade adalah pure template engine untuk View Model yang sudah jadi.

2. **Livewire hanya mengelola state dan interaction.** Setiap method Livewire yang memerlukan data harus memanggil Service atau QueryBuilder. Tidak ada `Shipment::query()` langsung di Livewire.

3. **Filament Page adalah Presentation Layer controller.** Ia menerima HTTP request, menginisialisasi state, memanggil Application Service, dan meneruskan View Model ke Blade. Tidak ada business logic.

4. **Service tidak boleh mengetahui UI.** Service menerima Model/DTO, mengembalikan View Model. Tidak ada `request()`, `session()`, `auth()` di Service (kecuali untuk audit logging).

5. **ViewModel tidak boleh melakukan query.** ViewModel adalah `readonly class` dengan hanya properties. Tidak ada constructor yang menerima Builder atau Model yang melakukan lazy loading.

6. **Query Builder tidak boleh mengetahui Blade.** Query Builder menerima Filter DTO, mengembalikan Eloquent Builder atau Collection. Tidak ada HTML/formatting logic.

7. **Timeline tidak boleh menghitung KPI.** TimelineBuilder hanya mengurutkan dan men-label stage. Lead time, KPI evaluation, dan badge computation berada di LeadTimeBuilder (terpisah).

8. **Inspection tidak boleh mengetahui Voyage.** InspectionSummaryBuilder hanya menerima `Unit` dengan `inspections.items`. Tidak ada akses ke `Shipment.voyageRecord` dari inspection builder.

9. **ExceptionEvaluator tidak boleh mengetahui Timeline.** Evaluator hanya melihat track status, inspection gate decision, voyage delay flag, dan age. Tidak ada timeline construction.

10. **DetailUnitProvider adalah satu-satunya service yang boleh memanggil multiple services.** Lainnya single-responsibility.

### 13.2 Data Flow Rules

11. **Satu arah:** Filter DTO → QueryBuilder → Model → Service → ViewModel → Blade. Tidak boleh ada arah balik.

12. **Tidak ada lazy loading di loop.** Semua relasi harus eager-loaded sebelum iterasi. Jika relation tidak di-eager-load, service harus meng-throw exception, bukan trigger lazy query.

13. **Filter DTO adalah satu-satunya input.** Semua filter, search, sort, group, dan pagination state di-bundle ke `MonitoringFilter` DTO. Tidak ada parameter terpisah.

### 13.3 Coding Rules

14. **readonly properties di ViewModel.** Semua ViewModel class menggunakan `public readonly` constructor property promotion. Immutable by design.

15. **Type-safe enums.** Gunakan `TrackStatus`, `ShipmentStatus`, `ShipmentMode` enum di semua signature. Tidak ada magic string.

16. **No `optional()` di View Model construction.** Semua nullable field harus explicit nullable type. `optional()` adalah code smell — jika relation mungkin null, handle di service, bukan di View Model.

17. **Config-driven thresholds.** Stuck days, demurrage threshold, page size, poll interval — semua via `config('jss_kpi.monitoring.*')`. Tidak ada hard-coded angka di service.

18. **Log but don't crash.** Pada error data partial (missing relation, broken track), log warning dan tampilkan fallback ("—"), bukan throw exception. Exception hanya untuk error total (shipment not found, permission denied).

### 13.4 Testing Rules

19. **Service unit tests** tidak boleh menggunakan database. Mock atau construct Model instances manually. Test pure logic.

20. **Query Builder integration tests** menggunakan database (SQLite in-memory atau PostgreSQL test). Test query correctness, not logic.

21. **Livewire tests** test interaction only (state changes, event dispatch). Mock the Application Service.

22. **Blade tests** render with a pre-constructed View Model — no database, no service calls.

---

## 14. Scalability Review

### 14.1 Current Design Scalability Assessment

| Requirement | Status | Notes |
|-------------|--------|-------|
| Route bertambah | **Scalable** | `RouteResolver` maps route codes to customer_id filters. Adding new route = add mapping entry. No code change needed in services. |
| Customer Portal dibuat | **Scalable** | All services are stateless and accept `MonitoringFilter`. Customer portal creates `MonitoringFilter` with `customer_id` scope instead of `branch_id`. Same View Models reusable. |
| Monitoring Truck ditambah | **Partially Scalable** | `ShipmentMode` enum already has Sea + Land. Land mode has 3-stage timeline. UnitMonitoringQueryBuilder already filters by mode. TimelineBuilder uses `orderForMode()`. No change needed. |
| Monitoring Container ditambah | **Needs Extension** | Container monitoring is at shipment level, not unit level. Would need a new `ContainerMonitoringQueryBuilder` and `ContainerRowData`. Existing exception types mostly reusable. Timeline and inspection builders reusable. |
| Monitoring Vessel diintegrasikan | **Needs Extension** | Vessel monitoring exists separately (`VoyageOperationalState`). Would need a `VoyageMonitoringFilter` and `VoyageRowData`. ExceptionEvaluator partially reusable (delay, demurrage). Timeline not reusable (voyage milestones ≠ track stages). |
| Jumlah unit meningkat 10x | **Scalable** | Pagination at 50 rows. Eager loading prevents N+1. Polling touches only aggregate queries. Column selection minimizes memory. Index strategy documented. |

### 14.2 Extensibility Points

| Extension Point | How to Extend | Impact |
|----------------|-------------|--------|
| New exception type | Add case to ExceptionEvaluator + ExceptionCountQueryBuilder | Isolated; no change to other services |
| New route | Add to `RouteResolver` mapping | No code change |
| New track stage | Add to `TrackStatus` enum + `orderForMode()` | TimelineBuilder auto-adapts |
| New inspection stage | Add to `UnitInspection::STAGES` | InspectionSummaryBuilder auto-adapts |
| Customer portal | New Filament Page with same services, different filter | All services + View Models reusable |
| Mobile-responsive | Blade already uses responsive classes | No Service/Query change |

### 14.3 What Would Need Refactoring

| If... | Then... |
|-------|--------|
| Need real-time push (WebSocket) | Add event broadcasting to ShipmentTrack model events. Replace polling with Pusher/SSE listener. Services remain same. |
| Need inline edit (violates D1) | Would require rewriting the entire architecture — D1 is LOCKED, so this should not happen |
| Need > 10,000 active units | Consider cursor pagination, denormalized summary table, or materialized view for exception counts |
| Need cross-branch view | `MonitoringFilter.branch_id` already supports null (all branches). Performance impact: larger query result. Pagination handles it. |
| Need saved views / presets | Add `SavedView` model. `MonitoringFilter` deserializes from saved view. No service change. |

---

## 15. Architecture Risk Assessment

### 15.1 Risk Matrix

| Risk | Probability | Impact | Risk Score | Mitigation |
|------|------------|--------|-----------|-----------|
| Complex main query (exception-first sort + search + filter + eager load) | Medium | High | **Medium-High** | Use subquery for `has_exception` and `age_days`; benchmark with 1000+ shipments; add database indexes |
| Large Timeline (13 stages × multiple data fields per stage) | Low | Medium | **Low-Medium** | Timeline is per-slide-over (1 unit), not per-row. 13 stages × ~8 fields = ~1KB. Negligible. |
| Heavy relationship chain (shipment → tracks → units → inspections → items) | Medium | High | **Medium-High** | Eager load with column selection. Use `withCount` for NG count instead of loading items. Slide-over loads full items. |
| Polling cost (60s interval, multiple concurrent users) | Medium | Medium | **Medium** | 30s cache on aggregate queries. Poll touches only 2 cached queries. If cache hit, no DB query. |
| State explosion (too many Livewire properties + URL sync) | Low | Medium | **Low-Medium** | Use `MonitoringFilter` DTO to bundle state. Explicit state taxonomy. Lifecycle documented. |
| Memory usage (50 rows × eager-loaded relations) | Low | Low | **Low** | Column selection on all eager loads. 50 × ~5KB = 250KB. Within PHP default 128MB. |
| Concurrency (multiple users polling simultaneously) | Low | Medium | **Low-Medium** | 30s cache shared across users (same filter hash). Cache absorbs concurrent poll requests. |
| Maintenance complexity (11 services + 4 query builders + 15 view models) | Medium | Medium | **Medium** | Each module is single-responsibility and independently testable. Clear dependency graph. Documentation in this document. |
| Stale data (poll shows old exception counts) | Low | Low | **Low** | 30s cache TTL means max 30s staleness. Acceptable for monitoring (not real-time control). |
| Shipment.voyage string shadowing relation | Medium | High | **Medium-High** | All code must use `voyageRecord()` accessor. Documented in Shipment model. QueryBuilder uses `voyageRecord` relation explicitly. |
| Track skeleton rows (tracked_at = null) appearing in data | Low | Medium | **Low-Medium** | AgeCalculator uses fallback (D18). TimelineBuilder shows skeleton stages explicitly. ExceptionEvaluator skips skeleton tracks. |

### 15.2 Mitigation Details

#### Risk: Complex Main Query

The main query combines:
- Base scope (status, branch, mode, route)
- Search match subquery (EXISTS)
- Exception-first sort (computed has_exception + age_days)
- Eager loading (tracks, units, inspections, voyage)
- Pagination

**Mitigation:**
1. Datebase indexes on `shipment_tracks(shipment_id, tracked_at DESC)` and `shipments(branch_id, mode, status)`
2. `has_exception` and `age_days` computed as subquery columns, not PHP-side evaluation
3. PostgreSQL `FILTER` clause for conditional aggregates in exception count query
4. Benchmark with seed data of 500/1000/2000 active shipments

#### Risk: Heavy Relationship Chain

```
Shipment → tracks (many)
         → units (many)
              → inspections (many)
                   → items (many)
```

**Mitigation:**
1. Table query: `withCount('items as ng_count')` — aggregate in SQL, no items loaded
2. Table query: only load `inspections` stage + gate_decision + submitted_at (5 columns)
3. Slide-over: full items loaded for this 1 unit only
4. No `with('tracks.checkseet')` in table query — checksheet JSON is heavy, only in slide-over

---

## 16. Final Architecture Audit

### 16.1 Clean Architecture Compliance

| Principle | Compliance | Notes |
|-----------|-----------|-------|
| Dependency Inversion | **Yes** | Services depend on abstractions (Enums, Config). Query Builders implement the data interface. |
| Single Responsibility | **Yes** | Each service has exactly one responsibility. 11 services, each with a single clear purpose. |
| Open/Closed | **Yes** | New exception types, routes, stages can be added without modifying existing services. |
| Liskov Substitution | **Yes** | All services are stateless and interchangeable. View Models are immutable and substitutable. |
| Interface Segregation | **Yes** | Blade only receives View Model (not Model). Livewire only receives Filter DTO (not raw request). |

### 16.2 Layer Responsibility Clarity

| Layer | Clear? | Notes |
|-------|--------|-------|
| Presentation (Filament Page + Livewire) | **Yes** | Manages state, dispatches to service, renders View Model |
| Application (MonitoringQueryService, DetailUnitProvider) | **Yes** | Orchestrates queries and services into View Models |
| Domain Service (StageResolver, TimelineBuilder, etc.) | **Yes** | Pure business logic, stateless, no DB access |
| Query Layer (QueryBuilder classes) | **Yes** | Builds and executes Eloquent queries only |
| View Model (readonly DTOs) | **Yes** | Pure data carrier, no behavior, no dependencies |

### 16.3 Coupling Assessment

| Coupling | Level | Acceptable? | Notes |
|----------|-------|-------------|-------|
| Service → Enum | Tight | Yes | Enums are stable, domain-level constants |
| Service → Config | Loose | Yes | Config values are primitives (int, string) |
| Service → Model attributes | Medium | Yes | Read-only access to eager-loaded relations; no lazy loading |
| QueryBuilder → Model relations | Tight | Yes | Query MUST know schema; this is the query layer's purpose |
| Livewire → Service | Loose | Yes | Via `app()` resolution; can be mocked for testing |
| Blade → View Model | Tight | Yes | Blade is designed to render a specific View Model shape |
| SlideOver → TimelineBuilder | Via Provider | Yes | DetailUnitProvider orchestrates; SlideOver doesn't call Builder directly |

### 16.4 Over-Engineering Check

| Area | Over-Engineered? | Recommendation |
|------|-----------------|----------------|
| 11 services for monitoring | **No** | Each has clear single responsibility. Splitting prevents "god service" anti-pattern. |
| 15 View Models | **Slightly** | Some could be merged (e.g., CurrentStageData + AgeData into RowStateData). But separation aids testing and readability. Keep as-is. |
| 4 Query Builders | **No** | Each returns a different shape and is independently testable. |
| DTO for filter | **No** | Bundling filter state into a DTO prevents parameter explosion and enables cache-key hashing. |
| Custom Livewire table | **No** | Justified by D19 — FilamentTable cannot meet requirements. |
| 30s cache on aggregate queries | **No** | Standard pattern for polling optimization. |

### 16.5 Engineer Readiness Assessment

**Can an engineer implement this module without changing Sprint 0–4 decisions?**

**Yes.** This document provides:
- Exact namespace and class names for all components
- Input/output contracts for every service and query builder
- Property listings for every View Model
- Query strategies with SQL-level detail
- State management taxonomy with lifecycle
- Data flow diagrams for every user interaction
- Error handling fallbacks
- Authorization policy skeleton
- Folder structure
- Engineering guidelines (18 rules)
- Performance budget
- Database index recommendations

An engineer needs to:
1. Read this document + Sprint 0–4 documents
2. Resolve D10 and D18 (see below)
3. Create the files per Section 12
4. Implement each class per its contract
5. Write tests per Section 13.4
6. Benchmark against Section 9.1 targets

### 16.6 Remaining Decision Points

#### D10 — Stuck Threshold (PENDING → RECOMMEND LOCK)

**Status:** PENDING since Sprint 1B
**Recommendation:** Add to `config/jss_kpi.php`:
```
'thresholds' => [
  // ... existing ...
  'stuck_days' => 3,
],
```
**Rationale:** 3 days without tracking progress is the operational red flag threshold used by the existing dashboard `getTamPortStock` method (which counts `age >= 3` as `over_three`). Consistency with existing codebase.

**Proposed:** `config('jss_kpi.thresholds.stuck_days', 3)` — configurable, default 3.

#### D18 — Age Fallback (PENDING → RECOMMEND LOCK)

**Status:** PENDING since Sprint 1B
**Recommendation:** Use `requested_at` as fallback start timestamp when `tracked_at` is null. Label as "Menunggu Pickup" with age in parentheses.

**Rationale:** `requested_at` is always set (it's filled on shipment creation). It represents the date the order was placed. For units with no tracking yet, this is the most meaningful "age since order" metric. Alternative: `created_at` — but `requested_at` is the business-relevant date.

**Proposed Implementation:**
```
if ($lastTrackedAt !== null) {
  $days = now()->diffInDays($lastTrackedAt);
  $label = "{$days} hari";
  $fallback = false;
} elseif ($requestedAt !== null) {
  $days = now()->diffInDays($requestedAt);
  $label = "Menunggu Pickup ({$days}h)";
  $fallback = true;
} else {
  $days = null;
  $label = "—";
  $fallback = true;
}
$is_stuck = $days !== null && $days >= config('jss_kpi.thresholds.stuck_days', 3);
```

### 16.7 New Decision Points for Sprint 6

| ID | Decision | Context | Recommendation |
|----|---------|---------|----------------|
| D21 | Poll interval | How often should exception band + summary poll? | 60 seconds (documented in Section 9.1). Configurable via `config('jss_kpi.monitoring.poll_interval', 60)`. |
| D22 | Page size | How many rows per page? | 50 rows (documented in Section 5.7). Configurable via `config('jss_kpi.monitoring.page_size', 50)`. |
| D23 | Demurrage threshold | How many days at port before demurrage exception fires? | Recommend 7 days (separate from stuck_days which is 3). Configurable via `config('jss_kpi.monitoring.demurrage_days', 7)`. |
| D24 | Cache TTL | How long to cache exception counts and summary? | 30 seconds (documented in Section 5.9). Configurable via `config('jss_kpi.monitoring.cache_ttl', 30)`. |
| D25 | Exception Band interaction | Should clicking an exception chip filter the table? | Yes — per Sprint 2 UX flow. Filter sets `exception_filter` and re-queries. |

### 16.8 Audit Conclusion

**Is this architecture ready for implementation?** **Yes.**

The architecture:
- Follows Clean Architecture principles with clear layer separation
- Enforces business logic isolation in Domain Services
- Prevents N+1 queries through documented eager loading strategy
- Scales to thousands of active units through pagination + caching + polling strategy
- Is extensible for future routes, customer portal, and new monitoring types
- Has clear authorization boundaries matching existing codebase patterns
- Handles errors gracefully with documented fallback behaviors
- Provides implementation-ready contracts for every component

**No Sprint 0–4 decision is violated.** All LOCKED decisions (D1–D9, D14–D20) are respected and enforced by architectural boundaries.

**Two pending decisions (D10, D18) have clear recommendations** ready for confirmation.

**Five new minor decisions (D21–D25) are recommended** for Sprint 6 confirmation but have sensible defaults that can be overridden later via config.

---

---

## 17. Architecture Decision Records

This section documents every significant architectural decision made across Sprint 0–5. Each ADR follows the standard format: Status, Context, Decision, Consequences, Rejected Alternatives. New engineers should read this section first to understand *why* the architecture is shaped the way it is without reading all six Sprint documents.

### ADR-001 — Workspace Read Model

**Status:** ACCEPTED (Sprint 0, locked D1)

**Context:**
The Pelacakan & Monitoring workspace is an operational monitoring surface for Office Admin and Super Admin. Users need to observe shipment/unit status, exceptions, and progress in real time. However, the actual tracking operations (updating track status, creating inspections, gate decisions) are performed by Field Coordinators via the FC panel and AppSheet. The workspace must never be a place where tracking updates are created or shipments are edited.

**Decision:**
The workspace is a pure READ MODEL. It only queries and displays data. All actions are deep-links to existing Filament Resources where write operations are governed by existing policies. No create/update/delete operations are available within the workspace itself.

**Consequences:**
- Positive: Simplifies architecture — no form validation, no transaction management, no optimistic locking, no conflict resolution in the workspace.
- Positive: Authorization is delegated to target Resources; workspace only needs read-level branch scoping.
- Positive: Polling and caching are safe — no risk of overwriting user edits.
- Negative: Users cannot perform inline corrections; must navigate to the target Resource.
- Negative: Deep-link navigation causes full page load (state lost on return).

**Rejected Alternatives:**
1. *Inline edit with optimistic concurrency* — Adds significant complexity (form state, validation, conflict handling). Violates the principle that FC owns operational execution.
2. *Modal-based edit in slide-over* — Would require embedding Filament Resource forms inside a Livewire slide-over. Fragile coupling between read and write surfaces.

---

### ADR-002 — Custom Filament Page (not Resource)

**Status:** ACCEPTED (Sprint 4, locked D19)

**Context:**
Filament Resources are designed for CRUD operations with FilamentTable. The monitoring table requires: hybrid rows (unit identity + inherited SPPB operational state), group mode (flat / SPPB / Voyage), slide-over detail, exception-first sort with computed columns, row click triggering slide-over (not edit page), keyboard navigation, and search-as-highlight (not filter). None of these are supported by FilamentTable.

**Decision:**
Implement the workspace as a custom Filament Page (`Filament\Pages\Page`) with `maxContentWidth = 'full'`. The table is a custom Livewire component. The slide-over uses Filament `Action::slideOver` mounted from Livewire.

**Consequences:**
- Positive: Full control over table rendering, sorting, grouping, and interaction.
- Positive: Filament Page still provides authentication, navigation, panel middleware, and render hooks.
- Positive: Consistent with existing custom pages (`AdminDashboard`, `MonitoringKapalTam`, `EvaluasiVoyage`).
- Negative: Cannot use FilamentTable features (column infrastructure, filters, bulk actions). Must build table features from scratch.
- Negative: More blade/livewire code to maintain.

**Rejected Alternatives:**
1. *Filament Resource with custom Table* — Resource forces CRUD lifecycle (List, Create, Edit, View pages). The workspace is not a CRUD surface. Hiding unused pages creates confusing navigation.
2. *Pure Livewire component (no Filament)* — Loses Filament authentication, navigation, and panel middleware. Would require re-implementing security infrastructure.
3. *Filament Resource with RelationManager pattern* — Wrong abstraction; units are not sub-resources of shipments in the monitoring context.

---

### ADR-003 — CQRS-lite Architecture

**Status:** ACCEPTED (Sprint 5)

**Context:**
The system has two distinct data access patterns: the FC panel performs writes (track updates, inspection submissions) with complex validation and business rules. The monitoring workspace performs reads with complex aggregations, eager loading, and transformations. Mixing these in the same model methods leads to fat models and unclear dependencies.

**Decision:**
Adopt a CQRS-lite separation. Write path remains in Eloquent Model events and existing Services (Shipment::appendTrack, ShipmentKpiEvaluator, etc.). Read path is served by dedicated Query Builders and Domain Services that never write. There is no shared "model method" that both reads for display and writes for updates.

**Consequences:**
- Positive: Read queries can be optimized independently (column selection, joins, caching) without affecting write logic.
- Positive: Write models remain rich with business rules; read services remain pure functions.
- Positive: Enables future optimization (materialized views, read replicas) without touching write path.
- Negative: Some logic appears in two places (e.g., stage resolution exists in both Shipment::currentTrackStatus() and StageResolver). Acceptable because they serve different consumers.
- Negative: More classes to maintain.

**Rejected Alternatives:**
1. *Full CQRS with separate write/read models* — Over-engineered for this system. Full event sourcing not needed.
2. *Fat model methods for both read and write* — Leads to N+1 issues when models are loaded in bulk and their accessor methods trigger queries.

---

### ADR-004 — Hybrid Row (Unit-centric + Shipment-aware)

**Status:** ACCEPTED (Sprint 1B, locked D4)

**Context:**
ShipmentTrack is shipment-level (no `unit_id`). Stage, age, hold, and delay are inherited from the shipment. Only PDI inspection and container assignment are unit-specific. A pure unit-centric table would miss the Shipment/SPPB context. A pure shipment-centric table would lose per-unit inspection and container detail. Office Admins think in terms of units (physical vehicles) but operational state is at the SPPB level.

**Decision:**
Table rows are hybrid: each row shows unit identity (reg_no, model_no, chassis_no, color, container) alongside inherited SPPB operational state (current stage, progress, age, exceptions, voyage, ETA). In flat mode (default), each unit is its own row. In SPPB group mode, units are grouped under a shipment header. In Voyage group mode, shipments are grouped under a voyage header.

**Consequences:**
- Positive: Users see both the physical vehicle identity and its operational context in one row.
- Positive: Group modes allow zooming out to SPPB or Voyage level without leaving the workspace.
- Negative: Row construction requires joining shipment-level and unit-level data. More complex View Model.
- Negative: Sort and filter operate at shipment level, which may be ambiguous for unit-level attributes. Mitigated by exception-first sort which uses shipment-level computed values.

**Rejected Alternatives:**
1. *Two separate tables (Units table + Shipments table)* — Forces user to cross-reference between two tables to understand a unit's operational state.
2. *Shipment-only table with expandable unit list* — Hides individual unit inspection status; users must click to expand every shipment to find NG units.
3. *Unit-only table without SPPB context* — Loses voyage, ETA, lead time, and progress information which are shipment-level.

---

### ADR-005 — Detail Unit via Slide-over

**Status:** ACCEPTED (Sprint 1C, locked D6/D15/D20)

**Context:**
Detail Unit needs to show: unit identity, full timeline, inspection summary, lead time, sibling units, administrative info, and deep links. This is too much for a table cell expansion. A full page navigation would lose the workspace context (filter state, scroll position). A modal dialog is too constrained for the amount of content.

**Decision:**
Detail Unit is a slide-over panel (right drawer) that overlays the workspace. It uses Filament `Action::slideOver` mounted from a Livewire component. The slide-over is pure read-only: Infolist + custom blade partials. All actions are deep-link buttons.

**Consequences:**
- Positive: User maintains workspace context (table still visible underneath).
- Positive: Slide-over can hold rich content without page navigation.
- Positive: Filament's slide-over infrastructure handles animation, focus trapping, and responsive behavior.
- Negative: Slide-over state is lost on browser back button. Mitigated by URL state for workspace filter — returning to workspace restores filter, but slide-over is closed.
- Negative: Mobile screens may be too narrow for slide-over. Filament handles this by making slide-over full-width on mobile.

**Rejected Alternatives:**
1. *Hybrid page (slide-over for summary, full page for detail)* — Adds complexity with two presentation modes. Reserving for v2 if needed.
2. *In-page expansion (row expands to show detail below)* — Limits content area; no vertical space for timeline + inspection + lead time.
3. *Filament ViewShipment page* — This page exists but is designed for FC context, not monitoring context. Different data shape, different actions.

---

### ADR-006 — SPPB as Operational Aggregate

**Status:** ACCEPTED (Sprint 0, implicit in model design)

**Context:**
The business operates on SPPB (shipment) level for tracking, voyage assignment, and KPI measurement. However, the physical reality is units (vehicles). A single SPPB may contain 1–20+ units. KPI is measured per shipment but reported per unit (unit-weighted). Inspections are per unit. Container assignment is per unit. The monitoring workspace must present data at the right granularity without confusing the user.

**Decision:**
SPPB (Shipment) is the operational aggregate root for tracking, stage, age, voyage, and KPI. Unit is the physical entity for identity, inspection, and container assignment. The MonitoringRowData carries both: shipment-level fields (stage, progress, age, exceptions, voyage) and unit-level fields (reg_no, model_no, container_display). Group modes allow the user to shift perspective between unit-level and SPPB-level.

**Consequences:**
- Positive: Matches the mental model of both Office Admin (thinks SPPB) and field operations (thinks individual units).
- Positive: KPI calculations can be unit-weighted (existing dashboard pattern) without changing the query structure.
- Negative: Some fields are null in group mode (unit_reg_no is null in sppb/voyage group). Blade must handle nulls gracefully.

**Rejected Alternatives:**
1. *Pure shipment-level table* — Hides per-unit inspection NG information. Cannot show which specific unit has issues.
2. *Pure unit-level table without shipment grouping* — Loses SPPB context. Cannot show voyage or ETA (shipment-level).

---

### ADR-007 — ViewModel Pattern (Readonly DTO)

**Status:** ACCEPTED (Sprint 5)

**Context:**
Blade templates historically mix HTML rendering with Eloquent calls, accessor chains, and conditional logic. This leads to untestable views, N+1 queries triggered during rendering, and business rules leaking into templates. The monitoring workspace has 15 distinct data shapes (row, detail, timeline, inspection, lead time, etc.) each consumed by different Blade partials.

**Decision:**
Every data shape passed to Blade is an immutable, readonly PHP class (using constructor property promotion with `public readonly`). These View Models are constructed by the Application/Service layer and consumed by Blade. View Models contain no methods except `__construct()` and optionally `toArray()`. They never perform queries or lazy-load relations.

**Consequences:**
- Positive: Blade becomes a pure template engine — it renders properties, nothing else.
- Positive: View Models are trivially testable (construct with test data, assert properties).
- Positive: N+1 prevention is guaranteed — if a relation isn't in the View Model, Blade can't access it.
- Positive: Type safety — IDE knows the shape of data in every Blade partial.
- Negative: More classes to write (15 View Models).
- Negative: Data transformation happens in a separate layer from rendering — slight indirection.

**Rejected Alternatives:**
1. *Pass Eloquent models directly to Blade* — Encourages lazy loading, accessor chains, and business logic in templates. The existing dashboard partially does this and it's already problematic.
2. *Use Laravel API Resources as View Models* — API Resources are designed for JSON serialization, not Blade consumption. They lack type safety and encourage accessors.

---

### ADR-008 — Domain Service Separation

**Status:** ACCEPTED (Sprint 5)

**Context:**
The monitoring workspace needs to resolve stages, calculate progress, build timelines, evaluate exceptions, summarize inspections, and compute lead times. If these are all in one service, it becomes a God Service. If they're all in the Livewire component, business logic leaks into the presentation layer. If they're Eloquent model methods, models become fat and trigger N+1 when called in loops.

**Decision:**
Each domain concern is a separate, stateless service class: StageResolver, ProgressCalculator, TimelineBuilder, ExceptionEvaluator, InspectionSummaryBuilder, LeadTimeBuilder, AgeCalculator. They receive pre-loaded Model data and return View Models. They never query the database. DetailUnitProvider is the single orchestrator that may call multiple services; all others are single-responsibility.

**Consequences:**
- Positive: Each service is independently testable with mock data.
- Positive: Services are stateless — safe to resolve from container, no lifecycle concerns.
- Positive: Clear dependency graph; easy to reason about what calls what.
- Negative: More files and indirection than a monolithic service.
- Negative: DetailUnitProvider must wire together 6 services; if the wiring is wrong, the bug is in the orchestrator, not the individual services.

**Rejected Alternatives:**
1. *Single MonitoringService that does everything* — God Service anti-pattern. Untestable, hard to modify.
2. *Eloquent model accessor methods* — Triggers N+1 in loops. Cannot be cached or mocked.
3. *Traits on Shipment model* — Same N+1 problem. Also mixes read logic with write model.

---

## 18. Layer Dependency Rules

### 18.1 Layer Hierarchy

```
Presentation Layer  (Filament Page, Livewire, Blade)
       ↓
Application Layer   (MonitoringQueryService, DetailUnitProvider, WorkspaceSummaryBuilder)
       ↓
Domain Service Layer (StageResolver, TimelineBuilder, ExceptionEvaluator, etc.)
       ↓
Query Layer         (UnitMonitoringQueryBuilder, ExceptionCountQueryBuilder, etc.)
       ↓
Infrastructure      (Eloquent Models, DB, Config, Cache)
```

**Rule:** Dependencies flow downward only. No layer may depend on a layer above it. No layer may skip a layer (e.g., Presentation may not call Query Layer directly — it must go through Application Layer).

### 18.2 Explicit Dependency Rules

| Rule | Enforcement |
|------|-------------|
| Blade tidak boleh melakukan business logic | Blade only reads View Model properties. No `if ($model->status === ...)` with business meaning. Display conditionals (if field is null, show "—") are allowed. |
| Blade tidak boleh menjalankan query | No `Model::find()`, `Model::where()`, `$model->relation` (lazy load) in Blade. All data must be in the View Model. |
| Blade tidak bolemen memanggil Service | No `app(Service::class)` or `Service::method()` in Blade. Services are called by Application Layer. |
| Livewire hanya mengelola state dan interaction | Livewire methods only: set state, validate input, call Application Service, dispatch events. No SQL, no business rules. |
| ViewModel tidak boleh mengetahui Eloquent | View Models are `readonly class` with primitive/enum properties. No Eloquent Model properties, no Builder properties. |
| ViewModel tidak boleh melakukan query | No `Model::query()`, no `$this->relation` in View Model. |
| Domain Service tidak boleh mengetahui Filament | No `Filament::`, no `Page`, no `Resource`, no `Notification` in Domain Service. |
| Domain Service tidak boleh mengetahui Blade | No `view()`, no `Blade::`, no rendering in Domain Service. |
| Domain Service tidak boleh mengetahui Livewire | No `Component`, no `wire:`, no Livewire events in Domain Service. |
| Query Layer tidak boleh menghasilkan HTML | No `view()`, no string interpolation of HTML. Returns Eloquent Builder or Collection. |
| Query Layer tidak boleh mengetahui View Model | Query Builder returns Models/Builders, not View Models. Transformation happens in Application Layer. |
| Application Layer tidak boleh mengetahui Blade | No `view()` in Application Service. It returns View Models. |
| Infrastructure tidak boleh dipanggil langsung dari Presentation | No `Shipment::query()` in Livewire or Page. Must go through Query Builder → Application Service. |

### 18.3 Dependency Matrix

| ↓ May depend on → | Presentation | Application | Domain Service | Query Layer | Infrastructure | View Model |
|-------------------|:----------:|:------------:|:--------------:|:----------:|:------------:|:----------:|
| **Presentation** | — | **YES** | NO | NO | NO | **YES** (consumes) |
| **Application** | NO | — | **YES** | **YES** | NO (via Query Layer) | **YES** (constructs) |
| **Domain Service** | NO | NO | — | NO | **YES** (read-only: Enums, Config, Model attributes) | **YES** (constructs) |
| **Query Layer** | NO | NO | NO | — | **YES** (Eloquent, DB) | NO |
| **Infrastructure** | NO | NO | NO | NO | — | NO |
| **View Model** | NO | NO | NO | NO | NO | — |

**Reading the matrix:** Row `A`, Column `B` = "A may depend on B".
- **YES** = dependency allowed
- **NO** = dependency forbidden (architecture violation)
- `—` = same layer (irrelevant)

### 18.4 Cross-Layer Communication Rules

1. **Presentation → Application:** Via method call on resolved service instance (`app(MonitoringQueryService::class)->paginate($filter)`) or constructor injection.

2. **Application → Domain Service:** Via method call. Application constructs View Models from Service output.

3. **Application → Query Layer:** Via method call on Query Builder. Receives Eloquent Builder or Model.

4. **Domain Service → Infrastructure:** Domain Service may read Model attributes, Enums, and Config values. It may NOT call `Model::query()`, `DB::`, or `Cache::`. If data is needed, it must be pre-loaded by the Application Layer and passed in.

5. **Query Layer → Infrastructure:** Query Builder uses Eloquent, DB facade, and schema. It may use `Cache::remember()` for caching aggregate results.

### 18.5 Anti-Patterns to Reject in Code Review

| Anti-Pattern | Example | Why Rejected | Fix |
|---------------|---------|-------------|-----|
| Lazy load in Blade | `$shipment->voyageRecord->vessel->name` in Blade | Triggers N+1 | Pre-load in Query Builder; expose in View Model |
| Service does query | `StageResolver::resolve()` calls `Shipment::with('tracks')->find($id)` | Violates Domain/Query separation | Move query to Query Builder; service receives pre-loaded Model |
| Livewire does business logic | `if ($track->status === TrackStatus::Hold) $this->alert()` in Livewire | Presentation layer contains business rules | Call `ExceptionEvaluator`; render returned `ExceptionChipData[]` |
| View Model has methods | `MonitoringRowData::isUrgent()` | View Model should be pure data | Move logic to Service; compute before constructing View Model |
| Query Builder returns ViewModel | `QueryBuilder::build()` returns `MonitoringRowData` | Query Layer must not know View Model | Return Builder/Model; Application Layer transforms |
| Blade calls auth() | `@if(auth()->user()->isOfficeAdmin())` | Presentation reads auth directly | Pass auth context via View Model or Livewire state |

---

## 19. Stable Domain Boundary

### 19.1 Core Domain vs Delivery Layer

```
┌─────────────────────────────────────────────────────┐
│                   Delivery Layer                        │
│  ┌───────────┐  ┌──────────┐  ┌──────┐  ┌──────────┐  │
│  │ Filament  │  │ Livewire │  │ Blade│  │ViewModel │  │
│  │ Page      │  │ Component│  │      │  │ DTO      │  │
│  └───────────┘  └──────────┘  └──────┘  └──────────┘  │
│  ┌───────────┐  ┌──────────┐                          │
│  │ REST API  │  │ Customer │  (future delivery)      │
│  │ Controller│  │ Portal   │                          │
│  └───────────┘  └──────────┘                          │
└───────────────────────┬─────────────────────────────────┘
                        │  Application Services
┌───────────────────────▼─────────────────────────────────┐
│                  Application Layer                       │
│  MonitoringQueryService · DetailUnitProvider             │
│  WorkspaceSummaryBuilder                                │
└───────────────────────┬─────────────────────────────────┘
                        │  Domain Services (pure functions)
┌───────────────────────▼─────────────────────────────────┐
│                    Domain Layer                           │
│  ┌────────────┐ ┌──────────┐ ┌─────────────┐            │
│  │StageResolver│ │Timeline  │ │Exception    │            │
│  │             │ │Builder   │ │Evaluator    │            │
│  └────────────┘ └──────────┘ └─────────────┘            │
│  ┌────────────┐ ┌──────────┐ ┌─────────────┐            │
│  │Progress    │ │Inspection│ │LeadTime     │            │
│  │Calculator  │ │Summary   │ │Builder      │            │
│  └────────────┘ └──────────┘ └─────────────┘            │
│  ┌────────────┐                                           │
│  │Age         │                                           │
│  │Calculator  │                                           │
│  └────────────┘                                           │
└───────────────────────┬─────────────────────────────────┘
                        │  Query Builders
┌───────────────────────▼─────────────────────────────────┐
│                   Query Layer                             │
│  UnitMonitoringQueryBuilder · ExceptionCountQueryBuilder │
│  ShipmentDetailQueryBuilder · WorkspaceSummaryQueryBuilder│
└───────────────────────┬─────────────────────────────────┘
                        │
┌───────────────────────▼─────────────────────────────────┐
│              Core Domain (Infrastructure)                 │
│  Shipment · Unit · ShipmentTrack · UnitInspection        │
│  Voyage · Customer · Branch                              │
│  TrackStatus · ShipmentStatus · ShipmentMode             │
│  ShipmentKpiEvaluator (existing)                        │
│  config/jss_kpi.php                                      │
└──────────────────────────────────────────────────────────┘
```

### 19.2 Core Domain Definition

The **Core Domain** comprises:
- **Eloquent Models:** `Shipment`, `Unit`, `ShipmentTrack`, `UnitInspection`, `UnitInspectionItem`, `Voyage`, `Customer`, `Branch`, `Office`, `Port`, `Depot`, `Driver`, `Vessel`, `ShippingSchedule`

- **Enums:** `TrackStatus`, `ShipmentStatus`, `ShipmentMode`, `CargoType`, `ServiceType`, `RequestType`, `DeliveryScope`

- **Existing Services:** `ShipmentKpiEvaluator`, `MpCheckGate`, `LoadingSessionAutoCreate`, `InspectionGateEvaluator`, `DepotResolver`

- **Config:** `config/jss_kpi.php`

### 19.3 Delivery Layer Definition

The **Delivery Layer** comprises:
- **Filament Page:** `MonitoringWorkspace`
- **Livewire Components:** `MonitoringTable`, `MonitoringDetailSlide`
- **Blade Templates:** all blade files in `resources/views/filament/pages/monitoring-workspace.blade.php` and `resources/views/livewire/monitoring/*.blade.php`

- **View Models:** all readonly classes in `App\ViewModels\Monitoring\`
- **DTO:** `MonitoringFilter`

- **Filament-specific:** `AdminPanelProvider` registration, `Action::slideOver`

### 19.4 The Golden Rule

> **Core Domain tidak boleh mengetahui Delivery Layer.**

`Shipment` model must not import `Filament`, `Livewire`, `Blade`, or any `App\ViewModels\*` class. `TrackStatus` enum must not reference any Filament color or icon (it already has `color()` and `icon()` methods, but these return string constants, not Filament objects — this is acceptable as long as the strings are framework-agnostic like 'info', 'warning', 'heroicon-o-map-pin').

Domain Services must not import `Filament\Facades\Filament`, `Livewire\Component`, or `Illuminate\Support\Facades\Blade`.

### 19.5 Why This Boundary Matters

| Future Need | Why Core Domain Must Be Delivery-Agnostic |
|-------------|-------------------------------------------|
| **REST API** | API controllers are a new Delivery Layer. They call the same Application Services with a different `MonitoringFilter` (from HTTP request instead of Livewire state). Core Domain does not change. |
| **Customer Portal** | Customer portal is a new Filament Panel with different auth and scoping (`customer_id` instead of `branch_id`). All Services, Query Builders, and View Models are reused. Only the Delivery Layer changes. |
| **Mobile App** | Mobile app calls REST API. API controllers use the same Application Services. Core Domain is untouched. |
| **Future Dashboard** | A new dashboard (e.g., BI dashboard) can call the same Query Builders for different View Models. Domain Services are reusable as-is. |
| **Background Jobs** | A scheduled job that generates exception reports can call `ExceptionEvaluator` and `ExceptionCountQueryBuilder` directly. No Filament/Livewire dependency needed. |
| **Console Commands** | `php artisan monitoring:stuck-report` can call `MonitoringQueryService` with a CLI-constructed `MonitoringFilter`. No HTTP context required. |
| **Event-Driven Architecture** | If `ShipmentTrack` model events broadcast to queue, a consumer can call Domain Services to evaluate exceptions and dispatch notifications. No Filament dependency. |

### 19.6 Current Codebase Audit

The existing `Shipment` model has `Filament::auth()` calls in `ShipmentTrack::booted()` (`app/Models/ShipmentTrack.php:169`). This is a pre-existing Core Domain → Delivery Layer coupling. **It is out of scope for this module** but should be flagged as tech debt: `ShipmentTrack` should use `auth()->id()` (framework-agnostic) instead of `Filament::auth()?->id()`.

The new monitoring module must NOT introduce any new Core Domain → Delivery Layer coupling. All new Services, Query Builders, and View Models must be delivery-agnostic.

---

## 20. Future Extension Strategy

### 20.1 Reusability Assessment by Future Target

#### Target: Monitoring Customer (Customer Portal)

| Layer | Reusable? | Notes |
|-------|-----------|-------|
| Canvas: Main Page | New | New Filament Page for customer panel |
| Toolbar: Filter Form | Modified | Replace branch filter with customer_id scope; remove Super Admin controls |
| Table: MonitoringTable | New | New Livewire component for customer panel (different auth context) |
| Application: MonitoringQueryService | **Reusable** | Accepts `MonitoringFilter` — just pass `customer_id` scope instead of `branch_id` |
| Application: DetailUnitProvider | **Reusable** | Branch scope check replaced with customer scope check |
| Application: WorkspaceSummaryBuilder | **Reusable** | |
| Domain Service: All 8 services | **Reusable** | All stateless, no delivery dependency |
| Query Layer: All 4 builders | Modified | Add `customer_id` scope to WHERE clause |

**What to build new:** Customer Panel Page, Customer Table Component, Customer-scoped Policy
**What to reuse:** ALL services, ALL query builders (with minor scope modification), ALL view models

**Most stable layer:** Domain Services (100% reusable)
**Layer that changes:** Delivery (new panel, new components)

#### Target: Monitoring Truck (Land Mode Enhancement)

| Layer | Reusable? | Notes |
|-------|-----------|-------|
| Canvas: Main Page | Modified | Add "Truck" tab/filter in toolbar |
| Toolbar | Modified | Add truck-specific filter options |
| Table: MonitoringTable | Modified | Display truck-specific columns (vehicle_plate, driver) |
| Application: MonitoringQueryService | **Reusable** | `mode` filter already supports 'land' |
| Domain Service: StageResolver | **Reusable** | `TrackStatus::orderForMode('land')` already returns 3-stage timeline |
| Domain Service: TimelineBuilder | **Reusable** | Already handles land mode (3 stages) |
| Domain Service: ExceptionEvaluator | **Reusable** | Land-mode exceptions (missing voyage not applicable, but delay/hold/NG/PDI are) |
| Domain Service: AgeCalculator | **Reusable** | |
| Query Layer: UnitMonitoringQueryBuilder | **Reusable** | Already filters by mode |

**What to build new:** Truck-specific display columns in View Model, truck-specific deep links
**What to reuse:** ALL services, ALL query builders (land mode already supported), most View Models

**Most stable layer:** ALL Domain Services + Query Layer
**Layer that changes:** Presentation (display columns, deep links)

#### Target: Monitoring Container

| Layer | Reusable? | Notes |
|-------|-----------|-------|
| Application: MonitoringQueryService | Extended | New `ContainerMonitoringQueryBuilder` for container-level query |
| Domain Service: ExceptionEvaluator | Extended | New exception types (e.g., "Container Damage", "Seal Broken") |
| Domain Service: TimelineBuilder | **Reusable** | Container stages are subset of shipment stages |
| Domain Service: InspectionSummaryBuilder | **Reusable** | Already handles unit-level inspection with container_display |
| Query Layer | Extended | New query builder for container-centric view |
| View Model | Extended | New `ContainerRowData` (shipped within container, has units) |

**What to build new:** Container MonitoringQueryBuilder, ContainerRowData, container-specific View Model fields
**What to reuse:** Timeline, Inspection, Progress, Stage services (80% reusable)
**Most stable layer:** Domain Services (exception evaluator minimal extension)
**Layer that changes:** Query Layer + View Models (new container-centric shape)

#### Target: Monitoring Vessel (Voyage-Level Monitoring)

| Layer | Reusable? | Notes |
|-------|-----------|-------|
| Application | **Reusable pattern** | Same orchestrator pattern (`VoyageMonitoringQueryService`) |
| Domain Service: TimelineBuilder | NOT reusable | Voyage timeline uses milestones (D2, D4, D6...) not track stages |
| Domain Service: ExceptionEvaluator | Extended | Already has voyage.is_delayed check; needs VesselCheck delay, readiness issues |
| Domain Service: StageResolver | NOT reusable | Voyage has `operational_status_enum` not track-based stages |
| Existing | **Reusable** | `VoyageOperationalState` service already exists and follows the same pattern |
| Query Layer | Extended | New `VoyageMonitoringQueryBuilder` (based on existing `MonitoringKapalTam` query) |
| View Model | Extended | New `VoyageRowData`, `VoyageTimeline` (milestone-based) |

**What to build new:** Voyage-specific Timeline (milestone-based), Voyage-specific StageResolver (status-based), VoyageRowData
**What to reuse:** ExceptionEvaluator pattern (voyage checks exist), existing `VoyageOperationalState`
**Most stable layer:** Query Builder pattern (generalized)
**Layer that changes:** Timeline + Stage services are replaced with voyage-specific equivalents

#### Target: REST API

| Layer | Reusable? | Notes |
|-------|-----------|-------|
| Delivery | New | API controllers, request validation, JSON response formatting |
| Application Services | **100% Reusable** | Stateless, accept DTO, return View Model |
| Domain Services | **100% Reusable** | |
| Query Builders | **100% Reusable** | |
| View Models | **Reusable** | Add `toArray()` / `JsonSerializable` for JSON response |
| DTO | **Reusable** | `MonitoringFilter` can be constructed from API request |

**What to build new:** API Routes, Controllers, Request classes, OpenAPI spec, API-specific auth
**What to reuse:** EVERYTHING below Delivery Layer (100% of business logic)
**Most stable layer:** ALL services, ALL query builders, ALL domain logic
**Layer that changes:** Delivery only (new controllers replace Filament Page)

#### Target: Mobile Application

| Layer | Reusable? | Notes |
|-------|-----------|-------|
| Delivery | New | API endpoints (same as REST API target) |
| Everything else | **100% Reusable** | Mobile app calls REST API; all business logic is server-side |

**Most stable layer:** Everything below API Delivery
**Layer that changes:** None on server side (if REST API exists)

#### Target: Background Jobs / Console Commands

| Layer | Reusable? | Notes |
|-------|-----------|-------|
| Delivery | New | `Illuminate\Console\Command` or `Illuminate\Bus\Job` |
| Application Services | **100% Reusable** | |
| Domain Services | **100% Reusable** | |
| Query Builders | **100% Reusable** | |

### 20.2 Reuse Stability Ranking

From most stable (least likely to change) to least stable:

1. **Enums** — zero change risk (domain constants)
2. **Domain Services** — pure functions, framework-independent
3. **Query Builders** — only schema changes affect them
4. **View Models** — only UI requirement changes affect them
5. **Application Services** — orchestrators; change when new data shapes are needed
6. **Livewire Components** — presentation; change with UX
7. **Blade Templates** — presentation; change with UI
8. **Filament Page** — presentation; change with Filament version

### 20.3 Framework-Independent Zone

The following are **completely independent of Filament, Livewire, and Blade**:

- All Enums (`TrackStatus`, `ShipmentStatus`, `ShipmentMode`)
- All Domain Services (`StageResolver` through `AgeCalculator`)
- All Query Builders (`UnitMonitoringQueryBuilder` through `WorkspaceSummaryQueryBuilder`)
- All View Models (readonly classes with no framework imports)
- `MonitoringFilter` DTO
- `config/jss_kpi.php` monitoring section

If Filament is replaced or removed, these components remain valid and usable.

---

## 21. God Service Prevention

### 21.1 Risk Assessment

| Service | God Service Risk | Why | Mitigation |
|---------|-----------------|-----|-----------|
| `MonitoringQueryService` | **Medium** | Currently orchestrates query + 4 sub-services (Stage, Age, Progress, Exception) per row. If rows get more complex, this grows. | Cap at pagination + transformation only. No business rule evaluation inside. |
| `DetailUnitProvider` | **Medium-High** | Orchestrates 6 sub-services. It's the only multi-service orchestrator by design. | Acceptable because it's a pure orchestrator (no logic, just wiring). If it starts containing logic, extract to a new service. |
| `TimelineBuilder` | **Low** | Single concern (ordered stages from tracks). | If timeline needs per-unit variant (e.g., individual pickup time), split into `ShipmentTimelineBuilder` and `UnitTimelineBuilder`. |
| `ExceptionEvaluator` | **Low-Medium** | Currently 6 exception types. Could grow if new types are added. | If exceeds 10 exception types or 200 LOC, extract per-type evaluators and use strategy pattern. |

### 21.2 Prevention Rules

#### Rule 1: Single Tanggung Jawab Utama

Setiap service memiliki **satu tanggung jawab utama** yang dapat dijelaskan dalam satu kalimat:

| Service | Responsibility (one sentence) |
|---------|------------------------------|
| StageResolver | Determine the current and next track stage for a shipment. |
| ProgressCalculator | Calculate a 0–100 progress percentage from stage and mode. |
| TimelineBuilder | Build an ordered timeline of stages from track records. |
| ExceptionEvaluator | Evaluate which of 6 exception types apply to a shipment. |
| InspectionSummaryBuilder | Summarize inspection status for one unit. |
| LeadTimeBuilder | Build Manado KPI lead time summary from milestone times. |
| AgeCalculator | Calculate age in days from last activity with fallback. |
| MonitoringQueryService | Transform a filter into a paginated collection of row data. |
| DetailUnitProvider | Assemble all data needed for the slide-over detail view. |
| WorkspaceSummaryBuilder | Compute aggregate summary numbers for the workspace header. |

If a service's responsibility cannot be described in one sentence, it should be split.

#### Rule 2: Domain Concern Boundary

Jika service mulai menangani lebih dari satu domain concern, pecah menjadi service baru.

**Example of violation:**
If `ExceptionEvaluator` starts calculating KPI status alongside exception evaluation, it has crossed into the LeadTime domain. Split: `ExceptionEvaluator` handles exceptions only; KPI status is in `LeadTimeBuilder`.

**Example of acceptable orchestration:**
`DetailUnitProvider` calls 6 services. It does NOT implement any of their logic. It only wires them together. This is orchestration, not multi-concern.

#### Rule 3: LOC Limit

Hindari service > 300 LOC kecuali memang justified.

| LOC Range | Status | Action |
|-----------|--------|--------|
| 0–100 | Ideal | None |
| 100–200 | Acceptable | Review annually |
| 200–300 | Borderline | Justify in ADR or refactor |
| 300+ | **Red flag** | Must refactor or justify with ADR |

#### Rule 4: Constructor Dependency Limit

Hindari constructor dependency yang berlebihan.

| Dependency Count | Status | Action |
|-----------------|--------|--------|
| 0–2 | Ideal | None |
| 3–4 | Acceptable | Review coupling |
| 5+ | **Red flag** | Likely God Service — refactor |

**Exception:** `DetailUnitProvider` has 6 dependencies by design (it's the orchestrator). This is the ONLY service allowed to exceed 4 dependencies. If any other service reaches 5, it must be refactored.

#### Rule 5: ViewModel Builder Mengetahui Business Rule

ViewModel builders (the Application Services that construct View Models) must NOT contain business rules. They:
1. Call a Query Builder to get data
2. Call Domain Services to evaluate business rules
3. Assemble the View Model from the results

If an Application Service starts containing `if` statements that represent business decisions (not null checks or assembly logic), those decisions belong in a Domain Service.

**Example of violation:**
```php
// In MonitoringQueryService (WRONG):
if ($shipment->latestTrack?->status === TrackStatus::Hold) {
    $row->is_stuck = true;  // ← business rule in Application Layer
}
```

**Correct:**
```php
// In MonitoringQueryService (RIGHT):
$ageData = $this->ageCalculator->calculate(...);  // contains is_stuck logic
$row = new MonitoringRowData(..., age: $ageData, ...);
```

### 21.3 Refactoring Triggers

When should a service be split?

| Trigger | Threshold | Action |
|---------|-----------|--------|
| Service handles > 1 domain concern | Immediate | Extract second concern to new service |
| Service > 300 LOC | On next sprint | Refactor or ADR-justify |
| Service > 5 constructor dependencies | Immediate (except DetailUnitProvider) | Extract orchestrator or reduce coupling |
| Service has `if/switch` with > 5 branches | On next sprint | Strategy pattern or lookup table |
| Service method > 50 LOC | On next sprint | Extract method or move to separate class |
| Two services need the same data transformation | Immediate | Extract to shared helper or value object |

### 21.4 Orchestrator Pattern

When a service must coordinate multiple services (like `DetailUnitProvider`), use the **Orchestrator Pattern**:

```
Orchestrator
├── calls Service A → gets Result A
├── calls Service B → gets Result B
├── calls Service C → gets Result C
└── assembles FinalResult from A + B + C
```

**Rules for orchestrators:**
1. Orchestrator must NOT contain business logic — only wiring
2. Orchestrator must NOT have `if` branches that represent decisions — only null checks
3. Orchestrator returns a single composite View Model
4. There should be at most ONE orchestrator per use-case

**Currently designated orchestrators:**
- `DetailUnitProvider` (for slide-over) — orchestrates 6 services
- `MonitoringQueryService` (for table) — orchestrates 4 services per row

If a new use-case needs multi-service coordination, create a new orchestrator — do not expand an existing service.

---

## 22. Architecture Evolution

This section evaluates how the architecture responds to future scale and complexity changes.

### 22.1 Evolution Scenarios

#### Scenario: Route Bertambah Menjadi Puluhan

| Layer | Impact | Action |
|-------|--------|--------|
| Enums | No change | |
| Domain Services | No change | |
| Query Builders | **Minor extension** | Route filtering maps route_code → customer_id filter. Current `RouteResolver` handles this. Add new mappings. |
| View Models | No change | |
| Performance | **No change** | Route filter is a WHERE clause; no additional query needed |

**Verdict: Extension only. No redesign.**

#### Scenario: Shipment Meningkat 10x

| Layer | Impact | Action |
|-------|--------|--------|
| Query Architecture | **May need optimization** | At 10x scale: |
| | | - Cursor pagination instead of `LENGTH-aware paginator` (if count query becomes too slow) |
| | | - Materialized view for exception counts (if aggregate query > 200ms) |
| | | - Read replica for monitoring queries (separate from write path) |
| Caching | **Extend TTL** | If 30s cache causes too many misses at scale, increase to 60s or 120s |
| Polling | **Extend interval** | If 60s poll causes too many concurrent cache misses, increase to 120s |
| Memory | **No change** | Pagination stays at 50 rows |
| Domain Services | **No change** | They operate per-row, scale linearly |

**Verdict: Extension (config tuning + optional materialized view). No redesign.**

#### Scenario: Customer Portal Aktif

| Layer | Impact | Action |
|-------|--------|--------|
| Application Services | **No change** | Already accept `MonitoringFilter` with `customer_id` scope |
| Domain Services | **No change** | |
| Query Builders | **Minor change** | Add `WHERE customer_id = ?` scope (already pattern exists) |
| Delivery Layer | **New** | New Filament Panel, new Page, new Livewire components |
| View Models | **No change** | Same `MonitoringRowData`, `UnitDetailData` shapes |
| Authorization | **New Policy** | `CustomerMonitoringPolicy` — scope to `user->customer_id` |
| Polling | **No change** | Same cache key strategy with customer_id in hash |

**Verdict: Extension (new Delivery Layer + Policy). Core domain unchanged.**

#### Scenario: Mobile App Dibuat

| Layer | Impact | Action |
|-------|--------|--------|
| Everything below Delivery | **No change** | |
| REST API | **New** | API controllers call same Application Services |
| View Models | **Minor extension** | Implement `JsonSerializable` or add `toArray()` for JSON response |
| Auth | **New** | Sanctum token auth for API routes |
| Polling | **Adapted** | Mobile handles polling client-side; server provides REST endpoint |

**Verdict: Extension (REST API Delivery Layer). Core domain + Services unchanged.**

#### Scenario: Event-Driven Architecture Diperlukan

| Layer | Impact | Action |
|-------|--------|--------|
| Core Domain Models | **Extend** | `ShipmentTrack::saved` event already fires. Add event broadcasting. |
| Domain Services | **No change** | Services can be called from event listeners |
| Application Services | **No change** | |
| New: Event Listeners | **New** | `OnTrackSaved` listener calls `ExceptionEvaluator` and dispatches notification |
| Polling | **Replaced** | WebSocket/SSE push replaces `wire:poll` |
| Delivery | **Minor change** | Replace Alpine.js poll listener with WebSocket listener |

**Verdict: Extension (add event broadcasting + listener). Polling replaced but services unchanged.**

#### Scenario: Background Processing Bertambah

| Layer | Impact | Action |
|-------|--------|--------|
| Application Services | **No change** | Called from Job/Command with constructed `MonitoringFilter` |
| Domain Services | **No change** | |
| Query Builders | **No change** | |
| New: Jobs/Commands | **New** | `GenerateStuckReport`, `SendExceptionAlert`, `ArchiveFinishedShipments` |
| New: Scheduler | **New** | `app/Console/Kernel.php` schedule entries |

**Verdict: Extension (new Jobs/Commands). Existing architecture fully supports this.**

#### Scenario: Dashboard BI Membutuhkan Data yang Sama

| Layer | Impact | Action |
|-------|--------|--------|
| Query Builders | **Reusable** | Same queries for BI (may add pre-aggregation) |
| Domain Services | **Reusable** | |
| View Models | **New shapes** | BI may need different aggregation level (e.g., monthly trend, not per-row) |
| New: BI Query Builders | **New** | If BI needs OLAP-style queries not supported by current builders |
| Caching | **Extend** | BI queries can use longer TTL (5min, 15min) since they're less real-time |

**Verdict: Extension (new BI-specific Query Builders + View Models). Existing services reusable.**

### 22.2 Evolution Summary Matrix

| Scenario | Core Domain | Domain Services | Query Layer | Application | Delivery | Action |
|----------|:-----------:|:---------------:|:-----------:|:-----------:|:--------:|--------|
| Puluhan route | — | — | Extension | — | Extension | Add mappings |
| 10x shipment | — | — | Extend (mat. view) | — | Extend (config) | Optimize queries |
| Customer Portal | — | — | Extension (scope) | — | **New** | New panel |
| Mobile App | — | — | — | — | **New** (API) | New API |
| Event-driven | Extension (broadcast) | — | — | — | Extension | Add listeners |
| Background jobs | — | — | — | — | **New** (CLI) | New Jobs |
| BI Dashboard | — | — | Extension | — | **New** | New BI builder |

**Key insight:** In **no scenario** does the Core Domain or Domain Services need redesign. The most common extension point is the Delivery Layer (new panels, APIs, commands) and Query Layer (new scopes, optimizations).

### 22.3 When Redesign Would Be Needed

| Redesign Trigger | Probability | What Would Break |
|-------------------|------------|-----------------|
| D1 (read-only workspace) reversed | Very low | Entire architecture — would need write models, validation, conflict handling |
| Hybrid row replaced with shipment-only or unit-only | Low | MonitoringRowData, Table component, group modes |
| Slide-over replaced with full page | Low | DetailUnitProvider, slide-over components, URL state |
| More than 6 exception types with complex interdependencies | Medium | ExceptionEvaluator may need strategy pattern refactor |
| Real-time sub-second updates required | Low | Polling architecture → WebSocket push. Services still same. Delivery changes. |
| Multi-tenant architecture (data isolation per customer) | Low | Query Builders add tenant scope. Services unchanged. Delivery may need tenant selection. |

---

## 23. Engineering Governance

### 23.1 Code Review Rules

#### Mandatory Checks Before Merge

| Check | Rejected If... | Severity |
|-------|----------------|----------|
| **Layer separation** | Blade contains Eloquent calls, Service imports Filament, QueryBuilder returns ViewModel | **Block merge** |
| **View Model immutability** | View Model has public setter, non-readonly property, or method with side effects | **Block merge** |
| **N+1 query risk** | New relation access without eager loading in a loop context | **Block merge** |
| **Business logic in Livewire** | Livewire method contains `if` with business meaning (not null check or state assignment) | **Block merge** |
| **Service > 300 LOC** | Service exceeds 300 LOC without ADR justification | **Request refactor** |
| **Service > 5 dependencies** | Service has > 5 constructor dependencies (except DetailUnitProvider) | **Request refactor** |
| **Missing type hint** | Service method missing parameter type or return type | **Request fix** |
| **Config hard coding** | Threshold, interval, page size hard-coded instead of `config()` | **Request fix** |
| **Missing ADR for new decision** | PR introduces significant architectural change without ADR | **Request ADR** |
| **Test coverage** | New service without unit test | **Request test** |

#### Architecture Violation Rejection Checklist

Reviewer must verify:
- [ ] No `Model::query()` or `Model::find()` in Blade
- [ ] No `Model::query()` in Livewire component
- [ ] No `view()` or `Blade::` in Domain Service
- [ ] No `Filament::` in Domain Service
- [ ] No `Livewire::` or `Component::` in Domain Service
- [ ] No `App\ViewModels\*` import in Query Builder
- [ ] No `App\ViewModels\*` import in Domain Service (Services construct View Models but don't import them — they use `new` directly within the same module, which is acceptable)

**Exception:** Domain Services DO construct View Models (e.g., `StageResolver` returns `CurrentStageData`). This is allowed because the Service is in `App\Services\Monitoring` and the View Model is in `App\ViewModels\Monitoring` — same module, different layer. The import is within-module, not cross-layer.

### 23.2 Refactoring Triggers

#### When to Split a Service

| Trigger | Example | Action |
|---------|---------|--------|
| Service handles 2+ domain concerns | `ExceptionEvaluator` starts computing KPI | Extract KPI logic to existing `LeadTimeBuilder` or new service |
| Service > 300 LOC | Any service growing beyond 300 lines | Split by responsibility (ADR-justify if not) |
| Service > 5 dependencies | Any service (except DetailUnitProvider) | Extract orchestration or reduce coupling |
| Method > 50 LOC | Any method in any service | Extract method or move logic to dedicated class |
| 2 services share identical helper logic | `StageResolver` and `ProgressCalculator` both compute `orderForMode` | Extract to shared helper or Enum method |
| Switch/if with > 5 branches | `ExceptionEvaluator` with 10+ exception types | Strategy pattern: `ExceptionTypeEvaluator` per type |

#### When to Move a Query

| Trigger | Action |
|---------|--------|
| Query logic appears in Livewire | Move to Query Builder |
| Query logic appears in Domain Service | Move to Query Builder — service should receive pre-loaded data |
| Query logic appears in Blade | **Immediate** move to Query Builder — this is a critical violation |
| Same WHERE clause in 2+ services | Extract to shared scope or Query Builder |
| Query needs different eager loading for different consumers | Create separate Query Builder methods (e.g., `buildForTable()` vs `buildForDetail()`) |

#### When to Split a View Model

| Trigger | Action |
|---------|--------|
| View Model > 20 properties | Consider splitting into nested View Models (e.g., `UnitDetailData` nests `UnitTimeline`, `InspectionSummary`) |
| View Model has properties used by only 1 consumer | Move to consumer-specific View Model |
| Same View Model shape used by table + slide-over with different nullability | Create separate DTOs — don't overload one class |
| View Model needs computed property | **Do NOT add method** — compute in Service before constructing |

### 23.3 Documentation Rules

#### ADR Maintenance

| Event | Action | Owner |
|-------|--------|-------|
| New architectural decision made | Create new ADR (ADR-XXX) in Section 17 | PR author |
| ADR status changes (accepted → deprecated) | Update ADR status, add deprecation note, create successor ADR | Architect |
| ADR rejected alternative becomes accepted | Create new ADR, mark old ADR as superseded | Architect |
| Code violates an ADR | Block PR, reference ADR in review comment | Reviewer |

#### When to Create New Architecture Decision

Create a new ADR when:
1. A new pattern, library, or framework is introduced
2. An existing ADR's decision is challenged or needs refinement
3. A new module follows a different architectural approach
4. A previously rejected alternative becomes viable due to changed circumstances

**Do NOT create an ADR for:**
- Bug fixes
- Feature additions within existing architecture
- Config value changes
- Query optimization within existing strategy

#### Sprint 0–5 Consistency Maintenance

| Rule | Enforcement |
|------|-------------|
| Sprint 0 (Product Discovery) decisions are LOCKED | Any PR that changes user roles, scope, success criteria → **Block** |
| Sprint 1A–1C (Workspace Architecture) is LOCKED | Any PR that changes section count, section order, workspace layout → **Block** |
| Sprint 2 (UX) is LOCKED | Any PR that changes user journeys, interaction flows, navigation rules → **Block** |
| Sprint 3 (Visual Design) is LOCKED | Any PR that changes density, color zones, badge hierarchy, typography tokens → **Block** |
| Sprint 4 (UI Spec) is LOCKED | Any PR that changes cell composition, interaction IDs, UI states → **Block** |
| Sprint 5 (this document) is LIVING | ADRs may be added, sections may be extended, but LOCKED decisions (D1–D25) cannot be changed without SIP (Significant Impact Process) |

**Significant Impact Process (SIP):** If a LOCKED decision must be changed:
1. Author creates a new ADR proposing the change
2. ADR must reference the original decision, explain why change is needed, and assess impact
3. ADR must be reviewed and accepted before implementation
4. Original decision is marked as SUPERSEDED, not deleted

### 23.4 Onboarding Guide for New Engineers

New engineers joining the monitoring module should read in this order:

1. **This document** (Sprint 5) — architecture, ADRs, dependency rules, governance
2. **Sprint 0** — product vision, user roles, scope
3. **Sprint 2** — UX flows, interaction patterns
4. **Sprint 4** — UI specification (cell composition, interaction IDs)
5. **Sprint 1A–1C** — workspace sections, table design, detail architecture
6. **Sprint 3** — visual design tokens, color zones

**First implementation task:** Pick one Domain Service (e.g., `StageResolver`), implement it with tests per Section 13.4, and submit PR for code review. This exercises the full architecture (Enums → Service → View Model) without touching the delivery layer.

---

## Appendix A — MonitoringFilter DTO

```
readonly class MonitoringFilter {
  public function __construct(
    public readonly ?int $branch_id,
    public readonly ?string $mode,           // 'sea' | 'land' | null
    public readonly ?string $route,           // 'tam' | 'all' | route_code
    public readonly ?string $exception_filter,// one of 6 types or null
    public readonly string $search,           // D14: jump-to
    public readonly string $group_mode,       // 'flat' | 'sppb' | 'voyage'
    public readonly bool $show_finished,      // D2
    public readonly string $sort,             // see sort options
    public readonly int $page,
  ) {}

  public function cacheKey(): string {
    return md5(serialize([
      $this->branch_id,
      $this->mode,
      $this->route,
      $this->show_finished,
    ]));
  }

  public function toArray(): array { ... }
}
```

## Appendix B — Exception Type Definitions (D8 LOCKED)

| Type | Key | Detection Logic | Severity | Deep Link Target |
|------|-----|-----------------|----------|-----------------|
| Delay | `delay` | latestTrack.status = 'hold' OR voyageRecord.is_delayed OR age.is_stuck | critical | Shipment Tracking |
| NG | `ng` | unit has inspection with items where result='ng' AND finding_type='major_damage' | critical | Unit Inspection |
| Hold | `hold` | latestTrack.status = 'hold' AND tracked_at IS NOT NULL | critical | Shipment Tracking |
| Demurrage | `demurrage` | mode='sea' AND voyageRecord.ata_at IS NOT NULL AND NOT delivered AND age_at_port > demurrage_days | warning | Voyage Detail |
| Missing Voyage | `missing_voyage` | mode='sea' AND voyage_id IS NULL | warning | Voyage Assignment |
| PDI Pending | `pdi_pending` | unit has inspection stage='handover_depot' AND submitted_at IS NULL | warning | Unit Inspection |

## Appendix C — TrackStatus Color Zone Mapping (D9 LOCKED)

| Zone | TrackStatus | Color | Meaning |
|------|------------|-------|---------|
| Pickup | Pickup, Handover, HandoverTrucking | info (blue) | Initial handover phase |
| Pre-Transit | Stuffing, DeliveryToPort, Stacking | warning (amber) | Preparation before sailing |
| Sailing | UnitLoading, OnShip, VesselDepart | primary (indigo) | At sea |
| Arrival | VesselArrival, Unloading | primary (indigo) | Reached destination port |
| Dooring | DeliveryToCustomer, Delivered | success (green) | Final delivery |
| Terminal | Hold, Cancelled | danger (red) | Exception states |

## Appendix D — Config Schema

```
// config/jss_kpi.php (additions)
'monitoring' => [
  'page_size' => 50,
  'poll_interval' => 60,       // seconds
  'cache_ttl' => 30,            // seconds
  'demurrage_days' => 7,       // days at port before demurrage exception
],
'thresholds' => [
  // ... existing ...
  'stuck_days' => 3,           // D10: days without progress before stuck
],
```

---

**End of Sprint 5 — Technical Architecture: Architecture Handbook (Rev 5.1)**