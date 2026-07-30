# Sprint DB-01 — Activate Stuffing Schema

**Status:** IMPLEMENTED — kedua migration Pending berhasil dijalankan, schema/model/service diverifikasi penuh terhadap database nyata, seluruh read-path pipeline diuji langsung tanpa error.
**Tanggal:** 23 Juli 2026
**Rujukan:** `SPRINT-DATA-02-CONTAINER-MIGRATION-STRATEGY.md` (temuan yang memicu sprint ini)

---

## Scope 1 — Audit Migration Pending

`php artisan migrate:status` (read-only) mengonfirmasi **tepat 2 migration Pending**, tidak ada yang tertinggal selain itu:

| Migration | Tabel | Kolom yang dibuat | Dependency kode |
|---|---|---|---|
| `2026_07_23_100000_add_stuffing_fields_to_containers_table` | `containers` | `stuffing_status` (varchar 20, default `'ready'`), `stuffing_started_at` (timestamp, nullable), `stuffing_completed_at` (timestamp, nullable) | `App\Models\Container` (cast `stuffing_status` → `ContainerStuffingStatus` enum), `App\Services\Stuffing\StuffingService` |
| `2026_07_23_100100_add_stuffing_fields_to_units_table` | `units` | `stuffed_at` (timestamp, nullable), `stuffed_by` (FK → `users.id`, nullable, `nullOnDelete`), `stuffing_remarks` (text, nullable) | `App\Models\Unit` (relasi `stuffedBy()`), `App\Services\Stuffing\StuffingService` |

Dikonfirmasi lewat `grep`: hanya 3 file yang mereferensikan kolom-kolom ini — `StuffingService.php`, `Unit.php`, `Container.php`. Tidak ada dependency tersembunyi di tempat lain.

**Catatan koreksi terhadap contoh field di brief sprint:** Scope 3 brief menyebut `Container.stuffed_by` dan `Unit.stuffing_notes` — keduanya **tidak sesuai skema nyata**. `stuffed_by` adalah kolom di **Unit**, bukan Container (Container tidak dan tidak perlu punya `stuffed_by` — itu identitas ORANG, level konfirmasi per-unit, bukan level container). Nama kolom yang benar adalah `stuffing_remarks`, bukan `stuffing_notes`. Saya tidak membuat kolom baru untuk menyamakan dengan penyebutan di brief — saya verifikasi terhadap skema yang SUDAH ditulis di migration ST-01 (yang memang jadi acuan sprint ini), dan melaporkan penyimpangan penamaan ini secara eksplisit alih-alih diam-diam mengikuti salah satu.

---

## Scope 2 — Safe Migration

1. **Preview dulu** (`php artisan migrate --pretend --force` — `--force` hanya melewati prompt konfirmasi interaktif `APP_ENV=production`, `--pretend` sendiri tetap 100% read-only): SQL yang akan dijalankan murni `ALTER TABLE ADD COLUMN` (3 kolom di `containers`, 3 kolom + 1 FK constraint `ON DELETE SET NULL` di `units`) — tidak ada `DROP`, tidak ada modifikasi data.
2. **Jalankan sungguhan** (`php artisan migrate --force`): kedua migration **DONE** tanpa error (51.55ms dan 33.78ms).
3. **Verifikasi status**: `migrate:status` sekarang menunjukkan keduanya `Ran` (batch 18).

Tidak ada migration lama yang diubah. Tidak ada migration baru yang dibuat — dua-duanya sudah ditulis sejak ST-01, sprint ini hanya mengeksekusinya.

---

## Scope 3 — Schema Verification

Dicek langsung ke `Schema::getColumnListing()` (bukan asumsi dari file migration):

```
containers: ..., is_ready_for_stuffing, marked_ready_at, marked_ready_by,
            created_at, updated_at, stuffing_status, stuffing_started_at,
            stuffing_completed_at          ✅ Seluruh 3 kolom ST-01 ada

units: ..., container_display, container_id, allocation_status,
       stuffed_at, stuffed_by, stuffing_remarks   ✅ Seluruh 3 kolom ST-01 ada
```

