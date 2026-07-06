# Vessel Plan Canon Compliance Report

**Module:** Vessel Plan Workspace
**Based on:** Product Architecture Canon v1.1 (Frozen)
**Applied Review:** Sprint A1
**Sprint:** 12 — Vessel Plan Canon Implementation
**Date:** July 2026

---

## Final Verdict

**Partially Compliant** — Material violations V2, V3, V4 require implementation fixes. All violations have identified solutions traceable to Canon axioms. No architecture changes required.

---

## P1 — Canon Mapping

### Story (Axiom 1)

**Story = Monthly Vessel Plan** (per Sprint 11.5, Axiom 1 revised).

Unit kerja adalah satu narasi bulanan (`VesselPlan` dengan `period_month`). Narasi: Draft → Sent → Revision → Final. Individual vessels (`VesselPlanItem`) adalah Information within the Story, bukan sub-Stories — mereka tidak memiliki DC sendiri.

**Canon compliant.**

### Workspace (Axiom 2)

**Workspace = `EditVesselPlan` page.**

Container untuk satu Monthly Plan Story. Boundary = satu `VesselPlan` record. Workspace Integrity dijaga oleh status lifecycle (Draft/Sent/Revision/Final) dan `isEditable()` gate.

**Canon compliant.**

### Decision Context (Axiom 4)

| DC | Name | Location | Pending decision |
|---|---|---|---|
| DC1 | Orientation | `ListVesselPlans` | "Periode mana yang perlu perhatianku?" |
| DC2 | Edit Schedule | Tab 1 (Jadwal) | "Apakah jadwal ini benar dan lengkap?" |
| DC3 | Analyze | Tab 2 (Analisis) | "Apa risiko dan gap dalam jadwal ini?" |
| DC4 | Submit | Header action | "Apakah jadwal siap dikirim ke TAM?" |
| DC5 | Review/Wait | Sent status | "Apakah TAM akan approve?" |
| DC6 | Reject/Revise | Header action | "Apa yang perlu direvisi?" |
| DC7 | Finalize | Header action | "Apakah final schedule layak diapprove?" |
| DC8 | History | Tab 3 (Riwayat) | "Apa yang telah berubah?" |

**Canon compliant** — 8 DCs, masing-masing dengan pending decision yang jelas.

### Information (Axiom 3)

**Legitimate planning Information:**

| Information | Source | Canon status |
|---|---|---|
| Vessel name, shipping line | Master data | Fact |
| voyage_no | TAM/shipping line input | Fact |
| planned_etd, planned_eta, planned_etb | Planner input | Fact |
| planned_sailing_days | Derived from ETD/ETA | Derived fact |
| ETD gaps (antar kapal) | `VesselPlanAnalyzer` | Derived fact (relational) |
| max_gap, sailing_avg | `VesselPlanAnalyzer` | Derived fact |
| SOP status (valid/warning/critical) | `VesselPlanAnalyzer` | Derived fact |
| cargo_plan | TAM Final Schedule | Fact (legitimate post-Draft) |
| Review history (action, actor, timestamp) | `VesselPlanReview` | Fact (History) |
| Snapshots (draft, final) | `VesselPlanSnapshot` | Fact (History) |

**Illegitimate Information (domain leakage):**

| Information | Source | Canon status | Violation |
|---|---|---|---|
| dwelling_days (hardcoded 2) | `VesselPlanItem::getDwellingDaysAttribute()` | Operational constant | V3.6 |
| dooring_days (hardcoded 3) | `VesselPlanItem::getDooringDaysAttribute()` | Operational constant | V3.7 |
| total_kpi (dwelling + sailing + dooring) | `VesselPlanItem::getTotalKpiAttribute()` | Composite operational | V3.8 |
| draft_kpi_total | DB column + `VesselPlan::$fillable` | Legacy KPI field | V3.1 |
| final_kpi_total | DB column + `VesselPlan::$fillable` | Legacy KPI field | V3.2 |
| kpi_total accessor | `VesselPlan::getKpiTotalAttribute()` | Legacy accessor | V3.3 |
| kpi_deviation accessor | `VesselPlan::getKpiDeviationAttribute()` | Legacy accessor | V3.4 |
| kpi_deviation_label accessor | `VesselPlan::getKpiDeviationLabelAttribute()` | Legacy accessor | V3.5 |
| Dwelling=6, Dooring=3, Lead Time=19, Variance | `schedule-analysis.blade.php` hardcoded | TAM operational standards | V2.1 |
| Final ETD, Actual ATD from Voyage | `VesselPlanScheduleAnalysis` widget | Voyage operational data | V2.2 |
| Dwelling impact, Sailing impact, Dooring impact | `VesselPlanScheduleAnalysis` widget | Operational impact metrics | V2.2 |
| Avg dwelling, delay count from Voyage | `VesselPlanDashboard` widget (dead code) | Voyage operational data | V2.3 |

### Recognition (Axiom 5)

**Legitimate Recognition:**

| Recognition | DC | Derived from |
|---|---|---|
| Schedule items (vessel, ETD, ETA, sailing) | DC2 | Primary fact |
| ETD gap per item | DC2, DC3 | Derived fact (relational) |
| SOP status (valid/warning/critical) | DC1, DC2, DC3 | Derived fact |
| Max gap, sailing avg | DC1, DC3 | Derived fact |
| Risk level, violations text | DC3 | Derived from gap analysis |
| Review history | DC8 | Fact (History) |
| Draft vs Final delta | DC8 | Fact (History) |

