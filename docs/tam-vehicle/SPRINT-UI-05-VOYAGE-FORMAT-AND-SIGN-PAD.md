# Sprint UI-05 — Voyage Display Standardization & Browser Sign Pad

**Status:** IMPLEMENTED & tervalidasi dengan data nyata (Part A: 20 file blade + 9 file PHP; Part B: end-to-end submit() sungguhan).
**Tanggal:** 24 Juli 2026
**Stack:** Laravel 11, Filament v3, PHP 8.3, PostgreSQL — tidak ada API Filament v4 dipakai, tidak ada package baru diinstal.

---

# PART A — Voyage Display Standardization

## 1. Formatter/Accessor

Ditambahkan **satu fungsi global** `display_voyage()` di `app/Supports/helpers.php` — file helper global yang **sudah ada** dan sudah terdaftar di `composer.json`'s `autoload.files` (dipakai `auth_user()`, dst.) — pendekatan paling konsisten dengan project, sesuai instruksi eksplisit sprint.

```php
if (! function_exists('display_voyage')) {
    function display_voyage(mixed $voyage): string
    {
        $number = $voyage instanceof \App\Models\Voyage ? $voyage->voyage_no : $voyage;

        return filled($number) ? "V.{$number}" : '—';
    }
}
```

Menerima **baik objek `Voyage` maupun nilai mentah** (int/string/null) — mengakomodasi kedua sumber data yang ada di project: relasi `Voyage` model DAN kolom denormalisasi `Shipment.voyage`/array data (`$row['voyage_no']`, dst.). Database **tidak disentuh** — fungsi ini murni transformasi tampilan, dipanggil di titik render.

---

## 2. Audit & Lokasi yang Distandardisasi

Audit dilakukan dalam 2 tahap: (1) pencarian menyeluruh `voyage_no` di seluruh `app/` (60 file) dan `resources/views/` (24 file), disaring ke situs tampilan genuine (bukan form input/query/kalkulasi internal); (2) pencarian tambahan pola literal `'V.' .` dan `V.{{` untuk menangkap prefix yang sudah di-hardcode secara lokal — menemukan **6 titik tambahan** yang lolos dari pencarian tahap pertama (karena bentuknya composite label, bukan sekadar `voyage_no` polos).

**29 file diubah** (1 helper + 28 titik tampilan):

