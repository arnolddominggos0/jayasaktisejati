# Sprint ARCH-02 — Remove Legacy Transition Signals

**Status:** PARTIAL IMPLEMENTATION — Scope 3 diimplementasikan (dengan koreksi premis berbasis bukti). **Scope 1 dan Scope 2 DIHENTIKAN sebelum implementasi**, sesuai instruksi eksplisit sprint ("Jika penghapusan legacy signal akan mengubah domain behaviour secara signifikan, JANGAN lanjutkan").
**Tanggal:** 23 Juli 2026
**Rujukan:** `AUDIT-SHIPMENTTRACK-TRANSITION-GATES.md`, `SPRINT-ARCH-01-CENTRALIZE-TRANSITION-GUARDS.md`, `AUDIT-PLANNING-LOADING-VS-STUFFING-EXECUTION.md`

---

## Ringkasan Eksekutif

Dua dari empat scope implementasi (Scope 1, Scope 2) **tidak dilanjutkan** setelah investigasi menunjukkan keduanya akan menyebabkan **penghentian operasional langsung** untuk seluruh populasi shipment yang sedang berjalan lewat jalur legacy — bukan sekadar "relokasi cek" seperti ARCH-01, tapi benar-benar **mengganti data yang dipakai untuk memutuskan**, padahal data pengganti itu kemungkinan besar kosong untuk shipment yang sedang aktif hari ini. Ini persis kondisi yang diperingatkan sprint ini sendiri di bagian "Important Constraints."

Scope 3 diimplementasikan — TAPI premisnya (dua jalur paralel: Delivery To Customer vs Handover Trucking) **ternyata salah**, dan audit menemukan masalah nyata yang berbeda: keduanya bukan jalur paralel, melainkan **satu jalur berurutan** (`Unloading → HandoverTrucking → DeliveryToCustomer → Delivered`), dan tombol `deliveryToCustomer` selama ini salah digerbangi (bug nyata, sekarang diperbaiki).

---

## Scope 1 — Remove Legacy Container Gate — ⛔ DIHENTIKAN

**Lokasi:** `Shipment::containerAssignmentBlockReason()` (baru diaktifkan di ARCH-01), dipanggil dari `ensureContainerAssigned()` dan `isContainerAssignmentComplete()`.

**Yang diminta:** ganti pembacaan `container_display` dengan `ContainerAllocationService`/`Container.is_ready_for_stuffing`.

**Kenapa dihentikan — bukti konkret, bukan dugaan:**

```
grep "container_display" app/Services/ContainerAllocation/ContainerAllocationService.php
→ No matches found
```

**`ContainerAllocationService` TIDAK PERNAH menyentuh `container_display` — dan sebaliknya, alur Handover Depo (yang menulis `container_display`) TIDAK PERNAH menyentuh `Container`/`Unit.container_id`.** Kedua sistem ini adalah dua sumber data yang **sepenuhnya terpisah dan tidak sinkron** (temuan ini sudah terdokumentasi sejak `AUDIT-PLANNING-LOADING-VS-STUFFING-EXECUTION.md`, dikonfirmasi ulang di sini secara definitif lewat kode, bukan inferensi).

**Dampak kalau tetap dipaksakan:** `ContainerAllocationWorkspace` baru mendapat entry-point PERTAMA KALINYA di OPS-06 (23 Juli 2026, sesi yang sama) — sebelum itu halaman ini **tidak bisa diakses operator sama sekali**. Artinya seluruh shipment yang sudah/sedang melalui Handover Depo sebelum hari ini **hampir pasti tidak punya satu baris pun** di tabel `containers` untuk shipment mereka (karena satu-satunya cara mengisinya, `ContainerAllocationWorkspace`, baru bisa dijangkau operator sekarang). Mengganti gate ke `is_ready_for_stuffing` berarti **seluruh shipment aktif hari ini yang sudah mengisi `container_display` lewat cara lama akan MENDADAK TIDAK BISA lanjut ke Stuffing**, walau secara fisik container mereka sudah benar — bukan perbaikan konsistensi, tapi penghentian operasional mendadak.

