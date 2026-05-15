# Prompt Pack — Monitoring Kapal TAM ala Excel

Dokumen ini adalah prompt siap pakai untuk menata ulang **modul Monitoring Kapal TAM** agar output datanya mengikuti format Excel operasional: final schedule, monitoring ETB/ETA, actual voyage, evaluasi OTB/OTD/OTA, total, dan achievement.

## Cara pakai

Tempel prompt di bawah ke agent coding/OpenCode CLI dari root repo. Sertakan path referensi supaya agent membaca konteks implementasi yang sudah ada.

```txt
Refer to:
- app/Filament/Pages/MonitoringKapalTam.php
- resources/views/filament/pages/monitoring-kapal-tam.blade.php
- resources/views/filament/pages/partials/tam-calendar.blade.php
- resources/views/filament/pages/partials/voyage-card-monitoring.blade.php
- app/Models/Voyage.php
- app/Models/VoyageMilestone.php
- app/Enums/VoyageOperationalStatus.php
- app/Enums/SlaStatus.php

Goal:
Ubah modul Monitoring Kapal TAM menjadi monitoring operasional seperti file Excel TAM. Fokusnya bukan modul Voyage master data, tetapi halaman `Monitoring Kapal TAM`.

Current problem:
Dashboard sekarang masih berupa KPI card + calendar lane. Data yang diharapkan user adalah tabel monitoring yang mirip Excel dengan grup kolom:
1. Final Schedule
2. Monitoring ETB & ETA
3. Actual
4. Evaluasi
5. Total dan Achievement

Required layout:
Buat mode/tampilan utama berupa tabel monitoring bulanan dengan struktur berikut.

A. Header umum
- Judul: Monitoring Kapal TAM
- Filter periode bulan
- Search kapal/voyage
- Tombol/segmen mode jika masih diperlukan: Dashboard, Tabel Monitoring, Calendar

B. Tabel utama ala Excel
Kolom wajib:
- No
- Final Schedule:
  - ETB
  - ETD
  - ETA
  - Vessel
  - Cargo Plan
- Monitoring ETB & ETA:
  - D-2
  - D-1
- Actual:
  - ATB
  - Date / Closing
  - ATD
  - D+4
  - D+6
  - D+8
  - D+10
  - D+12
  - ATA
  - Vessel
  - Cargo
- Evaluasi:
  - OTB
  - Reason
  - OTD
  - Reason
  - OTA
  - Reason

Data mapping:
- ETB: voyages.etb
- ETD: voyages.etd
- ETA: voyages.eta
- Vessel: voyage.vessel.name
- Cargo Plan: voyages.cargo_plan
- D-2 and D-1: voyage checkpoints/milestones against ETA monitoring, show OK/NG/blank based on available data
- ATB: voyages.atb_at
- Date / Closing: voyages.closing_at
- ATD: voyages.atd_at
- D+4, D+6, D+8, D+10, D+12: voyage.milestones code d4/d6/d8/d10/d12; show status/actual date/blank clearly
- ATA: voyages.ata_at
- Actual Vessel: voyage.vessel.name, unless there is a separate actual vessel field in schema
- Cargo: voyages.cargo_actual
- OTB: voyage.otb_status mapped to OK/NG/blank
- OTD: voyage.otd_status mapped to OK/NG/blank
- OTA: voyage.ota_status mapped to OK/NG/blank
- Reason fields: use the best existing reason field first; if one reason is insufficient, add separate nullable columns for otb_reason, otd_reason, ota_reason in a migration and expose them in UI

Display rules:
- Use OK for on-time.
- Use NG for late/not achieved.
- Use blank or `-` for no data.
- Color OK green.
- Color NG red/orange.
- Weekend/date highlights may remain in calendar mode, but Excel-like monitoring table is the priority.
- The table must be horizontally scrollable and readable on desktop.
- Header groups should visually match Excel sections with distinct background colors:
  - Final Schedule: green
  - Monitoring ETB & ETA: cyan/blue
  - Actual: orange
  - Evaluasi: cyan
  - Total row: bright green

Summary row and achievement:
- Add a Total row below the table:
  - Sum Cargo Plan
  - Sum Actual Cargo
  - OTB OK percentage
  - OTD OK percentage
  - OTA OK percentage
- Add an Achievement summary section:
  - OK/NG counts for OTB, OTD, OTA
  - percentages for OK/NG
  - top delay reason if available
  - average departure delay if available

Implementation notes:
1. Prefer implementing the Excel-like table as a Blade partial, for example:
   `resources/views/filament/pages/partials/tam-monitoring-excel-table.blade.php`
2. Keep `MonitoringKapalTam.php` as the data assembler. Add computed arrays if needed, for example:
   - monitoringRows
   - monitoringTotals
   - monitoringAchievement
3. Do not break the existing calendar; keep it as optional Dashboard/Calendar mode.
4. Fix old references to `delay_reason` if the current model uses `manual_delay_reason`.
5. Ensure any new database columns are introduced via migration, not direct schema assumptions.
6. Do not change unrelated FC/shipment/land logic.

Acceptance criteria:
- `Monitoring Kapal TAM` has an Excel-like monitoring table matching the required column groups.
- Per-row data shows final schedule, monitoring checkpoints, actual voyage dates, milestone D+ data, and OTB/OTD/OTA evaluation.
- Total row and achievement summary are visible for the selected month.
- Search and period filters still work.
- Existing calendar/dashboard does not regress.
- Empty state remains clear when there is no voyage in the selected period.

Run checks:
- php -l app/Filament/Pages/MonitoringKapalTam.php
- php artisan test --filter=MonitoringKapalTam
- ./vendor/bin/pint --test app/Filament/Pages/MonitoringKapalTam.php app/Models/Voyage.php

Return format:
- Summary of changed files
- Screenshot or description of the new table layout
- Exact commands run and real results
- Risk notes and any follow-up migration/data-fill requirement
```

