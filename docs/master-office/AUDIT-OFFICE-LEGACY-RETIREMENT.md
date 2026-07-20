# Architecture Audit — Office Legacy Retirement

**Status:** AUDIT ONLY — tidak ada perubahan kode, migration, resource, atau refactor.
**Tanggal:** 20 Juli 2026
**Pendahulu:** [`AUDIT-CABANG-ASAL-SOURCE-OF-TRUTH.md`](AUDIT-CABANG-ASAL-SOURCE-OF-TRUTH.md), [`SMART-ORIGIN-MIGRATION-BLOCKED-SCHEMA-GAP.md`](SMART-ORIGIN-MIGRATION-BLOCKED-SCHEMA-GAP.md)
**Pertanyaan yang harus dijawab:** *Apakah `Office` masih supporting entity yang sah, atau sudah legacy yang siap dipensiunkan?*

---

## Jawaban Ringkas (Success Criteria)

**`Office` sudah menjadi legacy dan siap dipensiunkan sepenuhnya.** → **Option B.**

Bukti ringkas, semuanya diverifikasi langsung (grep menyeluruh + query database, bukan asumsi):

- **Tidak ada satu baris kode pun yang menulis** `origin_office_id` atau `destination_office_id`. Diverifikasi di seluruh `app/`, `database/`, `tests/`, `routes/`. Kolomnya hanya pernah diisi oleh migration backfill historis (sekali jalan, 14 Sep 2025).
- **Setiap pembacaan `Office` yang tersisa berada di jalur mati (dead path) atau murni presentasi historis** — tidak ada satu pun yang operasional.
- **`offices` = 0 baris**, dan **tidak ada satu pun shipment** dengan `origin_office_id`/`destination_office_id` non-null (0 dari 0 saat audit).
- **Tidak ada UI aktif** (Resource/RelationManager/Widget/Global Search/Navigation) yang menyentuh `Office`.
- Sejak Smart Origin selesai, bahkan `use App\Models\Office;` di `ShipmentResource` sudah menjadi **import yatim** (tidak ada token `Office::` lagi di file itu).

Satu-satunya alasan `Office` belum bisa dihapus **detik ini** adalah dua FK constraint fisik (`shipments.origin_office_id`, `shipments.destination_office_id` → `offices.id`) yang harus dilepas dulu secara berurutan — bukan karena ada fungsi bisnis yang masih berjalan.

---

## Current Usage Map (Diagram Dependency Office)

```
                          ┌─────────────────────────────┐
                          │       Office (model)         │
                          │  offices: 0 baris            │
                          └──────────────┬──────────────┘
                                         │
        ┌────────────────────────────────┼────────────────────────────────┐
        │ relasi keluar                   │ relasi ke Office (inbound)      │ relasi internal Office
        ▼                                 ▼                                 ▼
  Office::branch()                  Shipment::originOffice()          Office::users()
  BelongsTo(Branch)                 BelongsTo(Office,                 HasMany(User)
        │                            'origin_office_id')                    │
        │ TIDAK ADA CALLER                 │                                │ ORPHAN: users tak punya
        │ (unreachable — Office            │                                │ kolom office_id sama sekali
        │  hanya dimuat lewat 3            ▼                                │ → relasi tak pernah bisa
        │  relasi di kanan, tak ada  ShipmentResource:586                  │  meng-hydrate apa pun
        │  satu pun mengakses ->branch)   Placeholder 'Cabang Asal'         │
        ▼                            $record->originOffice?->name          ▼
     (mati)                          → DISPLAY only, fallback              (mati)
                                      historis; selalu null utk
                                      shipment buatan aplikasi
                                  ┌───────────────────────────────┐
                                  │  Shipment::destinationOffice() │
                                  │  BelongsTo(Office,             │
                                  │  'destination_office_id')      │
                                  └───────────────┬───────────────┘
                                                  │  3 caller, SEMUANYA dead path:
                     ┌────────────────────────────┼────────────────────────────┐
                     ▼                            ▼                            ▼
   ShipmentResource:156                Shipment:1314                ShipmentResource:1404
   resolveTimelineMask()               kpiBranchId()                eager load
   $destBr = destOffice->branch_id     destOffice->branch_id        'destinationOffice:id,branch_id'
     │                                   │                            │
     │ DEAD: config rules=[]             │ DEAD: kpiBranchId()        │ DEAD: memuat relasi yang
     │ → $destBr tak pernah dipakai      │ TIDAK ADA CALLER           │ selalu null, utk konsumen
     ▼                                   ▼ (+ selalu fallback ke      ▼ yang juga sudah mati
   (nilai dihitung, dibuang)              branch_id krn kolom null)   (waste eager-load minor)

                       ┌─────────────────────────────────────┐
                       │  origin_office_id (prefill Armada)   │
                       │  ShipmentResource:1837               │
                       │  origin_office_id ?? depot_id        │
                       │  LEGACY: LHS selalu null → selalu    │
                       │  pakai depot_id. Lihat Finding §4.    │
                       └─────────────────────────────────────┘
```

