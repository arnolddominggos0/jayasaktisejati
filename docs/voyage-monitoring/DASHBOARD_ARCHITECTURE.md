# Dashboard Architecture

## Modes

### Matrix View (Single View)
Operational monitoring real-time. Spreadsheet-like interface. Satu-satunya tampilan monitoring.

## Main Components

- Operational Matrix (single view)
- Period summary strip

## Module Responsibility Mapping

| Module | Responsibility | Not Responsibility |
|---|---|---|
| Monitoring Vessel | operational monitoring workspace, period scanning | master CRUD |
| ViewVoyage | single voyage investigation, lifecycle detail | multi-voyage scanning, period overview |
| Data Voyage (VoyageResource) | operational data management, audit, lookup | operational dashboard, scanning |
| Dashboard Analytics | management analytics, cross-domain KPI | operational monitoring |
| Vessel Check | readiness & delay input | KPI evaluation |
| Vessel Plan | pre-operational planning | actual monitoring |

## Dashboard Separation Strategy

**Monitoring Vessel Matrix:**
- operational matrix per period
- vessel comparison
- inline actions
- anomaly visibility
- period summary strip

**AdminDashboard:**
- cross-operational summary
- shipment-wide KPI
- enterprise rollup
- sea operation summary card only

## Period-Centric Layout

Layout utama Monitoring Vessel:

```
┌──────────────────────────────────────────┐
│ Monitoring Vessel                        │
│                                          │
│ [ Period ] [ Search ]                     │
├──────────────────────────────────────────┤
│ Delayed 3 | Sailing 1 | Completed 2      │
│ Overdue 17 | OTD 85% | OTA 90%           │
├──────────────────────────────────────────┤
│                                          │
│   Operational Monitoring Matrix          │
│                                          │
└──────────────────────────────────────────┘
```

Period summary strip selalu terlihat, memberikan konteks periode.
Tidak ada tab, mode, atau view switching.
