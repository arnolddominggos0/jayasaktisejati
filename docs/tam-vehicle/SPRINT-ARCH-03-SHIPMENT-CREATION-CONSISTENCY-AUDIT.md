# Sprint ARCH-03 — Shipment Creation Consistency Audit

**Status:** AUDIT SELESAI. Tidak ada kode aplikasi yang diubah.
**Tanggal:** 28 Juli 2026
**Tipe sprint:** 100% audit & dokumentasi — dasar untuk standardisasi Shipment Creation Architecture.

---

## Ringkasan Eksekutif

Ditemukan **10 entry point** yang dapat menghasilkan baris baru di tabel `shipments`, mengikuti **4 pola lifecycle yang secara struktural berbeda**:

1. **Eloquent + transaction eksplisit/framework** → observer `created()` menyala, `DB::afterCommit()` benar-benar tertunda sampai commit → payload notifikasi **benar** (unit sudah ada saat body disusun).
2. **Eloquent tanpa transaction** → observer `created()` menyala, tapi `DB::afterCommit()` **berjalan seketika** (tidak ada transaksi untuk ditunggu) → payload notifikasi **salah** ("0 Unit") bila unit dibuat setelahnya — **ini persis root cause OPS-11D**, dan ditemukan di beberapa entry point non-Admin lain juga.
3. **Eloquent dibungkus `Model::withoutEvents()`** → observer **tidak pernah menyala sama sekali** → tidak ada notifikasi, tidak ada efek samping apa pun (by design, bukan bug).
4. **Raw `DB::table()->insert()`** → Eloquent event system sepenuhnya di-bypass secara struktural → observer **tidak mungkin** menyala, terlepas dari ada/tidaknya transaction.

Konsekuensi penting yang belum pernah dilaporkan sebelumnya: **command import historis (`ImportTamJanuary2026Units`, `ImportTamMay2026Units`, `ImportTamJune2026Units`) memakai pola #1 (Eloquent + transaction) dengan `Shipment::updateOrCreate()`**, sehingga payload notifikasinya akan **benar secara angka**, TAPI observer tidak pernah tahu bahwa ini adalah data historis (`status: Delivered` sejak baris pertama) — sehingga FC berpotensi menerima notifikasi "Pekerjaan Operasional Baru" untuk shipment yang **sudah selesai bertahun-tahun lalu**. Ini murni temuan audit (lihat §7), tidak diperbaiki di sprint ini.

---

## 1–4. Entry Point Inventory, Call Stack, Transaction, Order of Creation

### Entry Point 1 — Admin Panel `CreateShipment` (jalur utama produksi)

