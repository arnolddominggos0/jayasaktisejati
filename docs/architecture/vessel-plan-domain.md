# Vessel Plan Domain

## Purpose

Vessel Plan adalah modul:
> perencanaan dan approval jadwal kapal.

Modul ini digunakan untuk:
- menyusun draft jadwal kapal
- melakukan validasi continuity kapal
- melakukan review & approval
- menghasilkan final vessel schedule

Modul ini fokus pada:
> vessel scheduling workflow

Bukan:
> operational monitoring atau logistics KPI.

---

# Source of Truth

Dokumen acuan:
- SOP Excel "Procedure Vessel Plan"

Core SOP:
1. Request jadwal kapal
2. Terima draft jadwal
3. Analisa jadwal
4. Validasi gap antar kapal
5. Revisi jika gap > 6 hari
6. Rekap jadwal
7. Submit draft
8. Finalisasi

---

# Included Responsibilities

## Schedule Planning

- Shipping line
- Vessel assignment
- ETD
- ETA
- Sailing days

---

## SOP Validation

- ETD gap validation
- Max gap calculation
- Continuity validation
- SOP violation summary

---

## Workflow

- Draft
- Sent
- Revision
- Final

---

## Review & Audit

- Snapshot draft
- Snapshot final
- Review history
- Approval log

---

## Communication

- WhatsApp submission
- Customer feedback tracking

---

# Out of Scope

Berikut bukan domain Vessel Plan:

## Operational Monitoring
- actual ATD
- actual ATA
- delay tracking
- SLA monitoring
- operational checkpoint

---

## Logistics KPI
- dwelling KPI
- dooring KPI
- cargo KPI
- delivery KPI
- warehouse KPI

---

## Execution Tracking
- cargo actual
- shipment monitoring
- operational exception

Semua domain di atas:
bukan tanggung jawab Vessel Plan.

---

# Workflow Lifecycle

Draft
→ Sent
→ Revision
→ Final

---

## Draft

- editable
- dapat tambah/edit jadwal
- dapat submit jika SOP valid

---

## Sent

- waiting approval
- tidak editable
- dapat finalize atau reject

---

## Revision

- editable
- memerlukan revisi jadwal

---

## Final

- locked
- final approved schedule

---

# Core Business Rule

## ETD Gap Validation

Rule utama SOP:

```text
Gap antar kapal <= 6 hari
```

Jika:
- gap > 6 hari

Maka:
- status = PERLU REVISI

---

# Allowed Metrics

Metric yang diperbolehkan di planning layer:

- sailing_days
- sailing_avg
- etd_gap
- max_gap
- schedule_count

---

# Forbidden Metrics

Metric berikut tidak boleh muncul di planning layer:

- dwelling
- dooring
- logistics SLA
- cargo KPI
- delay KPI
- monitoring KPI

---

# UI Principles

UI Vessel Plan harus fokus pada:

- jadwal kapal
- ETD / ETA
- continuity kapal
- SOP validation
- approval status

Bukan:
- analytics operasional
- dashboard execution
- logistics monitoring

---

# Architectural Principles

## 1. SOP Driven

Semua validation mengikuti SOP Vessel Plan.

---

## 2. Planning Only

Vessel Plan hanya menangani planning workflow.

---

## 3. Operational Simplicity

UI harus sederhana dan mudah dipahami user operasional.

---

## 4. Auditability

Semua approval & revision harus tercatat.

---

## 5. Clean Domain

Planning layer tidak boleh tercampur dengan execution layer.
