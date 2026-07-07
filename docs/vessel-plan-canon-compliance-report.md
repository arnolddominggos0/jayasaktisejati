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

---

## Sprint 12.3 — Planning Analysis Workspace Refinement (Decision Support)

**Date:** July 2026
**Scope:** Decision Support refinement of Tab 2 (Planning Analysis Workspace) delivered in Sprint 12.2. No new features — every Recognition must yield a clear Planner decision.

### Principle applied

> Planning Analysis is a Decision Support Workspace, not a Validation Report.
> Every element shown must help the Planner take a decision. If an information
> does not change the Planner's decision, it does not belong in the Workspace.

### Rules removed (false-positive, no Behavior Judgment)

| Removed rule | Source | Reason |
|---|---|---|
| ETA previous > ETD next (overlap route warning) | `VesselPlanAnalyzer::detectConflicts()` | Parallel vessel operation is normal; this condition is not a Vessel Plan business rule and produced no actionable decision. |
| ETA overlap antar vessel (same-ETD overlap) | `VesselPlanAnalyzer::detectConflicts()` | Several vessels are scheduled to sail in parallel — identical ETD is normal, not a conflict. |
| Konflik ETD/ETA UI section | `tabs/schedule-analysis.blade.php` | The section surfaced the two false-positive rules above. It added cognitive load without a Behavior Judgment. |

### Rules retained (legitimate planning-domain Recognition)

| Retained rule | Canon Axiom 5 Recognition | Actionability |
|---|---|---|
| Gap antar ETD | ETD gap per item (DC2/DC3) | Shown in Gap Analysis Table + per-vessel gap warning |
| Gap Status (OK / Warning / Critical) | SOP status derived fact | Color-coded badge per vessel |
| Invalid chronology (ETA ≤ ETD) | Single-vessel planning rule | Per-vessel warning with vessel name → fix in Tab Jadwal |
| Missing sailing days | New — vessel with ETD/ETA empty | Per-vessel warning with vessel name → fill ETD/ETA |
| Missing voyage | New — vessel without voyage_no | Per-vessel warning with vessel name → select voyage |
| Planning readiness | New — aggregated Decision Support | "Siap dikirim ke TAM" / "Belum siap" + specific reason counts |
| SOP violation | ETD gap > SOP limit | Per-vessel gap warning with vessel name + SOP limit |

### Canon v1.1 mapping of Sprint 12.3 changes

| Canon Axiom | Sprint 12.3 change | Effect |
|---|---|---|
| Axiom 3 (Information) | Tab 2 now shows only planning-domain facts that drive a decision. | No operational KPI, no false-positive overlap data. |
| Axiom 4 (DC + Disclosure) | "Konflik ETD/ETA" section removed from DC3. | DC3 receives only planning-domain Recognition. |
| Axiom 5 (Recognition) | Added Missing sailing days + Missing voyage Recognition; removed false-positive overlap Recognition. | Every Recognition is legitimate and planning-domain. |
| Axiom 6 (Behavior Judgment) | Planning Readiness banner is the main focus: "Siap dikirim ke TAM" / "Belum siap" + specific reason counts. | Each Recognition maps to a clear Planner decision. |
| Axiom 12 (Directedness) | Actionable warnings carry the vessel name to inspect; non-actionable warnings hidden for locked Final plans. | Planner attention is directed to fixable items only. |

### Architecture preservation (Sprint 12.3 constraint)

No destructive refactoring. The following legacy artifacts are retained until Sprint 12.5:

- `VesselPlanAnalyzer::detectConflicts()` method — kept, now emits Invalid chronology only (legacy `conflicts` string contract preserved).
- `conflicts` array key in `analyze()` return — kept (string list) so existing consumers keep working.
- `violations` array key — kept as plain-text list (consumed by `VesselPlan::sopStatus()` and the header KPI strip widget `VesselPlanAnalysis`).
- `ok` key semantics — unchanged (SOP gap validation result), consumed by `buildSopSnapshot()`.
- Legacy KPI accessors, footer widgets (`VesselPlanScheduleAnalysis`, `VesselPlanReviewHistory`), dead-code widgets — all untouched.
- No service, model, accessor, widget, database column, or migration removed.

### Files changed

| File | Change |
|---|---|
| `app/Services/VesselPlanAnalyzer.php` | Removed false-positive overlap rules from `detectConflicts()`; retained Invalid chronology; added `buildGapWarnings()`, `detectChronologyIssues()`, `detectMissingSailing()`, `detectMissingVoyage()`, `buildReadiness()`; added Decision Support outputs (`gap_warnings`, `chronology_issues`, `missing_sailing`, `missing_voyage`, `readiness`) to `analyze()` return. |
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Removed "Konflik ETD/ETA" section; promoted Planning Readiness to main focus (Siap / Belum siap + specific reason counts); added actionable per-vessel warnings section with vessel names; hid non-actionable warnings for locked Final plans. |

### Acceptance criteria status

- [x] Only planning-domain Recognition is displayed.
- [x] Every information yields a clear Behavior Judgment.
- [x] No false-positive (ETD/ETA overlap rules removed).
- [x] No operational KPI in Tab 2.
- [x] No ETD/ETA overlap warning.
- [x] Backend preserved — no destructive refactoring.

---

## Sprint 12.4 — Planning Analysis Workspace Polish (UX)

**Date:** July 2026
**Scope:** Presentation/UX polish of Tab 2 only. **No business logic, analyzer, service, model, or workflow change.** All changes limited to wording, information hierarchy, visual emphasis, and presentation within `schedule-analysis.blade.php`.

### Principle applied

> Planning Analysis should feel like a Planner's workspace, not a report page.
> Every element helps the Planner take a decision in under 10 seconds.

### Changes (presentation only)

| # | Change | What changed | Business logic? |
|---|---|---|---|
| 1 | Planning Readiness → Executive Summary | Banner now shows: title + narrative line + bullet reasons + action line. Explains WHY ready/not-ready, not just status. | No — uses already-computed `readiness` + structured counts from analyzer |
| 2 | KPI cards → Decision Summary | 4 cards reworded: "Jadwal / Sailing / Gap Terbesar / Status Plan" with natural unit phrasing. Removed dashboard-KPI nuance ("Jumlah Jadwal", "Avg Sailing", "Max ETD Gap", "Risiko SOP"). | No — same data, different wording |
| 3 | OK badge removed from table | "Status" column renamed "Perhatian". Normal vessels show "—". Badges appear only for exceptions: ⚠ Gap SOP, ⚠ Sailing kosong, ⚠ Voyage belum dipilih, ❌ ETA tidak valid. | No — exception detection uses already-computed analyzer data + item attributes |
| 4 | Executive Conclusion added below table | Small summary section: if all good → "✓ Seluruh jadwal memenuhi SOP... Planner dapat melanjutkan"; if issues → "⚠ Ditemukan N jadwal yang perlu diperbaiki" + vessel name list. | No — vessel list derived from already-computed structured arrays |
| 5 | Business logic preserved | `VesselPlanAnalyzer`, validation rules, readiness calculation, gap calculation, service layer, workflow, Canon mapping — all untouched. | N/A — no logic files modified |

### Canon v1.1 mapping of Sprint 12.4 changes

| Canon Axiom | Sprint 12.4 change | Effect |
|---|---|---|
| Axiom 5 (Recognition) | Exception-only badges in table; Executive Summary bullets. | Planner recognizes what needs action, not what is normal. |
| Axiom 6 (Behavior Judgment) | Executive Summary banner explains WHY (narrative + bullets + action line). | Planner understands readiness reason in <10 seconds. |
| Axiom 12 (Directedness) | OK badges removed; normal state is quiet ("—"). Executive Conclusion names only vessels that need attention. | Attention directed to exceptions only; normal state doesn't compete for attention. |
| Axiom 13 (Workspace Experience) | Information hierarchy: Executive Summary → Decision Summary → Table → Conclusion → Action detail. | Workspace feels like a decision workspace, not a validation report. |

### Files changed

| File | Change | Business logic? |
|---|---|---|
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Presentation polish only (wording, hierarchy, badges, conclusion section). | No |

### Architecture preservation (Sprint 12.4 constraint)

- `VesselPlanAnalyzer.php` — **NOT modified** (verified: file mtime unchanged from Sprint 12.3).
- `conflicts`, `violations`, `ok`, `gap_warnings`, `chronology_issues`, `missing_sailing`, `missing_voyage`, `readiness` analyzer outputs — all consumed as-is.
- No service, model, accessor, widget, database column, or migration touched.
- All presentation derivations (exec bullets, status label, exception badges, conclusion vessel list) computed in-blade from already-available structured data.

### Acceptance criteria status

- [x] Planner understands ready/not-ready reason in <10 seconds (Executive Summary with narrative + bullets + action line).
- [x] No repeated "OK" badge (table shows "—" for normal; badges only for exceptions).
- [x] Important information appears before table (Executive Summary + Decision Summary).
- [x] Executive summary appears after table (Executive Conclusion section).
- [x] No business logic or architecture change (only blade presentation).
- [x] All changes limited to Decision Support Workspace presentation.

---

## Sprint 12.5 — Planning Analysis Workspace Final Polish

