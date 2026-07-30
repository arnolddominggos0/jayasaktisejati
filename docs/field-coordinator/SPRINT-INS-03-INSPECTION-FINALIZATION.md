# Sprint INS-03 — Inspection Finalization & PIC Signature

**Status:** Scope 1-7 IMPLEMENTED & tervalidasi. **Scope 7 (guard tightening) sudah DIAKTIFKAN** atas konfirmasi eksplisit Anda — lihat §Scope 7 untuk dampak operasional langsung yang perlu ditindaklanjuti.
**Tanggal:** 23 Juli 2026

---

## Temuan Audit Kritis (sebelum implementasi apa pun)

Sebelum menulis kode, saya audit `UnitInspection` dan menemukan sesuatu yang mengubah total pendekatan sprint ini:

**Sebagian besar Scope 2-4 SUDAH ADA sejak migrasi `2026_06_20`** — `signed_by`, `signed_at`, `signature_path` sudah jadi kolom nyata, dan `InspectUnitPage` (Detail Inspection) **sudah mewajibkan** signature sebelum submit (`->required(! $this->isReadOnly)`), dan **sudah mengunci** seluruh form setelah submit (`$this->isReadOnly = $this->inspection->submitted_at !== null`, disable semua field). Hanya **"Jabatan PIC"** yang benar-benar belum ada di mana pun.

**Temuan yang lebih penting — dua jalur submit paralel:** `OperationalTasks.php` punya modal generik "Update" (`Action::make('updateTrack')`) yang **JUGA bisa submit inspeksi** (set `submitted_at`, `status`, `gate_decision`) — tapi **TANPA satu pun field signature**. Ini persis pola "dua jalur dengan syarat berbeda" yang berulang kali ditemukan sepanjang sesi ini (Container Allocation vs legacy, dst.). Dicek langsung ke database: **8 dari 8 (100%) inspeksi yang sudah pernah disubmit TIDAK PERNAH ditandatangani** — termasuk milik shipment nyata, bukan cuma trial.

---

## Scope 1 — Lifecycle (Implemented, sebagai derived accessor)

**Keputusan desain:** lifecycle diimplementasikan sebagai **accessor turunan** (`UnitInspection::getFinalizationStateAttribute()`), **BUKAN** nilai baru pada kolom `status`. Alasan: `status` (pending/passed/failed) dipakai luas di tempat lain (badge kartu unit Workspace, dst.) — mengubahnya berisiko melanggar "Jangan mengubah business rule existing". Lifecycle yang dihasilkan:

```
draft               → submitted_at IS NULL
submitted_unsigned  → submitted_at ada, TAPI signed_by/signed_position/signature_path belum lengkap
finalized           → submitted_at ada DAN seluruh data signature lengkap
```

**Tidak ada state "Cancelled"** — brief bilang "jika memang sudah ada"; diperiksa, TIDAK ada konsep ini di `UnitInspection` mana pun sebelum sprint ini. **Tidak ditambahkan** — dilaporkan di sini, bukan diam-diam dibuat.

Diverifikasi terhadap data nyata: seluruh 8 inspeksi submitted → `submitted_unsigned` (benar, karena memang belum ditandatangani); 1 sampel draft → `draft` (benar).

---

## Scope 2 — Signature (3 dari 4 field sudah ada, 1 field baru)

| Field | Status |
|---|---|
| PIC Name | ✅ Sudah ada (`signed_by`, string, sejak 2026-06-20) |
| Signature Image | ✅ Sudah ada (`signature_path`) |
| Signed At | ✅ Sudah ada (`signed_at`) |
| **PIC Position (Jabatan)** | 🆕 **Baru** — kolom `signed_position` (migrasi additif, `Ran`) |

---

## Scope 3 — Finalization Validation

