# Sprint UNIT-04 — Complete Unit-Centric Inspection Navigation

**Status:** IMPLEMENTED & tervalidasi lewat render Livewire sungguhan terhadap data nyata.
**Tanggal:** 24 Juli 2026

---

## 1. Perbaikan Navigasi Inspection Menjadi Unit-Centric

| Elemen | Sebelum | Sesudah |
|---|---|---|
| Breadcrumb | Tugas Operasional › **Detail Pengiriman** › Inspeksi: {stage} — {chassis} | Tugas Operasional › **Inspeksi Unit** › **{chassis}** |
| Judul halaman | `Inspeksi: {stage} — {chassis}` | **Inspeksi Unit** |
| Subheading | *(tidak ada)* | **{chassis} · {stage_label}** — mis. `MHFA71BY6T0013594 · Pickup (PDC Asal)` |
| Tombol kembali | **Kembali ke Shipment** → `operational-shipments/{id}` | **Kembali ke Tugas Operasional** → `operational-tasks` |
| Redirect setelah Finalize | `OperationalShipmentPage` (Workspace Shipment) | `OperationalTasks` (Tugas Operasional) |
| Lokasi view | `resources/views/filament/fc/**resources/shipment-resource**/pages/inspect-unit.blade.php` | `resources/views/filament/fc/**pages**/inspect-unit.blade.php` |
| Informasi Shipment | Tidak ada di halaman ini sama sekali (hanya lewat "Detail Pengiriman") | Strip ringkas non-mencolok di atas halaman (Shipment/Customer/Voyage/Route) — informasi pendukung, bukan pusat halaman |

---

## 2. File yang Diubah

| File | Perubahan |
|---|---|
| `app/Filament/FC/Pages/InspectUnitPage.php` | `$view` diarahkan ke path baru. `getBreadcrumbs()`, `getTitle()` diubah total. `getSubheading()` ditambahkan (method baru). Action `back`: label + `url()` diubah. `submit()`'s redirect diubah ke `OperationalTasks::getUrl()`. |
| `resources/views/filament/fc/pages/inspect-unit.blade.php` | **Baru** (dipindah dari lokasi lama). Ditambah satu blok info Shipment ringkas di paling atas. Seluruh konten lain **disalin persis** dari file lama (form inspeksi, approval, PDF download — tidak ada yang diubah selain penambahan itu). |
| `resources/views/filament/fc/resources/shipment-resource/pages/inspect-unit.blade.php` | **Dihapus** — sudah dipindah, direktori lama sekarang kosong. |

**Tidak ada file lain yang diubah.** `OperationalShipmentPage.php`, `OperationalTasks.php`, `UnitInspection.php`, guard/transition, Observer, Notification — semuanya tidak disentuh (dikonfirmasi lewat `git status`, hanya `InspectUnitPage.php` + file blade yang berubah sprint ini). Tidak ada migration.

---

## 3. Ringkasan Perubahan Teknis

### Breadcrumb & Judul
`getBreadcrumbs()` tidak lagi menyisipkan `OperationalShipmentPage::getUrl()` sebagai crumb tengah — diganti dua crumb non-tautan (`'Inspeksi Unit'` dan chassis unit), memakai pola yang SAMA yang sudah ada di file ini (`'#'` sebagai key non-tautan, sudah dipakai untuk crumb terakhir sebelumnya). `getTitle()` disederhanakan jadi string tetap `'Inspeksi Unit'` (tidak lagi menyisipkan stage/chassis ke judul); informasi itu dipindah ke `getSubheading()` (method Filament resmi untuk baris kedua di header halaman) — fokus utama tetap "Inspeksi Unit", konteks unit/tahap tetap terlihat tanpa scroll.

### Navigasi Kembali
Tombol `back` dan redirect pasca-Finalize sama-sama diarahkan ke `OperationalTasks::getUrl()` — halaman yang SAMA dari mana user memulai (Sprint OPS-09 sudah menjadikan Operational Tasks sepenuhnya berbasis Unit), sehingga "Kembali ke Tugas Operasional" secara efektif juga berarti "kembali ke daftar Unit."

### Pemindahan Lokasi View
View sebelumnya hidup di path `.../shipment-resource/pages/...` — nama path itu sendiri menyiratkan halaman ini "milik" `ShipmentResource`, padahal `InspectUnitPage` adalah `Page` mandiri (bukan bagian dari Resource manapun). Dipindah ke `filament.fc.pages.inspect-unit`, pola yang **sama persis** dengan halaman FC mandiri lain (`filament.fc.pages.operational-tasks`, `filament.fc.pages.stuffing-workspace`). Dikonfirmasi lewat grep sebelum memindah: path lama **hanya** dirujuk oleh baris `$view` di `InspectUnitPage.php` sendiri (plus 3 dokumen historis yang tidak diubah) — memindahkannya nol risiko terhadap bagian lain sistem.