**Date:** July 2026
**Principle:** Remove duplicate information. Increase signal-to-noise ratio.
**Scope:** Presentation de-duplication + compression only. **No business logic, analyzer, workflow, Canon, or service change.** No new components.

### Changes (presentation only)

| # | Change | What changed | Business logic? |
|---|---|---|---|
| 1 | Hapus "Planning Summary" section | Section "Planning Summary / Executive Conclusion" di bawah tabel dihapus seluruhnya. Informasi ini sudah disampaikan oleh Executive Summary di atas. | No — pure deletion of duplicate UI |
| 2 | Ringkas Executive Summary | Banner sekarang hanya: title + bullets. Dihapus: narrative line ("Semua persyaratan...") dan action line ("Planner dapat melanjutkan...") — CTA button sudah jelas. Wording bullet lebih ringkas: "memenuhi SOP" (bukan "berada dalam batas SOP"), "Tidak ada data wajib" (bukan "Tidak ditemukan data wajib"). | No — same data, fewer words |
| 3 | Persingkat Checklist Finalisasi | Header: "Persyaratan Finalisasi" → "Checklist Finalisasi" / "Checklist Submit". Labels Planner-consistent: "Voyage seluruh kapal telah dipilih", "POL / POD lengkap", "ETD Gap memenuhi SOP" (menggantikan row SOP-status yang redundan). | No — same pass/fail booleans |
| 4 | Ringkas Decision Summary Card | Label card ke-4: "Status Plan" → "Status". Label value: "Siap Submit"/"Siap Finalisasi" → "Ready". | No — wording only |
| 5 | Rapikan spacing | Container `space-y-5` → `space-y-3` (~30% reduction). Card padding `py-3` → `py-2.5`. Card gap `gap-3` → `gap-2.5`. Checklist padding `py-4` → `py-3`, `space-y-2.5` → `space-y-1.5`. Actionable padding `py-4` → `py-3`. | No — CSS only |
| 6 | Tabel tidak diubah | Gap table tetap sederhana: tidak tambah warna / icon / badge / warning. | N/A — unchanged |

### Removed artifacts (de-duplication)

| Removed | Reason |
|---|---|
| Section "Planning Summary" (Executive Conclusion) | Duplikasi dari Executive Summary; tidak ada informasi baru. |
| Narrative line di Executive Summary ("Semua persyaratan... sudah dipenuhi") | Bunyi dari title + bullets sudah jelas. |
| Action line di Executive Summary ("Planner dapat melanjutkan...") | Tombol Setujui & Finalisasi / Kirim Draft adalah CTA yang jelas. |
| SOP-status row di Checklist (terpisah dari finalize checks) | Digantikan oleh baris checklist "ETD Gap memenuhi SOP" — satu sumber, bukan dua. |
| Kata "Plan" pada label card "Status Plan" | Konteks halaman sudah jelas bahwa ini adalah status Plan. |
| Variabel derivasi conclusion (`$issueVessels`, `$issueVesselCount`, `$hasIssues`, `$conclusionAction`) | Tidak lagi dipakai setelah section dihapus. |

### Canon v1.1 mapping of Sprint 12.5 changes

| Canon Axiom | Sprint 12.5 change | Effect |
|---|---|---|
| Axiom 5 (Recognition) | Executive Summary satu-satunya ringkasan keputusan. | Tidak ada Recognition duplikat di Workspace. |
| Axiom 6 (Behavior Judgment) | Setiap informasi muncul tepat satu kali; CTA button menangani langkah berikutnya. | Behavior Judgment tetap jelas, tanpa pengulangan. |
| Axiom 12 (Directedness) | Signal-to-noise ratio meningkat — lebih banyak informasi dalam satu layar. | Perhatian Planner tidak terbagi oleh duplicate summary. |
| Axiom 13 (Workspace Experience) | Spacing dipadatkan ~20-30% — informasi penting terlihat tanpa scroll pada layar pertama. | Workspace terasa ringkas, bukan panjang. |

### Files changed

| File | Change | Business logic? |
|---|---|---|
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Presentation de-duplication + spacing compression. | No |

### Architecture preservation (Sprint 12.5 constraint)

- `VesselPlanAnalyzer.php` — **NOT modified** (verified: file mtime unchanged from Sprint 12.3).
- `conflicts`, `violations`, `ok`, `gap_warnings`, `chronology_issues`, `missing_sailing`, `missing_voyage`, `readiness` analyzer outputs — all consumed as-is.
- No Canon change. No workflow change. No new component.
- No service, model, accessor, widget, database column, or migration touched.

### Acceptance criteria status

- [x] Tidak ada informasi yang muncul lebih dari satu kali (Planning Summary dihapus, SOP row disatukan ke checklist).
- [x] Executive Summary menjadi satu-satunya ringkasan keputusan.
- [x] Checklist hanya berisi validasi final (3 baris: Voyage / POL-POD / Gap SOP), bukan kesimpulan.
- [x] Satu layar pertama cukup untuk memahami kondisi plan (spacing ~30% lebih rapat).
- [x] Tidak ada perubahan business logic.
- [x] Tidak ada perubahan Canon.
- [x] Tidak ada komponen baru.

---

## Sprint 12.5 — Schedule Workspace Polish (Workspace Freeze)

**Date:** July 2026
**Principle:** Remove duplicate information. Increase signal-to-noise ratio. One product feel across all 3 tabs.
**Scope:** Workspace-wide UX polish across Tab Jadwal, Tab Analisis, Tab Riwayat, Edit Modal, dan Tab Jadwal page heading. **No business logic, analyzer, workflow, Canon, service, model, or migration change.** No new components — only wording, typography, color, density, hierarchy, consistency.
**Status:** Workspace Freeze — setelah sprint ini, modul Vessel Plan tidak lagi dimodifikasi kecuali bug fix atau perubahan kebutuhan bisnis yang nyata.

### Canonical decisions (frozen)

| Area | Decision |
|---|---|
| **Voyage format** | Kanon: `{Vessel} font-semibold gray-800` / `V.NNN · {Shipping Line}` (voyage gray-500 font-mono xs, shipping line gray-400 xs). Dipakai di Tab Jadwal (Filament description), Tab Analisis (custom blade), Tab Riwayat (custom blade), Modal Edit (Filament form input). |
| **Section headings** | Tab-level headings konsisten Indonesian, bukan English (e.g. bukan "Final Schedule TAM" / "Planning Analysis" / "Schedule History Logbook"). Phase-aware wording dengan satu noun utama. |
| **Cargo Plan label** | "Cargo Plan" → "Rencana Muatan". |
| **Empty value display** | `—` diganti dengan "Belum diisi" (warna abu) saat konteks informative, dipertahankan `—` untuk cell angka murni / ketiadaan SOP-level. |
| **ETD Gap warning badge** | Semantic color tier: `≤6 hari` = emerald (OK, success), `7–10 hari` = amber (warning), `≥10 hari` = red (critical).  Diterapkan konsisten di tab Analisis gap column + Tab Jadwal badge + legenda. |
| **Action buttons** | "+ Tambah Jadwal" (icon + label) konsisten untuk header action & empty-state action. Action bar Edit/Delete = icon buttons dengan jarak konsisten (Filament default + `mx-0.5`). |
| **Color palette** | Sistem semantic: green=success/final, amber=warning, red=critical/invalid, gray=neutral/draft/belum-diisi. Tidak ada warna tidak terdaftar. |
| **Density** | Padding custom-blade tables dikurangi ~10-15% (`py-3` → `py-2.5`) untuk lebih banyak kapal per layar. Tab Jadwal mengikuti density native Filament. |

### Changes (presentation only)

| # | Scope | File | Change | Business logic? |
|---|---|---|---|---|
| 1 | Samakan format Voyage | RelationManager + schedule-analysis + schedule-history | Format kanon `V.NNN · Shipping Line` + typography hierarchy (vessel semibold > voyage gray-500 > shipping gray-400) | No — wording/typography only |
| 2 | Konsistensi heading | RelationManager + schedule-history | "Final Schedule TAM" → "Jadwal Kapal"; "Draft Jadwal Kapal" → "Jadwal Kapal"; "Schedule History Logbook" → "Riwayat Jadwal" | No — wording only |
| 3 | Tombol | RelationManager | "Tambah Jadwal" → "+ Tambah Jadwal" (+ icon) untuk header action & empty-state action | No — wording only |
| 4 | Badge ETD Gap semantic color | schedule-analysis | Gap column: ≤6 → `text-emerald-700`, 7–9 → `text-amber-700`, ≥10 → `text-red-700`. Legend diperbarui dengan tier legend | No — color styling only; relation badge tetap native Filament semantic (already in place since Sprint 12.2) |
| 5 | Cargo Plan wording | RelationManager | "Cargo Plan" → "Rencana Muatan"; placeholder "—" → "Belum diisi" (abu) di Tab Jadwal | No — label/placeholder only |
| 6 | Action column spacing | RelationManager | Edit/Delete `iconButton()` + `mx-0.5` extraAttributes | No — native Filament valid API |
| 7 | Table density | schedule-analysis + schedule-history | `py-3` → `py-2.5` (~10-15% reduction) | No — CSS only |
| 8 | Typography | schedule-analysis + schedule-history | Vessel `font-semibold gray-800` > voyage `gray-500 font-mono text-[11px]` > shipping `gray-400 text-[11px]` | No — typography classes |
| 9 | Empty state | RelationManager + history blade | Tab Jadwal: `emptyStateHeading('Belum ada jadwal kapal')` + `emptyStateDescription` + `+ Tambah Jadwal` action button. Riwayat: wording Indonesian natural | No — wording/UI only |
| 10 | Header Card Tab Jadwal | edit-vessel-plan | Header card "JADWAL KAPAL / {N} jadwal untuk periode ini / Status: {phase}" equivalent to Tab Analisis Executive Summary | No — pure presentational card |
| 11 | Wording natural | All | "Final Schedule TAM" (heading) → "Jadwal Kapal"; "Cargo Plan" → "Rencana Muatan"; domain terms (ETD, ETA, ETB, Voyage, Sailing, ETD Gap) dipertahankan | No — wording only |
| 12 | Color system | All | Semantic palette green/amber/red/gray only; no off-palette Tailwind class as Filament color alias | No — palette alignment |