**Rekomendasi minimum change (tidak diimplementasikan, perlu keputusan terpisah):** penggantian sumber kebenaran ini membutuhkan strategi migrasi/transisi data (mis. backfill `Container`/`Unit.container_id` dari `container_display` yang sudah ada, atau periode transisi menerima KEDUA sinyal), bukan penggantian gate satu-langkah. Ini di luar cakupan "swap gate" — perlu direncanakan sebagai inisiatif tersendiri dengan keputusan eksplisit Anda tentang cara migrasi data historis.

---

## Scope 2 — Loading Readiness dari `shipmentStuffingSummary()` — ⛔ DIHENTIKAN

**Lokasi:** `unitLoadingAuto`'s `->visible()`, `OperationalTasks.php`.

**Yang diminta:** Loading hanya boleh mulai jika `shipmentStuffingSummary()->state === 'ready_loading'`.

**Kenapa dihentikan:** `shipmentStuffingSummary()` (dari `StuffingService`, ST-01) menghitung state HANYA dari `Container::query()->whereHas('units', fn($q) => $q->where('shipment_id', $shipment->id))` — yaitu container yang terhubung lewat `Unit.container_id`. Karena Scope 1 di atas sudah membuktikan `Unit.container_id` **hampir pasti kosong** untuk seluruh shipment yang belum menyentuh `ContainerAllocationWorkspace` (yaitu, praktis semua shipment aktif hari ini) — `shipmentStuffingSummary()` akan **selalu** mengembalikan `state === 'waiting_stuffing'` untuk mereka, **tidak pernah** `'ready_loading'`, **selamanya**, terlepas seberapa nyata stuffing fisiknya sudah selesai.

**Dampak kalau tetap dipaksakan:** tombol "Dimuat di Kapal" akan **hilang total** untuk semua shipment yang sedang di tahap Stacking hari ini menunggu Loading — operasional pengiriman laut akan berhenti di titik itu tanpa jalan keluar (bahkan `updateTrack` generik pun akan terblokir kalau saya juga memindahkan cek ini ke `appendTrack()`, sesuai pola ARCH-01).

**Rekomendasi minimum change (tidak diimplementasikan):** sama seperti Scope 1 — butuh strategi migrasi/adopsi bertahap terhadap engine terstruktur (Container Allocation + Stuffing) sebelum gate baru ini bisa aman diaktifkan. Alternatif yang lebih ringan (kalau ingin progres tanpa menunggu migrasi penuh): gate bisa dibuat **kondisional** — pakai `shipmentStuffingSummary()` HANYA untuk shipment yang terbukti sudah mulai memakai engine terstruktur (mis. `Container::whereHas('units', ...)` untuk shipment itu tidak kosong), dan fallback ke perilaku lama untuk sisanya. **Saya TIDAK mengimplementasikan ini** karena itu sendiri adalah bentuk "implementasi paralel"/kompleksitas kondisional yang eksplisit dilarang sprint ini ("Jangan membuat implementasi paralel").

---

## Scope 3 — Delivered Transition — ✅ DIIMPLEMENTASIKAN (dengan koreksi premis)

**Premis sprint (seperti tertulis):** ada dua jalur PARALEL dari Unloading — Delivery To Customer dan Handover Trucking/Self Drive — yang perlu disamakan agar sama-sama punya jalan ke Delivered.

**Temuan audit — premis ini TIDAK akurat:** `TrackStatus::orderSea()` menunjukkan keduanya **BUKAN paralel**, melainkan **satu jalur berurutan**:
```
Unloading → HandoverTrucking → DeliveryToCustomer → Delivered
```
(Ini juga sesuai dengan diagram pipeline yang konsisten dipakai di seluruh brief sprint OPS-06/OPS-07/ARCH-01/ARCH-02: "Unloading ↓ Self Drive ↓ Dooring ↓ Delivered" — SATU rantai, bukan cabang.)

