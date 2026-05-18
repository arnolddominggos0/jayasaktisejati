# Implementation Phases

## Completed Phases

### Phase 0 - Critical Bug Fixes
- **Remove VesselCheckCase::vesselCheckCase()** — broken self-referencing hasOne with no FK (infinite recursion risk)
- **Remove VesselCheckCase::vesselChecks()** — no `vessel_check_case_id` column exists on vessel_checks table
- **Create VesselCheckEtdLog model** — table existed since 2026_02_03 but model was missing
- **Create VesselCheckEvaluation model** — table existed since 2026_02_03 but model was missing
- **Fix VoyageDelayLog fillable** — add `new_etb` and `new_atb_at` to $fillable and $casts; Voyage::updating was writing these but model silently discarded them

### Phase 1 - FK Normalization & Ownership Cleanup
- **vessel_plan_items.voyage_id**: cascadeOnDelete → nullOnDelete — Voyage survives plan revisions; deleting Voyage orphans plan items instead of deleting them
- **shipping_schedules.voyage_id**: cascadeOnDelete → nullOnDelete — ShippingSchedule is transitional; deleting Voyage orphans schedule records instead of deleting them
- **vessel_checks.voyage_id**: normalize to nullOnDelete + index — reconciles two competing migrations (Feb 9: cascadeOnDelete; May 16: nullOnDelete+index); now canonical: nullable nullOnDelete indexed
- **vessel_check_cases.voyage_id**: add column nullable nullOnDelete indexed — enables direct Voyage-to-case queries in monitoring; prerequisite for Phase 10 (VesselCheck Case Integration)
- **Backfill vessel_check_cases.voyage_id**: from shipping_schedule.voyage_id for existing records
- **Add voyage() to VesselCheckCase**: new belongsTo(Voyage::class) relationship

### Phase 2 - Voyage Form & Detail Cleanup
- closing_at form
- readiness panel
- voyage detail cleanup

### Phase 2 - Status & Milestone Enhancement
- status standardization
- milestone enhancement

### Phase 3 - Analytics & KPI
- analytics
- KPI charts

### Phase 4 - Period-Centric Operational Monitoring Refactor
- Matrix View as single view
- Period-centric UX restructuring
- VoyageResource positioned as detail-record-only
- Monitoring Vessel as single operational workspace
- Priority View removed
- Dashboard removed
- Inline operational actions in matrix
- Period summary strip
- Anomaly-focused UI

### Phase 5 - Operational Workspace Stabilization
- Rename Monitoring Kapal TAM → Monitoring Vessel
- Remove Priority View, Matrix View tabs, dashboard-heavy behavior
- Matrix menjadi tampilan utama, default, dan satu-satunya
- Compact summary strip (horizontal, no cards)
- Matrix UX cleanup: reduce columns, minimal badges, subtle colors
- Actions fixed visible (not hover-only)
- Normal state = plain text, minimal badge
- Row issue = subtle left border only
- Rename Voyage → Data Voyage
- Data Voyage positioned as detail-record-only
- Navigation group rename: Monitoring Kapal TAM → Monitoring Vessel

### Phase 6 - Monitoring Vessel Visual Hierarchy Refinement
- Summary strip refinement: inline pill badges, more visible, compact
- Matrix row hierarchy: subtle left border + very soft tinted background for anomaly rows
- Vessel column improvement: bold vessel name, medium voyage no, small muted route, status pill
- Status visualization: compact pill badge kecil dengan subtle color
- Issue column refinement: compact mini pill badges (critical = red pill, warning = amber pill)
- KPI visibility: only NG gets highlighted pill, OK = muted text
- Actions refinement: ghost buttons with subtle border, rounded, consistent spacing
- Table density balance: slightly more breathing room, zebra striping
- Table styling cleanup: soft borders, subtle zebra, clean sticky header