---

## Scope 4 — Model Verification

| Field | Fillable | Ada di DB | Cast |
|---|---|---|---|
| `Container.stuffing_status` | ✅ | ✅ | `ContainerStuffingStatus` (enum, dikonfirmasi resolve) |
| `Container.stuffing_started_at` | ✅ | ✅ | `datetime` |
| `Container.stuffing_completed_at` | ✅ | ✅ | `datetime` |
| `Unit.stuffed_at` | ✅ | ✅ | `datetime` |
| `Unit.stuffed_by` | ✅ | ✅ | (tidak perlu cast — FK integer polos) |
| `Unit.stuffing_remarks` | ✅ | ✅ | (tidak perlu cast — text polos) |
| `Unit::stuffedBy()` relation | — | — | ✅ Method ada, `belongsTo(User::class, 'stuffed_by')` |

**Tidak ada field yang dipakai code tapi belum ada di DB.** **Tidak ada field di DB yang tidak dikenali model** (seluruh kolom baru sudah masuk `$fillable`/`$casts` sejak ditulis di ST-01 — migration yang tertinggal, bukan model).

---

## Scope 5 — Runtime Verification

| Command | Hasil |
|---|---|
| `php artisan migrate:status` | ✅ |
| `php artisan migrate --force` | ✅ 2 migration DONE, 0 error |
| `php artisan optimize:clear` | ✅ (cache/compiled/config/events/routes/views/blade-icons/filament — semua DONE) |
| `php artisan optimize` | ✅ (config/events/routes/views/blade-icons/filament — semua DONE) |
| `php artisan route:clear` | ✅ |
| `php artisan config:clear` | ✅ |
| `php artisan cache:clear` | ✅ |
| `composer dump-autoload` | ✅ |
| `php -l` (8 file inti pipeline: Container, Unit, StuffingService, StuffingWorkspace, ContainerAllocationWorkspace, ContainerAllocationService, Shipment, OperationalTasks) | ✅ Bersih semua |

**Fungsional nyata (bukan cuma php -l) — dijalankan terhadap database sungguhan, read-only:**
```
StuffingService::checkPreconditions()        → OK, tidak ada error, untuk 2 shipment nyata
StuffingService::shipmentStuffingSummary()   → OK, state='waiting_stuffing' (benar, konsisten
                                                 dengan 0 unit yang sudah di-stuff)
ContainerAllocationWorkspace::getUnallocatedUnits()         → OK, 4 unit
ContainerAllocationWorkspace::getContainers()                → OK, 4 container
ContainerAllocationWorkspace::getShipmentsReadyForStuffing() → OK, 0 shipment
StuffingWorkspace::getCandidateShipments()                   → OK, 1 shipment
```
**Tidak ada Unknown Column. Tidak ada Unknown Enum. Tidak ada SQL error.** Ini bukti langsung, bukan inferensi — persis risiko yang menjadi alasan sprint ini ada.

---

## Scope 6 — End-to-End Readiness Audit (per tahap)

| Tahap | Status | Bukti |
|---|---|---|
| Container Readiness | ✅ READY | Tabel ada (74 baris riwayat nyata), Resource berfungsi, field Service (CR-02) sudah termigrasi sejak sebelumnya |
| Container Allocation | ✅ READY | Tabel ada, FK ke Readiness berfungsi, seluruh method (`getUnallocatedUnits`/`getContainers`/`getShipmentsReadyForStuffing`) diuji langsung tanpa error |
| Stuffing | ✅ READY (baru, hasil sprint ini) | Skema baru saja diaktifkan; `StuffingService`/`StuffingWorkspace` diuji langsung terhadap data nyata tanpa error — sebelumnya akan error "Unknown Column" |
| Loading | ✅ READY | Tabel `loading_sessions` (jalur rack) ada; jalur regular murni transisi TrackStatus tanpa dependency skema (temuan Audit OPS-07, tidak berubah) |
| ShipmentTrack | ✅ READY | Fondasi inti, sudah lama stabil; guard tersentralisasi (ARCH-01), urutan Self Drive/Dooring diperbaiki (ARCH-02 Scope 3) |

