# Sprint INS-06 — Checklist Inspection Tooltip

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression INS-03/INS-04/OPS-08.
**Tanggal:** 24 Juli 2026

---

## 1. File yang Berubah

| File | Perubahan |
|---|---|
| `config/unit_inspection_templates.php` | Setiap item (36 total, 6 stage) ditambah key `'criteria'` (array bullet singkat) — **satu-satunya sumber teks tooltip**. Key `name`/`type` tidak diubah. |
| `app/Services/InspectionDraftAutoCreate.php` | Ditambahkan method statis baru `criteriaTooltip(?string $category, ?string $itemName): ?string` — membaca `config('unit_inspection_templates')`, mengembalikan teks tooltip siap-tampil atau `null`. Method lain di file ini (`resolveStage`, `ensureForTrack`, `ensureForShipmentAndStage`, `createItems`) **tidak disentuh**. |
| `app/Filament/FC/Pages/InspectUnitPage.php` | Field `TextInput::make('item_name')` di dalam Repeater `items` ditambah `->hintIcon(...)` — ikon info + tooltip, muncul hanya jika `criteriaTooltip()` mengembalikan nilai. Tidak ada baris lain yang diubah (`submit()`, `mount()`, `resolveStage()`, `getHeaderActions()` persis sama). |
| `resources/views/filament/fc/resources/shipment-resource/pages/inspect-unit.blade.php` | Blok help box biru global (INS-05, Scope 4) **dihapus total**, diganti komentar penjelas. Tidak ada perubahan lain di file ini. |

**Tidak ada migration, tidak ada perubahan database, tidak ada file baru** (semua perubahan terjadi di file yang sudah ada).

---

## 2. Bagaimana Tooltip Bersumber Datanya (Scope 3)

### 2.1 Audit field yang tersedia

Diperiksa skema `unit_inspection_items` (2 migration: `create_unit_inspection_items_table` + `add_finding_fields_to_unit_inspection_items_table`) dan model `UnitInspectionItem`. Kolom yang benar-benar ada:

```
id, unit_inspection_id, category, item_name, result, finding_type, notes, photo_url, created_at, updated_at
```

**Tidak ada** kolom `description`, `guidance`, atau `inspection_standard`.

**`notes` ADA**, tapi diaudit dan disimpulkan **tidak layak** dipakai sebagai sumber tooltip:
- `notes` diisi **oleh FC saat inspeksi berlangsung** (lihat `InspectUnitPage::submit()` — `'notes' => $isNg ? ($itemData['notes'] ?? null) : null`), bukan teks standar yang disiapkan di muka.
- Nilainya **kosong (`null`) di setiap draft baru** (`InspectionDraftAutoCreate::createItems()` selalu membuat item dengan `'notes' => null`) — kalau dipakai sebagai tooltip, ikon info tidak akan punya isi sama sekali sampai FC pertama kali mengisi NG, bertentangan langsung dengan tujuan sprint ini ("agar **seluruh** FC memiliki standar pemeriksaan yang **sama**").
- Nilainya **spesifik per-inspeksi** (unit A stage pickup bisa punya `notes` berbeda dari unit B stage pickup meski item_name sama) — bukan satu standar global yang konsisten, juga bertentangan dengan tujuan sprint.
- Field ini **sekaligus jadi input yang FC tulis sendiri** di form yang sama (`Textarea::make('notes')`) — memakainya sebagai sumber tooltik-baca akan membuat field yang sama menjadi tempat baca dan tulis secara bersamaan, source yang tidak stabil.

**Kesimpulan: tidak ada field yang cocok pada `unit_inspection_items`.**

### 2.2 Sumber yang dipakai

`config/unit_inspection_templates.php` **sudah menjadi SSOT** untuk nama/kategori/tipe checklist (dibaca `InspectionDraftAutoCreate::createItems()` saat membuat draft, dan `UnitInspectionGenerator::buildItems()` saat membuat historical import). Sprint ini **memperluas SSOT yang sama** dengan key `'criteria'` per item:

```php
[
    'name'     => 'Lampu Depan',
    'type'     => 'major_damage',
    'criteria' => [
        'Lampu kiri dan kanan menyala normal.',
        'Tidak retak.',
        'Tidak pecah.',
        'Masing-masing terpasang dengan baik.',
    ],
],
```

