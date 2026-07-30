# Sprint ST-01 — Workflow Stuffing (Architecture First)

**Status:** IMPLEMENTATION — kode ditulis, tervalidasi struktural (syntax, blade compile, reflection); **migrasi belum dijalankan** (blocker sama seperti CA-01/CA-01.5: `APP_ENV=production`, verifikasi keamanan DB belum selesai).
**Tanggal:** 23 Juli 2026
**Rujukan:** [`DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md`](DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md), [`SPRINT-CA-01.5-REFACTOR-REPORT.md`](SPRINT-CA-01.5-REFACTOR-REPORT.md), [`AUDIT-MP-08-BRIEFING-WORKFLOW-ROLE.md`](../field-coordinator/AUDIT-MP-08-BRIEFING-WORKFLOW-ROLE.md)

---

## Keputusan Arsitektur Kunci (dibaca dulu sebelum detail)

Sebelum menulis kode apa pun, dilakukan investigasi untuk memastikan Stuffing tidak menduplikasi yang sudah ada — mengulang disiplin yang sama seperti CA-01.5 (SSOT) dan Office Retirement:

1. **`LoadingSession` BUKAN "Stuffing".** Diperiksa penuh: `LoadingSession` adalah **gerbang kesiapan/keselamatan PRA-stuffing** (cek MP attendance, kesehatan, APD, alat, struktur rack/container, ukuran unit) yang berujung pada `final_decision_status` (Go/Warning/Stop) — bukan pencatatan "unit mana masuk container mana secara fisik". Tidak ada tumpang tindih; ST-01 tidak menyentuh `LoadingSession` sama sekali.
2. **Container & Unit dari Container Allocation DIPAKAI ULANG, bukan diciptakan ulang.** `Container.is_ready_for_stuffing` (CA-01.5) persis "Container sudah tersedia" pada precondition. `Unit.container_id`/`allocation_status = ReadyForStuffing` (CA-01) persis titik mulai Domain Goal sprint ini ("unit fisik dimasukkan ke container yang **sudah dinyatakan Ready**"). Migrasi hanya **menambah kolom** ke dua tabel ini — tidak ada tabel baru.
3. **`Shipment.status`/`ShipmentTrack`/`appendTrack()` TIDAK disentuh.** Mesin ini sudah ada, sudah punya gate sendiri (`ensureHandoverInspectionCleared`, dst.), dan `TrackStatus::Stuffing` sudah menjadi salah satu tahap resminya. Menambah kolom status Shipment baru untuk Stuffing akan menciptakan DUA sumber kebenaran status Shipment — persis kesalahan yang dikoreksi di CA-01.5. Status Shipment terkait Stuffing di sprint ini **selalu dihitung (derived), tidak pernah disimpan**.
4. **Container TETAP bukan root object.** Tidak ada FK `containers.shipment_id` ditambahkan. Hubungan Container→Shipment tetap transitif lewat Unit (`Container::shipment()`, method baru, murni query — bukan kolom).
5. **Precondition "Briefing selesai"/"MP Ready" memakai temuan Audit MP-08.** Bukan lewat pivot `briefing_session_shipments` (terbukti tidak reliably terisi di audit sebelumnya), melainkan `BriefingSession` untuk **(depot_id, tanggal hari ini)** — anchor asli Briefing yang sudah dikonfirmasi lewat audit, bukan asumsi baru.

---

## Domain Goal — Dipatuhi Secara Struktural

*"Stuffing adalah eksekusi, bukan planning, bukan allocation."* Ditegakkan lewat guard eksplisit di `StuffingService`:
- `markUnitStuffed()` **menolak** unit yang `container_id === null` (belum direncanakan — "Stuffing tidak membuat rencana baru").
- Tidak ada method apa pun di `StuffingService` yang menghitung kebutuhan container, memilih shipment briefing, menghitung manpower, melakukan health check, atau inspection — persis daftar "Hal yang TIDAK dilakukan Stuffing" di brief sprint.

---

## File yang Dibuat/Diubah

