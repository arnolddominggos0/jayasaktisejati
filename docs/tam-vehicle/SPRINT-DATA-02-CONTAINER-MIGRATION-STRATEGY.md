# Sprint DATA-02 — Container Allocation Migration Strategy

**Status:** IMPLEMENTATION — arsitektur migrasi + kedua command selesai dan tervalidasi struktural. **Angka nyata (Scope 5/6 output, verdict final Scope 8) belum bisa saya hasilkan sendiri** — dua kali percobaan baca read-only terhadap database ditolak auto-mode classifier karena constraint DB produksi yang belum terverifikasi (konsisten sepanjang sesi ini). Lihat §Yang Perlu Keputusan Anda.
**Tanggal:** 23 Juli 2026

---

## Temuan Kritis yang Mengubah Bentuk Sprint Ini

Sebelum masuk ke scope, satu temuan yang HARUS diketahui dulu: saya cek `php artisan migrate:status` (read-only, tidak mengubah apa pun) dan menemukan sesuatu yang mengubah total pemahaman saya sepanjang sesi ini:

```
2026_07_23_090000_create_containers_table ............................ Ran
2026_07_23_090100_add_allocation_fields_to_units_table ................ Ran
2026_07_23_100000_add_stuffing_fields_to_containers_table ............. Pending
2026_07_23_100100_add_stuffing_fields_to_units_table ................. Pending
```

**Tabel `containers` dan kolom `Unit.container_id`/`allocation_status` (CA-01) SUDAH live di database** — bukan hipotetis seperti asumsi saya di ARCH-02. Tapi **kolom stuffing ST-01** (`Container.stuffing_status`, `Unit.stuffed_at`, dst.) **belum ada** — artinya `StuffingWorkspace`/`StuffingService` yang saya bangun di ST-01 **akan error kalau benar-benar dipakai sekarang** (kolom belum ada di DB). Ini bukan bagian dari DATA-02, tapi perlu Anda ketahui sebagai risiko operasional terpisah — saya tidak menyentuhnya di sprint ini (Out of Scope: "Tidak mengubah Stuffing").

**Konsekuensi untuk DATA-02:** masalah "dua dunia data terpisah" yang diaudit ARCH-02 adalah **nyata dan hidup di database sekarang**, bukan sekadar potensi masa depan. Sprint ini menjadi lebih penting, bukan kurang.

---

## Scope 1 — Lifecycle Container: Lama vs Baru

```
LEGACY (Unit.container_display)
────────────────────────────────
1. Dibuat kapan?    Saat operator submit modal "Handover Depo"
2. Siapa?           Field Coordinator (FC), lewat Repeater form per-unit
3. File/service?    app/Filament/FC/Pages/OperationalTasks.php,
                     Action::make('handover')->action(), baris ~960-971
4. Validasi?        TIDAK ADA — TextInput bebas, tanpa cek terhadap
                     Container Readiness, tanpa cek keunikan/kapasitas
5. Berapa kali bisa diubah? Berkali-kali, lewat modal Handover yang sama
                     (form re-fill dari nilai saat ini)


BARU (Container + Unit.container_id)
────────────────────────────────
1. Dibuat kapan?    Saat FC membuka ContainerAllocationWorkspace dan
                     menyelesaikan Tipe/Kapasitas + assign unit
2. Siapa?           Field Coordinator, lewat halaman terpisah
                     (baru dapat entry-point di sesi ini — OPS-06)
3. File/service?    app/Filament/FC/Pages/ContainerAllocationWorkspace.php
                     + app/Services/ContainerAllocation/ContainerAllocationService.php
                     Container row dibuat lewat Container::resolveForSession()
                     — WAJIB tervalidasi terhadap ContainerReadinessSession
                     hari itu (FK container_readiness_session_id NOT NULL)
4. Validasi?        KUAT — container_no harus terdaftar di Readiness sesi
                     terkait, kapasitas ditegakkan, container yang sudah
                     "Siap Stuffing" tidak bisa diubah lagi
5. Berapa kali bisa diubah? Sampai container ditandai "Siap Stuffing"
                     (is_ready_for_stuffing), setelah itu terkunci


TITIK PERTEMUAN KEDUA PROSES INI?
────────────────────────────────
TIDAK ADA. Dikonfirmasi lewat kode, bukan dugaan:

  grep "container_display" app/Services/ContainerAllocation/ContainerAllocationService.php
  → No matches found

ContainerAllocationService tidak pernah menulis/membaca container_display.
Modal Handover tidak pernah menulis/membaca container_id/Container. Kedua
proses berjalan independen, atas unit yang sama, tanpa saling tahu.
```

