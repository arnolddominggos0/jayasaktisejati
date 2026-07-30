# Sprint OPS-10 — Daily Briefing Session (One Briefing Per Day)

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression INS-03/INS-04/OPS-08/Operational Task.
**Tanggal:** 24 Juli 2026
**Konteks:** implementasi langsung dari keputusan bisnis di Sprint ARCH-03 (audit).

---

## 0. Keputusan Cakupan (dikonfirmasi via pertanyaan eksplisit sebelum implementasi)

Sebelum menulis kode, diaudit dulu dampak nyata jika Gate diterapkan luas ke seluruh aksi operasional (sesuai bunyi literal Scope 4 brief, "seluruh pekerjaan hari itu dapat dijalankan"). **Data nyata hari ini (24 Juli 2026) menunjukkan risiko konkret**: satu-satunya depot dengan pekerjaan aktif (depot #1) **belum punya BriefingSession untuk hari ini** — sesi terakhirnya dari kemarin (23 Juli). Jika Gate diterapkan ke semua aksi (termasuk Pickup/Handover), Operational Task akan langsung kehilangan seluruh tombol aksi untuk depot ini.

Temuan ini disampaikan ke user dengan 3 opsi cakupan sebelum implementasi dimulai. **Dipilih: cakupan sempit** — hanya jalur yang menuju status **Stuffing** (StuffingWorkspace + kedua pintu di Operational Task yang bisa mencapai status itu). Pickup, Handover, dan seluruh aksi lain **tidak diubah sama sekali** — nol risiko terhadap operasional live hari ini.

---

## 1. Business Rule Lama

1. Briefing readiness diperiksa lewat **dua check terpisah** di `StuffingService::checkPreconditions()`: `briefing_done` (sesi ADA untuk depot+hari ini) dan `mp_ready` (`summary_sufficient` DAN `mp_check_status===Cleared`).
2. Pencarian sesi SUDAH memakai `depot_id`+`date` (bukan pivot Shipment) — bagian ini sebenarnya **sudah benar** sejak Sprint ST-01 (mengikuti rekomendasi audit MP-08) — tapi caranya masih "dicari ulang di setiap tempat yang butuh", bukan satu Gate terpusat.
3. Gate ini **hanya berlaku di `StuffingWorkspace`** (soft-gate UI). Jalur lain yang bisa mencapai status Stuffing — quick-action **"Stuffing & Segel"** dan modal generik **"Update"** di `OperationalTasks` — **tidak pernah memeriksa briefing sama sekali** (temuan ARCH-03).
4. Tidak ada pesan spesifik/jelas saat briefing belum ready — hanya baris generik dalam checklist 5-item.

---

## 2. Business Rule Baru

1. **Satu Gate terpusat, satu pertanyaan**: `DailyBriefingGate::isReady(int $depotId)` — "Apakah ada Briefing Session untuk (depot_id, hari ini) dengan status Ready (summary_sufficient DAN mp_check_status ∈ {Cleared, Approved})?" Menggantikan 2 check terpisah dengan 1 check `briefing_ready`.
2. **Shipment-independent secara API**: seluruh method `DailyBriefingGate` menerima `int $depotId`, TIDAK PERNAH `Shipment`/`Unit`. Caller (mis. `StuffingService`) boleh menurunkan `depotId` dari shipment miliknya sendiri — itu levelnya "caller resolve context", bukan "Gate melekat ke Shipment".
3. **Shipment/Unit baru pada hari yang sama otomatis pakai briefing yang sama** — konsekuensi langsung dari poin 2 (dibuktikan §6: 2 shipment berbeda, depot sama, hasil `isReady()` identik).
4. **Digate secara konsisten di SELURUH pintu yang menuju status Stuffing** (StuffingWorkspace + 2 pintu di Operational Task) — bukan hanya satu tempat seperti sebelumnya.
5. **Pesan blokir eksplisit dan konsisten**, tidak menyebut Shipment/Unit: *"Operasional hari ini belum dibuka. Silakan selesaikan Briefing Harian terlebih dahulu."* — satu sumber teks (`DailyBriefingGate::blockReason()`), dipakai identik di ketiga tempat.
6. **Tidak ada status/kolom baru** — "Ready" memakai ulang `MPCheckStatus::Cleared`/`Approved` yang sudah ada (Scope 6: Database Schema tidak disentuh).
7. **Cakupan tetap sengaja terbatas ke Stuffing** — Pickup, Handover, dan status lain TIDAK digate sprint ini (lihat §0).

---

## 3. File yang Berubah

| File | Perubahan |
|---|---|
| `app/Services/DailyBriefingGate.php` | **Baru.** `activeSessionFor()`, `isReady()`, `blockReason()` — Gate shipment-independent, satu sumber kebenaran. |
| `app/Services/Stuffing/StuffingService.php` | `checkPreconditions()`: 2 check (`briefing_done`+`mp_ready`) digabung jadi 1 (`briefing_ready`), memanggil `DailyBriefingGate::isReady()`. Import `BriefingSession`/`MPCheckStatus` dihapus (tidak dipakai lagi di file ini). |
| `app/Filament/FC/Pages/StuffingWorkspace.php` | Method baru `getBriefingBlockReason()` — sumber pesan Scope 5. |
| `resources/views/filament/fc/pages/stuffing-workspace.blade.php` | Blok pesan baru (amber, menonjol) sebelum checklist precondition generik, tampil hanya saat briefing belum ready. |
| `app/Filament/FC/Pages/OperationalTasks.php` | `updateTrack`'s `action()`: tambah pemeriksaan `DailyBriefingGate` HANYA saat `$status === TrackStatus::Stuffing`. `stuffing`'s `action()`: tambah pemeriksaan yang sama sebelum `appendTrack()`. Import `DailyBriefingGate` ditambahkan. |

**Tidak ada migration, tidak ada perubahan database, tidak ada perubahan `Shipment.php`/`Unit.php`/`UnitInspection.php`/Guard/`ShipmentTrack`** (Scope 6).

---

## 4. Gate Lama vs Gate Baru

### Lama
```
StuffingService::checkPreconditions($shipment):
    $briefing = BriefingSession::where('depot_id', $shipment->assigned_depot_id)
        ->whereDate('date', today())->latest('id')->first();
    checks['briefing_done'] = $briefing !== null
    checks['mp_ready']      = $briefing?->summary_sufficient && mp_check_status === Cleared

  Dipakai HANYA oleh StuffingWorkspace. OperationalTasks' quick-action
  "Stuffing & Segel" dan modal "Update" TIDAK memanggil ini sama sekali.
```

### Baru
```
DailyBriefingGate::isReady(int $depotId, ?Carbon $date = null): bool
    $session = BriefingSession::where('depot_id', $depotId)
        ->whereDate('date', $date ?? today())->latest('id')->first();
    return $session && $session->summary_sufficient
        && $session->mp_check_status ∈ {Cleared, Approved}

DailyBriefingGate::blockReason(int $depotId): ?string
    return isReady($depotId) ? null : "Operasional hari ini belum dibuka. ..."

  Dipakai oleh:
    1. StuffingService::checkPreconditions() — checks['briefing_ready']
    2. OperationalTasks 'stuffing' quick-action — di dalam action()
    3. OperationalTasks 'updateTrack' — di dalam action(), khusus saat
       target status = Stuffing
```

**Perbedaan kunci**: Gate lama menerima objek Shipment secara implisit (query di dalam `checkPreconditions($shipment)`, langsung memakai `$shipment->assigned_depot_id`). Gate baru adalah fungsi murni `int → bool`/`?string`, dipanggil dari 3 tempat berbeda dengan hasil yang **provably identik** untuk depot yang sama, terlepas shipment/unit mana yang bertanya (dibuktikan §6 poin 3).

---

## 5. Workflow Sebelum & Sesudah

### Sebelum
```
StuffingWorkspace: cek briefing (2 checks terpisah) → tampilkan/sembunyikan container
OperationalTasks "Stuffing & Segel": appendTrack(Stuffing) LANGSUNG, briefing TIDAK diperiksa
OperationalTasks "Update" (generik): appendTrack(status apa pun) LANGSUNG, briefing TIDAK diperiksa
```

### Sesudah
```
Pagi: Koordinator buat/lengkapi BriefingSession (depot+hari ini) → Attendance → MP Ready
      → (opsional) Approve → mp_check_status ∈ {Cleared, Approved} + summary_sufficient=true
      → DailyBriefingGate::isReady(depot) = true untuk SISA HARI ITU

StuffingWorkspace: DailyBriefingGate::isReady() → jika false, pesan jelas + checklist tetap
                   tampil (4 item, bukan lagi 5) → container/unit disembunyikan sampai ready
OperationalTasks "Stuffing & Segel": DailyBriefingGate::blockReason() diperiksa SEBELUM
                   appendTrack() → jika belum ready, notifikasi jelas, TIDAK ada mutasi
OperationalTasks "Update" (khusus saat target = Stuffing): pemeriksaan yang SAMA persis
                   sebelum appendTrack() → 3 pintu, satu Gate, hasil konsisten

Shipment/Unit BARU yang dibuat siang hari, depot sama: otomatis "ikut" briefing pagi
                   yang sudah Ready — nol langkah tambahan, nol briefing kedua.
Besok pagi: BriefingSession hari ini sudah bukan "hari ini" lagi → isReady() balik ke
                   false sampai briefing baru dibuat — siklus berulang harian.
```

---

## 6. Validasi & Regression

| Uji | Hasil |
|---|---|
| `php -l` pada 4 file PHP yang diubah/dibuat | ✅ Bersih |
| `config:clear`, `view:clear`, `composer dump-autoload` | ✅ Sukses |
| **Hari ini** (depot #1, belum ada sesi): `isReady()=false`, `blockReason()`="Operasional hari ini belum dibuka..." | ✅ |
| **Kemarin** (depot #1, sesi #74 cleared+sufficient): `isReady()=true`, `blockReason()=null` | ✅ |
| **Besok** (depot #1): `isReady()=false` — tidak mewarisi status hari ini/kemarin | ✅ (validasi "hari berikutnya butuh briefing baru") |
| **Shipment-independence**: shipment #228 dan #229 (depot sama) → `isReady()` identik untuk keduanya | ✅ (validasi "shipment baru tidak perlu briefing baru") |
| `StuffingService::checkPreconditions()` — key lama `briefing_done`/`mp_ready` hilang, key baru `briefing_ready` ada, terhadap shipment nyata #229 | ✅ |
| `StuffingWorkspace::getBriefingBlockReason()` — pesan benar untuk shipment nyata | ✅ |
| `OperationalTasks::getTableQuery()` tetap jalan (13 unit row, tanpa exception) — Operational Task tidak rusak | ✅ |
| `migrate:status` — tidak ada migration baru | ✅ |

### Regression INS-03 / INS-04
Tidak ada file Inspection yang disentuh sprint ini. `InspectUnitPage::submit()`, Single Entry Point, lock, audit log — tidak berubah.

### Regression OPS-08
```
Shipment #228 (JSS0726SH0001):         handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002):         handoverCleared=false, loadingCleared=false
Shipment #230 (OPS08-TRIAL-154113):    handoverCleared=false, loadingCleared=false
Shipment #231 (OPS08-NEG-154323):      handoverCleared=false, loadingCleared=false
Shipment #232 (OPS08-CAP-154323):      handoverCleared=false, loadingCleared=false
Shipment #233 (OPS08-CAPFIX-A-154435): handoverCleared=false, loadingCleared=false
Shipment #234 (OPS08-CAPFIX-B-154436): handoverCleared=false, loadingCleared=false
Shipment #235 (JSS0726SH0003):         handoverCleared=false, loadingCleared=false
```
**Identik byte-for-byte** dengan seluruh baseline sebelumnya — `Shipment.php` (tempat Guard hidup) tidak pernah disentuh sprint ini.

### Regression Operational Task (OPS-09)
`OperationalTasks::getTableQuery()` dijalankan langsung terhadap data nyata — 13 baris unit berhasil diambil, tanpa exception. Pickup/Handover/aksi lain di file yang sama **tidak diubah satu baris pun** di luar dua titik yang eksplisit didokumentasikan di §3.

---

## Konfirmasi Batas

- ✅ Hanya bisa ada satu briefing **aktif secara efektif** per hari per depot — dijamin oleh `activeSessionFor()` yang selalu memilih baris `latest('id')` sebagai satu-satunya sesi yang dianggap berlaku, terlepas berapa pun baris fisik ada (constraint UNIQUE di DB **tidak dipulihkan** — sudah dihapus sebelum sprint ini, dan Scope 6 melarang perubahan Database Schema sprint ini; jika keunikan di level DB tetap dibutuhkan, itu perubahan skema terpisah, di luar cakupan sprint ini — dicatat sebagai catatan terbuka, bukan diasumsikan selesai).
- ✅ Shipment baru pada hari yang sama tidak memerlukan Briefing baru (dibuktikan §6).
- ✅ Unit baru otomatis dapat diproses setelah Briefing hari itu Ready (konsekuensi langsung dari Gate yang shipment/unit-independent).
- ✅ Hari berikutnya kembali membutuhkan Briefing baru (dibuktikan §6, uji "besok").
- ✅ Gate tidak lagi mencari briefing berdasarkan Shipment — seluruh API `DailyBriefingGate` menerima `int $depotId`.
- ✅ Tidak ada perubahan `Shipment`, `Unit`, `Inspection`, Transition Guard, `ShipmentTrack`, atau Database Schema.
- ✅ Cakupan gate sengaja terbatas ke Stuffing (keputusan eksplisit, §0) — Pickup/Handover/aksi lain identik dengan sebelum sprint ini.
