# Briefing + MP Check Module — Final Audit Report
_Completed: 2026-06-09_

---

## Root Causes Found

### 1. Production FK Violation (manpower_id = 1)
- **Root cause**: `manpowers` table was empty (0 rows). Webhook used raw integer "1" from AppSheet as `manpower_id` directly without bridging through `appsheet_id`. This was a missing bridge column.
- **Status**: FIXED in previous sprint. `appsheet_id` column added to `manpowers`. `syncBriefingAttendance()` now resolves via `Manpower::where('appsheet_id', ...)`. If no match, attendance is gracefully skipped with a warning log (no FK violation).

### 2. Duplicate Unique Index Blocking Backup MP
- **Root cause**: Two conflicting unique indexes existed simultaneously:
  - `briefing_attendance_unique (session_id, manpower_id, mp_type, backup_name)` — correct, supports NULLs
  - `uniq_session_mp (session_id, manpower_id)` — legacy, blocks inserting two backup MPs (both with `manpower_id = NULL`) in the same session
- **Status**: FIXED. Migration `2026_06_09_100001_drop_legacy_uniq_session_mp_index` drops `uniq_session_mp` via `ALTER TABLE ... DROP CONSTRAINT`.

### 3. mp_type Normalization Gap
- **Root cause**: AppSheet sends "Tipe MP" as "Reguler" (Indonesian). `normalizeValue()` had no mapping for this field. `strtolower("Reguler")` = "reguler" ≠ "regular" → the backup-vs-regular branching in `syncBriefingAttendance()` would misfire: every regular MP was treated as backup.
- **Status**: FIXED. Added `mp_type` normalization to `AppSheetService::normalizeValue()`:
  - "Reguler" / "regular" → `"regular"`
  - "Backup" / "backup" → `"backup"`
  - Removed the redundant `strtolower()` calls inside `syncBriefingAttendance()` and `deleteBriefingAttendance()`.

### 4. Enum Safety in Filament (TypeError Risk)
- **Root cause**: `AttendancesRelationManager` called `AttendanceStatus::tryFrom($state)` where `$state` is already an `AttendanceStatus` enum object (because the model casts `attendance_status`). PHP 8.1+ `tryFrom()` does not accept an enum — it throws a `TypeError` at runtime.
- **Status**: FIXED. All `formatStateUsing` and `color` callbacks in `AttendancesRelationManager` now use the safe pattern:
  ```php
  $enum = $state instanceof AttendanceStatus
      ? $state
      : AttendanceStatus::tryFrom((string) $state);
  ```
  The `syncPpeItems` comparison was also fixed to compare against `AttendanceStatus::Present` (enum) rather than `->value` (string).

### 5. Dead Handler Architecture Not Labelled
- **Root cause**: 10 handler classes + `SyncHandlerRegistry` + `PayloadNormalizer` were silently dead, with no indication they were inactive.
- **Status**: FIXED. All files have `@deprecated` docblocks with explanation and pointer to the active code path. No handler was activated (constraint respected).

---

## Files Changed

| File | What Changed |
|---|---|
| `app/Services/AppSheetService.php` | Added `mp_type` normalization to `normalizeValue()`. Removed duplicate `strtolower()` calls in `syncBriefingAttendance()` and `deleteBriefingAttendance()`. |
| `app/Filament/FC/Resources/BriefingSessionResource/RelationManagers/AttendancesRelationManager.php` | Fixed `formatStateUsing` / `color` enum safety. Fixed `syncPpeItems` attendance_status comparison. |
| `app/Services/AppSheet/Handlers/BaseSyncHandler.php` | Added `@deprecated` header docblock. |
| `app/Services/AppSheet/Handlers/BriefingAttendanceHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/Handlers/BriefingSessionHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/Handlers/BriefingChecklistHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/Handlers/BriefingPpeItemHandler.php` | Added `@deprecated` with extra warning about "status" field bug. |
| `app/Services/AppSheet/Handlers/LoadingSessionHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/Handlers/EquipmentCheckHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/Handlers/RackContainerCheckHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/Handlers/UnitCheckHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/Handlers/LoadingFindingHandler.php` | Added `@deprecated`. |
| `app/Services/AppSheet/SyncHandlerRegistry.php` | Added `@deprecated` header docblock. |
| `app/Services/AppSheet/PayloadNormalizer.php` | Added `@deprecated` with note about missing normalization. |

---

## Migrations Added

| Migration File | Purpose |
|---|---|
| `2026_06_09_100001_drop_legacy_uniq_session_mp_index.php` | Drops `uniq_session_mp (session_id, manpower_id)` — legacy constraint that blocked inserting two backup MPs in the same session. |

---

## Final AppSheet Contract Table

### `mp_check` → `briefing_sessions`

| AppSheet Field | Laravel Column | Type | Normalization |
|---|---|---|---|
| `ID` | `appsheet_id` | varchar(nullable) | None |
| `Tanggal` | `date` | date | `Carbon::parse()` |
| `Depot ID` | `depot_id` | FK int | None |
| `Koordinator ID` | `coordinator_user_id` | FK int(nullable) | None |
| `Catatan Operasional` | `notes` | text(nullable) | None |
| `Foto Briefing` | `briefing_evidence_path` | string | URL → local download |
| `Kebutuhan MP` | `summary_headcount` | int | None |
| `Solusi Kekurangan` | `summary_solution` | text(nullable) | None |
| `Backup MP` | `backup_required` | boolean | "true"/"false" → bool |
| `Sumber Backup` | `backup_type` | enum MPBackupType | None |
| `Catatan Backup` | `backup_notes` | text(nullable) | None |
| `Aktivitas Pending` | `pending_activity` | boolean | "true"/"false" → bool |
| `Alasan Pending` | `pending_reason` | text(nullable) | None |
| `Status Permintaan APD` | `apd_request_status` | string(nullable) | None |
| `Catatan Permintaan APD` | `apd_request_note` | text(nullable) | None |

