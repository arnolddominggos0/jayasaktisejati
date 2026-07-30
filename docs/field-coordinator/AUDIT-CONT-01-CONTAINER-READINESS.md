# Audit CONT-01 — Container Readiness Business & Architecture Audit

**Status:** AUDIT SELESAI. Tidak ada kode yang diubah.
**Tanggal:** 29 Juli 2026
**Tipe:** Audit domain (bukan UI, bukan perbaikan)

---

## Ringkasan Eksekutif

Indikasi awal Anda benar: **halaman Container Readiness memang mencampurkan beberapa domain**, dan itu adalah hasil **evolusi implementasi**, bukan keputusan desain tunggal. Buktinya ada di jejak migration: tabel inti dibuat 2026-06-10 sebagai *demand planning harian* murni (5 angka), lalu pada 2026-06-23 kolom `container_numbers` (JSON daftar container fisik) **ditempelkan belakangan** — domain kedua masuk ke tabel yang sama.

Empat temuan utama:

1. **Dua field berbeda memakai label identik "Container Tersedia"** di form yang sama — satu angka manual (`container_available`), satu daftar (`container_numbers`) — dan **keduanya tidak saling terhubung sama sekali**.
2. **`container_need` tidak punya formula.** Diuji terhadap 72 baris data historis: `ceil(unit_count/2)` hanya cocok 43%, dan 2 baris punya `need = 0` padahal `unit_count > 0`. Ini murni angka judgment manual.
3. **Ada 4 representasi paralel "container mana"** yang tersebar di 4 tempat berbeda, dua di antaranya sudah diakui sendiri oleh codebase sebagai *legacy vs engine* (command `containers:audit`).
4. **Berbeda dengan Briefing, di sini TIDAK ada multiple source of truth untuk status READY.** Kelima konsumen membaca `summary_sufficient` yang sama. Ini titik yang justru sudah sehat.

Catatan penting: **seluruh tabel container saat ini kosong di produksi** (`container_readiness_sessions` = 0, `containers` = 0, `sea_containers` = 0, `rack_container_checks` = 0). Audit ini karena itu berbasis kode + data historis seeder, bukan data hidup.

---

## 1. Current Business Flow

```
Shipment (Office Admin)
    │
    ▼
Briefing Harian ──────────────► DailyBriefingGate ──┐
 (per depot, per hari)                              │
                                                    │
Container Readiness ──────────► summary_sufficient ─┤
 (GLOBAL, per hari, tanpa depot)                    │
    │                                               │
    │ container_numbers (JSON)                      ▼
    │                                        StuffingService
    ▼                                        ::checklist()
Container Allocation Workspace                      │
 (materialisasi baris `containers`)                 ▼
    │                                            Stuffing
    ▼                                               │
Unit.container_id  ◄── engine                       ▼
Unit.container_display ◄── legacy (jalur paralel)  Loading
                                                    │
                                                    ▼
                                                 Voyage
```

**Siapa membuat, kapan, siapa mengubah:**

| Entitas | Dibuat oleh | Kapan | Diubah oleh | Kapan selesai |
|---|---|---|---|---|
| `container_readiness_sessions` | FC, manual via Filament Create | Sekali per hari (kolom `session_date` **unique**) | FC via Edit | Tidak ada state "selesai" — hanya boolean `summary_sufficient` |
| `containers` (baris) | **Lazy**, otomatis saat `Container::resolveForSession()` dipanggil pertama kali | Saat FC melakukan alokasi di Container Allocation Workspace | `ContainerAllocationService`, `StuffingService` | `stuffing_status = ReadyLoading` |
| `units.container_id` | `ContainerAllocationService` | Saat alokasi unit → container | service yang sama | — |
| `units.container_display` | **Form Handover** di `OperationalTasks.php:790` | Saat FC input Handover Depo | form yang sama | — |

**Temuan alur:** tidak ada satu pun proses otomatis yang membuat `container_readiness_sessions`. Tidak ada observer, tidak ada scheduler, tidak ada turunan dari Shipment. Sepenuhnya bergantung FC mengingat untuk mengisi manual setiap hari.

