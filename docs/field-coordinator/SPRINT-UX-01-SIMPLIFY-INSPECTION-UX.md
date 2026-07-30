# Sprint UX-01 — Simplify Field Coordinator Inspection Workflow

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression check OPS-08.
**Tanggal:** 23 Juli 2026

---

## Ringkasan Perubahan UX

**Backend (accessor, method, nilai kolom) TIDAK diubah sama sekali** — `finalization_state` (`draft`/`submitted_unsigned`/`finalized`), `is_finalized`, `isHandoverInspectionCleared()`/`isLoadingInspectionCleared()` tetap persis seperti INS-03/ARCH-01. **Hanya LABEL yang ditampilkan ke FC yang berubah.**

---

## Istilah yang Diganti (Scope 1+2+4+6+7)

| Lokasi | Sebelum | Sesudah |
|---|---|---|
| Modal Update — badge status per unit | `FINALIZED` / `SUBMITTED (Belum Finalized)` / `DRAFT` / `BELUM ADA` | `✅ Sudah selesai` / `⚠ Belum selesai` / `⚠ Belum dilakukan` |
| Modal Update — tombol aksi | `Buka Inspection` (statis) | **Dinamis**: `Inspeksi Unit` / `Lanjutkan Inspeksi` / `Lihat Hasil` (Scope 3) |
| Modal Update — judul section | `Inspection Unit` | `Inspeksi Unit` |
| InspectUnitPage — badge status | `FINALIZED` / `SUBMITTED — BELUM FINALIZED` | `Inspeksi Selesai` / `Belum Lengkap` |
| InspectUnitPage — tombol submit | `Finalize Inspection` | `Selesaikan Inspeksi` |
| InspectUnitPage — judul section | `Persetujuan Inspeksi (Inspection Approval)` | `Persetujuan & Tanda Tangan` |
| InspectUnitPage — notifikasi sukses | `Inspeksi berhasil di-Finalize` | `Inspeksi selesai` |
| InspectUnitPage — notifikasi validasi | `Finalize ditolak` | `Belum bisa diselesaikan` |
| InspectUnitPage — pesan legacy warning | `...BELUM Finalized...` | `...belum lengkap...` |

**Tidak diubah** (di luar cakupan eksplisit sprint ini, konsisten di seluruh app): label "Status: PASSED/FAILED" dan `UnitInspection::GATE_LABELS` (Accept/Allow with Remark/Return to PDC) — ini konvensi lama yang sudah dipakai luas sejak sebelum INS-03/04, mengubahnya di sini saja akan menciptakan inkonsistensi baru dengan tempat lain (kartu unit Workspace, dst.) — di luar cakupan "hanya perubahan UX" sprint ini.

---

## Scope 3 — Dynamic Action (dengan satu keterbatasan jujur)

Tombol berubah otomatis berdasar `finalization_state` + heuristik tambahan:
```
finalized                                → "Lihat Hasil"
submitted_unsigned, ATAU draft+disentuh  → "Lanjutkan Inspeksi"
draft (belum disentuh) / belum ada       → "Inspeksi Unit"
```

**Catatan jujur:** tidak ada field di database yang secara presisi menandai "sedang dikerjakan" vs "belum disentuh sama sekali" (menambah field itu = mengubah database, eksplisit di luar cakupan sprint ini). Dipakai **heuristik**: item dianggap "pernah disentuh" kalau ada yang `result=NG`, atau `notes`/`photo_url` terisi (menyimpang dari default). Ini pendekatan, bukan pelacakan pasti — keterbatasan yang sama sudah diakui di INS-03 soal "checklist selesai".

---

## Scope 5 — Redirect Workflow

**Diimplementasikan, TIDAK ada kendala arsitektur yang menghalangi** (berbeda dari dugaan awal saya sebelum investigasi):