---

## Scope 7 — Deliverable

| Module | Status | Notes |
|---|---|---|
| Container Readiness | ✅ READY | Tidak disentuh sprint ini — sudah berfungsi sejak sebelumnya |
| Container Allocation | ✅ READY | Skema sudah ada sejak CA-01 (batch 17); diverifikasi ulang, tidak ada perubahan |
| Stuffing | ✅ READY | **Skema baru diaktifkan sprint ini** (batch 18) — sebelumnya schema-incomplete, sekarang lengkap dan teruji |
| Loading | ✅ READY | Tidak ada dependency skema yang hilang untuk kedua jalur (regular/rack) |
| ShipmentTrack | ✅ READY | Tidak disentuh sprint ini; guard/urutan sudah benar dari sprint-sprint sebelumnya |

---

## Final Report

### **READY FOR OPERATIONAL TRIAL**

**Bukti teknis, ringkas:**
1. Kedua migration Pending berhasil `Ran` — dikonfirmasi `migrate:status`.
2. Seluruh kolom yang dipakai `StuffingService`/`StuffingWorkspace` dikonfirmasi ADA di database nyata lewat `Schema::getColumnListing()` — bukan asumsi dari kode.
3. `StuffingService::checkPreconditions()` dan `::shipmentStuffingSummary()` dijalankan langsung terhadap 2 shipment nyata di database — **tidak ada error apa pun**, hasil derived state benar dan masuk akal.
4. Seluruh method baca `ContainerAllocationWorkspace`/`StuffingWorkspace` dijalankan terhadap data nyata — tidak ada error.
5. Tidak ada migration ST-01/CA-01/CR-02 yang masih Pending — hanya 2 yang tadinya Pending, keduanya sekarang Ran.

**Penting — perbedaan makna "READY" di sini vs verdict DATA-02:**

Sprint DATA-02 (sebelumnya) menyimpulkan **NOT READY untuk CUTOVER** — pertanyaan yang berbeda: *"bolehkah gate ShipmentTrack dipindah dari `container_display` ke `Container`/`is_ready_for_stuffing`?"* Itu masih **NOT READY** (dan sprint ini tidak mengubah kesimpulan itu — Out of Scope: "Tidak mengubah Transition Guard").

Sprint DB-01 ini menjawab pertanyaan yang berbeda: **"apakah kode Stuffing yang sudah ditulis bisa dijalankan tanpa runtime error?"** — jawabannya sekarang **YA**, dibuktikan langsung. "Operational Trial" di sini berarti: operator FC sekarang **bisa** mencoba `ContainerAllocationWorkspace` → `StuffingWorkspace` secara end-to-end tanpa risiko crash karena kolom hilang. Ini **tidak berarti** jalur legacy (`container_display`) sudah digantikan — kedua jalur tetap hidup berdampingan persis seperti dirancang di ARCH-02/DATA-02, sesuai Out of Scope sprint ini ("Tidak mengubah business rule/Workflow/Transition Guard").

---

## Konfirmasi Batas

- ✅ Tidak ada migration baru dibuat — hanya menjalankan yang sudah ada.
- ✅ Tidak ada migration lama diubah.
- ✅ Tidak ada workaround — migration gagal akan dihentikan & dilaporkan (tidak terjadi; keduanya sukses bersih).
- ✅ Tidak mengubah business rule, UI, Workflow, atau Transition Guard.
- ✅ Tidak melakukan Trial Operator — seluruh verifikasi bersifat baca (read-only) atau eksekusi command Laravel standar (migrate/optimize/clear), tidak ada simulasi alur kerja operator penuh (assign/mark-stuffed/dst.).
