# WORKFLOW — VOYAGE OPERATIONAL FLOW

## REAL OPERATIONAL FLOW

Shipping Line Schedule
→ Internal Draft Vessel Plan
→ Operational Analysis
→ Sent to TAM
→ TAM Final Approved Schedule
→ Generate Voyage
→ Operational Execution
→ Monitoring / Shipment / KPI

---

# FLOW DETAIL

## 1. Shipping Line Schedule

Internal ops menerima jadwal kapal dari:
- Tanto
- Meratus
- dll

Data awal:
- vessel
- ETD
- ETA
- route
- voyage number

Masih berupa:
- draft
- tentative
- belum customer-approved

---

## 2. Draft Vessel Plan

Internal ops membuat:
Draft Vessel Plan

Tujuan:
- prepare monthly operation
- evaluate vessel spacing
- evaluate dwelling
- evaluate route timing

Status:
- editable
- planning only
- belum execution

---

## 3. Operational Analysis

Ops melakukan:
- dwelling analysis
- spacing validation
- vessel availability check
- route validation

Jika tidak feasible:
- revisi draft
- cari kapal alternatif

---

## 4. Sent To TAM

Draft vessel plan dikirim ke TAM.

TAM:
- review
- adjust
- approve final operational commitment

---

## 5. Final Approved Schedule

Schedule final dari TAM menjadi:
canonical operational commitment.

Hasil final:
- vessel
- ETD
- ETA
- voyage number
- cargo allocation

---

## 6. Generate Voyage

Approved schedule digenerate menjadi:
Voyage.

Voyage menjadi:
- operational execution object
- canonical transport object

---

## 7. Operational Execution

Operational execution berjalan:
- ATB
- Closing
- ATD
- ATA
- SLA
- KPI

---

## 8. Monitoring & Shipment

Monitoring Vessel:
consume Voyage.

Shipment:
attach ke Voyage.

KPI:
consume Voyage operational data.