**Bug nyata yang ditemukan:** sebelum sprint ini, `deliveryToCustomer` memakai kondisi visible **yang sama persis** dengan `handoverTrucking` (`latest_track_status === Unloading`). Akibatnya:
- Untuk shipment laut: tombol "Antar ke Customer" muncul BERSAMAAN dengan "Handover Selfdrive", tapi mengkliknya akan DITOLAK `guardInvalidStatusTransition()` (lompat 2 langkah) — inilah sebabnya kode `deliveryToCustomer` punya `try/catch` ekstra menangkap `DomainException` (`handoverTrucking` tidak punya) — bekas jejak bahwa kegagalan ini sudah pernah terjadi/diantisipasi.
- Setelah "Handover Selfdrive" berhasil diklik, **KEDUA tombol menghilang** (status sekarang `HandoverTrucking`, tidak cocok kondisi manapun) — operator terjebak, harus memakai modal "Update" generik untuk lanjut.
- Untuk shipment darat (Land): tombol "Antar ke Customer" **tidak pernah muncul sama sekali**, karena `orderLand()` tidak pernah melewati status `Unloading`.

**Perbaikan (satu baris per Action, memakai method domain yang sudah ada):**
```php
// handoverTrucking — perilaku TIDAK berubah (dibuktikan lewat verifikasi index math)
->visible(fn (Shipment $record) => $record->nextTrackStatus() === TrackStatus::HandoverTrucking && ...)

// deliveryToCustomer — DIPERBAIKI
->visible(fn (Shipment $record) => $record->nextTrackStatus() === TrackStatus::DeliveryToCustomer && ...)
```
`Shipment::nextTrackStatus()` (method domain yang SUDAH ADA, dipakai modal "Update Track" generik) menghormati `orderSea()`/`orderLand()` dengan benar — bukan sinyal baru, murni mencocokkan kondisi UI dengan urutan yang sudah didefinisikan di domain.

`markDelivered` **tidak diubah** — gate-nya (`latest_track_status === DeliveryToCustomer`) sudah benar sekarang setelah `deliveryToCustomer` diperbaiki, karena memang hanya ADA satu jalur menuju Delivered.

---

## Scope 4 — Audit Ulang Seluruh `->visible()`/`->disabled()`/`->action()`

Diperiksa seluruh 24 titik di `OperationalTasks.php` (daftar lengkap lewat `grep`). Hasil:

| Kategori | Jumlah | Status |
|---|---|---|
| `latest_track_status`/`nextTrackStatus()` (status sebelumnya) | 12 | ✅ Pure UI, sesuai Scope 4 |
| `ShipmentOwnership::canEdit()` / `auth()->user()?->can()` | Tersebar di hampir semua Action | ✅ Authorization, sesuai Scope 4 |
| `isRackShipment()` | 4 | ✅ Klasifikasi/routing varian tombol (penegakan sesungguhnya sudah di `ensureLoadingSessionCompleted()`, domain layer) |
| `canCancel()` | 1 (`cancel`) | ✅ Sudah domain method bersih sejak awal |
| `isHandoverInspectionCleared()` / `isContainerAssignmentComplete()` | 5 | ✅ Sudah dipindah ke domain layer di ARCH-01 |
| `->disabled()` pada `chassis_no`/`model_no` (modal Handover) | 2 | ✅ Field display read-only, bukan business rule |

**Tidak ditemukan lagi raw SQL, business rule, atau readiness calculation di `->visible()`/`->disabled()`/`->action()` di luar yang sudah dibahas di Scope 1-3.** `OperationalTasks.php` sekarang murni UI layer untuk seluruh gate yang AMAN dipindahkan — dua pengecualian (Scope 1, Scope 2) tetap memakai sinyal lama karena alasan data-migrasi yang dijelaskan di atas, bukan karena belum diaudit.

---

## Scope 5 — Preserve Behaviour