---

## Scope 2 — Mapping Data Lama → Baru

| Field yang diminta diaudit | Tersedia dari legacy? | Catatan |
|---|---|---|
| `container_display` (nomor container) | ✅ **Tersedia** | Sumber utama backfill — string bebas (`REG001`, `TGHU1234567`, dst.) |
| `shipment_id` | ✅ **Tersedia** | Lewat `Unit.shipment_id` (FK yang sudah ada, tidak berubah) |
| `voyage_id` | ⚠️ **Tidak relevan untuk migrasi ini** | Ini atribut `Shipment`/`Voyage`, bukan atribut `Container`. `Container` (skema CA-01) TIDAK punya kolom voyage — sengaja (Container adalah entitas alokasi harian, bukan entitas per-voyage). Tidak perlu dimigrasikan ke `Container`. |
| `seal` (No. Segel) | ❌ **Tidak tersedia dari sumber ini** | `seal_no` yang ada di codebase adalah atribut **Shipment** (level sea-freight container, domain General Cargo — lihat "Informasi Operasional" panel UX-05), BUKAN atribut `Unit.container_display` (domain TAM Vehicle). Legacy `container_display` tidak pernah menyimpan data segel per unit/container. |
| `container_size` | ❌ **Tidak tersedia** | Sama seperti di atas — `container_size` (ISO size, `ContainerSize` enum) adalah domain General Cargo/Shipment-level, bukan domain Rack/Regular TAM Vehicle. `Container.type` (CA-01) memakai `ContainerAllocationType` (Rack/Regular) — konsep yang sama sekali berbeda, dan legacy `container_display` tidak pernah menyimpan klasifikasi ini. |
| `container_type` (Rack/Regular) | ❌ **Perlu inferensi, TIDAK dilakukan** | Bisa saja "ditebak" dari `Shipment.vehicle_loading` (`rack`/`flat_rack`/`regular` — field level-Shipment) sebagai proxy, tapi ini INFERENSI, bukan fakta langsung dari `container_display` itu sendiri, dan satu shipment bisa punya container campuran. Sesuai "Important Constraints" ("jangan membuat asumsi/inferensi"), **command backfill TIDAK mengisi `Container.type`** — dibiarkan `null`, sama seperti Container baru yang belum "Dilengkapi" lewat Allocation Workspace secara normal. FC tetap perlu melengkapi tipe/kapasitas secara manual, persis seperti alur normal. |
| `shipping_line` | ❌ **Tidak tersedia** | Tidak ada field ini di `Unit` maupun `Container` sama sekali di kedua sistem — bukan bagian dari domain TAM Vehicle. |
| `stuffing status` | ❌ **Tidak bisa diklaim dari legacy** | `container_display` terisi HANYA membuktikan "unit pernah dicatat masuk container ini" — TIDAK membuktikan container itu pernah ditandai "Siap Stuffing" (`is_ready_for_stuffing`) via proses yang tervalidasi, apalagi status stuffing ST-01 (`stuffing_status`) yang kolomnya bahkan belum ter-migrate. Command backfill mengisi `Unit.allocation_status = InContainer` (fakta paling jujur yang bisa dibuktikan) — TIDAK PERNAH `ReadyForStuffing` atau `Stuffed`. |
| `loading status` | ❌ **Tidak tersedia & di luar cakupan** | Tidak ada representasi "loading status" di level container manapun di kedua sistem. Di luar cakupan Out of Scope sprint ini juga ("Tidak mengubah Loading"). |