---

## Call Graph (Siapa Memanggil Siapa)

```
[Writers of office FK columns]
   (TIDAK ADA — 0 penulis di app/ database/ tests/ routes/)
        └─ hanya migration backfill historis 2025_09_14_150720 (sekali jalan, sudah lewat)

[Readers of Office / office relations]
   ShipmentResource::table()
        ├─ eager load 'destinationOffice:id,branch_id'  (ShipmentResource:1404)
        │       └─ konsumen: resolveTimelineMask() → dead (rules=[])
        ├─ Placeholder 'Cabang Asal' content()          (ShipmentResource:586)
        │       └─ $record->originOffice?->name  → display historis, null utk data baru
        ├─ column/action render                          (ShipmentResource:1702, 1740)
        │       └─ resolveTimelineMask($r)               (ShipmentResource:143)
        │               └─ optional($m->destinationOffice)->branch_id (ShipmentResource:156)
        │                       └─ dipakai oleh $matches() HANYA jika rule punya
        │                          'dest_branch_id_in' → config rules=[] → TAK PERNAH
        └─ action 'Buat Penugasan'                       (ShipmentResource:1837)
                └─ origin_office_id ?? depot_id → LHS selalu null → depot_id

   Shipment::kpiBranchId()                               (Shipment:1312)
        └─ destination_office_id ? destinationOffice->branch_id : null
                └─ CALLER: (TIDAK ADA — method tak pernah dipanggil di mana pun)

   Branch::offices()                                     (Branch:18)
        └─ CALLER: (TIDAK ADA — tak ada ->offices / with('offices') / load('offices'))

   Office::users()  / Office::branch()
        └─ CALLER: (TIDAK ADA — Office hanya dimuat via originOffice/destinationOffice,
                    dan keduanya cuma mengakses ->name / ->branch_id kolom, bukan relasi ini)
```

---

## Findings (per Scope)

### 1. Audit `destination_office_id`

| Pertanyaan | Jawaban |
|---|---|
| Masih pernah **ditulis**? | **Tidak.** 0 penulis di seluruh codebase (app/database/tests/routes). Hanya migration backfill historis `2025_09_14_150720` yang pernah mengisi, sekali jalan. |
| Hanya **dibaca**? | Ya — 3 titik baca, semuanya dead path (lihat call graph). |
| Hanya **relasi**? | Kolom + relasi `destinationOffice()`. |
| Dipakai **workflow aktif**? | **Tidak.** `kpiBranchId()` (satu-satunya pembaca semi-workflow) tak punya caller. |
| Hanya **data historis**? | Ya secara desain — tapi bahkan data historis pun kosong (0 shipment non-null). |
| Dipakai **export**? | **Tidak.** CSV export (`ListShipments::exportAction`) tidak menyentuh office; tak ada Office di kolom export. |
| Dipakai **report**? | Secara nominal via `kpiBranchId()` (KPI), tapi method itu tak dipanggil → efektif tidak. |
| Dipakai **placeholder**? | Tidak untuk destination; placeholder 'Cabang Asal' hanya memakai `originOffice` (origin), bukan destination. |

### 2. Audit `originOffice()`

Caller tunggal: **`ShipmentResource:586`** — di dalam `content()` Placeholder 'Cabang Asal', `$record->originOffice?->name ?? $record->branch?->name`.

| Kategori | Ada? |
|---|---|
| Workflow | Tidak |
| **Display** | **Ya** — hanya untuk menampilkan nama kantor pada record lama saat *edit* |
| Reporting | Tidak |
| Export | Tidak |
| Helper | Tidak |
| Historical | Ya — hanya relevan bila `origin_office_id` non-null, yang tak pernah terjadi untuk shipment buatan aplikasi |

