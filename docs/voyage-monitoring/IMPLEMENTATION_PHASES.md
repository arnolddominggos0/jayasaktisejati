# Implementation Phases

## Completed Phases

### Phase 1 - Voyage Form & Detail Cleanup
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
