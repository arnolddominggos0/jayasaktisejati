# FC PRD + Setup Rebuild (Branch Current State)

Dokumen ini menjadi **single source** untuk perbaikan struktur kerja di branch saat ini:
- apa yang menjadi baseline,
- bagaimana setup validasi,
- urutan build ulang,
- dan prompt final untuk OpenCode CLI.

---

## 0) Decision gate: prompting dulu atau next task?

Jawaban untuk kondisi branch saat ini:
- **Sekarang masuk ke prompting dulu** (eksekusi FC-05A + FC-05B di OpenCode CLI).
- **Belum masuk next task fitur baru**.

Kapan boleh lanjut next task (FC-06/FC-07)?
- jika lint/test gate lulus,
- tidak ada blocker 500 scope,
- dan migration gate client ke `effective_branch_id` sudah hijau.

---

## Fokus aktif sekarang

**Masuk ke FC-05A dan FC-05B** (stabilization + migration gate), dengan objective:
- stabilkan policy/scope,
- validasi kontrak auth field transisi,
- pastikan client siap pakai `effective_branch_id`.

FC-06/FC-07 ditahan sampai gate A/B selesai.

---

## 1) Baseline branch saat ini

Gunakan branch ini sebagai baseline fakta:
1. Transisi scope user sudah mengarah ke `effective_branch_id`.
2. API auth membawa field transisi: `branch_id`, `scope_branch_id`, `effective_branch_id`.
3. Setelah FC-05, pekerjaan harus lewat gate stabilisasi + migrasi client sebelum lanjut fitur baru.

---

## 2) Tujuan rebuild struktur

Tujuan perbaikan ini **bukan** menambah fitur baru, tetapi:
- merapikan alur PRD → setup → eksekusi,
- memastikan validasi dilakukan konsisten,
- mencegah scope drift saat transisi canonical scope.

---

## 3) Setup ulang (wajib sebelum build)

### 3.1 Setup environment

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate:status
```

### 3.2 Gate command (jalankan di OpenCode CLI)

```bash
./vendor/bin/pint --test
php artisan test --filter=FC
php artisan test --filter=Shipment
php artisan test --filter=Tracking
php artisan test --filter=Print
```

### 3.3 Targeted regression (scope transisi)

```bash
php artisan test --filter=ShipmentPolicyScopeTest
php artisan test --filter=CanonicalScopeModelTest
php artisan test --filter=AppSheetCanonicalScopeTest
php artisan test --filter=AppSheetBriefingIngestionTest
```

> Aturan: jangan klaim pass jika command tidak benar-benar jalan.

---

## 4) Urutan build ulang (disiplin tahap)

1. **Tahap A — FC-05A Stabilization Gate**
   - Audit scope/authorization,
   - fix blocker,
   - jalankan gate lint + test.

2. **Tahap B — FC-05B Client Migration Gate**
   - client read scoping wajib pakai `effective_branch_id`,
   - `branch_id` legacy dipertahankan sementara,
   - monitoring mismatch scope/error 4xx/5xx.

3. **Tahap C — Lanjut FC-06/FC-07 (opsional)**
   - hanya jika Tahap A + B hijau.

---

## 5) Triage cepat error 500 `/fc/briefing-sessions`

Urutan diagnosa:
1. cek migration status,
2. cek backfill canonical scope user FC,
3. cek log aplikasi.

Command:

```bash
php artisan migrate:status
tail -n 200 storage/logs/laravel.log
```

---

## 6) Prompt final (copy ke OpenCode CLI)

```txt
Konteks: gunakan baseline branch saat ini + dokumen docs/FC_PRD_SETUP_REBUILD.md.
Tujuan: final verification + hardening sebelum merge, tanpa menambah fitur baru.

Eksekusi:
- Jalankan di OpenCode CLI (plan + build di CLI), jangan patch langsung di chat.

Task:
1) Audit fallback branch scoping FC/Admin agar konsisten dengan effective_branch_id.
2) Verifikasi kontrak AuthController@login dan AuthController@me (branch_id, scope_branch_id, effective_branch_id).
3) Jalankan gate lint + test + targeted regression sesuai dokumen.
4) Jika muncul 500 pada /fc/briefing-sessions, jalankan triage dan laporkan akar masalah.

Output wajib:
- Unified patch summary
- Changed files
- Risk notes
- Exact command logs + hasil real (pass/fail)

Aturan:
- Jangan klaim test pass jika command gagal/tidak jalan.
- Jangan ubah logic shipment land.
```