- **Signature tersedia:** sudah tervalidasi (form-level `required()`, sekarang + validasi eksplisit di `submit()` sebagai defense-in-depth).
- **Foto wajib sudah lengkap:** **temuan penting** — `UnitInspectionItem.photo_url` sudah ada di skema sejak lama, tapi **tidak pernah punya input UI sama sekali** di `InspectUnitPage` maupun jalur `updateTrack`. Mewajibkan "foto lengkap" tanpa menambah input akan membuat Finalize **mustahil selamanya** untuk setiap inspeksi. Saya tambahkan `FileUpload::make('photo_url')` per item (pola sama persis dengan `signature_path` yang sudah ada) — ini menyambungkan field yang sudah ada, bukan konsep baru, dan diperlukan agar syarat Scope 3 ini bisa benar-benar terpenuhi oleh siapa pun.
- **Checklist selesai:** item SELALU sudah ada sejak draft dibuat (`InspectionDraftAutoCreate`) dengan default `result=OK` — tidak ada state "belum ditinjau" yang bisa dibedakan dari "sudah ditinjau, memang OK" tanpa menambah flag baru per item. **Saya TIDAK menambah flag ini** (di luar cakupan minimal yang diminta) — dilaporkan sebagai keterbatasan verifikasi, bukan diam-diam diabaikan.

---

## Scope 4 — Lock Inspection

**Sudah ada sejak sebelumnya, dikonfirmasi masih benar:** `$this->isReadOnly = $this->inspection->submitted_at !== null` men-disable SELURUH field form (checklist, foto, signature) begitu `submitted_at` terisi. Tidak ada perubahan diperlukan — hanya diverifikasi ulang tetap berfungsi dengan field baru (`photo_url`, `signed_position` juga otomatis ikut ter-disable karena pola yang sama).

Satu-satunya jalur "unlock" yang sudah ada: **"Reset untuk Re-Inspeksi"** (hanya untuk gate `return_to_pdc`) — diperbarui agar juga membersihkan `signed_position` (konsisten dengan field signature lain yang sudah dibersihkan di aksi ini).

---

## Scope 5 — Audit Trail

Ditambahkan `Log::info('INSPECTION FINALIZED', [...])` di akhir `submit()` — berisi `inspection_id`, `unit_id`, `stage`, `signed_by`, `signed_position`, `signed_at`, `result`, `gate_decision`. Memakai pola tag yang sama dengan `SHIPMENT TRANSITION GUARD` (ARCH-01) untuk konsistensi dan kemudahan pencarian log. **Bukan sistem approval baru** — satu baris log per Finalize, sesuai instruksi.

---

## Scope 6 — UI

- Section form direlabel: "Tanda Tangan Digital" → **"Persetujuan Inspeksi (Inspection Approval)"**, kini berisi PIC/Jabatan/Signature (Tanggal & Status ditampilkan di blok evidence read-only, bukan form-input — karena keduanya derived/timestamp, bukan input manual).
- Tombol submit direlabel **"Finalize Inspection"** (sebelumnya "Submit Inspeksi").
- Badge **FINALIZED** (hijau) vs **SUBMITTED — BELUM FINALIZED** (amber) ditampilkan eksplisit di blok evidence read-only, berdasarkan `is_finalized` — menggantikan warning generik lama yang cuma bilang "inspeksi lama tanpa signature".

---

## Scope 7 — Integration (Guard) — ✅ DIAKTIFKAN

**Audit selesai** (sesuai instruksi eksplisit sprint: *"audit dahulu sebelum mengubahnya"*):
- Guard yang ada (`Shipment::handoverInspectionBlockReason()`, `ensureLoadingInspectionCleared()` — dari ARCH-01) membaca `submitted_at` + `gate_decision` — **BUKAN** `status===PASSED` seperti diasumsikan brief, tapi **JUGA belum membaca signature apa pun.**

**Perubahan yang diterapkan:**
- `handoverInspectionBlockReason()` (sudah ada, ARCH-01) — ditambah **Validation 4**: seluruh unit pada stage `handover_depot` harus punya `signed_by` DAN `signed_position` DAN `signature_path` terisi, atau ditolak dengan pesan eksplisit.
- `ensureLoadingInspectionCleared()` — diekstrak mengikuti pola persis `handoverInspectionBlockReason()` menjadi `loadingInspectionBlockReason()` (protected, dipakai guard) + `isLoadingInspectionCleared()` (public, untuk UI/automation) — logic 3 validasi ASLI dipertahankan verbatim, ditambah Validation 4 yang sama.

