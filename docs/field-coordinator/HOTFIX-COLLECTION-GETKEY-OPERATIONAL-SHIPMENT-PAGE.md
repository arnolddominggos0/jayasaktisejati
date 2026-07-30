# Hotfix — `Collection::getKey()` Crash After Update Track

**Status:** FIXED — root cause diidentifikasi, reproduksi terkonfirmasi, patch minimal diterapkan & tervalidasi (blade compile).
**Tanggal:** 23 Juli 2026

---

## 1. Lokasi Bug

**File:** `resources/views/filament/fc/shipments/partials/daftar-unit.blade.php`
**Baris:** 48 (sebelum patch), di dalam blok `@if ($isVehicleCargo)` (baris 42-53).

```php
$namedGroups     = $units->filter(fn($u) => ! blank($u->container_display))
                         ->groupBy('container_display')
                         ->sortKeys();
$unassignedGroup = $units->filter(fn($u) => blank($u->container_display));
$containerGroups = $namedGroups->merge(                     // ← baris yang crash
    $unassignedGroup->isNotEmpty() ? ['__UNASSIGNED__' => $unassignedGroup] : []
);
```

Dikonfirmasi lewat compiled view cache (`storage/framework/views/529e90255896f1e69ecde9bd8fb3b04d.php`, hash persis sama dengan stacktrace Anda) — file itu adalah hasil kompilasi PERSIS dari `daftar-unit.blade.php`, dan baris crash sesuai baris `->merge()` di atas.

---

## 2. Akar Penyebab

`$units` berasal dari `$shipment->units()->with('inspections')->get()` — sebuah `Illuminate\Database\Eloquent\Collection`. Memanggil `->filter()->groupBy('container_display')` pada Eloquent Collection **mempertahankan tipe Eloquent Collection untuk WADAH LUAR maupun setiap grup di dalamnya** (late static binding Laravel) — sehingga `$namedGroups` menjadi `Eloquent\Collection` yang **isinya bukan Unit model, melainkan Collection lain** (satu per nilai `container_display` yang berbeda).

`Illuminate\Database\Eloquent\Collection::merge()` adalah **override khusus** (bukan `merge()` dasar dari `Support\Collection`):

```php
// vendor/laravel/framework/.../Eloquent/Collection.php:367
public function merge($items)
{
    $dictionary = $this->getDictionary();   // memanggil ->getKey() pada SETIAP item YANG SUDAH ADA di $this
    foreach ($items as $item) {
        $dictionary[$this->getDictionaryKey($item->getKey())] = $item;  // ->getKey() pada setiap item yang di-merge-kan
    }
    return new static(array_values($dictionary));
}
```

Method ini **mengasumsikan setiap item — di kedua sisi — adalah Eloquent Model** (untuk dedup berdasarkan primary key). Karena item `$namedGroups` sendiri adalah Collection (bukan Model), `getDictionary()` crash di baris pertama `merge()`, sebelum sempat memproses `$unassignedGroup` sama sekali.

**Direproduksi persis** (in-memory, tanpa DB) — pesan & stack trace identik dengan laporan Anda:

```
BadMethodCallException: Method Illuminate\Database\Eloquent\Collection::getKey does not exist.
  ...\Eloquent\Collection.php(599): Collection->__call('getKey', ...)
  ...\Eloquent\Collection.php(369): Collection->getDictionary()
  Command line code(28): Collection->merge(Array)
```

Ini **BUKAN** kasus "Filament menerima Collection padahal mengharapkan Model" seperti dugaan awal — Infolist/ViewEntry sama sekali tidak terlibat dalam crash-nya. Murni bug tipe-Collection di dalam kode grouping Blade itu sendiri, yang mengeksekusi lengkap SEBELUM hasilnya sempat disodorkan ke `unit-card.blade.php` (yang barulah memanggil `$unit->getKey()` — itu sebabnya gejala akhirnya terlihat seperti "Model diharapkan, Collection diterima").

---

## 3. Kenapa Baru Muncul Sekarang

Bug ini **laten sejak lama** — bukan regresi dari Sprint CR-02 atau ST-01. `merge()` akan selalu crash begitu `$namedGroups` berisi ≥1 grup (yaitu begitu ada ≥1 unit dengan `container_display` terisi pada shipment cargo Vehicle), **terlepas dari isi `$unassignedGroup`**.

Ditelusuri: `container_display` hanya diisi lewat satu jalur — **Handover action** (`OperationalTasks.php:937-967`, form `container_display` per unit), dikonfirmasi oleh komentar di `ShipmentService.php:200`: *"FC assigns container_display via the Handover action."* Handover action inilah yang dipicu oleh **Update Track** ke status Handover.

