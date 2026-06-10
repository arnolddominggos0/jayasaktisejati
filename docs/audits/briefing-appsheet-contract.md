# Briefing + MP Check — AppSheet Contract Audit
_Generated: 2026-06-09_

---

## 1. AppSheet Field Inventory

### Table: `mp_check` → Laravel `briefing_sessions`

| AppSheet Field | Laravel Column | Type | Notes |
|---|---|---|---|
| `ID` | `appsheet_id` | string(nullable) | AppSheet UNIQUEID; used to resolve FK in attendance webhook |
| `Tanggal` | `date` | date | Upsert key #1 |
| `Depot ID` | `depot_id` | FK → depots | Upsert key #2 |
| `Koordinator ID` | `coordinator_user_id` | FK → users (nullable) | Overwritten by `submittedByUserId` in service |
| `Catatan Operasional` | `notes` | text | Plain text; no multi-value field exists in AppSheet or DB |
| `Foto Briefing` | `briefing_evidence_path` | string | URL downloaded → local path |
| `Kebutuhan MP` | `summary_headcount` | int | |
| `Solusi Kekurangan` | `summary_solution` | text(nullable) | |
| `Backup MP` | `backup_required` | boolean | AppSheet sends "true"/"false" string |
| `Sumber Backup` | `backup_type` | enum MPBackupType | Cast in model |
| `Catatan Backup` | `backup_notes` | text(nullable) | |
| `Aktivitas Pending` | `pending_activity` | boolean | AppSheet sends "true"/"false" string |
| `Alasan Pending` | `pending_reason` | text(nullable) | |
| `Status Permintaan APD` | `apd_request_status` | string(nullable) | No enum cast |
| `Catatan Permintaan APD` | `apd_request_note` | text(nullable) | |

### Table: `detail_mp_check` → Laravel `briefing_attendances`

| AppSheet Field | Laravel Column | Type | Notes |
|---|---|---|---|
| `ID` | `appsheet_id` | string(nullable, unique) | Used for delete-by-appsheet_id path |
| `Sesi ID` | `session_id` (raw → resolved) | FK → briefing_sessions | AppSheet sends its own `appsheet_id`; resolved via `BriefingSession::where('appsheet_id',...)` |
| `MP ID` | `manpower_id` (raw → resolved) | FK → manpowers (nullable) | AppSheet sends its own manpower key; resolved via `Manpower::where('appsheet_id',...)` |
| `Status Kehadiran` | `attendance_status` | enum AttendanceStatus | AppSheet sends Indonesian: "Hadir","Tidak Hadir","Sakit","Izin" — normalized in `normalizeValue()` |
| `Tipe MP` | `mp_type` | string('regular'/'backup') | AppSheet may send "Reguler"/"Backup"; `strtolower()` applied post-mapping but NO mapping of "reguler"→"regular" |
| `Nama Backup MP` | `backup_name` | string(nullable) | |
| `Suhu` | `temperature` | decimal(4,1) | |
| `TD Sistolik` | `bp_systolic` | int(nullable) | |
| `TD Diastolik` | `bp_diastolic` | int(nullable) | |
| `Keluhan` | `health_complaint` | text(nullable) | |
| `Status Fit` | `fit_status` | string (RAW, uppercased) | No enum cast; normalizeValue() applies strtoupper |
| `Pemeriksaan Ulang` | `recheck_required` | boolean | AppSheet sends "true"/"false" |
| `Waktu Istirahat` | `rest_started_at` | datetime(nullable) | |
| `Hasil Pemeriksaan Ulang` | `recheck_result` | string(nullable) | Raw string: "FIT" / "TIDAK FIT" |
| `Tindakan Medis` | `medical_action` | string(nullable) | |
| `APD Lengkap` | `has_ppe` | boolean | AppSheet sends "true"/"false" |
| `Status APD Personal` | `personal_ppe_status` | string(nullable) | No enum cast |
| `Catatan` | `remark` | text(nullable) | |
| `Tanda Tangan MP` | `signature_path` | string(nullable) | |

### Table: `stok_apd_check` → Laravel `stock_apd_checks`

| AppSheet Field | Laravel Column | Notes |
|---|---|---|
| `Sesi ID` | `session_id` | Upsert key #1 |
| `Jenis APD` | `ppe_type` | Upsert key #2 |
| `Stok Tersedia` | `stock_available` | |
| `Kebutuhan` | `required_quantity` | |
| `Status APD` | `status` | |
| `Catatan` | `remark` | |

### Table: `briefing_attendance_ppe_items`

| AppSheet Field | Laravel Column | Notes |
|---|---|---|
| `Attendance ID` | `attendance_id` | Upsert key #1 |
| `Jenis APD` | `ppe_type` | Upsert key #2 |
| `Kondisi APD` | `condition` | |
| `Catatan` | `remark` | |

### Table: `briefing_checklists`

| AppSheet Field | Laravel Column | Notes |
|---|---|---|
| `Sesi ID` | `session_id` | Upsert key #1 |
| `Item` | `item` | Upsert key #2 |
| `Tipe` | `type` | |
| `Status` | `status` | |
| `Catatan` | `remark` | |