### Phase 7 - Monitoring Vessel Operational Workspace Refactor
- **Compact header with period subtitle**: "Operational Monitoring — {periode aktif}"
- **Compact operational summary cards**: 7 cards (Delayed, Sailing, Completed, Scheduled, Overdue, OTD, OTA)
- **Cards are compact, low-height, subtle, anomaly-first**
- **Matrix is PRIMARY workspace**: dense, 10-15 rows visible
- **Issue column cleanup**: critical = pill, secondary = ↳ plain text
- **Milestone symbols**: ✓ done, ! overdue, • pending (no more "Late" spam)
- **KPI cleanup**: NG = subtle red pill, OK = muted gray
- **Actions**: ghost buttons, fixed visible
- **Operational calendar**: placed BELOW matrix, compact
- **ViewVoyage renamed to "Operational Detail Sheet"**
- **ViewVoyage subheading**: "Detail voyage, audit trail & lifecycle — untuk monitoring harian gunakan Monitoring Vessel"
- **Calendar build logic restored** in MonitoringKapalTam.php
- **Priority sorting includes COMPLETED** (after sailing, before scheduled)

## Goals

- menyatukan operational UX
- mengurangi overlap module
- meningkatkan operational scanning clarity
- menjadikan Monitoring Vessel sebagai command center
- menjadikan ViewVoyage sebagai operational deep dive
- period-centric monitoring flow
- spreadsheet-like operational interface
- single view, no mode switching
- modern operational console feel
- scan speed optimization
- anomaly visibility enhancement

## Scope

1. Monitoring card refactor ✅
2. Unified operational timeline ✅
3. Dashboard separation ✅
4. ViewVoyage consolidation ✅
5. Status visualization standardization ✅
6. ShippingSchedule responsibility reduction ✅
7. Period-centric layout ✅
8. Matrix View only ✅
9. Inline actions ✅
10. Anomaly-focused UI ✅
11. Operational workspace stabilization ✅
12. Single view, no tabs ✅
13. Compact summary strip ✅
14. Minimal visual noise ✅
15. Visual hierarchy refinement ✅
16. Modern operational feel ✅
17. Compact operational cards ✅
18. Milestone symbol system ✅
19. Issue hierarchy (critical vs secondary) ✅
20. Operational calendar placement ✅

## Priority

P0:
- delayed voyage UX ✅
- readiness visibility ✅
- operational scanning ✅
- period-centric layout ✅
- matrix single view ✅
- compact summary ✅
- visual hierarchy ✅
- scan speed ✅

P1:
- timeline consolidation ✅
- KPI consistency ✅
- inline actions ✅
- minimal visual noise ✅
- modern operational feel ✅

P2:
- analytics separation ✅
- tech debt cleanup

### Phase 8 - Operational Monitoring UI Polish & Readability Refinement
- **Page header cleanup**: Title "Monitoring Vessel", subtitle "Operational Monitoring — {periode}"
- **Summary strip hierarchy**: Critical cards (Delayed, Overdue) = stronger contrast, bigger number
- **Summary informational cards** (Sailing, Completed, Scheduled) = lighter tone, muted
- **Summary KPI cards** (OTD, OTA) = lightest, white bg, gray text
- **Matrix readability**: Wider spacing for key columns (ETD, ETA, ATD, ATA, Issue: px-3)
- **Table header hierarchy**: Slightly darker text (gray-500), medium weight
- **Table border softening**: Lighter borders (gray-200/30, gray-100/30), softer separators
- **Status badge refinement**: Smaller, outline-style, minimal filled bg (bg-{color}-50/30)
- **Status labels shortened**: "Scheduled" → "Sched", "Completed" → "Done"
- **Issue column cleanup**: Only critical = red pill, secondary = ↳ plain amber text
- **Milestone symbols**: Slightly larger (11px), consistent alignment
- **Calendar compression**: Compact operational schedule strip
  - Cell height: h-14 (from h-24)
  - Chip padding: px-1.5 py-1 (from px-2 py-2)
  - Header padding: px-4 py-2 (from px-6 py-4)
  - Legend dots: w-2 h-2 (from w-3 h-3)
  - Legend text: text-[10px] (from text-xs)
  - Overall: min-w-[1200px] (from min-w-[1500px])
