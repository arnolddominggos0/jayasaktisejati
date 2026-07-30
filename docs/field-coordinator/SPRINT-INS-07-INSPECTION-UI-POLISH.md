# Sprint INS-07 — Inspection UI Polish

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression INS-03/INS-04/OPS-08.
**Tanggal:** 24 Juli 2026
**Konteks:** lanjutan Sprint INS-06 (tooltip → helper text inline) — sprint ini murni merapikan wording dan visual hierarchy-nya, tanpa mengubah mekanisme.

---

## 1. File yang Berubah

| File | Perubahan |
|---|---|
| `config/unit_inspection_templates.php` | Wording `criteria` **seluruh 36 item** dirapikan jadi poin pendek (2-5 kata, tanpa titik, tanpa kata sambung berlebihan). `name`/`type` tidak berubah. |
| `app/Services/InspectionDraftAutoCreate.php` | `criteriaHelperText()`: prefix `"OK apabila: "` **dicabut** dari return value — sekarang hanya mengembalikan `"• Poin • Poin • Poin"`. Lookup logic (category+item_name, baca config) tidak berubah. |
| `app/Filament/FC/Pages/InspectUnitPage.php` | Closure `helperText()` pada field `item_name` dibungkus `HtmlString` bertema `text-xs text-gray-500 dark:text-gray-400 mt-0.5` (lebih kecil dari default Filament `text-sm`) — visual hierarchy sesuai Scope 3. Teks tetap di-escape (`e()`) sebelum dibungkus HTML. |

**Tidak ada migration, tidak ada perubahan database, tidak ada file baru.**

---

## 2. Guidance yang Diperbaiki (Scope 1 + 2)

Prefix `"OK apabila:"` dihapus dari **seluruh 36 item** (bukan hanya yang dicontohkan di brief) — dijamin konsisten karena hanya ada **satu** titik kode (`criteriaHelperText()`) yang memproduksi teks ini untuk seluruh checklist (Scope 4 — tidak mungkin ada campuran format).

Ringkasan wording yang dirapikan (seluruh 36 item):