**Ringkasan:** dari 9 field yang diminta diaudit, hanya **2 (container_display, shipment_id) benar-benar bisa dimigrasikan langsung**. Sisanya BUKAN karena datanya "hilang" — tapi karena field-field itu (voyage_id, seal, container_size, shipping_line) secara desain adalah domain **General Cargo/Shipment-level yang berbeda**, tidak pernah menjadi bagian dari `Unit.container_display` (domain TAM Vehicle) sejak awal. Ini bukan gap migrasi — ini kesalahpahaman scope yang perlu diluruskan: **`Container`/`Unit.container_display` tidak pernah dirancang untuk membawa data itu**, di kedua sistem.

---

## Scope 3 — Migration Plan (4 Fase)

```
┌─────────────────────────────────────────────────────────────────┐
│ PHASE A — Historical Backfill                                    │
├─────────────────────────────────────────────────────────────────┤
│ Syarat masuk : Tabel `containers` & kolom allocation SUDAH live  │
│                (✅ dikonfirmasi — sudah Ran).                    │
│ Aktivitas    : Jalankan `containers:backfill --commit` untuk     │
│                seluruh unit dengan container_display terisi,     │
│                container_id kosong.                              │
│ Syarat keluar: Command selesai jalan; laporan "units_pending"    │
│                ditinjau — sisa unit yang gagal dibackfill        │
│                (kemungkinan besar: shipment sebelum Container    │
│                Readiness ada) DIDOKUMENTASIKAN sebagai            │
│                "tidak bisa dimigrasikan otomatis", bukan blocker │
│                untuk lanjut ke Phase B.                          │
├─────────────────────────────────────────────────────────────────┤
│ PHASE B — Dual Validation                                        │
├─────────────────────────────────────────────────────────────────┤
│ Syarat masuk : Phase A selesai (backfill sudah di-commit).       │
│ Aktivitas    : Jalankan `containers:audit` secara BERKALA         │
│                (mis. harian) selama periode transisi. Operator   │
│                FC tetap memakai KEDUA jalur (legacy tetap hidup, │
│                Allocation Workspace makin dipakai lewat OPS-06). │
│ Syarat keluar: `conflicting` count di containers:audit = 0       │
│                secara konsisten (tidak ada shipment baru yang    │
│                datanya bertentangan antara kedua sisi) selama    │
│                periode observasi yang disepakati (mis. 2-4       │
│                minggu operasional).                              │
├─────────────────────────────────────────────────────────────────┤
│ PHASE C — Cutover                                                 │
├─────────────────────────────────────────────────────────────────┤
│ Syarat masuk : Phase B keluar bersih DAN Anda memutuskan gate    │
│                ShipmentTrack (ARCH-02 Scope 1/2, yang DIHENTIKAN │
│                di sprint sebelumnya) boleh dipindah ke            │
│                Container/is_ready_for_stuffing.                  │
│ Aktivitas    : Baru di titik INI ARCH-02 Scope 1/2 dieksekusi —  │
│                bukan di sprint ini.                               │
│ Syarat keluar: Gate baru berjalan tanpa insiden untuk seluruh    │
│                shipment aktif.                                    │
├─────────────────────────────────────────────────────────────────┤
│ PHASE D — Legacy Removal                                         │
├─────────────────────────────────────────────────────────────────┤
│ Syarat masuk : Phase C stabil dalam periode signifikan (mis. 1   │
│                siklus penuh operasional tanpa insiden).           │
│ Aktivitas    : BARU di titik ini `container_display` dipensiunkan │
│                (bukan dihapus paksa — deprecation bertahap,       │
│                di luar cakupan sprint mana pun sejauh ini).       │
│ Syarat keluar: (di luar cakupan DATA-02 — keputusan terpisah).   │
└─────────────────────────────────────────────────────────────────┘
```

Sprint DATA-02 ini **hanya membangun tooling untuk Phase A dan B** — tidak menjalankan Phase C/D, sesuai Out of Scope ("Tidak mengubah transition gate").

---

## Scope 4 — Backfill Command: `containers:backfill`

**File:** `app/Console/Commands/ContainersBackfillCommand.php`

