# Office Retirement — Phase 3 & 4 Implementation Report (FINAL)

**Status:** SELESAI — Office resmi dipensiunkan dari arsitektur Jaya Sakti Sejati.
**Tanggal:** 20 Juli 2026
**Scope:** Database Retirement (drop FK, kolom, tabel) + hapus model. Tahap terakhir migrasi Office → Branch.
**Pendahulu:** [`OFFICE-RETIREMENT-PHASE-1-2-REPORT.md`](OFFICE-RETIREMENT-PHASE-1-2-REPORT.md), [`AUDIT-OFFICE-LEGACY-RETIREMENT.md`](AUDIT-OFFICE-LEGACY-RETIREMENT.md)

---

## Hasil Ringkas

- **Schema:** kolom `shipments.origin_office_id` & `destination_office_id` **dihapus**, 2 FK constraint **dilepas**, tabel `offices` **di-drop**. Zero data loss (0 baris offices, 0 shipment ber-FK non-null saat retirement).
- **Model:** `app/Models/Office.php` **dihapus**; autoloader di-`dump-autoload` ulang.
- **`$fillable`:** entri `origin_office_id` / `destination_office_id` **dihapus** dari `Shipment`.
- **Validasi nyata (dev DB):** Create, Edit, List/Detail, Armada Assignment, Smart Origin, Timeline, KPI — semua berjalan normal (transaksi di-rollback, dev DB tak berubah).
- **Reversibilitas:** `migrate:rollback` → `migrate` diuji pada Postgres nyata; `down()` merestore penuh, `up()` men-drop lagi.
- **`migrate:fresh` dari nol:** diuji pada database throwaway terpisah → seluruh rantai migrasi (buat offices → buat shipments+FK → … → drop) selesai tanpa error. Dev DB tidak disentuh.
- **`php -l`:** bersih pada seluruh file yang diubah.

---

## Migration

**Nama:** `database/migrations/2026_07_20_110000_drop_offices_table_and_office_columns_from_shipments.php` (baru)

**Perubahan schema (`up()`):**
1. `shipments`: `dropConstrainedForeignId('origin_office_id')` — lepas FK `shipments_origin_office_id_foreign` + drop kolom.
2. `shipments`: `dropConstrainedForeignId('destination_office_id')` — lepas FK `shipments_destination_office_id_foreign` + drop kolom.
3. `Schema::dropIfExists('offices')` — drop tabel.

**`down()` (reversibel penuh):** recreate tabel `offices` (persis mirror `2025_08_15_075624_create_offices_table` — kolom `id, code, name, city, address, branch_id, timestamps`), lalu re-add kedua kolom FK nullable `nullOnDelete` (mirror `create_shipments_table`). Semua langkah ber-guard `Schema::hasColumn`/`hasTable` agar idempotent.

**Prinsip:** migration lama **tidak diubah** (constraint). Pada `migrate:fresh`, migration lama tetap membuat offices + kolom FK, lalu migration baru ini (timestamp terakhir) menghapusnya — pola aditif standar Laravel.

---

## File yang Diubah

| File | Perubahan |
|---|---|
| `database/migrations/2026_07_20_110000_drop_offices_table_and_office_columns_from_shipments.php` | **Baru.** Migration drop FK+kolom+tabel, dengan `down()` lengkap. |
| `app/Models/Shipment.php` | Hapus 2 entri `$fillable`: `'origin_office_id'`, `'destination_office_id'`. Tidak ada perubahan lain. |
| `vendor/composer/*` (autoload) | `composer dump-autoload` — regenerasi classmap agar entri `Office.php` yang terhapus tidak lagi direferensikan. |

*(File dari Phase 1 & 2 — relasi/method/import Office — sudah bersih sebelum sprint ini; tidak disentuh lagi.)*

## File yang Dihapus

- `app/Models/Office.php` (via `git rm -f`).

## Database yang Dihapus

- Tabel `offices`
- Kolom `shipments.origin_office_id` (+ FK `shipments_origin_office_id_foreign`)
- Kolom `shipments.destination_office_id` (+ FK `shipments_destination_office_id_foreign`)

---

## Validation (Pengujian Nyata — dev DB `jss_db`)

### A. Schema setelah migrasi
```
offices table exists?            GONE (ok)
shipments.origin_office_id?      GONE (ok)
shipments.destination_office_id? GONE (ok)
Office model class exists?       GONE (ok)
shipments table exists?          yes (intact)
```

