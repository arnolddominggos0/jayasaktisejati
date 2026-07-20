# Architecture Audit — Cabang Asal / Master Office

**Status:** AUDIT ONLY — tidak ada perubahan kode
**Tanggal:** 20 Juli 2026
**Lingkup:** Investigasi source of truth untuk field "Cabang Asal" pada form Permintaan Pengiriman. Tidak ada bug fix, migration, atau perubahan Resource/UI dalam dokumen ini.

---

## Ringkasan Satu Baris

`offices` table di database berisi **0 baris** (dikonfirmasi langsung via query), dan tidak pernah ada UI (Filament Resource) untuk mengisinya — sehingga *setiap* Branch, bukan hanya "Jakarta", akan selalu memicu warning ini. Ini bukan bug logic dan bukan regresi dari refactor — ini data master yang memang belum pernah diisi, ditambah satu keputusan arsitektur (DOMAIN-03: "Office sebagai source of truth") yang tidak pernah selesai diimplementasikan sampai ke UI-nya.

---

## Current Architecture

### Alur aktual hari ini (bukan alur yang didokumentasikan sebagai niat)

```
User login (Office Admin)
   │
   ├─ users.branch_id / users.scope_branch_id  →  User::effectiveBranchId()
   │  (app/Models/User.php — tidak melibatkan Office sama sekali di sisi User)
   │
   ▼
Shipment form dibuka (app/Filament/Resources/ShipmentResource.php)
   │
   ├─ Super Admin  → Select::make('branch_id') → pilih dari Branch::pluck('name','id')
   ├─ Office Admin → Placeholder (readonly) → branch_id TIDAK dipilih, hanya ditampilkan
   │
   ▼
ShipmentResource::resolveOriginCityFromUser($branchId)   [baris 100-127]
   │
   ├─ Office::where('branch_id', $branchId)->first()   ← QUERY 1
   │     jika NULL  →  city_id = null, city_name = null   ⇒ WARNING TAMPIL
   │
   ├─ jika Office ditemukan tapi office->city kosong
   │     →  city_id = null, city_name = null              ⇒ WARNING TAMPIL
   │
   └─ jika Office->city ada isinya
         → City::whereRaw('LOWER(name)=LOWER(office.city)')->where('is_active', true)  ← QUERY 2 (name-match, BUKAN foreign key)
         → city_name SELALU dikembalikan sebagai office->city apa adanya
           (walau QUERY 2 tidak match apa pun) — lihat Finding 4.3

   ▼
Saat disimpan: Shipment::booted() → creating & saving hook   [Shipment.php:158-186, 251-272]
   │
   ├─ branch_id: dari Auth user, fallback dari origin_office_id → Office.branch_id
   └─ origin_city_id: diturunkan ULANG dari branch_id → Office.city → City (name-match)
      "Backend adalah source of truth — selalu override apa pun yang dikirim request"
```

### Tiga field yang terlibat pada `shipments`

| Kolom | Tipe FK | Siapa yang mengisi hari ini | Peran aktual |
|---|---|---|---|
| `branch_id` | FK → `branches` | User login (Auth) atau pilihan Super Admin | **Anchor/primary** — dipakai duluan di setiap hook |
| `origin_office_id` | FK nullable → `offices` | **Tidak ada** (lihat Finding 3.3) | Vestige — dibaca sebagai fallback, tidak pernah ditulis oleh kode yang berjalan hari ini |
| `origin_city_id` | FK nullable → `cities` | Sistem (backend hook) | **Derived cache** — selalu ditimpa, tidak pernah dipercaya sebagai input |

---

## Findings

### 1. Audit Master Office

