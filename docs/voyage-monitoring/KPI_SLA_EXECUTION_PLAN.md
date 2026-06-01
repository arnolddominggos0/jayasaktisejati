# KPI SLA EXECUTION PLAN

## PURPOSE

Define canonical KPI and SLA architecture.

KPI must measure:
- real operational execution
- Voyage performance
- operational delay
- SLA achievement

KPI must NOT measure:
- planning drafts
- tentative schedules
- non-operational records

---

# KPI PRINCIPLE

KPI consumes:
- Voyage execution ONLY

Source:
- Voyage
- VoyageMilestone
- VesselCheck
- VesselCheckCase

---

# PRIMARY KPI

## OTB — ON TIME BERTHING

Measure:
actual berth arrival vs planned ETB

---

## OTD — ON TIME DEPARTURE

Measure:
ATD vs planned ETD

---

## OTA — ON TIME ARRIVAL

Measure:
ATA vs planned ETA

---

## DWELL

Measure:
berthing to departure duration

---

## SAILING DAYS

Measure:
ATD → ATA duration

---

## DELAY %

Measure:
delayed voyages vs total voyages

---

# SLA PRINCIPLE

SLA evaluates:
- operational execution quality

NOT:
- planning quality

---

# KPI OWNERSHIP

| KPI | SOURCE |
|---|---|
| OTB | Voyage |
| OTD | Voyage |
| OTA | Voyage |
| dwell | Voyage |
| sailing days | Voyage |
| readiness risk | VesselCheck |
| delay escalation | VesselCheckCase |

---

# KPI STATUS

GREEN:
- on target

YELLOW:
- monitor

RED:
- breach

---

# KPI RECALCULATION TRIGGERS

- ATB updated
- ATD updated
- ATA updated
- ETA revised
- delay case resolved

---

# EXECUTIVE KPI

- Total Voyage
- Active Voyage
- Completed Voyage
- Delayed Voyage
- OTB %
- OTD %
- OTA %
- Delay %
- Avg Dwell
- Avg Sailing Days

---

# FINAL TARGET

KPI becomes:
- operationally trustworthy
- executive-grade
- voyage-centric