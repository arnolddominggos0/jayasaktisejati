# Operational Workflow

## 1. Planning

VesselPlan (Draft)
→ Submit
→ Review
→ Final
→ Generate Voyage

## 2. Readiness Monitoring

ETA set
→ Auto generate D-2 / D-1 checkpoints

ETD approaching
→ Auto generate H-3 / H-2 / H-1 vessel checks

## 3. Actual Operation

ATB
→ Closing
→ ATD
→ Milestones
→ ATA

## 4. KPI Evaluation

OTB = ATB <= ETB
OTD = ATD <= ETD
OTA = ATA <= ETA

## Operational Monitoring Lifecycle

Vessel Plan
→ Voyage generation
→ Readiness monitoring
→ Vessel check
→ Operational monitoring (per period)
→ Milestone tracking
→ KPI evaluation
→ Management analytics

## Period-Centric Operational Scanning Flow

Operator membuka Monitoring Vessel.

Pilih periode operasional.

Matrix menampilkan seluruh vessel dalam periode:
- scan horizontal: bandingkan vessel
- identifikasi anomaly: delay, ETA risk, readiness issue
- inline action: update milestone, update actual, acknowledge

Priority scanning (dalam satu matrix, sudah sorted):
1. delayed voyage
2. sailing voyage with ETA risk
3. sailing normal
4. readiness issue
5. scheduled voyage
6. completed voyage

Jika ditemukan issue:
→ lihat di matrix (detail terlihat di row)
→ atau klik Detail → ViewVoyage untuk investigasi detail
→ update milestone / operation dari matrix (modal inline)
→ monitor progress per periode

## History Flow

Switch periode → lihat vessel pada periode tersebut → lihat completed voyage → lihat KPI periode sebelumnya.

History berbasis periode, bukan individual voyage navigation.