**Illegitimate Recognition (produced by domain leakage):**

| Recognition | DC | Source | Violation |
|---|---|---|---|
| Dwelling days (6 or 2) | DC3 | Hardcoded constants | V2.1, V3.6 |
| Dooring days (3) | DC3 | Hardcoded constants | V2.1, V3.7 |
| Lead Time (6 + sailing + 3 = 19) | DC3 | Composite of operational | V2.1 |
| Variance (LT - 19) | DC3 | Derived from operational | V2.1 |
| ON STANDARD / MINOR / HIGH DEVIATION | DC3 | Derived from variance | V2.1 |
| Dwelling impact (Final ETD - Draft ETD) | DC3 (footer) | Voyage data | V2.2 |
| Sailing impact (Actual ATD - Final ETD) | DC3 (footer) | Voyage data | V2.2 |
| Dooring impact (dwelling + sailing impact) | DC3 (footer) | Voyage data | V2.2 |
| Avg dwelling from Voyage | All DC (footer) | Voyage data | V2.3 |
| Delay count from Voyage | All DC (footer) | Voyage data | V2.3 |

### Behavior Judgment (Axiom 6)

| BJ | DC | Decision | Canon status |
|---|---|---|---|
| BJ1 | DC2 | "Tambah/edit/hapus jadwal kapal" | Compliant |
| BJ2 | DC4 | "Apakah jadwal siap dikirim ke TAM?" | Compliant |
| BJ3 | DC7 | "Apakah final schedule layak diapprove?" | Compliant |
| BJ4 | DC6 | "Apa alasan penolakan?" | Compliant |
| BJ5 | DC2 | "Apakah plan ini perlu dihapus?" | Compliant |
| BJ6 | DC2 | "Apakah revisi sudah selesai?" (auto-transition Revision→Draft) | Compliant |

**Canon compliant** — all BJs have clear DC and sufficient Recognition.

### Behavior (Axiom 7)

| Behavior | Type | State change | Canon status |
|---|---|---|---|
| B1: Add/edit/delete VesselPlanItem | Action | Item created/updated/deleted | Compliant |
| B2: Submit Draft | Action | status: Draft→Sent; snapshot; WA sent | Compliant |
| B3: Finalize | Action | status: Sent→Final; snapshot; Voyage synced; schedule history | Compliant |
| B4: Reject | Action | status: Sent→Revision; feedback recorded | Compliant |
| B5: Edit during Revision | Auto-transition | status: Revision→Draft | Compliant |
| B6: Delete Vessel Plan | Action | Record deleted (Draft only) | Compliant |

**Canon compliant.**

### Workflow (Axiom 8)

**Canon Workflow (Story = Monthly Plan):**
```
Orientation → Edit Schedule → Analyze → Submit → Review → (Revision → Edit → Submit) → Finalize → History
```

**Current implementation:**
```
ListVesselPlans → EditVesselPlan(Tab 1) → Tab 2(Analisis) → Submit(action) → Sent(wait) → [Reject(action) → Revision → Edit(Tab 1) → Submit(action)] → Finalize(action) → Tab 3(Riwayat)
```

**Mapping:**

| Canon node | Implementation | Present? |
|---|---|---|
| Orientation | ListVesselPlans | Yes |
| Edit Schedule | Tab 1 (Jadwal) | Yes |
| Analyze | Tab 2 (Analisis) | Yes |
| Submit | Header action "Kirim ke TAM" | Yes |
| Review | Sent status (waiting TAM) | Yes |
| Revision | Reject action → Revision status → Edit | Yes |
| Finalize | Header action "Setujui & Finalisasi" | Yes |
| History | Tab 3 (Riwayat) + footer widget | Yes |

**Canon compliant** — all Workflow nodes present. No missing nodes (per Sprint 11.5, "Kapal Berikutnya" is vessel-centric instance, not needed for Monthly Plan Story).

### Navigation (Axiom 9)

| Transition | Mechanism | Canon status |
|---|---|---|
| List → Edit | Click record | Compliant |
| Tab 1 → Tab 2 → Tab 3 | Tab bar with `x-show` | Compliant |
| Edit → List → Edit | Breadcrumb/back | Compliant |
| Status transitions | Action buttons gated by status | Compliant |

**Canon compliant.**

### Subject Continuity (Axiom 10)

| Aspect | Status |
|---|---|
| Plan record persists across all DCs | Compliant |
| Snapshots preserve draft and final state | Compliant |
| Review log preserves action history | Compliant |
| Schedule history records draft vs final per vessel | Compliant |

**Canon compliant.**

### Disclosure (Axiom 4 — boundary property of DC)

| Information | Gate | Canon status |
|---|---|---|
| cargo_plan field | `!isDraft()` | Compliant |
| feedback_reason column | `isRevision()` | Compliant |
| Edit/Delete item actions | `isEditable()` | Compliant |
| Submit action | `isDraft()` + validation | Compliant |
| Finalize action | `isSent()` + validation | Compliant |
| Reject action | `isSent()` | Compliant |
| Delete plan action | `isDraft()` | Compliant |
| VesselPlanScheduleAnalysis (operational) | **Always visible (footer widget)** | **VIOLATION V2.2** |
| Tab 2 operational constants | **Visible in Analyze DC** | **VIOLATION V2.1** |
| VesselPlanDashboard (operational) | Dead code (not registered) | **VIOLATION V2.3** |

