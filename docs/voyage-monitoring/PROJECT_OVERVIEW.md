# Voyage Operational Monitoring System

## Purpose
Digitalisasi workflow operational monitoring vessel yang sebelumnya menggunakan Excel.

## Main Objectives
- mempertahankan workflow operasional existing
- menjaga parity dengan Excel
- centralized operational monitoring
- standardisasi KPI
- auditability
- future analytics

## Core Philosophy

**Period-Centric Operational Monitoring.**

Pengalaman user berbasis:
- periode monitoring aktif
- vessel dalam periode tersebut
- operational issue dalam periode tersebut
- KPI periode tersebut

Data tetap voyage-centric secara database, tetapi UX operasional berbasis periode.

## Main Operational Areas
1. Planning
2. Readiness Monitoring
3. Actual Operation
4. KPI Evaluation
5. Delay Management
6. SLA Monitoring
7. Operational Analytics

## Main Modules
- Monitoring Vessel (operational workspace)
- Data Voyage (detail record, audit trail, lifecycle)
- Vessel Plan (planning source)
- Vessel Check (readiness input)
- Shipping Line
- Port

## Operational Monitoring Philosophy

Sistem monitoring menggunakan pendekatan **period-centric operational monitoring**.

**Monitoring Vessel** berfungsi sebagai:
- single operational workspace
- period-based operational monitoring
- spreadsheet operasional modern
- pusat seluruh aktivitas monitoring vessel

**Data Voyage** berfungsi sebagai:
- detail record individual
- audit trail
- lifecycle detail
- histori per voyage
- data correction

**Data Voyage (VoyageResource)** berfungsi sebagai:
- admin lookup
- data management
- master record maintenance

Bukan lagi operational workspace atau pusat scanning vessel.

## Module Responsibility

| Module | Responsibility | Not Responsibility |
|---|---|---|
| Monitoring Vessel | operational monitoring workspace, period scanning, inline actions | master CRUD, individual voyage deep dive |
| Data Voyage (VoyageResource) | data management, audit, lookup | operational monitoring workspace |
| ViewVoyage | single voyage investigation, lifecycle detail | multi-voyage scanning, period overview |
| Dashboard Analytics | management analytics, cross-domain KPI | operational monitoring |
| Vessel Check | readiness & delay input | KPI evaluation, case workflow execution |
| Vessel Plan | pre-operational planning | actual monitoring |

## Key Principles

1. **Period-first**: Operator berfokus pada periode operasional, bukan individual voyage
2. **Matrix-only**: Matrix adalah satu-satunya tampilan monitoring, tidak ada tab/mode lain
3. **Anomaly-focused**: Issue terlihat jelas, data normal tetap ada tapi low-noise
4. **Inline actions**: Operator bisa bertindak langsung dari matrix tanpa banyak navigasi
5. **Spreadsheet-like**: UI terasa seperti Excel monitoring modern, bukan analytics dashboard
6. **Minimal navigation**: Kurangi perpindahan halaman, semua scanning di satu tempat
7. **Clean visual**: Warna hanya untuk delayed, overdue, risk, NG. Normal state = plain text