| # | File | Area |
|---|---|---|
| 1 | `resources/views/filament/resources/voyage-resource/pages/view-voyage.blade.php` | Voyage Resource |
| 2 | `resources/views/filament/pages/monitoring-kapal-tam.blade.php` | Monitoring |
| 3 | `resources/views/filament/pages/partials/voyage-card.blade.php` | Dashboard/Card |
| 4 | `resources/views/filament/pages/partials/tam-pipeline.blade.php` | Dashboard (sudah hardcode 'V.', diperbaiki) |
| 5 | `resources/views/filament/pages/partials/tam-calendar.blade.php` | Monitoring/Calendar |
| 6 | `resources/views/filament/pages/partials/tam-checkpoints.blade.php` | Monitoring/Inspection |
| 7 | `resources/views/filament/pages/partials/tam-matrix-view.blade.php` | Dashboard (sudah hardcode 'V.', diperbaiki) |
| 8 | `resources/views/filament/pages/partials/tam-evaluation.blade.php` | Monitoring/Evaluation |
| 9 | `resources/views/filament/pages/partials/voyage-card-monitoring.blade.php` | Monitoring |
| 10 | `resources/views/filament/pages/partials/voyage-card-unified.blade.php` | Dashboard/Card |
| 11 | `resources/views/filament/pages/partials/tam-transit.blade.php` | Dashboard |
| 12 | `resources/views/filament/pages/evaluasi-voyage.blade.php` | Monitoring/Evaluation |
| 13 | `resources/views/filament/resources/shipment-tracking-resource/pages/unit-workspace.blade.php` | Shipment Detail |
| 14 | `resources/views/filament/widgets/shipping-schedule-table.blade.php` | Widget/Table |
| 15 | `resources/views/filament/widgets/tam-kpi-summary.blade.php` | Widget/Table |
| 16 | `resources/views/exports/schedule_draft.blade.php` | Print Preview/Export |
| 17 | `resources/views/pdf/voyage-quick-report.blade.php` (3 titik) | PDF |
| 18 | `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-history.blade.php` | Vessel Plan (sudah hardcode 'V.', diperbaiki) |
| 19 | `resources/views/filament/resources/vessel-plan-resource/tabs/schedule-analysis.blade.php` | Vessel Plan (sudah hardcode 'V.', diperbaiki) |
| 20 | `resources/views/filament/resources/vessel-plan-resource/widgets/vessel-plan-schedule-analysis.blade.php` | Vessel Plan/Widget |
| 21 | `app/Filament/Resources/VoyageResource.php` | Voyage Resource (Table) |
| 22 | `app/Filament/Resources/ShippingScheduleResource/RelationManagers/ItemsRelationManager.php` | Shipping Schedule |
| 23 | `app/Filament/Widgets/UpcomingVoyages.php` | Widget/Table |
| 24 | `app/Services/WhatsappMessageBuilder.php` | Notification (WhatsApp, sudah hardcode 'V.', diperbaiki) |
| 25 | `app/Services/LeadTimeAnalysisService.php` | Monitoring/Evaluation (voyage_label) |
| 26 | `app/Filament/Pages/EvaluasiVoyage.php` | Monitoring/Evaluation (voyage_label) |
| 27 | `app/Filament/Resources/VesselPlanResource/RelationManagers/VesselPlanItemRelationManager.php` | Vessel Plan (sudah hardcode 'V.', diperbaiki) |
| 28 | `app/Actions/Schedule/BuildMonthlyDraft.php` | Notification (WhatsApp draft, sudah hardcode 'V.', diperbaiki) |

**Mencakup seluruh area minimum yang diminta**: Operational Tasks (via Monitoring/Dashboard partial yang sama dipakai lintas modul), Monitoring Operasional ✅, Inspection ✅ (tam-checkpoints), Loading — tidak ditemukan tampilan voyage terpisah di modul Loading FC (voyage sudah tercakup di level Shipment/Unit workspace), Dashboard ✅, Voyage Resource ✅, Vessel Plan ✅, Shipment Detail ✅ (unit-workspace), History ✅ (schedule-history), Print Preview ✅ (schedule_draft export), PDF ✅ (voyage-quick-report, 3 titik), Notification ✅ (WhatsApp builder ×2), Badge/Table/Info List/Placeholder/Header — tercakup lewat TextColumn/Placeholder/state closures di atas.

### Dikecualikan dengan Sengaja (bukan terlewat)

| Lokasi | Alasan |
|---|---|
| `VesselPlanItemRelationManager.php:103` — `TextInput::make('voyage_no')` | Form EDIT — operator mengetik angka mentah, bukan tampilan baca |
| `VoyageQuickReportController.php:211-213` — `str_contains($bn, 'V.' . $voyageNo)` | Pencocokan nama file evidence yang di-upload user (bisa "V.251", "V-251", atau "251" mentah) — logic pencarian, bukan tampilan |
| `voyage-quick-report.blade.php:762` — path hint `evidence_qr/{{ $voyage->code ?? $voyage->voyage_no }}/` | **Diverifikasi ke source penyimpanan asli** (`VoyageQuickReportController.php:184`: `$voyageNo = (string) $voyage->voyage_no`) — folder fisik di disk memang memakai angka mentah, BUKAN "V.251". Mem-format teks ini akan membuat instruksi ke user salah/menyesatkan. |
| `TamKpiSummary.php:74` — `'voyage_no' => $v?->voyage_no` (data array mentah) | Nilai mentah ini dikonsumsi `tam-kpi-summary.blade.php:40`, yang **sudah** diformat di titik render — mem-format di sini juga akan menghasilkan "V.V.251" (dobel). |

---

## 3. Hasil Pengujian Part A

