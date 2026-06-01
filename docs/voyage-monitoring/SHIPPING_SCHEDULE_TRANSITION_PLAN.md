# SHIPPING SCHEDULE TRANSITION PLAN

## PURPOSE

Transition ShippingSchedule into:
- compatibility layer ONLY

Voyage becomes:
- canonical operational source

---

# CURRENT ISSUE

ShippingSchedule duplicates:
- vessel
- voyage
- ETD
- ETA
- KPI fields

This creates:
- duplicate ownership
- operational ambiguity

---

# TARGET

ShippingSchedule becomes:
- transitional compatibility bridge

---

# PHASE 1

ShippingSchedule delegates:
- ETD
- ETA
- vessel
- voyage_no
to Voyage

---

# PHASE 2

All new code reads:
- Voyage ONLY

---

# PHASE 3

Deprecated duplicated fields removed.

---

# FINAL TARGET

Voyage becomes:
- single operational execution source