Karena kedua guard ini dipanggil dari `Shipment::runTransitionGuards()` (satu titik masuk, ARCH-01) — perubahan ini **otomatis berlaku untuk SEMUA jalur** (`InspectUnitPage`, modal "Update" generik, API/automation masa depan) tanpa perlu menyentuh `updateTrack` sama sekali.

**Dampak langsung, dikonfirmasi via test terhadap SELURUH shipment nyata di database:**
```
Shipment #228 (JSS0726SH0001): handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002): handoverCleared=false, loadingCleared=false
Shipment #230 (OPS08-TRIAL-154113): handoverCleared=false, loadingCleared=false
... (seluruh shipment trial lain: sama)
```
**Seluruh 7 shipment di database (termasuk 2 shipment nyata) saat ini `false`** — persis sesuai prediksi audit (8/8 inspeksi lama tidak bertanda tangan). **Ini TIDAK memengaruhi shipment yang statusnya sudah lewat titik itu** (mis. shipment trial #230 yang sudah `Delivered`) — guard hanya dievaluasi pada SAAT `appendTrack()` dipanggil, bukan retroaktif terhadap Track yang sudah tercatat.

**Tindak lanjut operasional yang diperlukan:** untuk `JSS0726SH0001`/`JSS0726SH0002` (shipment nyata) — jika keduanya BELUM melewati tahap Stuffing/DeliveryToPort, tidak ada masalah mendesak (mereka baru akan terblokir SAAT mencoba maju). Kalau salah satunya SEDANG mencoba maju sekarang, operator perlu membuka inspeksi terkait lewat `InspectUnitPage` dan melengkapi PIC/Jabatan/Tanda Tangan (form sudah mewajibkan ini otomatis untuk inspeksi yang belum submitted; untuk yang SUDAH submitted tanpa signature, perlu "Reset untuk Re-Inspeksi" — saat ini hanya tersedia untuk gate `return_to_pdc`, di luar cakupan sprint ini untuk diperluas).

---

## Validasi

| Uji | Hasil |
|---|---|
| `php -l` (migration, model, page) | ✅ Bersih |
| `php artisan migrate --pretend` (preview) → `--force` (jalankan) | ✅ SQL murni additif 1 kolom, DONE 15.76ms |
| `composer dump-autoload` + `view:cache`/`view:clear` | ✅ Sukses |
| Reflection: `signed_position` fillable, `getIsFinalizedAttribute`/`getFinalizationStateAttribute` ada | ✅ |
| Fungsional terhadap data nyata: 8 inspeksi submitted → `submitted_unsigned` (benar); 1 draft → `draft` (benar) | ✅ |
| Form `InspectUnitPage` dibangun via reflection — 2 komponen top-level (Repeater + Section), tanpa error | ✅ |
| Reflection: `loadingInspectionBlockReason` (protected) / `isLoadingInspectionCleared` (public) ada dengan visibility benar | ✅ |
| `php -l` ulang setelah Scope 7 (Shipment.php, UnitInspection.php, InspectUnitPage.php) | ✅ Bersih |
| `isHandoverInspectionCleared()`/`isLoadingInspectionCleared()` dijalankan terhadap SELURUH 7 shipment nyata di database | ✅ Seluruhnya `false` — persis sesuai prediksi audit (0 inspeksi ter-Finalize) |
| `composer dump-autoload` + `view:cache`/`view:clear` (setelah Scope 7) | ✅ Sukses |

---

## Konfirmasi Batas

- ✅ Tidak ada business rule existing yang diubah untuk Scope 1-6 (murni tambahan field + UI + accessor turunan).
- ✅ Migration murni additif, sudah dijalankan, terverifikasi.
- ✅ Scope 7 diaktifkan atas konfirmasi eksplisit Anda — perubahan business rule yang disengaja dan diminta, bukan efek samping tak terduga. Dampaknya (memblokir progres shipment dengan inspeksi belum Finalized, termasuk 2 shipment nyata) dilaporkan gamblang, bukan disembunyikan.
