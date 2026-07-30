# Sprint INS-04 — Unify Inspection Entry Point

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression check terhadap OPS-08.
**Tanggal:** 23 Juli 2026

---

## Scope 1 — Audit Entry Point (tidak ada kode diubah pada scope ini)

Ditelusuri seluruh 34 file yang mereferensikan `UnitInspection`/`UnitInspectionItem`, disaring ke yang benar-benar bisa MENULIS (bukan sekadar membaca/menampilkan):

| Entry Point | Status Sebelum Sprint | Keterangan |
|---|---|---|
| `InspectUnitPage::submit()` | ✅ Live, sudah lengkap sejak INS-03 | Checklist + foto + PIC + jabatan + signature + Finalize |
| `OperationalTasks.php` → `updateTrack` action | ⚠️ **Live, DUPLIKAT** | Repeater checklist sendiri (`ShipmentResource::inspectionFormFields()`), submit inline TANPA signature sama sekali — inilah yang dihapus sprint ini |
| `UnitInspectionsRelationManager` (Admin panel) | ✅ Tidak aktif | `canCreate() => false`, hanya `ViewAction` (read-only detail). `form()`-nya secara eksplisit didokumentasikan sebagai "schema referensi untuk panel FC... tidak aktif di admin panel" — bukan entry point nyata. **Ditemukan menarik:** form referensi ini punya aturan foto berbeda dari yang saya implementasikan di INS-03 (wajib hanya jika `finding_type=major_damage`, bukan wajib untuk semua item) — dicatat sebagai temuan audit, TIDAK ditindaklanjuti (di luar cakupan sprint ini, dan file ini tidak aktif). |
| `InspectionDraftAutoCreate` | ✅ Bukan entry point submit | Hanya membuat DRAFT (`result=OK` default) — tidak pernah mengisi `submitted_at`/signature. Tetap dipertahankan (dibutuhkan agar draft ada sebelum InspectUnitPage dibuka). |
| Console commands (`UnitInspectionGenerator`, `GenerateHistoricalInspections`, `BackfillInspectionDrafts`) | ✅ Bukan entry point operator live | Jalur import data historis (`source=historical_import`), offline/retroaktif — bukan bagian dari workflow operator harian. Di luar cakupan sprint ini. |
| API Controller | — | Tidak ditemukan satu pun endpoint API yang menyentuh `UnitInspection`. |

**Kesimpulan audit: hanya ADA SATU duplikasi nyata** — `OperationalTasks.php`'s modal Update — persis seperti dugaan brief.

---

## Scope 2+3 — InspectUnitPage sebagai Single Entry Point

**`ShipmentResource::inspectionFormFields()` DIHAPUS TOTAL**, diganti `inspectionStatusFields()` — method baru yang HANYA menampilkan status (tidak ada satu pun input form). `OperationalTasks.php`'s `updateTrack`:
- Form: `array_merge(trackUpdateForm(), inspectionFormFields())` → `array_merge(trackUpdateForm(), inspectionStatusFields())`.
- Action: seluruh blok pemrosesan checklist (grouping `inspection_items_flat`, update `UnitInspectionItem`, evaluasi gate, update `UnitInspection` submitted_at/status/gate_decision) **DIHAPUS TOTAL** — sekitar 70 baris logic dihilangkan.

**Dikonfirmasi via grep menyeluruh:** `InspectUnitPage.php` sekarang **satu-satunya file** di seluruh aplikasi yang menulis `result`/`finding_type`/`notes`/`photo_url` pada item, atau `signed_by`/`signature_path` pada inspection.

**Yang TETAP dipertahankan (bukan dihapus):**
- `InspectionDraftAutoCreate::ensureForShipmentAndStage()` di `fillForm()` — draft harus tetap ada sebelum InspectUnitPage dibuka (Scope 7: "Jangan membuat draft kedua" — dipertahankan APA ADANYA, bukan diduplikasi).
- Snapshot `check_result` pada `ShipmentTrack` (dipakai Timeline `ViewShipment`) — **dibangun ulang sebagai pembacaan read-only** dari `UnitInspection` yang SUDAH Finalized (guard baru saja memverifikasinya saat `appendTrack()` berhasil), BUKAN dari input form yang dihapus. Ini menjaga fitur Timeline tetap berfungsi tanpa menghidupkan kembali jalur edit.
- `optionalChecksheetSchema()` (`checkseet` Repeater, "Checksheet Unit — Opsional") — **TIDAK disentuh**, ini mekanisme berbeda untuk tahap TANPA inspeksi formal (OnShip, VesselDepart, dst.), disimpan ke `ShipmentTrack.checkseet`, sama sekali di luar cakupan sprint ini.

