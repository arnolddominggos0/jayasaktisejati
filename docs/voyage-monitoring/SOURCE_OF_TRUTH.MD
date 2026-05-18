# SOURCE OF TRUTH

## DOMAIN HIERARCHY

Shipping Line Schedule
→ source operational vessel availability

Draft Vessel Plan
→ source internal planning

Final Approved Schedule
→ source customer-approved commitment

Voyage
→ canonical operational execution object

Shipment
→ cargo execution attached to voyage

Monitoring Vessel
→ operational visibility layer

---

# IMPORTANT BOUNDARIES

## Vessel Plan

Vessel Plan:
- planning workspace
- editable
- negotiation layer

Vessel Plan is NOT:
- operational execution
- monitoring source
- shipment source

---

## Voyage

Voyage:
- finalized operational object
- approved operational commitment
- canonical execution schedule

Voyage is:
- immutable operational reference
- shared operational backbone

---

# VOYAGE IS CONSUMED BY

- Monitoring Vessel
- Shipment
- KPI
- Operational Timeline
- Delay History
- Readiness
- SLA Evaluation

---

# SHIPMENT RELATIONSHIP

Shipment must attach to:
Voyage.

Shipment cannot:
- create voyage
- override voyage ETD
- override voyage ETA
- override route

Shipment only consumes voyage operational schedule.

---

# MONITORING RELATIONSHIP

Monitoring Vessel:
- reads Voyage only
- does not read Vessel Plan directly

Monitoring reflects:
- actual operational execution
- delay anomalies
- KPI performance