### Directedness (Axiom 12)

| DC | Attention directed to | Distraction? |
|---|---|---|
| DC1 (List) | Period list, status, SOP risk | None |
| DC2 (Edit) | Schedule items, form | **Footer widget VesselPlanScheduleAnalysis shows operational impact** (V4.2) |
| DC3 (Analyze) | SOP validation, gaps | **Dwelling/dooring/lead time/variance distract from planning analysis** (V4.1) |
| DC4-DC7 | Action confirmations | **Footer widgets visible during all action DCs** (V4.2) |
| DC8 (History) | Draft vs Final, review log | None |

---

## Complete Canon Violation List

### V2 — Domain Leakage (P0)

| ID | Location | Description | Canon axiom | File |
|---|---|---|---|---|
| V2.1 | Tab 2 Analisis | Hardcoded TAM operational constants: dwelling=6, sailing=10, dooring=3, lead_time=19, variance, ON STANDARD/MINOR/HIGH DEVIATION status | Axiom 3 (Information), Axiom 4 (Disclosure), Axiom 12 (Directedness) | `tabs/schedule-analysis.blade.php` |
| V2.2 | Footer widget | Pulls Voyage operational data (Final ETD, Actual ATD) into planning workspace. Calculates dwelling impact, sailing impact, dooring impact — all operational execution metrics | Axiom 3, Axiom 4, Axiom 12 | `Widgets/VesselPlanScheduleAnalysis.php` + `widgets/vessel-plan-schedule-analysis.blade.php` |
| V2.3 | Dead code widget | Pulls Voyage operational data (avg dwelling, delay count). Not registered in any page but file exists | Axiom 3 | `Widgets/VesselPlanDashboard.php` + `widgets/vessel-plan-dashboard.blade.php` |

### V3 — Legacy KPI Artifacts (P0)

| ID | Location | Description | Canon axiom | File |
|---|---|---|---|---|
| V3.1 | DB column + model | `draft_kpi_total` column in `vessel_plans` table, in `$fillable` | Axiom 3 | `VesselPlan.php`, migration `2026_04_10_163936` |
| V3.2 | DB column + model | `final_kpi_total` column in `vessel_plans` table, in `$fillable` | Axiom 3 | `VesselPlan.php`, migration `2026_04_10_163936` |
| V3.3 | Model accessor | `getKpiTotalAttribute()` — returns different values based on status | Axiom 3 | `VesselPlan.php:180-187` |
| V3.4 | Model accessor | `getKpiDeviationAttribute()` — calculates final - draft KPI total | Axiom 3 | `VesselPlan.php:189-196` |
| V3.5 | Model accessor | `getKpiDeviationLabelAttribute()` — formats deviation as label | Axiom 3 | `VesselPlan.php:198-209` |
| V3.6 | Model accessor | `getDwellingDaysAttribute()` — hardcoded `return 2;` | Axiom 3 | `VesselPlanItem.php:88-91` |
| V3.7 | Model accessor | `getDooringDaysAttribute()` — hardcoded `return 3;` | Axiom 3 | `VesselPlanItem.php:93-96` |
| V3.8 | Model accessor | `getTotalKpiAttribute()` — composite dwelling + sailing + dooring | Axiom 3 | `VesselPlanItem.php:98-107` |
| V3.9 | Service | `VesselPlanSubmissionService` sets `draft_kpi_total` | Axiom 3 | `VesselPlanSubmissionService.php:41` |
| V3.10 | Service | `VesselPlanFinalizationService` sets `final_kpi_total` | Axiom 3 | `VesselPlanFinalizationService.php:54` |

### V4 — Directedness Dilution (P0)

| ID | Location | Description | Canon axiom | File |
|---|---|---|---|---|
| V4.1 | Tab 2 Analisis | Mixes planning Recognition (sailing, gap, SOP) with operational Recognition (dwelling, dooring, lead time, variance) in same DC | Axiom 12 | `tabs/schedule-analysis.blade.php` |
| V4.2 | Footer widgets | `VesselPlanScheduleAnalysis` (operational) always visible regardless of active tab/DC — operational data visible during Edit Schedule DC | Axiom 4, Axiom 12 | `EditVesselPlan.php:91-93` (footer widget registration) |

### Dead Code

| ID | File | Status |
|---|---|---|
| DC1 | `Widgets/VesselPlanDashboard.php` | Not registered in any page |
| DC2 | `widgets/vessel-plan-dashboard.blade.php` | Dead blade |
| DC3 | `widgets/vessel-plan-summary.blade.php` | Not registered in any page |

### Redundancy

| ID | Description | Files |
|---|---|---|
| R1 | Tab 2 `schedule-analysis.blade.php` overlaps with header widget `VesselPlanAnalysis` — both show sailing avg and risk summary | `tabs/schedule-analysis.blade.php` + `widgets/vessel-plan-analysis.blade.php` |
| R2 | Footer widget `VesselPlanReviewHistory` (review log) and Tab 3 `schedule-history.blade.php` (draft vs final) are both "History" but in different locations — should be consolidated | `Widgets/VesselPlanReviewHistory.php` + `tabs/schedule-history.blade.php` |

---

## Refactoring Plan

### Phase 1 — Model Cleanup (P0, backend, no UI impact)