- **Model `Office` masih aktif digunakan** — `app/Models/Office.php` dipakai di `Shipment::originOffice()`, `Shipment::destinationOffice()`, `Branch::offices()`, dan di logic Smart Origin (`ShipmentResource.php`, `Shipment.php`). Model bukan dead code.
- **Resource Master Office TIDAK ADA.** `Glob app/Filament/Resources/*Office*.php` hanya menemukan `app/Models/Office.php` — tidak ada `OfficeResource.php` di path resource mana pun (admin/FC/customer/CMS), tidak ada `OfficePolicy`, tidak ada relation manager (`grep "Office::class"` di seluruh `app/` hanya match `Shipment.php` dan `Branch.php`, keduanya sekadar deklarasi relasi, bukan UI).
- **Bukan disembunyikan — memang tidak pernah dibuat.** Dikonfirmasi lewat `git log --all --diff-filter=A|D -- "*OfficeResource*"`: **tidak ada satu pun commit** di seluruh histori repo yang pernah menambah *atau* menghapus file bernama `OfficeResource`. Sebagai pembanding, `CityResource.php` (`app/Filament/Resources/CityResource.php`, grup nav "Master Data", label "Kota") memang ada dan berfungsi normal — jadi asimetrinya nyata: Kota bisa dikelola lewat UI, Office tidak pernah bisa.
- **Sejak kapan "digantikan"?** Tidak pernah digantikan oleh apa pun — tidak ada mekanisme pengganti. Satu-satunya cara mengisi/mengubah `offices` sejak tabel ini dibuat (`2025_08_15_075624_create_offices_table.php`) adalah lewat seeder atau akses database langsung (tinker/SQL). Digrep di `database/seeders/`: **tidak ada satu pun seeder yang menyebut "Office"** — tabelnya tidak pernah punya jalur pengisian otomatis sama sekali, bukan cuma "belum dijalankan seedernya".

### 2. Audit Navigation

Pertanyaan "kenapa menu Master Office tidak muncul di panel Super Admin" punya jawaban lebih mendasar dari sekadar `shouldRegisterNavigation()`:

- **Bukan soal visibility flag** — tidak ada kode `shouldRegisterNavigation()` untuk Office karena tidak ada *class*-nya sama sekali untuk dievaluasi.
- **Bukan soal Policy/permission** — tidak ada `OfficePolicy`; tidak ada authorization gate yang menyembunyikannya, karena tidak ada resource yang butuh digate.
- **Bukan soal Panel Provider** — `AdminPanelProvider.php:56` memakai `->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')`, yaitu auto-discovery tanpa allow-list/exclude-list eksplisit. Kalau file `OfficeResource.php` ada di folder itu, ia otomatis terdaftar — sama seperti `CityResource.php`, `DepotResource.php`, dll. Mekanisme discovery tidak menge-block apa pun.
- **Bukan soal navigation group** — grup "Master Data" sudah ada dan dipakai `CityResource` (dan resource master lain), jadi kalaupun `OfficeResource` dibuat, tempatnya sudah tersedia.
- **Kesimpulan:** menu tidak muncul karena **filenya memang tidak ada**, titik. Tidak ada satu pun dari lima kemungkinan di atas (visibility/policy/permission/panel provider/nav group) yang relevan sebagai penyebab — semuanya baru relevan **setelah** resource-nya dibuat.

### 3. Audit Source of Truth — dari mana nilai "Cabang Asal" berasal

Alur nyata (bukan alur ideal) untuk kolom yang tampil sebagai label **"Cabang Asal"** di form:

```
users.branch_id / users.scope_branch_id   (app/Models/User.php, effectiveBranchId())
        │
        ▼
Shipment.branch_id  ← INI yang sebenarnya dipilih/ditampilkan sebagai "Cabang Asal"
        │
        │   (Select::make('branch_id')->label('Cabang Asal *'),
        │    app/Filament/Resources/ShipmentResource.php:550-569)
        │
        ▼  [derivasi, bukan pilihan user]
Office::where('branch_id', $branch_id)->value('city')   ← STRING, bukan relasi FK
        │
        ▼  [name-match, bukan FK]
City::whereRaw('LOWER(name) = LOWER(office.city)')->where('is_active', true)
        │
        ▼
Shipment.origin_city_id
```

**File yang terlibat:**
- `app/Filament/Resources/ShipmentResource.php` — field `branch_id` (form input), `resolveOriginCityFromUser()` (baris 100-127, logic derivasi + teks bantuan/warning).
- `app/Models/Shipment.php` — `originOffice()` (1069-1071, relasi `belongsTo(Office::class,'origin_office_id')`), `originCity()` (1142-1144, relasi `belongsTo(City::class,'origin_city_id')`), dan **dua** blok hook (`creating` 158-186, `saving` 251-272) yang menjalankan ulang derivasi yang sama persis.
- `app/Models/Office.php` — sumber string `city` (bukan FK).
- `app/Models/Branch.php` — `offices(): HasMany` (baris 17).

