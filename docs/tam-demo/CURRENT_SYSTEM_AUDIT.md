# CURRENT SYSTEM AUDIT

## Dashboard TAM

Source:

app/Filament/Pages/AdminDashboard.php

Methods:

- getTamKpiSummary()
- getTamLeadTimeSeries()
- getTamLeadTimeEvaluation()
- getTamMonthlyBreakdown()
- getTamPortStock()

## KPI Engine

Source:

app/Services/ShipmentKpiEvaluator.php

Thresholds:

Dwelling = 6 days

Sailing = 10 days

Dooring = 3 days

Total Lead Time = 19 days

## Customer Filter

config/jss_kpi.php

customer_ids:

[1]

## Period Filter

Dashboard uses:

delivered_at

NOT:

- pickup date
- ATD
- ATA

## Dashboard Dependency

Dashboard depends on:

- shipments
- shipment_tracks

Dashboard does NOT directly depend on voyages.

## Monitoring Dependency

Monitoring Kapal depends on:

- vessel_plans
- voyages
- voyage_checkpoints
- voyage_milestones