**Goal:** Remove all legacy KPI artifacts from VesselPlan and VesselPlanItem models.

| Step | Action | File | Canon axiom |
|---|---|---|---|
| 1.1 | Remove `getDwellingDaysAttribute()` | `app/Models/VesselPlanItem.php` | Axiom 3 |
| 1.2 | Remove `getDooringDaysAttribute()` | `app/Models/VesselPlanItem.php` | Axiom 3 |
| 1.3 | Remove `getTotalKpiAttribute()` | `app/Models/VesselPlanItem.php` | Axiom 3 |
| 1.4 | Remove `getKpiTotalAttribute()` | `app/Models/VesselPlan.php` | Axiom 3 |
| 1.5 | Remove `getKpiDeviationAttribute()` | `app/Models/VesselPlan.php` | Axiom 3 |
| 1.6 | Remove `getKpiDeviationLabelAttribute()` | `app/Models/VesselPlan.php` | Axiom 3 |
| 1.7 | Remove `draft_kpi_total` from `$fillable` | `app/Models/VesselPlan.php` | Axiom 3 |
| 1.8 | Remove `final_kpi_total` from `$fillable` | `app/Models/VesselPlan.php` | Axiom 3 |
| 1.9 | Remove `'draft_kpi_total' => round(...)` from `submit()` | `app/Services/VesselPlanSubmissionService.php` | Axiom 3 |
| 1.10 | Remove `'final_kpi_total' => round(...)` from `finalize()` | `app/Services/VesselPlanFinalizationService.php` | Axiom 3 |
| 1.11 | Create migration to drop `draft_kpi_total`, `final_kpi_total` columns | `database/migrations/` | Axiom 3 |

**Canon justification:** Axiom 3 (Information = substrat fakta dalam Workspace). Dwelling, dooring, total_kpi, kpi_total, kpi_deviation bukan fakta planning — adalah konstanta/metric operational execution layer. Mereka melanggar boundary domain planning.

**Dependency check:** Verify no other code references `dwelling_days`, `dooring_days`, `total_kpi`, `kpi_total`, `kpi_deviation`, `kpi_deviation_label`, `draft_kpi_total`, `final_kpi_total` before removing.

### Phase 2 — Dead Code Removal (P0, cleanup)

**Goal:** Remove widgets that are not registered and pull operational data.

| Step | Action | File |
|---|---|---|
| 2.1 | Delete `VesselPlanDashboard.php` | `app/Filament/.../Widgets/VesselPlanDashboard.php` |
| 2.2 | Delete `vessel-plan-dashboard.blade.php` | `resources/views/.../widgets/vessel-plan-dashboard.blade.php` |
| 2.3 | Delete `vessel-plan-summary.blade.php` | `resources/views/.../widgets/vessel-plan-summary.blade.php` |

**Canon justification:** Dead code yang pulls Voyage operational data (avg dwelling, delay count). Jika diaktifkan, melanggar Axiom 3 (domain leakage).

### Phase 3 — Footer Widget Removal (P0, Workspace refactoring)

**Goal:** Remove operational widget from footer; consolidate History into Tab 3.

| Step | Action | File | Canon axiom |
|---|---|---|---|
| 3.1 | Remove `VesselPlanScheduleAnalysis` from `getFooterWidgets()` | `app/Filament/.../Pages/EditVesselPlan.php` | Axiom 4, 12 |
| 3.2 | Delete or relocate `VesselPlanScheduleAnalysis.php` | `app/Filament/.../Widgets/VesselPlanScheduleAnalysis.php` | Axiom 3 |
| 3.3 | Delete or relocate `vessel-plan-schedule-analysis.blade.php` | `resources/views/.../widgets/vessel-plan-schedule-analysis.blade.php` | Axiom 3 |
| 3.4 | Remove `VesselPlanReviewHistory` from `getFooterWidgets()` | `app/Filament/.../Pages/EditVesselPlan.php` | Axiom 8 |
| 3.5 | Integrate review history into Tab 3 (Riwayat) | `resources/views/.../tabs/schedule-history.blade.php` | Axiom 8, 10 |

**Canon justification:**
- VesselPlanScheduleAnalysis pulls Voyage operational data (Final ETD, Actual ATD, dwelling/sailing/dooring impact) into planning Workspace — violates Axiom 3 (domain leakage) and Axiom 4 (Disclosure: operational data not legitimate in planning DC). Footer placement means it's visible in all DCs — violates Axiom 12 (Directedness dilution).
- VesselPlanReviewHistory is legitimate History (Axiom 10) but placing it in footer means it's visible across all DCs. Moving it into Tab 3 aligns with Axiom 8 (Workflow: History = last node) and Axiom 4 (Disclosure: History Recognition gated by History DC).

**Relocation option for VesselPlanScheduleAnalysis:** This widget's logic (Draft→Final→Actual impact analysis) belongs in the Voyage module (operational monitoring domain), not Vessel Plan (planning domain). If Voyage module has a similar view, merge there. If not, create as a new Voyage widget.

### Phase 4 — Tab 2 Rewrite (P0, Disclosure + Directedness)

**Goal:** Replace operational constants with planning-only analysis.