**Fungsi bisnis?** **Tidak ada — murni presentasi, dan presentasi historis pula.** Sejak migrasi Smart Origin, shipment baru tak pernah mengisi `origin_office_id`, jadi cabang `originOffice?->name` selalu jatuh ke `branch?->name`. Relasi ini mempertahankan tampilan nama-kantor **hanya seandainya** ada baris lama dengan office terisi — dan saat ini tidak ada satu pun.

### 3. Audit `destinationOffice()`

Jangan diasumsikan sama dengan origin — dan memang **berbeda**: destination punya lebih banyak caller (3), tapi semuanya **dead**, sementara origin punya 1 caller yang *hidup tapi cuma display*.

Caller:
- **`ShipmentResource:156`** (`resolveTimelineMask`) → `$destBr`. **Dead**: dipakai hanya oleh rule ber-`dest_branch_id_in`, dan `config('jss_timeline')['rules'] = []` (kosong). Nilai dihitung lalu dibuang.
- **`ShipmentResource:1404`** eager load `destinationOffice:id,branch_id` → memberi makan `resolveTimelineMask` (yang dead). Waste eager-load minor.
- **`Shipment:1314`** (`kpiBranchId`) → **dead**: method tak punya caller, dan logikanya pun sudah fallback ke `branch_id` saat `destination_office_id` null (selalu).

**Fungsi bisnis?** **Tidak ada yang hidup.** Semua konsumen mati karena (a) config rules kosong, (b) method tak dipanggil, (c) kolom selalu null.

### 4. Audit Armada Prefill — `origin_office_id ?? depot_id`

Lokasi: `ShipmentResource:1837`, action **"Buat Penugasan"** (visible hanya untuk shipment mode Land aktif), memprefill `prefill[depot_id]` pada `ArmadaAssignmentResource::create`.

| Pertanyaan | Jawaban |
|---|---|
| Memang **Office**? | Secara nama kolom ya (`origin_office_id` → `offices.id`), **tapi dipakai untuk mengisi `depot_id`** — mencampur identitas Office dengan identitas Depot. |
| Sebenarnya **Depot**? | Ya, secara maksud. RHS fallback `$record->depot_id` mengonfirmasi yang diinginkan adalah **Depot**, bukan Office. |
| Hanya **fallback lama**? | Ya. `origin_office_id` selalu null (tak pernah ditulis) → ekspresi **selalu** mengevaluasi ke `$record->depot_id`. |
| **Legacy compatibility**? | Ya — ini peninggalan dari era saat Office diperlakukan sebagai lokasi asal fisik (pra-Depot). Sisi kiri `??` sudah mati; menghapusnya tidak mengubah perilaku apa pun. |

**Kesimpulan:** ini bukan penggunaan Office yang sah — ini bug laten/legacy yang tersembunyi oleh fakta bahwa LHS selalu null. Aman disederhanakan menjadi `$record->depot_id` saja (di sprint implementasi terpisah).

### 5. Audit Office Model — Klasifikasi `Office::`

| Kelas | Temuan |
|---|---|
| **Operational** | **KOSONG.** Tidak ada penggunaan `Office` di jalur workflow yang hidup. |
| **Display** | `ShipmentResource:586` (`originOffice?->name`) — satu-satunya, dan historis. |
| **Historical** | Kolom `origin_office_id`/`destination_office_id` + relasi `originOffice`/`destinationOffice`, disiapkan untuk membaca data lama. Data lamanya kosong. |
| **Compatibility** | `ShipmentResource:1837` (`origin_office_id ?? depot_id`) — fallback legacy yang LHS-nya sudah mati. |
| **Dead** | `Shipment::kpiBranchId()` (tak ada caller); `$destBr` di `resolveTimelineMask` (rules=[]); eager-load `destinationOffice` (1404); `Branch::offices()` (tak ada caller); `Office::users()` (orphan, tak ada `users.office_id`); `use App\Models\Office;` di `ShipmentResource:23` (import yatim, tak ada token `Office::`). |

Catatan: `Office::where(...)` (lookup Smart Origin lama) **sudah tidak ada** — dihapus di sprint Smart Origin. Tidak ada satu pun `Office::<static>` tersisa di seluruh `app/`.