---

## Scope 4+6 — Status Indicator & UX

Section baru **"Inspection Unit"** di modal Update (`resources/views/filament/fc/shipments/partials/inspection-status-list.blade.php`, baru) — satu baris per unit, badge status memakai **`UnitInspection::finalization_state`** (accessor INS-03, **tidak dihitung ulang** — persis instruksi Scope 4):

```
🟢 FINALIZED   | 🟡 SUBMITTED (Belum Finalized)   | ⚪ DRAFT   | ⚪ BELUM ADA
```

Setiap baris punya tombol **"Buka Inspection"** → link langsung ke `InspectUnitPage::getUrl(['record' => ..., 'unit' => ...])`.

---

## Scope 5 — Workflow (guard tetap satu-satunya sumber kebenaran)

**Tidak ada kode baru diperlukan untuk scope ini** — sudah terpenuhi secara struktural: `updateTrack`'s `->action()` sudah membungkus `appendTrack()` dalam `try/catch (DomainException $e)` sejak sebelumnya (tidak diubah), dan pesan guard INS-03 Scope 7 ("Ada unit yang inspeksi ... belum di-Finalize...") SUDAH merupakan "pesan yang jelas" sesuai contoh di brief. Saya **tidak menambahkan validasi apa pun di UI** untuk mengecek status Finalized sebelum submit — itu akan melanggar "Jangan memindahkan business rule ke UI". Satu-satunya sumber keputusan tetap `Shipment::runTransitionGuards()`, tidak disentuh sprint ini.

---

## Scope 7 — Navigation

"Buka Inspection" adalah `<a href="...">` biasa (navigasi langsung, bukan Livewire action dalam modal) menuju `InspectUnitPage` untuk kombinasi shipment+unit yang benar. Draft dipastikan ada lewat `ensureForShipmentAndStage()` yang **sudah ada** (dipertahankan di `fillForm()`) — **tidak ada draft kedua yang dibuat**.

---

## Scope 8 — Negative Test / Konfirmasi

| Klaim | Hasil |
|---|---|
| Tidak ada lagi dua tempat submit inspection | ✅ Dikonfirmasi via grep: hanya `InspectUnitPage.php` menulis `result`/`signed_by`/`signature_path` di seluruh app |
| Checklist hanya bisa diubah dari InspectUnitPage | ✅ Sama seperti di atas |
| Signature hanya ada di InspectUnitPage | ✅ Sama seperti di atas |
| OperationalTasks hanya membaca status | ✅ `inspectionStatusFields()` + partial baru murni read-only, dikonfirmasi lewat render test terhadap shipment nyata |
| Transition Guard tetap bekerja | ✅ **Regression test**: `isHandoverInspectionCleared()`/`isLoadingInspectionCleared()` dijalankan ulang terhadap seluruh 7 shipment nyata — hasil **identik byte-for-byte** dengan baseline INS-03 (`Shipment.php` tidak disentuh sama sekali sprint ini) |
| Tidak ada perubahan business workflow | ✅ `Shipment.php` (guard/appendTrack) tidak diedit di sprint ini |

---

## Validasi

| Uji | Hasil |
|---|---|
| `php -l` (ShipmentResource.php, OperationalTasks.php, partial baru) | ✅ Bersih |
| Import yang jadi tidak terpakai (`UnitInspectionItem`, `InspectionGateEvaluator`, `ToggleButtons`, `Grid`) dibersihkan dari kedua file | ✅ |
| `composer dump-autoload` + `view:cache`/`view:clear` (termasuk partial baru) | ✅ Sukses |
| `OperationalTasks::table()` dibangun penuh — action `updateTrack` masih terdaftar | ✅ |
| Partial `inspection-status-list.blade.php` di-render langsung terhadap shipment nyata (`JSS0726SH0001`) | ✅ Berhasil, mengandung badge status dan tombol "Buka Inspection" |
| **Regression OPS-08**: guard dijalankan ulang terhadap seluruh 7 shipment nyata (termasuk shipment trial OPS-08) | ✅ Hasil identik dengan sebelum sprint ini |

---

## Konfirmasi Batas

- ✅ Tidak mengubah Transition Guard, Signature, Checklist (isi/aturan), Inspection Engine, atau business workflow — hanya merapikan SATU entry point yang duplikat.
- ✅ Tidak ada approval tambahan, tidak ada digital certificate.
- ✅ Fitur Timeline (`check_result` snapshot) tetap berfungsi lewat pembacaan read-only dari data yang sudah Finalized, bukan dihidupkan kembali sebagai jalur edit.