| Step | Action | File | Canon axiom |
|---|---|---|---|
| 4.1 | Rewrite `schedule-analysis.blade.php` to show planning-only analysis | `resources/views/.../tabs/schedule-analysis.blade.php` | Axiom 3, 4, 12 |
| 4.2 | Remove: dwelling=6, dooring=3, lead_time=19, variance, ON STANDARD/MINOR/HIGH | Same file | Axiom 3 |
| 4.3 | Add: ETD gap analysis table (per vessel pair), sailing comparison, continuity validation, SOP violation summary | Same file | Axiom 5 |

**New Tab 2 content (planning-only Recognition):**

| Section | Content | Source | Canon |
|---|---|---|---|
| Summary | Schedule count, sailing avg, max gap, SOP risk (same as header but detailed) | `VesselPlanAnalyzer` | Axiom 5 (Recognition) |
| Gap Analysis Table | Per-vessel: ETD, ETA, sailing days, ETD gap to previous, gap status badge | `VesselPlanAnalyzer::etdGaps()` | Axiom 5 |
| Continuity Validation | Vessel sequence with gap visualization, SOP threshold line (6 days) | `VesselPlanAnalyzer` | Axiom 5 |
| Violations Summary | List of SOP violations with reason text | `VesselPlanAnalyzer::$violations` | Axiom 5 |

**What is REMOVED from Tab 2:**

| Removed | Reason |
|---|---|
| Dwelling column (6) | Operational constant, not planning fact |
| Dooring column (3) | Operational constant, not planning fact |
| Lead Time column (19) | Composite of operational constants |
| Variance column | Derived from operational constants |
| ON STANDARD/MINOR/HIGH DEVIATION status | Derived from variance |
| Standards reminder cards (Dwelling/Sailing/Dooring/Lead Time) | Operational standards, not planning |
| Formula footer mentioning dwelling/dooring | Operational formula |

**Canon justification:** Axiom 3 (Information = planning substrat only). Axiom 4 (Disclosure gates Information by DC — operational metrics not legitimate in planning DC). Axiom 12 (Directedness — operational metrics distract from planning Recognition).

### Phase 5 — Header Widget Dedup (P1, redundancy cleanup)

**Goal:** Eliminate overlap between header widget and Tab 2.

| Step | Action | File |
|---|---|---|
| 5.1 | Keep header widget as compact SOP status strip (always visible — legitimate across planning DCs) | `widgets/vessel-plan-analysis.blade.php` |
| 5.2 | Tab 2 shows detailed analysis (gap table, violations) — not just summary | `tabs/schedule-analysis.blade.php` |

**Canon justification:** Header widget = Orientation Recognition (legitimate across DCs, like a status bar). Tab 2 = Analyze DC Recognition (detailed). No redundancy — different DC levels.

---

## P2 — Workspace Refactoring

### Current structure (Canon-violating)

```
EditVesselPlan
├── Header: VesselPlanAnalysis (SOP strip) — always visible ✓
├── Tab 1: Jadwal (Form + RelationManager) — DC2 ✓
├── Tab 2: Analisis (operational constants) — DC3 ✗ V2.1
├── Tab 3: Riwayat (draft vs final) — DC8 ✓
├── Footer: VesselPlanScheduleAnalysis (operational impact) — all DCs ✗ V2.2
└── Footer: VesselPlanReviewHistory (review log) — all DCs ✗ V4.2
```

### Target structure (Canon-compliant)

```
EditVesselPlan
├── Header: VesselPlanAnalysis (SOP strip) — always visible ✓
├── Tab 1: Jadwal (Form + RelationManager) — DC2 ✓
├── Tab 2: Analisis (planning-only: gap table, sailing, continuity, violations) — DC3 ✓
└── Tab 3: Riwayat (Schedule History + Review Log) — DC8 ✓
```

**Changes:**
1. Footer widgets removed (Phase 3)
2. Tab 2 rewritten with planning-only content (Phase 4)
3. Tab 3 expanded to include review log (Phase 3.5)
4. Header widget stays as compact status strip (Phase 5)

**Canon mapping of target structure:**

| Element | DC | Canon axiom | Status |
|---|---|---|---|
| Header (SOP strip) | Cross-DC orientation | Axiom 4, 12 | Compliant — SOP status legitimate across planning DCs |
| Tab 1 (Jadwal) | DC2 (Edit Schedule) | Axiom 4, 5, 6, 7 | Compliant — schedule items + edit actions |
| Tab 2 (Analisis) | DC3 (Analyze) | Axiom 4, 5, 12 | Compliant — planning analysis only |
| Tab 3 (Riwayat) | DC8 (History) | Axiom 4, 10 | Compliant — draft vs final + review log |

---

## P3 — Workflow Verification

### Canon Workflow (Story = Monthly Plan)

```
Orientation → Edit Schedule → Analyze → Submit → Review → (Revision) → Finalize → History
     DC1          DC2          DC3       DC4       DC5        DC6          DC7        DC8
```

### Implementation mapping

| DC | Trigger | Location | Canon status |
|---|---|---|---|
| DC1 | Navigate to List | `ListVesselPlans` | Compliant |
| DC2 | Click record / Edit | Tab 1 | Compliant |
| DC3 | Click Tab 2 | Tab 2 | Compliant (after Phase 4) |
| DC4 | Click "Kirim ke TAM" | Header action (DC2 active) | Compliant — BJ triggered from DC2 with sufficient Recognition |
| DC5 | Status = Sent | Implicit (waiting state) | Compliant — DC5 is passive (external actor) |
| DC6 | Click "Tolak / Kembalikan" | Header action (DC5 active) | Compliant — BJ triggered from Sent status |
| DC7 | Click "Setujui & Finalisasi" | Header action (DC5 active) | Compliant — BJ triggered from Sent status |
| DC8 | Click Tab 3 | Tab 3 | Compliant (after Phase 3.5) |