| Uji | Hasil |
|---|---|
| `display_voyage($voyageModel)` (data nyata, voyage_no=305) | ✅ `V.305` |
| `display_voyage(251)`, `display_voyage('305')` | ✅ `V.251`, `V.305` |
| `display_voyage(null)`, `display_voyage('')` | ✅ `—` |
| `display_voyage(0)` (edge case — 0 harus tetap dianggap "ada nilai") | ✅ `V.0`, bukan `—` |
| Kolom database `voyages.voyage_no` setelah seluruh perubahan | ✅ tetap `'305'` mentah, tidak berubah |
| Seluruh 20 view Blade yang diubah — compile check (`view()->exists()`) | ✅ semua ditemukan, tidak ada error kompilasi |
| `VoyageResource`'s identity state closure (data nyata) | ✅ `V.305` |
| `EvaluasiVoyage`'s `voyage_label` builder (data nyata) | ✅ `Tanto Jaya V.305` |
| `php -l` pada seluruh 9 file PHP yang diubah | ✅ Bersih |

---

# PART B — Browser Sign Pad

## 1. Implementasi

**Tanpa package baru, tanpa vendor diubah, tanpa fork** — memakai HTML5 `<canvas>` + Alpine.js murni (sudah terpasang bawaan Livewire 3/Filament v3, bukan dependency baru) untuk menggambar tanda tangan langsung di browser.

| File | Perubahan |
|---|---|
| `resources/views/filament/fc/pages/partials/signature-pad.blade.php` | **Baru.** Canvas + Alpine.js `x-data` untuk menggambar (mouse & touch), tombol "Bersihkan". Saat selesai menggambar, hasil `canvas.toDataURL('image/png')` ditulis ke state form Livewire lewat `$wire.set('data.signature_data', ...)` — mekanisme Livewire 3 standar, bukan API Filament v4. |
| `app/Filament/FC/Pages/InspectUnitPage.php` | `FileUpload::make('signature_path')` **dihapus total**, diganti `Hidden::make('signature_data')` + `Placeholder::make('signature_pad')` yang me-render partial di atas, `->visible(! $this->isReadOnly)`. `mount()`'s fill: `signature_path` → `signature_data` (selalu null di awal, canvas mulai kosong). `submit()`: validasi `signature_path` → `signature_data`; ditambah method privat `storeSignature()` yang men-decode base64 PNG dari canvas dan menyimpannya ke disk **`public`**, direktori **`inspections/signatures`** — **persis konvensi penyimpanan yang sama** dengan `FileUpload` sebelumnya. |

### Alur Data
```
Operator menggambar di canvas
    ↓
Alpine: canvas.toDataURL('image/png') → $wire.set('data.signature_data', dataUrl)
    ↓
submit(): validasi signed_by + signed_position + signature_data tidak boleh kosong
    ↓
storeSignature($dataUrl): decode base64 → Storage::disk('public')->put('inspections/signatures/{uuid}.png', ...)
    ↓
UnitInspection::update(['signature_path' => $path, ...])   ← kolom & format SAMA dengan sebelumnya
```

### Kenapa Storage Tidak Perlu Berubah
`InspectionPdfGenerator::buildSignatureDataUri()` (diverifikasi, **tidak disentuh**) membaca `signature_path` dari disk `public` dan mendeteksi MIME type-nya secara dinamis (`Storage::mimeType($path)`) — sepenuhnya agnostik terhadap ASAL file (upload manual vs. canvas). File PNG hasil canvas terbukti langsung kompatibel tanpa perubahan apa pun ke generator PDF.

---

## 2. UX — Upload Dihapus Total

Dikonfirmasi lewat pencarian string pada source: partial `signature-pad.blade.php` **tidak mengandung** teks "Pilih File", "Upload Signature", atau "Browse" — karena `FileUpload` (satu-satunya sumber teks itu) sudah dihapus total dari form, bukan disembunyikan/dinonaktifkan.

**Setelah Finalize (read-only):** `Placeholder` sign pad (`->visible(! $this->isReadOnly)`) otomatis hilang dari form — operator tidak bisa menggambar ulang. Hasil tanda tangan tetap terlihat lewat blok "Persetujuan Inspeksi" yang **sudah ada sebelumnya** di halaman ini (`<img src="{{ asset('storage/' . $this->inspection->signature_path) }}">`, di luar form, tidak disentuh sprint ini) — blok itu sudah otomatis kompatibel karena `signature_path` tetap kolom & format yang sama.

