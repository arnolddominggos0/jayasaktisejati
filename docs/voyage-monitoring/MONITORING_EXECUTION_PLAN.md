# MONITORING EXECUTION PLAN

## PURPOSE

Define canonical operational monitoring workflow.

Monitoring must become:
- operational execution monitoring
- anomaly detection
- ETA risk visibility
- readiness validation
- operational escalation system

NOT:
- passive table display
- CRUD checklist
- manual spreadsheet replacement

---

# MONITORING PRINCIPLE

Monitoring reads:
- Voyage ONLY

Monitoring does NOT read:
- VesselPlan
- draft schedule
- tentative planning data

Voyage is the canonical operational object.

---

# OPERATIONAL MONITORING FLOW

Voyage Created
→ Readiness Monitoring
→ Departure Monitoring
→ Sailing Monitoring
→ Delay Detection
→ Escalation
→ Resolution
→ KPI Evaluation

---

# 1. MONITORING LAYERS

## LAYER 1 — READINESS

Focus:
- operational readiness before departure

Checks:
- cargo readiness
- vessel readiness
- document readiness
- port readiness

---

## LAYER 2 — EXECUTION

Focus:
- actual operational execution

Checks:
- ATB
- Closing
- ATD
- ATA

---

## LAYER 3 — ANOMALY

Focus:
- operational deviation

Checks:
- ETA risk
- ETD change
- vessel issue
- port congestion
- cargo delay

---

## LAYER 4 — ESCALATION

Focus:
- operational disruption handling

Actions:
- create delay case
- request approval
- vessel substitution
- ETA revision
- customer communication

---

# 2. READINESS TIMELINE

## MONITORING WINDOWS

| WINDOW | PURPOSE |
|---|---|
| D-7 | Initial readiness |
| D-3 | Operational confirmation |
| D-2 | Readiness validation |
| D-1 | Final readiness |
| H-1 | Departure validation |

---

# 3. READINESS STATUS

## STATUS

### CLEAR

Operationally safe.

No anomaly detected.

---

### MONITOR

Potential operational issue exists.

Requires observation.

---

### ACTION REQUIRED

Operational issue requires immediate action.

---

### WAITING CONFIRMATION

Awaiting:
- TAM confirmation
- shipping line confirmation
- operational approval

---

# 4. MONITORING SIGNALS

## ETA RISK

Trigger:
- sailing delay
- congestion
- weather
- vessel issue

Impact:
- KPI risk
- SLA risk
- customer impact

---

## ETD CHANGE

Trigger:
- vessel readiness issue
- cargo readiness issue
- operational disruption

Impact:
- operational replanning

---

## VESSEL ISSUE

Examples:
- engine issue
- maintenance
- vessel substitution
- operational breakdown

---

## PORT CONGESTION

Examples:
- berth waiting
- loading queue
- crane issue

---

## DOCUMENT ISSUE

Examples:
- incomplete manifest
- customs delay
- operational clearance issue

---

# 5. MONITORING MATRIX

## PURPOSE

Provide operational visibility.

---

## ROWS

Voyage

---

## COLUMNS

- D-7
- D-3
- D-2
- D-1
- H-1

---

## CELL STATUS

GREEN:
- CLEAR

YELLOW:
- MONITOR

RED:
- ACTION REQUIRED

BLUE:
- WAITING CONFIRMATION

---

# 6. DELAY ESCALATION FLOW

## FLOW

Potential Delay
→ Monitoring Alert
→ Delay Review
→ Delay Case Opened
→ TAM Confirmation
→ Operational Resolution
→ KPI Recalculation

---

# 7. DELAY CASE OWNERSHIP

## DELAY CASE BELONGS TO

Voyage

NOT:
- VesselPlan
- draft schedule

---

## DELAY CASE CONTAINS

- chronology
- operational issue
- ETD revision
- ETA revision
- vessel substitution
- approval history
- communication history

---

# 8. VESSEL SUBSTITUTION FLOW

## FLOW

Issue Detected
→ Alternative Voyage Search
→ TAM Review
→ Approval
→ Shipment Reassignment
→ Monitoring Update

---

# 9. KPI RELATIONSHIP

Monitoring impacts:
- OTB
- OTD
- OTA
- dwell
- SLA

Monitoring is the operational source of KPI anomalies.

---

# 10. MONITORING DATA OWNERSHIP

## SOURCE OF TRUTH

| DATA | OWNER |
|---|---|
| ETD | Voyage |
| ETA | Voyage |
| ATA | Voyage |
| ATD | Voyage |
| delay reason | Voyage |
| readiness status | VesselCheck |
| delay escalation | VesselCheckCase |

---

# 11. OPERATIONAL EVENTS

## EVENTS

### VoyageCreated

Triggers:
- readiness monitoring

---

### VoyageDelayed

Triggers:
- delay escalation
- KPI recalculation

---

### ETAChanged

Triggers:
- monitoring alert
- shipment impact review

---

### VesselSubstituted

Triggers:
- shipment reassignment review

---

# 12. ALERT PRIORITY

## LOW

Observation only.

---

## MEDIUM

Requires operational review.

---

## HIGH

Requires escalation.

---

## CRITICAL

Requires immediate operational intervention.

---

# 13. EXECUTIVE VISIBILITY

Executives should see:
- delayed voyages
- ETA risk
- SLA risk
- operational bottlenecks
- branch performance
- shipping line performance

NOT:
- raw operational tables

---

# 14. MAY 2026 SCENARIO ROLE

May 2026 dataset must validate:
- readiness flow
- monitoring realism
- delay escalation
- ETA risk
- operational KPI

---

# 15. FINAL TARGET

Monitoring becomes:
- operational intelligence layer

NOT:
- passive operational checklist

System should behave like:
- logistics operational control tower.