- **Overall visual direction**: Calm operational UI, anomaly-first, low noise, modern logistics board

## Goals

- menyatukan operational UX
- mengurangi overlap module
- meningkatkan operational scanning clarity
- menjadikan Monitoring Vessel sebagai command center
- menjadikan ViewVoyage sebagai operational deep dive
- period-centric monitoring flow
- spreadsheet-like operational interface
- single view, no mode switching
- modern operational console feel
- scan speed optimization
- anomaly visibility enhancement
- calm operational UI
- low visual noise for long monitoring sessions

## Scope

1. Monitoring card refactor ✅
2. Unified operational timeline ✅
3. Dashboard separation ✅
4. ViewVoyage consolidation ✅
5. Status visualization standardization ✅
6. ShippingSchedule responsibility reduction ✅
7. Period-centric layout ✅
8. Matrix View only ✅
9. Inline actions ✅
10. Anomaly-focused UI ✅
11. Operational workspace stabilization ✅
12. Single view, no tabs ✅
13. Compact summary strip ✅
14. Minimal visual noise ✅
15. Visual hierarchy refinement ✅
16. Modern operational feel ✅
17. Compact operational cards ✅
18. Milestone symbol system ✅
19. Issue hierarchy (critical vs secondary) ✅
20. Operational calendar placement ✅
21. Summary card hierarchy (critical vs informational vs KPI) ✅
22. Matrix column breathing room ✅
23. Table header clarity ✅
24. Calendar compression ✅
25. Status badge minimalism ✅
26. Calm operational UI ✅

## Priority

P0:
- delayed voyage UX ✅
- readiness visibility ✅
- operational scanning ✅
- period-centric layout ✅
- matrix single view ✅
- compact summary ✅
- visual hierarchy ✅
- scan speed ✅
- calm UI ✅

P1:
- timeline consolidation ✅
- KPI consistency ✅
- inline actions ✅
- minimal visual noise ✅
- modern operational feel ✅

P2:
- analytics separation ✅
- tech debt cleanup

### Phase 9 - Operational Monitoring & ViewVoyage UX Refinement

**Direction:**
- Monitoring Vessel = fleet monitoring console / freight dispatch board / shipping operation workspace
- ViewVoyage = operational investigation workspace / operational audit room

**A. Monitoring Vessel Refinement**

A1. Summary Strip Compression
- Refactor from grid cards to horizontal compact strip
- Format: "Delayed 3 | Sailing 1 | Overdue 16 | OTD 85% | OTA 90%"
- Reduce card height by ~40%
- Delayed & Overdue remain dominant
- KPI most subtle (white bg)
- Goal: matrix visible without large scroll

A2. Header Compression
- Compress title, subtitle, search/filter spacing
- Operator enters data immediately
- Top whitespace reduced

A3. Matrix Column Restructure
- Alignment: Vessel + dates LEFT aligned, Readiness/Milestone/KPI/Actions CENTER
- Issue column repositioned closer to ATA (operational data flow: Vessel → Schedule → Issue)
- Issue becomes primary operational signal

A4. Anomaly-First Visual Strategy
- Normal rows: very quiet, minimal visual noise
- Anomaly rows: subtle left border + subtle tint
- Not all rows feel equally important

A5. Table Border Reduction
- Vertical borders reduced drastically
- Use whitespace separation
- Horizontal separators softer
- Cleaner operational aesthetic

A6. Status Badge Simplification
- Remove Delayed badge from vessel area
- Delayed visible via issue column, overdue, row anomaly
- Keep: Sailing, Done, Sched (small, subtle)

A7. Action Column Cleanup
- Ultra-subtle ghost actions
- Monochrome
- Row feels clickable
- Actions secondary
- Not like Filament admin table

A8. Matrix Density Tuning
- Target: 10-15 vessel visible
- Dense but readable
- Not cramped, not overly airy

A9. Calendar Final Polish
- Compress lane height further
- Reduce chip roundness
- Event title slightly bolder
- Legend more subtle
- Direction: compact operational schedule strip