## Prompt singkat untuk iterasi UI saja

Gunakan prompt ini jika data sudah benar dan yang perlu dirapikan hanya tampilan tabelnya.

```txt
Fokus hanya UI Monitoring Kapal TAM. Jangan ubah schema dan jangan ubah logic shipment.

Ubah `resources/views/filament/pages/monitoring-kapal-tam.blade.php` atau buat partial baru agar ada tabel monitoring ala Excel dengan grup header:
Final Schedule, Monitoring ETB & ETA, Actual, Evaluasi.

Kolom wajib:
No, ETB, ETD, ETA, Vessel, Cargo Plan, D-2, D-1, ATB, Date/Closing, ATD, D+4, D+6, D+8, D+10, D+12, ATA, Vessel, Cargo, OTB, Reason, OTD, Reason, OTA, Reason.

Gunakan data yang sudah tersedia dari `$rows` dan accessor `Voyage` yang sudah ada. Jika field belum ada, tampilkan `-` dan tulis follow-up note, jangan bikin schema baru pada prompt UI-only ini.
```

## Prompt singkat untuk data/schema saja

Gunakan prompt ini jika tampilan sudah ada, tapi data reason/evaluasi belum cukup.

```txt
Fokus hanya data/schema untuk Monitoring Kapal TAM. Jangan redesign UI besar.

Audit `App\Models\Voyage`, migration voyages, dan halaman `MonitoringKapalTam` untuk memastikan field Excel tersedia:
ETB, ETD, ETA, vessel, cargo_plan, D-2, D-1, ATB, closing_at, ATD, D+4/D+6/D+8/D+10/D+12, ATA, cargo_actual, OTB/OTD/OTA, reason masing-masing evaluasi.

Jika reason OTB/OTD/OTA belum tersedia, buat migration nullable columns:
- otb_reason
- otd_reason
- ota_reason

Expose field tersebut di model fillable/cast bila perlu dan pastikan halaman monitoring dapat membacanya.
```
