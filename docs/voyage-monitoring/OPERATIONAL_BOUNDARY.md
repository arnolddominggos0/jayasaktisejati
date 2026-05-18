# OPERATIONAL BOUNDARY

## PURPOSE

Define strict ownership boundaries between:
- Vessel Plan
- Voyage
- Monitoring
- Shipment
- KPI
- Delay Handling

This document is the canonical operational architecture reference.

---

# 1. VESSEL PLAN

## PURPOSE

Vessel Plan is:
- internal planning workspace
- draft operational schedule
- approval coordination layer

Vessel Plan is NOT:
- operational execution source
- monitoring source
- shipment source
- KPI source

---

## OWNERSHIP

Owned by:
- JSS Operational Planning

Consumes:
- Shipping Line draft schedules

Produces:
- approved operational commitment

---

## STATUS LIFECYCLE

Draft
→ Review
→ Sent To TAM
→ Revision
→ Final Approved

---

## OUTPUT

Final Vessel Plan generates:
- Voyage Registry records

---

# 2. VOYAGE REGISTRY

## PURPOSE

Voyage is the canonical operational execution object.

Voyage becomes:
- shipment attachment source
- monitoring source
- SLA source
- KPI source
- operational tracking source

---

## OWNERSHIP

Owned by:
- Operational Execution

Generated from:
- Final approved Vessel Plan

---

## CANONICAL FIELDS

Voyage owns:
- voyage_no
- vessel
- ETD
- ETA
- ATD
- ATA
- route
- cargo_plan
- cargo_actual
- operational status

---

## IMMUTABILITY

After operational activation:
- Shipment cannot override voyage data
- Monitoring cannot override voyage route
- VesselPlan revisions must not mutate historical Voyage execution

---

# 3. MONITORING VESSEL

## PURPOSE

Monitoring validates operational execution readiness.

Modules:
- Monitoring Kapal TAM
- Pemeriksaan Jadwal Kapal
- Tindak Lanjut Perubahan Jadwal

---

## SOURCE OF TRUTH

Monitoring reads:
- Voyage ONLY

Monitoring does NOT read:
- VesselPlan
- draft schedule

---

## MONITORING RESPONSIBILITIES

Monitoring tracks:
- ETD readiness
- ETA risk
- operational anomalies
- delay escalation
- vessel substitution
- SLA risks

---

# 4. DELAY CASE MANAGEMENT

## PURPOSE

Delay Case handles:
- ETD changes
- operational disruptions
- vessel substitution
- TAM communication
- escalation workflow

---

## SOURCE

Delay Case consumes:
- Voyage
- Monitoring result

---

## OUTPUT

Delay Case may produce:
- revised voyage execution
- alternative vessel assignment
- TAM approval flow

---

# 5. SHIPMENT EXECUTION

## PURPOSE

Shipment executes cargo movement using operational voyage commitment.

---

## SOURCE OF TRUTH

Shipment consumes:
- Voyage ONLY

Shipment must NOT override:
- ETD
- ETA
- vessel
- route

---

## ALLOWED CACHE FIELDS

Shipment may cache:
- vessel_name
- voyage_no
- ETD
- ETA

But Voyage remains authoritative source.

---

# 6. KPI / SLA

## PURPOSE

KPI measures operational execution quality.

---

## SOURCE OF TRUTH

KPI consumes:
- Voyage execution result
- Monitoring result
- Delay records

KPI does NOT consume:
- VesselPlan draft data

---

## KPI TYPES

Operational KPI:
- OTB
- OTD
- OTA
- dwell
- sailing days
- delay %

---

# 7. SHIPPING SCHEDULE

## CURRENT STATUS

ShippingSchedule is:
- transitional compatibility layer

ShippingSchedule exists only to:
- support legacy modules
- bridge migration
- preserve compatibility

---

## LONG TERM

ShippingSchedule may eventually:
- be deprecated
OR
- reduced to TAM reference layer only

---

# 8. CANONICAL FLOW

Shipping Line Draft
→ Vessel Plan Draft
→ TAM Approval
→ Final Vessel Plan
→ Generate Voyage
→ Monitoring
→ Delay Handling
→ Shipment Execution
→ KPI / SLA

---

# 9. MAY 2026 DATASET

May 2026 operational dataset is:
- canonical baseline dataset
- operational simulation reference
- KPI validation dataset
- monitoring validation dataset

All operational testing should use:
- May 2026 canonical operational dataset.