### Informasi Shipment (Supporting, Bukan Pusat Halaman)
Ditambahkan satu baris ringkas di paling atas Blade — teks kecil abu-abu (`text-xs text-gray-500`), BUKAN section/heading besar seperti "Informasi Unit" di bawahnya — menampilkan kode Shipment, Customer, Voyage, dan Route (`$this->record->route_summary`, field yang sudah ada/dihitung otomatis oleh `Shipment`, tidak perlu logic baru). Ini secara sengaja jauh lebih kecil secara visual dibanding section "Informasi Unit" agar Shipment tetap terasa sebagai info pendukung, bukan pusat halaman.

---

## 4. Screenshot Before / After

**Catatan jujur (sama seperti sprint UI sebelumnya):** tidak ada tool browser di sesi ini untuk screenshot piksel sungguhan. Sebagai gantinya, berikut hasil render **sungguhan** (`Livewire::test()`, bukan simulasi) terhadap unit nyata (`MHFA71BY6T0013594`, shipment `JSS0726SH0001`):

**Sebelum** (representasi struktur, berdasarkan kode yang sudah dihapus):
```
Tugas Operasional › Detail Pengiriman › Inspeksi: Pickup (PDC Asal) — MHFA71BY6T0013594

                                                    ← Kembali ke Shipment
[Informasi Unit ...]
```

**Sesudah** (dikonfirmasi via render HTML sungguhan):
```
Tugas Operasional › Inspeksi Unit › MHFA71BY6T0013594

Inspeksi Unit
MHFA71BY6T0013594 · Pickup (PDC Asal)

Shipment: JSS0726SH0001   Customer: Toyota Astra Motor   Voyage: —   Route: ...

                                                    ← Kembali ke Tugas Operasional
[Informasi Unit ...]
```
Diverifikasi lewat pencarian string pada HTML hasil render sungguhan: `"Detail Pengiriman"` → tidak ditemukan, `"Kembali ke Shipment"` → tidak ditemukan, `"Workspace FC"` → tidak ditemukan; `"Inspeksi Unit"`, chassis, kode shipment (sebagai info pendukung), dan `"Kembali ke Tugas Operasional"` → semuanya ditemukan.

---

## 5. Audit Routing (Scope: Routing Review)

Route saat ini: `operational-inspections/{record}/{unit}`, `mount(Shipment $record, int|string $unit)` — `{record}` masih mengikat ke `Shipment` (dipakai untuk `ShipmentOwnership::canView/canEdit()` dan `currentTrackStatus()` demi resolusi stage, Sprint UNIT-03).

**Dievaluasi, TIDAK diubah sprint ini** — alasan:
1. **Tidak terlihat oleh operator sama sekali.** Seluruh Acceptance Criteria bersifat visual (breadcrumb/judul/tombol/istilah) — struktur URL internal tidak pernah muncul di UI FC manapun. Mengubahnya tidak menambah kepatuhan terhadap kriteria yang diminta.
2. **Menyentuh area yang dilarang diubah.** Restrukturisasi ke `{unit}`-root akan mengubah `mount()` — tempat `abort_unless(auth()->user()?->can('view', $this->record), 403)` (Authorization) berada. Coding Rules eksplisit: "Jangan mengubah Authorization." Mekanisme SAAT INI (deriving lewat Shipment yang sudah diverifikasi memiliki unit tsb — baris `abort_if($this->inspectedUnit->shipment_id !== $this->record->getKey(), 403, ...)`) tetap dipertahankan utuh.
3. **Brief sendiri menyediakan jalan keluar eksplisit** untuk kasus ini: *"Apabila perubahan routing terlalu besar untuk sprint ini, minimal pastikan seluruh UX dan navigasi sudah tidak lagi menampilkan konsep Shipment"* — sudah terpenuhi penuh (§1, §4).