A10. Typography Hierarchy
- Primary: vessel name, issue, anomaly, KPI NG
- Secondary: ETD/ETA, actual dates
- Tertiary: route, metadata, empty states

**B. ViewVoyage Refinement**

B1. Fix Error First
- Fix any Livewire/component errors
- Fix missing component, invalid blade include, broken filament component
- Clean render without stacktrace

B2. Remove Form-Like Feel
- Less admin form / vertical form dump
- More compact, information-oriented, operational
- Reduce whitespace, oversized cards, giant sections, long vertical spacing

B3. Operational Header
- Compact operational header showing:
  - Vessel + Voyage No
  - Route | Period | Status
  - ETD/ETA/ATD/ATA in row
  - KPI metrics on right side (OTD, OTA, delay, overdue)
- Not like analytics dashboard

B4. Information Hierarchy
- A. Operational Summary: vessel, route, status, ETD/ETA, ATD/ATA, KPI, delay
- B. Readiness & Milestone: D-2, D-1, H-1, D+ milestones
- C. Delay & Operational Notes: delay history, manual delay reason, notes
- D. Audit & Lifecycle: generated from vessel plan, created/updated by, timestamps, audit logs

B5. KPI Visibility
- OTD/OTA/OTB as compact operational indicators
- Anomaly-first: NG = stronger emphasis
- OK = subtle

B6. Timeline/History Cleanup
- Delay history not raw table
- Use compact operational timeline: newest first, concise spacing
- Example: "25 Apr 14:30 | ETD changed | 24 Apr → 25 Apr | Reason: Port congestion"

B7. Section Cleanup
- Reduce: nested cards, double borders, heavy shadows, giant containers
- Use: typography hierarchy, lightweight separators, spacing hierarchy

**C. Results**
- Monitoring Vessel feels like operational command center
- ViewVoyage feels like operational investigation room
- No new features
- No business logic changes
- No architecture redesign
- Focus: readability, hierarchy, anomaly-first visibility, operational ergonomics, scanning speed, investigation workflow

## Next Phase

### Phase 10 - VesselCheck Case Integration
- Connect VesselCheckCase to Monitoring Vessel
- Add case status to matrix view
- Quick follow-up panel
- Persistent acknowledgement

### Phase 11 - May 2026 Canonical Operational Seeding
- **Objective**: Transform real May 2026 operational data into canonical entities
- **Dataset**: 8 voyages (3 from sample docs + 5 realistic expansion)
- **JSS column** → voyages.voyage_no (canonical)
- **LTS column** → ignored
- **Default route**: JKT → BTG

**Stage 1 — Base Entities**
- Ports: JKT (POL), BTG (POD)
- ShippingLines: Tanto, Meratus
- Customer: TAM
- Branches: JKT, MDO
- Cities: Jakarta, Manado
- Vessels: 8 vessels from dataset

**Stage 2 — VesselPlan (Final)**
- Period: May 2026
- Status: Final
- Route: JKT-BTG

**Stage 3 — VesselPlanItems + Voyages**
- 8 VesselPlanItems generated
- 8 Voyages generated with operational timestamps:
  - 3 Scheduled (no ATD)
  - 4 Sailing (ATD, no ATA)
  - 1 Completed (ATD + ATA)
  - 1 Delayed (ATD + delay reason)
- VoyageCheckpoints auto-generated: 16 (D-2, D-1 per voyage)

**Stage 4 — ShippingSchedule (Transitional)**
- 8 ShippingSchedule records linked to Voyages
- Contains jss, cargo_plan, etd, eta, vessel_name

**Stage 5 — Shipments (Voyage Consumption)**
- 19 Shipment records
- All linked to voyage_id
- Consume vessel_name, voyage_no, pol, pod, etd, eta from Voyage
- Mode: sea, service_type: sea_freight

**Stage 6 — VesselCheck Scenarios**
- 16 VesselCheck records
- D-1, D-2 readiness checks for all voyages
- H-1 checks where date doesn't conflict
- Mix of on_schedule and potential_delay