| File | Jenis | Isi |
|---|---|---|
| `app/Enums/ContainerStuffingStatus.php` | Baru | Ready/Stuffing/Full/ReadyLoading — persis state machine Container di brief. |
| `app/Enums/UnitAllocationStatus.php` | Diubah (additif) | Tambah 1 case `Stuffed`. Enum yang sama dengan CA-01 — **bukan enum baru** (lihat §Keputusan Unit di bawah). |
| `database/migrations/..._add_stuffing_fields_to_containers_table.php` | Baru | Tambah `stuffing_status`, `stuffing_started_at`, `stuffing_completed_at` ke `containers` (kolom existing tidak disentuh). |
| `database/migrations/..._add_stuffing_fields_to_units_table.php` | Baru | Tambah `stuffed_at`, `stuffed_by` (FK users), `stuffing_remarks` ke `units`. |
| `app/Models/Container.php` | Diubah (additif) | Fillable/cast baru + `plannedUnitCount()`, `stuffedUnitCount()`, `isStuffingComplete()`, `shipment()` (transitif, bukan FK). |
| `app/Models/Unit.php` | Diubah (additif) | Fillable/cast baru + relasi `stuffedBy()`. |
| `app/Services/Stuffing/StuffingService.php` | Baru | Precondition check + 4 action (open/mark/unmark/auto-complete) + ringkasan turunan. |
| `app/Filament/FC/Pages/StuffingWorkspace.php` | Baru | Halaman FC, `shouldRegisterNavigation=false` (konsisten dengan konvensi panel FC — lihat di bawah). |
| `resources/views/filament/fc/pages/stuffing-workspace.blade.php` | Baru | Tampilan polos, tanpa design system kustom, sesuai instruksi sprint. |

---

## Keputusan Desain yang Perlu Dijelaskan

### Unit: kenapa "Stuffing" (in-progress) tidak jadi status tersendiri

Brief meminta state machine Unit: `Waiting Stuffing → Stuffing → Stuffed`. Diimplementasikan sebagai **2 status tersimpan**, bukan 3: `ReadyForStuffing` ("Waiting Stuffing" — label existing "Siap Stuffing" sudah pas) langsung ke `Stuffed`. **Alasan:** satu unit fisik ditandai lewat SATU aksi operator atomik (scan/centang → selesai) — tidak ada sub-state "sedang di-scan" yang bermakna untuk disimpan per unit. "Sedang berlangsung" adalah properti **Container** (agregat banyak unit, sebagian sudah/sebagian belum) — dilacak di `ContainerStuffingStatus::Stuffing`, bukan diduplikasi di level Unit. Ini konsisten dengan prinsip "Satu Entitas — Satu Status Aktif" yang sudah dibekukan sebelumnya di initiative ini.

### Kenapa `UnitAllocationStatus` diperluas, bukan dibuat enum baru

Docblock ASLI enum ini (ditulis di CA-01) sudah secara eksplisit menyatakan: *"Status ini BERHENTI di ReadyForStuffing... yang terjadi setelahnya adalah domain Stuffing — di luar cakupan enum ini."* Ini adalah titik ekstensi yang sudah disiapkan, bukan keputusan baru. Satu Unit punya SATU siklus hidup (Allocation lalu Stuffing berurutan) — dua enum terpisah untuk fase berurutan dari entitas yang sama akan menciptakan ambiguitas "field mana yang aktif sekarang", persis masalah yang berulang kali dihindari sepanjang initiative ini.

### Precondition "Container sudah tersedia" — per Shipment, bukan global

Diimplementasikan sebagai: *shipment ini* punya minimal satu Unit yang ter-alokasi ke Container dengan `is_ready_for_stuffing=true` — bukan sekadar "ada container ready di mana pun hari ini". Tanpa ini, precondition bisa lolos untuk shipment yang sama sekali tidak punya container siap, hanya karena shipment LAIN kebetulan sudah siap.

### Auto-buka Container saat unit pertama ditandai

Brief: `Container dipilih → List Unit → Operator scan/centang unit`. Diimplementasikan sebagai satu gerakan: `markUnitStuffed()` otomatis memanggil transisi Ready→Stuffing bila container masih Ready, idempotent. Operator tidak perlu klik "Buka Stuffing" terpisah sebelum menandai unit pertama — mengurangi langkah tanpa mengubah maknanya (memilih container untuk mulai menandai unit di dalamnya = membuka sesi stuffing container itu).

### Completion Rule dipakai apa adanya, dengan satu klarifikasi

*"Container dianggap selesai apabila Stuffed Unit == Planned Unit."* Diimplementasikan `plannedUnitCount()` = jumlah unit yang **sudah dialokasikan** ke container ini (`filledCount()` dari CA-01, tidak dihitung ulang) — **bukan** `capacity` (kapasitas maksimal container, dipakai guard terpisah di Allocation). Container boleh sah "Full" walau tidak terisi penuh sampai kapasitas maksimalnya, selama seluruh unit yang **direncanakan** untuknya sudah masuk — ini pembacaan literal dari Completion Rule, bukan asumsi baru.

---

## Alur End-to-End (sesuai Deliverable Sprint)