**Workflow verification: PASS.** No structural change needed. All DCs present and correctly ordered.

**Note on DC4/DC6/DC7:** These DCs are triggered via header actions while another tab/DC is visually active. This is Canon-compliant because:
- Actions are gated by status (Disclosure — only visible when legitimate)
- Submit action visible only when `isDraft()` — DC2 active
- Finalize/Reject visible only when `isSent()` — DC5 active
- The action IS the DC transition (Behavior Judgment → Navigation → DC change)

---

## P4 — Navigation Verification

### Navigation structure

| Navigation | Type | Canon status |
|---|---|---|
| List → Edit | Inter-Workspace | Compliant |
| Tab 1 → Tab 2 → Tab 3 | Intra-Workspace (inter-DC) | Compliant |
| Status transitions (Draft→Sent→Revision→Final) | DC transitions via Behavior | Compliant |
| Edit → List → Edit | Reversible | Compliant |

**Navigation is manifestasi Workflow.** Tab order (Jadwal → Analisis → Riwayat) maps to Workflow order (Edit → Analyze → History). Status transitions map to Workflow transitions (Submit, Reject, Finalize).

**After Phase 3:** Footer widgets removed → all Information gated by tab (DC) → Navigation = DC selection. No independent Navigation structure.

**Navigation verification: PASS.**

---

## P5 — Disclosure Verification

### Post-refactoring Disclosure map

| DC | Legitimate Information | Gated? | Canon status |
|---|---|---|---|
| DC1 (Orientation) | Period, status, jadwal count, avg sailing, max gap, SOP risk | Table columns | Compliant |
| DC2 (Edit) | Schedule items, form, ETD gap per item, SOP status header | Tab 1 active | Compliant |
| DC3 (Analyze) | Gap analysis, sailing comparison, continuity, SOP violations | Tab 2 active | Compliant (after Phase 4) |
| DC4 (Submit) | Current schedule, SOP status, WA recipient | Action modal | Compliant |
| DC5 (Review) | Sent status, waiting | Status badge | Compliant |
| DC6 (Reject) | Current schedule, SOP violations, reason form | Action modal | Compliant |
| DC7 (Finalize) | Final schedule, voyage_no completeness | Action modal | Compliant |
| DC8 (History) | Draft vs Final, delta, review log | Tab 3 active | Compliant (after Phase 3.5) |

**Information that is NO LONGER visible in any planning DC (after refactoring):**

| Removed Information | Reason |
|---|---|
| Dwelling days (6 or 2) | Operational constant — not planning fact |
| Dooring days (3) | Operational constant — not planning fact |
| Lead Time (19) | Composite of operational constants |
| Variance | Derived from operational |
| ON STANDARD/MINOR/HIGH DEVIATION | Derived from operational |
| Dwelling impact (Final ETD - Draft ETD from Voyage) | Operational execution data |
| Sailing impact (Actual ATD - Final ETD from Voyage) | Operational execution data |
| Dooring impact (dwelling + sailing impact) | Operational execution data |
| Avg dwelling from Voyage | Operational execution data |
| Delay count from Voyage | Operational execution data |

**Disclosure verification: PASS (after refactoring).**

---

## P6 — Behavior Verification

### Behavior chain per action

**B2: Submit Draft**
```
Recognition: items > 0, customer terhubung, WA number ada, SOP status
    ↓
Behavior Judgment: "Apakah jadwal siap dikirim ke TAM?"
    ↓
Behavior: submitDraft() → status: Draft→Sent, snapshot created, WA URL opened
```
Canon: Recognition (Axiom 5) → BJ (Axiom 6) → Behavior (Axiom 7). **Compliant.**

**B3: Finalize**
```
Recognition: Sent status, route ports synced, voyage_no complete, final schedule
    ↓
Behavior Judgment: "Apakah final schedule layak diapprove?"
    ↓
Behavior: finalizeSchedule() → status: Sent→Final, snapshot, Voyage synced, schedule history
```
Canon: Recognition → BJ → Behavior. **Compliant.**

**B4: Reject**
```
Recognition: Sent status, current schedule, SOP violations
    ↓
Behavior Judgment: "Apa alasan penolakan?"
    ↓
Behavior: reject() → status: Sent→Revision, feedback_reason/by/at recorded
```
Canon: Recognition → BJ → Behavior. **Compliant.**

**B5: Edit during Revision (auto-transition)**
```
Recognition: Revision status, feedback_reason visible
    ↓
Behavior Judgment: "Apakah revisi sudah selesai?" (implicit — Planner edits)
    ↓
Behavior: mutateFormDataBeforeSave() → status: Revision→Draft, feedback cleared
```
Canon: Recognition → BJ → Behavior. **Compliant.**

**B6: Delete Plan**
```
Recognition: Draft status, plan has no Sent/Final commitment
    ↓
Behavior Judgment: "Apakah plan ini perlu dihapus?"
    ↓
Behavior: delete() → record removed
```
Canon: Recognition → BJ → Behavior. **Compliant.**

**Behavior verification: PASS.** All behaviors follow Recognition → BJ → Behavior chain per Canon Axioms 5-7.

