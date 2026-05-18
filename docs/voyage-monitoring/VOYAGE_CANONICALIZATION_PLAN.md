# VOYAGE CANONICALIZATION PLAN

## PURPOSE

Define the canonical operational architecture centered around:
- Voyage Registry

This document finalizes:
- operational ownership
- source-of-truth rules
- migration strategy
- legacy deprecation path
- compatibility boundaries

---

# 1. CORE PRINCIPLE

## VOYAGE IS THE CANONICAL OPERATIONAL OBJECT

Voyage becomes the authoritative source for:
- operational execution
- shipment attachment
- monitoring
- SLA
- KPI
- delay handling
- operational reporting

---

# 2. WHY CANONICALIZATION IS REQUIRED

Historically:
- ShippingSchedule
- VesselPlan
- Shipment
- Monitoring

all stored overlapping operational data.

This caused:
- duplicated ETD
- duplicated ETA
- duplicated vessel assignment
- monitoring inconsistency
- shipment inconsistency
- KPI inconsistency

Canonicalization solves:
- ownership ambiguity
- duplicated operational state
- conflicting execution data

---

# 3. OPERATIONAL OWNERSHIP

| MODULE | ROLE |
|---|---|
| VesselPlan | planning workspace |
| Voyage | operational execution source |
| Monitoring | operational validation |
| Delay Case | operational escalation |
| Shipment | operational consumption |
| KPI | operational measurement |

---

# 4. VESSEL PLAN POSITION

## PURPOSE

VesselPlan remains:
- planning layer
- approval coordination layer

VesselPlan is NOT:
- execution source
- shipment source
- monitoring source

---

## FINALIZATION

Final Approved VesselPlan generates:
- Voyage Registry records

After generation:
- Voyage becomes operationally authoritative

---

# 5. VOYAGE POSITION

## PURPOSE

Voyage is the operational execution object.

---

## VOYAGE OWNS

| FIELD |
|---|
| voyage_no |
| vessel |
| ETD |
| ETA |
| ATD |
| ATA |
| route |
| cargo_plan |
| cargo_actual |
| operational status |

---

## IMMUTABILITY

After operational activation:
- Voyage becomes historical operational record
- changes require delay workflow
- direct mutation discouraged

---

# 6. MONITORING POSITION

## PURPOSE

Monitoring validates Voyage operational readiness.

---

## MONITORING READS

Monitoring reads:
- Voyage ONLY

Monitoring must NOT read:
- VesselPlan draft data
- planning schedules

---

## MONITORING OUTPUT

Monitoring produces:
- readiness validation
- operational risk
- delay escalation
- vessel substitution recommendation

---

# 7. SHIPMENT POSITION

## PURPOSE

Shipment consumes operational Voyage commitment.

---

## SHIPMENT SOURCE

Shipment attaches to:
- voyage_id ONLY

Shipment may cache:
- vessel_name
- voyage_no
- ETD
- ETA

But Voyage remains authoritative source.

---

## SHIPMENT MUST NOT

Shipment must NOT override:
- Voyage ETD
- Voyage ETA
- Voyage vessel
- Voyage route

---

# 8. SHIPPING SCHEDULE POSITION

## CURRENT STATUS

ShippingSchedule becomes:
- transitional compatibility layer

---

## WHY IT STILL EXISTS

Required temporarily for:
- legacy monitoring
- legacy shipment integration
- historical compatibility
- old operational workflow support

---

# 9. SHIPPING SCHEDULE OVERLAP

ShippingSchedule currently duplicates:
- vessel_name
- voyage_no
- ETD
- ETA
- cargo_plan
- cargo_actual
- operational KPI fields

These fields are Voyage-owned.

---

# 10. SHIPPING SCHEDULE DEPRECATION PLAN

## PHASE 5A — COMPATIBILITY BRIDGE

Current phase.

ShippingSchedule:
- remains functional
- delegates operational fields to Voyage
- marked transitional

New development must use:
- Voyage

---

## PHASE 5B — READ-THROUGH REDIRECT

All reads migrate to:
- ShippingSchedule->voyage

Deprecated:
- direct ETD reads
- direct ETA reads
- duplicated vessel fields

---

## PHASE 5C — LEGACY CLEANUP

Remove duplicated fields:
- vessel_name
- voyage_no
- ETD
- ETA
- cargo_actual
- KPI fields

ShippingSchedule reduced to:
- compatibility reference only

---

# 11. DELAY CASE POSITION

## PURPOSE

Delay Case handles:
- ETD change
- operational disruption
- vessel substitution
- escalation workflow

---

## SOURCE

Delay Case consumes:
- Voyage
- Monitoring result

---

## OUTPUT

Delay Case may:
- update operational status
- trigger alternative vessel
- notify TAM
- revise operational execution

---

# 12. KPI POSITION

## PURPOSE

KPI measures Voyage execution quality.

---

## KPI SOURCE

KPI consumes:
- Voyage execution
- Monitoring result
- Delay records

KPI must NOT consume:
- VesselPlan draft data

---

# 13. LEGACY DATA STRATEGY

## GOAL

Avoid breaking existing modules while migrating architecture.

---

## APPROACH

Use:
- compatibility delegation
- nullOnDelete normalization
- incremental migration
- transitional accessors

Avoid:
- hard removal
- abrupt schema deletion
- operational downtime

---

# 14. FK NORMALIZATION

All operational ownership FKs should use:
- nullOnDelete

Avoid:
- cascadeOnDelete on execution objects

Reason:
- Voyage historical execution must survive planning revisions

---

# 15. EXECUTION FLOW

Shipping Line Draft
→ VesselPlan Draft
→ TAM Approval
→ Final VesselPlan
→ Generate Voyage
→ Monitoring
→ Delay Handling
→ Shipment Execution
→ KPI Finalization

---

# 16. MAY 2026 BASELINE

May 2026 operational dataset becomes:
- canonical execution baseline
- monitoring validation dataset
- KPI validation dataset
- shipment execution dataset

All architecture validation should use:
- May 2026 dataset

---

# 17. ARCHITECTURE RULES

## ALLOWED

✓ Shipment consumes Voyage  
✓ Monitoring consumes Voyage  
✓ KPI consumes Voyage  
✓ DelayCase consumes Voyage  

---

## FORBIDDEN

✗ Shipment overrides Voyage ETD  
✗ Shipment overrides Voyage ETA  
✗ Monitoring mutates VesselPlan  
✗ KPI consumes draft schedule  
✗ VesselPlan deletes historical Voyage  

---

# 18. FINAL TARGET ARCHITECTURE

Planning Layer:
- VesselPlan

Execution Layer:
- Voyage

Monitoring Layer:
- Monitoring Vessel
- Delay Case

Consumption Layer:
- Shipment

Measurement Layer:
- KPI / SLA

Voyage remains the operational center.