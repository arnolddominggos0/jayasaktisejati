# May 2026 Seed Mapping

## Objective

Transform real May 2026 operational Excel dataset
into canonical operational entities.

---

# Canonical Flow

ShippingLine Draft
→ VesselPlan
→ VesselPlanItem
→ Final Approval
→ Voyage
→ Monitoring
→ Shipment Consumption

---

# Excel → Entity Mapping

## Excel Column: Vessel

Maps to:
- vessels.name

Used by:
- VesselPlanItem
- Voyage

---

## Excel Column: Voyage No / JSS

Maps to:
- voyages.voyage_no

Canonical rule:
- JSS column becomes canonical voyage number

Ignore:
- LTS column

---

## Excel Column: ETD

Maps to:
- vessel_plan_items.planned_etd
- voyages.etd

---

## Excel Column: ETA

Maps to:
- vessel_plan_items.planned_eta
- voyages.eta

---

## Excel Column: Cargo Plan

Maps to:
- voyages.cargo_plan

---

# Seeding Lifecycle

## Stage 1

Seed:
- ShippingLine
- Vessel
- Port
- Customer

---

## Stage 2

Generate:
- VesselPlan
- VesselPlanItem

Status:
- Final

---

## Stage 3

Generate Voyage from finalized VesselPlanItem

---

## Stage 4

Generate:
- VoyageCheckpoint
- Monitoring rows
- SLA baseline

---

## Stage 5

Generate:
- VesselCheck scenarios
- delay simulation
- anomaly cases

---

# Monitoring Simulation

Monitoring source:
- Voyage

NOT:
- ShippingSchedule
- VesselPlan

---

# Shipment Consumption

Shipment source:
- voyage_id

Shipment receives:
- ETD
- ETA
- vessel
- route

from Voyage only.

---

# Canonical Rules

Voyage:
- immutable operational commitment

VesselPlan:
- editable planning workspace

ShippingSchedule:
- transitional TAM coordination layer