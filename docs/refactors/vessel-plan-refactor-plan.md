# Vessel Plan Refactor Plan

## Objective

Menyederhanakan Vessel Plan menjadi:
> pure vessel scheduling & approval module

Fokus:
- alignment dengan SOP
- simplifikasi UI
- cleanup KPI non-planning
- memperjelas planning domain

---

# Current Problems

## KPI Overload

UI saat ini menampilkan:
- total KPI
- draft KPI
- final KPI
- KPI deviation
- dwelling
- dooring

Padahal SOP utama hanya:
- continuity kapal
- ETD gap validation

---

## UI Too Analytical

Planning page terlalu banyak:
- KPI analytics
- operational metrics
- execution dashboard

Akibatnya:
- user operasional bingung
- fokus SOP menjadi kabur

---

## Domain Leakage

Planning layer masih mengandung:
- logistics KPI
- operational metrics

Padahal bukan domain Vessel Plan.

---

# Refactor Principles

## 1. Preserve Workflow

Jangan merusak:
- Draft
- Sent
- Revision
- Final

---

## 2. Preserve Audit

Tetap pertahankan:
- snapshots
- review history
- approval logs

---

## 3. Refactor Gradually

Gunakan:
- deprecate
- cleanup bertahap
- backward compatibility

---

# Refactor Phases

---

# Phase 1 — UI Simplification

## Remove From Table

- Total KPI
- Draft KPI
- Final KPI
- KPI Deviation
- Status KPI
- Dw/Sa/Dr

---

## Keep In Table

- Periode
- Status
- Jumlah Jadwal
- Avg Sailing
- Max Gap
- Status SOP
- Violation

---

# Phase 2 — Analyzer Cleanup

## Remove From Analyzer

- dwelling
- dooring
- total KPI
- KPI limit
- kpi_ok

---

## Keep In Analyzer

- sailing_avg
- max_gap
- gaps
- gap_ok
- violations
- schedule_count

Analyzer menjadi:
> SOP Validation Engine

---

# Phase 3 — Widget Cleanup

## Remove

- VesselPlanDashboard
- dwelling analytics
- cargo KPI
- delay KPI

---

## Keep

- SOP Validation
- Review History
- Schedule Summary

---

# Phase 4 — Model Cleanup

## Deprecate In VesselPlan

- draft_kpi_total
- final_kpi_total
- getKpiTotalAttribute()
- getKpiDeviationAttribute()
- getKpiDeviationLabelAttribute()

---

## Deprecate In VesselPlanItem

- getDwellingDaysAttribute()
- getDooringDaysAttribute()
- getTotalKpiAttribute()

---

# Phase 5 — Config Cleanup

## Move To Config

- gap_limit
- SOP thresholds

Hilangkan hardcoded value.

---

# Safe Cleanup Order

## Step 1
Cleanup UI

---

## Step 2
Cleanup analyzer

---

## Step 3
Cleanup widgets

---

## Step 4
Deprecate KPI fields

---

## Step 5
Safe migration cleanup

---

# Success Criteria

Refactor dianggap berhasil jika:

- Vessel Plan menjadi planning-only module
- SOP validation menjadi fokus utama
- UI lebih sederhana
- KPI non-planning hilang
- Workflow tetap stabil
- Snapshot & review tetap aman

---

# Non Goals

Refactor ini TIDAK mencakup:
- redesign Voyage
- execution monitoring
- SLA engine
- logistics dashboard

Fokus hanya:
> Vessel Plan module