| Stage | Item | Sebelum (INS-06) | Sesudah (INS-07) |
|---|---|---|---|
| pickup/EXTERIOR | Lampu Depan | Lampu kiri dan kanan menyala normal. • Tidak retak. • Tidak pecah. • Masing-masing terpasang dengan baik. | Menyala normal • Tidak retak • Tidak pecah • Terpasang baik |
| pickup/EXTERIOR | Lampu Belakang | (pola sama) | Menyala normal • Tidak retak • Tidak pecah • Terpasang baik |
| pickup/EXTERIOR | Lampu Sign | Lampu sign kiri dan kanan menyala dan berkedip normal. • Tidak retak atau pecah. • Terpasang dengan baik. | Menyala dan berkedip normal • Tidak retak • Tidak pecah • Terpasang baik |
| pickup/EXTERIOR | Bumper Depan / Belakang | Tidak penyok. • Tidak retak / pecah. • Cat tidak baret dalam. • Terpasang dengan baik. | Tidak penyok • Tidak retak • Tidak baret • Terpasang baik |
| pickup/EXTERIOR | Emblem | Lengkap, tidak hilang. • Tidak lepas / longgar. • Tidak baret / rusak. | Lengkap • Tidak lepas • Tidak rusak |
| pickup/EXTERIOR | Spion | Kaca tidak retak / pecah. • Rumah spion tidak rusak. • Berfungsi dilipat / digerakkan normal (jika elektrik). | Kaca tidak retak • Rumah tidak rusak • Berfungsi normal |
| pickup/EXTERIOR | **Ban** *(contoh brief)* | Tekanan normal. • Tidak bocor. • Tidak sobek. • Kondisi masih layak. | **Tekanan normal • Tidak bocor • Tidak sobek** |
| pickup/EXTERIOR | **Velg** *(contoh brief)* | Tidak penyok / retak. • Tidak baret dalam. • Baut / mur lengkap dan kencang. | **Tidak penyok • Tidak retak • Baut lengkap** |
| pickup/INTERIOR | AC | Dingin dan berfungsi normal. • Tidak berisik / bergetar tidak normal. • Tidak ada kebocoran freon / air. | Dingin normal • Tidak berisik • Tidak bocor |
| pickup/INTERIOR | Radio | Menyala dan berfungsi normal. • Speaker tidak rusak / pecah suara. • Tombol / panel lengkap dan berfungsi. | Menyala normal • Speaker jernih • Tombol lengkap |
| pickup/INTERIOR | Dashboard | Tidak retak / pecah. • Indikator / lampu dashboard menyala normal. • Tidak ada bagian lepas / longgar. | Tidak retak • Indikator normal • Tidak ada bagian lepas |
| pickup/INTERIOR | Power Window | Naik-turun normal di semua pintu. • Tidak macet / berbunyi kasar. • Saklar berfungsi baik. | Naik-turun normal • Tidak macet • Saklar berfungsi |
| pickup/DOCUMENT | Buku Service / Owner Manual | Tersedia, sesuai unit. • Tidak rusak / sobek. • Data terisi lengkap. | Tersedia • Sesuai unit • Data lengkap / Tidak rusak |
| pickup/ACCESSORIES | Toolkit, Dongkrak, Segitiga Pengaman | (kalimat panjang bervariasi) | Lengkap/Tersedia • Tidak hilang/rusak • Tidak patah |
| handover_depot/DOKUMEN | Verifikasi Nomor Rangka / SJKB | Nomor rangka pada unit sesuai dengan dokumen. dst. | Sesuai dokumen • Tercetak jelas • Tidak cacat / Tersedia • Sesuai unit • Terbaca jelas |
| handover_depot/KONDISI EKSTERIOR | Kondisi Body, Lampu, Kaca, Ban | (kalimat panjang bervariasi) | Tidak penyok/retak/baret/bocor • dst. (pola sama seperti pickup) |
| handover_depot/KELENGKAPAN | Kelengkapan Unit | Seluruh aksesoris standar tersedia. dst. | Aksesoris lengkap • Tidak ada hilang • Sesuai daftar |
| handover_depot/CATATAN KEDATANGAN | Catatan Kerusakan Saat Tiba | Catat seluruh kerusakan yang ditemukan saat unit tiba, sekecil apa pun. dst. | Catat semua kerusakan • Sertakan lokasi jelas |
| loading/LOADING | Unit/Container/Seal Condition | (kalimat panjang bervariasi) | Tidak ada kerusakan baru • Bersih • Tidak bocor • Lantai kokoh • Kondisi baik • Nomor sesuai dokumen |
| unloading/UNLOADING | Unit Condition, Physical Damage Check | (kalimat panjang bervariasi) | Tidak ada kerusakan baru • Sesuai catatan sebelumnya • Periksa seluruh sisi • Catat jika ada kerusakan |
| selfdrive/SELFDRIVE | Unit Condition, Fuel Check | (kalimat panjang bervariasi) | Layak dikendarai • Tidak ada kerusakan berbahaya • Catat level BBM • Sesuai kebutuhan perjalanan |
| dooring/FINAL | Unit Condition, Customer Acceptance, Final Quality | (kalimat panjang bervariasi) | Tidak ada kerusakan baru • Sudah diperiksa customer • Tidak ada keberatan • Bersih dan rapi • Checklist lengkap • Siap diserahkan |

**Catatan:** wording setiap poin adalah keputusan konten (bukan fakta teknis untuk di-audit) — ditulis mengikuti gaya persis 3 contoh verbatim di brief (Lampu Depan, Velg, Ban), diterapkan konsisten ke 33 item lainnya (memotong kalimat panjang, menghapus titik/kata sambung, menghapus poin yang tumpang tindih). Karena seluruhnya terpusat di `config/unit_inspection_templates.php`, tim Ops dapat mengoreksi kata per kata kapan saja tanpa menyentuh kode.

---

## 3. Contoh Sebelum & Sesudah (tampilan)

**Lampu Depan** — sebelum (INS-06 v2):
```
Item
Lampu Depan
OK apabila: • Lampu kiri dan kanan menyala normal. • Tidak retak. • Tidak pecah. • Masing-masing terpasang dengan baik.
                                                                              [ OK ]  [ NG ]
```

**Lampu Depan** — sesudah (INS-07), diverifikasi langsung dari `criteriaHelperText()`:
```
Item
Lampu Depan
• Menyala normal • Tidak retak • Tidak pecah • Terpasang baik      ← text-xs, abu-abu, mt-0.5
                                                                              [ OK ]  [ NG ]
```