### Files changed (Sprint 12.5 Workspace Polish)

| File | Change | Business logic? |
|---|---|---|
| `app/Filament/Resources/VesselPlanResource/RelationManagers/VesselPlanItemRelationManager.php` | Tab Jadwal section heading/label/placeholder/empty-state/button wording + Voyage format + action spacing | No |
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Tab Analisis voyage typography + ETD Gap semantic color tier + legend + row density | No |
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-history.blade.php` | Tab Riwayat heading "Riwayat Jadwal" + Voyage V. prefix + density + empty-state wording | No |
| `resources/views/filament/resources/vessel-plan-resource/pages/edit-vessel-plan.blade.php` | Header card Tab Jadwal (JADWAL KAPAL + count + Status chip) | No |

### Architecture preservation (Sprint 12.5 Workspace Freeze)

- `VesselPlanAnalyzer.php` — **NOT modified** (still mtime 10:29 from Sprint 12.3).
- Canon, workflow, behavior, Recognition, behavior judgment — **unchanged**.
- No service / model / accessor / widget / database column / migration touched.
- All Filament APIs used verified to exist on Filament 3.3 (`color()`, `placeholder()`, `extraAttributes()`, `emptyStateHeading/Description/Actions()`).
- No invalid API calls in committed code.

### Canon v1.1 mapping of Sprint 12.5 Workspace Polish

| Canon Axiom | Sprint 12.5 change | Effect |
|---|---|---|
| Axiom 3 (Information) | Voyage / Shipping Line / Cargo wording consistent & natural. | Information terbaca konsisten lintas tab. |
| Axiom 5 (Recognition) | Voyage format kanon + visual hierarchy (vessel > voyage > shipping). | Recognition cepat discan, tidak perlu berpindah tab. |
| Axiom 12 (Directedness) | ETD Gap tier color + empty value bukan dash + density padding. | Sinyal visual langsung → keputusan, tanpa interpretasi tambahan. |
| Axiom 13 (Workspace Experience) | Header Card di Tab Jadwal + heading konsisten Indonesian + spacing dipadatkan. | Workspace terasa satu produk, bukan kumpulan halaman. |

### Acceptance criteria status

- [x] Workspace terasa satu produk (heading Indonesian konsisten, header card di Tab Jadwal setara Executive Summary di Tab Analisis).
- [x] Konsisten typography di Tab Jadwal / Analisis / Riwayat (vessel semibold > voyage gray-500 > shipping gray-400).
- [x] Konsisten format Voyage & Shipping Line kanon `V.NNN · {Shipping Line}`.
- [x] Konsisten semantic color palette (green/amber/red/gray only).
- [x] Badge ETD Gap semantic color sesuai tier risiko.
- [x] Nilai kosong tidak ditampilkan dash bila bisa dijelaskan ("Belum diisi" abu).
- [x] Hierarki visual diperkuat (heading > subheading > tabel > action).
- [x] Tidak ada perubahan business logic, workflow, service, analyzer, model, migration, struktur data.

### Freeze note

Sprint 12.5 menandai pembekuan Workspace Vessel Plan. Modul ini **tidak lagi dimodifikasi** kecuali:
1. Bug fix,
2. Perubahan kebutuhan bisnis yang nyata.

Workspace Vessel Plan dinyatakan lengkap dan matang.

---

## Sprint 12.5 — Decision Review Workspace Polish (UX Final)

**Date:** July 2026
**Objective:** Decision Support UX final — Planner mengambil keputusan dalam ≤10 detik.
**Scope:** UX polish only. **No business logic, analyzer, workflow, Canon, service, model, migration change.** No new component. Sprint 12.5 ini melengkapi Workspace Polish dengan repositioning Tab 2 sebagai **Decision Review Workspace** (bukan halaman analisis eksploratif).

### Positioning shift

Tab 2 sebelumnya disebut "Analisis Jadwal" — menyarankan halaman eksplorasi. Padahal isinya adalah **review sebelum approval**: Planner memutuskan submit / finalize. Maka:

- Tab name: `Analisis Jadwal` → **`Review Jadwal`**
- Workflow baru: **Jadwal → Review Jadwal → Riwayat Jadwal** (alur natural)

### Hierarchy re-ordered (Sprint 12.5 Decision Review)

Planner membaca halaman dalam urutan keputusan:
```
1. Executive Summary   → status + narrative + alasan
2. Decision Summary     → 4 cards ringkas
3. Exception First     → scan layer SEBELUM tabel
4. Tabel Jadwal        → Supporting Information (verifikasi, demoted)
5. Checklist            → gate validation (no narrative repeat)
```

Tabel bukan lagi pusat perhatian — tabel tempat verifikasi cepat. Fokus utama: Executive Summary + Exception.

### Changes (presentation only)

| # | Scope | Change | Business logic? |
|---|---|---|---|
| 1 | Rename Tab | "Analisis Jadwal" → "Review Jadwal" (tab bar + page container docblock) | No |
| 2 | Executive Summary | Tambah narrative "Semua persyaratan telah terpenuhi" / "Masih terdapat item yang perlu diperbaiki". Bullets lebih pendek & natural: "9 jadwal telah diverifikasi", "ETD Gap sesuai SOP", "Data wajib telah lengkap". | No |
| 3 | Decision Cards rename | "Jadwal" → "Total Jadwal", "Sailing" → "Rata-rata Sailing", "Gap Terbesar" → "Gap ETD Terbesar", "Status" → "Status Plan". Label value: "Ready" → "Siap Submit" / "Siap Finalisasi" / "Perlu Review". | No |
| 4 | Table polish | Voyage format kanon `V.NNN · Shipping Line` (sudah ada di Sprint 12.5 Workspace Polish, dipertahankan). Sailing kosong: `—` → **"Belum diisi"** (abu, bukan dash). Kolom terakhir "Perhatian" tetap; `—` warna abu (gray-300), exception badge only. | No |
| 5 | **Exception First** | Section baru SEBELUM tabel: "Tidak ada exception. Semua jadwal memenuhi aturan planning." (emerald) atau "{N} exception ditemukan" + bullet list per exception dengan nama vessel (amber/red). | No — derived from already-computed structured arrays |
| 6 | Hierarchy reorder | Urutan div: Executive Summary → Decision Summary → Exception → Tabel → Checklist. Tabel di-label "Tabel Verifikasi Jadwal". | No |
| 7 | Checklist as gate | Checklist Finalisasi: 3 baris gate ("Voyage seluruh kapal tersedia", "Route POL/POD lengkap", "ETD Gap sesuai SOP"). Tidak ada kalimat "Planner dapat melanjutkan..." (Executive Summary sudah mengatakannya). | No — wording only |
| 8 | Semantic colors | Hijau=ready/valid/success, Amber=warning/perlu review, Red=critical, Abu=belum diisi/draft/tidak berlaku. Tidak ada warna lain. "Perlu Review" menggantikan "Perlu Perbaikan" (kuning, bukan merah) — lebih tepat secara workflow. | No |
| 9 | Typography | Heading "Review Jadwal" 18px bold (text-lg font-bold). Subtitle "Ringkasan kesiapan jadwal sebelum dikirim ke TAM." 14px gray. Vessel semibold gray-800 > Voyage V.NNN gray-500 font-mono xs > Shipping Line gray-400 xs. | No |
| 10 | Empty state | "Belum ada jadwal kapal." + "Tambahkan jadwal pertama untuk memulai perencanaan." (stat natural, tanpa button — Tab Review tidak bertanggung jawab menambah jadwal; itu di Tab Jadwal). | No |
| 11 | No business logic change | VesselPlanAnalyzer, workflow, service, validation, Canon mapping, business rule, ETD Gap calculation, readiness logic, finalization logic — **tidak diubah**. | N/A |

### Canon v1.1 mapping of Sprint 12.5 Decision Review Polish

| Canon Axiom | Sprint 12.5 Decision Review change | Effect |
|---|---|---|
| Axiom 3 (Information) | Information dipersepsi dalam urutan keputusan (Exec → Cards → Exception → Tabel → Checklist). | Planner tidak reading-time panjang di tabel detail. |
| Axiom 5 (Recognition) | Exception-First section menampilkan hanya item yang perlu ditindaklanjuti, dengan nama vessel. | Planner langsung mengenali masalah tanpa membaca tabel. |
| Axiom 6 (Behavior Judgment) | Executive Summary narrative (semua persyaratan terpenuhi / masih ada yang perlu diperbaiki) + bullets ringkas. | Keputusan (submit / finalize / perbaiki) jelas dalam ≤10 detik. |
| Axiom 12 (Directedness) | Tabel demoted jadi "Tabel Verifikasi Jadwal"; `—` abu; exception badge only. | Perhatian diarahkan ke exception, bukan ke keadaan normal. |
| Axiom 13 (Workspace Experience) | Tab rename "Review Jadwal"; terminology konsisten (Jadwal / Review Jadwal / Riwayat Jadwal); checklist sebagai gate; hierarchy rapi. | Workspace terasa satu produk; alur natural. |

### Files changed (Sprint 12.5 Decision Review Polish)

| File | Change | Business logic? |
|---|---|---|
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Header rename, exec narrative, decision cards rename, Exception First section, table demoted, checklist as gate, typography hierarchy, semantic colors. | No |
| `resources/views/filament/resources/vessel-plan-resource/pages/edit-vessel-plan.blade.php` | Tab bar label "Analisis Jadwal" → "Review Jadwal"; docblock updated. | No |

### Architecture preservation (Sprint 12.5 Decision Review Polish)

- `VesselPlanAnalyzer.php` — **NOT modified** (verified: mtime 10:29 from Sprint 12.3).
- Backend legacy artifacts (`conflicts`, `violations`, `ok`, `gap_warnings`, `chronology_issues`, `missing_sailing`, `missing_voyage`, `readiness`) — consumed as-is.
- Canon, workflow, behavior, Recognition, behavior judgment — **unchanged**.
- No service / model / accessor / widget / database column / migration touched.
- All presentation derivations (exec narrative, status label, exception list, status plan label) computed in-blade from already-available structured data.

### Acceptance criteria status

- [x] Planner memahami status plan ≤10 detik (Executive Summary + Exception First sebelum tabel).
- [x] Tidak ada informasi diulang di dua section (Checklist tidak lagi mengulang Executive Summary; Planning Summary sudah dihapus di Sprint 12.5 sebelumnya).
- [x] Exception selalu terlihat sebelum tabel (Exception First section).
- [x] Terminology konsisten (Jadwal → Review Jadwal → Riwayat Jadwal).
- [x] Penulisan Voyage, Shipping Line, badge, warna, typography konsisten lintas workspace.
- [x] Checklist sebagai gate validasi (3 baris tunggal, tanpa narrative).
- [x] Tabel sebagai supporting evidence (dilabel "Tabel Verifikasi Jadwal", demoted).
- [x] Tidak ada perubahan business logic, analyzer, service, workflow, model, migration, struktur data.

### Planner target experience

```
Buka Review Jadwal
  ↓