- Urutan `Pickup → ... → Delivered` **tidak berubah** — `guardInvalidStatusTransition()`/`orderSea()`/`orderLand()` tidak disentuh.
- Tidak ada status/enum/tabel/service baru.
- Perbaikan Scope 3 **tidak mengubah transisi yang DIIZINKAN** — hanya memperbaiki KAPAN tombol yang sudah ada muncul, mencocokkan urutan yang sudah berlaku di domain layer sejak awal.
- Scope 1/2 tidak diimplementasikan — nol risiko perubahan perilaku dari situ.

---

## Scope 6 — Legacy Cleanup Audit

| Legacy Signal | Status | Diganti Dengan | Catatan |
|---|---|---|---|
| `waiting_inspection_count`/`bermasalah_count` (raw SQL, gate Stuffing/DeliveryToPort) | ✅ Dihapus dari gate (ARCH-01) | `Shipment::isHandoverInspectionCleared()` | Kolom raw SQL tetap ada untuk TextColumn display (bukan legacy dalam pengertian gate) |
| `unassigned_container_count` sebagai satu-satunya penegakan (guard domain mati) | ✅ Diperbaiki (ARCH-01) | `Shipment::isContainerAssignmentComplete()` (`ensureContainerAssigned()` diaktifkan kembali) | `container_display` **masih** jadi sumber data di baliknya — lihat baris berikutnya |
| `container_display` sebagai **source of truth data** container assignment | ⛔ **BELUM bisa dihapus** | — | Lihat Scope 1: `Container`/`Unit.container_id` belum punya data untuk shipment yang sudah berjalan; butuh strategi migrasi terpisah |
| `latest_track_status` sebagai readiness gate Loading | ⛔ **BELUM bisa diganti** | — | Lihat Scope 2: `shipmentStuffingSummary()` akan selalu `waiting_stuffing` untuk shipment yang belum memakai Container Allocation Workspace |
| `deliveryToCustomer` digerbangi kondisi yang sama dengan `handoverTrucking` (bug, bukan legacy signal per se) | ✅ Diperbaiki (Scope 3) | `Shipment::nextTrackStatus()` | Bukan penggantian legacy→engine, tapi koreksi kondisi UI agar cocok urutan domain yang sudah benar |

**Ringkasan:** dari 4 target sprint ini, **2 berhasil** (sudah selesai di ARCH-01, dikonfirmasi ulang di sini) atau selesai sprint ini (Scope 3), **2 dihentikan** dengan alasan konkret dan rekomendasi jelas (Scope 1, Scope 2) — keduanya membutuhkan keputusan terpisah tentang strategi migrasi data sebelum bisa aman dilanjutkan.

---

## Validasi

| Uji | Hasil |
|---|---|
| `php -l` pada `OperationalTasks.php` | ✅ Bersih (Shipment.php tidak disentuh sprint ini) |
| `composer dump-autoload` | ✅ Sukses |
| Verifikasi index-math `nextTrackStatus()` terhadap `orderSea()`/`orderLand()` (murni logika, tanpa DB) — membuktikan `handoverTrucking` tidak berubah perilaku, `deliveryToCustomer` diperbaiki untuk sea DAN land mode | ✅ Sesuai analisis |
| `OperationalTasks::table()` dibangun penuh, seluruh 24 action terdaftar tanpa error setelah perubahan Scope 3 | ✅ |

---

## Konfirmasi Batas

- ✅ Tidak ada redesign, tidak ada modul baru, tidak ada implementasi paralel.
- ✅ Scope 1 & 2 dihentikan TEPAT sesuai instruksi eksplisit sprint ini sendiri — dilaporkan dengan lokasi, alasan, dampak, dan rekomendasi, bukan diam-diam dilewati.
- ✅ Scope 3 menyimpang dari premis literal brief HANYA setelah bukti konkret (`orderSea()`) menunjukkan premisnya salah — perbaikan yang diterapkan justru MEMPERBAIKI bug nyata, bukan mengabaikan instruksi.