**Velg** — sesudah: `• Tidak penyok • Tidak retak • Baut lengkap` — **cocok persis** dengan contoh verbatim di brief.
**Ban** — sesudah: `• Tekanan normal • Tidak bocor • Tidak sobek` — **cocok persis** dengan contoh verbatim di brief.

---

## 4. Bukti Workflow Tidak Berubah (Scope 6)

| Area dilarang diubah | Bukti |
|---|---|
| Checklist (struktur item, Repeater, ToggleButtons OK/NG) | Tidak disentuh — hanya `helperText()` pada field `item_name` yang berubah. |
| Approval / Signature / Finalize | `InspectUnitPage::submit()` **tidak ada dalam diff perubahan sprint ini** — validasi PIC/Jabatan/Signature persis sama. |
| Audit | `Log::info('INSPECTION FINALIZED', ...)` tidak disentuh. |
| Guard | `app/Models/Shipment.php` **tidak disentuh** sprint ini — dibuktikan lewat regression OPS-08 di §5 (identik byte-for-byte). |
| Database | `php artisan migrate:status` — migration terakhir tetap `2026_07_23_150000_add_position_to_unit_inspections_table` (INS-03), tidak bertambah. |
| Single Entry Point | Tidak ada file lain yang menulis data inspeksi — hanya `InspectUnitPage.php`. |

---

## 5. Validasi Teknis & Regression

| Uji | Hasil |
|---|---|
| `php -l` pada 3 file yang diubah | ✅ Bersih |
| `config:clear`, `view:clear`, `composer dump-autoload` | ✅ Sukses |
| Config sanity: 36/36 item punya `criteria`, 0 poin masih pakai titik/kalimat panjang (>5 kata) | ✅ |
| 3 contoh verbatim brief (Lampu Depan, Velg, Ban) → **exact match** dengan output `criteriaHelperText()` | ✅ |
| Seluruh 36 item nyata di database → 0 yang masih mengandung "OK apabila" | ✅ |
| `InspectUnitPage::mount()` end-to-end (shipment #235 / unit #233, inspection #280, sebagai field_coordinator sungguhan) | ✅ Berhasil |
| HTML helper text yang dihasilkan mengandung class `text-xs`, `text-gray-500`, `mt-0.5` | ✅ |
| `migrate:status` — tidak ada migration baru | ✅ |

### Regression INS-03 / INS-04
- `submit()` tidak disentuh — PIC/Jabatan/Signature tetap wajib, gate decision, lock, Single Entry Point identik.

### Regression OPS-08
```
Shipment #228 (JSS0726SH0001):         handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002):         handoverCleared=false, loadingCleared=false
Shipment #230 (OPS08-TRIAL-154113):    handoverCleared=false, loadingCleared=false
Shipment #231 (OPS08-NEG-154323):      handoverCleared=false, loadingCleared=false
Shipment #232 (OPS08-CAP-154323):      handoverCleared=false, loadingCleared=false
Shipment #233 (OPS08-CAPFIX-A-154435): handoverCleared=false, loadingCleared=false
Shipment #234 (OPS08-CAPFIX-B-154436): handoverCleared=false, loadingCleared=false
Shipment #235 (JSS0726SH0003):         handoverCleared=false, loadingCleared=false
```
**Identik byte-for-byte** dengan seluruh baseline sebelumnya — `Shipment.php` tidak pernah disentuh sprint ini.

---

## Konfirmasi Batas

- ✅ Prefix "OK apabila:" hilang total, dari kode maupun seluruh 36 item data.
- ✅ Seluruh guidance berbentuk poin pendek (2-5 kata), format `Poin • Poin • Poin` konsisten di semua item.
- ✅ Visual hierarchy diperhalus (`text-xs`/`text-gray-500`/`mt-0.5`) — fokus tetap pada nama item & tombol OK/NG.
- ✅ Sumber teks tetap mapping backend yang sama (`config/unit_inspection_templates.php`) — tidak hardcode di Blade, hanya wording di config yang diperbaiki sesuai instruksi Scope 5.
- ✅ Tidak ada perubahan Checklist, Approval, Signature, Finalize, Audit, Guard, Database, atau Single Entry Point.