Jadi urutannya: Update Track (Handover) → form mengisi `container_display` per unit untuk PERTAMA KALInya pada shipment tsb → redirect balik ke `OperationalShipmentPage` → Infolist render ulang → `daftar-unit.blade.php` sekarang punya `$namedGroups` terisi untuk pertama kali → bug laten yang sebelumnya tidak pernah tersentuh (karena `container_display` sebelumnya selalu kosong di titik ini) langsung meledak.

**Konfirmasi eksplisit — CR-02 (Container Service pada Container Readiness) TIDAK terlibat:** digrep seluruh penggunaan `container_number_list` (3 titik: `Container.php`, `OperationalTasks.php` Handover options, `ContainerAllocationWorkspace.php` — semuanya flat `string[]`, tidak pernah nested) dan `container_service_list` (0 konsumen di luar model itu sendiri — field baru ini belum dipakai di mana pun). Tidak ada irisan kode antara CR-02 dan `daftar-unit.blade.php`/`container_display` sama sekali — dua hal yang sepenuhnya berbeda (`container_display` adalah kolom lama free-text di `units`; `container_numbers`/`container_service_list` adalah kolom di `container_readiness_sessions`).

---

## 4. Patch Minimal

Satu baris ditambah (`->toBase()`), tidak ada logic lain yang berubah:

```php
// SEBELUM
$containerGroups = $namedGroups->merge(
    $unassignedGroup->isNotEmpty() ? ['__UNASSIGNED__' => $unassignedGroup] : []
);

// SESUDAH
$containerGroups = $namedGroups->toBase()->merge(
    $unassignedGroup->isNotEmpty() ? ['__UNASSIGNED__' => $unassignedGroup] : []
);
```

`->toBase()` mengubah `$namedGroups` menjadi `Illuminate\Support\Collection` (dasar) sebelum `merge()` dipanggil — sehingga yang dipakai adalah `merge()` dasar (`array_merge` asosiatif biasa), persis semantik yang memang dimaksud kode ini ("gabungkan grup bernama + grup unassigned"), bukan semantik "gabungkan dua koleksi Model, dedup by primary key" milik Eloquent. Item di dalam setiap grup (`$containerUnits`) **tetap** `Eloquent\Collection` berisi `Unit` model asli — tidak berubah, karena bug hanya ada di level WADAH LUAR, bukan di grup individual.

**Tidak ada workaround** (tidak ada try/catch menyembunyikan error, tidak ada perubahan struktur data yang dikirim ke view lain, tidak ada casting baru selain yang menyasar akar masalah persis).

---

## 5. Validasi

| Uji | Hasil |
|---|---|
| Reproduksi bug persis (in-memory, tanpa DB) — sebelum patch | ✅ Crash tereproduksi identik (pesan + 3 frame stack trace sama persis dengan laporan Anda) |
| Reproduksi ulang dengan `->toBase()` — sesudah patch | ✅ Tidak crash; `$containerGroups` → `Illuminate\Support\Collection`; setiap `$containerUnits` tetap `Eloquent\Collection<Unit>` berisi model `Unit` asli (bukan Collection) |
| `php -l` pada file yang dipatch | ✅ Bersih |
| `composer dump-autoload` | ✅ Sukses |
| `php artisan view:cache` (mengompilasi ULANG `daftar-unit.blade.php`) | ✅ Sukses tanpa error; cache baru dikonfirmasi mengandung `toBase()` |
| `php artisan view:clear` (kembali bersih) | ✅ Sukses |
| Audit silang CR-02 (`container_number_list`/`container_service_list`) | ✅ Tidak ada keterlibatan — dikonfirmasi lewat grep seluruh codebase |

**Belum tervalidasi:** eksekusi end-to-end sungguhan (klik Update Track → lihat halaman render) di environment nyata — konsisten dengan batasan `APP_ENV=production` yang berlaku di seluruh sprint sebelumnya. Reproduksi di atas memakai model in-memory (tanpa query DB) yang meniru persis struktur data asli (`Unit` dengan `container_display`), sehingga confidence tinggi tanpa perlu menyentuh database.

---

## Konfirmasi Batas

- ✅ Tidak mengubah workflow — hanya memperbaiki tipe Collection di satu baris.
- ✅ Tidak mengubah business logic — pengelompokan per container_display, urutan (named dulu, unassigned terakhir), dan isi tiap grup identik sebelum/sesudah patch.
- ✅ Tidak menyentuh `ShipmentTrack`/Update Track — Update Track hanyalah PEMICU (trigger) tidak langsung karena mengisi `container_display` untuk pertama kali; logic Track itu sendiri tidak disentuh.
- ✅ Tidak menyentuh Container Readiness / CR-02.
- ✅ Tidak ada workaround / tidak ada error yang disembunyikan.