```
Shipment (dipilih dari dropdown, scope depot FC)
   ↓
[Precondition Gate — StuffingService::checkPreconditions()]
   ✓ Shipment aktif        (Shipment.status)
   ✓ Briefing selesai      (BriefingSession, depot+hari ini — temuan MP-08)
   ✓ MP Ready              (BriefingSession.summary_sufficient + mp_check_status=cleared)
   ✓ Container Readiness selesai (ContainerReadinessSession.summary_sufficient)
   ✓ Container tersedia    (Unit shipment ini punya container is_ready_for_stuffing)
   → semua lengkap? lanjut. Tidak? "Belum dapat melakukan Stuffing" + daftar yang kurang.
   ↓
Container dipilih (daftar container is_ready_for_stuffing berisi unit shipment ini)
   ↓
List seluruh Unit shipment di container tsb
   ↓
Operator tandai ("Tandai Masuk Container") → markUnitStuffed()
   → auto-buka container (Ready→Stuffing) bila baru pertama kali
   → unit: ReadyForStuffing → Stuffed (stuffed_at/stuffed_by/remarks tercatat)
   ↓
Progress bertambah (Stuffed Unit / Planned Unit, dihitung live per container)
   ↓
Semua unit selesai → Completion Rule terpenuhi → OTOMATIS Container: Stuffing → Full
   ↓
(derived, read-only) Seluruh container shipment Full → shipmentStuffingSummary() = "ready_loading"
```

Ini mencerminkan diagram Deliverable Sprint persis — hanya "Ready Loading" tingkat Shipment bersifat **ringkasan terhitung**, bukan status tersimpan (lihat §Keputusan Arsitektur poin 3).

---

## Data yang Dicatat (sesuai spesifikasi)

**Per Container** (kolom baru di `containers`): Container Number (`container_no`, existing), Shipment (turunan, tidak disimpan), Start/Finish Stuffing (`stuffing_started_at`/`stuffing_completed_at`), Total Unit (`plannedUnitCount()`, terhitung), Stuffing Status (`stuffing_status`).

**Per Unit** (kolom baru di `units`): Unit (existing), Stuffed At (`stuffed_at`), Stuffed By (`stuffed_by`), Container (`container_id`, existing dari CA-01), Remarks (`stuffing_remarks`, opsional).

---

## Out of Scope — Dikonfirmasi Tidak Diimplementasikan

Dashboard, Analytics, Productivity, Timeline, KPI, Heatmap, Chart, Report, Audit UX, Performance — **tidak ada satu pun** dari daftar ini di kode yang ditulis. `StuffingWorkspace` hanya menampilkan daftar container/unit + progress angka sederhana (`X / Y unit`), tanpa grafik/agregat lintas-hari/analitik apa pun.

---

## Validasi yang Dilakukan

| Uji | Hasil |
|---|---|
| `php -l` pada 8 file yang ditulis/diubah | ✅ Bersih semua |
| `composer dump-autoload` | ✅ Sukses |
| `php artisan view:cache` (compile SELURUH blade, termasuk view baru) | ✅ Sukses, tanpa error — lalu `view:clear` untuk kembali bersih |
| Reflection: seluruh class baru bisa di-load, seluruh method service ada, seluruh case enum resolve dengan label/color benar | ✅ (4 case `UnitAllocationStatus`, 4 case `ContainerStuffingStatus`, 4 method `Container`, 6 method `StuffingService` — semua terverifikasi lewat `ReflectionClass`) |

**Belum tervalidasi:** migrasi terhadap database nyata, dan alur fungsional end-to-end (assign→mark→auto-complete) dengan data sungguhan — terblokir oleh batasan environment `APP_ENV=production` yang sama seperti seluruh sprint TAM Vehicle sebelumnya (CA-01, CA-01.5). Begitu blocker itu selesai, langkah pertama yang saya rekomendasikan: jalankan migrasi, lalu ulangi uji fungsional bergaya CA-01 (transaksi di-rollback) untuk precondition check, mark/unmark, dan auto-transition Full.

---

## Konfirmasi Batas Sprint

- ✅ Tidak ada redesign visual — Blade polos, tanpa `.jss-*`/`.mon-*` atau komponen kustom baru.
- ✅ Tidak ada perubahan kosmetik pada halaman lain — hanya file baru + kolom additif.
- ✅ Fokus workflow & domain — seluruh keputusan di atas adalah keputusan domain, bukan tampilan.
- ✅ Konsisten dengan arsitektur yang sudah ada — Container Allocation, Domain Freeze, dan Audit MP-08 seluruhnya dipakai ulang, bukan diduplikasi.

Siap menjadi fondasi untuk Loading, Voyage, Unloading, dan Delivery — sesuai urutan yang direncanakan setelah Stuffing stabil.
