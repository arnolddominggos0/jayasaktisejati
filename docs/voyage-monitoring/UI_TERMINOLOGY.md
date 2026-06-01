# UI TERMINOLOGY STANDARD

## PURPOSE

Standardize all:
- menu naming
- badge wording
- operational labels
- monitoring terminology
- workflow naming

This document aligns UI terminology with:
- real operational workflow
- May 2026 operational dataset
- TAM operational process
- Voyage canonical architecture

---

# 1. MAIN MODULE TERMINOLOGY

| CURRENT | STANDARDIZED |
|---|---|
| Rencana Jadwal Kapal | Vessel Planning |
| Monitoring Kapal TAM | Voyage Monitoring |
| Pemeriksaan Jadwal Kapal | Voyage Readiness Check |
| Tindak Lanjut Perubahan Jadwal | Delay Case Management |
| Voyage Registry | Voyage Registry |
| Shipping Schedule | Shipping Schedule (Legacy) |

---

# 2. VESSEL PLAN TERMINOLOGY

## PURPOSE

Planning workspace.

NOT operational execution.

---

## STATUS LABELS

| STATUS | LABEL |
|---|---|
| draft | Draft |
| review | Internal Review |
| sent_to_tam | Sent To TAM |
| revision | Need Revision |
| final | Final Approved |
| archived | Archived |

---

## BADGE COLORS

| STATUS | COLOR |
|---|---|
| Draft | gray |
| Internal Review | warning |
| Sent To TAM | info |
| Need Revision | danger |
| Final Approved | success |
| Archived | gray |

---

# 3. VOYAGE TERMINOLOGY

## PURPOSE

Canonical operational execution object.

---

## STATUS LABELS

| STATUS | LABEL |
|---|---|
| planned | Planned |
| ready | Ready |
| active | Sailing |
| delayed | Delayed |
| completed | Completed |
| archived | Archived |
| cancelled | Cancelled |

---

## BADGE COLORS

| STATUS | COLOR |
|---|---|
| Planned | gray |
| Ready | info |
| Sailing | primary |
| Delayed | danger |
| Completed | success |
| Archived | gray |
| Cancelled | danger |

---

# 4. MONITORING TERMINOLOGY

## PURPOSE

Operational readiness validation.

---

## MONITORING STATUS

| CURRENT | STANDARDIZED |
|---|---|
| Aman | CLEAR |
| Aman — Sesuai Jadwal | ON SCHEDULE |
| Perlu Perhatian | MONITOR |
| ETD Berubah | ETD CHANGED |
| Perlu Dibuat | ACTION REQUIRED |
| Menunggu | WAITING CONFIRMATION |

---

## BADGE COLORS

| STATUS | COLOR |
|---|---|
| ON SCHEDULE | success |
| CLEAR | success |
| MONITOR | warning |
| ETD CHANGED | danger |
| ACTION REQUIRED | danger |
| WAITING CONFIRMATION | info |

---

# 5. DELAY CASE TERMINOLOGY

## PURPOSE

Operational disruption management.

---

## CASE STATUS

| STATUS | LABEL |
|---|---|
| open | Open |
| monitoring | Monitoring |
| waiting_tam | Waiting TAM |
| approved | Approved |
| resolved | Resolved |
| rejected | Rejected |

---

## CASE ACTIONS

| CURRENT | STANDARDIZED |
|---|---|
| Buat Tindak Lanjut | Create Delay Case |
| Perlu Dibuat | Action Required |
| Menunggu Konfirmasi | Waiting TAM Confirmation |
| Evaluasi Kapal Pengganti | Alternative Vessel Review |
| Revisi Jadwal | Reschedule Required |

---

# 6. KPI TERMINOLOGY

## PURPOSE

Operational execution measurement.

---

## KPI LABELS

| CURRENT | STANDARDIZED |
|---|---|
| TOTAL KPI | TOTAL VOYAGE |
| DRAFT KPI | DRAFT |
| FINAL KPI | FINAL |
| DEVIASI | AVG DWELLING |
| DW/SA/DR | DWELL / SAIL / DELAY |
| STATUS SOP | OPERATION STATUS |

---

# 7. KPI STATUS TERMINOLOGY

| STATUS | LABEL |
|---|---|
| valid | VALID |
| waiting | WAITING REVIEW |
| revision | NEED REVISION |
| final | FINALIZED |

---

# 8. OPERATIONAL KPI TERMINOLOGY

| KPI | DESCRIPTION |
|---|---|
| OTB | On Time Berthing |
| OTD | On Time Departure |
| OTA | On Time Arrival |
| DWELL | Port dwelling duration |
| SAILING DAYS | Voyage sailing duration |
| DELAY % | Delay occurrence percentage |

---

# 9. SHIPMENT TERMINOLOGY

## PURPOSE

Shipment execution consuming Voyage operational commitment.

---

## SHIPMENT STATUS

| STATUS | LABEL |
|---|---|
| draft | Draft |
| planned | Planned |
| assigned | Assigned |
| transit | In Transit |
| delivered | Delivered |
| closed | Closed |
| cancelled | Cancelled |

---

# 10. OPERATIONAL ACTION TERMINOLOGY

| ACTION | LABEL |
|---|---|
| approve | Approve |
| revise | Request Revision |
| finalize | Finalize Schedule |
| activate | Activate Voyage |
| escalate | Escalate Delay |
| resolve | Resolve Case |
| archive | Archive |

---

# 11. MAY 2026 ALIGNMENT

All UI wording must align with:
- May 2026 operational spreadsheet
- operational vessel workflow
- TAM approval process
- canonical Voyage architecture

---

# 12. FORBIDDEN TERMINOLOGY

Avoid:
- fake KPI wording
- generic dashboard wording
- technical database terminology in UI
- internal migration naming
- dummy operational labels

---

# 13. UI PRINCIPLES

UI must feel:
- operational
- logistics-driven
- voyage-centric
- monitoring-oriented
- execution-focused

NOT:
- ERP-generic
- admin-dashboard generic
- spreadsheet dump