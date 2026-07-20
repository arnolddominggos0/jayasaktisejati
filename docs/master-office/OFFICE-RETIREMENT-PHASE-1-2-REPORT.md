# Office Retirement — Phase 1 & 2 Implementation Report

**Status:** SELESAI
**Tanggal:** 20 Juli 2026
**Scope:** Menghapus seluruh **dependency kode** terhadap `Office`. **Tidak** menyentuh tabel, kolom, FK, atau migration (itu Phase 3).
**Referensi:** [`AUDIT-OFFICE-LEGACY-RETIREMENT.md`](AUDIT-OFFICE-LEGACY-RETIREMENT.md)

---

## Hasil Ringkas

- **0 dependency kode aktif** terhadap `Office` tersisa (diverifikasi grep menyeluruh — hanya komentar & migration yang menyebut Office).
- **`php -l` bersih** pada 4 file yang diubah.
- **Validasi nyata** (dev DB, transaksi di-rollback): Create, Edit, Placeholder Cabang Asal, Armada prefill, Timeline mask, dan Origin resolver semuanya berperilaku identik dengan sebelumnya.
- **Constraint dipatuhi:** tabel `offices`, kolom `origin_office_id`/`destination_office_id`, dan seluruh migration **tidak disentuh**. Model `Office` **tidak dihapus** (dikosongkan menjadi shell inert, siap Phase 3).

---

## Daftar File yang Diubah (4)

| File | Perubahan |
|---|---|
| `app/Models/Shipment.php` | Hapus relasi `originOffice()`, hapus relasi `destinationOffice()`, hapus method mati `kpiBranchId()`. |
| `app/Models/Branch.php` | Hapus relasi orphan `offices()`. |
| `app/Models/Office.php` | Hapus relasi orphan `users()` & `branch()`; hapus import `HasMany` yatim; tambah docblock status "retired-pending-Phase-3". Model tetap ada (shell + `$fillable`). |
| `app/Filament/Resources/ShipmentResource.php` | (1) Hapus import yatim `use App\Models\Office;` · (2) Hapus docblock stale "Smart Origin by Office" · (3) `resolveTimelineMask()`: hapus `$destBr` + klausa `dest_branch_id_in` · (4) Hapus eager-load `destinationOffice:id,branch_id` · (5) Placeholder Cabang Asal: `originOffice?->name ?? branch?->name` → `branch?->name` · (6) Armada prefill: `origin_office_id ?? depot_id` → `depot_id`. |

---

## Perubahan per Scope Item

### 1. Import Yatim ✅
`ShipmentResource.php:23` `use App\Models\Office;` dihapus. Setelah Smart Origin, sudah tak ada token `Office::` di file ini → import murni yatim.

### 2. Dead Timeline Logic ✅
`resolveTimelineMask()`: `config('jss_timeline')['rules']` = `[]`, jadi `$destBr` (dari `destinationOffice->branch_id`) tak pernah dikonsumsi. Dihapus: variabel `$destBr`, parameter `use (..., $destBr)` pada closure, dan klausa `dest_branch_id_in`. Dimensi rule yang tetap hidup — `mode` & `branch_id_in`/`not_branch_id_in` (dari **Branch**, source of truth) — dipertahankan. **Output tidak berubah** (fallback profile tetap sama; divalidasi mengembalikan `standard_sea`).