- **Dry-run secara default** — HANYA menghitung dan melaporkan, TIDAK menulis apa pun kecuali `--commit` diberikan. Ini keputusan keamanan tambahan (di luar yang diminta literal, tapi konsisten dengan disiplin "tidak menulis ke DB produksi tanpa izin eksplisit" yang berlaku sepanjang sesi ini).
- **Hanya membaca data lama** — sumber: `Unit.container_display`, `ShipmentTrack` (untuk tanggal anchor), `ContainerReadinessSession` (untuk validasi SSOT).
- **Membuat Container hanya lewat `Container::resolveForSession()`** — method yang SAMA PERSIS dipakai `ContainerAllocationWorkspace` — tidak ada jalur pembuatan Container paralel/baru.
- **Idempotent** — hanya memproses `Unit::whereNotNull('container_display')->whereNull('container_id')`; unit yang sudah tertaut otomatis dikecualikan di run berikutnya oleh WHERE clause itu sendiri.
- **Tidak overwrite** — tidak pernah menyentuh unit yang `container_id`-nya sudah terisi (dari proses manapun).
- **Tidak ada data sintetis** — `Container.type`/`capacity` dibiarkan `null` (FC tetap melengkapi manual); `Unit.allocation_status` diisi `InContainer` saja (fakta paling jujur, bukan `ReadyForStuffing`/`Stuffed`).
- **Migration pending, bukan silent failure** — setiap unit yang tidak bisa dibackfill dicatat lengkap dengan alasannya di laporan akhir (lihat kode: kondisi tanpa Handover track, tanpa Readiness session pada tanggal itu, atau container_no tidak terdaftar di sesi itu — SEMUANYA dilaporkan, bukan dilewati diam-diam).

---

## Scope 5 — Struktur Validation Report (dihasilkan otomatis oleh command)

Command mencetak tabel:
```
Unit dipindai (container_display terisi, container_id kosong) | N
Container dibuat baru                                          | N
Container reused (sudah ada dari proses lain)                  | N
Unit berhasil dihubungkan (container_id terisi)                | N
Unit migration pending (TIDAK dihubungkan — lihat alasan)      | N
```
diikuti tabel detail per-unit untuk SETIAP kegagalan (Unit ID, Shipment, Container legacy, **alasan tekstual eksplisit**) — tidak ada "silent failure", sesuai instruksi Scope 5.

---

## Scope 6 — Audit Command: `containers:audit`

**File:** `app/Console/Commands/ContainersAuditCommand.php`

READ-ONLY selamanya (tidak ada mode commit sama sekali — tidak relevan/tidak dibuat, karena command ini memang tidak pernah menulis apa pun). Untuk setiap unit yang punya `container_display` dan/atau `container_id`, mengklasifikasi:
- `legacy_only` — kandidat untuk `containers:backfill`.
- `engine_only` — sudah pakai Allocation Workspace, `container_display` belum diisi (kasus baru pasca-OPS-06).
- `consistent` — kedua sisi cocok.
- `conflicting` — **berbeda** — dilaporkan, TIDAK diperbaiki otomatis, persis instruksi Scope 6.

---

## Scope 7 — Compatibility Selama Migrasi

Dijamin BENAR secara struktural (bukan hanya klaim) karena:
- `containers:backfill` **tidak pernah menghapus/mengubah `container_display`** — dikonfirmasi lewat kode (satu-satunya penulisan adalah ke `Unit.container_id`/`allocation_status`, dalam blok `if ($commit)`).
- `containers:backfill` **tidak menyentuh `ShipmentTrack`, gate transisi, atau `appendTrack()`** — jadi shipment legacy yang sedang berjalan (memakai `unassigned_container_count`/`isHandoverInspectionCleared()` dari ARCH-01, yang membaca `container_display`) **sama sekali tidak terpengaruh**.
- Shipment yang SUDAH memakai `ContainerAllocationWorkspace` (`container_id` sudah terisi) **otomatis dikecualikan** dari backfill (WHERE `container_id IS NULL`) — data mereka tidak disentuh sama sekali.

**Tidak ada downtime, tidak ada shipment yang mendadak tidak bisa Stuffing** — karena command ini TIDAK mengubah gate apa pun (itu domain ARCH-02 Scope 1/2, yang secara sadar tidak dieksekusi).

---

## Scope 8 — Cutover Readiness

### Verdict: **NOT READY** — tapi dengan alasan yang berbeda dari dugaan awal, dan risiko jauh lebih rendah dari yang diperkirakan

