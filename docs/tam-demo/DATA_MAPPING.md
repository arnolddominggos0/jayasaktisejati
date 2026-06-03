# DATA MAPPING

## Voyage Mapping

Spreadsheet ETB
→ vessel_plan.etb

Spreadsheet ETD
→ voyage.etd

Spreadsheet ETA
→ voyage.eta

Spreadsheet Vessel
→ vessel.name

Spreadsheet Cargo Plan
→ voyage.planned_units

---

## Shipment Mapping

Masuk Pelabuhan
→ pickup

Unit Loading
→ unit_loading

ATD
→ vessel_depart

ATA
→ vessel_arrival

RVDC / Dooring
→ delivered

---

## KPI Mapping

Dwelling

pickup
↓
unit_loading

Sailing

unit_loading
↓
vessel_arrival

Dooring

vessel_arrival
↓
delivered