# Sprint OPS-07 — Audit Loading Architecture (Implementasi DIHENTIKAN sesuai instruksi)

**Status:** AUDIT SELESAI — implementasi **tidak dilanjutkan**, sesuai instruksi eksplisit sprint: *"Jika engine Loading belum tersedia, hentikan implementasi dan laporkan hasil audit beserta rekomendasi minimum change sebelum melanjutkan."*
**Tanggal:** 23 Juli 2026
**Rujukan:** `SPRINT-OPS-06-WIRE-PLANNING-LOADING.md`, `SPRINT-ST-01-STUFFING-WORKFLOW.md`

---

## Kesimpulan Utama — premis sprint perlu dikoreksi berdasarkan bukti

Sprint ini berangkat dari asumsi: *"apakah engine Loading sebenarnya sudah ada namun belum di-wire seperti kasus Container Allocation sebelumnya."* Setelah audit menyeluruh, **premis ini tidak terbukti** — situasinya BERBEDA dari Container Allocation/Stuffing (OPS-06), dengan cara yang penting:

- Di OPS-06: `ContainerAllocationWorkspace`/`StuffingWorkspace` **ADA, LENGKAP, TERISOLASI** (nol entry-point) — murni butuh wiring.
- Di OPS-07 (Loading): **tidak ada halaman/engine "Loading Workspace" generik yang terisolasi menunggu disambungkan.** Yang ada adalah dua hal yang berbeda:
  1. **`unitLoadingAuto`** — Action yang SUDAH LIVE, SUDAH punya entry-point, SUDAH reachable di `OperationalTasks.php`, untuk shipment REGULAR (non-rack).
  2. **`LoadingSession`** (+ Resource + AppSheet handler) — engine yang SECARA SENGAJA hanya untuk shipment RACK, dan SUDAH terhubung dengan benar lewat jalur yang berbeda (AppSheet, bukan FC Operational Tasks).

**Konsekuensi:** Deliverable *"Loading memiliki entry-point dari workflow operasional"* **sudah terpenuhi tanpa perubahan kode apa pun** — baik untuk shipment reguler maupun rack. Tidak ada implementasi paralel untuk dihapus, dan tidak ada halaman tersembunyi untuk disambungkan.

---

## Jawaban Scope 1 — Audit Loading Architecture

### 1. Apakah sudah ada Loading Workspace atau Loading Service?

**Untuk shipment REGULAR (mayoritas populasi yang melewati Planning Loading/Stuffing di OPS-06): TIDAK ADA, dan memang tidak dibutuhkan.** Loading untuk shipment ini adalah satu Action sederhana (`unitLoadingAuto`, `OperationalTasks.php:1137`) yang langsung `appendTrack(TrackStatus::UnitLoading)` setelah konfirmasi — persis pola yang sama dengan `onShip`, `vesselDepart`, `vesselArrival`, `unloading`, dst.

**Untuk shipment RACK: ADA** — `LoadingSession` (model lengkap dengan 9-langkah checklist: MP attendance, health check, APD, equipment, rack/container safety, unit check, stock APD, manpower, final decision), `LoadingSessionResource` (Filament Resource dengan halaman List/Create/View/Edit + 4 sub-halaman check: rack-check, equipment-check, unit-check, final-decision), `LoadingSessionAutoCreate` (service), `LoadingSessionObserver`. **Tapi ini bukan untuk kasus yang jadi target pipeline OPS-06/07** — lihat komentar eksplisit di kode:
> `// Navigation hidden — LoadingSession is an internal AppSheet-driven workflow.`

Data entry-nya terjadi di aplikasi AppSheet (eksternal), disinkronkan lewat `App\Services\AppSheet\Handlers\LoadingSessionHandler`. Resource Filament-nya hanya cermin baca (+ sedikit edit), diakses lewat `LoadingSessionsRelationManager` di `ShipmentResource`, bukan dari `OperationalTasks.php`.

### 2. Apakah sudah ada business logic untuk Loading?

- **Shipment RACK:** ya, dan **sudah aktif ditegakkan** di level model: `Shipment::ensureLoadingSessionCompleted()` (dipanggil dari dalam `appendTrack()`) menolak transisi ke `TrackStatus::UnitLoading` kalau `LoadingSession.status !== Completed` (via `LoadingSessionAutoCreate::canTransitionTo()`). Ini BUKAN gap — ini business rule yang sudah hidup dan tervalidasi.
- **Shipment REGULAR:** tidak ada logic tambahan — `ensureLoadingSessionCompleted()` early-return untuk non-rack (`if (! isRackShipment($this)) return;`). Transisi ke `UnitLoading` hanya digerbangi oleh `guardInvalidStatusTransition()` yang generik (urutan status biasa), sama seperti seluruh transisi Track lain.

### 3. Apakah Loading saat ini masih menggunakan modal Update Track biasa?

**Ya, untuk shipment regular — bahkan lebih sederhana dari modal:** `unitLoadingAuto` tidak punya `->form([...])` sama sekali, hanya `->requiresConfirmation()` lalu langsung `appendTrack()`. Ini identik dengan pola SELURUH transisi Track lain di file yang sama (`onShip`, `vesselDepart`, `vesselArrival`, `unloading`, `handoverTrucking`, dst.) — bukan pengecualian atau keterbatasan, ini memang desain yang konsisten untuk seluruh pipeline pasca-Stuffing.