---

## 2. Current Domain Map

Empat domain berbeda saat ini tinggal di dalam satu resource + satu tabel:

| Domain | Pertanyaan bisnis | Tempat tinggal sekarang |
|---|---|---|
| **A. Demand Planning** | "Hari ini butuh berapa container?" | `unit_count`, `container_need` |
| **B. Supply / Readiness** | "Tersedia berapa? Cukup?" | `container_available`, `gap`, `summary_sufficient` |
| **C. Container Registry** | "Container fisik mana saja (nomor + service)?" | `container_numbers` (JSON) — **ditambahkan 13 hari setelah tabel dibuat** |
| **D. Assignment / Stuffing** | "Unit mana masuk container mana? Sudah di-stuff?" | tabel `containers`, `units.container_id`, `units.container_display` |

**Domain A + B + C berada dalam SATU form dan SATU tabel.** Domain D sudah dipisah ke tabel sendiri (`containers`), tetapi **masih bergantung pada JSON di domain C** sebagai gerbang validasi (`Container::resolveForSession()` menolak nomor yang tidak terdaftar di `container_number_list`).

Bukti evolusi (bukan desain awal):
- Migration `2026_06_10_000002` — komentar tabel eksplisit: *"Satu baris per hari — demand planning harian"*. Hanya 5 angka + notes. **Tidak ada daftar container.**
- Migration `2026_06_23_000003` — menambahkan `container_numbers` dengan komentar *"Daftar nomor container fisik yang tersedia hari ini"*. Domain C masuk belakangan.
- Komentar di `ContainerReadinessSessionResource.php:25` masih menyebut form berisi *"Tanggal, Jumlah Unit, Kebutuhan Container, Container Tersedia, Catatan"* — **tidak menyebut Daftar Container sama sekali**. Docblock tertinggal dari sebelum domain C ditambahkan.

---

## 3. Dependency Graph

```
Shipment ──► Units
   │           │
   │           └─(TIDAK terhubung)──X──► unit_count   ← diinput MANUAL
   │
   └─(TIDAK terhubung)──X──► container_need           ← diinput MANUAL

container_need ─────┐
                    ├──► gap = available − need       (computed, stored)
container_available ┘         │
        ▲                     └──► summary_sufficient = available >= need
        │                                 │            (computed, stored)
   diinput MANUAL                         │
        ╎                                 ├──► ContainerSnapshotWidget
        ╎ (TIDAK ADA hubungan)            ├──► Dashboard FC
        ╎                                 ├──► OperationalTasks (Setup Hari Ini)
        ▼                                 ├──► MpReadinessMonitoring
container_numbers (JSON)                  └──► StuffingService::checklist()  ← GATE
        │                                          │
        ├──► todayContainerOptions()               ▼
        ├──► OperationalTasks:663 (dropdown)   Stuffing boleh dibuka
        ├──► ContainerAllocationWorkspace
        └──► Container::resolveForSession() ──► containers (baris)
                                                    │
                                                    ├──► units.container_id  (engine)
                                                    └──► stuffing_status

units.container_display (legacy) ──X── tidak terhubung ke containers
        ▲
        └── ditulis form Handover (OperationalTasks:790)
```

**Dua garis putus (`X`) adalah temuan inti:** `unit_count` dan `container_need` tidak punya jalur data dari Shipment/Units, dan `container_available` tidak punya jalur ke `container_numbers`.

---

## 4. Source of Truth Analysis

