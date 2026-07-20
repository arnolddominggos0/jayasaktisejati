# Smart Origin Migration (Office → Branch) — Implementation Halted: Schema Gap

**Status:** ✅ RESOLVED (20 Juli 2026, sprint lanjutan hari yang sama). Opsi A (tambah `branches.city_id`) dipilih dan sudah diimplementasikan — lihat migration `2026_07_20_100000_add_city_id_to_branches_table.php`, `Branch::city()`, dan `ShipmentResource::resolveOriginCityFromUser()` yang baru. Dokumen di bawah ini dipertahankan apa adanya sebagai catatan historis kenapa implementasi sempat berhenti dan opsi apa saja yang dipertimbangkan.
**Tanggal laporan awal:** 20 Juli 2026
**Terkait:** [`AUDIT-CABANG-ASAL-SOURCE-OF-TRUTH.md`](AUDIT-CABANG-ASAL-SOURCE-OF-TRUTH.md) (audit sebelumnya)

---

## Kenapa berhenti

Sprint ini secara eksplisit menetapkan kondisi berhenti: *"Jika ... branches belum memiliki relasi atau atribut yang cukup untuk menentukan origin_city_id ..., jangan membuat workaround. Hentikan implementasi dan laporkan kebutuhan perubahan skema tersebut terlebih dahulu."*

Kondisi itu **terbukti benar**. Diverifikasi langsung ke schema dan database:

```php
// app/Models/Branch.php — SELURUH isi model
protected $fillable = ['code', 'name'];   // tidak ada city_id, tidak ada city
```

```php
// database/migrations/2025_08_14_111824_create_branches_table.php — SATU-SATUNYA migration branches
$table->id();
$table->string('code', 10)->unique();
$table->string('name', 100);
$table->timestamps();
// tidak pernah ada migration susulan yang menambah kolom apa pun ke branches
```

`branches` tidak punya `city_id`, tidak punya kolom `city`, dan tidak punya relasi apa pun ke `City`. Tanpa ini, tidak mungkin mengganti `Office::where('branch_id', ...)->value('city')` dengan turunan `Branch → City` yang valid — apa pun yang saya tulis untuk "menurunkan" city dari Branch hari ini **harus** mengarang ulang mekanisme baru dari nol, yaitu persis definisi *workaround* yang dilarang eksplisit oleh sprint ini.

### Jalur alternatif yang sudah dicek — dan kenapa semuanya bukan solusi sah

Sebelum menyimpulkan "tidak ada mekanisme", tiga kemungkinan jalur lain diperiksa lebih dulu:

| Jalur dicoba | Hasil pengecekan |
|---|---|
| `branch_mode_defaults` (Branch → Depot) lalu Depot → City | Depot **tidak punya** `city_id`/`city` sama sekali (`fillable`: `code, name, mode, port_id, service_types, address, branch_id, coordinator_user_id`). Dan tabel `branch_mode_defaults` sendiri **kosong** — `BranchModeDefault::count() = 0` (dicek langsung ke DB). Menggunakan jalur ini butuh dua lapis derivasi baru yang sama-sama tidak punya data. |
| `Branch.name` dicocokkan langsung ke `City.name` (skip Office, tapi tetap string-match) | Tidak ada satu pun kode yang sudah melakukan ini hari ini (dicek lewat pencarian pola `City::where(...name...branch...)` di seluruh `app/` — nihil). Membangunnya sekarang berarti menciptakan mekanisme baru, dan tetap mewarisi kelemahan struktural yang sama persis dengan pendekatan Office lama (pencocokan string tanpa foreign key, rentan typo/kapitalisasi) — memindahkan utang teknis, bukan menyelesaikannya, persis yang diminta untuk dihindari. |
| Bukti historis: apakah pernah ada pemetaan Branch→City yang berhasil? | `database/seeders/JanuariDataSeeder.php:87` — komentar seeder data demo secara eksplisit **mengarang manual**: *"branch_id=1 (Jakarta), origin_city_id=3 (JAKARTA), destination_city_id=1 (MANADO)"*. Ini justru bukti tambahan: bahkan data seed pun harus di-hardcode manual per baris karena tidak pernah ada mekanisme relasional Branch→City yang bisa dipakai otomatis. |

**Kesimpulan: gap skema ini nyata, bukan kekeliruan baca kode.** Diagram target sprint (`User → Branch → Shipment.branch_id`) sudah benar dan sudah *terpenuhi* untuk menentukan Branch itu sendiri — tapi Validation sprint ini juga menuntut *"Origin City tetap diturunkan dengan benar menggunakan source of truth baru"*, dan langkah itu butuh `Branch → City`, yang secara skema belum ada jalurnya sama sekali.

---

## Apa yang SUDAH dikerjakan (aman, tidak menyentuh kode)

Scope 1 ("Audit seluruh penggunaan Office") selesai dikerjakan penuh — ini murni investigasi, tidak bergantung pada keputusan skema, dan langsung memenuhi **Deliverable #6**.

### Inventaris lengkap seluruh `Office::` dan `*_office_id` di `app/`