`InspectionDraftAutoCreate::criteriaTooltip(string $category, string $itemName): ?string` mencari entri yang cocok (category + item_name) di seluruh stage pada config ini, menggabungkan bullet-nya jadi satu string (`"OK apabila: • ... • ... • ..."`), dan mengembalikan `null` bila tidak ditemukan atau tidak ada `criteria`. `InspectUnitPage::form()` memanggil method ini lewat closure `Get $get` per baris Repeater — **tidak ada string tooltip yang ditulis di Blade maupun di form() sama sekali**, murni delegasi ke config.

**Lookup key sengaja (category, item_name) — bukan stage.** Diverifikasi terhadap seluruh isi config: nama kategori (`EXTERIOR`, `DOKUMEN`, `LOADING`, dst.) tidak pernah dipakai ulang antar stage, sehingga pasangan (category, item_name) sudah unik tanpa perlu stage untuk disambiguasi. Dicatat sebagai asumsi eksplisit di docblock method, untuk diperiksa ulang jika suatu saat ada kategori yang sama dipakai di dua stage berbeda dengan kriteria berbeda.

### 2.3 Rekomendasi untuk masa depan (tanpa migration sekarang, sesuai instruksi)

Saat ini **tidak ada tabel DB untuk template checklist** — seluruhnya hidup di file config, yang berarti mengubah kriteria butuh deploy kode. Ini konsisten dengan bagaimana `name`/`type` sudah bekerja sejak awal (bukan sesuatu yang baru diperkenalkan sprint ini).

Jika ke depannya tim Ops ingin **mengubah teks kriteria tanpa deploy** (mis. lewat halaman admin), rekomendasi terbaik: promosikan `config/unit_inspection_templates.php` menjadi tabel `unit_inspection_item_templates` (kolom: `stage`, `category`, `item_name`, `type`, `criteria` json/text), lalu `InspectionDraftAutoCreate::createItems()` dan `criteriaTooltip()` dialihkan membaca dari tabel itu alih-alih `config()`. **Tidak dikerjakan sekarang** — murni rekomendasi, sesuai Scope 3 ("laporkan rekomendasi terbaik tanpa langsung menambah migration").

---

## 3. Contoh Tooltip (dibangkitkan dari config, bukan hardcode)

Diambil langsung dari hasil pemanggilan `InspectionDraftAutoCreate::criteriaTooltip()` terhadap config yang sudah diperluas:

| Item | Tooltip |
|---|---|
| Lampu Depan (EXTERIOR, pickup) | `OK apabila: • Lampu kiri dan kanan menyala normal. • Tidak retak. • Tidak pecah. • Masing-masing terpasang dengan baik.` |
| Ban (EXTERIOR, pickup) | `OK apabila: • Tekanan normal. • Tidak bocor. • Tidak sobek. • Kondisi masih layak.` |
| Verifikasi SJKB (DOKUMEN, handover_depot — item dokumen kendaraan terdekat; tidak ada item bernama persis "Dokumen Kendaraan" di checklist yang sudah ada, lihat §5) | `OK apabila: • SJKB tersedia dan sesuai unit. • Data unit pada SJKB terbaca jelas. • Tidak rusak / hilang.` |
| AC (INTERIOR, pickup) | `OK apabila: • Dingin dan berfungsi normal. • Tidak berisik / bergetar tidak normal. • Tidak ada kebocoran freon / air.` |
| Seal Condition (LOADING, loading) | `OK apabila: • Seal tersedia dan dalam kondisi baik. • Nomor seal tercatat dan sesuai dokumen.` |
| Customer Acceptance (FINAL, dooring) | `OK apabila: • Customer telah memeriksa dan menyetujui kondisi unit. • Tidak ada keberatan / catatan dari customer saat serah terima.` |

