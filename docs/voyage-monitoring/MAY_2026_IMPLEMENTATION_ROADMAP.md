# MAY 2026 IMPLEMENTATION ROADMAP

## PURPOSE

Define implementation roadmap for:
- canonical Voyage architecture
- operational monitoring
- delay workflow
- KPI standardization
- shipment operational execution

This roadmap uses:
- May 2026 operational dataset
as the canonical operational baseline.

---

# IMPLEMENTATION PRINCIPLE

Implementation priority:

1. Architecture stabilization
2. Operational consistency
3. Monitoring realism
4. KPI accuracy
5. UI refinement
6. Executive analytics

Do NOT prioritize:
- cosmetic dashboard
- unnecessary automation
- generic ERP features

---

# PHASE 0 — ARCHITECTURE STABILIZATION

## STATUS

COMPLETED

---

## OBJECTIVE

Stabilize ownership boundaries.

---

## IMPLEMENTED

✓ Voyage canonicalization foundation  
✓ FK normalization  
✓ nullOnDelete strategy  
✓ VesselCheckCase cleanup  
✓ VoyageDelayLog fix  
✓ missing model restoration  
✓ VesselCheck voyage normalization  
✓ VesselCheckCase voyage relation  
✓ dangerous cascade cleanup  

---

## OUTPUT

System no longer has:
- critical cascade deletion risk
- broken monitoring relationship
- duplicate FK ambiguity

---

# PHASE 1 — MAY 2026 CANONICAL SEEDING

## STATUS

COMPLETED

---

## OBJECTIVE

Transform real May 2026 spreadsheet into:
- canonical operational dataset

---

## IMPLEMENTED

✓ VesselPlan generation  
✓ Voyage generation  
✓ ShippingSchedule transitional bridge  
✓ Shipment voyage consumption  
✓ VesselCheck operational simulation  
✓ real voyage numbering  
✓ real vessel schedule flow  

---

## OUTPUT

System now reflects:
- real operational lifecycle
- real voyage execution
- real shipment consumption
- real monitoring scenario

---

# PHASE 2 — OPERATIONAL TERMINOLOGY CLEANUP

## STATUS

NEXT PRIORITY

---

## OBJECTIVE

Align UI terminology with:
- operational workflow
- TAM operational process
- voyage-centric execution

---

## IMPLEMENTATION

### MODULES

- Vessel Planning
- Voyage Monitoring
- Voyage Readiness Check
- Delay Case Management

---

## CLEANUP TARGETS

### Replace generic wording

Examples:

OLD:
- Aman
- Perlu Perhatian
- TOTAL KPI
- Draft KPI

NEW:
- ON SCHEDULE
- MONITOR
- TOTAL VOYAGE
- FINALIZED

---

## OUTPUT

UI becomes:
- operationally realistic
- logistics-oriented
- monitoring-driven

---

# PHASE 3 — VOYAGE REGISTRY REFINEMENT

## OBJECTIVE

Make Voyage Registry the true operational center.

---

## IMPLEMENTATION

### ADD

- operational timeline
- milestone visualization
- delay indicators
- KPI badges
- readiness indicators

---

## REMOVE

- planning ambiguity
- duplicate operational ownership

---

## OUTPUT

Voyage Registry becomes:
- canonical operational cockpit

---

# PHASE 4 — MONITORING REFACTOR

## OBJECTIVE

Monitoring consumes:
- Voyage ONLY

---

## IMPLEMENTATION

### REFACTOR

- Monitoring Kapal TAM
- Pemeriksaan Jadwal Kapal
- VesselCheck
- VesselCheckCase

---

## REMOVE

Monitoring dependency on:
- VesselPlan
- draft schedule

---

## ADD

- delay escalation
- ETA risk
- operational anomaly detection
- readiness visualization

---

## OUTPUT

Monitoring becomes:
- operationally realistic
- anomaly-driven
- execution-focused

---

# PHASE 5 — DELAY CASE MANAGEMENT

## OBJECTIVE

Implement operational disruption workflow.

---

## FLOW

Delay Risk
→ Delay Case
→ TAM Confirmation
→ Alternative Vessel Review
→ Operational Approval
→ Resolution

---

## IMPLEMENTATION

### FEATURES

- ETD change request
- vessel substitution
- escalation workflow
- approval flow
- operational comments
- revision history

---

## OUTPUT

System supports:
- real operational disruption handling

---

# PHASE 6 — KPI IMPLEMENTATION

## OBJECTIVE

Implement canonical operational KPI.

---

## IMPLEMENTATION

### KPI

- OTB
- OTD
- OTA
- dwell
- sailing days
- delay percentage

---

## SOURCE

KPI consumes:
- Voyage execution ONLY

---

## OUTPUT

Dashboard becomes:
- operationally measurable
- trustworthy
- executive-ready

---

# PHASE 7 — SHIPPING SCHEDULE TRANSITION

## OBJECTIVE

Reduce ShippingSchedule dependency.

---

## IMPLEMENTATION

### PHASE 7A

Compatibility bridge.

ShippingSchedule delegates:
- ETD
- ETA
- vessel
- voyage_no
to Voyage.

---

### PHASE 7B

Read-through redirect.

All new code reads:
- Voyage

---

### PHASE 7C

Legacy cleanup.

Remove duplicated fields.

---

## OUTPUT

Voyage becomes:
- single operational source

---

# PHASE 8 — SHIPMENT EXECUTION NORMALIZATION

## OBJECTIVE

Shipment fully consumes Voyage.

---

## IMPLEMENTATION

### ENFORCE

Shipment cannot override:
- ETD
- ETA
- vessel
- route

---

## ADD

- operational sync validation
- voyage consistency validation

---

## OUTPUT

Shipment execution becomes:
- voyage-driven

---

# PHASE 9 — SLA & EXECUTIVE DASHBOARD

## OBJECTIVE

Build executive operational visibility.

---

## IMPLEMENTATION

### EXECUTIVE KPI

- voyage completion
- OTB %
- OTD %
- OTA %
- delay %
- operational SLA

---

## DASHBOARD

- executive summary
- operational heatmap
- delay analytics
- branch performance
- shipping line performance

---

## OUTPUT

System becomes:
- executive-operational platform

---

# PHASE 10 — AUTOMATION & ALERTING

## OBJECTIVE

Add operational intelligence.

---

## IMPLEMENTATION

### ALERTS

- ETA risk
- delay escalation
- SLA breach
- vessel readiness risk

---

## AUTOMATION

- KPI recalculation
- monitoring escalation
- TAM notification
- operational reminders

---

## OUTPUT

System becomes:
- proactive operational platform

---

# IMPLEMENTATION ORDER

1. Terminology cleanup
2. Voyage Registry refinement
3. Monitoring refactor
4. Delay workflow
5. KPI implementation
6. ShippingSchedule transition
7. Shipment normalization
8. Executive dashboard
9. Automation

---

# MAY 2026 ROLE

May 2026 dataset is:
- canonical operational simulation
- architecture validation dataset
- KPI validation dataset
- monitoring validation dataset

All testing should use:
- May 2026 baseline.

---

# FINAL TARGET

Planning:
- VesselPlan

Execution:
- Voyage

Monitoring:
- Monitoring + Delay Case

Consumption:
- Shipment

Measurement:
- KPI / SLA

Voyage remains the canonical operational center.