### 6. Audit Office Relations

Model `Office` (`app/Models/Office.php`) punya 2 relasi:

| Relasi | Dipakai? | Status |
|---|---|---|
| `Office::branch()` — BelongsTo(Branch) | **Tidak** | Orphan. Office hanya di-hydrate lewat `originOffice`/`destinationOffice`, yang cuma mengakses `->name`/`->branch_id` (kolom), tak pernah `->branch` (relasi). |
| `Office::users()` — HasMany(User) | **Tidak** | **Orphan struktural**: tabel `users` **tidak punya kolom `office_id`** (dikonfirmasi — User model tak punya relasi/kolom office, migration users tak menambah office_id). Relasi ini akan query `users WHERE office_id = ?` terhadap kolom yang tak ada → tak pernah bisa dipakai. |

Relasi terbalik pada model lain:
- `Branch::offices()` — HasMany(Office): **tak ada caller**, orphan.
- `Shipment::originOffice()` / `destinationOffice()`: dipetakan di Findings §2–§3 (display-historis / dead).

**Eager loading yang tak lagi dibutuhkan:** `ShipmentResource:1404` `'destinationOffice:id,branch_id'` — memuat relasi yang selalu null untuk konsumen yang sudah mati.

### 7. Audit Database — tabel `offices`

| Aspek | Temuan (query langsung ke `jss_db`) |
|---|---|
| Jumlah data | **0 baris** |
| FK **masuk** (inbound) | `shipments.origin_office_id → offices.id`, `shipments.destination_office_id → offices.id` (keduanya nullable, `nullOnDelete`) |
| FK **keluar** (outbound) | `offices.branch_id → branches.id` (NOT NULL, `cascadeOnDelete`) |
| Masih dipakai constraint? | Ya secara fisik — 2 FK inbound masih ada di skema. Inilah satu-satunya penghalang teknis penghapusan tabel. |
| Masih target relasi aktif? | Tidak — semua relasi ke arahnya mati/historis (Findings §2–§6). |
| Seluruh row historical? | Tidak ada row sama sekali (0). Tidak ada data operasional maupun historis. |
| Masih punya fungsi operasional? | **Tidak.** |

### 8. Audit Filament

Diperiksa: Resource, Relation Manager, Form, Table, Widget, Action, Navigation, Global Search — di seluruh panel (Admin, FC, Customer, CMS).

- **OfficeResource**: tidak ada (tidak pernah ada — dikonfirmasi via git history di audit sebelumnya).
- **Relation Manager / Widget / Global Search / Navigation** untuk Office: tidak ada.
- **Form/Table/Action** yang menyentuh model `Office`: hanya `ShipmentResource` (Placeholder display :586, eager-load :1404, timeline mask :156, armada prefill :1837) — semuanya sudah dikategorikan mati/display-historis/legacy.
- Kemunculan string "office" di FC/Services/blade lain (`heroicon-*-building-office*`, komentar "Office Admin", docblock "office_admin") adalah **false positive** — nama ikon & nama role, bukan model `Office`.

**Kesimpulan:** **tidak ada UI aktif** yang bergantung pada `Office`.

### 9. Audit Business Domain

Berdasarkan implementasi (bukan opini): seluruh fungsi yang dulu diemban `Office` **sudah tergantikan**:

| Fungsi lama Office | Penggantinya sekarang | Bukti |
|---|---|---|
| Menentukan kota asal (Origin City) | **Branch → `Branch.city_id` → City** | Sprint Smart Origin; `resolveOriginCityFromUser()` kini baca `Branch::with('city')` |
| Scoping organisasi (cabang mana) | **Branch** (`branch_id` di users & shipments) | `User::effectiveBranchId()`, `ShipmentResource::getEloquentQuery()` filter `branch_id` |
| Lokasi fisik asal/tujuan operasional | **Depot** (`assigned_depot_id`, `depot_id`, `branch_mode_defaults`) | Action Armada memakai `depot_id`; workflow FC berbasis Depot |

Tidak tersisa satu pun fungsi bisnis yang **hanya** bisa dijawab oleh `Office`.

---

## Classification Matrix