**Setelah izin eksplisit Anda, kedua command dijalankan read-only terhadap `jss_db`. Angka nyata (bukan proyeksi):**

```
units total                          : 4
units dengan container_display terisi: 0
units dengan container_id terisi     : 0
containers table (baris)             : 0
container_readiness_sessions (baris) : 74
shipments total                      : 2
shipments cargo_type = vehicle       : 2
```

`containers:audit` dan `containers:backfill` (dry-run) keduanya **secara independen mengonfirmasi angka yang sama**: nol unit punya `container_display`, nol punya `container_id`.

**Interpretasi yang jujur:** ini bukan basis data produksi dengan riwayat operasional yang panjang — hanya 2 shipment dan 4 unit total ada di sistem saat ini. `container_readiness_sessions` (74 baris) menunjukkan modul ITU sudah dipakai/di-seed cukup lama, tapi alur Shipment→Unit→Container (baik legacy maupun baru) **belum pernah benar-benar dipakai sama sekali** — bukan karena bermasalah, tapi karena datanya memang belum ada.

**Kenapa verdict tetap NOT READY** (alasan struktural, bukan soal volume data):
1. Kolom `Container.stuffing_status`/`Unit.stuffed_at` (ST-01) **belum ter-migrate** (`Pending`) — `StuffingWorkspace` akan error kalau dipakai sekarang, terlepas dari isu container manapun.
2. `containers` table masih 0 baris — belum ada satu pun bukti `ContainerAllocationWorkspace` pernah benar-benar dipakai sampai selesai (wajar, baru dapat entry-point hari ini di OPS-06).
3. `Container.container_readiness_session_id` yang wajib (FK) tetap jadi syarat keras begitu data legacy MULAI muncul — arsitekturnya belum teruji dengan data sungguhan sama sekali.

**Kabar baiknya:** karena **tidak ada legacy data yang berisiko saat ini** (0 unit dengan `container_display`), ini adalah momen PALING AMAN untuk sprint semacam ini — tooling (`containers:backfill`/`containers:audit`) sudah siap dan akan langsung berguna begitu data mulai masuk, tanpa perlu migrasi retroaktif yang rumit nantinya. Tidak ada urgensi migrasi mendesak — situasinya jauh lebih ringan dari dugaan struktural awal saya sebelum angka nyata ini didapat.

---

## Validasi yang Sudah Dilakukan

| Uji | Hasil |
|---|---|
| `php -l` pada kedua command | ✅ Bersih |
| `composer dump-autoload` | ✅ Sukses |
| `php artisan list containers` — kedua command terdaftar & auto-discovered | ✅ |
| `php artisan containers:backfill --help` / `containers:audit --help` — opsi sesuai desain (`--commit`, `--shipment`, `--limit`) | ✅ |
| `php artisan migrate:status` (read-only) — dasar temuan kritis di atas | ✅ |
| **`php artisan containers:audit` (read-only, izin eksplisit Anda)** | ✅ Dijalankan — 0 unit dalam cakupan |
| **`php artisan containers:backfill` TANPA `--commit` (dry-run, izin eksplisit Anda)** | ✅ Dijalankan — 0 unit dipindai, 0 pending, konsisten dengan audit |
| Raw count cross-check (`units`, `containers`, `shipments`, dll., izin eksplisit Anda) | ✅ Dijalankan — angka di atas |
| `--commit` (penulisan sungguhan) | ❌ Tidak dijalankan — tidak relevan (0 unit untuk dibackfill) dan tetap butuh izin terpisah sesuai disiplin sesi ini |

## Konfirmasi Batas

- ✅ Tidak mengubah ShipmentTrack, transition gate, Stuffing, Loading, UI, atau Workflow.
- ✅ Tidak menghapus `container_display`.
- ✅ Tidak ada data sintetis/inferensi tanpa dasar (Container.type sengaja dibiarkan kosong, allocation_status hanya diisi fakta yang bisa dibuktikan).
- ✅ Migration bersifat deterministic, repeatable, idempotent — dijamin oleh desain (WHERE container_id IS NULL, resolveForSession() firstOrCreate).
