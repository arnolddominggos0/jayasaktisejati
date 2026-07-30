# Architecture Audit — ShipmentTrack Transition Gates

**Status:** AUDIT ONLY — tidak ada kode yang diubah.
**Tanggal:** 23 Juli 2026
**Rujukan:** `SPRINT-OPS-06-WIRE-PLANNING-LOADING.md`, `AUDIT-OPS-07-LOADING-ARCHITECTURE.md`

---

## Catatan Metodologi Penting — sebelum tabel

Setiap transisi punya **DUA lapis gate yang independen**, dan ini sendiri adalah temuan arsitektural:

1. **Lapis UI (shortcut button)** — setiap transisi (Handover, Stuffing, dst.) punya `Action::make()` sendiri di `OperationalTasks.php` dengan `->visible()` masing-masing. Inilah yang diaudit di tabel bawah.
2. **Lapis Model (`Shipment::appendTrack()`)** — SEMUA transisi, dari jalur mana pun, akhirnya lewat `appendTrack()`, yang punya guard sendiri: `guardInvalidStatusTransition()` (urutan status valid) + `ensureLoadingSessionCompleted()` (khusus rack, lihat Audit OPS-07).
3. **Ada JALUR KETIGA yang perlu disadari:** `Action::make('updateTrack')` (baris 582) adalah modal **generik** yang bisa memicu transisi APAPUN lewat `$record->nextTrackStatus()`, dengan `->action()` yang **TIDAK mereplikasi** sebagian besar cek UI-level dari shortcut button (mis. tidak mengecek `waiting_inspection_count`/`bermasalah_count`/`unassigned_container_count` sama sekali) — hanya mengandalkan guard di `appendTrack()` itu sendiri. **Artinya: gate UI-level pada tabel di bawah bisa DILEWATI kalau operator memakai tombol "Update" generik alih-alih shortcut spesifik.** Ini relevan untuk penilaian risiko di §4.

---

## Tabel Audit

| # | Transition | Action (file:line) | Current Gate Membaca | Source of Truth | Classification |
|---|---|---|---|---|---|
| 1 | Pickup | `startPickup`, `OperationalTasks.php:797` | `Shipment.status === 'pending'` | `Shipment.status` (ShipmentStatus enum) | A — Track/Status Progress Manual |
| 2 | Handover Depo | `handover`, `:826` | `latest_track_status === Pickup` | `ShipmentTrack` (latest) | A |
| 3 | Planning Loading | `planningLoading`, `:995` (OPS-06) | `latest_track_status === Handover` + `unassigned_container_count > 0` | `unassigned_container_count` = raw SQL `container_display IS NULL` | C — Legacy Signal (by design, per OPS-06 Scope 5) |
| 4 | Stuffing & Segel | `stuffing`, `:997` | `latest_track_status===Handover` + `waiting_inspection_count===0` + `bermasalah_count===0` + (`!isRackShipment` → `unassigned_container_count===0`) | Campuran: `waiting_inspection_count`/`bermasalah_count` dari `unit_inspections` (Domain Engine) + `unassigned_container_count` dari `container_display` (Legacy) | **B+C (campuran)** — TIDAK membaca `Container.is_ready_for_stuffing`/`ContainerAllocationService`/`StuffingService` sama sekali |
| 5 | Delivery to Port | `deliveryToPort`, `:1090` | `latest_track_status===Stuffing` ATAU (`===Handover` + rack + inspection counts) | `ShipmentTrack` (+ inspection counts utk cabang rack) | A (jalur utama), B (jalur rack) |
| 6 | Stacking | `stacking`, `:1122` | `latest_track_status===DeliveryToPort` | `ShipmentTrack` | A |
| 7 | Unit Loading | `unitLoadingAuto`/`unitLoadingInfo`, `:1137`/`:1158` | `latest_track_status===Stacking` + `!isRackShipment` (auto) / `isRackShipment` (info) | `ShipmentTrack` saja untuk regular; `LoadingSession.status` (via `ensureLoadingSessionCompleted()` di **model**, bukan di UI gate) untuk rack | **A untuk regular** (dikonfirmasi Audit OPS-07 — TIDAK membaca `shipmentStuffingSummary()`); **B untuk rack** (enforced di model layer) |
| 8 | On Ship | `onShip`, `:1176` | `latest_track_status===UnitLoading` | `ShipmentTrack` | A |
| 9 | Vessel Departure | `vesselDepart`, `:1191` | `latest_track_status===OnShip` | `ShipmentTrack` | A |
| 10 | Vessel Arrival | `vesselArrival`, `:1206` | `latest_track_status===VesselDepart` | `ShipmentTrack` | A |
| 11 | Unloading | `unloading`, `:1221` | `latest_track_status===VesselArrival` | `ShipmentTrack` | A |
| 12 | Self Drive (Handover Selfdrive) | `handoverTrucking`, `:1240` | `latest_track_status===Unloading` | `ShipmentTrack` | A |
| 13 | Dooring (Antar ke Customer) | `deliveryToCustomer`, `:1255` | `latest_track_status===Unloading` | `ShipmentTrack` | A |
| 14 | Delivered | `markDelivered`, `:1274` | `latest_track_status===DeliveryToCustomer` **SAJA** | `ShipmentTrack` | A — **lihat catatan di §Analisis, transisi #12/#13/#14** |