| Component | Operational | Display | Historical | Compatibility | Legacy | Dead |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| `origin_office_id` (kolom) | — | — | ✓ (kosong) | — | ✓ | — |
| `destination_office_id` (kolom) | — | — | ✓ (kosong) | — | ✓ | — |
| `Shipment::originOffice()` | — | ✓ | ✓ | — | — | — |
| `Shipment::destinationOffice()` | — | — | — | — | — | ✓ |
| `Shipment::kpiBranchId()` | — | — | — | — | — | ✓ (no caller) |
| `resolveTimelineMask` `$destBr` (:156) | — | — | — | — | — | ✓ (rules=[]) |
| eager load `destinationOffice` (:1404) | — | — | — | — | — | ✓ |
| Placeholder `originOffice?->name` (:586) | — | ✓ | ✓ | — | — | — |
| Armada prefill `origin_office_id ??` (:1837) | — | — | — | ✓ | ✓ | ✓ (LHS null) |
| `Branch::offices()` | — | — | — | — | — | ✓ (no caller) |
| `Office::branch()` | — | — | — | — | — | ✓ (unreachable) |
| `Office::users()` | — | — | — | — | — | ✓ (orphan, no FK) |
| `use App\Models\Office;` (:23) | — | — | — | — | — | ✓ (import yatim) |
| tabel `offices` (0 baris) | — | — | ✓ (kosong) | ✓ (FK target) | — | — |

Tidak ada satu pun sel di kolom **Operational**. Itu inti dari keputusan ini.

---

## Risk Analysis

| Aksi (hipotetis — TIDAK dilakukan di sprint ini) | Yang akan rusak | Tingkat Risiko |
|---|---|---|
| **Hapus `Office` model** | `Shipment::originOffice()`, `destinationOffice()`, `Branch::offices()` akan merujuk kelas tak ada → fatal error saat file di-load **jika** relasi/import masih menyebutnya. Import yatim `ShipmentResource:23` juga. Semua caller relasi harus dibersihkan lebih dulu. | **Sedang** — bukan karena fungsi hilang, tapi karena referensi simbol harus dibereskan berurutan agar tak fatal. |
| **Hapus tabel `offices`** | 2 FK constraint inbound (`shipments.origin_office_id`, `destination_office_id`) akan mencegah `DROP TABLE` sampai kolom/FK-nya dilepas dulu. Migration historis `2025_09_14_150720` sudah ber-guard `Schema::hasTable('offices')`, jadi `migrate:fresh` tetap aman. | **Rendah** (data 0, tak ada pembaca hidup), asalkan **urutan** benar: lepas FK → drop kolom → drop tabel. |
| **Hapus kolom `origin_office_id`** | Placeholder display `originOffice?->name` (:586) & armada prefill LHS (:1837) kehilangan sumber — tapi keduanya sudah selalu null → perilaku tak berubah. Relasi `originOffice()` jadi menunjuk kolom tak ada → harus dihapus berbarengan. | **Rendah** — tak ada perilaku hidup yang bergantung; hanya perlu bereskan relasi + 2 pemakai display/legacy bersamaan. |
| **Hapus kolom `destination_office_id`** | `resolveTimelineMask` `$destBr`, `kpiBranchId`, eager-load :1404 kehilangan sumber — **semuanya sudah dead**, jadi tak ada perilaku berubah. Relasi `destinationOffice()` harus dihapus berbarengan. **Catatan:** kapabilitas config `dest_branch_id_in` di timeline rule-engine akan hilang — tapi kapabilitas itu **sudah tak berfungsi** (selalu match null), jadi bukan kehilangan nyata. | **Rendah** — perlu penyesuaian di `resolveTimelineMask`/`kpiBranchId` agar tak merujuk relasi terhapus, tapi output tak berubah. |

**Risiko keseluruhan penghapusan: Rendah**, dengan syarat **urutan eksekusi** dijaga (bersihkan referensi kode → lepas FK/kolom → drop tabel/model). Tidak ada risiko kehilangan data (0 baris) dan tidak ada risiko kehilangan fungsi (0 pemakai operasional).

---

## Recommendation

### ✅ Option B — Office sudah dapat dipensiunkan

Seluruh bukti (Usage Map, Call Graph, Classification Matrix, Risk Analysis) menunjuk satu arah: `Office` tidak lagi mengemban fungsi bisnis apa pun. Semua perannya sudah pindah ke **Branch** (identitas cabang + kota via `city_id`), **City** (kota), dan **Depot** (lokasi fisik operasional). Yang tersisa hanyalah kolom kosong, relasi orphan, dan satu pemakaian display historis yang datanya pun tidak ada.

