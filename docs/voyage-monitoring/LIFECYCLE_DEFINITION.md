# LIFECYCLE DEFINITION

## PURPOSE

Define lifecycle states for:
- Vessel Plan
- Voyage
- Monitoring
- Delay Case
- Shipment
- KPI evaluation

This document standardizes:
- operational status
- transitions
- allowed actions
- ownership boundaries

---

# 1. VESSEL PLAN LIFECYCLE

## PURPOSE

Vessel Plan represents:
- internal planning workspace
- draft operational coordination

It is NOT execution.

---

## STATUS FLOW

Draft
→ Review
→ Sent To TAM
→ Revision
→ Final Approved
→ Archived

---

## STATUS DEFINITIONS

### Draft

Initial planning state.

Allowed:
- add vessel
- edit ETD
- edit ETA
- edit route
- remove schedule

---

### Review

Internal operational review.

Validation:
- dwelling
- vessel spacing
- operational feasibility

---

### Sent To TAM

Draft submitted to TAM.

Allowed:
- TAM review
- TAM revision request

Locked:
- operational deletion

---

### Revision

TAM requested schedule revision.

Allowed:
- modify draft
- replace vessel
- revise ETD

---

### Final Approved

Approved operational commitment.

Triggers:
- Voyage generation

Locked:
- operational mutation
- historical overwrite

---

### Archived

Historical planning record.

Read-only.

---

# 2. VOYAGE LIFECYCLE

## PURPOSE

Voyage is the canonical operational execution object.

---

## STATUS FLOW

Planned
→ Ready
→ Active
→ Delayed
→ Completed
→ Archived

Alternative:
Planned
→ Cancelled

---

## STATUS DEFINITIONS

### Planned

Generated from Final Vessel Plan.

No operational execution yet.

---

### Ready

Operationally ready.

Requirements:
- ETB confirmed
- cargo readiness confirmed
- operational validation complete

---

### Active

Voyage departed.

Triggers:
- ATD recorded

Monitoring active.

---

### Delayed

Operational disruption detected.

Triggers:
- ETD change
- ETA risk
- vessel issue
- port congestion

Requires:
- monitoring escalation
- delay handling workflow

---

### Completed

Voyage execution complete.

Triggers:
- ATA recorded

KPI finalized.

---

### Archived

Historical operational record.

Immutable.

---

### Cancelled

Voyage execution cancelled.

Must preserve:
- historical audit
- delay reason
- operational traceability

---

# 3. MONITORING STATUS LIFECYCLE

## PURPOSE

Monitoring validates operational readiness and execution risk.

---

## STATUS FLOW

On Schedule
→ Delay Risk
→ ETD Changed
→ Waiting Confirmation
→ Resolved

Alternative:
On Schedule
→ Operational Issue
→ Delay Case

---

## STATUS DEFINITIONS

### On Schedule

Execution aligned with operational commitment.

---

### Delay Risk

Potential operational disruption detected.

Examples:
- port congestion
- cargo readiness issue
- vessel delay

---

### ETD Changed

Operational departure schedule changed.

Requires:
- TAM communication
- escalation review

---

### Waiting Confirmation

Waiting:
- TAM confirmation
- shipping line confirmation
- operational approval

---

### Resolved

Issue resolved.

Monitoring closed.

---

### Operational Issue

Escalated operational anomaly.

Triggers:
- Delay Case creation

---

# 4. DELAY CASE LIFECYCLE

## PURPOSE

Delay Case manages operational disruption workflow.

---

## STATUS FLOW

Open
→ Monitoring
→ Waiting TAM
→ Approved
→ Resolved

Alternative:
Open
→ Rejected

---

## STATUS DEFINITIONS

### Open

Delay case created.

Triggers:
- ETD change
- operational anomaly
- vessel issue

---

### Monitoring

Operational review ongoing.

Activities:
- alternative vessel evaluation
- ETA recalculation
- operational assessment

---

### Waiting TAM

Waiting TAM operational approval.

---

### Approved

Operational revision approved.

Triggers:
- voyage update
- monitoring update

---

### Resolved

Operational issue closed.

---

### Rejected

Revision rejected.

Requires:
- operational fallback
- re-planning

---

# 5. SHIPMENT LIFECYCLE

## PURPOSE

Shipment executes cargo movement using Voyage operational commitment.

---

## STATUS FLOW

Draft
→ Planned
→ Assigned
→ In Transit
→ Delivered
→ Closed

Alternative:
Draft
→ Cancelled

---

## STATUS DEFINITIONS

### Draft

Initial shipment request.

Editable.

---

### Planned

Assigned to operational voyage.

Voyage becomes authoritative source.

---

### Assigned

Operational execution assigned.

Examples:
- container assigned
- depot assigned
- driver assigned

---

### In Transit

Cargo operationally moving.

---

### Delivered

Cargo received by destination.

---

### Closed

Shipment completed.

Immutable.

---

### Cancelled

Shipment execution cancelled.

Audit trail required.

---

# 6. KPI LIFECYCLE

## PURPOSE

KPI evaluates operational execution quality.

---

## STATUS FLOW

Pending
→ Calculating
→ Finalized
→ Archived

---

## STATUS DEFINITIONS

### Pending

Voyage not completed yet.

---

### Calculating

Operational execution being evaluated.

---

### Finalized

KPI locked.

Examples:
- OTB
- OTD
- OTA
- SLA

---

### Archived

Historical KPI record.

Immutable.

---

# 7. IMMUTABILITY RULES

## VESSEL PLAN

Editable until:
- Final Approved

---

## VOYAGE

Mutable until:
- Active

After Active:
- ETD changes require Delay Case
- operational changes require monitoring escalation

---

## SHIPMENT

Shipment cannot override:
- Voyage ETD
- Voyage ETA
- Voyage vessel
- Voyage route

---

# 8. CANONICAL TRANSITION FLOW

Shipping Line Draft
→ Vessel Plan Draft
→ TAM Approval
→ Final Vessel Plan
→ Generate Voyage
→ Voyage Monitoring
→ Delay Case
→ Shipment Execution
→ KPI Finalization

---

# 9. MAY 2026 BASELINE

May 2026 operational dataset validates:
- Vessel lifecycle
- Monitoring lifecycle
- Delay handling
- Shipment execution
- KPI calculation