# MAY 2026 OPERATIONAL SCENARIO

## OBJECTIVE

Implement real operational flow using May 2026 operational dataset.

This scenario replaces:
- dummy schedules
- fake monitoring lifecycle
- artificial KPI simulation

---

# REAL OPERATIONAL FLOW

## 1. Shipping Line Draft

Shipping lines provide:
- vessel
- ETD
- ETA
- voyage number
- route

Examples:
- Tanto
- Meratus

---

## 2. Internal Draft Vessel Plan

JSS internal ops:
- creates draft vessel plan
- validates spacing
- validates dwelling
- evaluates availability

Draft status:
- editable
- tentative
- not operational yet

---

## 3. Sent To TAM

Draft vessel plan sent to TAM.

TAM:
- reviews
- adjusts
- confirms final operational commitment

---

## 4. Final Approved Schedule

Approved schedule becomes:
canonical monthly operational commitment.

Approved schedule contains:
- final vessel
- final voyage
- final ETD
- final ETA

---

## 5. Generate Voyage

Approved schedule generates:
Voyage Registry records.

Voyage becomes:
- operational execution object
- shipment attachment source
- monitoring source
- KPI source

---

## 6. Monitoring Execution

Monitoring Vessel reads:
- voyage operational status
- ETA delays
- readiness
- SLA
- operational anomalies

---

## 7. Shipment Execution

Shipments attach to:
- Voyage only

Shipment execution consumes:
- voyage number
- vessel
- route
- ETD
- ETA

---

# MAY 2026 DATASET

Dataset source:
real May 2026 operational spreadsheet.

---

# SAMPLE DATA

| ETD | ETA | Vessel | Cargo | Voyage |
|---|---|---|---|---|
| 07-May | 21-May | KM Tanto Sejahtera V.154 | 58 | VOY179MVLMNDJSS |
| 08-May | 22-May | KM Tanto Cahaya V.384 | 7 | VOY151TSLMNDJSS |
| 13-May | 27-May | KM Teto Jaya V.309 | 57 | VOY180MVIMNDJSS |

---

# FINAL OBJECTIVE

After implementation:
- system reflects real operational lifecycle
- monitoring becomes operationally realistic
- voyage registry becomes canonical
- shipment execution becomes voyage-driven