**Option A (pertahankan sebagai Supporting Entity) ditolak** karena tidak ada bukti implementasi yang mendukungnya: tidak ada workflow, tidak ada UI, tidak ada data, tidak ada pembaca operasional. Mempertahankannya hanya memelihara ambiguitas ("apakah origin ditentukan Office atau Branch?") yang justru baru saja diselesaikan oleh sprint Smart Origin.

### Roadmap Retirement (untuk sprint implementasi terpisah — BUKAN sekarang)

**Phase 1 — Hapus dependency mati (aman, tanpa risiko perilaku).**
- Hapus import yatim `use App\Models\Office;` (`ShipmentResource:23`).
- Hapus `Shipment::kpiBranchId()` (tak ada caller) **atau** sederhanakan agar tak menyentuh `destinationOffice` (langsung ke `branch_id`).
- Hapus perhitungan `$destBr` dari `resolveTimelineMask` + eager-load `destinationOffice:id,branch_id` (`ShipmentResource:156, 1404`) — keduanya dead karena `rules=[]`.
- Hapus `Branch::offices()` (orphan) dan relasi `Office::users()`/`Office::branch()` yang tak terpakai.
- Sederhanakan armada prefill (`ShipmentResource:1837`) menjadi `$record->depot_id` (LHS `origin_office_id` selalu null).

**Phase 2 — Migrasikan compatibility layer yang tersisa.**
- Placeholder display 'Cabang Asal' (`ShipmentResource:586`): hapus cabang `originOffice?->name`, sisakan `branch?->name` (sudah jadi perilaku faktual hari ini). Setelah ini, **tidak ada lagi pembaca `Office` sama sekali** di kode.
- Hapus relasi `Shipment::originOffice()` & `destinationOffice()`.
- Konfirmasi ulang (grep) 0 referensi `Office` tersisa sebelum lanjut.

**Phase 3 — Migration database (urutan wajib dijaga).**
1. Lepas FK `shipments.origin_office_id` & `destination_office_id`.
2. Drop kolom `origin_office_id` & `destination_office_id` dari `shipments`.
3. `Schema::dropIfExists('offices')`.
- Sediakan `down()` yang membangun ulang tabel + kolom + FK (reversibilitas), meniru pola guard `Schema::hasTable` yang sudah dipakai migration `2025_09_14_150720`.

**Phase 4 — Retirement penuh.**
- Hapus `app/Models/Office.php`.
- Hapus/anotasi migration lama sebagai historis (biarkan file migration asli demi integritas riwayat; jangan edit yang sudah ter-deploy).
- Perbarui dokumen `docs/master-office/*` menjadi status "RETIRED", tautkan ke commit retirement.
- Verifikasi: `php artisan migrate:fresh` bersih, test suite hijau, tidak ada referensi `Office` tersisa.

Setiap fase berdiri sendiri dan bisa di-review terpisah; Phase 1–2 murni kode (reversible via VCS), Phase 3 baru menyentuh skema.

---

## Lampiran — Bukti yang Diperiksa

- **Grep menyeluruh** (`app/`, `database/`, `tests/`, `routes/`, `resources/`, `config/`): pemetaan setiap token `Office`, `origin_office_id`, `destination_office_id`, `originOffice`, `destinationOffice`, `offices`.
- **Query database `jss_db`** (read-only): `Office::count()` = 0; `Shipment` non-null office FK = 0/0; FK inbound/outbound via `information_schema`.
- **File kunci:** `app/Models/Office.php`, `app/Models/Branch.php`, `app/Models/Shipment.php` (1059-1087 relasi, 1312-1323 `kpiBranchId`), `app/Filament/Resources/ShipmentResource.php` (23, 143-179, 586, 1404, 1837), `config/jss_timeline.php` (`rules => []`), `database/migrations/2025_08_15_075624_create_offices_table.php`, `database/migrations/2025_09_14_150000_create_shipments_table.php`.
- **Negatif yang dikonfirmasi:** tak ada `users.office_id`; tak ada OfficeResource/RelationManager/Widget/GlobalSearch/Navigation; tak ada Office di FC/Customer/CMS panel, Services, Exports, AppSheet, tests, factories, blade.