| Field | Source of Truth | Manual / Auto / Turunan | Catatan |
|---|---|---|---|
| `session_date` | Input FC | **Manual** | `unique()` → 1 baris/hari **global**. Tidak ada `depot_id` (kontras dengan Briefing yang per-depot). |
| `unit_count` | Input FC | **Manual** | Bisa diturunkan. Preseden sudah ada: `BriefingSession::getExpectedUnitAttribute()` dan `effectiveUnitSqlExpression()` sudah menghitung unit dari Shipment/Handover track. |
| `container_need` | Input FC | **Manual** | **Tidak ada formula** — dibuktikan di §5. |
| `container_available` | Input FC | **Manual** | **Redundan** terhadap `count(container_numbers)` — lihat §6. |
| `container_numbers` | Input FC (Repeater) | **Manual** | Source of truth **de facto** untuk container fisik: dipakai `Container::resolveForSession()` sebagai validasi, dropdown Planning Loading, dan Allocation Workspace. |
| `service` (per container) | Input FC | **Manual** | `ContainerAllocationType`: Regular / Rack. Ditambahkan pada CR-02 — baris lama tersimpan sebagai string polos tanpa service (ditangani `afterStateHydrated`). |
| `gap` | Dihitung | **Turunan (stored)** | `booted()::saving` → `available − need`. Disimpan **dan** ada accessor `getGapAttribute()` yang menghitung ulang bila kolom tidak di-select. Formula identik, jadi tidak berbahaya — tapi dua jalur untuk satu nilai. |
| `summary_sufficient` | Dihitung | **Turunan (stored)** | `booted()::saving` → `available >= need`. |

**Field yang sebenarnya dapat dihitung dari data lain:**
1. `unit_count` → dari Units/Shipment (infrastruktur perhitungan **sudah ada** di `BriefingSession`).
2. `container_available` → dari `count(container_numbers)`.
3. `gap` dan `summary_sufficient` → sudah turunan (benar).

---

## 5. Container Need — Audit Khusus

**Pertanyaan: berasal dari mana?**

Jawaban: **input manual murni.** Tidak ada formula, tidak ada turunan dari cargo plan, manifest, voyage, maupun jumlah unit.

Diuji secara empiris terhadap **72 baris data historis** di `ContainerReadinessBackfillSeeder` (Januari–Mei 2026):

| Uji | Hasil |
|---|---|
| `need == ceil(unit_count / 2)` | **31 / 72 = 43%** → bukan formula |
| Rasio `unit_count / container_need` | Bervariasi **1.00 – 3.00**, rata-rata **2.09** |
| Baris dengan `need = 0` padahal `unit_count > 0` | **2 baris** — `2026-02-12` (5 unit, need 0), `2026-02-13` (7 unit, need 0) |

Dua baris terakhir mematahkan **semua** kemungkinan formula berbasis unit: mustahil membutuhkan 0 container untuk 5–7 unit di bawah rumus apa pun. Ini mengonfirmasi `container_need` adalah **angka penilaian operasional manusia**, kemungkinan mempertimbangkan ukuran container, jenis muatan (Regular vs Rack), dan tujuan — variabel yang **tidak satu pun tersimpan di tabel ini**.

Konsekuensi: rasio unit-per-container yang sesungguhnya bergantung pada `service` (Regular vs Rack) — data yang **baru ada di `container_numbers` sejak CR-02**, dan tetap tidak dipakai untuk menghitung `need`.

---

## 6. Container Available — Audit Khusus

**Pertanyaan Anda: apakah `available` seharusnya = jumlah daftar container?**

Secara domain: **ya, seharusnya.** Secara implementasi: **tidak, sama sekali tidak terhubung.**

Bukti di `ContainerReadinessSessionResource::form()`:

```php
// Baris 83-89 — ANGKA manual
TextInput::make('container_available')
    ->label('Container Tersedia')          // ← label A
    ->helperText('Jumlah container yang tersedia / dikonfirmasi')

// Baris 96-98 — DAFTAR manual
Repeater::make('container_numbers')
    ->label('Container Tersedia')          // ← label IDENTIK
    ->helperText('Setiap container wajib diisi nomor dan service ...')
```

**Dua field berbeda dengan label yang sama persis dalam satu form.** Tidak ada `->live()`, tidak ada `afterStateUpdated`, dan `booted()::saving` hanya menghitung `gap`/`summary_sufficient` — tidak pernah menyinkronkan `container_available` dengan `count(container_numbers)`.

