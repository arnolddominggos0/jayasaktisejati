# Sprint INS-05 — Simplify Inspection (Operational SOP)

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression INS-03 & OPS-08.
**Tanggal:** 23 Juli 2026

---

## 1. File yang Berubah

| File | Perubahan |
|---|---|
| `app/Filament/FC/Pages/InspectUnitPage.php` | `FileUpload('photo_url')` dihapus dari form; validasi "foto wajib" dihapus dari `submit()`; `photo_url` dihapus dari fill/update payload |
| `resources/views/filament/fc/resources/shipment-resource/pages/inspect-unit.blade.php` | Ditambahkan help text (Scope 4) — syarat penyelesaian inspeksi, hanya tampil saat form masih bisa diedit |

**Tidak ada migration, tidak ada perubahan database.**

---

## 2. Validasi yang Dihapus (Scope 1)

1. **Field UI:** `FileUpload::make('photo_url')` (per item, di dalam Repeater checklist) — dihapus total dari `form()`.
2. **Validasi Finalize:** blok `$missingPhoto = collect(...)->first(...); if ($missingPhoto) { ... return; }` di `submit()` — dihapus total.
3. **Payload penyimpanan:** `'photo_url' => $itemData['photo_url']` dihapus dari `UnitInspectionItem::update()`, dan `'photo_url' => $item->photo_url` dihapus dari `mount()`'s `fill()`.

**Finalize sekarang hanya membutuhkan:** checklist (item selalu ada sejak draft dibuat) + PIC (`signed_by`) + Jabatan (`signed_position`) + Signature (`signature_path`) — persis Scope 3.

---

## 3. Tooltip/Help Text yang Ditambahkan (Scope 4)

Kotak info biru sederhana, ditampilkan **hanya saat inspeksi masih bisa diedit** (`! $this->isReadOnly` — tidak relevan lagi setelah Finalize/terkunci), persis di atas form:

> **Inspeksi dinyatakan selesai apabila:**
> • Seluruh checklist telah diperiksa.
> • PIC telah diisi.
> • Jabatan telah diisi.
> • Tanda tangan telah diberikan.

Murni informatif — tidak ada logic baru, tidak memengaruhi validasi apa pun.

---

## 4. Bukti Foto Item Tidak Lagi Dipakai

| Bukti | Hasil |
|---|---|
| Form schema `InspectUnitPage::form()` di-build & seluruh nama field dienumerasi | `photo_url` **tidak ada** dalam daftar field (`items, id, category, item_name, result, signed_by, signed_position, signature_path`) |
| Kolom `unit_inspection_items.photo_url` di database | **Tetap ada** (Scope 5 — schema tidak diubah), hanya tidak lagi diminta/ditampilkan/ditulis |
| `mount()` dijalankan langsung terhadap shipment/unit nyata (#235/#233) | Berhasil tanpa error meski `photo_url` tidak lagi ada di fill payload |

---

## 5. Regression Check

### INS-03 (Approval/Finalization tetap utuh)
```
is_finalized dengan PIC+Jabatan+Signature lengkap (foto SAMA SEKALI tidak dicek): true (benar)
is_finalized dengan signed_position kosong:                                       false (benar — tetap ditegakkan)
```
`UnitInspection::is_finalized`/`finalization_state` **tidak pernah bergantung pada `photo_url`** sejak awal (accessor itu hanya mengecek `submitted_at`+`signed_by`+`signed_position`+`signature_path`) — sehingga tidak ada risiko regresi pada logic Finalization di level model, persis sesuai Scope 5.

### Lock & Audit Log & Single Entry Point
- `isReadOnly = $this->inspection->submitted_at !== null` — baris tidak berubah, dikonfirmasi masih ada.
- `Log::info('INSPECTION FINALIZED', ...)` — baris tidak berubah, dikonfirmasi masih ada.
- Grep menyeluruh: **hanya `InspectUnitPage.php`** yang menulis data checklist/signature di seluruh aplikasi — Single Entry Point (INS-04) utuh.

### OPS-08
```
Shipment #228 (JSS0726SH0001): handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002): handoverCleared=false, loadingCleared=false
... (seluruh shipment lain: sama, termasuk #235)
```
**Identik byte-for-byte** dengan seluruh baseline sebelumnya — `Shipment.php` (Guard) tidak disentuh sprint ini.

---

## Validasi Teknis

| Uji | Hasil |
|---|---|
| `php -l` pada 2 file yang diubah | ✅ Bersih |
| `composer dump-autoload` + `view:cache`/`view:clear` (mengompilasi blade baru termasuk help text) | ✅ Sukses |
| `mount()` dijalankan langsung terhadap data nyata setelah perubahan | ✅ Berhasil |
| Form schema dibangun & dienumerasi — `photo_url` nihil, field checklist & approval utuh | ✅ |
| Kolom `photo_url` di skema database dikonfirmasi masih ada | ✅ |
| Help text dikonfirmasi ada di source blade | ✅ |
| **Regression INS-03** (Finalization/Lock/Audit Log/Single Entry Point) | ✅ Semua utuh |
| **Regression OPS-08** | ✅ Identik dengan seluruh baseline sebelumnya |

---

## Konfirmasi Batas

- ✅ Tidak ada perubahan `UnitInspection`, Transition Guard, `Shipment.php`, Single Entry Point, Audit Log, Lock, atau Database Schema.
- ✅ Checklist tetap jadi dasar inspeksi (Scope 2) — tidak disentuh.
- ✅ Approval (PIC/Jabatan/Signature) tetap wajib penuh untuk Finalize (Scope 3).
- ✅ Inspeksi sekarang lebih ringan bagi FC — satu langkah upload (signature) dihilangkan per unit, bukan per item.