**File:** [`app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php:343`](../../app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php#L343)

```
Office Admin submit form (Filament CreateRecord::create())
        │
        ▼
beginDatabaseTransaction()          [vendor/filament/filament CreateRecord.php:90]
        │
        ▼
mutateFormDataBeforeCreate()        — resolve depot/branch/eta, generate code
        │
        ▼
handleRecordCreation()
    │
    ├─▶ Shipment::create($data)         → Eloquent `created` event
    │        │                             → ShipmentObserver::created()
    │        │                             → DB::afterCommit(fn () => notifier)
    │        │                               (transaksi MASIH terbuka → callback DITUNDA)
    │        ▼
    ├─▶ $shipment->units()->createMany() → unit benar-benar tertulis
    │        ▼
    └─▶ forceFill(['status' => Pending])->saveQuietly()   — TIDAK memicu event apa pun
        ▼
form->saveRelationships() / afterCreate hook
        ▼
commitDatabaseTransaction()          [CreateRecord.php:123]
        │
        ▼
        ← DI SINI callback afterCommit BARU dieksekusi →
        ▼
NewOperationalTaskNotifier::notifyForNewShipment()
        │
        ▼
buildBody(): $shipment->units()->count()  →  MEMBACA UNIT YANG SUDAH ADA
        ▼
Notification Payload: "N Unit" — BENAR
```

- **Transaction:** ✅ Ya — dibungkus otomatis oleh `Filament\Resources\Pages\CreateRecord::create()` (framework-level, bukan eksplisit di kode aplikasi).
- **Order:** Shipment → Unit → (status quiet-update) → **commit** → Notification.
- **Terbukti empiris di OPS-11D** (Scenario A: afterCommit callback melihat 2 Unit, sesuai realita).

### Entry Point 2 — Public/Partner API `ShipmentController::store()`

**File:** [`app/Http/Controllers/Api/ShipmentController.php:91-115`](../../app/Http/Controllers/Api/ShipmentController.php#L91)

```
POST /api/shipments
        │
        ▼
DB::transaction(function () {
        │
        ▼
    Shipment::create($validated)     — status dipaksa 'Draft'
        │                               → `created` event → ShipmentObserver
        │                               → DB::afterCommit(...) DITUNDA (transaksi aktif)
        ▼
    return $shipment;
})                                    ← commit di sini → callback jalan
        ▼
    TIDAK ADA unit yang pernah dibuat — ShipmentStoreRequest::rules()
    tidak memiliki field `units` sama sekali (diperiksa langsung, §5).
        ▼
Notification Payload: "0 Unit" — SECARA STRUKTURAL SELALU 0, bukan race condition.
```

- **Transaction:** ✅ Ya, eksplisit (`DB::transaction()` di controller).
- **Order:** Shipment saja — endpoint ini **tidak memiliki jalur pembuatan Unit sama sekali**. Ini berbeda dari OPS-11D (yang soal *timing*) — di sini payload akan **selalu** `0 Unit` karena tidak pernah ada unit untuk endpoint ini, titik.
- **Catatan tambahan (di luar fokus utama, ditemukan saat audit):** `ShipmentStoreRequest::rules()` memvalidasi `'mode' => Rule::in(['Sea', 'Land'])` (kapital), sedangkan `App\Enums\ShipmentMode` hanya mendefinisikan `'sea'`/`'land'` (huruf kecil) sebagai backing value, dan `Shipment::$casts['mode'] = ShipmentMode::class`. Native enum cast Laravel memvalidasi nilai saat attribute **dibaca** (bukan saat di-set) — dan `ShipmentObserver::saving()` langsung membaca `$s->mode?->value` di baris pertamanya. Berdasarkan pembacaan kode, mengirim `mode: "Sea"` (persis sesuai aturan validasi API sendiri) berpotensi melempar `\ValueError` saat `saving()` dieksekusi. **Belum diverifikasi secara live di sprint ini** (di luar scope audit murni) — dilaporkan sebagai risiko, bukan fakta final.

### Entry Point 3 — Historical Import: `ImportTamJanuary2026Units`

**File:** [`app/Console/Commands/ImportTamJanuary2026Units.php:193-280`](../../app/Console/Commands/ImportTamJanuary2026Units.php#L193)

```
php artisan (import command)
        │
        ▼
DB::transaction(function () use ($readyToImport, ...) {
        │
        ▼
    foreach ($readyToImport as $entry) {
        │
        ├─▶ Shipment::updateOrCreate(['code'=>...], [..., 'status'=>Delivered, ...])
        │        → jika baris baru: `created` event → ShipmentObserver
        │        → DB::afterCommit(...) DITUNDA (transaksi SATU BESAR untuk semua baris)
        │
        ├─▶ Unit::updateOrCreate(['shipment_id'=>$shipment->id], [...])
        │        → unit tertulis SEBELUM transaksi besar ini commit
        │
        └─▶ ShipmentTrack::create([...]) × N per baris
    }
})                                    ← commit HANYA SETELAH SELURUH BARIS diproses
        ▼
Notification Payload: "1 Unit" — BENAR secara angka (unit sudah ada saat commit),
        TAPI notifikasi ini dikirim untuk shipment berstatus 'Delivered' sejak
        baris pertama dibuat — secara bisnis TIDAK ADA "pekerjaan baru".
```

- **Transaction:** ✅ Ya — **satu transaksi besar membungkus seluruh loop** (bukan per-baris). Ini justru **melindungi** dari bug "0 Unit" (sama seperti Entry Point 1), karena `afterCommit` baru berjalan setelah *seluruh* baris — termasuk unit-nya — selesai ditulis.
- **Order:** Shipment → Unit → Track (per iterasi), commit di akhir seluruh loop.
- **Idempotensi:** `updateOrCreate` — bila `code` sudah ada, ini memicu event `updated`, BUKAN `created`, sehingga notifier **tidak** berjalan pada re-run. Aman untuk re-import.
- **Depot & recipient:** `branch_id=1`, `mode=Sea` (enum value benar di sini, `ShipmentMode::Sea->value`), `voyage_id` di-resolve dari `Voyage` asli → `ShipmentObserver::saving()`'s `tryAssignDepot()` **kemungkinan besar berhasil** meng-assign `assigned_depot_id` → `NewOperationalTaskNotifier` **kemungkinan besar menemukan FC penerima nyata** dan mengirim notifikasi untuk data historis. Lihat §7 Risk.

### Entry Point 4 — Historical Import: `ImportTamMay2026Units`

**File:** [`app/Console/Commands/ImportTamMay2026Units.php:46-`](../../app/Console/Commands/ImportTamMay2026Units.php#L46)

Pola **identik** dengan Entry Point 3: `DB::transaction()` membungkus satu loop CSV besar, `Shipment::updateOrCreate()` → `Unit::updateOrCreate()` → `ShipmentTrack::create()`, `status: Delivered` sejak awal, `mode: ShipmentMode::Sea->value` (benar). Risiko sama.

### Entry Point 5 — Historical Import: `ImportTamJune2026Units`

**File:** [`app/Console/Commands/ImportTamJune2026Units.php:53-`](../../app/Console/Commands/ImportTamJune2026Units.php#L53)

Pola **identik** dengan Entry Point 3 & 4. Satu perbedaan kecil: memanggil `$shipment->tracks()->delete()` sebelum menulis ulang track (destructive re-sync), tidak mempengaruhi lifecycle Shipment/Unit/Notification.

### Entry Point 6 — Seeder: `TamMay2026Seeder`

**File:** [`database/seeders/TamMay2026Seeder.php:282-316`](../../database/seeders/TamMay2026Seeder.php#L282)

```
php artisan db:seed --class=TamMay2026Seeder
        │
        ▼
Shipment::withoutEvents(function () use (...) {
        │
        ▼
    $shipment = Shipment::create([...])   ← event `created` DIPADAMKAN SELURUHNYA
})
        ▼
createOrUpdateTracks($shipment, $unit)    — ShipmentTrack::updateOrCreate (event tetap aktif
                                             untuk model Track, tapi tidak relevan ke notifier)
        ▼
Observer TIDAK PERNAH menyala. Tidak ada notifikasi, tidak ada job, tidak ada efek samping.
```

- **Transaction:** ❌ Tidak eksplisit — tapi tidak relevan, karena `withoutEvents()` membuat pertanyaan "apakah transaksi menunda observer" menjadi **tidak berlaku**; observer memang tidak pernah didaftarkan untuk menyala.
- **Order:** Shipment (event mati) → Track. **Model `Unit` sama sekali tidak pernah dibuat oleh seeder ini** — 1 baris `shipments` merepresentasikan 1 unit kendaraan langsung di kolom-kolomnya sendiri (`notes` berisi model+warna), bukan lewat relasi `units()`. Konsekuensi: `$shipment->units()->count()` untuk seluruh shipment hasil seeder ini **selalu 0 selamanya** — bukan race condition, melainkan model data yang berbeda dari alur produksi.

### Entry Point 7 — Seeder: `JanuariDataSeeder`

**File:** [`database/seeders/JanuariDataSeeder.php:356-505`](../../database/seeders/JanuariDataSeeder.php#L356)

```
php artisan db:seed --class=JanuariDataSeeder
        │
        ▼
DB::transaction(function () use (...) {
        │
        ├─▶ DB::table('voyages')->insertGetId([...])     — RAW QUERY BUILDER
        ├─▶ DB::table('shipments')->insertGetId([...])   — RAW QUERY BUILDER
        ├─▶ DB::table('shipment_tracks')->insert([...])  — RAW QUERY BUILDER
        └─▶ DB::table('units')->insert([...])            — RAW QUERY BUILDER (loop terpisah, SETELAH shipment)
})
        ▼
Eloquent event system TIDAK PERNAH TERLIBAT. ShipmentObserver::created() TIDAK MUNGKIN
menyala — bukan soal timing/transaction, tapi karena `DB::table()->insert()` tidak
pernah melewati Eloquent model layer sama sekali.
```

- **Transaction:** ✅ Ya, tapi **tidak relevan** untuk observer (raw insert membuat pertanyaan "apakah afterCommit menunggu" tidak berlaku sejak awal).
- **Order (data, bukan Eloquent):** Voyage → Shipment → Track → **(loop terpisah di akhir)** → Unit. Bila hipotetis ini memakai Eloquent, urutan ini sendiri sudah salah relatif terhadap kebutuhan notifier (Track sebelum Unit) — namun ini murni observasi struktural, tidak berdampak nyata karena observer tidak pernah menyala.

### Entry Point 8 — Trial/Validation Scripts (`storage/app/ops08-trial.php`, `ops08-trial-resume.php`)

**File:** [`storage/app/ops08-trial.php:74,347,383,397`](../../storage/app/ops08-trial.php#L74), [`ops08-trial-resume.php:148,184`](../../storage/app/ops08-trial-resume.php#L148)

```
Shipment::create([...])           — TANPA DB::transaction() pembungkus
        │                            → `created` event → ShipmentObserver
        │                            → DB::afterCommit(...) TIDAK ADA transaksi aktif
        │                              → CALLBACK BERJALAN SEKETIKA (sebelum unit ada)
        ▼
(new ShipmentService())->syncUnits($shipment, [...])   — unit baru dibuat DI SINI,
                                                           SETELAH notifier sudah berjalan
        ▼
Notification Payload: "0 Unit" — SALAH, persis pola bug OPS-11D.
```

- **Transaction:** ❌ Tidak ada sama sekali.
- **Order:** Shipment → **(notifier berjalan di titik ini, seketika)** → Unit (via `syncUnits()`).
- Ini adalah **reproduksi nyata pola root-cause OPS-11D** yang ditemukan di file produksi (bukan skenario buatan) — one-off script bekas sprint OPS-08, kemungkinan dijalankan manual via `php artisan tinker` atau `include`, bukan dipanggil rutin oleh aplikasi, tapi tetap sebuah entry point yang eksis di codebase.

### Entry Point 9 — Testing Utility (Feature Tests, `RefreshDatabase`)

**File contoh:** [`tests/Feature/FC/ShipmentTrackingWorkflowTest.php`](../../tests/Feature/FC/ShipmentTrackingWorkflowTest.php), [`ViewShipmentDetailTest.php`](../../tests/Feature/FC/ViewShipmentDetailTest.php), [`ShipmentPolicyScopeTest.php`](../../tests/Feature/FC/ShipmentPolicyScopeTest.php), [`ShipmentPolicyCanonicalScopeTest.php`](../../tests/Feature/FC/ShipmentPolicyCanonicalScopeTest.php), [`ShipmentPrintAccessTest.php`](../../tests/Feature/FC/ShipmentPrintAccessTest.php), [`DashboardWidgetTest.php`](../../tests/Feature/FC/DashboardWidgetTest.php), [`UnitStateIsolationTest.php`](../../tests/Feature/FC/UnitStateIsolationTest.php), [`AppSheetBriefingIngestionTest.php`](../../tests/Feature/FC/AppSheetBriefingIngestionTest.php)

Semua 8 test file di atas memakai `use RefreshDatabase;` (dikonfirmasi langsung, mis. `ShipmentTrackingWorkflowTest.php:10,16`) dan memanggil `Shipment::create()` / `Shipment::factory()->create()`, lalu `->units()->create()` secara **terpisah setelahnya** — persis pola non-transaksional Entry Point 8.

```
Test method dimulai
        │
        ▼
RefreshDatabase::setUp()  →  DB::beginTransaction()   [transaksi test-isolation]
        │
        ▼
Shipment::create([...])            → `created` event → ShipmentObserver
        │                             → DB::afterCommit(...)
        │                               transaksi test-isolation SEDANG AKTIF
        │                               → callback DITUNDA... TAPI TIDAK PERNAH COMMIT
        ▼
$shipment->units()->create([...])  — unit dibuat, tapi tidak relevan lagi
        ▼
Test selesai
        ▼
RefreshDatabase::tearDown()  →  DB::rollBack()   ← transaksi DIBATALKAN, BUKAN di-commit
        ▼
Callback afterCommit TIDAK PERNAH DIEKSEKUSI. NewOperationalTaskNotifier
TIDAK PERNAH BERJALAN dalam pengujian apa pun yang memakai RefreshDatabase.
```

- **Transaction:** ✅ Ada (dari `RefreshDatabase`), tapi karena **sengaja tidak pernah commit** (rollback di akhir test), `DB::afterCommit()` callback apa pun yang didaftarkan selama test **tidak akan pernah tereksekusi** — bukan bug pada test, ini perilaku dokumented Laravel.
- **Konsekuensi arsitektural penting:** jalur notifikasi ini **tidak mungkin diverifikasi lewat PHPUnit Feature Test standar** proyek ini selama memakai `RefreshDatabase` + `DB::afterCommit()`. Ini menjelaskan mengapa OPS-11/11B/11C/11D seluruhnya memvalidasi lewat skrip manual terhadap database nyata (`queue:work --stop-when-empty` + insert manual), bukan lewat test suite otomatis — **bukan karena tidak ada test, tapi karena mekanisme `afterCommit` secara struktural tidak teramati oleh `RefreshDatabase`.** Juga tidak bisa ditangkap `Event::fake()` karena `DB::afterCommit()` bukan Laravel Event, melainkan callback pada `DatabaseTransactionsManager`.

### Entry Point 10 — `ShipmentFactory` (utility, bukan entry point independen)

**File:** [`database/factories/ShipmentFactory.php`](../../database/factories/ShipmentFactory.php)

Bukan entry point berdiri sendiri — dipanggil oleh Entry Point 9 (`Shipment::factory()->create()`). `definition()` hanya menyusun atribut; tidak pernah membuat `Unit` (unit harus ditambahkan eksplisit di test, mengikuti pola non-transaksional yang sama).

### Entry Point yang TERBUKTI TIDAK ADA (diaudit, dikonfirmasi absen)

- **FC Panel:** dikonfirmasi dari `docs/SCOPING.md` §2 (`field_coordinator` → Create: **No**) dan tidak ditemukan satu pun `Shipment::create(` di `app/Filament/FC/**` — FC tidak pernah bisa membuat Shipment baru, hanya mengedit/mengupdate status shipment yang sudah ada (sea + unit scope). Konfirmasi struktural, bukan hanya kebijakan.
- **Queue Job:** tidak ada direktori `app/Jobs/`; tidak ada job Laravel kustom yang membuat `Shipment` di seluruh aplikasi.
- **AppSheetService:** ditelusuri (`app/Services/AppSheetService.php`) — menangani ingestion briefing/loading checkpoint untuk shipment yang **sudah ada**, tidak pernah memanggil `Shipment::create()`.
- **OCR/Intake (`IntakePrefill`):** bukan entry point terpisah — hanya mekanisme *prefill* form Livewire di Entry Point 1 (`CreateShipment::applyIntakePrefill()`), tetap bermuara ke `handleRecordCreation()` yang sama.

---

## 5. Observer Impact — Sensitivitas Timing

| Observer | Method | Bergantung pada urutan Shipment→Unit/Track? | Sikap terhadap ketiadaan transaksi |
|---|---|---|---|
| `ShipmentObserver` | `created()` → `NewOperationalTaskNotifier` | **Ya, sangat sensitif.** Mengandalkan `DB::afterCommit()` untuk "menunggu" unit selesai — hanya benar jika ADA transaksi aktif untuk ditunggu. | **Tidak ada guard.** Tidak memeriksa apakah dipanggil dari dalam transaksi; berasumsi selalu ada. Terbukti gagal senyap (Entry Point 2, 8, 9) — lihat OPS-11D & audit ini. |
| `UnitObserver` | `created()` → `InspectionDraftAutoCreate` | Ya, tapi **dijaga eksplisit**: memeriksa `$shipment->tracks()->exists()` dan no-op bila belum ada track, dengan komentar yang menjelaskan alasan desain (`UnitObserver.php:11-24`). | **Aman by design** — mendelegasikan ke trigger lain (`ensureForTrack()`) bila prasyarat belum terpenuhi, alih-alih mengasumsikan data sudah lengkap. |
| `ShipmentTrackObserver` | semua method | Tidak — hanya mencatat activity log dari state track itu sendiri, tidak membaca relasi lain yang mungkin belum lengkap. | Tidak relevan. |
| `CustomerObserver`, `ShippingScheduleObserver`, `LoadingSessionObserver` | — | Tidak terkait siklus hidup Shipment→Unit sama sekali. | Tidak relevan. |

**Kesimpulan:** dari seluruh observer yang terdaftar di `AppServiceProvider::boot()`, **hanya `ShipmentObserver::created()` (via `NewOperationalTaskNotifier`) yang rentan terhadap timing** — dan justru inilah satu-satunya yang tidak memiliki guard structural terhadap kondisi tidak-ada-transaksi, berbeda dari `UnitObserver` yang sudah menangani kasus serupa dengan benar.

---

## 6. Consistency Matrix

| Entry Point | Transaction | Unit sebelum Notification | Observer Aman | Status |
|---|---|---|---|---|
| 1. Admin `CreateShipment` | ✅ (framework) | ✅ Ya | ✅ Aman | **Konsisten** |
| 2. API `ShipmentController::store` | ✅ (eksplisit) | ❌ Tidak pernah ada unit | ⚠️ Aman secara timing, tapi payload struktural selalu "0 Unit" | **Konsisten tapi cacat desain** |
| 3. `ImportTamJanuary2026Units` | ✅ (satu transaksi/loop) | ✅ Ya | ✅ Aman secara timing | **Konsisten secara angka, TIDAK konsisten secara bisnis** (notifikasi utk data historis) |
| 4. `ImportTamMay2026Units` | ✅ (satu transaksi/loop) | ✅ Ya | ✅ Aman secara timing | Sama seperti #3 |
| 5. `ImportTamJune2026Units` | ✅ (satu transaksi/loop) | ✅ Ya | ✅ Aman secara timing | Sama seperti #3 |
| 6. `TamMay2026Seeder` | ❌ (tidak relevan) | N/A — `withoutEvents`, Unit tidak pernah dipakai | ✅ Observer tidak pernah menyala | **Konsisten (by design, event dimatikan)** |
| 7. `JanuariDataSeeder` | ✅ (tidak relevan) | N/A — raw insert, event tidak mungkin menyala | ✅ Observer tidak mungkin menyala | **Konsisten (by design, bypass Eloquent)** |
| 8. `storage/app/ops08-trial*.php` | ❌ Tidak ada | ❌ Tidak — unit dibuat setelah notifier jalan | ❌ **Tidak aman** — reproduksi nyata bug OPS-11D | **Inkonsisten — TERBUKTI cacat** |
| 9. Feature Tests (`RefreshDatabase`) | ✅ (test-isolation, never commits) | Tidak relevan — notifier tidak pernah benar-benar jalan | ⚠️ Tidak bisa diverifikasi lewat test | **Tidak bisa diuji secara otomatis** |
| 10. `ShipmentFactory` | — (utility Entry Point 9) | — | — | Mengikuti #9 |

---

## 7. Risk

| Risiko | Entry Point Terdampak | Dampak |
|---|---|---|
| **Payload notifikasi "0 Unit" palsu** | 2 (struktural), 8 (timing) | Sudah diperbaiki di level payload untuk kasus timing (OPS-11D: baris unit disembunyikan bila 0, bukan lagi ditampilkan salah). Untuk Entry Point 2, "0 Unit" akan selalu tidak muncul di body (baris disembunyikan) — secara teknis benar, tapi FC tidak pernah tahu shipment API tidak pernah dapat unit. |
| **Notifikasi untuk data historis/sudah selesai** | 3, 4, 5 | `NewOperationalTaskNotifier` tidak memeriksa `status` — shipment yang di-*insert* dengan `status: Delivered` tetap memicu "Pekerjaan Operasional Baru" bila depot ter-resolve. Menjalankan command import ini di lingkungan dengan FC/depot yang valid berpotensi mengirim notifikasi palsu massal untuk pekerjaan yang sudah lama selesai. **Belum diverifikasi berjalan di produksi** — dilaporkan sebagai risiko berbasis code-path, bukan insiden yang sudah terjadi. |
| **Observer tanpa guard terhadap ketiadaan transaksi** | 1, 2, 3, 4, 5, 8, 9 (semua yang lewat Eloquent) | `ShipmentObserver::created()` mengasumsikan `DB::afterCommit()` selalu berarti "tunggu sampai semua beres" — asumsi ini SALAH bila pemanggil tidak membungkus dalam transaksi. `UnitObserver` menunjukkan pola yang lebih aman (guard eksplisit) yang bisa dijadikan referensi standar. |
| **Race condition potensial pada Entry Point 1** | 1 | Meski terbukti benar di skenario yang diuji, `saveRelationships()` dan `afterCreate` hook Filament berjalan SEBELUM commit — bila ada logic masa depan yang menambah unit di `afterCreate` (bukan di `handleRecordCreation`), urutan tetap aman selama masih di dalam transaksi yang sama; risiko baru muncul hanya jika unit dibuat via `saveQuietly()`/proses async setelah commit. |
| **Jalur tidak bisa diuji otomatis** | 9 | Tidak ada automated regression test yang benar-benar memverifikasi `NewOperationalTaskNotifier` berjalan dan menghasilkan payload benar — seluruh validasi historis (OPS-11 s.d. 11D) dilakukan manual terhadap database nyata. Perubahan di masa depan pada `ShipmentObserver`/`NewOperationalTaskNotifier` berisiko regresi tanpa terdeteksi CI. |
| **Model data ganda untuk "unit"** | 6 | `TamMay2026Seeder` merepresentasikan 1 kendaraan = 1 baris `shipments` (bukan `units`), berbeda dari model produksi (1 shipment bisa banyak `units`). Query apa pun yang mengasumsikan `units()` selalu terisi untuk shipment "sea + delivered" akan salah untuk data hasil seeder ini. |
| **Potensi enum cast error di API** | 2 | Lihat catatan Entry Point 2 — `mode: "Sea"` (sesuai validasi API sendiri) vs enum `ShipmentMode` yang hanya menerima `"sea"`. Berpotensi melempar `ValueError` saat `ShipmentObserver::saving()` membaca `$s->mode`. Belum diverifikasi live di sprint ini. |

---

## 8. Rekomendasi Standard Architecture

*(Rekomendasi ini murni untuk didokumentasikan sebagai dasar sprint berikutnya — tidak diimplementasikan di sprint audit ini.)*

1. **Jadikan "wajib dalam transaksi" sebagai kontrak eksplisit, bukan asumsi implisit.** Entry point mana pun yang membuat `Shipment` lalu `Unit` secara terpisah harus membungkus keduanya dalam `DB::transaction()` — persis pola yang sudah benar di Entry Point 1, 3, 4, 5. Entry Point 8 (trial scripts) dan pola test di Entry Point 9 adalah contoh nyata mengapa asumsi implisit gagal.
2. **Pertimbangkan guard structural di `NewOperationalTaskNotifier`/`ShipmentObserver`, meniru pola `UnitObserver`.** `UnitObserver` sudah menunjukkan pola yang benar: periksa prasyarat data eksplisit, no-op dan delegasikan ke trigger lain bila belum siap — alih-alih berasumsi `afterCommit` selalu berarti "data lengkap".
3. **Definisikan apakah command import historis SEHARUSNYA memicu notifikasi.** Saat ini tidak ada mekanisme (flag, context, atau status check) untuk membedakan "shipment baru yang butuh tindakan FC" vs "data historis yang di-backfill". Rekomendasi: notifier sebaiknya memeriksa status di luar kondisi "assigned_depot_id ada" — misalnya hanya notify bila status masuk kategori aktif/pending, bukan `Delivered`/`Cancelled` sejak awal dibuat.
4. **Selesaikan atau dokumentasikan status "shell shipment" API.** `ShipmentController::store()` tidak pernah menghasilkan unit — perlu diputuskan apakah ini API yang memang hanya untuk membuat draft (lalu unit ditambahkan lewat jalur lain yang belum ada), atau requirement yang belum lengkap.
5. **Standardisasi satu pola: Eloquent + transaction eksplisit, hindari raw `DB::table()->insert()` untuk data yang seharusnya tunduk pada business rule (observer, notifikasi, dsb).** `JanuariDataSeeder` valid untuk backfill satu kali yang sadar-diri (idempotent, tidak butuh notifikasi), tapi pola ini rawan disalahgunakan bila ada developer baru yang meniru untuk kasus yang seharusnya memicu efek samping.
6. **Tambahkan strategi verifikasi otomatis untuk jalur `DB::afterCommit()`**, misalnya lewat test yang secara eksplisit memanggil `DB::commit()`/tidak memakai `RefreshDatabase` untuk skenario ini secara terisolasi (mis. `DatabaseTransactions` dengan commit manual, atau testing database terpisah yang boleh di-commit sungguhan lalu dibersihkan manual — pola yang sudah dipraktikkan manual di sprint OPS-11 s.d. 11D).

---

## Daftar File yang Diaudit

**Entry point (kode aplikasi):**
- `app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php`
- `app/Http/Controllers/Api/ShipmentController.php`
- `app/Http/Requests/Api/ShipmentStoreRequest.php`
- `app/Console/Commands/ImportTamJanuary2026Units.php`
- `app/Console/Commands/ImportTamMay2026Units.php`
- `app/Console/Commands/ImportTamJune2026Units.php`
- `database/seeders/TamMay2026Seeder.php`
- `database/seeders/JanuariDataSeeder.php`
- `database/factories/ShipmentFactory.php`
- `storage/app/ops08-trial.php`
- `storage/app/ops08-trial-resume.php`

**Observer & model:**
- `app/Observers/ShipmentObserver.php`
- `app/Observers/UnitObserver.php`
- `app/Observers/ShipmentTrackObserver.php`
- `app/Providers/AppServiceProvider.php` (registrasi observer)
- `app/Models/Shipment.php` (casts, relasi `units()`)
- `app/Services/NewOperationalTaskNotifier.php`
- `app/Services/ShipmentService.php` (`syncUnits()`)
- `app/Services/AppSheetService.php` (dikonfirmasi bukan entry point)

**Test (Entry Point 9):**
- `tests/Feature/FC/ShipmentTrackingWorkflowTest.php`
- `tests/Feature/FC/ViewShipmentDetailTest.php`
- `tests/Feature/FC/ShipmentPolicyScopeTest.php`
- `tests/Feature/FC/ShipmentPolicyCanonicalScopeTest.php`
- `tests/Feature/FC/ShipmentPrintAccessTest.php`
- `tests/Feature/FC/DashboardWidgetTest.php`
- `tests/Feature/FC/UnitStateIsolationTest.php`
- `tests/Feature/FC/AppSheetBriefingIngestionTest.php`

**Framework (dibaca untuk membuktikan mekanisme, bukan diubah):**
- `vendor/filament/filament/src/Resources/Pages/CreateRecord.php`
- `vendor/laravel/framework/src/Illuminate/Database/Concerns/ManagesTransactions.php`
- `vendor/laravel/framework/src/Illuminate/Database/DatabaseTransactionsManager.php`

**Referensi silang:**
- `docs/SCOPING.md` (konfirmasi FC tidak punya izin create)
- `docs/tam-vehicle/SPRINT-OPS-11D-*` (root cause asal yang memicu audit ini — lihat riwayat percakapan, belum berupa file terpisah)

---

*Akhir dari Shipment Creation Consistency Audit — ARCH-03. Tidak ada perubahan kode dalam sprint ini.*