**Akibatnya:** FC bisa mengisi `container_available = 10` sementara hanya mendaftarkan 3 nomor container. Sistem akan menyatakan READY (jika `need <= 10`) dan **membuka gate Stuffing**, padahal hanya 3 container fisik yang benar-benar terdaftar dan bisa dipakai `Container::resolveForSession()`. Angka yang membuka gate dan daftar yang benar-benar bisa dipakai adalah dua hal yang sepenuhnya terpisah.

Tidak dapat diverifikasi terhadap data hidup karena tabel kosong; ini temuan struktural dari kode.

---

## 7. Current Container Readiness Logic (Status)

```php
// ContainerReadinessSession::booted()
$model->gap                = $model->container_available - $model->container_need;
$model->summary_sufficient = $model->container_available >= $model->container_need;

// getStatusAttribute()
return $this->summary_sufficient ? 'READY' : 'NOT READY';
```

**Kabar baik: TIDAK ada multiple source of truth di sini.** Berbeda total dengan temuan Briefing (BRF-STATUS-01 menemukan 8 implementasi READY berbeda). Kelima konsumen membaca kolom `summary_sufficient` yang sama:

| Konsumen | Cara baca |
|---|---|
| `ContainerSnapshotWidget:35` | `(bool) $row->summary_sufficient` |
| `Dashboard:195` | `(bool) $cRow->summary_sufficient` |
| `OperationalTasks:143` | `(bool) $container->summary_sufficient` |
| `MpReadinessMonitoring:511-512` | `$row->summary_sufficient ? 'READY' : 'NOT READY'` |
| `StuffingService:44` | `(bool) ($readinessSession?->summary_sufficient)` ← **GATE** |

Satu-satunya duplikasi minor: `gap` disimpan sebagai kolom **dan** punya accessor `getGapAttribute()` yang menghitung ulang bila kolom tidak ter-select. Formulanya identik sehingga tidak menghasilkan perbedaan nilai, tetapi tetap dua jalur untuk satu angka.

**Gap bisnis pada logika ini:** `summary_sufficient` hanya membandingkan dua angka yang **keduanya diketik manual**. Ia tidak memvalidasi bahwa container tersebut benar-benar ada, benar-benar terdaftar nomornya, atau benar-benar cocok service-nya. Secara historis, **0 dari 72 baris pernah bernilai NOT READY** — gate ini belum pernah sekalipun memblokir apa pun.

---

## 8. Current State Machine

**`ContainerReadinessSession` tidak memiliki state machine.** Tidak ada kolom status, tidak ada enum. Yang ada hanya boolean turunan `summary_sufficient`:

```
(baris dibuat) ──► summary_sufficient = (available >= need)
                        │
                   ┌────┴────┐
                   ▼         ▼
                 READY   NOT READY     ← bolak-balik bebas, tanpa transisi terkontrol
```

Tidak ada Draft / Closed / Completed / Cancelled. Tidak ada penutupan hari. Tidak ada `approved_at`. Baris dapat diedit atau **dihapus** kapan saja (`EditContainerReadinessSession` mengekspos `DeleteAction`), termasuk setelah stuffing berjalan berdasarkan data tersebut.

**State machine yang benar-benar hidup ada di domain D (`containers`), bukan di readiness:**

| Enum | Nilai | Status pemakaian |
|---|---|---|
| `ContainerStuffingStatus` | Ready → Stuffing → Full → ReadyLoading | ✅ **HIDUP** — transisi ditulis `StuffingService` (baris 71, 113, 126, 150) |
| `ContainerAllocationType` | Rack, Regular | ✅ **HIDUP** — tipe (bukan state), dipakai 4 file |
| `ContainerStructureStatus` | Good, Damaged, Leaking | ⚠️ Dipakai 2 file (jalur `RackContainerCheck`) |
| `ContainerStatus` | Draft, Stuffing, GateIn, OnShip, Completed, Cancelled | ❌ **MATI** — hanya muncul sebagai `$casts` di `SeaContainer.php:24`. Tidak ada satu pun kode yang membaca atau menulisnya. |