5–10 detik: baca Executive Summary → tahu siap / belum siap
  ↓
Ada masalah? → lihat Exception First → langsung tahu vessel mana & apa
  ↓
Tidak ada masalah? → verifikasi cepat di Tabel → klik Setujui & Finalisasi
```

Workspace ini sekarang **Decision Review Workspace** sejati — mendukung pengambilan keputusan secara cepat dan percaya diri, bukan halaman laporan.

---

## Sprint 12.6 — Review Workspace Final Polish (UX Freeze)

**Date:** July 2026
**Objective:** Polish terakhir sebelum Workspace Vessel Plan dibekukan.
**Scope:** Visual hierarchy, wording, consistency, information density, decision clarity. **Tidak ada perubahan business logic, analyzer, workflow, validation, service, struktur data.**

### Freeze Rule (post-sprint)

Setelah Sprint 12.6:
- ❌ Tidak boleh menambah KPI baru.
- ❌ Tidak boleh menambah card baru.
- ❌ Tidak boleh menambah widget baru.
- ❌ Tidak boleh menambah summary baru.
- ✓ Perubahan berikutnya hanya: bug fix, perubahan requirement bisnis, improvement kecil hasil usability test.

### Changes (presentation only)

| # | Scope | Change | Business logic? |
|---|---|---|---|
| 1 | Executive Summary dipadatkan | Bullets lebih singkat: "9 jadwal diverifikasi" (bukan "9 jadwal telah diverifikasi"), "ETD Gap sesuai SOP" (sudah ringkas), "Data wajib lengkap" (bukan "Data wajib telah lengkap"). Narrative spesifik per fase: "Seluruh persyaratan submit telah terpenuhi." / "Seluruh persyaratan finalisasi telah terpenuhi." Padding `py-3` → `py-2.5`, icon `w-5` → `w-4`, bullet `text-sm` → `text-xs`. | No — wording/density only |
| 2 | Exception Box satu kalimat | Tanpa exception: "✓ Tidak ada exception yang memerlukan tindak lanjut." (satu kalimat emerald, padding `py-2`). Dengan exception: "⚠ {N} exception memerlukan perhatian." + bullet list vessel — tidak ada paragraf tambahan. | No — wording/density only |
| 3 | Decision Cards polish | "Gap ETD Terbesar" → "Gap ETD Maksimum". Status value dirender sebagai **badge kecil** (rounded-full text-xs, emerald/amber) — bukan heading besar `text-xl font-black`. | No — wording/badge only |
| 4 | Table header | "Tabel Verifikasi Jadwal" → "Daftar Jadwal" (lebih natural, lebih pendek). | No — wording only |
| 5 | Voyage typography | Dipertahankan kanon `V.NNN · Shipping Line` (vessel semibold gray-800 > voyage gray-500 font-mono xs > shipping gray-400 xs). Tidak ada variasi. | No — typography dipertahankan |
| 6 | Table density | Padding `py-2.5` → `py-2` (~10% reduction). Lebih banyak kapal per layar, tetap nyaman. | No — CSS only |
| 7 | ETD Gap legend dipangkas | Legend sekarang satu baris singkat: "ETD Gap = selisih ETD kapal sebelumnya. Target SOP ≤ 6 hari." Penjelasan warna (hijau/kuning/merah) dihapus — badge + colored text sudah menjelaskan sendiri. | No — wording only |
| 8 | Checklist Finalisasi compact | Checklist diubah dari card besar menjadi footer-style inline: `flex-wrap items-center gap-x-4`, icon `w-3.5`, text-xs. Labels dipangkas: "Voyage tersedia" (bukan "Voyage seluruh kapal tersedia"), "POL/POD lengkap" (bukan "Route POL/POD lengkap"), "ETD Gap sesuai SOP". Tidak ada card border, tidak ada card padding — hanya footer checklist ringkas. | No — wording/density only |
| 9 | White space | Container `space-y-3` → `space-y-2.5` (~17% reduction). Mata bergerak lebih cepat antar section. | No — CSS only |
| 10 | Konsistensi Bahasa | "Perlu Review" → "Perlu Perbaikan" (lebih tepat secara workflow, badge amber). Domain terms (ETD, ETA, ETB, Voyage, SOP) tetap. Bahasa Indonesia konsisten. | No — wording only |
| 11 | Icon consistency | Hanya semantic icon: ✓ (success, emerald), ⚠ (warning, amber), ✕ (error, red). Tidak ada icon dekoratif lain. Badge exception sekarang menampilkan prefix icon (`✕ ETA tidak valid`, `⚠ Gap SOP`, dll) — semantic + konsisten. | No — icon only |
| 12 | Freeze Rule | Didokumentasikan di sini. Setelah sprint ini, tidak ada penambahan KPI / card / widget / summary. | N/A |

### Information density comparison

| Section | Sprint 12.5 | Sprint 12.6 |
|---|---|---|
| Executive Summary | py-3, icon w-5, bullets text-sm | py-2.5, icon w-4, bullets text-xs |
| Exception Box | card py-3 + paragraf "Semua jadwal memenuhi aturan planning." | box py-2 + satu kalimat "Tidak ada exception yang memerlukan tindak lanjut." |
| Decision Card Status | text-xl font-black (heading besar) | badge kecil rounded-full text-xs (lebih netral) |
| Gap ETD card label | "Gap ETD Terbesar" | "Gap ETD Maksimum" |
| Table header label | "Tabel Verifikasi Jadwal" | "Daftar Jadwal" |
| Table row padding | py-2.5 | py-2 (~10% reduction) |
| ETD Gap legend | 4 baris dengan penjelasan warna | 1 baris, tanpa penjelasan warna |
| Checklist | card border besar py-3, 3 baris vertikal space-y-1.5 | inline footer flex-wrap, no card, gap-x-4 |
| Container space | space-y-3 | space-y-2.5 |
| Status Plan value | "Ready" (English) | "Siap Submit" / "Siap Finalisasi" / "Perlu Perbaikan" (Indonesia) |

### Canon v1.1 mapping of Sprint 12.6 UX Freeze

| Canon Axiom | Sprint 12.6 change | Effect |
|---|---|---|
| Axiom 3 (Information) | Information dipadatkan; legend dipangkas; checklist inline. | Density tinggi, scanning cepat. |
| Axiom 5 (Recognition) | Exception box satu kalimat; badge exception prefix icon. | Recognition langsung jelas tanpa paragraf. |
| Axiom 6 (Behavior Judgment) | Executive Summary lebih singkat; Status sebagai badge netral (bukan heading besar). | Behavior Judgment cepat dipahami. |
| Axiom 12 (Directedness) | White space lebih rapat; checklist sebagai footer; badge kecil. | Perhatian diarahkan ke keputusan, tidak didistraksi oleh visual weight berlebih. |
| Axiom 13 (Workspace Experience) | Bahasa Indonesia konsisten; terminology kanon; table header natural. | Workspace terasa polished & selesai. |

### Files changed (Sprint 12.6 UX Freeze)

| File | Change | Business logic? |
|---|---|---|
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Executive Summary dipadatkan, Exception Box satu kalimat, Decision Card polish, table header rename, table density, legend dipangkas, checklist compact footer, white space rapat, bahasa Indonesia, semantic icons. | No |

### Architecture preservation (Sprint 12.6 UX Freeze)

- `VesselPlanAnalyzer.php` — **NOT modified** (verified: mtime 10:29 from Sprint 12.3).
- Backend outputs (`conflicts`, `violations`, `ok`, `gap_warnings`, `chronology_issues`, `missing_sailing`, `missing_voyage`, `readiness`) — consumed as-is.
- Canon, workflow, behavior, Recognition, behavior judgment — **unchanged**.
- No service / model / accessor / widget / database column / migration touched.
- No new KPI / card / widget / summary added (Freeze Rule enforced).

### Acceptance criteria status

- [x] Planner memahami status plan ≤10 detik (Executive Summary compact + Exception satu kalimat sebelum tabel).
- [x] Tidak ada informasi diulang antar section (Checklist tidak mengulang Executive Summary; legend tidak menjelaskan ulang warna badge).
- [x] Exception selalu muncul sebelum detail tabel (Exception First section).
- [x] Terminology konsisten dengan Tab Jadwal dan Riwayat Jadwal (Voyage format kanon, Bahasa Indonesia, domain terms preserved).
- [x] Typography, spacing, warna, badge, format Voyage konsisten di seluruh Workspace.
- [x] Checklist sebagai gate validasi ringkas (inline footer, no big card).
- [x] Tidak ada perubahan business logic, analyzer, workflow, service, model, migration, struktur data.

### Workspace Vessel Plan — Final Freeze

Sprint 12.6 menandai **Final Freeze** Workspace Vessel Plan. Modul ini dinyatakan:

- ✅ Lengkap dari segi fitur
- ✅ Lengkap dari segi UX
- ✅ Lengkap dari segi Canon compliance
- ✅ Lengkap dari segi Decision Support

Modul tidak akan dimodifikasi lagi kecuali:
1. Bug fix
2. Perubahan requirement bisnis yang nyata
3. Improvement kecil hasil usability test

Workspace Vessel Plan siap untuk commit dan produksi.

---

## Sprint 12.8 — Workspace Cleanup: Remove Legacy Operational Analysis

**Date:** July 2026
**Objective:** Membersihkan Workspace Vessel Plan dari artefak legacy yang tidak lagi termasuk Story Monthly Vessel Plan.
**Scope:** Cleanup registrasi widget saja. **Tidak ada perubahan business logic, analyzer, service, model, migration, Canon implementation, workflow, submission, atau finalization.**
**Verdict dasar (Sprint 12.7 Architecture Review):** `VesselPlanScheduleAnalysis` widget bukan anggota sah Workspace Vessel Plan. Widget menganalisis `Draft → Final → Actual`, Dwelling, Sailing, Dooring — semua adalah data pasca-eksekusi (`voyage.etd`, `voyage.atd_at`) yang bukan domain Planner.

### Background

Widget footer `VesselPlanScheduleAnalysis` dulunya terdaftar di `EditVesselPlan::getFooterWidgets()` sebelum refactor 3-tab. Sejak refactor 3-tab (Sprint 12.2), registrasi widget sudah dikomentari (footer widgets tidak terender). Sprint 12.8 memformalkan penghapusan tersebut.

Sprint 12.7 (Architecture Review) mengonfirmasi widget melanggar:
- Axiom 1 (Story) — widget mensupport Story "menilai pelaksanaan", bukan "menyusun rencana"
- Axiom 2 (Workspace) — widget membaca `voyage.etd`/`voyage.atd_at` (operational entity), leak boundary
- Axiom 3 (Information) — 10 dari 11 data widget adalah Voyage/Operational domain
- Axiom 5 (Recognition) — dua badge "Status" bersaing dengan Review Jadwal
- Axiom 12 (Directedness) — footer always-on menyaingi Review Jadwal
- Axiom 13 (Workspace Experience) — glossary dwelling/sailing/dooring duplikat Glossary kanon

### Changes (Sprint 12.8)

| File | Change | Business logic? |
|---|---|---|
| `app/Filament/Resources/VesselPlanResource/Pages/EditVesselPlan.php` | Hapus `use ...VesselPlanScheduleAnalysis` import; hapus referensi `VesselPlanScheduleAnalysis::class` dari blok komentar `getFooterWidgets()`. | No — menghapus registrasi dead code |
| `app/Filament/Resources/VesselPlanResource/Widgets/VesselPlanScheduleAnalysis.php` | Tambah docblock `@deprecated` yang menjelaskan: widget dihapus dari Vessel Plan Workspace di Sprint 12.8, milik domain Operational/Voyage Evaluation, kandidat reuse di Voyage Evaluation Workspace. | No — docblock only, class tetap utuh |

### Files NOT modified (Sprint 12.8)

| File | Status |
|---|---|
| `app/Services/VesselPlanAnalyzer.php` | FROZEN (mtime 10:29 dari Sprint 12.3) |
| `app/Models/VesselPlan.php`, `VesselPlanItem.php`, `Voyage.php` | Tidak disentuh |
| Migration, workflow, submission, finalization | Tidak disentuh |
| `VesselPlanScheduleAnalysis.php` (logic/blade/template) | Tidak dihapus, tidak di-refactor. Hanya docblock `@deprecated` ditambahkan |
| `vessel-plan-schedule-analysis.blade.php` | Tidak dihapus |

### Files NOT deleted (sesuai scope)

- ✅ `VesselPlanScheduleAnalysis.php` — dipertahankan, ditandai `@deprecated`
- ✅ `vessel-plan-schedule-analysis.blade.php` — dipertahankan
- ✅ Narrative builder / impact calculator inside widget — dipertahankan apa adanya

### Workspace Vessel Plan setelah Sprint 12.8

Workspace hanya memiliki **tiga Decision Context**, tidak ada section tambahan:

```
Edit Vessel Plan
├── Header widgets: VesselPlanAnalysis (kanban header)
├── Tab 1: Jadwal              (DC: edit items, Filament relation manager)
├── Tab 2: Review Jadwal       (DC: Decision Support Workspace untuk Planner)
└── Tab 3: Riwayat Jadwal      (DC: review requests history)
```

Tidak ada lagi:
- ❌ Operational Analysis
- ❌ Draft → Final → Actual analysis
- ❌ Dwelling analysis
- ❌ Dooring analysis
- ❌ Forecast Dooring
- ❌ Operational KPI

### Canon v1.1 mapping of Sprint 12.8

| Canon Axiom | Sprint 12.8 change | Effect |
|---|---|---|
| Axiom 1 (Story) | Story Workspace kembali ke "menyusun rencana" tanpa kontaminasi Story pasca-eksekusi. | Workspace menjalankan satu Story tunggal. |
| Axiom 2 (Workspace) | Boundary domain: tidak ada lagi entity Voyage (`voyage.etd`/`voyage.atd_at`) ditampilkan di Workspace Planning. | Domain leak ditutup di level Workspace. |
| Axiom 3 (Information) | Workspace tidak lagi menampilkan data operasional/Voyage. | Information Workspace sesuai DC. |
| Axiom 5 (Recognition) | Dua badge "Status" paralel dihilangkan. Review Jadwal jadi satu-satunya authority untuk Status Plan. | Recognition tunggal. |
| Axiom 12 (Directedness) | Tidak ada footer widget always-on yang menyaingi Review Jadwal. | Attention Planner terarah ke satu Workspace analisis. |
| Axiom 13 (Workspace Experience) | Glossary tunggal (definitions lengkap di Review Jadwal). Tidak ada lagi definisi duplikat dwelling/sailing/dooring. | Konsistensi glossary. |

### Acceptance criteria status

- [x] VesselPlanScheduleAnalysis tidak lagi tampil pada halaman Edit Vessel Plan.
- [x] Review Jadwal menjadi satu-satunya Workspace analisis bagi Planner.
- [x] Tidak ada perubahan business logic (analyzer mtime tetap 10:29 dari Sprint 12.3).
- [x] Tidak ada class yang dihapus (file tetap, ditandai `@deprecated`).
- [x] Widget ditandai sebagai deprecated untuk dipindahkan ke modul Voyage Evaluation pada sprint mendatang.
- [x] Workspace Vessel Plan hanya berisi tiga Decision Context: Jadwal → Review Jadwal → Riwayat.

### Verification

- `php -l` EditVesselPlan.php: no syntax errors
- `php -l` VesselPlanScheduleAnalysis.php: no syntax errors
- `php artisan view:clear`: success
- `grep VesselPlanScheduleAnalysis app/`: 2 matches tersisa — class declaration + catatan removal di komentar (tidak ada registrasi aktif)
- `VesselPlanAnalyzer.php` mtime: 07/07/2026 10:29:14 — FROZEN

### Next sprint

Sprint 12.8 adalah cleanup minimum. Pemindahan widget logic ke modul Voyage Evaluation akan dilakukan pada sprint mendatang, setelah modul tersebut siap menerima widget tersebut. Saat ini widget hanya ditandai `@deprecated` dan tidak diregistrasi di mana pun.

Workspace Vessel Plan tetap pada status Final Freeze sejak Sprint 12.6. Sprint 12.8 hanya memformalkan penghapusan artefak legacy.

---

## Sprint 12.9 — Workspace Productivity: Tab Jadwal Final Schedule Sync

**Date:** July 2026
**Objective:** Membuat Tab Jadwal menjadi Workspace sinkronisasi Final Schedule TAM yang produktif untuk Planner fase Terkirim/Revisi.
**Scope:** Workspace productivity polish — header cleanup, context bar, Shipping Line filter, Voyage consistency, Cargo Plan UX, Review Jadwal subtitle, UX consistency antar tab. **Tidak ada perubahan Workflow, Canon, business rule, Service, Analyzer, status Vessel Plan, KPI baru, atau widget baru.**

### Story context (mendasari Sprint 12.9)

Saat status Terkirim, Planner menjalankan flow operasional nyata:

```
Terkirim (menunggu email Final Schedule TAM)
  → Buka email
  → Sesuaikan ETD/ETA per kapal di Tab Jadwal
  → Isi Cargo Plan
  → Finalisasi
  → Review Jadwal (verifikasi final)
  → Riwayat Jadwal (audit Draft → Final)