---

## Analisis per Kolom "Status"

### ✅ Sudah konsisten dengan arsitektur (tidak perlu dipertimbangkan ulang)

**#1, #2, #6, #8, #9, #10, #11, #12, #13** — Transisi Track sekuensial murni (Pickup s/d Delivered, kecuali yang dicatat di bawah). Klasifikasi A (Track Progress Manual) **BENAR di sini** — bukan kekurangan. Transisi-transisi ini SECARA DOMAIN memang tidak punya "readiness engine" tersendiri untuk dibaca; satu-satunya syarat sahnya memang "status sebelumnya sudah tercapai." Memaksakan derived-state di sini justru akan menciptakan gate yang tidak berarti.

### ⚠ Perlu dipertimbangkan

**#3 — Planning Loading.** Legacy signal (`container_display`) dipakai SECARA SADAR sesuai keputusan eksplisit OPS-06 Scope 5 ("Jangan migrasi source of truth"). Bukan gap baru — sudah didokumentasikan dan disetujui sebelumnya. Dicantumkan di sini untuk kelengkapan, bukan temuan baru.

**#4 — Stuffing & Segel.** Ini gate paling tidak konsisten di seluruh pipeline: mencampur sinyal Domain Engine (inspection counts) dengan sinyal Legacy (`container_display`), DAN **sama sekali tidak membaca mesin terstruktur yang sudah dibangun** (`ContainerAllocationService`/`Container.is_ready_for_stuffing`) — padahal mesin itu SUDAH ADA sejak CA-01.5 dan SUDAH disambungkan lewat entry-point terpisah di OPS-06 (`ContainerAllocationWorkspace`'s tombol "Mulai Stuffing"). Akibatnya ADA DUA jalur berbeda menuju status `Stuffing`, dengan syarat kelayakan yang BERBEDA:
  - Jalur lama: tombol "Stuffing & Segel" di `OperationalTasks.php` — syarat: `container_display` terisi semua.
  - Jalur baru (OPS-06): `StuffingWorkspace` — syarat: `Container.is_ready_for_stuffing` per container, ditegakkan oleh `StuffingService::guardUnitReadyToStuff()`.

  Kedua jalur SAMA-SAMA berujung ke `appendTrack(TrackStatus::Stuffing)`, tapi lewat pintu syarat yang berbeda. Ini persis pola yang sudah diidentifikasi di `AUDIT-PLANNING-LOADING-VS-STUFFING-EXECUTION.md` (dua implementasi paralel, belum disatukan) — **belum diselesaikan**, hanya diberi entry-point tambahan di OPS-06, bukan disatukan sumber kebenarannya.

**#7 — Unit Loading (shipment regular).** Sudah teridentifikasi di Audit OPS-07: gate hanya membaca `latest_track_status===Stacking`, tidak pernah membaca `StuffingService::shipmentStuffingSummary()` yang sudah punya derived state `ready_loading`. **Ini SATU-SATUNYA titik di pipeline pasca-Stuffing di mana mesin yang sudah ada (ST-01) benar-benar tersedia tapi belum dipakai gate-nya** — persis pola yang diminta diperiksa di sprint ini.

**#14 — Delivered, dikombinasikan dengan #12/#13.** Temuan struktural (bukan soal source-of-truth, tapi soal kelengkapan gate): dari `Unloading`, ada DUA cabang paralel — `handoverTrucking` (self-drive) dan `deliveryToCustomer` (antar langsung) — TAPI `markDelivered` **hanya** visible ketika `latest_track_status === DeliveryToCustomer`. **Tidak ada shortcut action untuk menyelesaikan shipment yang lewat jalur `HandoverTrucking`** menuju `Delivered`. Operator pada jalur self-drive harus memakai modal `updateTrack` generik (yang mengandalkan `nextTrackStatus()`) — bukan bug per se (jalur itu tetap bisa diselesaikan), tapi inkonsistensi UX: satu cabang dari dua cabang paralel tidak punya tombol penyelesai khusus.

### Temuan tambahan (di luar tabel, tapi relevan untuk risiko)

**Jalur `updateTrack` generik melewati SEMUA cek UI-level** (`waiting_inspection_count`, `bermasalah_count`, `unassigned_container_count`) untuk transisi #4 dan #5(cabang rack) — hanya bergantung pada guard `appendTrack()` di level model. Ini berarti gate UI-level yang sudah benar sekalipun (klasifikasi B) **bisa dilewati** operator yang memilih tombol "Update" generik alih-alih tombol shortcut. Tidak melanggar apa pun secara teknis (transisi tetap valid secara `TrackStatus` sequencing), tapi berarti kontrol kualitas (mis. "jangan biarkan Stuffing kalau ada unit bermasalah") **hanya kuat selama operator memakai tombol shortcut, bukan tombol generik.**

---

## Jawaban 4 Pertanyaan Penutup

