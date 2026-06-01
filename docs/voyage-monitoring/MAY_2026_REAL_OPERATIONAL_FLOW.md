# May 2026 Real Operational Flow

## Objective

Menggunakan data real Mei 2026 sebagai canonical operational seed dataset.

Dataset ini menjadi:
- baseline operational execution
- monitoring simulation
- KPI calculation reference
- anomaly simulation source

---

# Real Operational Workflow

## Step 1 — Shipping Line Draft

JSS menerima draft vessel schedule dari:
- Tanto
- Meratus

Draft digunakan untuk:
- spacing analysis
- dwelling analysis
- feasibility planning

Output:
- VesselPlan
- VesselPlanItem

Status:
- Draft

---

## Step 2 — Internal Planning

JSS melakukan:
- vessel spacing analysis
- ETA overlap checking
- cargo capacity balancing
- operational feasibility review

Output:
- revised vessel proposal

---

## Step 3 — TAM Coordination

Draft final dikirim ke TAM.

TAM melakukan:
- approval
- revision
- operational adjustment

Output:
- approved operational schedule

---

## Step 4 — Voyage Generation

Approved schedule menjadi:
- Voyage

Voyage adalah:
- canonical execution object
- source of truth operasional

Voyage digunakan oleh:
- Monitoring
- Shipment
- KPI
- SLA
- VesselCheck

---

## Step 5 — Monitoring

Monitoring membaca:
- ETD
- ETA
- ATD
- ATA
- delays
- anomaly

Monitoring source:
- Voyage

---

## Step 6 — Vessel Check

Jika terjadi:
- delay
- vessel issue
- route issue
- operational disruption

maka dibuat:
- VesselCheck
- VesselCheckCase

---

## Step 7 — Shipment Consumption

Shipment consume:
- voyage_id
- vessel
- ETA
- ETD
- route

Shipment tidak boleh override:
- voyage schedule

---

# Canonical Rule

Voyage adalah:
- final operational commitment
- immutable operational reference

VesselPlan hanyalah:
- planning workspace

ShippingSchedule hanyalah:
- TAM coordination layer