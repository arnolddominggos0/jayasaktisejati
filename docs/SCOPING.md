# SCOPING.md — JSS Platform Authorization & Data Isolation Architecture

## Document Purpose

This document defines the canonical authorization boundaries, data isolation rules, scope inheritance, and query scoping strategy for the JSS multi-branch maritime logistics platform. It is the single source of truth for how roles interact with data across the Admin, FC, and Customer panels, as well as the public API.

**Version:** 1.0  
**Last Updated:** 2026-05-13  
**Scope Status:** Canonical scope transition in progress (`effective_branch_id` is the new source of truth; `branch_id` is legacy fallback).

---

## Table of Contents

1. [Scope Philosophy](#1-scope-philosophy)
2. [Role Hierarchy](#2-role-hierarchy)
3. [Canonical Scope Model](#3-canonical-scope-model)
4. [Scope Resolution Rules](#4-scope-resolution-rules)
5. [Query Scope Standards](#5-query-scope-standards)
6. [Policy Enforcement Rules](#6-policy-enforcement-rules)
7. [Filament Scope Enforcement](#7-filament-scope-enforcement)
8. [API Scope Enforcement](#8-api-scope-enforcement)
9. [Scope Edge Cases](#9-scope-edge-cases)
10. [Common Leakage Risks](#10-common-leakage-risks)
11. [Recommended Laravel Patterns](#11-recommended-laravel-patterns)
12. [Anti-Patterns to Avoid](#12-anti-patterns-to-avoid)
13. [Future Scalability Concerns](#13-future-scalability-concerns)

---

## 1. Scope Philosophy

### Core Tenets

1. **Deny by Default:** Every query without an explicit scope returns zero rows.
2. **Scope at the Edge:** Scoping happens as close to the request boundary as possible (middleware, resource `getEloquentQuery()`, controller entry).
3. **Canonical over Legacy:** `scope_branch_id` + `scope_unit_type` + `scope_unit_id` are the canonical scope fields. `branch_id` on the `users` table is legacy and will be deprecated.
4. **Validation at Write Time:** Scope consistency is validated when users are created or updated, not deferred to query time.
5. **Defense in Depth:** A single scoping layer is never enough. Middleware + app bindings + policy checks + manual query filters must all align.

### Threat Model

- **Cross-Branch Leakage:** An office_admin in Branch A must never see shipment data from Branch B.
- **Cross-Depot Leakage:** An FC assigned to Depot A must never see sea shipments assigned to Depot B.
- **Cross-Customer Leakage:** A customer must only see their own shipments.
- **Privilege Escalation:** A field_coordinator must never create/delete shipments or access admin configuration.
- **API Bypass:** Direct API calls must respect the same scoping rules as Filament panels.

---

## 2. Role Hierarchy

### Role Matrix

| Role | Panel Access | Scope Dimension | Create | Update | Delete | Global Config |
|---|---|---|---|---|---|---|
| `super_admin` | Admin + FC | Global (null scope) | All | All | All | Yes |
| `office_admin` | Admin | Branch (`effective_branch_id`) | Branch | Branch | No | No |
| `field_coordinator` | FC | Depot/Pool (`scope_unit_type` + `scope_unit_id` + `scope_branch_id`) | No | Sea-only, assigned | No | No |
| `customer` | Customer Portal | Own shipments (`customer_id`) | No | No | No | No |

### Scope Dimension Definitions

| Dimension | Field(s) | Applies To | Null Semantics |
|---|---|---|---|
| **Global** | `hasRole('super_admin')` | super_admin | Null = see everything |
| **Branch** | `effective_branch_id()` | office_admin | Null = fallback to legacy `branch_id` |
| **Unit** | `scope_unit_type` + `scope_unit_id` + `scope_branch_id` | field_coordinator | Any null = abort 403/409 |
| **Customer** | `user.customer_id` → `shipment.customer_id` | customer | Null = empty result set |

### Hierarchy Rules

1. `super_admin` overrides all scope checks via `Policy::before()` returning `true`.
2. `office_admin` is scoped by branch. They see all data within their branch regardless of depot assignment.
3. `field_coordinator` is scoped by branch **AND** unit (depot/pool). They see only sea shipments assigned to their unit.
4. `customer` is scoped by `customer_id` linkage. No branch or unit dimension applies.

---

## 3. Canonical Scope Model

### User Model Scope Fields

```php
// On users table
scope_branch_id   // INT, nullable — canonical branch
scope_unit_id     // INT, nullable — canonical depot/pool ID
scope_unit_type   // ENUM('depot', 'pool'), nullable — unit type
branch_id         // INT, nullable — LEGACY, to be deprecated
customer_id       // INT, nullable — links to customers table
```

### `effectiveBranchId()` — The Canonical Resolver

```php
public function effectiveBranchId(): ?int
{
    return $this->scope_branch_id ?? $this->branch_id;
}
```

**Rules:**
- Prefer `scope_branch_id` always.
- Fallback to `branch_id` only during transition period.
- All new code MUST use `effectiveBranchId()`.
- `branch_id` should not be read directly for authorization decisions.

### Unit Scope Validation (Model Boot)

When a user's canonical scope fields change, the `User::booted()` saving callback validates:

1. The unit (`depot` or `pool`) exists.
2. The unit's `coordinator_user_id` matches the user being saved.
3. The unit's `branch_id` matches `scope_branch_id`.

**Failure modes:** Throws `InvalidArgumentException` with explicit message.

### Unit Uniqueness Guarantee

`Depot` and `Pool` models enforce that a coordinator can only be assigned to **one** unit total (cross-table uniqueness via booted saving hooks).

---

## 4. Scope Resolution Rules

### Resolution Order

When the system needs to determine a user's data scope, it resolves in this order:

```
1. Role check → super_admin? → bypass all scoping
2. Panel check → Which panel is being accessed?
   a. Admin Panel  → resolve branch via effectiveBranchId()
   b. FC Panel     → resolve branch + unit via ScopeByBranchAndDepot middleware
   c. Customer Panel → resolve customer_id via Auth::user()->customer_id
3. Request context → API vs Filament vs Public
4. Data ownership → Does the record belong to the resolved scope?
```

### Scope Binding Lifecycle

| Layer | Binding Key | Set By | Used By |
|---|---|---|---|
| Admin/General | `currentBranchId` | `ScopeByBranch` middleware | Admin resources, API controllers |
| FC | `scope.branch_id` | `ScopeByBranchAndDepot` middleware | FC resources, widgets, policies |
| FC | `scope.depot_id` | `ScopeByBranchAndDepot` middleware | FC ShipmentResource, loading sessions |
| FC | `scope.unit_type` | `ScopeByBranchAndDepot` middleware | FC widgets, dashboard |
| FC | `scope.mode` | `ScopeByBranchAndDepot` middleware | FC conditional logic |
| Session | `fc.active_branch_id` | `ScopeByBranchAndDepot` middleware | Legacy session-based fallbacks |
| Session | `fc.active_depot_id` | `ScopeByBranchAndDepot` middleware | Legacy session-based fallbacks |

**Critical Rule:** Code must never assume these bindings exist. Always check `app()->bound('key')` before accessing.

### Scope Inheritance

- **Shipment** inherits `branch_id` from creator's `effectiveBranchId()` at creation time.
- **Shipment** inherits `assigned_depot_id` from `Depot::resolveIdFor(branch, mode, voyage)` when sent to FC.
- **BriefingSession** inherits `depot_id` from the FC's active scope.
- **LoadingSession** inherits `shipment_id` + `depot_id` from the parent shipment.
- **Voyage** is NOT branch-scoped in the database; filtering happens via related shipping schedules and shipment lookups.

---

## 5. Query Scope Standards

### The Golden Rule

> **Every database query that returns user-visible data must have an explicit `where` clause that restricts by scope.**

### Current Implementation Pattern

```php
// Admin panel / API — branch scoping
$query->where(fn ($w) => $w
    ->where('branch_id', $user->effectiveBranchId())
    ->orWhereNull('branch_id')
);

// FC panel — branch + depot + sea mode
$query->where('mode', 'sea')
    ->where(fn ($w) => $w
        ->where('branch_id', app('scope.branch_id'))
        ->orWhereNull('branch_id')
    )
    ->where(fn ($w) => $w
        ->where('assigned_depot_id', app('scope.depot_id'))
        ->orWhere('coordinator_id', $user->id)
    );

// Customer panel — customer scoping
$query->where('customer_id', $user->customer_id);
```

### What Is Missing (Weakness)

**No Eloquent Global Scopes are applied.** Every query must manually scope. This is the single largest architectural risk.

### Recommended Query Pattern

```php
// In a model
public function scopeForUser(Builder $query, User $user): Builder
{
    if ($user->hasRole('super_admin')) {
        return $query;
    }

    if ($user->hasRole('office_admin')) {
        return $query->where(fn ($w) => $w
            ->where('branch_id', $user->effectiveBranchId())
            ->orWhereNull('branch_id')
        );
    }

    if ($user->hasRole('field_coordinator')) {
        return $query->where('mode', 'sea')
            ->where(fn ($w) => $w
                ->where('branch_id', $user->scope_branch_id)
                ->orWhereNull('branch_id')
            )
            ->where(fn ($w) => $w
                ->where('assigned_depot_id', $user->scope_unit_id)
                ->orWhere('coordinator_id', $user->id)
            );
    }

    if ($user->hasRole('customer')) {
        return $query->where('customer_id', $user->customer_id);
    }

    return $query->whereRaw('1 = 0'); // deny by default
}
```

### Null Branch Handling

The `orWhereNull('branch_id')` pattern exists throughout the codebase to handle legacy data where `branch_id` is NULL.

**Risk:** This creates a bypass path. A malicious or misconfigured record with `branch_id = NULL` becomes visible to ALL scoped users.

**Mitigation:**
1. Backfill all NULL `branch_id` values.
2. Add `NOT NULL` constraint to `branch_id` on all operational tables.
3. Remove `orWhereNull('branch_id')` from all queries.

---

## 6. Policy Enforcement Rules

### Policy Architecture

JSS uses Laravel Policies (`ShipmentPolicy`, `ShipmentTrackPolicy`) plus Spatie Permissions for RBAC.

### ShipmentPolicy Rules

| Action | super_admin | office_admin | field_coordinator | customer |
|---|---|---|---|---|
| `viewAny` | ✅ | ✅ | ✅ | ❌ (uses Customer resource) |
| `view` | ✅ | same branch | sea + unit scope | own shipment only |
| `create` | ✅ | ✅ | ❌ | ❌ |
| `update` | ✅ | same branch | sea + unit scope | ❌ |
| `delete` | ✅ | same branch | ❌ | ❌ |
| `print` | ✅ | same branch | sea only | ❌ |

### FC Update Logic (Policy + Model)

The FC `update` check in `ShipmentPolicy` has **three fallback layers**:

1. **Canonical scope:** `scope_unit_type === 'depot'` AND `scope_unit_id === assigned_depot_id`
2. **App binding fallback:** `app('scope.depot_id')` OR legacy depot lookup via `Depot::where('coordinator_user_id', $user->id)`
3. **Legacy coordinator_id:** `shipment->coordinator_id === $user->id`

**Critique:** Three fallback layers increase complexity and risk of inconsistent behavior. The canonical scope (layer 1) should be the ONLY check. Layers 2 and 3 are transition crutches.

### Policy Checklist for New Resources

When adding a new resource, verify:
- [ ] Policy class exists and is registered in `AuthServiceProvider`
- [ ] `before()` method grants `super_admin` full access
- [ ] Each role has explicit conditions (no implicit fallback to `true`)
- [ ] Scope checks use canonical fields, not legacy fields
- [ ] Return `false` as the final fallback (deny by default)

---

## 7. Filament Scope Enforcement

### Panel Architecture

| Panel | Middleware Stack | Scope Applied |
|---|---|---|
| Admin (`/admin`) | `EnsurePanelRole` → `ScopeByBranch` | Branch only |
| FC (`/fc`) | `EnsurePanelRole` → `ScopeByBranchAndDepot` | Branch + Depot/Pool |
| Customer (`/portal`) | `EnsurePanelRole` | Customer ID |

### Resource-Level Scoping

Every Filament resource MUST override `getEloquentQuery()`:

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    // Super admin bypass
    if (Filament::auth()->user()?->hasRole('super_admin')) {
        return $query;
    }

    // Apply role-specific scope
    // ...

    return $query;
}
```

### FC Resource Pattern

```php
public static function getEloquentQuery(): Builder
{
    $q = parent::getEloquentQuery();
    $u = Filament::auth()->user();

    if (! $u) return $q->whereRaw('1=0');

    // Mode restriction
    $q->where('mode', ShipmentMode::Sea->value);

    // Scope binding check
    $branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : null;
    $depotId = app()->bound('scope.depot_id') ? app('scope.depot_id') : null;

    if (! $branchId || ! $depotId) {
        return $q->whereRaw('1=0'); // deny if scope unresolved
    }

    // Branch scope
    $q->where(fn ($w) => $w
        ->where('branch_id', $branchId)
        ->orWhereNull('branch_id')
    );

    // Depot scope (canonical + legacy coordinator)
    $q->where(fn ($w) => $w
        ->where('assigned_depot_id', $depotId)
        ->orWhere('coordinator_id', $u->id)
    );

    return $q;
}
```

### Widget Scoping

All FC widgets MUST read scope from app bindings with fallback:

```php
$branchId = app()->bound('scope.branch_id')
    ? app('scope.branch_id')
    : ($user->effectiveBranchId() ?? null);

$depotId = app()->bound('scope.depot_id')
    ? app('scope.depot_id')
    : null;
```

**Risk:** If widget runs in a context where middleware hasn't fired (e.g., queued job, console command), `scope.branch_id` will be unbound.

---

## 8. API Scope Enforcement

### Route Architecture

```php
Route::middleware(['auth:sanctum', 'scope.branch'])->group(function () {
    // All protected API routes
});
```

**Note:** The middleware alias `scope.branch` maps to `ScopeByBranch`. The FC panel uses `ScopeByBranchAndDepot` directly in its panel provider, NOT in API routes.

### API Scoping Behavior

| Endpoint Group | Middleware | Scope Applied |
|---|---|---|
| Dashboard | `auth:sanctum` + `scope.branch` | Branch (super_admin bypass) |
| Shipments | `auth:sanctum` + `scope.branch` + `role:super_admin\|office_admin\|field_coordinator` | Branch (controller manually applies) |
| Customers | same | Branch |
| Voyages | same | Branch |
| Branches | same | Branch |

### API Controller Pattern

```php
// In controller methods
if (!$request->user()->hasRole('super_admin')) {
    $query->where(fn ($w) => $w
        ->where('branch_id', $request->user()->effectiveBranchId())
        ->orWhereNull('branch_id')
    );
}
```

### API FC Gap

**Critical Finding:** The API routes do NOT include `ScopeByBranchAndDepot` middleware. FC-scoped API endpoints (if any exist for FC) would NOT have depot-level scoping enforced at the middleware layer. Currently, FC primarily uses Filament, but any future FC API endpoints must add explicit depot scoping.

### Customer API

There is no dedicated Customer API route group. Customers access data through:
1. Filament Customer Panel (`/portal`)
2. Public tracking (`/tracking` — no auth required, code-based lookup)

---

## 9. Scope Edge Cases

### 9.1. Super Admin on FC Panel

`super_admin` can access the FC panel (`canAccessPanel` returns true for both `admin` and `fc` panels). However, `super_admin` does NOT have `scope_branch_id` or `scope_unit_id` set.

**Behavior:** In FC panel, `ScopeByBranchAndDepot` middleware will:
1. Query depots/pools where `coordinator_user_id = super_admin->id`
2. If none found, abort 403

**Implication:** A `super_admin` user without a depot/pool coordinator assignment CANNOT access the FC panel. This is intentional — super_admin should use the Admin panel for global oversight.

### 9.2. Multi-Assignment Detection

`ScopeByBranchAndDepot` middleware aborts with HTTP 409 if a user is coordinator of more than one unit (depot + pool, or multiple depots).

**This is a safety guard, not a feature.** The system assumes 1 FC = 1 Unit.

### 9.3. Canonical Mismatch

If a user has `scope_branch_id`, `scope_unit_id`, or `scope_unit_type` populated, but these don't match the live depot/pool assignment, the middleware aborts with HTTP 409.

**Purpose:** Prevents stale scope data from granting incorrect access after reassignments.

### 9.4. Null Customer ID

If a customer user has `customer_id = NULL`, the Customer Panel's `ShipmentResource::getEloquentQuery()` returns `whereRaw('1 = 0')` (empty result set).

**Risk:** Customer sees empty dashboard with no explanation. UX should communicate this clearly.

### 9.5. Shipment with NULL branch_id

Records created before branch scoping may have `branch_id = NULL`. The `orWhereNull('branch_id')` pattern makes them visible to ALL users in the same scope dimension.

**This is a data integrity bug, not a feature.** All operational records MUST have `branch_id`.

### 9.6. Mode Filtering for FC

FC can ONLY see `mode = 'sea'` shipments. However, the mode filter is applied at the **resource/query level**, not at the middleware level.

**Risk:** If a new FC resource is added and forgets the mode filter, FC could see land shipments.

### 9.7. Scope in Console/Queue Context

Console commands and queued jobs have no authenticated user. Any code that calls `Auth::user()->effectiveBranchId()` or `app('scope.branch_id')` will fail.

**Mitigation:** Console commands must explicitly pass scope parameters. Never rely on auth/middleware in CLI context.

---

## 10. Common Leakage Risks

### Risk 1: Missing Query Scope (HIGH)

**Scenario:** A developer adds a new Filament resource or API endpoint and forgets to apply `getEloquentQuery()` override or controller-level scope.

**Impact:** Data becomes globally visible.

**Detection:** Code review must check every new resource/controller for scope application.

**Mitigation:** Implement global scopes on models (see Section 11).

### Risk 2: `orWhereNull('branch_id')` Bypass (MEDIUM)

**Scenario:** A record with `branch_id = NULL` is visible to all users within the same panel.

**Impact:** Unintended cross-branch visibility for unassigned records.

**Mitigation:** Backfill and enforce `NOT NULL` on `branch_id`.

### Risk 3: Policy-Query Mismatch (HIGH)

**Scenario:** `ShipmentPolicy::update()` checks `scope_unit_id`, but the query in `ShipmentResource::getEloquentQuery()` checks `app('scope.depot_id')`. If these diverge, a user might pass policy but see no data (or vice versa).

**Impact:** Inconsistent UX or authorization bypass.

**Mitigation:** Policies and queries MUST use the same canonical scope fields. Consolidate scope resolution into a single service class.

### Risk 4: App Binding Null in Policies (MEDIUM)

**Scenario:** `ShipmentPolicy` checks `app()->bound('scope.depot_id')`. In API context or queued jobs, this binding may not exist.

**Impact:** Falls back to legacy depot lookup, which may return different results.

**Mitigation:** Policies should rely on user model fields (`scope_unit_id`) not app bindings.

### Risk 5: Customer Panel Brute Force (LOW)

**Scenario:** Customer tracking is public (`/tracking`). Anyone with a valid shipment code can see sender, receiver, vessel, and tracking history.

**Impact:** Information disclosure (but limited to knowing the code).

**Mitigation:** Tracking codes are sufficiently random. Consider rate-limiting public tracking.

### Risk 6: Super Admin without Depot Assignment Blocked from FC (LOW)

**Scenario:** Super admin tries to access FC panel but has no depot assignment.

**Impact:** 403 error. Super admin must use Admin panel instead.

**Status:** By design. Document clearly.

### Risk 7: Widgets Bypassing Resource Queries (MEDIUM)

**Scenario:** A dashboard widget queries `Shipment::query()` directly instead of using the resource's scoped query.

**Impact:** Widget shows unscoped data.

**Mitigation:** All widgets must implement their own scope logic. Consider a shared scope trait.

---

## 11. Recommended Laravel Patterns

### Pattern 1: Scope Service Class

Create a single `UserScopeService` that resolves scope for any user:

```php
class UserScopeService
{
    public static function for(User $user): ScopeContext
    {
        if ($user->hasRole('super_admin')) {
            return ScopeContext::global();
        }
        if ($user->hasRole('office_admin')) {
            return ScopeContext::branch($user->effectiveBranchId());
        }
        if ($user->hasRole('field_coordinator')) {
            return ScopeContext::unit(
                $user->scope_branch_id,
                $user->scope_unit_type,
                $user->scope_unit_id
            );
        }
        if ($user->hasRole('customer')) {
            return ScopeContext::customer($user->customer_id);
        }
        return ScopeContext::denyAll();
    }
}
```

### Pattern 2: Eloquent Global Scope

Apply a global scope to scopable models:

```php
class BranchScopedScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $user = Auth::user();
        if (!$user || $user->hasRole('super_admin')) {
            return;
        }
        // Apply scope based on UserScopeService
    }
}
```

**Caveat:** Global scopes apply to ALL queries, including console commands. Use `withoutGlobalScope()` in CLI contexts.

### Pattern 3: Scoped Query Builder Macro

```php
Builder::macro('forCurrentUser', function () {
    $user = Auth::user();
    if (!$user) return $this->whereRaw('1=0');
    return UserScopeService::applyToQuery($this, $user);
});

// Usage
Shipment::query()->forCurrentUser()->get();
```

### Pattern 4: Unified Scope Trait for Filament

Replace `BranchScoped` trait with a comprehensive `ScopedResource` trait:

```php
trait ScopedResource
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if (!$user) return $query->whereRaw('1=0');
        if ($user->hasRole('super_admin')) return $query;

        return UserScopeService::applyToQuery($query, $user);
    }
}
```

### Pattern 5: Middleware Scope to Request Attribute

Instead of `app()->instance()`, bind scope to the Request object:

```php
$request->attributes->set('scope', UserScopeService::for($user));
```

This is more testable and explicit than app container bindings.

---

## 12. Anti-Patterns to Avoid

### ❌ Anti-Pattern 1: Reading `branch_id` Directly

```php
// BAD
$user->branch_id

// GOOD
$user->effectiveBranchId()
```

### ❌ Anti-Pattern 2: Assuming App Bindings Exist

```php
// BAD
$branchId = app('scope.branch_id'); // May throw if unbound

// GOOD
$branchId = app()->bound('scope.branch_id') ? app('scope.branch_id') : null;
```

### ❌ Anti-Pattern 3: Implicit Allow in Policies

```php
// BAD
public function view(User $user, Shipment $shipment): bool
{
    if ($user->hasRole('office_admin')) {
        return true; // No branch check!
    }
    // ...
}

// GOOD
public function view(User $user, Shipment $shipment): bool
{
    if ($user->hasRole('office_admin')) {
        return is_null($shipment->branch_id)
            || $shipment->branch_id === $user->effectiveBranchId();
    }
    // ...
    return false; // deny by default
}
```

### ❌ Anti-Pattern 4: Multiple Fallback Layers

```php
// BAD — Three different ways to check depot access
if ($user->scope_unit_type === 'depot' && ...) { ... }
$depotId = app()->bound('scope.depot_id') ? ... : Depot::where(...)->value('id');
if ($depotId && ...) { ... }
return $shipment->coordinator_id === $user->id;

// GOOD — Single canonical check
return $shipment->assigned_depot_id === $user->scope_unit_id
    && $shipment->branch_id === $user->scope_branch_id;
```

### ❌ Anti-Pattern 5: `orWhereNull('branch_id')` Without Backfill Plan

```php
// BAD — Permanent workaround for bad data
->orWhereNull('branch_id')

// GOOD — Temporary during migration, with migration deadline
// Add TODO: remove after backfill completes
```

### ❌ Anti-Pattern 6: Scope Logic in Controllers Only

```php
// BAD — Scope only in controller, not in policy/resource
// If someone adds an API endpoint, they might forget

// GOOD — Scope at the model/policy layer
```

### ❌ Anti-Pattern 7: Hardcoded Role Strings

```php
// BAD
$user->hasRole('field_coordinator')

// GOOD
$user->hasRole(Role::FieldCoordinator)
// or constants/enums
```

### ❌ Anti-Pattern 8: Console Commands Using Auth::user()

```php
// BAD — In console command
$branchId = Auth::user()->effectiveBranchId();

// GOOD — Explicit parameter
$branchId = $this->argument('branch_id');
```

---

## 13. Future Scalability Concerns

### 13.1. Multi-Unit Assignment for FC

Currently, 1 FC = 1 Unit. Future requirements may need 1 FC = multiple units (e.g., covering nearby depots).

**Impact:** `ScopeByBranchAndDepot` middleware aborts on multi-assignment. The canonical scope model (`scope_unit_id` is a single INT) doesn't support arrays.

**Mitigation:** Migrate to a `coordinator_assignments` pivot table: `(user_id, unit_type, unit_id)`. Deprecate `scope_unit_id`/`scope_unit_type` on users.

### 13.2. Cross-Branch Read-Only Access

Future: A regional manager needs read-only access to multiple branches.

**Impact:** Current model assumes single branch per user.

**Mitigation:** Introduce a `user_branch_access` pivot table with access levels (`read`, `write`).

### 13.3. Customer Multi-Company Access

Future: A user represents multiple customer companies.

**Impact:** `customer_id` is a single INT on users.

**Mitigation:** `user_customer_access` pivot table.

### 13.4. API-First Architecture

Future: Mobile apps, partner integrations, white-label portals.

**Impact:** Current API scoping is basic. FC API endpoints don't exist yet.

**Mitigation:**
1. Build a dedicated FC API route group with `ScopeByBranchAndDepot` middleware.
2. Implement API rate limiting per scope dimension.
3. Version the API (`/api/v2/`).

### 13.5. Tenant Isolation

Future: Multiple legal entities or franchise models.

**Impact:** `branch_id` is the current isolation boundary. Tenant isolation would require a higher-level dimension.

**Mitigation:** Add `tenant_id` to all models and scope queries by tenant first, then branch.

### 13.6. Performance at Scale

Current scoping adds `WHERE` clauses to every query. At high shipment volumes (millions), unindexed scope queries will degrade.

**Mitigation:**
- Composite indexes: `(branch_id, status, created_at)`
- Partition tables by `branch_id` or `created_at`
- Read replicas for reporting queries

### 13.7. Audit & Compliance

Future: External auditors need read-only access to specific branches for specific time periods.

**Impact:** Current roles don't support time-bound or audit-scoped access.

**Mitigation:** Time-bound role assignments or scoped API tokens with expiration.

---

## Appendix A: Weak Areas Critique

### A.1. No Model-Level Global Scope

**Severity:** HIGH  
**Finding:** Scoping is applied ad-hoc in controllers, resources, policies, and widgets. There is no centralized enforcement.  
**Fix:** Implement `UserScopeService` + model global scope (see Pattern 2).

### A.2. `orWhereNull('branch_id')` Everywhere

**Severity:** MEDIUM  
**Finding:** Null branch records are visible to all users. This is a data integrity issue masked as compatibility.  
**Fix:** Backfill all null `branch_id` values, add `NOT NULL` constraint, remove `orWhereNull`.

### A.3. Three-Layer FC Fallback in Policy

**Severity:** MEDIUM  
**Finding:** `ShipmentPolicy::update()` for FC checks canonical scope → app binding → legacy coordinator_id. This creates inconsistent behavior.  
**Fix:** Consolidate to canonical scope only after transition gate is green.

### A.4. App Container Bindings Are Invisible

**Severity:** MEDIUM  
**Finding:** `app()->instance('scope.branch_id', ...)` is hard to trace in tests and debugging. No IDE autocompletion.  
**Fix:** Use Request attributes or a dedicated `ScopeContext` value object.

### A.5. API Routes Lack FC Depot Scoping

**Severity:** MEDIUM  
**Finding:** The API route group only applies `scope.branch` middleware. FC-specific API endpoints would lack depot-level isolation.  
**Fix:** Add FC API route group with `ScopeByBranchAndDepot` when FC API is built.

### A.6. `EnsurePanelRole` Is Incomplete

**Severity:** LOW  
**Finding:** The middleware doesn't prevent `office_admin` from accessing the FC panel URL directly if they know it. It only checks `fc` and `customer` panels explicitly. The `admin` panel is implicitly open to anyone authenticated.  
**Fix:** Add explicit `admin` panel role check:
```php
if ($panel->getId() === 'admin') {
    if (! $user->hasAnyRole(['super_admin', 'office_admin'])) {
        abort(403);
    }
}
```

### A.7. Customer Tracking Is Information-Rich

**Severity:** LOW  
**Finding:** Public tracking (`/tracking/{code}`) reveals sender name, receiver name, vessel name, voyage number, and full tracking history.  
**Fix:** Consider masking sensitive fields (e.g., truncate sender/receiver names, hide vessel details until in-transit).

### A.8. `ScopeByBranchAndDepot` Depends on Database Query per Request

**Severity:** LOW  
**Finding:** The middleware queries `depots` and `pools` on every FC panel request. This is redundant if canonical scope fields are already populated.  
**Fix:** If canonical fields exist, validate them directly without querying depots/pools. Only query when canonical fields are null.

---

## Appendix B: Scope Migration Checklist

Use this checklist when transitioning from legacy to canonical scope:

- [ ] All users have `scope_branch_id` backfilled from `branch_id`
- [ ] All FC users have `scope_unit_id` and `scope_unit_type` backfilled from depot/pool assignment
- [ ] All `shipment.branch_id` records are backfilled (no NULLs)
- [ ] `orWhereNull('branch_id')` removed from all queries
- [ ] `effectiveBranchId()` is used everywhere instead of direct `branch_id` reads
- [ ] All policies use canonical scope fields
- [ ] All FC widgets use canonical scope fallbacks
- [ ] `ScopeByBranchAndDepot` middleware validates canonical fields without DB query when possible
- [ ] Tests added for cross-branch data leakage
- [ ] Tests added for cross-depot data leakage
- [ ] Tests added for customer data isolation

---

## Appendix C: Quick Reference — Scope by Context

| Context | User Role | Scope Field(s) | Query Pattern |
|---|---|---|---|
| Admin Panel List | super_admin | none | no filter |
| Admin Panel List | office_admin | `effective_branch_id()` | `where('branch_id', $id)` |
| FC Panel List | field_coordinator | `scope_branch_id` + `scope_unit_id` | `where('branch_id', $bid).where('assigned_depot_id', $did).where('mode', 'sea')` |
| Customer Panel List | customer | `customer_id` | `where('customer_id', $cid)` |
| API Index | super_admin | none | no filter |
| API Index | office_admin | `effective_branch_id()` | `where('branch_id', $id)` |
| API Index | field_coordinator | `effective_branch_id()` | `where('branch_id', $id)` (no depot filter!) |
| Public Tracking | anonymous | shipment `code` | `where('code', $code)` |

**Note:** API index for field_coordinator currently only applies branch filter, not depot filter. This is a gap if FC API endpoints are exposed.

---

*End of SCOPING.md*