1. **Redirect ke Inspeksi:** ditambahkan pengecekan di `updateTrack`'s `->action()`, SEBELUM `appendTrack()` dipanggil — kalau tahap target benar-benar digerbangi Inspeksi yang belum selesai (persis kondisi yang sama dipakai `ensureHandoverInspectionCleared()`/`ensureLoadingInspectionCleared()`, ARCH-01+INS-03), operator diarahkan LANGSUNG ke `InspectUnitPage` untuk unit yang belum selesai, alih-alih mengalami `appendTrack()` gagal dulu.
   - **Guard tetap satu-satunya sumber kebenaran** — baris baru ini hanya MEMBACA `isHandoverInspectionCleared()`/`isLoadingInspectionCleared()` (method publik yang sudah ada sejak ARCH-01/INS-03), tidak menduplikasi logic-nya.
   - **Sengaja TIDAK diterapkan** untuk tahap pickup/unloading/selfdrive/dooring — tahap-tahap itu TIDAK digerbangi Inspeksi sama sekali oleh `appendTrack()` (temuan audit) — menerapkan redirect di sana akan menciptakan aturan UX yang lebih ketat dari domain-nya sendiri, melanggar "Guard tetap satu-satunya sumber validasi".
2. **Kembali otomatis ke Operational Tasks:** ditambahkan dukungan parameter `?return=operational-tasks` (opsional, additive) di `InspectUnitPage` — kalau dibuka lewat link ini, tombol "Kembali"/redirect setelah selesai mengarah balik ke `OperationalTasks`, bukan default (`OperationalShipmentPage`). Entry-point LAIN yang tidak mengirim parameter ini (mis. dari kartu unit di halaman lain) **berperilaku persis seperti sebelumnya, tidak berubah**.

Dengan ini, alur penuh Scope 5 tercapai: `Operational Tasks → (redirect otomatis) → InspectUnitPage → Selesaikan Inspeksi → (redirect otomatis) → kembali ke Operational Tasks`.

---

## Regression Check OPS-08

**Wajib dibuktikan, bukan diasumsikan.** Dijalankan ulang `isHandoverInspectionCleared()`/`isLoadingInspectionCleared()` terhadap seluruh 7 shipment nyata (termasuk shipment trial OPS-08) setelah SEMUA perubahan sprint ini:

```
Shipment #228 (JSS0726SH0001): handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002): handoverCleared=false, loadingCleared=false
Shipment #230 (OPS08-TRIAL-154113): handoverCleared=false, loadingCleared=false
... (seluruh shipment lain: sama)
```
**Hasil identik byte-for-byte** dengan baseline INS-03/INS-04 — konsisten dengan fakta bahwa `Shipment.php` (tempat guard hidup) **tidak disentuh sama sekali** di sprint ini.

---

## Validasi

| Uji | Hasil |
|---|---|
| `php -l` (ShipmentResource.php, OperationalTasks.php, InspectUnitPage.php, partial, blade view) | ✅ Bersih semua |
| `composer dump-autoload` + `view:cache`/`view:clear` | ✅ Sukses |
| Render partial baru terhadap shipment nyata — dikonfirmasi TIDAK ada lagi `FINALIZED`/`DRAFT`/`SUBMITTED`/`Buka Inspection`, DAN mengandung istilah operasional + `?return=operational-tasks` | ✅ |
| Logic deteksi redirect (`$gatingStage`, `whereDoesntHave`) diuji terhadap shipment nyata — benar mengidentifikasi stage `handover_depot` sebagai gerbang untuk `Stuffing`, benar menemukan unit yang belum selesai | ✅ |
| `InspectUnitPage::getUrl(['return' => 'operational-tasks'])` menghasilkan URL benar (`?return=operational-tasks` di query string) | ✅ |
| `OperationalTasks::table()` dibangun penuh setelah seluruh perubahan | ✅ |
| **Regression OPS-08**: guard identik dengan baseline sebelum sprint ini | ✅ |

---

## Konfirmasi Batas

- ✅ Tidak ada perubahan domain, guard, workflow, database, atau Inspection Engine — `Shipment.php` tidak disentuh sama sekali.
- ✅ Backend tetap memakai `draft`/`submitted_unsigned`/`finalized` sebagai nilai internal — FC tidak pernah melihat istilah ini.
- ✅ Redirect Scope 5 memakai method guard PUBLIK yang sudah ada (bukan logic baru) — tidak ada duplikasi business rule ke UI.
- ✅ Perubahan `?return=` bersifat additive/opsional — entry-point lain ke InspectUnitPage tidak terpengaruh.
