# MP Check Remediation Plan

Status: ACTIVE
Owner: Jaya Sakti Sejati
Module: MP Check + AppSheet Integration + FC Dashboard
Last Updated: 2026-05-30

---

# Objective

Menjadikan MP Check sebagai source of truth operasional lapangan yang konsisten dari:

AppSheet
→ Webhook
→ Laravel
→ Database
→ FC Dashboard

Tujuan utama:

- Data AppSheet selalu tersimpan dengan benar.
- Status MP Check dihitung konsisten.
- Dashboard FC menampilkan kondisi operasional aktual.
- Tidak ada dead code, dead config, atau hidden business rules.

---

# Current Findings Summary

Audit menemukan:

Critical:
- C1 summary_sufficient dihitung dengan logika berbeda.
- C2 fit_status menggunakan raw string dan case-sensitive.
- C3 webhook dapat dipanggil tanpa signature.
- C4 dead method evaluateMpCheckStatus() memanggil relation yang tidak ada.

Important:
- I1 created_by / checked_by tidak konsisten.
- I2 add_checked_by dan after_sync tidak digunakan.
- I3 dead methods.
- I4 duplicate unique index.
- I5 duplicate migration.
- I6 missing casts.
- I7 migration cleanup.

Nice To Have:
- N1 dead fields documentation.
- N2 summary_headcount cast.
- N3 enum consistency.
- N4 normalizeValue hardening.
- N5 APD enum.

---

# Source Of Truth

## Operational Readiness

Operational Readiness ditentukan oleh:

1. MP hadir cukup
2. MP FIT cukup
3. Tidak ada recheck pending
4. APD mencukupi
5. mp_check_status = Cleared atau Approved

---

## Manpower Sufficiency

Source of truth:

FIT count

Formula:

FIT_COUNT >= SUMMARY_HEADCOUNT

Bukan:

PRESENT_COUNT >= SUMMARY_HEADCOUNT

Seluruh sistem wajib menggunakan aturan yang sama.

---

## Fit Status

Nilai valid:

FIT
UNFIT
WAITING_RECHECK

Semua input AppSheet harus dinormalisasi menjadi uppercase.

---

# Sprint 1 - Stabilization

Target:
Menghilangkan bug yang mempengaruhi operasional.

---

## C1 - Fix summary_sufficient inconsistency

Priority:
CRITICAL

Problem:

Saat ini terdapat beberapa implementasi:

- evaluateBriefingSession()
- BriefingSession::booted()
- BriefingAttendance::booted()
- recalculateBriefingSession()

Menggunakan metric berbeda:

FIT count
vs
PRESENT count

Impact:

summary_sufficient dapat berubah secara tidak konsisten.

Decision:

Gunakan:

FIT_COUNT >= SUMMARY_HEADCOUNT

untuk seluruh sistem.

Actions:

- Audit seluruh assignment summary_sufficient
- Hapus logika berbasis PRESENT count
- Standardisasi ke FIT count

Validation:

- 8 FIT dari kebutuhan 8 → sufficient = true
- 7 FIT dari kebutuhan 8 → sufficient = false

Status:
DONE

---

## C2 - Normalize fit_status

Priority:
CRITICAL

Problem:

Dashboard menggunakan:

fit_status === 'FIT'

Jika AppSheet mengirim:

fit
Fit
FIT

hasil berbeda.

Decision:

Semua fit_status disimpan uppercase.

Actions:

Tambahkan pada normalizeValue():

```php
$value = strtoupper(trim($value));
```

untuk field:

- fit_status

Validation:

Input:

fit
Fit
FIT

Output:

FIT

Status:
DONE

---

## C3 - Require Webhook Signature

Priority:
CRITICAL

Problem:

Saat ini:

Jika header signature tidak ada,
request tetap diproses.

Impact:

Webhook bisa dipanggil siapa saja.

Decision:

Jika APPSHEET_WEBHOOK_SECRET tersedia:

header wajib ada.

Actions:

Reject request:

HTTP 403

jika:

X-AppSheet-Signature tidak ditemukan.

Validation:

Tanpa signature:
403

Dengan signature valid:
200

Status:
DONE

---

## C4 - Remove dead evaluateMpCheckStatus()

Priority:
CRITICAL

Problem:

Method:

evaluateMpCheckStatus()

memanggil:

checklists()

yang tidak ada pada model.

Impact:

Latent runtime bug.

Decision:

Method dihapus.

Actions:

- Cari seluruh reference
- Pastikan tidak digunakan
- Hapus method

Validation:

Project tetap lulus test.

Status:
DONE

---

# Sprint 2 - Cleanup

Target:
Mengurangi technical debt.

---

## I1 - created_by / checked_by consistency

Priority:
IMPORTANT

Problem:

mapFields() menginject:

created_by
checked_by

tetapi model tidak memiliki kolom.

Decision:

Pilih salah satu:

Option A:
Tambahkan migration.

Option B:
Stop inject field.

Recommended:
Option B.

Status:
TODO

---

## I2 - Remove dead config

Priority:
IMPORTANT

Fields:

add_checked_by
after_sync

tidak pernah digunakan.

Decision:

Hapus dari config.

Status:
TODO

---

## I3 - Remove dead methods

Priority:
IMPORTANT

Audit:

recalculateBriefingSession()

Decision:

Jika tidak direferensikan,
hapus.

Status:
TODO

---

## I4 - Duplicate unique index

Priority:
IMPORTANT

Table:

briefing_attendances

Duplicate:

- briefing_attendance_unique
- uniq_session_mp

Decision:

Pertahankan satu.

Status:
TODO

---

## I5 - Duplicate migration

Priority:
IMPORTANT

Fields:

- rest_started_at
- recheck_result

Decision:

Dokumentasikan dan cleanup.

Status:
TODO

---

## I6 - StockApdCheck casts

Priority:
IMPORTANT

Add:

```php
protected $casts = [
    'stock_available' => 'integer',
    'required_quantity' => 'integer',
];
```

Status:
TODO

---

## I7 - Migration cleanup

Priority:
IMPORTANT

briefing_evidence_path migration
memiliki down() kosong.

Decision:

Tambahkan rollback.

Status:
TODO

---

# Sprint 3 - Hardening

---

## N1 - Document AppSheet-only fields

Fields:

BriefingSession:

- notes
- briefing_evidence_path
- summary_solution
- backup_required
- backup_type
- backup_notes
- apd_request_status
- apd_request_note

BriefingAttendance:

- temperature
- bp_systolic
- bp_diastolic
- health_complaint
- rest_started_at
- recheck_result
- medical_action
- personal_ppe_status
- remark
- signature_path

StockApdCheck:

- remark

Decision:

Tetap disimpan.

Tidak ditampilkan pada dashboard.

Status:
TODO

---

## N2 - summary_headcount cast

Add:

```php
'summary_headcount' => 'integer'
```

Status:
TODO

---

## N3 - AttendanceStatus consistency

Decision:

Gunakan enum secara konsisten.

Status:
TODO

---

## N4 - normalizeValue hardening

Handle:

- numeric string
- empty string
- null string
- date string

Status:
TODO

---

## N5 - APD Status Enum

Create:

ApdStockStatus

Values:

- cukup
- kurang

Status:
TODO

---

# Validation Checklist

## AppSheet

- MP Check creates BriefingSession
- Detail MP creates BriefingAttendance
- APD Check creates StockApdCheck

---

## Evaluation

- summary_sufficient calculated correctly
- mp_check_status updated correctly
- readiness status updated correctly

---

## Dashboard

- Operational Readiness reflects MP Check
- MP roster updates automatically
- APD shortage displayed
- Unfit manpower displayed

---

## Security

- Invalid signature rejected
- Missing signature rejected
- Unauthorized depot rejected

---

# Done Definition

Project dianggap selesai apabila:

- Semua Critical selesai.
- Dashboard membaca data MP Check tanpa mismatch.
- AppSheet webhook aman.
- Tidak ada dead operational logic.
- FC Dashboard menjadi single operational view untuk MP Check.