### 4. Apakah ada halaman Loading yang belum memiliki entry-point seperti ContainerAllocationWorkspace sebelumnya?

**Tidak.** `LoadingSessionResource` memang `shouldRegisterNavigation = false`, tapi ini BUKAN kasus "lupa di-wire" — ini desain yang sengaja: workflow rack sepenuhnya berjalan di AppSheet (di luar FC Operational Tasks), dan Resource Filament-nya memang bukan untuk diakses dari alur kerja FC harian. Berbeda total dari `ContainerAllocationWorkspace` (yang memang DIRANCANG untuk dipakai FC tapi kelupaan disambungkan).

---

## Jawaban Scope 2 — Loading Readiness

**Temuan (gap nyata, tapi TIDAK diimplementasikan sprint ini karena menyentuh business rule):** Visibility `unitLoadingAuto` HANYA mengecek `latest_track_status === Stacking`. Ia **tidak pernah membaca**:
- `StuffingService::shipmentStuffingSummary()` (ST-01) — yang sudah punya derived state `ready_loading` persis untuk keperluan ini.
- `Container.is_ready_for_stuffing`/`ContainerStuffingStatus::Full`.
- `Unit.allocation_status === Stuffed`.

Artinya: readiness "boleh Loading" hari ini murni berdasar **progres manual operator melalui tahap Track** (Stuffing → DeliveryToPort → Stacking → Loading), bukan berdasar apakah stuffing SUNGGUH-SUNGGUH selesai secara data. Operator bisa saja mengklik "Antar ke Pelabuhan" lalu "Stacking" lalu "Dimuat di Kapal" berturut-turut tanpa sistem pernah mengecek `shipmentStuffingSummary()->state === 'ready_loading'`.

**Ini TIDAK diimplementasikan sekarang** karena mengubahnya berarti mengubah business rule/gate yang sedang hidup (`unitLoadingAuto`'s visibility) — eksplisit di luar cakupan sprint ini ("Tidak mengubah business rule"). Dicatat sebagai rekomendasi untuk sprint terpisah (lihat §Rekomendasi).

---

## Jawaban Scope 5 — ShipmentTrack

**Tidak ada gap struktural.** `TrackStatus::UnitLoading` sudah ada dan sudah persis merepresentasikan "Loading". Tidak perlu enum baru, tidak perlu status baru — dikonfirmasi selaras dengan instruksi sprint.

---

## Deliverables — status tanpa perubahan kode

| Deliverable | Status |
|---|---|
| Audit lengkap implementasi Loading yang sudah ada | ✅ (dokumen ini) |
| Loading memiliki entry-point dari workflow operasional | ✅ **Sudah terpenuhi sejak awal** — `unitLoadingAuto` (regular) dan jalur AppSheet (rack) keduanya sudah live |
| Operator dapat melanjutkan dari Stuffing ke Loading tanpa keluar dari alur kerja | ✅ Sudah bisa — `Stuffing → deliveryToPort → stacking → unitLoadingAuto`, seluruhnya di `ActionGroup` yang sama, sudah reachable |
| Tidak ada implementasi Loading paralel | ✅ Dikonfirmasi — tidak dibuat apa pun |
| Seluruh wiring tetap menggunakan engine yang sudah ada | ✅ N/A — tidak ada wiring yang perlu dilakukan |

**Acceptance Criteria** ("Pickup → Handover → Planning Loading → Stuffing → Loading, tanpa URL manual") — **sudah tercapai** dengan kombinasi OPS-06 (Planning Loading + Mulai Stuffing) dan mekanisme Loading yang MEMANG SUDAH reachable sejak sebelum sprint ini dimulai. Tidak ada kode yang perlu diubah untuk mencapai kriteria ini.

---

## Rekomendasi (bukan bagian sprint ini — perlu keputusan terpisah)

Kalau readiness Loading ingin benar-benar mencerminkan status Stuffing (bukan hanya progres Track manual), perubahan minimum yang konsisten dengan arsitektur yang ada:

```php
// Di dalam visible() milik Action::make('unitLoadingAuto'):
return ! LoadingSessionAutoCreate::isRackShipment($record)
    && app(StuffingService::class)->shipmentStuffingSummary($record)['state'] === 'ready_loading';
```

Ini murni MEMBACA `StuffingService::shipmentStuffingSummary()` yang sudah ada sejak ST-01 — tidak perlu field/tabel/enum baru. **Tapi ini adalah perubahan business rule** (memperketat kapan tombol "Dimuat di Kapal" muncul) yang berdampak langsung ke operator yang sedang memakai alur ini — sengaja tidak saya terapkan tanpa konfirmasi eksplisit Anda, konsisten dengan batas "Tidak mengubah business rule" di sprint ini.

---

## Konfirmasi Batas

Sesuai instruksi eksplisit sprint (*"Jika engine Loading belum tersedia, hentikan implementasi dan laporkan hasil audit... sebelum melanjutkan"*) — **implementasi dihentikan di titik ini.** Tidak ada file yang diubah pada sprint OPS-07 ini.