---

## Dependency on Other Modules

| Dependency | Direction | Canon status | Impact of refactoring |
|---|---|---|---|
| VesselPlan → Voyage | Vessel Plan produces Voyage via finalization | Compliant — Final Vessel Plan generates Voyage (per `OPERATIONAL_BOUNDARY.md`) | No change. Finalization service remains. |
| VesselPlan → Customer | Vessel Plan resolves TAM customer for submission | Compliant — customer is recipient of submission | No change. |
| VesselPlan → ShippingLine, Vessel, Port | Master data references | Compliant — planning uses master data as Information | No change. |
| VesselPlan → WhatsApp | Submission channel | Compliant — WA is Behavior output channel, not Information | No change. |
| Voyage → VesselPlan | Voyage reads VesselPlan for operational activation | Compliant — one-way dependency (planning → execution) | No change. |
| **VesselPlanScheduleAnalysis → Voyage** | **Widget reads Voyage operational data** | **VIOLATION — operational data flows backward into planning workspace** | **Removed in Phase 3.** |
| **VesselPlanDashboard → Voyage** | **Widget reads Voyage operational data** | **VIOLATION — same** | **Removed in Phase 2.** |

**Key dependency fix:** After refactoring, Vessel Plan Workspace no longer reads Voyage data. The only VesselPlan → Voyage relationship is via finalization (one-way output). This aligns with `OPERATIONAL_BOUNDARY.md`: "Vessel Plan is NOT: operational execution source, monitoring source, shipment source, KPI source."

---

## Implementation Roadmap

### Sprint 12.1 — Model Cleanup (Phase 1)

**Scope:** Remove legacy KPI from models and services.

| Task | File | Type |
|---|---|---|
| Remove 3 accessors from VesselPlanItem | `app/Models/VesselPlanItem.php` | Code deletion |
| Remove 3 accessors from VesselPlan | `app/Models/VesselPlan.php` | Code deletion |
| Remove 2 fields from `$fillable` | `app/Models/VesselPlan.php` | Code deletion |
| Remove KPI total from SubmissionService | `app/Services/VesselPlanSubmissionService.php` | Code deletion |
| Remove KPI total from FinalizationService | `app/Services/VesselPlanFinalizationService.php` | Code deletion |
| Create migration to drop KPI columns | `database/migrations/` | New migration |
| Search & verify no references to removed attributes | Codebase-wide | Verification |

**Risk:** Low. Accessors are hardcoded constants, not used in business logic. KPI columns store `sailing_avg` (misleadingly named) — verify `sailing_avg` is still available via `analyze()`.

**Verification:** `php artisan test` (if tests exist). Manual: open Vessel Plan list and edit page, verify no errors.

### Sprint 12.2 — Dead Code & Widget Cleanup (Phase 2 + 3)

**Scope:** Remove dead widgets and operational footer widget.

| Task | File | Type |
|---|---|---|
| Delete VesselPlanDashboard widget | `app/Filament/.../Widgets/VesselPlanDashboard.php` | File deletion |
| Delete VesselPlanDashboard blade | `resources/views/.../widgets/vessel-plan-dashboard.blade.php` | File deletion |
| Delete vessel-plan-summary blade | `resources/views/.../widgets/vessel-plan-summary.blade.php` | File deletion |
| Remove VesselPlanScheduleAnalysis from footer | `app/Filament/.../Pages/EditVesselPlan.php` | Code edit |
| Remove VesselPlanReviewHistory from footer | `app/Filament/.../Pages/EditVesselPlan.php` | Code edit |
| Delete or relocate VesselPlanScheduleAnalysis widget | `app/Filament/.../Widgets/VesselPlanScheduleAnalysis.php` | File deletion/relocation |
| Delete or relocate vessel-plan-schedule-analysis blade | `resources/views/.../widgets/vessel-plan-schedule-analysis.blade.php` | File deletion/relocation |

**Risk:** Low-medium. Removing footer widgets changes page layout. Verify EditVesselPlan page renders correctly without footer widgets.

### Sprint 12.3 — Tab 2 Rewrite (Phase 4)

**Scope:** Replace operational analysis with planning-only analysis.

| Task | File | Type |
|---|---|---|
| Rewrite Tab 2 with planning-only analysis | `resources/views/.../tabs/schedule-analysis.blade.php` | Rewrite |
| Remove: dwelling, dooring, lead time, variance, deviation status | Same file | Code deletion |
| Add: gap analysis table, sailing comparison, continuity visualization, violations | Same file | New content |

**New Tab 2 content specification:**

```
Tab 2: Analisis Jadwal (Planning Domain Only)
├── Summary bar: schedule count, sailing avg, max gap, SOP risk
├── Gap Analysis Table:
│   ├── Vessel name + voyage_no
│   ├── ETD (planned)
│   ├── ETA (planned)
│   ├── Sailing days (planned)
│   ├── ETD Gap to previous (days, color-coded)
│   └── Gap status (OK / Warning / Critical based on gap_limit)
├── Continuity Visualization:
│   ├── Sequential vessel timeline
│   └── SOP threshold line (6 days)
└── SOP Violations:
    ├── Violation text per vessel pair
    └── Risk level summary
```

**Risk:** Medium. Rewriting a full tab. Ensure analyzer data is correctly consumed.

### Sprint 12.4 — Tab 3 Consolidation (Phase 3.5)

