# KPI DEFINITION

## PURPOSE

Define canonical KPI formulas for:
- Voyage execution
- Monitoring performance
- Operational SLA
- Delay evaluation
- TAM operational reporting

This document standardizes:
- KPI ownership
- calculation formula
- operational interpretation
- achievement thresholds

---

# 1. KPI OWNERSHIP

## SOURCE OF TRUTH

All KPI calculations consume:
- Voyage
- Voyage Milestone
- Monitoring Result
- Delay Case
- Shipment Execution

KPI must NOT consume:
- Vessel Plan draft data
- draft operational schedules

---

# 2. OPERATIONAL KPI GROUPS

KPI groups:

1. Operational Timeliness
2. Voyage Execution
3. Monitoring Quality
4. Delay Performance
5. Shipment Execution
6. SLA Achievement

---

# 3. OTB — ON TIME BERTHING

## PURPOSE

Measure vessel berthing punctuality.

---

## FORMULA

OTB = ATB <= ETB

---

## RESULT

| CONDITION | RESULT |
|---|---|
| ATB <= ETB | PASS |
| ATB > ETB | FAIL |

---

## SOURCE

Voyage:
- etb
- atb_at

---

# 4. OTD — ON TIME DEPARTURE

## PURPOSE

Measure vessel departure punctuality.

---

## FORMULA

OTD = ATD <= ETD

---

## RESULT

| CONDITION | RESULT |
|---|---|
| ATD <= ETD | PASS |
| ATD > ETD | FAIL |

---

## SOURCE

Voyage:
- etd
- atd_at

---

# 5. OTA — ON TIME ARRIVAL

## PURPOSE

Measure vessel arrival punctuality.

---

## FORMULA

OTA = ATA <= ETA

---

## RESULT

| CONDITION | RESULT |
|---|---|
| ATA <= ETA | PASS |
| ATA > ETA | FAIL |

---

## SOURCE

Voyage:
- eta
- ata_at

---

# 6. DWELL TIME

## PURPOSE

Measure port dwelling duration.

---

## FORMULA

DWELL = ATD - ATB

---

## UNIT

Hours

---

## SOURCE

Voyage:
- atb_at
- atd_at

---

# 7. SAILING DAYS

## PURPOSE

Measure operational sailing duration.

---

## FORMULA

SAILING DAYS = ATA - ATD

---

## UNIT

Days

---

## SOURCE

Voyage:
- atd_at
- ata_at

---

# 8. DELAY PERCENTAGE

## PURPOSE

Measure operational disruption frequency.

---

## FORMULA

DELAY % =
Delayed Voyages
÷
Total Voyages

---

## SOURCE

Voyage:
- registry_status

---

# 9. MONITORING ESCALATION RATE

## PURPOSE

Measure operational monitoring quality.

---

## FORMULA

ESCALATION RATE =
Delay Cases
÷
Monitoring Alerts

---

## SOURCE

- VesselCheck
- VesselCheckCase

---

# 10. SHIPMENT EXECUTION KPI

## PURPOSE

Measure shipment operational execution.

---

## KPI TYPES

| KPI | FORMULA |
|---|---|
| Voyage Utilization | cargo_actual / cargo_plan |
| Shipment Completion | delivered / total shipment |
| Voyage Shipment Accuracy | shipment voyage consistency |

---

# 11. SLA KPI

## PURPOSE

Measure contractual operational quality.

---

## SLA TYPES

| KPI | DESCRIPTION |
|---|---|
| SLA OTB | Berthing SLA |
| SLA OTD | Departure SLA |
| SLA OTA | Arrival SLA |
| SLA Delay Response | Delay handling speed |
| SLA Monitoring | Monitoring responsiveness |

---

# 12. KPI ACHIEVEMENT THRESHOLDS

## OTB / OTD / OTA

| ACHIEVEMENT | THRESHOLD |
|---|---|
| Excellent | >= 95% |
| Good | >= 90% |
| Warning | >= 80% |
| Critical | < 80% |

---

# 13. KPI STATUS

| STATUS | MEANING |
|---|---|
| Pending | Voyage not completed |
| Calculating | KPI in progress |
| Finalized | KPI locked |
| Archived | Historical KPI |

---

# 14. KPI FREEZE RULES

## KPI becomes immutable after:

- Voyage Completed
AND
- KPI Finalized

---

# 15. KPI CALCULATION TIMING

| EVENT | ACTION |
|---|---|
| Voyage Generated | KPI Pending |
| ATD Recorded | OTD calculated |
| ATA Recorded | OTA calculated |
| Voyage Completed | KPI finalized |
| Delay Case Opened | Delay KPI recalculated |

---

# 16. DASHBOARD KPI

## EXECUTIVE DASHBOARD

Must display:
- Total Voyage
- Active Voyage
- Delayed Voyage
- OTB %
- OTD %
- OTA %
- Delay %
- Avg Dwell
- Avg Sailing Days

---

# 17. MAY 2026 KPI BASELINE

May 2026 dataset becomes:
- canonical KPI validation dataset
- dashboard baseline dataset
- monitoring KPI simulation dataset

All KPI testing should use:
- May 2026 operational data.

---

# 18. KPI ARCHITECTURE RULE

KPI is derived from:
- operational execution

KPI is NOT:
- manually editable
- planning estimation
- draft prediction

Voyage execution remains authoritative source.