**Rekomendasi untuk sprint mendatang (bila memang diinginkan):** ubah route menjadi `operational-inspections/{unit}`, `mount(Unit $unit)`, derive `$shipment = $unit->shipment` di baris pertama. Perubahan mekanis, dampak terbatas ke 1 caller (`OperationalTasks`'s action `inspeksi`) — tapi tetap perubahan Authorization-adjacent, sebaiknya jadi sprint tersendiri dengan restart eksplisit dari user, bukan disisipkan diam-diam di sprint UX ini.

---

## 6. Audit Resource (Scope: Resource Review — `OperationalShipmentPage`)

**Pertanyaan brief:** apakah `OperationalShipmentPage` sekarang hanya menjadi media membuka Inspection Unit?

**Jawaban, berdasarkan bukti (grep menyeluruh terhadap seluruh `app/`), bukan asumsi: TIDAK.** `OperationalShipmentPage` masih dipakai aktif oleh:
- `OperationalTasks`'s action **"Lihat Detail"** (`viewDetail`) — tujuan utamanya, tidak berkaitan dengan Inspection sama sekali.
- **4 titik redirect-setelah-aksi** di `OperationalTasks.php` (`startPickup`, `handover`, dan lainnya) — mengarahkan FC ke sana setelah mencatat suatu transisi status, untuk melihat ringkasan/timeline shipment.
- `LoadingSessionResource.php` — link kembali ke workspace shipment dari konteks Loading Session.

**Kesimpulan:** halaman ini tetap punya peran administratif/ringkasan yang sah dan aktif dipakai fitur lain — **bukan** sekadar jalur ke Inspection. Sesuai instruksi eksplisit *"Jangan melakukan penghapusan besar apabila masih digunakan oleh fitur lain"* — **tidak dihapus, tidak direstrukturisasi, tidak diubah sama sekali** sprint ini. Perannya sebagai "Workspace administratif Shipment" (bukan lagi bagian dari alur Inspection FC, sejak sprint ini) sudah konsisten dengan Architecture Decision: *"Shipment hanya menjadi konteks informasi, relasi data, dokumen administrasi."*

---

## 7. Penjelasan Keputusan UX

- **Breadcrumb 2 level non-tautan setelah "Tugas Operasional"** (bukan 1 level "Inspeksi Unit — {chassis}" digabung) — dipilih agar chassis tetap terbaca sebagai identitas unit yang jelas di ujung breadcrumb, konsisten dengan pola penamaan tabel Operational Tasks (OPS-09) yang juga menonjolkan chassis sebagai identitas utama baris.
- **Subheading, bukan menyisipkan stage ke judul** — Filament punya mekanisme resmi (`getSubheading()`) persis untuk kebutuhan "judul utama + konteks sekunder"; memakainya lebih sesuai konvensi Filament v3 dibanding menggabungkan semua ke satu string judul panjang seperti sebelumnya.
- **Info Shipment sebagai strip teks kecil di atas, bukan section/card** — perbedaan bobot visual (ukuran font, warna abu-abu, tanpa border/heading) secara eksplisit menegaskan Shipment sebagai info pendukung, sesuai instruksi "Shipment tidak boleh menjadi pusat halaman" — kontras jelas dengan section "Informasi Unit" di bawahnya yang tetap jadi konten utama.
- **`OperationalShipmentPage` dan routing `{record}` dipertahankan** — bukan karena "malas mengerjakan", tapi karena bukti (§5, §6) menunjukkan keduanya masih diperlukan/berisiko bila diubah tanpa perlu, dan brief sendiri mengizinkan pendekatan ini secara eksplisit.

---

## 8. Konfirmasi: Business Rule Tidak Berubah

`mount()`'s authorization check, `resolveStage()` (Sprint UNIT-03, tidak disentuh lagi), `form()`, `submit()`'s validasi Finalize (PIC/Jabatan/Signature), `InspectionGateEvaluator`, `InspectionPdfGenerator`, `getHeaderActions()`'s `resetForReinspection` — **seluruhnya identik**, tidak ada satu baris logic bisnis yang berubah. Satu-satunya baris di `submit()` yang berubah adalah TARGET REDIRECT (`OperationalTasks::getUrl()` alih-alih `OperationalShipmentPage::getUrl()`) — murni navigasi, dieksekusi SETELAH seluruh proses Finalize (validasi, simpan, PDF, notifikasi) selesai dan tidak mengubah apa pun tentang proses itu sendiri.

Diverifikasi lewat render Livewire sungguhan: `mount()` tetap berhasil resolve ke inspection yang sama (`inspection #6, stage=pickup`) dengan data nyata, tanpa exception.

---

## 9. Konfirmasi: Tidak Ada Komentar Baru

- `app/Filament/FC/Pages/InspectUnitPage.php`: seluruh baris yang diedit (view path, breadcrumb, title, subheading baru, back button, redirect) ditulis tanpa satu komentar pun. Satu komentar pra-existing (`// {record} in route → Livewire binds Shipment model...`, baris 35) **tidak disentuh** — masih akurat karena route binding-nya memang tidak berubah (§5).
- `resources/views/filament/fc/pages/inspect-unit.blade.php`: blok baru (strip info Shipment) ditulis tanpa komentar. Seluruh komentar `{{-- --}}` lain di file ini **tersalin persis** dari file lama — bukan tambahan sprint ini.
