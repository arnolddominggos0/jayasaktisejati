# Sprint UNIT-03 — Workflow Correction: Inspection Belongs to Current Stage

**Status:** IMPLEMENTED & tervalidasi end-to-end dengan shipment nyata (progres Pending → Pickup → Handover).
**Tanggal:** 24 Juli 2026

---

## 1. Perbaikan Mapping Status ↔ Inspection Stage

**Root cause:** kode UI (bukan Guard backend) menghitung `inspection_stage` dari `nextTrackStatus()` (status TUJUAN), padahal seharusnya dari `currentTrackStatus()` (status yang SEDANG dijalani). Ini menyebabkan modal Update mewajibkan inspeksi tahap TUJUAN sebelum tahap itu sendiri tercapai — persis keluhan sprint ini (Menunggu→Penjemputan diblokir "Inspeksi Penjemputan").

**Temuan penting (diverifikasi, bukan asumsi):** `Shipment::runTransitionGuards()` — Guard backend sesungguhnya (`ensureHandoverInspectionCleared()`, `ensureLoadingInspectionCleared()`, di `Shipment.php`, **tidak disentuh sprint ini**) — **sudah benar sejak awal**. Guard-guard itu memeriksa `status===Stuffing` untuk mewajibkan inspeksi `handover_depot` (bukan `status===Handover`), dan `status===DeliveryToPort` untuk mewajibkan inspeksi `loading` — pola ini SUDAH "current-stage-required-to-exit", bukan "next-stage-required-to-enter". Bug murni ada di lapisan UI/presentasi (`OperationalTasks.php`, `InspectUnitPage.php`) yang salah menghitung `$stage` untuk ditampilkan/divalidasi di modal — sehingga perbaikan ini **tidak perlu menyentuh `Shipment.php` sama sekali**, sesuai Architecture Rule.

---

## 2. File yang Diubah

| File | Perubahan |
|---|---|
| `app/Filament/FC/Pages/OperationalTasks.php` | Action `inspeksi`: blok fallback ke `nextTrackStatus()` dihapus dari `visible()` — sekarang murni `currentTrackStatus()`. Action `updateTrack`'s `fillForm()`: `$stage` sekarang dihitung dari `currentTrackStatus()` (variabel baru `$currentStatus`), bukan `$nextStatus` — `$nextStatus` tetap dipakai HANYA untuk `track_status` (nilai status yang akan dicatat), terpisah dari `inspection_stage`. |
| `app/Filament/FC/Pages/InspectUnitPage.php` | `resolveStage()`: fallback ke `nextTrackStatus()` dihapus — sekarang murni `currentTrackStatus()`. Docblock lama yang menjelaskan fallback (sudah tidak relevan, menjelaskan kode yang dihapus) turut dihapus. |