**Verification Results**
- Voyage generation integrity: PASS (all 8 voyages have vessel, pol, pod, shippingLine)
- Monitoring reads Voyage: PASS (16 checkpoints auto-generated)
- Shipment reads Voyage: PASS (19 shipments, all consume voyage data)
- VesselPlan→Voyage linkage: PASS (8/8 linked)
- ShippingSchedule transitional: PASS (8/8 linked to voyages)
- VesselCheck scenarios: PASS (16 checks, all have voyage + schedule)
- migrate:fresh --seed: PASS

**Bug Fixes During Seeding**
- Fixed ShipmentObserver using non-existent `port_from_id`/`port_to_id` → `pol_id`/`pod_id`
- Fixed DepotResolver using non-existent `port_to_id` → `pod_id`
- Fixed SyncVoyagesToSchedule using non-existent `port_from_id`/`port_to_id` → `pol_id`/`pod_id`

## SPRINT A - Navigation & Detail Sheet Refinement

### SPRINT A.1 — Sidebar & Terminology Cleanup
- **Navigation groups restructured** into 3 canonical groups:
  - OPERATIONS (Monitoring Vessel, Vessel Planning, Vessel Readiness, Delay Cases, Voyage Registry)
  - SHIPMENT EXECUTION (Shipment Requests, Sea Bookings)
  - MASTER DATA (Ports, Shipping Lines, Vessels, Depots, Customers, Users)
- **AdminPanelProvider** updated to define new navigation groups explicitly
- **13 resources + 1 page** updated with new groups, English labels, sort order, icons:
  - MonitoringKapalTam → OPERATIONS, "Voyage Monitoring"
  - VesselPlanResource → OPERATIONS, "Vessel Planning"
  - VesselCheckResource → OPERATIONS, "Vessel Readiness"
  - VesselCheckCaseResource → OPERATIONS, "Delay Cases"
  - VoyageResource → OPERATIONS, "Voyage Registry"
  - ShipmentResource → SHIPMENT EXECUTION, "Shipment Requests"
  - SeaBookingResource → SHIPMENT EXECUTION, "Sea Bookings" (now visible)
  - PortResource → MASTER DATA, "Ports"
  - ShippingLineResource → MASTER DATA, "Shipping Lines"
  - VesselResource → MASTER DATA, "Vessels" (now visible)
  - DepotResource → MASTER DATA, "Depots"
  - CustomerResource → MASTER DATA, "Customers"
  - UserResource → MASTER DATA, "Users"
- **Page action labels** updated to English: Add Port, Add Shipping Line, Add Depot, Add User, Add Customer, Add Voyage, Create Request
- **ViewVoyage subheading** updated: "Fleet command detail — for daily monitoring use Voyage Monitoring"

### SPRINT A.2 — ViewVoyage Operational Detail Refactor
- **Replaced widget-based layout** with unified custom blade view (`view-voyage.blade.php`)
- **ViewVoyage.php** now uses custom view + eager-loads all relationships in `mount()`
- **Removed `getFooterWidgets()`** — all content rendered inline for cohesion
- **Target feel**: fleet command sheet, dispatch operational workbook, NOT admin CRUD

**Section 1 — Compact Operational Header**
- Single compact strip with left border color indicating severity (red/orange/blue/green/gray)
- Vessel name (bold) + voyage number (mono) + route (POL→POD) + shipping line
- ETD › ETA timeline with ATD/ATA when available, color-coded by SLA
- Cargo load percentage when available
- Status badge + delay/overdue badge on the right
- Anomaly pills below (delay, milestone critical, ETA overdue, sailing risk)

**Section 2 — Operational Status Summary**
- Horizontal KPI strip: OTB, OTD, OTA as compact bordered pills
- SLA status badge + sailing days comparison
- Delay root cause on the right

**Section 3 — Operational Timeline**
- Unified chronological feed combining: checkpoints, vessel checks, ETB, ATB, closing, ATD, milestones, ATA, delay events
- Anomaly events get red left border + tinted background
- Each row: type badge (CP/VC/ETB/ATB/CL/ATD/ATA/D+) + label + state symbol + detail + timestamp
- Priority-based sorting (critical first, then chronological)