```

Artinya saat status Terkirim, **Workspace utama Planner adalah Tab Jadwal** — bukan Review Jadwal. Tab Jadwal harus dioptimalkan untuk sinkronisasi per Shipping Line.

### Changes (presentation/filter only)

| # | Scope | Change | Business logic? |
|---|---|---|---|
| 1 | Header Jadwal cleanup | Hapus pill "Status: Terkirim/Draft/Revisi/Final" dari header card Tab Jadwal — status sudah ditampilkan oleh Header Workspace (VesselPlanAnalysis widget di atas). Subtitle dibuat fase-spesifik: sent/revisi "Sinkronkan Final Schedule dari TAM per Shipping Line.", final "Jadwal telah difinalisasi.", draft "Susun jadwal kapal sebelum dikirim ke TAM.". Variabel `$statusLabel`/`$statusStyle` dihapus dari blade (tidak dipakai lagi). | No — wording/visual cleanup |
| 2 | Context filter bar | Tambah context bar inline di header card: pill POL → arrow → pill POD (read-only dari `$record->pol`/`$record->pod`) + daftar Shipping Line yang ada di plan (auto-derived dari items). Bukan filter interaktif — hanya konteks yang membantu Planner men-sync per pelayaran tanpa scroll ke field record. | No — read-only context |
| 3 | Filter Shipping Line | Tambah `SelectFilter::make('shipping_line_id')` di RelationManager table dengan placeholder "Semua", preload, searchable. Layout `FiltersLayout::AboveContent` (single filter, muncul sebagai baris dropdown di atas tabel). Filter ini bekerja via Livewire — tidak ada full page reload, hanya refresh tabel. Memenuhi syarat "Livewire/client-side sudah cukup". | No — Filament table filter |
| 4 | Filter-aware empty state | `emptyStateDescription` sekarang deteksi `getTableFilterState('shipping_line_id')`: bila filter aktif → "Belum ada jadwal untuk Shipping Line ini.", bila tidak → "Tambah jadwal pertama untuk memulai penyusunan plan." | No — empty state wording |
| 5 | Konsistensi Voyage | Format kanon `V.NNN · Shipping Line` diterapkan konsisten: (a) Tab Jadwal RelationManager sudah pakai sejak Sprint 12.5 di description kolom Kapal; (b) Tab Review `V.{voyage_no} · {shippingLine}` sudah ada; (c) Tab Riwayat sebelumnya hanya menampilkan `V.{voyage_no}` tanpa Shipping Line — Sprint 12.9 menambahkan Shipping Line: row key `voyage_no` diubah ke `voyage_label` (`V.NNN · Shipping Line`), drawer header pakai `x-text="selected.voyage_label"`. | No — formatting only |
| 6 | Cargo Plan UX | Tab Jadwal `cargo_plan` column: placeholder "Belum diisi" + `color: gray` saat kosong, color = null (default) saat sudah terisi. **Tidak ada badge merah**. Sudah diimplementasi Sprint 12.5 — dipertahankan di Sprint 12.9 (tidak diubah). | No — UX compliance |
| 7 | Review Jadwal subtitle context-aware | Subtitle Tab Review diubah dari satu kalimat ("Ringkasan kesiapan jadwal sebelum dikirim ke TAM.") menjadi phase-aware: Final → "Hasil akhir jadwal yang telah disetujui.", Sent → "Verifikasi jadwal final sebelum difinalisasi.", Draft/Revisi → "Ringkasan kesiapan jadwal sebelum dikirim ke TAM.". Review Jadwal tetap membaca `planned_etd` (current state, post-TAM-sync untuk Sent) — bukan Draft snapshot — sudah benar sejak awal. | No — wording only |
| 8 | Riwayat Draft → Final tetap | Tab Riwayat tetap membaca `draftSnapshot()` (snapshot pada Submit) vs current planned_etd/eta (Final) untuk audit. Tidak ada perubahan behavior — hanya penyesuaian Voyage format + spacing. | No — unchanged behavior |
| 9 | UX consistency antar tab | (a) Header eyebrow style disatukan ke `text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1` di semua 3 tab (sebelumnya Tab Review pakai `text-lg font-bold text-gray-800`). (b) Root container spacing: Tab Review `space-y-2.5` (Sprint 12.6), Tab Riwayat `space-y-3` → `space-y-2.5` (Sprint 12.9). (c) Padding tab kontainer: rounded-2xl shadow-sm p-5 (sudah konsisten). (d) Badge color palette semantic (emerald=success, amber=warning, red=critical, gray=neutral) — konsisten di semua tab. (e) Voyage typography kanon (`Vessel` semibold gray-800 > `V.{voyage_no}` gray-500 font-mono xs > `·` gray-300 > `{shippingLine}` gray-400 xs) konsisten di semua tab. | No — visual unification |

### Files changed (Sprint 12.9)

| File | Change | Business logic? |
|---|---|---|
| `app/Filament/Resources/VesselPlanResource/RelationManagers/VesselPlanItemRelationManager.php` | Import `SelectFilter`, `FiltersLayout`. Tambah `->filters([SelectFilter::make('shipping_line_id')->...], layout: FiltersLayout::AboveContent)`. `emptyStateDescription` jadi closure filter-aware. | No — filter + empty state |
| `resources/views/filament/resources/vessel-plan-resource/pages/edit-vessel-plan.blade.php` | Header card Tab Jadwal: hapus status pill, hapus variabel statusLabel/statusStyle, subtitle fase-spesifik, context bar POL/POD + Shipping Line roster. | No — presentation only |
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Subtitle Tab Review fase-aware, header eyebrow unified (`text-lg` → `text-[11px] uppercase`). | No — wording/header only |
| `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-history.blade.php` | `voyage_no` → `voyage_label` (`V.NNN · Shipping Line`); drawer header pakai `selected.voyage_label`. Root container `space-y-3` → `space-y-2.5`. | No — formatting + spacing |

### Files NOT modified (Sprint 12.9)

| File | Status |
|---|---|
| `app/Services/VesselPlanAnalyzer.php` | FROZEN (mtime 07/07/2026 10:29:14 = Sprint 12.3) |
| `app/Models/VesselPlan.php`, `VesselPlanItem.php`, `Voyage.php`, `Port.php`, `ShippingLine.php` | Tidak disentuh |
| Migration, workflow, submission, finalization | Tidak disentuh |
| Business rule, Canon, status Vessel Plan | Tidak disentuh |
| KPI / widget / summary baru | **Tidak ada** (Final Freeze dari Sprint 12.6 tetap) |

### Tab voyage format audit (kanon V.NNN · Shipping Line)

| Lokasi | Sebelum Sprint 12.9 | Setelah Sprint 12.9 |
|---|---|---|
| Tab Jadwal — RM description | `V.273 · Meratus Line` ✓ | Tidak berubah ✓ |
| Tab Review — kolom Kapal | `V.273` + separator + `Meratus Line` ✓ | Tidak berubah ✓ |
| Tab Riwayat — row tabel | `V.273` saja ✗ | `V.273 · Meratus Line` ✓ |
| Tab Riwayat — drawer header | `selected.voyage_no` (`V.273`) ✗ | `selected.voyage_label` (`V.273 · Meratus Line`) ✓ |

### Tab header eyebrow audit (Sprint 12.9 unification)

| Tab | Sebelum | Setelah |
|---|---|---|
| Tab Jadwal | `text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1` "JADWAL KAPAL" | Tidak berubah ✓ |
| Tab Review | `text-lg font-bold text-gray-800` "Review Jadwal" | `text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1` "REVIEW JADWAL" ✓ |
| Tab Riwayat | `text-[11px] uppercase tracking-wider font-bold text-gray-500 mb-1` "RIWAYAT JADWAL" | Tidak berubah ✓ |

### Container spacing audit

| Tab | Sebelum | Setelah |
|---|---|---|
| Tab Review root | `space-y-2.5` ✓ | Tidak berubah ✓ |
| Tab Riwayat root | `space-y-3` | `space-y-2.5` ✓ |

### Canon v1.1 mapping of Sprint 12.9

| Canon Axiom | Sprint 12.9 change | Effect |
|---|---|---|
| Axiom 1 (Story) | Tab Jadwal dioptimalkan untuk Story "sinkronisasi Final Schedule TAM" — context Shipping Line, filter per pelayaran. Subtitle Tab Review fase-aware (post-TAM-sync vs pre-submit). | Workspace menjalankan Story sebenarnya yang dijalankan Planner. |
| Axiom 2 (Workspace) | Filter Shipping Line + context bar tidak membuka boundary domain — tetap di Planning Workspace (entities VesselPlan + VesselPlanItem + relasi shippingLine). | Boundary domain dijaga. |
| Axiom 3 (Information) | POL/POD + roster Shipping Line adalah Info milik Planning Workspace (lihat $record->pol/pod & $item->shippingLine) — sah ditampilkan. Pill status dihapus (Info sudah ada di Header Workspace — tidak duplikat). | Info tunggal per konsep, tidak redundan. |
| Axiom 5 (Recognition) | Voyage format disatukan ke `V.NNN · Shipping Line` di seluruh Workspace — satu Recognition authority untuk identifier Voyage. Header eyebrow unified — satu pola visual untuk "kita ada di tab X". | Recognition konsisten antar tab. |
| Axiom 6 (Behavior Judgment) | Filter Shipping Line memungkinkan Planner menyelesaikan sinkronisasi per pelayaran secara berurutan — behavioral chunking mempercepat judgment "semua kapal Meratus sudah selesai, lanjut Tanto". | Keputusan per-grup bukan per-item. |
| Axiom 12 (Directedness) | Status pill dihapus dari header — attention tidak dibagi dua antara Tab Jadwal pill dan Header Workspace status. Filter AboveContent langsung terlihat, tidak di-balik "Filters" toggle. | Attention Planner terarah ke satu status source + satu filter source. |
| Axiom 13 (Workspace Experience) | Padding/typography/badge/spacing konsisten antar tab — Workspace terasa polished & selesai. Voyage format konsisten = Glossary tunggal. | UX polish konsisten lintas tab. |

### Acceptance criteria status

- [x] Tab Jadwal benar-benar menjadi Workspace sinkronisasi Final Schedule TAM (header subtitle fase-spesifik + context bar POL/POD + filter Shipping Line + roster Shipping Line aktif).
- [x] Shipping Line dapat difilter tanpa reload halaman (Livewire SelectFilter — no full page reload, hanya tabel refresh).
- [x] Planner dapat menyelesaikan sinkronisasi per Shipping Line dengan lebih cepat (filter + roster membuat per-pelayaran chunking natural).
- [x] Header tidak lagi menduplikasi informasi status (pill "Status: X" dihapus, variabel statusLabel/statusStyle dibuang).
- [x] Penulisan Voyage konsisten di seluruh Workspace (`V.NNN · Shipping Line` di Tab Jadwal, Tab Review, Tab Riwayat row, Tab Riwayat drawer).
- [x] Cargo Plan lebih mudah dibaca saat proses sinkronisasi (placeholder abu "Belum diisi", tidak ada badge merah — dipertahankan dari Sprint 12.5).
- [x] Review Jadwal hanya digunakan setelah jadwal Final selesai (subtitle fase-aware: "Verifikasi jadwal final sebelum difinalisasi." untuk Sent; "Hasil akhir jadwal yang telah disetujui." untuk Final).
- [x] Riwayat Jadwal tetap menjadi audit Draft → Final (draftSnapshot vs current `planned_etd/eta` — tidak diubah).
- [x] Tidak ada perubahan business logic maupun Product Architecture Canon (analyzer FROZEN, model/migration/workflow/status untouched).

### Verification

- `php -l` semua file PHP yang diubah: no syntax errors.
- `php artisan view:clear`: success.
- `php artisan view:cache`: success (semua blade kompilasi tanpa error).
- Script verify_sprint_12_9: 14 PASS / 0 FAIL untuk semua plan (Draft/Sent/Final/Revisi).
  - Subtitle Tab Review fase-aware untuk semua plan ✓
  - Header eyebrow Tab Review ter-unifikasi ✓
  - Voyage label (`V.NNN · Shipping Line`) muncul di Tab Riwayat row & drawer ✓
  - Draft vs Final context di Tab Riwayat tetap ada ✓
  - Spacing `space-y-2.5` di Tab Riwayat ✓
- `VesselPlanAnalyzer.php` mtime: 07/07/2026 10:29:14 — FROZEN (Sprint 12.3).

### Workspace Vessel Plan pasca Sprint 12.9

```
Edit Vessel Plan
├── Header Workspace: VesselPlanAnalysis (status kanban, summary)
├── Tab 1: Jadwal
│   ├── Header card (eyebrow "JADWAL KAPAL" + subtitle fase-aware + POL/POD + roster Shipping Line)
│   └── Filter Shipping Line (AboveContent) + Tabel (dengan kolom Voyage = V.NNN · Shipping Line)
├── Tab 2: Review Jadwal (eyebrow "REVIEW JADWAL" + subtitle fase-aware + Executive Summary + Decision Cards + Exception + Daftar Jadwal + Checklist)
└── Tab 3: Riwayat Jadwal (eyebrow "RIWAYAT JADWAL" + subtitle + tabel Draft vs Final + drawer)
```

Tiga Decision Context tetap utuh, tidak ada KPI/card/widget/summary baru. Workspace Vessel Plan tetap pada status Final Freeze — Sprint 12.9 hanya menambah productifity surface di dalam Workspace yang sudah ada.

---

## Sprint 13.1 — Jadwal Workspace Header Polish

**Date:** July 2026
**Objective:** Rapikan Header Tab Jadwal + ganti Filter Card Filament dengan slim inline toolbar Shipping Line.
**Scope:** Presentation + UX polish di Tab Jadwal header. **Tidak ada perubahan Workflow, Canon, business rule, Service, Analyzer, status Vessel Plan, KPI, widget, summary.**

### Motivation

Sprint 12.9 menambahkan context bar POL/POD + roster Shipping Line serta Filament `SelectFilter` dengan `FiltersLayout::AboveContent`. Setelah dijalankan, ditemukan:

1. **Duplikat context** — Workspace main subheading (`EditVesselPlan::getSubheading()`) sudah menampilkan badge status + rute (Jakarta → Bitung) + customer + jumlah jadwal. Tab Jadwal header card menampilkan POL/POD lagi — duplikasi Axiom 3 (Information).
2. **Filter card form terlalu berat** — Filament `AboveContent` memberi kesan "form baru" dengan label "Shipping Line" + section padding. Padahal hanya satu pilihan dropdown + reset.
3. **Tinggi vertikal berlebih** — Filter card mengambil ~3 baris layar (~150px), padahal workspace utama Planner adalah tabel jadwal.

Sprint 13.1 memangkas ulang header sesuai cerita operasional nyata: Workspace utama saat status Terkirim adalah Tab Jadwal — harus ringkas, langsung kurasi per Shipping Line.

### Changes (presentation/filter only)

| # | Scope | Change | Business logic? |
|---|---|---|---|
| 1 | Header Workspace tetap | `EditVesselPlan::getHeading()` "Vessel Plan — F Y" + `getSubheading()` "[Status badge] Jakarta → Bitung · Toyota Astra Motor · 9 jadwal" — tidak diubah (sudah benar). | No — preserved |
| 2 | Header Section Jadwal dipangkas | Hapus POL/POD badges + roster Shipping Line + arrow svg dari Tab Jadwal header card. Subtitle disederhanakan: sent/revisi "Sinkronkan Final Schedule dari TAM." (hilangkan "per Shipping Line" — sudah tersedia toolbar), final "Jadwal telah difinalisasi.", draft "Susun jadwal kapal sebelum dikirim ke TAM.". Padding card `px-4 py-3 mb-3` → `px-4 py-2.5 mb-2`. Subtitle dari `text-sm` → `text-xs`. | No — wording/visual cleanup |
| 3 | Toolbar Shipping Line | Filter Card Filament `SelectFilter + FiltersLayout::AboveContent` dihapus dari RelationManager. Diganti dengan toolbar inline pada header card: native `<select>` ("Semua" + opsi auto-derived dari `$items->pluck('shippingLine')->unique()->sortBy('name')`) + tombol "Reset" text link kecil. Toolbar muncul hanya bila >1 Shipping Line di plan. | No — UX control |
| 4 | Dropdown Shipping Line | Opsi diisi otomatis dari `$items` (no hardcode Meratus/Tanto/SPIL/Temas) — natural dari data plan aktual. Tidak ada Preload query `shippingLine` — langsung ambil dari eager-loaded items. Pilihan opsional "Semua" sebagai default. Live update via Livewire `wire:change="$dispatch('vpFilterShippingLine', ...)"`. | No — auto-derived options |
| 5 | Konsistensi visual | Select pakai `text-sm py-1.5 pl-2.5 pr-8 border-gray-300 shadow-sm leading-none` — match tinggi Filament button default. Reset text-xs abu, underline on hover, `x-show` muncul hanya jika `shippingLine !== ''`. Hilangkan label "Filter" (placeholder dropdown sudah cukup). Toolbar terasa bagian dari card, bukan form baru. Reduced vertical whitespace ~50% vs filter card sebelumnya. | No — styling |

### Architecture: Livewire event-based filtering

Sebelum Sprint 13.1 — Filament native filter:
```
Parent blade (no control) → [Filament SelectFilter + AboveContent layout] → Table query
```

Sesudah Sprint 13.1 — Custom slim toolbar + Livewire event:
```
Parent blade dropdown (select + Reset)
  → wire:change/$dispatch('vpFilterShippingLine', { value })  ← Livewire v3 global event
  → RelationManager::applyVpShippingLineFilter(?string $value) ← #[On('vpFilterShippingLine')]
  → set $vpShippingLineFilter + $this->resetPage()
  → Table->modifyQueryUsing(...) clause: `->where('shipping_line_id', $value)` bila filled