**Jawaban eksplisit:** field "Cabang Asal" mengambil nilainya dari **`Shipment.branch_id`**, bukan dari `origin_city_id` dan bukan (secara langsung) dari `origin_office_id`. Alur yang benar-benar berjalan adalah **User → Branch → Office (string city) → City (name-match)** — mirip contoh kedua di brief (`Shipment.origin_city_id`), TAPI `origin_city_id` di sini adalah *hasil akhir derivasi*, bukan titik awal.

### 4. Audit Warning

- **File:** `app/Filament/Resources/ShipmentResource.php`
- **Method:** closure `content()` milik `Placeholder::make('origin_branch_display')`, baris 573-599 (teks presisi warning di baris 594); logic sumbernya sendiri ada di method `resolveOriginCityFromUser()`, baris 100-127.
- **Kondisi pemicu (persis, bukan perkiraan):**
  ```php
  // baris 585-594
  $branchName = Branch::whereKey(Auth::user()?->effectiveBranchId())->value('name');
  $resolved   = self::resolveOriginCityFromUser();

  if ($resolved['city_name']) {           // <-- kalau TRUE, warning TIDAK tampil
      return "🏢 {$branchName} — Kota asal: {$resolved['city_name']}";
  }

  return ($branchName ? "🏢 {$branchName} — " : '')
      . 'kota asal belum terdaftar di Master Office (hubungi Super Admin).';
  ```
  Warning muncul **hanya jika** `$resolved['city_name']` falsy. Melihat `resolveOriginCityFromUser()`, itu terjadi kalau:
  1. `Office::where('branch_id', $branchId)->first()` = **null** (tidak ada baris Office untuk branch itu), **atau**
  2. Office ditemukan tapi kolom `city`-nya kosong/null.

  Prefiks `"Jakarta — "` pada pesan yang dilaporkan berasal dari `$branchName` (nama Branch user, dari tabel `branches`) — **bukan** dari tabel `cities`. Jadi pesan itu **tidak berarti** "kota Jakarta tidak terdaftar" — artinya sebenarnya **"Branch bernama Jakarta tidak punya baris Office yang valid"**.

- **Data yang sebenarnya sedang dicek:** keberadaan baris di tabel `offices` dengan `branch_id` yang cocok, dan apakah kolom string `offices.city` terisi. **Bukan** mengecek tabel `cities` secara langsung pada titik ini.

- **Dikonfirmasi empiris** (query langsung ke database `jss_db`, koneksi `.env` non-testing, read-only):
  ```
  Branches: 2   (Jakarta, Manado)
  Offices:  0
  Cities (aktif): 10
  Branch tanpa Office: Jakarta, Manado   ← KEDUANYA
  ```
  **Tabel `offices` kosong total** — bukan cuma untuk Jakarta. Warning ini akan muncul untuk *setiap* branch, karena `Office::where('branch_id', ...)->first()` selalu `null`.

- **Catatan tambahan (bug laten terpisah, di luar simtom yang dilaporkan):** kalau suatu saat `offices` sudah terisi tapi `offices.city` berisi string yang **tidak** cocok dengan `cities.name` manapun (typo, kapitalisasi, atau City-nya `is_active=false`), warning ini **TIDAK akan muncul** — karena baris 122-126 tetap mengembalikan `city_name => $office->city` apa adanya, terlepas apakah pencarian City berhasil. Placeholder akan menampilkan "Kota asal: {nama-yang-tidak-valid}" seolah sukses, padahal `origin_city_id` sebenarnya tidak terisi. Ini bukan penyebab warning yang dilaporkan sekarang, tapi kelemahan struktural yang sama.

### 5. Audit Domain

**A. Apa yang sebenarnya direpresentasikan "Cabang Asal" dalam arsitektur saat ini?**

Ada dua jawaban berbeda tergantung ditanya ke *komentar/niat kode* atau ke *kode yang benar-benar berjalan*:

- **Niat yang didokumentasikan** (`ShipmentResource.php:544-547`, komentar `DOMAIN-03`): *"Origin Office sebagai Source of Truth. Yang membuat shipment adalah Office/Cabang, bukan Kota."* — niatnya, "Cabang Asal" = **Office** (unit fisik yang lebih granular dari Branch; `Branch::offices()` adalah `hasMany`, artinya satu Branch bisa punya banyak Office).
- **Yang benar-benar berjalan:** field yang dipilih/ditampilkan, dipakai untuk scoping query (`ShipmentResource::getEloquentQuery()` filter `where('branch_id', ...)`), dan jadi anchor di kedua hook model, adalah **`branch_id`** — yaitu **Branch**, bukan Office. Office cuma dipakai sebagai *tabel lookup* untuk menerjemahkan Branch menjadi nama kota (string), bukan sebagai entitas yang dipilih/disimpan sebagai identitas asal.
- **City bukan representasi "Cabang Asal"** sama sekali — City eksplisit didokumentasikan sebagai turunan murni ("tidak pernah dipilih user").

**Jawaban eksplisit:** secara implementasi nyata, "Cabang Asal" = **Branch**. Secara niat yang ditulis di komentar, seharusnya = **Office**. Kesenjangan antara niat dan implementasi inilah akar masalahnya (lihat Root Cause).

**B. `origin_office_id` atau `origin_city_id` — mana source of truth sekarang, mana yang seharusnya?**

| | Source of truth **saat ini** | Source of truth **menurut niat implementasi (DOMAIN-03)** |
|---|---|---|
| Field | **`branch_id`** | `origin_office_id` |
| Bukti | Diisi dari `Auth::user()->effectiveBranchId()` atau pilihan Super Admin; dipakai duluan di kedua hook (`creating`/`saving`) sebelum field lain diturunkan; dipakai untuk query scoping resource | Komentar eksplisit "Origin Office sebagai Source of Truth"; kolom `origin_office_id` sudah ada di skema (`2025_09_14_150000_create_shipments_table.php:22`, nullable FK ke `offices`) dan relasi `originOffice()` sudah didefinisikan |
| Status `origin_office_id` | **Tidak pernah ditulis oleh kode yang berjalan hari ini.** Digrep di seluruh `app/`: hanya muncul sebagai (1) entri `$fillable`, (2) definisi relasi, (3) **pembacaan** di `elseif ($m->origin_office_id)` (fallback, baris 161 & 254 — dibaca, tidak ditulis), (4) pembacaan di `ShipmentResource.php:1829` untuk prefill URL aksi lain. Satu-satunya tempat nilai ini pernah **ditulis** adalah migration backfill SQL satu-kali (`2025_09_14_150720_add_city_refs_to_shipments_and_backfill.php`) — historis, bukan jalur aplikasi yang berjalan sekarang. |
| `origin_city_id` | Murni **cache turunan** — "Backend adalah source of truth — selalu override" (komentar di `Shipment.php:174` & `260`). Tidak pernah jadi input, selalu jadi output. | Sama — tetap turunan, di kedua skenario. Pertanyaan A vs B (branch vs office) tidak mengubah peran `origin_city_id`: dia selalu hasil akhir, bukan sumber. |

**Jawaban eksplisit:** Source of truth **saat ini** adalah `branch_id`. Menurut **implementasi yang sudah ada** (komentar DOMAIN-03 + skema yang sudah disiapkan), yang **seharusnya** menjadi source of truth adalah `origin_office_id` — tapi ini adalah niat yang tidak pernah dituntaskan: tidak ada UI untuk memilih Office secara langsung di form manapun, dan tidak ada baris Office di database untuk dipilih sekalipun UI-nya ada.

### 6. Audit Smart Origin