---

## 2. Enum Audit

| Enum | Model Cast | Raw usage | Issues |
|---|---|---|---|
| `AttendanceStatus` | `BriefingAttendance::$casts` ✓ | Filament table uses `tryFrom($state)` | **BUG**: `tryFrom()` called on `$state` which is already an enum object after DB read with cast → throws TypeError in some Filament versions. Must use `$state instanceof AttendanceStatus ? $state : AttendanceStatus::tryFrom((string)$state)` |
| `MPCheckStatus` | `BriefingSession::$casts` ✓ | Service compares `$session->mp_check_status === MPCheckStatus::Cleared` | OK (enum object comparison) |
| `MPBackupType` | `BriefingSession::$casts` ✓ | Not used in Filament column rendering | OK |
| `PpeCondition` | Not in `BriefingAttendance` or `BriefingAttendancePpeItem` | Used raw in RelationManager | No cast — raw string comparison: risky if data varies |
| `fit_status` | **No enum, no cast** | Stored as RAW uppercase string "FIT"/"TIDAK FIT" | Design choice: intentional raw string. But no enum validation — anything AppSheet sends gets stored. |
| `mp_type` | **No enum, no cast** | Stored as raw string; `strtolower()` applied in service | "Reguler" from AppSheet → "reguler" after strtolower ≠ "regular" → breaks backup-vs-regular branching logic |
| `personal_ppe_status` | **No enum, no cast** | Raw string | No validation |
| `apd_request_status` | **No enum, no cast** | Raw string | No validation |

---

## 3. Topik Briefing Investigation

- The AppSheet `mp_check` table has a field **"Catatan Operasional"** mapped to `notes` (text column, plain string).
- There is **no** "topik_briefing" or "briefing_topics" field anywhere in: DB schema, config/appsheet.php, or model fillable.
- The `briefing_checklists` table serves the structured checklist role (item + type + status).
- **Decision**: `notes` is a single freetext operational notes field. No migration needed. No array cast required.

---

## 4. Manpower Bridge Status — Where Resolution Still Fails

### Current state
- `manpowers.appsheet_id` column exists (migration `2026_06_09_000001`), unique, nullable.
- All resolution goes through `Manpower::where('appsheet_id', $appsheetMpId)->first()`.
- **manpowers table is EMPTY** (count = 0).
- Therefore: **every** `detail_mp_check` webhook for `mp_type = regular` will produce a warning log and `return null` (graceful skip, no FK violation).

### What still needs external action
1. **AppSheet-side**: The `daftar_mp` AppSheet table must send a webhook on each manpower record (or a bulk import command must be run: `php artisan manpower:import-appsheet`).
2. **Until manpowers are seeded with appsheet_id**, briefing attendance sync is a no-op for all regular MPs.
3. For backup MPs (`mp_type = backup`): resolution skips the manpower lookup entirely — these WILL be inserted correctly.

### mp_type normalization gap (BUG)
AppSheet sends "Tipe MP" as "Reguler" (Indonesian) not "regular" (English). The `normalizeValue()` method does NOT normalize this field. After `strtolower()` in `syncBriefingAttendance()`, the value becomes "reguler" — which does NOT match `=== 'regular'`, causing backup-branch logic to misfire: regular MPs are treated as backup MPs.

---

## 5. Architecture Audit: Active vs Dead Code

### ACTIVE path (confirmed from routes/api.php + controller):
```
POST /api/appsheet/webhook
  → AppSheetWebhookController::handle()
  → AppSheetService::syncFromWebhook()
  → AppSheetService::sync{Entity}() methods
```

### DEAD CODE (never called):
- `app/Services/AppSheet/Handlers/` — all 10 handler classes
- `app/Services/AppSheet/SyncHandlerRegistry.php`
- `app/Services/AppSheet/PayloadNormalizer.php`

None of these are injected into any controller or service provider. The `SyncHandlerRegistry` maps different table names than AppSheetService uses:
- Registry uses `briefing_sessions` (plural); Service uses `mp_check`
- Registry uses `briefing_attendances` (plural); Service uses `detail_mp_check`

The Handler architecture was built as a refactor that was never wired in. It is dead code.

### Discrepancies in dead Handler architecture:
- `BriefingAttendanceHandler` requires `manpower_id` in `validateData()` — would fail for backup MPs.
- `BriefingPpeItemHandler` validates a "status" field that does not exist in config/appsheet.php (`condition` is the field, not `status`).
- `PayloadNormalizer::mapFields()` has no normalization logic (no `normalizeValue()` call).

---

## 6. Duplicate Unique Index (Bug)

Two conflicting unique indexes exist simultaneously on `briefing_attendances`:
- `briefing_attendance_unique`: `(session_id, manpower_id, mp_type, backup_name)` — correct for backup MP support
- `uniq_session_mp`: `(session_id, manpower_id)` — legacy, does NOT allow NULL manpower_id duplicates for backup MPs

**The `uniq_session_mp` index will block inserting two backup MPs** in the same session since both have `manpower_id = NULL`, violating `(session_id, NULL)` uniqueness. This is a production blocker for backup MP support.

A migration must drop `uniq_session_mp`.