**`ContainerStatus` adalah state machine paling lengkap di seluruh modul container — dan sepenuhnya tidak terpakai.** Ia milik `SeaContainer`, yang hanya direferensikan oleh sesama model `Sea*` (`SeaBooking`, `SeaContainerCargo`, `SeaContainerEvent`) dan tidak tersentuh alur FC mana pun. Tabel `sea_containers` = 0 baris.

---

## 9. Current UI Analysis

**Jawaban: D — campuran ketiganya.**

Satu form `ContainerReadinessSessionResource::form()` memuat tiga domain sekaligus:

| Field di form | Domain sebenarnya |
|---|---|
| Tanggal | kerangka sesi |
| **Jumlah Unit** | **A — Planning** (berapa beban hari ini) |
| **Kebutuhan Container** | **A — Planning** (berapa yang dibutuhkan) |
| **Container Tersedia** (angka) | **B — Operational Readiness** (cukup atau tidak) |
| Catatan | bebas |
| **Daftar Container** (nomor + service) | **C — Container Registry**, dan de facto **prasyarat D (Assignment)** |

Alasan mengapa ini campuran, bukan satu domain:

1. **Ritme pengisian berbeda.** Planning (unit/need) diisi di awal hari sebagai perkiraan; Daftar Container diisi ketika container fisik benar-benar tiba. Dua momen berbeda dipaksa ke satu form yang hanya boleh ada satu per hari.
2. **Konsumen berbeda.** Angka Planning/Readiness dipakai dashboard & laporan bulanan; Daftar Container dipakai `Container::resolveForSession()`, dropdown Planning Loading, dan Allocation Workspace — semuanya di domain operasional.
3. **Dampak berbeda.** Salah mengisi `container_need` hanya mengubah badge. Salah mengisi Daftar Container **memblokir alokasi**: `resolveForSession()` melempar `DomainException` untuk nomor yang tidak terdaftar.
4. **Label bertabrakan.** Dua field bernama "Container Tersedia" adalah gejala langsung dari dua domain yang dijejalkan ke satu layar.
5. **Docblock resource sendiri** (baris 25) masih mendeskripsikan form versi lama tanpa Daftar Container — bukti tekstual bahwa domain C ditambahkan tanpa meninjau ulang identitas halaman.

---

## 10. Redundant Fields

| Field | Redundan? | Terhadap apa | Catatan |
|---|---|---|---|
| `container_available` | **Ya, kuat** | `count(container_numbers)` | Dua field satu label, tidak tersinkron. Angka inilah yang membuka gate, bukan daftarnya. |
| `unit_count` | **Ya, berpotensi** | Units via Shipment/Handover track | Infrastruktur perhitungan sudah ada di `BriefingSession` (`expected_unit`, `effectiveUnitSqlExpression`). Perlu keputusan definisi: "unit hari ini" = handover hari itu, atau seluruh unit sea aktif? Kedua angka berbeda. |
| `gap` | Sebagian | `available − need` | Sudah turunan, tapi disimpan **dan** punya accessor penghitung ulang. |
| `summary_sufficient` | Sebagian | `available >= need` | Sama seperti `gap` — turunan yang disimpan. Wajar untuk query SQL, tapi perlu diakui sebagai cache. |
| `units.container_display` | **Ya** | `units.container_id → containers.container_no` | Diakui sendiri oleh codebase sebagai *legacy* (command `containers:audit` / `containers:backfill`, bertanda "DATA-02"). Dua jalur tulis paralel yang masih aktif. |
| `sea_containers` (+ `ContainerStatus`) | **Ya, total** | — | Modul paralel yang tidak tersentuh alur FC. 0 baris. |

**Empat representasi paralel "container mana":**
1. `container_readiness_sessions.container_numbers` (JSON) — deklarasi/registry
2. `containers` (baris) — anotasi operasional + stuffing state
3. `units.container_display` (string) — **legacy**, ditulis form Handover
4. `units.container_id` (FK) — **engine**, ditulis `ContainerAllocationService`