- **Masih digunakan?** Ya, aktif, dan bahkan dilindungi secara sengaja. Dipakai di 3 tempat dengan logic identik: `ShipmentResource::resolveOriginCityFromUser()` (tampilan/helper text), `Shipment::creating` hook, `Shipment::saving` hook. `CreateShipment.php` (baris 305-321) secara eksplisit memanggil ulang `ShipmentResource::resolveOriginCityFromUser()` sebagai *"backend protection (always override)"* sebelum simpan, dengan fallback dua-tahap (branch user → branch hasil resolusi lain). Komentar "Rule 7" di file yang sama (baris 137-138) secara eksplisit menyatakan fitur OCR/intake-prefill **sengaja tidak menyentuh** Smart Origin: *"Origin Office tetap mengikuti Smart Origin yang ada (tidak disentuh)."*
- **Apakah ada refactor yang menyebabkan warning ini muncul?** **Tidak ada indikasi.** `offices` table hanya punya satu migration sepanjang sejarah repo (`2025_08_15_075624_create_offices_table.php`) — tidak pernah ada migration susulan yang mengubah/menghapus kolom `city` atau menambah `city_id`. Fitur OCR yang lebih baru secara eksplisit mendokumentasikan dirinya tidak menyentuh jalur ini. Kesimpulan paling didukung bukti: warning ini bukan regresi dari perubahan kode, melainkan **paparan dari data master yang memang belum pernah diisi sejak awal** — kemungkinan besar sudah laten sejak fitur ini pertama dibuat, baru terlihat/dilaporkan sekarang. *(Catatan: audit ini hanya bisa memverifikasi state database di environment yang diakses saat audit — `jss_db` via `.env`. Apakah environment lain (staging/production) juga kosong tidak bisa dipastikan dari sini dan perlu dicek terpisah.)*
- **Relasi Office ↔ City konsisten?** Secara teknis **tidak ada relasi formal** (tidak ada FK `city_id` di tabel `offices` sejak dibuat — dikonfirmasi tidak ada migration `Schema::table('offices', ...)` sama sekali). Yang ada adalah pencocokan **string** (`LOWER(office.city) = LOWER(city.name)`) yang diulang di 3 tempat kode + 1 migration backfill historis, dengan aturan yang sama persis di semua tempat (jadi setidaknya *konsisten secara logic*, walau rapuh secara struktur — tidak ada integritas referensial, rentan typo/kapitalisasi/status `is_active`). Saat ini pertanyaan konsistensi jadi tidak relevan secara praktis karena `offices` kosong — tidak ada baris untuk dicek konsistensinya.

---

## Root Cause

Warning **bukan bug pada logic pencarian/pencocokan** — logic-nya (walau diduplikasi 3-4 kali dan rapuh secara desain) berjalan persis seperti yang ditulis. Akar masalahnya berlapis dua:

1. **Data master hilang total.** Tabel `offices` sudah kosong sejak awal (tidak pernah ada seeder untuk itu), sehingga `Office::where('branch_id', $branchId)->first()` selalu `null` untuk kedua branch yang ada (Jakarta, Manado) — dikonfirmasi langsung ke database, bukan dugaan.
2. **Tidak ada cara mengisi data itu.** Bahkan kalau tim menyadari `offices` kosong, tidak ada Filament Resource untuk mengisinya — satu-satunya jalan adalah query manual/tinker. Pesan "hubungi Super Admin" pada dasarnya salah arah secara operasional: Super Admin pun tidak punya tombol untuk menyelesaikannya.

Di baliknya ada **kesenjangan arsitektur yang lebih dalam**, bukan cuma data kosong: komentar `DOMAIN-03` menyatakan **Office** seharusnya jadi source of truth, tapi implementasi yang benar-benar berjalan memakai **Branch** sebagai anchor dan memperlakukan Office semata sebagai tabel terjemahan nama-kota berbasis string — sebuah desain peninggalan dari sebelum tabel `cities` (dengan `is_active`, relasi proper) dibuat. `offices` table lahir 15 Agustus 2025; `cities` table + kolom `origin_city_id`/`destination_city_id` di `shipments` baru lahir 14 September 2025 — sebulan kemudian. Saat `cities` diperkenalkan, `shipments` diretrofit dengan benar (FK + backfill), tapi `offices` **tidak pernah** diretrofit dengan `city_id` FK yang setara — ia tertinggal dengan kolom string `city` dari desain lama. Warning yang muncul hari ini adalah titik pertama di mana kekosongan data master **dan** utang desain lama ini sama-sama menjadi terlihat oleh pengguna.

---

## Recommendation

*(Arsitektur saja — belum implementasi, sesuai batasan sprint ini.)*