**Cakupan penuh:** ke-36 item di seluruh 6 stage (pickup, handover_depot, loading, unloading, selfdrive, dooring) diberi `criteria` — bukan hanya 3 contoh dari brief. Alasan: agar goal sprint ("seluruh FC memiliki standar pemeriksaan yang sama") tercapai untuk **seluruh checklist**, bukan sebagian yang membuat tampilan tidak konsisten (sebagian item ada ⓘ, sebagian tidak). Teks kriteria ini disusun berdasarkan nama/kategori/`type` (major_damage/minor_missing/information_only) tiap item sebagai konten operasional yang wajar — **bukan disalin dari dokumen SOP tertulis manapun** (tidak ditemukan satu di codebase). Karena sekarang terpusat satu-satunya di `config/unit_inspection_templates.php`, tim Ops dapat mengoreksi kata-katanya kapan saja tanpa menyentuh Blade/PHP lain.

---

## 4. Perilaku Tampilan (Scope 4)

- Ikon `heroicon-o-information-circle` muncul di baris label field **"Item"**, memakai mekanisme bawaan Filament `hintIcon()` (`Filament\Forms\Components\Concerns\HasHint`, dipakai semua field Filament 3 — sudah tersedia, bukan komponen kustom baru).
- Tooltip muncul lewat Tippy/Alpine (`x-tooltip`) bawaan Filament — **hover di desktop, tap/klik di mobile** — persis mekanisme standar yang dipakai hint icon Filament di seluruh aplikasi ini.
- **Ikon disembunyikan total** (bukan ikon kosong) untuk item yang tidak punya `criteria` di config — dikonfirmasi lewat closure `fn (Get $get) => filled(criteriaTooltip(...)) ? 'heroicon-o-information-circle' : null`.
- Tidak ada teks panjang yang mengisi halaman — satu baris ringkas per item, hanya tampil saat diminta (hover/klik). Checklist (Repeater `items`) tetap elemen utama form, tidak ada elemen baru yang bersaing secara visual.

---

## 5. Catatan: Contoh "Dokumen Kendaraan" pada Brief

Brief menyebutkan contoh format untuk item **"Dokumen Kendaraan"** — item dengan nama persis ini **tidak ada** di `config/unit_inspection_templates.php` manapun (diverifikasi dengan membaca seluruh isi file sebelum mengubah apa pun). Item yang ada dan paling dekat secara makna: `Buku Service`/`Owner Manual` (DOCUMENT, pickup) dan `Verifikasi Nomor Rangka`/`Verifikasi SJKB` (DOKUMEN, handover_depot).

**Keputusan:** tidak menambah/mengganti nama item checklist untuk mencocokkan contoh secara harfiah — mengubah nama/menambah item checklist adalah perubahan set item pemeriksaan itu sendiri (bukan sekadar teks bantuan), berisiko memengaruhi inspeksi yang sudah berjalan (di luar Scope 3 yang eksplisit hanya bicara "tooltip"), dan berpotensi melanggar Scope 5 ("Tidak mengubah: ... Workflow"). Sebagai gantinya, keempat item dokumen yang sudah ada diberi `criteria` bergaya sama (lihat §3) — semangat instruksi brief tetap terpenuhi tanpa mengubah struktur checklist.

---

## 6. Bukti Tidak Ada Perubahan Workflow (Scope 5)

Scope 5 eksplisit: *"Tidak mengubah: UnitInspection, Guard, Workflow, Finalization, Approval, Database."*

| Area dilarang diubah | Bukti tidak disentuh |
|---|---|
| `app/Models/UnitInspection.php` | **Tidak ada di daftar file yang diedit** sprint ini (§1) — accessor `finalization_state`/`is_finalized` persis sama dengan INS-03/INS-05. |
| Guard (`app/Models/Shipment.php`, `runTransitionGuards()`, `isHandoverInspectionCleared()`/`isLoadingInspectionCleared()`) | **Tidak disentuh.** Dibuktikan lewat regression check §7 — hasil identik byte-for-byte dengan seluruh baseline sebelumnya. |
| Workflow (`OperationalTasks.php`, `ShipmentResource::inspectionStatusFields()`, `resolveStage()`) | **Tidak disentuh.** `InspectUnitPage.php` hanya berubah pada satu field (`item_name` → `hintIcon()`) di dalam `form()`; `mount()`, `resolveStage()` (termasuk fix bugfix sebelumnya) persis sama. |
| Finalization/Approval (`InspectUnitPage::submit()`, validasi PIC/Jabatan/Signature) | **Tidak disentuh** — method `submit()` tidak ada dalam diff perubahan; tetap menolak submit tanpa `signed_by`/`signed_position`/`signature_path` seperti INS-03/INS-05. |
| Database | **Tidak ada migration baru** — `php artisan migrate:status` mengonfirmasi migration terakhir tetap `2026_07_23_150000_add_position_to_unit_inspections_table` (dari INS-03), tidak bertambah. Perubahan `config/unit_inspection_templates.php` menambah key `'criteria'` yang **tidak dibaca** oleh `createItems()`/`buildItems()` (keduanya hanya mengakses `$item['name']`/`$item['type']`) — nol risiko terhadap alur pembuatan draft/historical import. |

