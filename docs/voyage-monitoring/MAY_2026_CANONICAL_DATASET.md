# MAY 2026 — CANONICAL OPERATIONAL DATASET

## OBJECTIVE

Dataset Mei 2026 akan menjadi:
canonical operational dataset.

Tujuan:
- replace dummy data
- simulate real operational flow
- validate monitoring architecture
- validate voyage lifecycle

---

# DATASET SOURCE

Dataset berasal dari:
real operational spreadsheet Mei 2026.

---

# IMPORTANT RULES

## JSS = Canonical Voyage Number

Kolom JSS menjadi:

voyages.voyage_no

Contoh:
VOY179MVLMNDJSS

Voyage number internal lama tidak digunakan.

---

## LTS DIABAIKAN

Kolom LTS:
- tidak digunakan
- tidak disimpan
- bukan domain internal

Ignore total.

---

# FLOW

Shipping Line Schedule
→ Draft Vessel Plan
→ TAM Approval
→ Generate Voyage
→ Monitoring & Shipment

---

# ROUTE

Default route:
- POL = JKT
- POD = BTG

---

# REGISTRY STATUS

| Condition | Status |
|---|---|
| approved schedule | Planned |
| ATD exists | Active |
| ETA overdue | Delayed |
| ATA exists | Completed |

---

# MONITORING STATUS

| Condition | Monitoring |
|---|---|
| no ATD | Scheduled |
| ATD no ATA | Sailing |
| overdue ETA | Delayed |
| ATA exists | Done |

---

# SHIPMENT RELATIONSHIP

Shipment attaches to Voyage.

Shipment consumes:
- voyage_id
- voyage_no
- vessel
- route
- ETD
- ETA

---

# DATASET TARGET

Seeder must generate:
- vessel plans
- approved schedules
- voyages
- monitoring data
- KPI data
- readiness checkpoints
- operational timeline

---

# MAY 2026 SAMPLE DATA

| ETD | ETA | Vessel | Cargo | Voyage |
|---|---|---|---|---|
| 07-May | 21-May | KM Tanto Sejahtera V.154 | 58 | VOY179MVLMNDJSS |
| 08-May | 22-May | KM Tanto Cahaya V.384 | 7 | VOY151TSLMNDJSS |
| 13-May | 27-May | KM Teto Jaya V.309 | 57 | VOY180MVIMNDJSS |

---

# FINAL OBJECTIVE

After seed:
- Monitoring Vessel becomes operationally realistic
- Voyage Registry becomes canonical
- Shipment execution becomes voyage-based
- KPI becomes operationally valid