### `detail_mp_check` → `briefing_attendances`

| AppSheet Field | Laravel Column | Type | Normalization |
|---|---|---|---|
| `ID` | `appsheet_id` | varchar(nullable) | None |
| `Sesi ID` | `session_id` | FK (appsheet_id bridge) | `BriefingSession::where('appsheet_id', ...)` |
| `MP ID` | `manpower_id` | FK (appsheet_id bridge, nullable) | `Manpower::where('appsheet_id', ...)` |
| `Status Kehadiran` | `attendance_status` | enum AttendanceStatus | "Hadir"→"present" / "Tidak Hadir"→"absent" / "Sakit"→"sick" / "Izin"→"leave" |
| `Tipe MP` | `mp_type` | string | "Reguler"→"regular" / "Backup"→"backup" (FIXED) |
| `Nama Backup MP` | `backup_name` | string(nullable) | None |
| `Suhu` | `temperature` | decimal(4,1) | None |
| `TD Sistolik` | `bp_systolic` | int(nullable) | None |
| `TD Diastolik` | `bp_diastolic` | int(nullable) | None |
| `Keluhan` | `health_complaint` | text(nullable) | None |
| `Status Fit` | `fit_status` | string (raw) | `strtoupper(trim(...))` |
| `Pemeriksaan Ulang` | `recheck_required` | boolean | "true"/"false" → bool |
| `Waktu Istirahat` | `rest_started_at` | datetime(nullable) | None |
| `Hasil Pemeriksaan Ulang` | `recheck_result` | string(nullable) | None |
| `Tindakan Medis` | `medical_action` | string(nullable) | None |
| `APD Lengkap` | `has_ppe` | boolean | "true"/"false" → bool |
| `Status APD Personal` | `personal_ppe_status` | string(nullable) | None |
| `Catatan` | `remark` | text(nullable) | None |
| `Tanda Tangan MP` | `signature_path` | string(nullable) | None |

---

## Final Enum Mapping Table

| Enum | Model | DB Column | Cast? | Notes |
|---|---|---|---|---|
| `AttendanceStatus` | `BriefingAttendance` | `attendance_status` | YES | AppSheet → Indonesian → normalized via `normalizeValue()` |
| `MPCheckStatus` | `BriefingSession` | `mp_check_status` | YES | Set by `evaluateBriefingSession()`, never sent by AppSheet |
| `MPBackupType` | `BriefingSession` | `backup_type` | YES | AppSheet sends English/raw value |
| `PpeCondition` | `BriefingAttendancePpeItem` | `condition` | NO | Raw string; Filament uses string comparison |
| `fit_status` (no enum) | `BriefingAttendance` | `fit_status` | NO | Raw uppercase string: "FIT" / "TIDAK FIT" — intentional design |
| `mp_type` (no enum) | `BriefingAttendance` | `mp_type` | NO | Normalized to "regular"/"backup" — no enum needed given only 2 values |

---

## Topik Briefing Decision

**No "topik_briefing" field exists or is needed.**

- The `briefing_sessions.notes` column is a single freetext field mapped from AppSheet "Catatan Operasional".
- Structured checklist items live in the `briefing_checklists` table (item + type + status).
- No migration was created. No array cast was added. Decision: the current design is correct.

---

## Technical Debt Remaining (Prioritised)

### P0 — Blocker (must fix before backup MPs can work end-to-end)

None remaining after this sprint.

### P1 — High (data loss risk)

1. **Manpowers table is empty** — All `mp_type = regular` attendance webhooks will be gracefully skipped until `manpowers` is seeded with `appsheet_id` values. Either run `php artisan manpower:import-appsheet` or add a webhook handler for the AppSheet `daftar_mp` table. Until then, NO regular MP attendance is stored.

2. **`briefing_sessions.appsheet_id` not always set** — The existing session (id=1) has `appsheet_id = NULL`. If a `detail_mp_check` webhook arrives referencing a session by AppSheet ID, it will fail with "Session AppSheet NULL tidak ditemukan". Root fix: ensure every `mp_check` webhook sets `ID` in the payload.

### P2 — Medium (correctness / maintainability)

3. **`fit_status` has no enum / no validation** — Any value AppSheet sends is stored raw. A mis-spelled "Tidak Fit" (lowercase) vs "TIDAK FIT" would break `evaluateBriefingSession()` which counts `WHERE fit_status = 'FIT'`. Consider adding an enum `FitStatus` with cases `Fit` and `TidakFit`, and a `normalizeValue()` mapping. Requires AppSheet-side audit of actual sent values first.

4. **`personal_ppe_status` and `apd_request_status` have no enum / no validation** — Raw strings, no cast, no normalization. Low urgency since they are display-only fields.

5. **`BriefingAttendanceResource` (global admin panel) lacks `mp_type` / backup MP support** — The Filament admin resource still only shows regular MP columns. Backup MP rows (with `manpower_id = NULL`) will render as blank name. Should add a `getDisplayNameAttribute()` call or show `backup_name` in the table.

### P3 — Low (cleanup)

6. **Dead Handler architecture** — 10 handler files + registry + normalizer are now labelled `@deprecated` but still exist. Can be deleted in a future cleanup sprint once all stakeholders confirm the active architecture is stable.

7. **Two duplicate indexes were present** — Resolved by this sprint. Consider a schema audit for other tables.

8. **`BriefingSession::booted()` saving hook fires on every save** — Recalculates `summary_sufficient` on every `saving` event even when unrelated fields change. Low cost but could be scoped to only fire when `summary_headcount` changes.