---

## 7. Validasi Teknis & Regression

| Uji | Hasil |
|---|---|
| `php -l` pada 3 file PHP yang diubah | ✅ Bersih |
| `config:clear`, `view:clear`, `composer dump-autoload` | ✅ Sukses |
| Config sanity: 36 item total, 0 item tanpa `criteria` | ✅ |
| `criteriaTooltip()` untuk 3 contoh verbatim dari brief (Lampu Depan, Ban) — teks cocok persis | ✅ |
| `criteriaTooltip()` untuk item tidak dikenal / kosong → `null` | ✅ |
| **Seluruh 36 pasangan (category, item_name) yang benar-benar ada di database** (bukan hanya di config) → semuanya resolve tooltip, 0 unresolved | ✅ |
| `InspectUnitPage::mount()` dijalankan end-to-end terhadap data nyata (shipment #235 / unit #233, inspection #280 — sama seperti kasus bugfix sebelumnya), sebagai user field_coordinator sungguhan | ✅ Berhasil, tidak ada exception |
| Seluruh 18 item checklist stage pickup pada inspection #280 nyata → ikon tooltip "shown" untuk semuanya | ✅ |
| Grep: tidak ada teks kriteria yang di-hardcode di Blade atau di `form()` | ✅ (hanya closure yang memanggil `InspectionDraftAutoCreate::criteriaTooltip()`) |

### Regression INS-03/INS-04 (Finalization/Single Entry Point tetap utuh)
- `submit()` tidak berubah — PIC/Jabatan/Signature tetap wajib, logic gate/finalize identik.
- Single Entry Point tetap `InspectUnitPage.php` — tidak ada file lain yang disentuh untuk menulis data inspeksi.

### Regression OPS-08
```
Shipment #228 (JSS0726SH0001):        handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002):        handoverCleared=false, loadingCleared=false
Shipment #230 (OPS08-TRIAL-154113):   handoverCleared=false, loadingCleared=false
Shipment #231 (OPS08-NEG-154323):     handoverCleared=false, loadingCleared=false
Shipment #232 (OPS08-CAP-154323):     handoverCleared=false, loadingCleared=false
Shipment #233 (OPS08-CAPFIX-A-154435):handoverCleared=false, loadingCleared=false
Shipment #234 (OPS08-CAPFIX-B-154436):handoverCleared=false, loadingCleared=false
Shipment #235 (JSS0726SH0003):        handoverCleared=false, loadingCleared=false
```
**Identik byte-for-byte** dengan seluruh baseline sebelumnya (ARCH-01 → INS-03 → INS-04 → UX-01 → UX-02 → UX-03 → Bugfix → INS-05) — `Shipment.php` tidak pernah disentuh sprint ini.

---

## Konfirmasi Batas

- ✅ Help box global (INS-05) dihapus total dari `inspect-unit.blade.php`.
- ✅ Tooltip per item checklist tampil, hover/klik, sumber teks 100% dari `config/unit_inspection_templates.php` (tidak hardcode di Blade/PHP).
- ✅ Tooltip sesuai item yang diperiksa — diverifikasi terhadap seluruh item nyata di database, bukan hanya contoh di config.
- ✅ Proses Finalize tidak berubah — `submit()`, validasi PIC/Jabatan/Signature, gate decision, audit log, lock, semua identik dengan sebelum sprint ini.
- ✅ Tidak ada perubahan `UnitInspection`, Guard, Workflow, Approval, atau Database — dibuktikan lewat diff scope, migrate:status, dan regression check.
