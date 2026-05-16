# Voyage Module PRD

## Vision

Unified voyage operational monitoring platform dengan pendekatan period-centric.

## Main Goals

- period-centric operational monitoring
- unified operational timeline
- readiness consolidation
- operational analytics

## Main Screens

1. **Monitoring Vessel** — Operational Matrix (single view)
2. **Data Voyage (ViewVoyage)** — Lifecycle detail, KPI audit, readiness detail, delay history
3. **Data Voyage (VoyageResource)** — Data management, admin lookup, historical record

## Information Hierarchy

**Monitoring Vessel (Period Workspace):**
- period summary strip (compact, horizontal)
- operational matrix (all vessels in period)
- inline operational actions
- anomaly visibility

**ViewVoyage (Individual Detail):**
- operational lifecycle detail
- KPI detail
- readiness detail
- delay audit history
- milestone detail

**Dashboard Analytics:**
- aggregated KPI per period
- SLA trend
- delay distribution
- operational comparison

## Monitoring Philosophy

Monitoring harus:
- cepat discan secara horizontal
- fokus pada anomaly dan issue
- memiliki hierarchy jelas (Delayed → ETA Risk → Sailing → Readiness → Scheduled)
- tidak menampilkan excessive detail di level scanning
- period-centric, bukan voyage-centric
- single view, tidak ada tab/mode switching

Priority scanning (dalam satu matrix):
1. delayed voyage
2. sailing voyage with ETA risk
3. sailing normal
4. readiness issue
5. scheduled voyage

Jika ditemukan issue:
→ lihat di matrix (detail terlihat di row)
→ atau klik Detail → ViewVoyage untuk investigasi detail
→ update milestone / operation dari matrix (modal inline)
→ monitor progress per periode

## UX Direction

**Matrix (Single View):**
- spreadsheet-like interface
- horizontal scanning
- vessel comparison
- inline actions
- anomaly-focused color coding
- minimal visual noise
- subtle left border untuk issue rows
- plain text untuk normal state