```

Keuntungan:
- Tidak ada native Filter Card Filament (vertical space berlebih, label "Shipping Line", "Filter" button).
- Filter state tersimpan di public property Livewire RelationManager (auto-sync dengan rerender).
- Dropdown lebar penuh → tidak ada card wrapper → toolbar terlihat sebagai bagian header.
- Reset link tampil kondisional lewat Alpine `x-show` (hanya muncul jika filter aktif).

### Files changed (Sprint 13.1)

| File | Change | Business logic? |
|---|---|---|
| `app/Filament/Resources/VesselPlanResource/RelationManagers/VesselPlanItemRelationManager.php` | Hapus import `FiltersLayout` & `SelectFilter`. Tambah `use Illuminate\Database\Eloquent\Builder`, `use Livewire\Attributes\On`. Tambah public property `$vpShippingLineFilter`. Tambah `#[On('vpFilterShippingLine')] applyVpShippingLineFilter()` listener (set value + resetPage). Tambah `->modifyQueryUsing(...)` pada `table()` untuk apply where clause. Hapus `->filters([SelectFilter::make('shipping_line_id')...], layout: FiltersLayout::AboveContent)`. Update `emptyStateDescription` baca `$this->vpShippingLineFilter` (bukan `getTableFilterState`). | No — filter plumbing via Livewire property |
| `resources/views/filament/resources/vessel-plan-resource/pages/edit-vessel-plan.blade.php` | Header card Tab Jadwal dipangkas: POL/POD badges, arrow, roster Shipping Line dihapus. Padding card `py-3 mb-3` → `py-2.5 mb-2`. Tambah Alpine-powered `<select>` + Reset text link inline kanan-atas card. Dispatch Livewire event `vpFilterShippingLine` ke RelationManager child via `wire:change` / `x-on:click`. Toolbar muncul hanya bila `$shippingLines->count() > 1`. | No — presentation + slim dropdown |