### 1. Apakah seluruh ShipmentTrack sudah membaca source of truth yang benar?

**Sebagian besar (10 dari 14) sudah benar dan memang seharusnya begitu** — transisi sekuensial murni tanpa readiness-engine yang relevan, gate `latest_track_status` adalah source of truth yang TEPAT untuk kasus-kasus itu. **4 transisi perlu dipertimbangkan ulang** (#3, #4, #7, dan pasangan #12/#13/#14) — tapi dengan alasan berbeda-beda, bukan satu pola tunggal.

### 2. Berapa banyak gate yang masih memakai sinyal legacy / progress manual yang seharusnya pakai engine?

- **1 gate** memakai Legacy Signal secara SADAR & sudah disetujui (#3, Planning Loading — `container_display`, per keputusan OPS-06).
- **1 gate** campuran Legacy+Engine yang TIDAK PERNAH menyentuh engine terstruktur yang relevan sama sekali (#4, Stuffing & Segel — tidak membaca `Container.is_ready_for_stuffing` walau sudah ada dan sudah dipakai jalur lain).
- **1 gate** murni Track Progress Manual padahal engine yang tepat sudah tersedia dan sudah punya derived state siap pakai (#7, Unit Loading regular — `shipmentStuffingSummary()`).
- **1 pasangan** gate dengan kelengkapan tidak simetris (#12/#13/#14 — satu cabang tanpa shortcut penyelesai).

Total: **4 dari 14 transisi (~29%)** perlu dipertimbangkan ulang.

### 3. Prioritas Risiko

**High:**
- **#4 — Stuffing & Segel.** Dua jalur paralel menuju status yang sama dengan syarat berbeda adalah risiko domain paling signifikan di pipeline ini — operator bisa mencapai `TrackStatus::Stuffing` tanpa planning container benar-benar selesai (lewat jalur lama), sementara jalur baru (OPS-06) sudah menegakkan itu dengan benar tapi tidak menggantikan jalur lama.

**Medium:**
- **#7 — Unit Loading (regular).** Operator bisa lanjut ke "Dimuat di Kapal" tanpa sistem memverifikasi stuffing sungguh selesai — risikonya operasional (potensi human error terlewat), bukan risiko data ganda seperti #4.
- **Jalur `updateTrack` generik melewati cek UI-level** — risiko kontrol kualitas berkurang untuk operator yang (sengaja/tidak sengaja) memakai tombol generik.

**Low:**
- **#12/#13/#14 — ketiadaan shortcut Delivered untuk jalur self-drive.** Murni UX/kelengkapan tombol, bukan risiko data — transisi tetap valid lewat `updateTrack`.
- **#3 — Planning Loading legacy signal.** Sudah sadar & disetujui, risiko rendah karena sudah didokumentasikan eksplisit.

### 4. Rekomendasi Minimum Change (tanpa modul baru — TIDAK diimplementasikan sekarang)

Diurutkan sesuai prioritas risiko, murni sebagai catatan untuk sprint terpisah (masing-masing tetap butuh keputusan Anda karena menyentuh business rule yang hidup):

1. **(High) #4 — Selaraskan gate "Stuffing & Segel"** agar turut membaca `Container.is_ready_for_stuffing` (via `ContainerAllocationService`/`Container::shipment()`) untuk shipment vehicle non-rack — bukan mengganti `unassigned_container_count`, tapi MENAMBAHKAN cek itu sebagai syarat tambahan (AND), sehingga kedua jalur (tombol lama & `StuffingWorkspace`) akhirnya menuntut syarat yang sama.
2. **(Medium) #7 — Tambahkan cek `shipmentStuffingSummary()->state === 'ready_loading'`** ke visibility `unitLoadingAuto` (sudah direkomendasikan di Audit OPS-07, dicantumkan ulang di sini karena relevan dengan pola yang sama).
3. **(Medium) Selaraskan `updateTrack` generik** agar menjalankan cek UI-level yang sama (inspection counts, container readiness) sebelum memanggil `appendTrack()` — atau, alternatif yang lebih minimal, pindahkan cek-cek itu ke dalam `Shipment::appendTrack()`/`ensureLoadingSessionCompleted()`-style guard di model, sehingga BERLAKU UNTUK SEMUA JALUR (shortcut maupun generik) secara otomatis, bukan diduplikasi di setiap `->visible()`.
4. **(Low) #14 — Tambahkan shortcut Action "Tandai Terkirim"** yang juga visible saat `latest_track_status === HandoverTrucking`, menyamakan kelengkapan dengan cabang `DeliveryToCustomer`.

**Tidak ada satu pun dari keempatnya yang membutuhkan tabel/enum baru** — seluruhnya adalah pembacaan tambahan dari service/state yang sudah ada (`ContainerAllocationService`, `StuffingService::shipmentStuffingSummary()`) atau penyelarasan `->visible()` kecil.

---

## Konfirmasi Batas

Sesuai instruksi: **tidak ada kode yang diubah, tidak ada refactor, tidak ada sprint baru dibuat, tidak ada business rule diubah.** Rekomendasi §4 murni tercatat untuk keputusan Anda di masa depan.