**Section 4 — Readiness Feed**
- Compact vertical list of checkpoints (CP) and vessel checks (VC)
- Code + status (color-coded) + timestamp
- Hover left border for scanability

**Section 5 — Delay Incident Log**
- Red-tinted rows with left border
- Each incident: reason + ETD delta + timestamp

**Section 6 — Milestone Rail**
- Horizontal flex grid of milestone cells
- Each cell: code + symbol (✓/✗/!/●/—) + planned date
- Color-coded: done=green, overdue=red, due today=orange, pending=gray

**Component Refactors**
- `components/voyage-kpi-panel.blade.php` — compact horizontal strip
- `components/voyage-operational-timeline.blade.php` — unified chronological timeline
- `components/voyage-milestone-progress.blade.php` — horizontal rail
- `components/voyage-readiness-timeline.blade.php` — compact feed
- `filament/resources/voyage-resource/widgets/delay-history.blade.php` — incident log style

### Phase 12A — Inline Operational Actions
- **Model changes**: Added `Voyage::vesselCheckCases()` relationship; added `voyage_id` to `VesselCheckCase::$fillable`
- **ATB inline action**: Ghost "Mark" button when null, date + edit icon when set; compact datetime modal
- **ATD inline action**: Same pattern
- **ATA inline action**: Same pattern
- **Closing inline action**: Same pattern
- **D-1 / H-1 readiness interaction**: Clickable cells; compact modal with status select + note; updates VesselCheck
- **Issue column interaction**: Existing delay case → status badge; no case + anomaly → "+ Case" ghost button; compact confirmation modal
- **Compact inline modal**: 340px wide, single input, optional note, Cancel/Save buttons
- **Matrix density preserved**: No large buttons, ghost/inline controls only, two narrow columns added (ATB, Closing)
- **Validation added**: datetime nullable|date, status in:on_schedule/potential_delay, note max 500
- **Livewire state management**: `showInlineModal`, `inlineModalType`, `inlineForm` with reset lifecycle

**Files modified**:
- `app/Models/Voyage.php` — added `vesselCheckCases()` hasMany
- `app/Models/VesselCheckCase.php` — added `voyage_id` to fillable
- `app/Filament/Pages/MonitoringKapalTam.php` — modal state, 8 action methods, eager-load vesselCheckCases
- `resources/views/filament/pages/partials/tam-matrix-view.blade.php` — inline buttons, clickable readiness cells, case badges
- `resources/views/filament/pages/monitoring-kapal-tam.blade.php` — compact inline modal overlay

### Phase 12B — Operational State Engine & Readability Rebalance
- **Readability rebalance**: Row padding increased (`py-2` from `py-1.5`), base font `text-xs` (12px), header `text-[11px]` uppercase tracking-wider
- **Operational hierarchy**: Planned dates (ETD/ETA) = `text-gray-400 font-normal` muted secondary; Actual dates (ATD/ATA/ATB/Closing) = `text-gray-900 font-semibold` bold primary
- **Anomaly-first styling**: Critical rows = `border-l-4 border-l-red-500` + `bg-red-50/40`; Warning = `border-l-4 orange` + `bg-orange-50/25`; Attention = `border-l-4 amber` + `bg-amber-50/15`; Normal = transparent, quiet
- **Issue column stronger**: Critical pills = `px-2 py-1 text-[11px] font-bold bg-red-100 text-red-700 border-red-200 shadow-sm`; Secondary = `text-[11px] text-amber-700 font-medium`
- **Inline action usability**: Edit icons = `w-5 h-5` with `w-3 h-3` SVG; Mark buttons = `text-[10px] px-2 py-0.5` with hover border darkening; ghost styling preserved
- **Milestone visibility**: Symbols inside `w-6 h-6` circle backgrounds — done=green circle, overdue=red circle, pending=gray circle; `text-xs font-bold` symbols
- **KPI visibility**: OK = `text-green-600 text-sm font-bold ✓`; NG = `px-1.5 py-0.5 bg-red-100 text-red-700 border-red-200 text-[10px] font-bold`
- **Action buttons**: `w-6 h-6` touch targets, `w-3.5 h-3.5` icons, stronger hover contrast
- **Summary strip polish**: Slightly larger numbers (`text-base` from `text-sm`), better padding (`px-2.5 py-1.5`), vertical divider bars instead of `|` characters
- **Sticky column depth**: Added `shadow-[2px_0_4px_-2px_rgba(0,0,0,0.05)]` to sticky vessel column for visual separation
- **Removed zebra striping**: Replaced with `divide-y` + anomaly tints for cleaner tactical look