### 3. `destinationOffice` Dependency ✅
Ketiga caller sudah mati (audit): `resolveTimelineMask` (scope #2), `kpiBranchId` (scope #6), eager-load (`ShipmentResource:1404`). Semua dihapus, lalu relasi `Shipment::destinationOffice()` dihapus. Tak ada caller tersisa.

### 4. `originOffice` Compatibility Layer ✅
Placeholder Cabang Asal disederhanakan ke `$record->branch?->name`. Karena `origin_office_id` tak pernah terisi, cabang `originOffice?->name` **selalu** null → perilaku identik. Relasi `Shipment::originOffice()` lalu dihapus.

### 5. Armada Prefill ✅
`$record->origin_office_id ?? $record->depot_id` → `$record->depot_id`. LHS selalu null → ekspresi lama pun selalu menghasilkan `depot_id`. Perilaku identik (divalidasi: `depot_id` diakses tanpa error).

### 6. Dead Method ✅
`Shipment::kpiBranchId()` — 0 caller (diverifikasi grep whole-repo) — dihapus seluruhnya.

### 7. Dead Relations ✅
`Branch::offices()` (tanpa caller), `Office::branch()` & `Office::users()` (orphan; `users` tak punya `office_id`) — semua dihapus. Diverifikasi via reflection: keenam symbol (`originOffice`, `destinationOffice`, `kpiBranchId`, `Branch::offices`, `Office::branch`, `Office::users`) → `method_exists` = `false`.

---

## Validasi Nyata (dev DB `jss_db`, transaksi rollback)

Shipment dibuat via model (`->save()` memicu hook `creating`+`saving` — jalur yang sama dengan Create Shipment), branch_id=1 (Jakarta):

| # | Uji | Hasil |
|---|---|---|
| 1 | **Create Shipment** | `code=JSS0726SH0001`, `origin_city_id=3` diturunkan benar dari Branch (Jakarta→city 3). Tak ada fatal dari relasi yang dihapus. |
| 2 | **Edit Shipment** | reload + re-save → `origin_city_id` tetap 3. Hook `saving` jalan bersih. |
| 3 | **Placeholder Cabang Asal** | `branch=Jakarta`, `originCity=JAKARTA` — output benar via jalur baru. |
| 4 | **Armada prefill** | `$record->depot_id` diakses tanpa error (null pada sampel ini — identik perilaku lama). |
| 5 | **Timeline mask** | `{show_planning:true, show_terminal_detail:true, show_legacy:false}` — tanpa fatal dari `destinationOffice` yang dihapus. |
| 6 | **resolveOriginCityFromUser(1)** | `{city_id:3, city_name:"JAKARTA", branch_name:"Jakarta"}` — derivasi Branch→City benar. |

Semua di-`rollBack()` → dev DB tidak berubah. Boot aplikasi penuh (Filament resources + model + enum) sukses saat tinker berjalan → tak ada error autoload/registrasi akibat import/method yang dihapus.

---

## Hasil Grep Akhir

Perintah: `grep -rn "<token>" app/ resources/ tests/ routes/ config/ database/seeders/ database/factories/` (di luar `docs/`, `vendor/`, `database/migrations/`).

| Token | Sisa di **kode aktif** | Keterangan |
|---|:---:|---|
| `originOffice` | **0** | Hanya muncul di 1 komentar penjelas (`ShipmentResource.php:578`). |
| `destinationOffice` | **0** | Hanya muncul di 1 komentar penjelas (`ShipmentResource.php:152`). |
| `Office::` | **0** | Tak ada satu pun pemanggilan statis. |
| `use App\Models\Office` | **0** | Import yatim sudah dihapus. |
| `kpiBranchId` | **0** | Method + seluruh referensi hilang. |
| `->offices` | **0** | Relasi Branch::offices() + pemanggilnya hilang. |
| `Office` (kata) | komentar saja | Semua sisa = nama role "Office Admin", nama ikon `heroicon-*-building-office*`, atau docblock — **bukan** model. |
| `origin_office_id` | **2 (sengaja)** | `Shipment.php:36` `$fillable`. Lihat "Dependency Tersisa". |
| `destination_office_id` | **2 (sengaja)** | `Shipment.php:37` `$fillable`. Lihat "Dependency Tersisa". |

---

## Dependency Office yang Masih Tersisa (Sengaja — Belum Bisa/Perlu Dihapus)

| Artefak | Alasan dipertahankan |
|---|---|
| Kolom `origin_office_id` & `destination_office_id` di **`$fillable`** (`Shipment.php:36-37`) | Ini **string nama kolom**, bukan referensi model `Office`. Kolomnya **masih ada** di tabel `shipments` (Phase 3 belum jalan), dan constraint sprint melarang menghapus kolom. Menghapus dari `$fillable` sekarang berisiko (mass-assign error bila ada import/command lama menyetelnya) tanpa manfaat. Akan dihapus **bersamaan** dengan drop kolom di Phase 3. |
| Model **`app/Models/Office.php`** (shell) | Constraint eksplisit: jangan hapus model. Dikosongkan dari relasi (kini hanya `$fillable`), diberi docblock penanda retirement. Dihapus di Phase 4. |
| **Migration** yang menyebut `offices`/`office_id` (`2025_08_15_*`, `2025_09_14_150000`, `2025_09_14_150720`) | Constraint: jangan ubah migration lama. Ini riwayat skema; tetap apa adanya. Migration backfill `2025_09_14_150720` sudah ber-guard `Schema::hasTable('offices')` sehingga aman saat tabel di-drop nanti. |
| Tabel `offices` + 2 FK inbound | Domain Phase 3 (Database Retirement), bukan sprint ini. |

**Tidak ada** dependency **kode logika** terhadap `Office` yang tersisa. Yang tersisa murni artefak database-layer yang memang dijadwalkan untuk Phase 3.

---

## Kesiapan Phase 3 (Database Retirement)

Codebase kini bersih dari `Office`. Phase 3 dapat dijalankan aman dengan urutan:
1. Lepas FK `shipments.origin_office_id` & `destination_office_id`.
2. Drop kolom `origin_office_id` & `destination_office_id` (sekaligus hapus 2 entri `$fillable`).
3. `Schema::dropIfExists('offices')`.
4. (Phase 4) Hapus `app/Models/Office.php`.

Tidak ada lagi kode yang akan rusak oleh langkah-langkah di atas — seluruh pembaca sudah dihapus di Phase 1 & 2 ini.