### Files NOT modified (Sprint 13.1)

| File | Status |
|---|---|
| `app/Filament/Resources/VesselPlanResource/Pages/EditVesselPlan.php` | Tidak disentuh (Workspace header subheading tetap) |
| `app/Filament/Resources/VesselPlanResource/Widgets/VesselPlanAnalysis.php` + `.blade.php` | Tidak disentuh (KPI strip Workspace header tetap) |
| `app/Services/VesselPlanAnalyzer.php` | FROZEN (mtime 07/07/2026 10:29:14 = Sprint 12.3) |
| `app/Models/VesselPlan.php`, `VesselPlanItem.php`, `Voyage.php`, `Port.php`, `ShippingLine.php` | Tidak disentuh |
| Migration, workflow, submission, finalization | Tidak disentuh |
| Business rule, Canon, status Vessel Plan, KPI / widget / summary baru | Tidak ada (Final Freeze Sprint 12.6 tetap) |

### Workspace Vessel Plan pasca Sprint 13.1

```
Edit Vessel Plan
├── Workspace heading  : "Vessel Plan — F Y"
├── Workspace subheading: [Status badge] Rute · Customer · N jadwal  (preserved)
├── Header widget       : VesselPlanAnalysis (KPI strip: jadwal / sailing / max ETD gap / risk)
└── Tab 1: Jadwal
    ├── Slim header card (eyebrow "JADWAL KAPAL" + subtitle fase-aware)
    │   └── Inline toolbar kanan-atas: [Semua ▾] Reset
    └── Tabel jadwal (RelationManager) ← listens 'vpFilterShippingLine'
├── Tab 2: Review Jadwal (tidak diubah sejak Sprint 12.9)
└── Tab 3: Riwayat Jadwal (tidak diubah sejak Sprint 12.9)
```

