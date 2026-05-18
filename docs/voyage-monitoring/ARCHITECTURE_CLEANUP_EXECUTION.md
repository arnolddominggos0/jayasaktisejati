# Architecture Cleanup Execution Plan
Pre-May 2026 Seeding

## Objective

Menormalkan ownership boundary antara:

- VesselPlan
- ShippingSchedule
- Voyage
- VesselCheck
- Shipment
- Monitoring

agar:

- Voyage menjadi canonical operational execution object
- Shipment consume Voyage
- Monitoring consume Voyage
- VesselPlan menjadi planning-only workspace
- ShippingSchedule menjadi transitional TAM coordination layer

---

# Cleanup Phases

## Phase 0 — Critical Bug Fixes

### 0.1 Remove broken VesselCheckCase self-reference

Delete:
- VesselCheckCase::vesselCheckCase()

Reason:
- invalid recursive relationship
- no FK exists
- unsafe recursion risk

---

### 0.2 Remove VesselCheckCase::vesselChecks()

Reason:
- vessel_checks table has no vessel_check_case_id
- relationship is invalid

---

### 0.3 Create VesselCheckEtdLog model

Table exists but model missing.

---

### 0.4 Create VesselCheckEvaluation model

Table exists but model missing.

---

### 0.5 Fix VoyageDelayLog fillable

Add:
- new_etb
- new_atb_at

Add casts:
- datetime

Reason:
Voyage::updating writes these values but model silently discards them.

---

## Phase 1 — FK Normalization

### 1.1 vessel_plan_items.voyage_id

Change:
- cascadeOnDelete()
→ nullOnDelete()

Reason:
Voyage must survive plan revisions.

---

### 1.2 shipping_schedules.voyage_id

Change:
- cascadeOnDelete()
→ nullOnDelete()

Reason:
ShippingSchedule is transitional only.

---

### 1.3 Normalize vessel_checks.voyage_id

Canonical behavior:
- nullable
- nullOnDelete()

---

### 1.4 Add voyage_id to vessel_check_cases

Reason:
Monitoring and delay case must directly reference Voyage.

---

# Canonical Ownership

## VesselPlan

Role:
- draft planning workspace
- operational proposal
- pre-finalization

NOT:
- execution source
- shipment source
- monitoring source

---

## ShippingSchedule

Role:
- TAM coordination layer
- transitional compatibility layer

NOT:
- canonical schedule source

---

## Voyage

Role:
- canonical operational execution object
- operational source of truth

Consumed by:
- Monitoring
- Shipment
- SLA
- KPI
- Delay tracking

---

## Shipment

Shipment consumes:
- voyage_id

Shipment must NOT override:
- ETD
- ETA
- vessel
- route

---

## Monitoring

Monitoring reads:
- Voyage only

NOT:
- VesselPlan directly

---

# Seeding Dependency Order

1. ShippingLine
2. Port
3. Customer
4. Vessel
5. VesselPlan
6. VesselPlanItem
7. Finalize VesselPlan
8. Voyage
9. VoyageCheckpoint
10. VesselCheck
11. VoyageMilestone
12. VoyageDelayLog
13. SlaResult
14. Shipment
15. ShippingSchedule

---

# Pre-Seeding MUST COMPLETE

- VoyageDelayLog fix
- VesselCheckCase cleanup
- VesselCheckEtdLog model
- VesselCheckEvaluation model
- FK normalization
- voyage_id on vessel_check_cases
- backfill voyage_id

---

# Deferred Until Post-Seeding

- Shipment sync refactor
- ShippingSchedule deprecation
- Voyage immutability
- Voyage auto-generation service
- legacy column removal