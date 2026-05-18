# CANONICALIZATION PLAN

## OBJECTIVE

Normalize operational ownership.

Ensure Voyage becomes:
- canonical operational execution object
- immutable operational reference
- single source of truth

---

# TARGET ARCHITECTURE

Shipping Line Draft
→ Vessel Plan
→ TAM Approved Schedule
→ Voyage
→ Monitoring / Shipment / KPI

---

# SOURCE OF TRUTH

| Domain | Source |
|---|---|
| ETD | Voyage |
| ETA | Voyage |
| Voyage Number | Voyage |
| Vessel | Voyage |
| Route | Voyage |
| Monitoring | Voyage |
| Shipment | Voyage |
| KPI | Voyage |

---

# VESSEL PLAN ROLE

Vessel Plan:
- planning workspace
- editable
- operational preparation layer
- negotiation layer

Vessel Plan is NOT:
- execution object
- monitoring source
- shipment source
- KPI source

---

# VOYAGE ROLE

Voyage:
- approved operational commitment
- finalized operational schedule
- execution object
- canonical operational backbone

Voyage must survive:
- vessel plan revisions
- planning cleanup
- monitoring lifecycle

---

# CLEANUP TARGETS

## 1. Remove Circular Ownership

Current:
- voyage.vessel_plan_item_id
- vessel_plan_items.voyage_id

Target:
- one directional ownership only

Voyage must not be cascade deleted from planning layer.

---

## 2. Normalize Delete Strategy

Target:
- use nullOnDelete for planning relationships
- preserve operational execution records

Never allow:
- VesselPlan deletion destroying Voyage
- planning deletion destroying monitoring history

---

## 3. Monitoring Ownership

Monitoring reads:
- Voyage only

Monitoring does NOT read:
- Vessel Plan directly

---

## 4. Shipment Ownership

Shipment:
- consumes Voyage

Shipment cannot:
- override ETD
- override ETA
- override route
- override vessel

---

## 5. ShippingSchedule Strategy

ShippingSchedule currently overlaps:
- Vessel Plan
- Voyage

Determine:
- transitional compatibility layer
OR
- deprecated legacy module

Avoid duplicated operational truth.

---

# IMMUTABILITY RULE

After Voyage becomes ACTIVE:
- direct destructive planning actions forbidden
- operational history preserved
- delay changes logged via VoyageDelayLog

---

# FINAL OBJECTIVE

After canonicalization:
- Voyage becomes operational backbone
- Monitoring becomes deterministic
- Shipment becomes execution attachment
- KPI becomes valid operational metric