Nomor 3 dan 4 sudah punya command audit + backfill khusus, artinya migrasi legacy→engine **sedang berjalan dan belum selesai**.

---

## 11. Business Rule Gap

| # | Gap | Bukti | Dampak |
|---|---|---|---|
| G1 | `container_available` tidak tersinkron dengan `container_numbers` | §6 | Gate Stuffing bisa terbuka dengan angka fiktif sementara daftar container nyata kosong/kurang |
| G2 | `container_need` tanpa formula & tanpa variabel penentu | §5 (43% match, 2 baris `need=0`) | Tidak dapat divalidasi, tidak dapat diaudit, sepenuhnya bergantung ingatan FC |
| G3 | `unit_count` manual padahal turunan tersedia | §4 | Angka bisa menyimpang dari kenyataan Shipment tanpa terdeteksi |
| G4 | Tidak ada `depot_id` — 1 baris/hari **global** | migration `session_date->unique()` | Multi-depot berbagi satu angka readiness. Briefing per-depot, Container Readiness tidak. Inkonsisten secara arsitektur. |
| G5 | Tidak ada state machine / penutupan hari | §8 | Baris dapat diedit **atau dihapus** setelah stuffing berjalan di atasnya |
| G6 | `summary_sufficient` tidak memvalidasi eksistensi container | §7 | Historis: **0 dari 72 baris** pernah NOT READY — gate belum pernah menahan apa pun |
| G7 | Tidak ada pembuatan otomatis / pengingat harian | §1 | Bila FC lupa mengisi, `StuffingService` memblokir stuffing tanpa penjelasan akar masalah |
| G8 | Dua jalur assignment aktif bersamaan (legacy vs engine) | §10 | `container_display` dan `container_id` bisa saling bertentangan; command audit khusus dibuat justru untuk mendeteksi ini |
| G9 | `ContainerStatus` (6 state) mati total | §8 | Schema & enum menyesatkan pembaca berikutnya |
| G10 | `service` (Regular/Rack) tidak dipakai menghitung apa pun | §5 | Satu-satunya variabel yang bisa menjelaskan rasio unit/container justru tidak masuk perhitungan |

---

## 12. Recommendation

Sesuai instruksi, ini **bukan** rencana implementasi — hanya kerangka keputusan yang perlu dijawab lebih dulu.

### Pertanyaan bisnis yang harus dijawab sebelum sprint implementasi

1. **Apakah Container Readiness milik depot atau global?** Ini keputusan paling fundamental (G4). Briefing per-depot; Container Readiness tidak. Salah satunya harus mengalah, dan jawabannya menentukan apakah `session_date` boleh tetap `unique()`.
2. **Apa definisi resmi "Jumlah Unit hari ini"?** Unit yang Handover hari itu, atau seluruh unit sea aktif? Dua angka ini berbeda dan keduanya sudah dihitung di tempat berbeda dalam sistem.
3. **Apa yang sebenarnya menentukan `container_need`?** Jika jawabannya melibatkan ukuran container / Regular vs Rack / tujuan, maka variabel itu belum tersimpan di mana pun — perlu diputuskan sebelum formula apa pun dibuat.
4. **Mana yang otoritatif: angka `container_available`, atau Daftar Container?** Keduanya tidak bisa terus hidup berdampingan tanpa sinkronisasi (G1).

### Arah arsitektur — tiga opsi, dengan konsekuensinya

**Opsi A — Biarkan satu modul, rapikan redundansi.**
Turunkan `container_available` dari `count(container_numbers)`, turunkan `unit_count` dari Units, sisakan `container_need` sebagai satu-satunya input manual.
*Konsekuensi:* perubahan paling kecil, G1 & G3 selesai. Tapi domain Planning dan Registry tetap dalam satu form dengan dua ritme pengisian berbeda (§9 poin 1) — akar masalah UX tidak hilang. Data historis `container_available` yang tidak sama dengan `count(numbers)` perlu diputuskan nasibnya.

