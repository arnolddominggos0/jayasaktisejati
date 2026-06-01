# VOYAGE UI REFACTOR PLAN

## PURPOSE

Refactor UI architecture to align with:
- canonical Voyage architecture
- operational logistics workflow
- monitoring-driven execution
- May 2026 operational dataset

Goal:
Transform current admin-style UI into:
- operational control tower

---

# UI PRINCIPLE

UI must feel:
- operational
- voyage-centric
- monitoring-focused
- execution-oriented
- logistics-driven

UI must NOT feel:
- generic ERP
- CRUD admin panel
- spreadsheet dump
- technical database interface

---

# 1. INFORMATION ARCHITECTURE

## TARGET STRUCTURE

Planning Layer
→ Vessel Planning

Execution Layer
→ Voyage Registry

Monitoring Layer
→ Voyage Monitoring
→ Voyage Readiness Check
→ Delay Case Management

Execution Consumption
→ Shipment Execution

Measurement Layer
→ KPI / SLA Dashboard

---

# 2. SIDEBAR RESTRUCTURE

## CURRENT ISSUE

Sidebar structure still reflects:
- technical modules
- database ownership
- historical implementation

Not operational workflow.

---

## TARGET SIDEBAR

### OPERATIONS

- Vessel Planning
- Voyage Registry
- Voyage Monitoring
- Voyage Readiness Check
- Delay Case Management

---

### EXECUTION

- Shipment Execution
- Depot Operation
- Container Movement

---

### PERFORMANCE

- KPI Dashboard
- SLA Dashboard
- Delay Analytics

---

### MASTER DATA

- Vessel
- Shipping Line
- Port
- Customer

---

# 3. VESSEL PLANNING UI

## PURPOSE

Planning workspace.

NOT operational execution.

---

## UI STYLE

Should feel:
- collaborative
- scheduling-oriented
- draft-focused

---

## REMOVE

- KPI dominance
- operational execution status
- monitoring terminology

---

## ADD

### HEADER

- Period
- Route
- TAM approval status
- revision indicator

---

### VISUALIZATION

- vessel timeline
- spacing visualization
- route grouping
- draft vs final comparison

---

## STATUS BADGES

Draft
Review
Sent To TAM
Need Revision
Final Approved

---

# 4. VOYAGE REGISTRY UI

## PURPOSE

Canonical operational cockpit.

---

## UI STYLE

Should feel:
- operational
- execution-centric
- timeline-driven

---

## ADD

### VOYAGE TIMELINE

ETB
→ Closing
→ ATD
→ Sailing
→ ATA

---

### OPERATIONAL BADGES

- ON SCHEDULE
- DELAYED
- ACTIVE
- COMPLETED

---

### KPI INDICATORS

- OTB
- OTD
- OTA
- dwell
- delay risk

---

### QUICK ACTIONS

- open monitoring
- create delay case
- view shipment execution

---

# 5. VOYAGE MONITORING UI

## PURPOSE

Operational anomaly cockpit.

---

## UI STYLE

Should feel:
- real-time
- risk-focused
- anomaly-driven

---

## REMOVE

- generic table feeling
- passive monitoring display

---

## ADD

### MONITORING MATRIX

Rows:
- voyage

Columns:
- D-7
- D-3
- D-2
- D-1
- H-1

---

### STATUS COLORS

GREEN:
- ON SCHEDULE

YELLOW:
- MONITOR

RED:
- ACTION REQUIRED

BLUE:
- WAITING CONFIRMATION

---

### ALERT PANELS

- ETA risk
- ETD change
- vessel issue
- delay escalation

---

# 6. VOYAGE READINESS CHECK UI

## PURPOSE

Operational validation room.

---

## UI STYLE

Should feel:
- operational checklist
- pre-departure validation

---

## ADD

### VALIDATION GROUPS

- cargo readiness
- vessel readiness
- port readiness
- documentation readiness

---

### RESULT STATUS

CLEAR
MONITOR
ACTION REQUIRED

---

# 7. DELAY CASE MANAGEMENT UI

## PURPOSE

Operational escalation workflow.

---

## UI STYLE

Should feel:
- incident management
- operational escalation center

---

## CASE FLOW VISUALIZATION

Delay Risk
→ Open Case
→ TAM Confirmation
→ Alternative Vessel
→ Resolution

---

## ADD

### CASE COMPONENTS

- operational chronology
- ETD revision history
- vessel substitution
- approval history
- monitoring escalation

---

# 8. SHIPMENT EXECUTION UI

## PURPOSE

Operational cargo execution.

---

## UI STYLE

Should feel:
- voyage-driven
- execution-oriented

---

## ADD

### VOYAGE GROUPING

Group shipment by:
- voyage
- vessel
- route

---

## DISPLAY

- voyage status
- ETA risk
- operational delay
- container readiness

---

# 9. KPI DASHBOARD UI

## PURPOSE

Operational performance measurement.

---

## UI STYLE

Should feel:
- executive operational dashboard

NOT:
- fake analytics
- generic charts

---

## PRIMARY KPI

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

## VISUALIZATION

- operational trend
- voyage heatmap
- shipping line comparison
- branch comparison

---

# 10. COLOR SYSTEM

## OPERATIONAL STATUS COLORS

| STATUS | COLOR |
|---|---|
| ON SCHEDULE | green |
| ACTIVE | blue |
| MONITOR | yellow |
| DELAYED | red |
| COMPLETED | emerald |
| WAITING CONFIRMATION | cyan |

---

# 11. TABLE DESIGN PRINCIPLE

## REMOVE

- excessive CRUD feel
- raw database field exposure
- technical naming

---

## ADD

- operational grouping
- timeline context
- KPI context
- anomaly visibility

---

# 12. MOBILE / RESPONSIVE PRIORITY

Priority:
- monitoring readability
- operational badge visibility
- timeline visibility

Not:
- full CRUD parity

---

# 13. MAY 2026 ALIGNMENT

All UI validation must use:
- May 2026 dataset

Reason:
- operational realism
- monitoring realism
- KPI realism
- delay realism

---

# 14. FINAL TARGET EXPERIENCE

User should feel like operating:
- shipping operation control tower

NOT:
- generic Laravel admin panel

---

# 15. FINAL UI ARCHITECTURE

Planning Workspace
→ Vessel Planning

Operational Cockpit
→ Voyage Registry

Operational Monitoring
→ Voyage Monitoring
→ Readiness Check
→ Delay Case

Operational Execution
→ Shipment Execution

Operational Measurement
→ KPI / SLA Dashboard

Voyage remains the operational center.