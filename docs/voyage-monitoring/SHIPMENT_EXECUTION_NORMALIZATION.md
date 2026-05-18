# SHIPMENT EXECUTION NORMALIZATION

## PURPOSE

Normalize Shipment execution ownership.

Shipment must consume:
- Voyage ONLY

Shipment must NOT override:
- ETD
- ETA
- vessel
- route

---

# SHIPMENT PRINCIPLE

Planning:
- VesselPlan

Execution:
- Voyage

Shipment:
- operational consumer

---

# SHIPMENT FLOW

Shipment Created
→ Voyage Assigned
→ Voyage Monitoring
→ Operational Execution
→ Delivery Completion

---

# SHIPMENT DATA SOURCE

| FIELD | OWNER |
|---|---|
| voyage | Voyage |
| vessel | Voyage |
| ETD | Voyage |
| ETA | Voyage |
| route | Voyage |

---

# SHIPMENT RESTRICTIONS

Shipment cannot:
- manually override ETA
- manually override ETD
- manually override vessel

---

# SHIPMENT REASSIGNMENT

Allowed only through:
- operational workflow
- delay escalation
- vessel substitution

---

# VALIDATION

Shipment must validate:
- voyage consistency
- route consistency
- operational timeline consistency

---

# FINAL TARGET

Shipment becomes:
- voyage-driven operational execution