**Opsi B — Pisahkan Planning dari Registry.**
`container_readiness_sessions` menyimpan Planning + Readiness (A + B); Daftar Container pindah ke entitas/tabel sendiri, dengan `container_available` menjadi hasil hitung dari situ.
*Konsekuensi:* domain jadi bersih dan dua ritme pengisian terpisah. Tapi ini menyentuh `Container::resolveForSession()`, `todayContainerOptions()`, Allocation Workspace, dan dropdown Planning Loading — dan `containers` sudah punya FK ke `container_readiness_session_id`. Perlu migration + backfill JSON→tabel.

**Opsi C — Jadikan Registry sumber tunggal, Planning jadi turunan.**
Daftar Container adalah fakta; `available = count(list)`, `unit_count` = turunan, `need` tetap manual.
*Konsekuensi:* paling dekat dengan realitas operasional dan menutup G1/G3/G6 sekaligus, karena READY jadi mustahil tanpa container nyata terdaftar. Tapi ini **mengubah arti historis `summary_sufficient`** yang sudah dipakai laporan bulanan (`MonthlyBriefingSummaryWidget`, `OperationalStatsWidget`, `MpReadinessMonitoring`) — sama seperti risiko yang teridentifikasi pada audit Briefing. Perlu keputusan: hitung ulang backfill, atau bekukan angka historis.

### Yang layak dikerjakan pada opsi mana pun

- **G1** (dua label "Container Tersedia") — minimal perbaiki label agar tidak identik; ini membingungkan terlepas dari opsi arsitektur yang dipilih.
- **G9** (`ContainerStatus` mati) dan modul `Sea*` — putuskan hidupkan atau hapus.
- **G8** (legacy `container_display`) — selesaikan migrasi DATA-02 yang sudah dimulai; command audit & backfill-nya sudah tersedia.
- **G5** — putuskan apakah baris readiness boleh dihapus setelah dipakai stuffing.

Catatan: karena seluruh tabel container **kosong di produksi saat ini**, ini adalah momen dengan biaya migrasi paling rendah untuk mengubah arsitektur — tidak ada data hidup yang perlu dipindahkan, hanya 72 baris historis di seeder.

---

## Daftar File yang Diaudit

**Inti:**
- `app/Models/ContainerReadinessSession.php`
- `app/Filament/FC/Resources/ContainerReadinessSessionResource.php`
- `app/Filament/FC/Resources/ContainerReadinessSessionResource/Pages/{Create,Edit,List}*.php`
- `database/migrations/2026_06_10_000002_create_container_readiness_sessions_table.php`
- `database/migrations/2026_06_23_000003_add_container_numbers_to_container_readiness_sessions.php`
- `database/seeders/ContainerReadinessBackfillSeeder.php`

**Domain Assignment / Stuffing:**
- `app/Models/Container.php`
- `app/Services/ContainerAllocation/ContainerAllocationService.php`
- `app/Services/Stuffing/StuffingService.php`
- `app/Filament/FC/Pages/ContainerAllocationWorkspace.php`

**Konsumen status:**
- `app/Filament/FC/Widgets/ContainerSnapshotWidget.php`
- `app/Filament/FC/Pages/Dashboard/Dashboard.php`
- `app/Filament/FC/Pages/OperationalTasks.php`
- `app/Filament/FC/Pages/MpReadinessMonitoring.php`

**Enum & state:**
- `app/Enums/{ContainerStuffingStatus,ContainerAllocationType,ContainerStatus,ContainerStructureStatus}.php`

**Legacy / paralel:**
- `app/Console/Commands/ContainersAuditCommand.php`
- `app/Console/Commands/ContainersBackfillCommand.php`
- `app/Models/{SeaContainer,SeaContainerCargo,SeaContainerEvent,RackContainerCheck}.php`

---

*Akhir audit CONT-01. Tidak ada perubahan kode, migration, UI, atau data dalam sprint ini.*