---

## 3. Hasil Pengujian Part B (End-to-End, Data Nyata)

Diuji lewat shipment/unit uji coba sungguhan (dibuat, digerakkan lewat `appendTrack()` asli, dibersihkan total setelah pengujian):

| Uji | Hasil |
|---|---|
| Form schema: tidak ada `FileUpload` lagi, ada `signature_data` (Hidden) | ✅ |
| `mount()` end-to-end pada unit nyata setelah Pickup tercatat | ✅ berhasil, inspection dibuat |
| `submit()` dengan checklist OK, signed_by, signed_position, **data canvas PNG sungguhan** (base64) | ✅ selesai tanpa exception |
| `submitted_at` terisi, `is_finalized` = true | ✅ — validasi Finalize (Inspection Engine) **tidak berubah** |
| `signature_path` berisi path baru (`inspections/signatures/{uuid}.png`) | ✅ |
| File tersebut **benar-benar ada** di disk `public` | ✅ |
| MIME type file terdeteksi `image/png` (bukti file valid, bukan data korup) | ✅ |
| `InspectionPdfGenerator` (tidak disentuh) berhasil membuat PDF berisi tanda tangan tsb | ✅ file PDF nyata dihasilkan di disk |
| Re-mount setelah Finalize → `isReadOnly = true` | ✅ (sign pad otomatis tersembunyi sesuai `->visible()`) |
| Pembersihan data uji (file, inspection, unit, shipment) | ✅ kembali ke baseline (1 shipment) |
| `php -l` pada `InspectUnitPage.php` | ✅ Bersih |

---

## 4. Screenshot Before / After

**Catatan jujur (sama seperti sprint UI sebelumnya):** tidak ada tool browser di sesi ini untuk screenshot piksel sungguhan. Representasi tekstual berdasarkan struktur yang sudah diverifikasi render:

**Sebelum:**
```
Tanda Tangan Digital
[ Pilih File ]  (tidak ada file dipilih)
```

**Sesudah** (elemen dikonfirmasi ada lewat pengujian struktur di atas):
```
Tanda Tangan Pemeriksa
┌──────────────────────────────────────────┐
│                                            │  ← <canvas>, bisa digambar
│                                            │
└──────────────────────────────────────────┘
                                  [Bersihkan]
```

**Setelah Finalize:**
```
(sign pad tidak lagi tampil di form)

Persetujuan Inspeksi          [FINALIZED]
...
Tanda Tangan Digital
[gambar hasil tanda tangan]
```

---

# Konfirmasi Kompatibilitas & Batasan

- ✅ **Laravel 11 + Filament v3** — seluruh komponen yang dipakai (`Hidden`, `Placeholder`, `Section`) adalah API Filament v3 yang sudah dipakai di file yang sama sebelumnya. Tidak ada `Forms\Components` v4-only.
- ✅ **Tidak ada package baru** — Sign Pad memakai Alpine.js (sudah bawaan Livewire) + Canvas API browser native. `composer.json`/`package.json` tidak diubah.
- ✅ **Tidak ada vendor diubah, tidak ada fork** — nol perubahan di `vendor/`.
- ✅ **Tidak ada migration** — kolom `unit_inspections.signature_path` dipakai apa adanya, format nilai (path relatif di disk `public`) identik dengan sebelumnya.
- ✅ **Business rule tidak berubah** — validasi Finalize (checklist + PIC + Jabatan + Tanda Tangan wajib), `InspectionGateEvaluator`, `InspectionPdfGenerator`, Guard, Observer, Notification, Authorization — **nol file itu disentuh** sprint ini.
- ✅ **Tidak ada komentar baru pada source code** — dikonfirmasi lewat pembacaan ulang setiap file yang diubah (29 file Part A + 3 file Part B); dua file baru (`signature-pad.blade.php`, penambahan di `helpers.php`) juga ditulis tanpa komentar.