1. **Putuskan dulu arah source of truth secara sadar**, karena ini keputusan produk, bukan cuma teknis: tetap jadikan **Branch** sebagai source of truth resmi (lalu turunkan komentar DOMAIN-03 dan hapus/nonaktifkan kolom `origin_office_id` yang tidak terpakai — lebih sederhana, sesuai apa yang sudah benar-benar berjalan), **atau** benar-benar tuntaskan niat **Office**-sebagai-source-of-truth (bangun Resource Master Office, tambah `city_id` FK proper ke `offices`, ubah form agar user memilih Office — bukan Branch — sebagai origin). Menyimpan dua-duanya setengah jalan seperti sekarang adalah sumber kebingungan berikutnya.
2. **Kalau arah Office dipilih:** relasi Office↔City perlu dinaikkan dari string-matching menjadi foreign key sungguhan (`offices.city_id` → `cities.id`), menghilangkan seluruh pola `LOWER(name)=LOWER(city)` yang saat ini diduplikasi di 3+ tempat — konsolidasi ke satu titik resolusi (mis. sebuah method di `Office` sendiri) alih-alih diulang di `ShipmentResource` dan dua hook `Shipment`.
3. **Terlepas dari arah mana yang dipilih**, `offices` butuh jalur pengisian data yang nyata: seeder minimal (khusus untuk 2 branch yang ada sekarang) sebagai perbaikan cepat data, dan/atau Resource admin sebagai perbaikan jangka panjang — supaya "hubungi Super Admin" benar-benar berarti ada sesuatu yang bisa Super Admin lakukan.
4. **Pertimbangkan mengangkat pesan warning ke level yang lebih tepat.** Karena kegagalan ini adalah kegagalan *data master*, bukan kegagalan input per-shipment, memunculkannya berulang di setiap form Create Shipment (untuk setiap user, setiap saat) kurang tepat sasaran dibanding satu peringatan tingkat-sistem (mis. di dashboard Super Admin) begitu terdeteksi ada Branch tanpa Office yang valid.
5. **Rapikan celah senyap yang ditemukan di Finding 4** (city_name selalu truthy walau City tidak match) sebagai bagian dari sprint perbaikan nanti — di luar cakupan audit ini untuk diperbaiki sekarang, tapi perlu masuk catatan supaya tidak jadi kejutan berikutnya setelah `offices` diisi.

---

## Lampiran: File yang Diperiksa

| File | Peran dalam temuan |
|---|---|
| `app/Filament/Resources/ShipmentResource.php` | Field "Cabang Asal" (baris 550-599), `resolveOriginCityFromUser()` (100-127), lokasi persis warning (594), query scoping branch (129-140) |
| `app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php` | Backend protection Smart Origin saat save (305-321), komentar "Rule 7" soal OCR tidak menyentuh Smart Origin (137-138) |
| `app/Models/Shipment.php` | `$fillable` (34-38), hook `creating` (158-186) & `saving` (251-272) — dua salinan identik derivasi Smart Origin, relasi `originOffice()`/`originCity()`/`destinationOffice()` (1069-1148) |
| `app/Models/Office.php` | Struktur model: `code`, `name`, `address`, `city` (string), `branch_id`; relasi `users()`, `branch()` — tidak ada relasi `city()` |
| `app/Models/Branch.php` | Relasi `offices(): HasMany` |
| `app/Providers/Filament/AdminPanelProvider.php` | Mekanisme `discoverResources()` (56) — konfirmasi tidak ada exclude-list |
| `app/Filament/Resources/CityResource.php` | Pembanding: Resource yang ADA untuk City, grup nav "Master Data" |
| `database/migrations/2025_08_15_075624_create_offices_table.php` | Skema asli `offices` — `city` string nullable, `branch_id` FK NOT NULL; satu-satunya migration untuk tabel ini |
| `database/migrations/2025_09_14_149000_create_cities_table.php` | `cities` lahir ~1 bulan setelah `offices`, dengan `is_active`, `slug` |
| `database/migrations/2025_09_14_150700_add_city_id_to_shipments.php`, `..._150720_add_city_refs_to_shipments_and_backfill.php` | Bukti `shipments` diretrofit dengan benar saat `cities` diperkenalkan; `offices` tidak |
| `database/migrations/2025_09_14_150000_create_shipments_table.php` | Kolom `origin_office_id`/`destination_office_id` nullable FK ke `offices` (22-23) |
| Query langsung ke `jss_db` (read-only, via `.env`) | Empiris: `Branches=2, Offices=0, Cities aktif=10`, kedua branch tanpa Office |