| Lokasi | Baris | Kategori | Status yang direkomendasikan |
|---|---|---|---|
| `Shipment.php` — `creating` hook | 161-162 | Smart Origin (fallback `origin_office_id`→`branch_id`) | **Hapus** — dalam scope Sprint ini |
| `Shipment.php` — `creating` hook | 177 | Smart Origin (`Office.city` → City derivation) | **Ganti** — dalam scope, tapi *diblokir* gap skema |
| `Shipment.php` — `saving` hook | 254-255 | Smart Origin (duplikat persis dari 161-162) | **Hapus** — dalam scope |
| `Shipment.php` — `saving` hook | 263 | Smart Origin (duplikat persis dari 177) | **Ganti** — dalam scope, *diblokir* |
| `ShipmentResource.php` — `resolveOriginCityFromUser()` | 110 | Smart Origin (query utama) | **Ganti** — dalam scope, *diblokir* |
| `ShipmentResource.php` — warning & helper text | 561-599 | Wording "Master Office" | **Ganti wording** — dalam scope, tapi menunggu mekanisme pengganti supaya tidak menyembunyikan kegagalan derivasi secara diam-diam |
| Komentar `DOMAIN-03` (`ShipmentResource.php:544-547`) | 544-547 | Dokumentasi usang ("Office sebagai Source of Truth") | **Perbarui** — dalam scope, tapi lebih baik diperbarui bersamaan dengan implementasi nyata, bukan sebelum mekanismenya ada (supaya komentar tidak mendeskripsikan sesuatu yang belum benar-benar berjalan) |
| `Shipment.php` — `originOffice()` relasi | 1069-1071 | Relasi model, **dipakai untuk tampilan** (`ShipmentResource.php:578`, nama office di placeholder) | **Pertahankan** — bukan logic Smart Origin, murni label tampilan; masih dipakai |
| `Shipment.php` — `$fillable` | 36 | Kolom `origin_office_id` | **Pertahankan** — konstrain eksplisit: jangan hapus kolom/model |
| `Branch.php` — `offices()` relasi | 17-20 | Relasi model umum | **Pertahankan** — bukan bagian alur Smart Origin, relasi generik |
| `ShipmentResource.php` — prefill Buat Penugasan | 1829 | `$record->origin_office_id ?? $record->depot_id` — dipakai sebagai fallback nilai `prefill[depot_id]` pada action "Buat Penugasan" Armada | **Pertahankan, JANGAN disentuh** — ini **bukan** Smart Origin sama sekali, tujuannya beda total (prefill form Armada Assignment). Di luar scope sprint ini. |

### Eksplisit di luar scope — sisi Destination (tidak disentuh sama sekali)

`destination_office_id`, `destinationOffice()` (`Shipment.php:37, 1095-1097, 1324`) — sprint ini eksplisit hanya soal **Origin**. Tidak diaudit lebih dalam, tidak disentuh.

**Ringkasan Deliverable #6:** di luar flow Smart Origin, `Office` masih dipakai secara sah di dua tempat: (1) label tampilan nama kantor di placeholder Cabang Asal (`originOffice()` relation), dan (2) fallback prefill `depot_id` pada aksi Buat Penugasan Armada. Keduanya tidak berkaitan dengan penentuan Origin/City, sehingga model `Office`, tabel `offices`, dan relasi-relasinya **tidak boleh dihapus** — sejalan dengan Constraint #7 sprint ini.

---

## Yang BELUM dikerjakan (sengaja, menunggu keputusan)

Scope 2, 3 (bagian derivasi), 4, dan 6 (bagian yang menjelaskan mekanisme baru) **tidak dikerjakan** karena semuanya bergantung pada satu hal yang sama: cara menurunkan `origin_city_id` dari `Branch` yang valid dan bukan workaround. Tidak ada file `Shipment.php` atau `ShipmentResource.php` yang diubah pada sprint ini.

---

## Kebutuhan perubahan skema (untuk diputuskan)

Untuk menuntaskan migrasi ini dengan benar, `branches` butuh cara resmi menentukan City-nya. Dua opsi konkret:

**Opsi A — Tambah `city_id` langsung ke `branches` (direkomendasikan).**
Migration kecil: `branches.city_id` (nullable FK → `cities.id`, `nullOnDelete`), plus relasi `Branch::city(): BelongsTo`. Backfill data untuk 2 branch yang ada sekarang murah dan tanpa ambiguitas — nama Branch ("Jakarta", "Manado") sudah cocok persis dengan nama City yang ada. Ini paling sesuai dengan diagram target sprint (`Branch ↓ City`, foreign key sungguhan, bukan string-match) dan benar-benar **menghilangkan** pola pencocokan string yang selama ini jadi sumber kerapuhan — bukan memindahkannya.

**Opsi B — Branch.name dicocokkan langsung ke City.name (tanpa migration).**
Lebih cepat (tidak butuh migration), tapi mewarisi persis kelemahan yang sama dengan pendekatan `Office.city` lama: tidak ada foreign key, rentan typo/kapitalisasi/City yang di-nonaktifkan. Ini kemungkinan besar **bukan** yang dimaksud "menyelesaikan technical debt dengan benar" di instruksi sprint — hanya memindahkan pola yang sama dari kolom Office ke nama Branch.

**Opsi C — Ada mekanisme lain yang sudah direncanakan tim** (di luar apa yang bisa ditemukan lewat audit kode) — mohon infokan jika ada arah yang sudah diputuskan sebelumnya yang belum tercermin di codebase.

Begitu arah skema diputuskan, sisa sprint (Scope 2-6: hapus lookup `Office::`, ganti `resolveOriginCityFromUser()`, bersihkan kedua hook `Shipment`, perbarui wording placeholder & komentar `DOMAIN-03`) adalah pekerjaan yang jelas dan bisa langsung dieksekusi — seluruhnya sudah dipetakan di tabel inventaris di atas.

---

## File yang diperiksa (tidak ada yang diubah)

`app/Models/Branch.php`, `app/Models/Depot.php`, `app/Models/City.php`, `app/Models/Shipment.php`, `app/Filament/Resources/ShipmentResource.php`, `database/migrations/2025_08_14_111824_create_branches_table.php`, `database/migrations/2025_10_04_092622_create_branch_mode_defaults_table.php`, `database/seeders/JanuariDataSeeder.php`, plus query langsung ke `jss_db` (`BranchModeDefault::count()`).