**Tidak ada file lain yang diubah.** `Shipment.php`, `UnitInspection.php`, `InspectionDraftAutoCreate.php` (termasuk `resolveStage()`'s mapping table itu sendiri, dan `ensureForTrack()`), observer, notification, `ShipmentResource.php` — **semuanya tidak disentuh**. Tidak ada migration.

---

## 3. Ringkasan Perubahan Teknis

### `OperationalTasks::updateTrack` → `fillForm()`
```php
$nextStatus = $shipment->nextTrackStatus();
$currentStatus = $shipment->currentTrackStatus();
$stage = $currentStatus ? InspectionDraftAutoCreate::resolveStage($currentStatus) : null;
```
`track_status` (status yang akan dicatat) tetap dari `$nextStatus` — tidak berubah. `inspection_stage` (dipakai untuk validasi + pesan di modal) sekarang dari `$currentStatus`. `ensureForShipmentAndStage()` tetap dipanggil (idempotent, tidak berubah perannya) tapi sekarang memastikan draft untuk stage yang BENAR (stage yang sedang berjalan).

### `OperationalTasks` action `inspeksi` → `visible()`
Fallback ke `nextTrackStatus()` dihapus total. Tombol "Inspeksi" sekarang hanya tampil ketika `currentTrackStatus()` benar-benar memetakan ke sebuah stage — tidak lagi muncul secara prematur sebelum status terkait tercapai.

### `InspectUnitPage::resolveStage()`
Disederhanakan dari "coba current, fallback next" menjadi murni `currentTrackStatus()`. Aman dihapus karena SATU-SATUNYA alasan fallback itu ada (modal Update di `OperationalTasks` dulu memakai `nextTrackStatus()`) sudah tidak berlaku setelah perbaikan di atas — kedua caller `InspectUnitPage` (`unit-card.blade.php` dan action `inspeksi`) sekarang konsisten memakai `currentTrackStatus()`, sehingga fallback tidak pernah lagi tersentuh oleh siapa pun.

### `InspectionDraftAutoCreate::resolveStage()` dan `::ensureForTrack()`
**Tidak diubah sama sekali.** Mapping tabel (`Pickup→pickup`, dst.) sudah benar dan tidak perlu berubah — yang salah bukan MAPPING-nya, tapi STATUS MANA yang di-mapping (current vs next). `ensureForTrack()` (dipanggil `ShipmentTrackObserver` setiap track baru dicatat) sudah otomatis benar: begitu status X tercapai, draft inspeksi stage(X) langsung dibuat — persis timing yang dibutuhkan ("draft dibuat saat MASUK tahap, wajib selesai saat KELUAR tahap").

---

## 4. Hasil Pengujian Setiap Transisi Status

Diuji end-to-end terhadap **shipment nyata** yang dibuat dan digerakkan lewat `appendTrack()` sungguhan (bukan simulasi/mock), lalu dibersihkan setelah pengujian:

| Transisi | current | next | `inspection_stage` dihitung | `unitInspectionIncomplete` | Tombol "Inspeksi" |
|---|---|---|---|---|---|
| **Kasus 1** Menunggu → Penjemputan | `null` | `pickup` | `null` | `false` (tidak ada syarat) | **tidak tampil** — benar, belum ada yang bisa diperiksa |
| **Kasus 2** Penjemputan → Handover Depo | `pickup` | `handover` | `pickup` | `true` sebelum inspeksi pickup difinalisasi; **`false` setelah difinalisasi** | **tampil** |
| **Kasus 3** Handover Depo → Loading | `handover` | `stuffing` | `handover_depot` | `true` (belum ada inspeksi handover_depot) | *(pola sama, tidak diulang manual — resolusi stage identik untuk tahap berikutnya)* |

**Bukti transisi nyata berhasil:** setelah inspeksi Pickup unit test difinalisasi (submitted + signed), `$shipment->appendTrack(TrackStatus::Handover, ...)` **benar-benar berhasil tanpa exception** — membuktikan perbaikan ini selaras dengan Guard backend yang sudah ada (`ensureHandoverInspectionCleared()`), bukan bertentangan dengannya.

**`InspectUnitPage::resolveStage()` pasca-perubahan**, diuji langsung pada shipment yang current-nya Handover: mengembalikan `handover_depot` — konsisten dengan hasil `OperationalTasks`.

---

## 5. UX — Warning Tidak Lagi Muncul Prematur

Karena `inspection_notice` (Sprint UNIT-02) memakai kondisi `unitInspectionIncomplete($record, $get('inspection_stage'))`, dan `inspection_stage` sekarang dihitung benar (current, bukan next) — **tidak perlu ada perubahan kode UNIT-02 sama sekali**. Saat status Menunggu, `inspection_stage=null` → `unitInspectionIncomplete` otomatis `false` → kotak "Pemeriksaan Diperlukan" otomatis tidak tampil. Perilaku ini murni konsekuensi dari data yang sekarang benar, bukan perubahan tambahan di `unitTrackUpdateForm()`.

---

## 6. Konfirmasi: Tidak Ada Komentar Baru

Kedua file yang diubah dibaca ulang penuh setelah implementasi:
- `InspectUnitPage.php`: docblock lama yang menjelaskan mekanisme fallback (kode yang dihapus) turut dihapus — **bukan penambahan komentar**, melainkan pembersihan komentar yang sudah tidak akurat karena menjelaskan logic yang sudah tidak ada. Tidak ada komentar baru ditulis untuk menggantikannya.
- `OperationalTasks.php`: tidak ada baris komentar apa pun ditambahkan pada blok yang diubah (`visible()` action `inspeksi`, `fillForm()` action `updateTrack`).