### B. Fitur (transaksi, di-rollback — dev DB tak berubah)
| # | Uji | Hasil |
|---|---|---|
| 1 | **Create Shipment** | `code=JSS0726SH0001`, `origin_city_id=3` (Jakarta) — Smart Origin Branch→City benar. |
| 2 | **Edit Shipment** | reload + re-save → `origin_city_id=3` tetap. |
| 3 | **List / Detail** | `branch=Jakarta`, `originCity=JAKARTA`, `status=draft`. |
| 4 | **Armada Assignment prefill** | `$record->depot_id` diakses tanpa error. |
| 5 | **Timeline** | `resolveTimelineMask` → `{show_planning:true,show_terminal_detail:true,show_legacy:false}` tanpa fatal. |
| 6 | **Smart Origin** | `resolveOriginCityFromUser(1)` → `{city_id:3,city_name:"JAKARTA",branch_name:"Jakarta"}`. |
| 7 | **KPI** | `kpiBranchId` sudah tak ada; `isManadoKpiTarget()` → `true` (berfungsi). |

### C. Reversibilitas (Postgres nyata)
```
migrate:rollback → offices restored? YES | origin_office_id restored? YES | destination_office_id restored? YES
migrate (up lagi) → offices gone? YES | columns gone? YES
```

### D. `migrate:fresh` dari nol (database throwaway `jss_office_retire_check`, lalu di-drop)
Seluruh rantai migrasi berjalan `DONE` tanpa error, termasuk `create_offices_table` → `create_shipments_table` (dengan kolom FK) → … → `2026_07_20_110000_drop_offices...` di urutan terakhir. End-state temp DB: `offices` gone, kedua kolom gone, `shipments` intact. Dev DB **tidak disentuh** selama pengujian ini.

---

## Final Grep

Token entitas Office (`Office::`, `originOffice`, `destinationOffice`, `origin_office_id`, `destination_office_id`, `App\Models\Office`) di seluruh `app/`:

```
[COMMENT] ShipmentResource.php:152  // destinationOffice->branch_id, but destination_office_id is never
[COMMENT] ShipmentResource.php:578  // ...Relasi originOffice()
[COMMENT] ShipmentResource.php:579  // sudah dihapus — origin_office_id tidak pernah terisi lagi,
[COMMENT] ShipmentResource.php:1833 // `origin_office_id ??` prefix — origin_office_id is never
```
→ **4 baris, seluruhnya komentar penjelas. ZERO runtime dependency.**

- `$fillable` office columns: **NONE** (bersih).
- `app/Models/Office.php`: **DELETED**.
- `Office::` / `App\Models\Office` / `new Office`: **ZERO** di kode aktif.

**Yang boleh & memang tersisa (sesuai spesifikasi):**
- **Migration historis:** `2025_08_15_075624_create_offices_table`, `2025_09_14_150000_create_shipments_table`, `2025_09_14_150700`, `2025_09_14_150720` (backfill, ber-guard `hasTable`) — tidak diubah.
- **Migration retirement baru:** `2026_07_20_110000_drop_offices...`.
- **Komentar & dokumentasi** historis di `docs/master-office/`.
- **Role `office_admin`** (`isOfficeAdmin()`/`isOfficeUser()` di `User` + pemanggil) — ini taksonomi **role**, bukan entitas Office. Tidak berkaitan, tetap.
- **Konten alamat kantor fisik** di landing page publik & `config/contact.php` ("Our Office", "Branch Offices") — teks, bukan model.

---

## Runtime Architecture (Setelah Office Retirement)

```
  ORGANISASI                         LOKASI OPERASIONAL
  ──────────                         ──────────────────
  User                               Shipment
    │ effectiveBranchId()              │ assigned_depot_id / depot_id
    ▼                                  ▼
  Branch  ◄──────────────┐           Depot
    │ branch.city_id     │ branch_id   (satu-satunya representasi
    ▼                    │             lokasi operasional)
  City                   │
   (administratif)       └─── Shipment.branch_id
    ▲                          (satu-satunya representasi organisasi
    │                           pada shipment)
    │ origin_city_id (derived)
    │
  Shipment ── originCity() ──► City
           └─ destinationCity() ──► City

  ❌ Office — TIDAK ADA lagi di runtime, schema, maupun model.
```

Alur runtime kini persis sesuai target sprint:
```
User → Branch → Branch.city_id → City
Shipment → Depot
Shipment → Branch
```
Tidak ada satu pun Office di jalur runtime.

---

## Success Criteria — Checklist

| Kriteria | Status |
|---|---|
| Office tidak ada di runtime | ✅ (grep: 0 kode aktif) |
| Office tidak ada di schema aktif | ✅ (tabel + 2 kolom + 2 FK di-drop) |
| Office tidak ada di model | ✅ (`Office.php` dihapus) |
| Tidak ada dependency aktif terhadap Office | ✅ (hanya komentar/migration historis) |
| Seluruh fitur Shipment berjalan normal | ✅ (validasi A–B) |
| `migrate:fresh` tanpa error | ✅ (validasi D, temp DB) |
| `php -l` bersih pada file yang diubah | ✅ |
| Migration reversibel | ✅ (validasi C) |

**Office resmi dipensiunkan.** Branch = organisasi, City = administratif, Depot = lokasi operasional.