**Scope:** Integrate review log into Tab 3.

| Task | File | Type |
|---|---|---|
| Add review log section to Tab 3 | `resources/views/.../tabs/schedule-history.blade.php` | Code addition |
| Include review history data in Tab 3 | Blade include or inline | Code addition |

**New Tab 3 structure:**

```
Tab 3: Riwayat (History DC)
├── Section 1: Schedule History (existing)
│   ├── Draft vs Final comparison table
│   └── Detail drawer (delta per vessel)
└── Section 2: Review Log (moved from footer widget)
    ├── Action log table (date, action, actor, detail)
    └── Detail modal
```

**Risk:** Low. Moving existing widget content into tab.

### Sprint 12.5 — Verification

| Task | Type |
|---|---|
| Verify all DCs have legitimate Information only | Manual audit |
| Verify no operational data in any planning DC | Manual audit |
| Verify all Behaviors work correctly | Manual test |
| Verify Disclosure gates (cargo_plan, feedback_reason, actions) | Manual test |
| Verify Tab 2 shows planning-only analysis | Manual test |
| Verify Tab 3 shows both schedule history and review log | Manual test |
| Run existing tests | `php artisan test` |
| Check for PHP errors/warnings | Browser console + Laravel logs |

---

## UI/UX Changes Required (Canon-derived, not visual preference)

| Change | Canon axiom | Rationale |
|---|---|---|
| Remove dwelling/dooring/lead time/variance from Tab 2 | Axiom 3, 4, 12 | Operational metrics not legitimate in planning DC |
| Remove footer widget VesselPlanScheduleAnalysis | Axiom 3, 4, 12 | Operational data not legitimate in planning Workspace |
| Move review log from footer to Tab 3 | Axiom 8, 10 | History should be in History DC, not visible across all DCs |
| Add gap analysis table to Tab 2 | Axiom 5 | Planning Recognition (gap analysis) is primary DC3 content |
| Add continuity visualization to Tab 2 | Axiom 5 | Planning Recognition (continuity) is primary DC3 content |

**Note:** These are NOT visual preferences. Each change is traced to a specific Canon axiom. The Canon determines WHAT information is legitimate in each DC; the UI merely implements the Canon requirement.

---

## Backend/Domain Model Changes Required

| Change | Canon axiom | Rationale |
|---|---|---|
| Remove `getDwellingDaysAttribute()` from VesselPlanItem | Axiom 3 | Hardcoded operational constant, not planning fact |
| Remove `getDooringDaysAttribute()` from VesselPlanItem | Axiom 3 | Same |
| Remove `getTotalKpiAttribute()` from VesselPlanItem | Axiom 3 | Composite of operational |
| Remove `getKpiTotalAttribute()` from VesselPlan | Axiom 3 | Legacy accessor |
| Remove `getKpiDeviationAttribute()` from VesselPlan | Axiom 3 | Legacy accessor |
| Remove `getKpiDeviationLabelAttribute()` from VesselPlan | Axiom 3 | Legacy accessor |
| Remove `draft_kpi_total` from `$fillable` + DB column | Axiom 3 | Legacy KPI field |
| Remove `final_kpi_total` from `$fillable` + DB column | Axiom 3 | Legacy KPI field |
| Remove KPI total setting from SubmissionService | Axiom 3 | Legacy field population |
| Remove KPI total setting from FinalizationService | Axiom 3 | Legacy field population |

---

## Success Criteria Checklist

| Criterion | Status |
|---|---|
| All material violations have implementation solution | Yes — V2.1 (Tab 2 rewrite), V2.2 (footer removal), V2.3 (dead code deletion), V3.1-V3.10 (model/service cleanup), V4.1-V4.2 (fixed by V2.1/V2.2) |
| No implementation decision contradicts Canon | Yes — all changes traced to specific axioms |
| Vessel Plan becomes first module fully built on Canon v1.1 | Pending implementation of Phases 1-5 |

---

## Canon Compliance Summary

| Canon Concept | Current Status | After Refactoring |
|---|---|---|
| Story (Axiom 1) | Compliant (Monthly Plan = Story, per Sprint 11.5) | Compliant |
| Workspace (Axiom 2) | Compliant | Compliant |
| Information (Axiom 3) | VIOLATION — domain leakage (V2, V3) | Compliant — operational data removed |
| DC + Disclosure (Axiom 4) | VIOLATION — footer widgets leak across DCs (V2.2, V4.2) | Compliant — all Information gated by DC |
| Recognition (Axiom 5) | VIOLATION — illegitimate Recognition in DC3 (V2.1) | Compliant — planning-only Recognition |
| Behavior Judgment (Axiom 6) | Compliant | Compliant |
| Behavior (Axiom 7) | Compliant | Compliant |
| Workflow (Axiom 8) | Compliant | Compliant — History consolidated in Tab 3 |
| Navigation (Axiom 9) | Compliant | Compliant |
| Subject Continuity (Axiom 10) | Compliant | Compliant |
| Product Rhythm (Axiom 11) | Compliant | Compliant |
| Directedness (Axiom 12) | VIOLATION — operational metrics dilute DC3 (V4.1, V4.2) | Compliant — operational data removed |
| Workspace Experience (Axiom 13) | Partially compliant | Compliant — all DCs clean |

**Final Verdict: Partially Compliant → will become Canon Compliant after Phase 1-5 implementation.**