Tiga Decision Context tetap utuh. Tab Jadwal sekarang benar-benar menjadi Workspace sinkronisasi Final Schedule TAM yang ringkas — tanpa duplikasi context header, dengan filter Shipping Line live-update yang terasa bagian dari tabel.

### Canon v1.1 mapping of Sprint 13.1

| Canon Axiom | Sprint 13.1 change | Effect |
|---|---|---|
| Axiom 1 (Story) | Header Tab Jadwal fokus ke Story "sinkronisasi Final Schedule per Shipping Line" tanpa distraksi context bar yang redundant dengan Workspace header. | Story Workspace jelas: tabel + filter Shipping Line. |
| Axiom 3 (Information) | Hapus duplikasi POL/POD + Shipping Line roster — Info tunggal per konsep (rute hanya di Workspace heading; Shipping Line list hanya di tabel + filter). | Information authority satu per item. |
| Axiom 5 (Recognition) | Dropdown + Reset text link = satu sinyal filter tunggal. Hilangkan form card "Filter" (=sinyal ganda). | Recognition filter jelas: lihat dropdown = lihat saringan aktif. |
| Axiom 6 (Behavior Judgment) | Toolbar live-update mempercepat chunking per-pelayaran tanpa klik "Apply" (sebagaimana native Filament Button). | Keputusan per-grup cepat. |
| Axiom 12 (Directedness) | Vertical whitespace berkurang ~50% vs filter card lama. Tinggi header Workspace + Tab Jadwal header lebih hemat → perhatian langsung ke tabel. | Attention Planner ke tabel (bukan ke chrome filter). |
| Axiom 13 (Workspace Experience) | Tinggi dropdown = tinggi tombol Filament. Reset text-xs underline. Konsistensi visual antar kontrol. | Polish konsisten. |

### Acceptance criteria status

- [x] Header Workspace tetap (subheading: status + rute + customer + jumlah jadwal).
- [x] Section Jadwal header hanya berisi eyebrow "JADWAL KAPAL" + subtitle fase-aware (POL/POD & roster dihapus).
- [x] Toolbar Shipping Line ringan: dropdown native + Reset text link kecil.
- [x] Tidak ada card form "Filter" Filament (FiltersLayout::AboveContent dihapus).
- [x] Dropdown opsi auto-derived dari Shipping Line di plan (Semua + opsinya).
- [x] Live update tabel tanpa reload halaman (Livewire event dispatch).
- [x] Consistency visual: tinggi dropdown = py-1.5 (match Filament button); Reset = text-xs underline; no "Filter" label.
- [x] Whitespace vertikal berkurang ~50% (card py-3 mb-3 → py-2.5 mb-2; subtitle text-sm → text-xs; toolbar flex item-center single-line).
- [x] Toolbar terlihat sebagai bagian dari card header, bukan form baru.
- [x] Tidak ada perubahan business logic / Canon / analyzer / workflow / KPI.

### Verification

- `php -l VesselPlanItemRelationManager.php`: no syntax errors.
- `php artisan view:clear`: success.
- `php artisan view:cache`: success (semua blade kompilasi tanpa error).
- `VesselPlanAnalyzer.php` mtime: 07/07/2026 10:29:14 — FROZEN (Sprint 12.3).
- `getHeading()`/`getSubheading()` di `EditVesselPlan.php`: tidak diubah (Sprint 4.x preserved).

### Catatan teknis

- `wire:change` dipakai (bukan `wire:model`) karena perubahan select langsung dispatch — tidak perlu stage state di parent (parent `EditVesselPlan` tidak perlu tahu state filter; hanya RelationManager child).
- Alpine `x-model` dipakai supaya select bisa diperbarui saat Reset diklik (UI sync).
- Toolbar invisible bila `$shippingLines->count() <= 1` (single-line plan tidak butuh filter).
- `resetPage()` dipanggil dalam listener supaya pagination tidak stuck di halaman 2 saat filter mengunci result set.