**Files modified**:
- `resources/views/filament/pages/partials/tam-matrix-view.blade.php`
- `resources/views/filament/pages/monitoring-kapal-tam.blade.php`

### Phase 12C — Indonesian Operational Terminology Standardization
- **Navigation groups Indonesian**: Operasional, Perencanaan, Eksekusi Pengiriman, Master Data
- **Resource labels standardized**:
  - Monitoring Voyage (Operasional)
  - Registry Voyage (Operasional)
  - Kesiapan Kapal (Operasional)
  - Kasus Delay (Operasional)
  - Perencanaan Kapal (Perencanaan)
  - Permintaan Pengiriman (Eksekusi Pengiriman)
  - Booking Kontainer (Eksekusi Pengiriman)
  - Pelabuhan, Pelayaran, Kapal, Depo, Pelanggan, Pengguna (Master Data)
- **Page titles Indonesian**: Lembar Eksekusi Operasional, Registry Voyage, Tambah Voyage, Tambah Pelabuhan, Tambah Pelayaran, Tambah Depo, Tambah Pengguna, Tambah Pelanggan, Tambah Permintaan
- **Matrix headers Indonesian**: Kapal, Masalah, Aksi (was Vessel, Issue, Ops)
- **Matrix inline actions Indonesian**: Tandai (was Mark), + Kasus (was + Case)
- **Matrix issue texts Indonesian**: Delay, ETA Lewat, Risiko ETA, Lewat Jadwal, D-1 Terlambat, H-1 Risiko
- **Matrix status badges Indonesian**: Berlayar, Selesai, Terjadwal (was Sailing, Done, Sched)
- **Summary strip Indonesian**: Delay, Berlayar, Selesai, Terjadwal, Lewat (was Delayed, Sailing, Done, Sched, Overdue)
- **Detail sheet Indonesian**: Muatan (was Cargo), Penyebab (was Cause), Timeline Operasional, Kesiapan, Log Insiden Delay, Milestone
- **Empty states Indonesian**: Belum ada kejadian operasional, Belum ada data kesiapan, Belum ada catatan delay, Belum ada milestone
- **Modal labels Indonesian**: Tanggal & Waktu, Catatan, Catatan operasional opsional, Buat kasus delay baru untuk voyage ini?, Batal
- **VoyageResource form**: Tanggal Closing, Penyebab Delay, Periode, Kapal, Tampilkan Arsip, Arsipkan Terpilih

**Files modified**:
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Pages/MonitoringKapalTam.php`
- `app/Filament/Resources/*` (14 resources)
- `app/Filament/Resources/*/Pages/*` (10 page classes)
- `resources/views/filament/pages/monitoring-kapal-tam.blade.php`
- `resources/views/filament/pages/partials/tam-matrix-view.blade.php`
- `resources/views/filament/resources/voyage-resource/pages/view-voyage.blade.php`
- `resources/views/components/voyage-operational-timeline.blade.php`
- `resources/views/components/voyage-readiness-timeline.blade.php`
- `resources/views/components/voyage-milestone-progress.blade.php`
- `resources/views/filament/resources/voyage-resource/widgets/delay-history.blade.php`

## Next Phase

### Phase 13 - VesselCheck Case Deep Integration
- Connect VesselCheckCase to Monitoring Vessel
- Add case status to matrix view
- Quick follow-up panel
- Persistent acknowledgement
