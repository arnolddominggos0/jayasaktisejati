# Sprint ARCH-03 — Operational Briefing Domain Audit

**Status:** ANALYSIS ONLY — tidak ada kode/model/migration/PR yang diubah (Scope 7 dipatuhi penuh).
**Tanggal:** 24 Juli 2026
**Metode:** Seluruh temuan ditelusuri langsung dari model, migration, service, dan Blade view yang berjalan — bukan asumsi dari nama kolom/label UI.

---

## Catatan Awal — Audit Ini Membangun di Atas Audit Sebelumnya

Sebuah audit domain Briefing yang **sangat menyeluruh sudah pernah dilakukan**: `docs/field-coordinator/AUDIT-MP-08-BRIEFING-WORKFLOW-ROLE.md` (23 Juli 2026). Sprint ARCH-03 ini **memverifikasi ulang** seluruh temuan MP-08 terhadap kode SAAT INI (dikonfirmasi: semuanya masih akurat, tidak ada regresi sejak MP-08 — lihat bukti verifikasi per bagian di bawah), lalu **menambahkan** yang belum dijawab MP-08:

1. Diagram alur & relasi dalam format yang diminta sprint ini (workflow, model, Gate Decision terpisah).
2. Definisi presisi "Gate" — termasuk nuansa **soft-gate (UI) vs hard-gate (domain guard)** yang MP-08 belum eksplisit bedakan (§3 di bawah — temuan BARU sprint ini).
3. Analisis dampak eksplisit terhadap arah Unit-Centric (Sprint OPS-09, yang **terjadi SETELAH MP-08 ditulis** — MP-08 tidak bisa membahas ini).
4. Rekomendasi dalam format 3-opsi objektif (Scope 6), belum ada di MP-08.

**Bukti bahwa MP-08 sudah berdampak nyata:** rekomendasi #3 MP-08 ("Tetapkan Shipment sebagai input resmi Stuffing") **sudah diadopsi** — `app/Services/Stuffing/StuffingService.php` (Sprint ST-01, dibuat setelah MP-08) mengutip MP-08 secara eksplisit di komentarnya sendiri:
```php
// 2 & 3. Briefing selesai + MP Ready — dari BriefingSession
// (depot_id, date) hari ini. Anchor ini SENGAJA depot+tanggal,
// BUKAN pivot briefing_session_shipments — sesuai temuan Audit
// MP-08 ... (lihat AUDIT-MP-08-BRIEFING-WORKFLOW-ROLE.md).
```

---

## Jawaban Langsung atas 4 Pertanyaan Inti (Background)

### "Briefing sebenarnya melekat ke apa?"
**Depot + Tanggal** (satu sesi operasional harian per depot). BUKAN Shipment, BUKAN SPPB, BUKAN Unit. Bukti kode identik dengan MP-08 §1, diverifikasi ulang hari ini — masih akurat:
- `BriefingSession.shipment_id` ditandai eksplisit `@deprecated` di model.
- Komentar arsitektur dari penulis kode sendiri (`Shipment.php:384-389`): *"The active path is sendToFc()... firstOrCreate(['depot_id','date'])... One session per depot per day, many shipments per session."*
- Constraint UNIQUE(date, depot_id) di DB **pernah ada**, lalu **dihapus** — anchor konseptualnya tetap sama, hanya penegakannya di level DB yang hilang (temuan risiko, lihat §5).

### "Apa yang divalidasi oleh Gate?"
**Ada DUA gate berbeda yang sering tertukar** — ini temuan baru sprint ini, lihat §3 untuk detail lengkap. Ringkas:
1. **Domain Transition Guard** (`Shipment::runTransitionGuards()`, ARCH-01) — **TIDAK PERNAH memeriksa Briefing sama sekali**, di status manapun (Pickup, Handover, Stuffing, dst.). Diverifikasi lewat grep menyeluruh: nol referensi "briefing" di seluruh guard methods `Shipment.php`.
2. **`StuffingService::checkPreconditions()`** (dipakai HANYA di `StuffingWorkspace`, sebuah halaman UI manual, BUKAN dipanggil dari `appendTrack()`) — memvalidasi 5 hal: Shipment aktif, Briefing selesai (BriefingSession ada untuk depot+hari ini), MP Ready (`summary_sufficient` + `mp_check_status=Cleared`), Container Readiness selesai, Container tersedia untuk shipment ini. Ini **soft-gate tingkat UI** (`@if ($preconditions['ok'])` di Blade — menyembunyikan daftar container/unit bila belum lengkap), bukan hard-gate domain.

### "Apa hubungan Briefing dengan Pickup?"
**Tidak ada, sama sekali, di kode yang berjalan.** `startPickup` action (`OperationalTasks.php`, tidak diubah sprint OPS-09 kemarin) memanggil `appendTrack(TrackStatus::Pickup, ...)` langsung — nol pemeriksaan Briefing. Diagram di brief sprint ini (`Shipment → Briefing → Gate → Pickup`) **menggambarkan alur yang diinginkan/diasumsikan, bukan yang terimplementasi** — persis temuan MP-08 §2 untuk hubungan Stuffing, ternyata berlaku sama untuk Pickup.

### "Apakah Briefing harus tetap Shipment-centric atau berubah menjadi Operational Session?"
**Secara desain kode, Briefing SUDAH BUKAN Shipment-centric sejak migrasi 17 Juni 2026** — sudah menjadi Operational Session (depot+hari) dalam arsitektur aktifnya. Yang tersisa "Shipment-centric" hanyalah kolom legacy `shipment_id` (deprecated) dan pivot `briefing_session_shipments` yang **UI pengisiannya sudah dimatikan** dan tidak dikonsumsi keputusan apa pun yang bermakna (lihat §4, §6). Lihat §7 untuk opsi resmi ke depan.

---

## 1. Diagram Workflow Aktual Briefing

```
┌─────────────────────────────────────────────────────────────────────┐
│  PAGI HARI, PER DEPOT (bukan per shipment)                          │
│                                                                       │
│  FC Coordinator membuka "Tugas Operasional" → Card "Briefing         │
│  Hari Ini" (OperationalTasks::getDailySetup(), query:                │
│  depot_id=X AND date=hari ini)                                       │
│                                                                       │
│      Belum ada sesi?                    Sudah ada sesi?              │
│           │                                    │                     │
│           ▼                                    ▼                     │
│  CreateBriefingSession                  ViewBriefingSession          │
│  (FC isi manual: depot_id auto-         (lihat status MP Check,      │
│   terisi dari Depot::coordinator_       Ringkasan Manpower/          │
│   user_id, summary_headcount,           Kesehatan/APD)               │
│   dst. — TIDAK ada langkah "pilih                                    │
│   SPPB/Shipment" karena UI-nya                                       │
│   di-comment total)                                                  │
│           │                                                          │
│           ▼                                                          │
│  4 baris StockApdCheck dibuat otomatis (helm/rompi/sepatu/           │
│  sarung tangan), required_quantity = summary_headcount               │
│           │                                                          │
│           ▼                                                          │
│  BriefingAttendance dicatat PER MANPOWER (hadir/tidak, suhu,         │
│  tensi, APD, fit_status) — via form manual ATAU AppSheet             │
│  (kolom appsheet_id ada di banyak tabel; BriefingSessionHandler.php  │
│   yang lama DEAD CODE, jalur aktif: AppSheetService, belum           │
│   ditelusuri detail method-nya — di luar cakupan sprint ini)         │
│           │                                                          │
│           ▼                                                          │
│  Setiap BriefingAttendance disimpan → trigger otomatis                │
│  BriefingSessionEvaluator::evaluate($session):                       │
│    ready = readyManpowerCount() (present + APD lengkap + FIT)        │
│    required = summary_headcount                                      │
│    → mp_check_status: OnCheck / WaitingAction / Cleared              │
│    → summary_sufficient: true/false                                  │
│           │                                                          │
│           ▼                                                          │
│  (opsional, manual) FC approve sesi → mp_check_status = Approved     │
│  (status TERMINAL — tidak lagi dihitung ulang formula)                │
│                                                                       │
│  ═══════════ Briefing "dianggap selesai" saat: ═══════════           │
│  BriefingSession untuk depot+hari ini EXISTS (bukan status           │
│  tertentu!) — inilah definisi "briefing_done" yang benar-benar       │
│  dipakai StuffingService::checkPreconditions(). "MP Ready" adalah    │
│  pemeriksaan TERPISAH (mp_check_status===Cleared+summary_sufficient) │
│  — dua kondisi berbeda yang sering disebut satu ("briefing selesai") │
│  padahal keduanya independen secara kode.                            │
└─────────────────────────────────────────────────────────────────────┘

Relasi ke Shipment lifecycle (RIWAYAT DIINGINKAN vs KENYATAAN):

  DIINGINKAN (diagram brief):    Shipment → Briefing → Gate → Pickup → Handover → ...
  KENYATAAN (kode berjalan):     Shipment ⇢ (paralel, tidak wajib) briefing_session_shipments (pivot MATI)
                                  Shipment → Pickup → Handover → Stuffing → ...  (JALUR INI TIDAK PERNAH
                                                                                   memeriksa Briefing)
                                  Depot+Hari → BriefingSession → (dibaca HANYA oleh StuffingWorkspace,
                                                                    soft-gate UI, bukan appendTrack())
```

**Siapa yang membuat Briefing:** FC Coordinator, manual, lewat `CreateBriefingSession` (Filament FC panel) — `coordinator_user_id` diisi dari user yang login, `depot_id` di-auto-fill dari `Depot::where('coordinator_user_id', $user->id)`. Tidak ada pembuatan otomatis dari event Shipment/ShipmentTrack manapun (berbeda dari `InspectionDraftAutoCreate`/`LoadingSessionAutoCreate` yang auto-create dari `ShipmentTrackObserver`).

**Kapan dibuat:** kapan pun FC membukanya di pagi hari kerja — tidak dipicu oleh event Shipment tertentu (bukan saat `sendToFc()`, bukan saat Pickup).

**Kapan dianggap selesai:** **tidak ada satu definisi tunggal** — tergantung siapa yang bertanya:
- Bagi `StuffingService::checkPreconditions()` → "selesai" = **sesi EXISTS** untuk depot+hari ini (tidak peduli status apa pun).
- Bagi badge "Status Kesiapan Operasional" di `ViewBriefingSession` → "selesai" = `summary_sufficient=true` (manpower cukup).
- Bagi alur formal → "selesai" = `mp_check_status=Approved` (FC eksplisit approve), status terminal.
Tiga definisi berbeda yang tumpang tindih tapi tidak identik — layak diperjelas jika arsitektur Briefing didesain ulang (di luar cakupan analisis, dicatat sebagai temuan).

---

## 2. Diagram Relasi Model Briefing (Aktual, dari Kode)

```
Depot ──────────────┐
                     │ 1
                     ▼ N
              BriefingSession   (anchor: depot_id + date; shipment_id = @deprecated 1:1)
                 │        │  ╲___________________________
                 │ 1      │ 1                             ╲ N..M (pivot, TIDAK PERNAH
                 ▼ N      ▼ N                                DIISI lewat UI aktif manapun
        BriefingAttendance   StockApdCheck                   yang ditemukan — lihat §4)
                 │  1                                         │
                 ▼  N                                         ▼ N
      BriefingAttendancePpeItem                       briefing_session_shipments (pivot)
                 │                                             │
      AttendanceHealthLog (log audit per attendance)           │ N
                                                                 ▼ 1
                                                              Shipment ──── 1:N ──── Unit
                                                                 │
                                                                 │ (assigned_depot_id — field
                                                                 │  BIASA Shipment, DIBACA
                                                                 │  LANGSUNG oleh StuffingService,
                                                                 │  TIDAK lewat pivot manapun)
                                                                 ▼
                                                   StuffingService::checkPreconditions()
                                                     → BriefingSession::where('depot_id',
                                                         $shipment->assigned_depot_id)
                                                         ->whereDate('date', today())

LoadingSession ─ ─ ─ (FK opsional briefing_session_id, HANYA bisa diisi manual lewat
                       dropdown LoadingSessionResource — TIDAK PERNAH diisi otomatis oleh
                       LoadingSessionAutoCreate::forShipment(), dikonfirmasi baca penuh
                       source method tsb: nol referensi briefing_session_id)

Briefing (model terpisah!) ── TIDAK ADA RELASI ke BriefingSession sama sekali.
   fillable: tanggal, pic, topik, peserta, keterangan — kolom generik "notulen rapat",
   BUKAN bagian dari sistem BriefingSession/Attendance. Nama sangat mirip → risiko
   kebingungan penamaan bagi developer baru. Tidak ditelusuri apakah model ini masih
   dipakai di fitur manapun (di luar cakupan sprint ini) — dicatat sebagai temuan.
```

**Model yang diminta diaudit di brief, status ditemukan:**

| Model diminta | Status |
|---|---|
| `BriefingSession` | Ada, inti domain. |
| `BriefingAssignment` | **Tidak ada di codebase** — nama ini tidak match model manapun (dikonfirmasi grep menyeluruh, nol hasil). Kemungkinan istilah dari brief tidak sama dengan istilah kode; yang paling dekat perannya adalah `briefing_session_shipments` (pivot assignment shipment→session) atau `BriefingAttendance` (assignment manpower→session). |
| `BriefingAttendance` | Ada — per-manpower, dengan health check (suhu/tensi), APD (`BriefingAttendancePpeItem`), fit status, recheck flow. |
| MP Readiness | Bukan model terpisah — kumpulan accessor/logic di `BriefingSession` (`readyManpowerCount()`, `isOperationallyReady()`) + `BriefingSessionEvaluator` (service, single source of truth, dipanggil dari `BriefingAttendance::booted()` setiap attendance disimpan/dihapus). |
| Relasi Shipment | `BriefingSession::shipment()` (deprecated 1:1) + `::shipments()` (aktif, BelongsToMany, TAPI UI pengisiannya mati — §4). |
| Relasi Unit | **Tidak ada relasi langsung BriefingSession↔Unit sama sekali.** Satu-satunya sentuhan ke Unit adalah metrik turunan `actual_unit_masuk_yard` (COUNT Unit lewat JOIN Shipment+ShipmentTrack, dibatasi `assigned_depot_id` — bukan lewat pivot shipments yang di-attach). |

---

## 3. Diagram Gate Decision — TEMUAN BARU Sprint Ini

Brief meminta "jangan hanya menyebut function, jelaskan business rule". Berikut pemetaan lengkap dua gate yang berbeda:

```
┌──────────────────────────────────────────────────────────────────┐
│  GATE #1 — Domain Transition Guard (HARD, memblokir appendTrack) │
│  Shipment::runTransitionGuards() — ARCH-01                       │
│                                                                    │
│  Memvalidasi: status transition valid + inspeksi Handover cleared │
│  + container assignment complete + inspeksi Loading cleared +     │
│  loading session completed.                                       │
│                                                                    │
│  BRIEFING: TIDAK ADA di sini. Nol referensi, di status manapun.  │
│  Pickup, Handover, Stuffing — SEMUA bisa terjadi tanpa Briefing   │
│  apa pun ada/selesai, selama guard lain terpenuhi.                │
└──────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│  GATE #2 — StuffingWorkspace Precondition (SOFT, UI-only)        │
│  StuffingService::checkPreconditions($shipment) — ST-01           │
│  Dipanggil HANYA dari StuffingWorkspace::getPreconditions()       │
│  (satu Livewire page, manual, terpisah dari OperationalTasks'     │
│   ActionGroup "Stuffing & Segel" quick-action)                    │
│                                                                    │
│  Memvalidasi 5 hal (SEMUA harus true, AND):                       │
│    1. shipment_active    : Shipment.status bukan draft/delivered/ │
│                             cancelled                              │
│    2. briefing_done      : BriefingSession EXISTS untuk           │
│                             (depot_id=shipment.assigned_depot_id,  │
│                              date=hari ini) — TIDAK peduli status  │
│    3. mp_ready           : briefing.summary_sufficient===true DAN │
│                             briefing.mp_check_status===Cleared     │
│    4. container_readiness_done : ContainerReadinessSession        │
│                             (GLOBAL per-hari, BUKAN per-depot,     │
│                              tidak terhubung Briefing sama sekali) │
│                             .summary_sufficient===true             │
│    5. container_available : ADA Container is_ready_for_stuffing   │
│                             berisi Unit milik shipment INI          │
│                                                                     │
│  Efek bila ok=false: Blade `@if ($preconditions['ok'])` MENYEMBUNYI│
│  KAN seluruh daftar container/unit — FC tidak bisa memilih         │
│  container atau menandai unit stuffed dari halaman ini.            │
│                                                                     │
│  ⚠️ TEMUAN: Gate #2 TIDAK KONSISTEN diterapkan. OperationalTasks'  │
│  ActionGroup "Stuffing & Segel" (quick action, appendTrack         │
│  langsung) TIDAK memanggil checkPreconditions() sama sekali —      │
│  visibility-nya hanya cek isHandoverInspectionCleared() +          │
│  isContainerAssignmentComplete() (Gate #1's domain layer). Jadi FC │
│  BISA mencatat transisi status "Stuffing" lewat quick action tanpa │
│  briefing/MP-ready pernah diperiksa — meski TIDAK bisa memakai     │
│  StuffingWorkspace (fitur eksekusi fisik container) tanpa itu.     │
│  Dua pintu, satu tujuan (status "Stuffing"), gate berbeda.         │
└──────────────────────────────────────────────────────────────────┘
```

---

## 4. Daftar Business Rule Briefing (Ditemukan di Kode)

1. **Satu BriefingSession per depot per hari** (by design/comment, TIDAK oleh DB constraint sejak 17 Juni 2026 — unique(date,depot_id) di-drop pada hari yang sama pivot dibuat).
2. **MP Ready dihitung otomatis**, bukan diinput manual: `readyManpowerCount()` = attendance `present` + `has_ppe=true` + (`recheck_result=FIT` ATAU (`fit_status=FIT` DAN belum di-recheck)).
3. **`mp_check_status` adalah state machine dengan 2 status terminal** (`Failed`, `Approved`) — begitu salah satu dicapai, formula otomatis (`OnCheck`/`WaitingAction`/`Cleared`) TIDAK lagi menimpanya (hanya `summary_sufficient` yang tetap dihitung ulang).
4. **`fit_status` di-auto-evaluate dari vital sign** jika tidak di-override manual: suhu 35.5–37.2°C DAN sistolik 90–120 DAN diastolik 60–80 → FIT, selain itu TIDAK FIT. Setiap auto-eval dicatat ke `AttendanceHealthLog` (audit trail).
5. **Setiap perubahan BriefingAttendance memicu evaluasi ulang BriefingSession** (`booted()` → `BriefingSessionEvaluator::evaluate()`) — KECUALI sesi sudah terminal (`isTerminal()`).
6. **`expected_unit`/`unit_gap` dihitung dari shipment yang di-attach lewat pivot** (`briefing_session_shipments`) — TAPI **mekanisme attach manual sudah dimatikan** (form di-comment total), sehingga populasi datanya berisiko basi/kosong (temuan MP-08, masih berlaku).
7. **`actual_unit_masuk_yard` dihitung dari populasi BERBEDA**: seluruh Unit yang Handover pada tanggal tsb DI DEPOT itu (lewat `ShipmentTrack`, bukan pivot) — independen dari shipment mana pun yang "dipilih" ke briefing.
8. **Briefing TIDAK memengaruhi apa pun di `ShipmentTrack`/`appendTrack()`** — nol guard, di status manapun (Pickup s/d Delivered).
9. **Briefing MEMENGARUHI (soft-gate UI) satu halaman spesifik saja**: `StuffingWorkspace`, lewat `StuffingService::checkPreconditions()` — dan bahkan di situ, hanya "sesi EXISTS" + "MP ready" yang dipakai; bukan shipment mana pun yang di-attach ke sesi itu.

---

## 5. Daftar Ketergantungan Antar Modul

| Modul | Bergantung pada Briefing? | Bagaimana | Sifat |
|---|---|---|---|
| **Pickup** (`startPickup` action) | **Tidak** | — | — |
| **Handover** (`handover` action) | **Tidak** | — | — |
| **Container Readiness** | **Tidak** | Tidak ada `depot_id`, tidak ada FK ke Briefing sama sekali (migration dikonfirmasi ulang) | — |
| **Container Allocation** | **Tidak** | Ber-FK ke `ContainerReadinessSession`, bukan Briefing | — |
| **Stuffing (StuffingWorkspace, UI eksekusi container)** | **Ya, soft-gate** | `checkPreconditions()`: briefing session exists + MP ready, via depot+tanggal | UI-level, menyembunyikan konten bila gagal |
| **Stuffing (OperationalTasks quick-action "Stuffing & Segel")** | **Tidak** | appendTrack() langsung, guard hanya Inspection+Container | Inkonsistensi vs baris di atas (§3) |
| **Inspection (UnitInspection/InspectUnitPage)** | **Tidak** | Nol referensi silang ditemukan | — |
| **ShipmentTrack / seluruh transisi status lain** | **Tidak** | `runTransitionGuards()` nol referensi briefing | — |
| **LoadingSession** | **Sangat lemah** | Kolom `briefing_session_id` ada, HANYA bisa diisi manual lewat dropdown form, tidak pernah otomatis | Praktis tidak dipakai |
| **OperationalTasks dashboard ("Setup Hari Ini")** | **Ya, informational** | Menampilkan status briefing depot hari ini + link buat/lihat — TIDAK memblokir apa pun di halaman itu sendiri | Murni display |
| **Widget** (`FcTodayBriefingSummary`, `MonthlyBriefingSummaryWidget`, `BriefingProgress`) | Ya, sebagai konsumen data | Menampilkan ringkasan/statistik briefing | Reporting, bukan gate |

**Kesimpulan dependency:** modul yang benar-benar "bergantung" pada Briefing untuk BERFUNGSI (bukan sekadar menampilkan info) hanya **satu**: `StuffingWorkspace`. Semua modul lain di rantai Pickup→Handover→...→Delivery berjalan 100% independen dari Briefing.

---

## 6. Perbandingan Implementasi vs Operasional Nyata

Brief menanyakan: di lapangan, briefing dilakukan berdasarkan Shipment? SPPB? Shift? Tim? Kendaraan? Unit? Session kerja?

**Berdasarkan bukti struktur data (health check per-orang, PPE per-orang, bukan per-shipment/per-unit), briefing operasional K3/safety-briefing SECARA ALAMI adalah kegiatan per-KRU/per-SHIFT di satu DEPOT, satu kali di pagi hari** — bukan diulang per SPPB atau per unit kendaraan. Ini **konsisten** dengan anchor depot+tanggal yang sudah diadopsi kode sejak 17 Juni 2026. Pada titik ini, **implementasi dan realita operasional SUDAH SELARAS** untuk fungsi inti Briefing (kehadiran, kesehatan, APD manpower).

**Perbedaan muncul hanya di SATU bagian**: "Ringkasan Beban Kerja" (Shipment Terpilih / Expected Unit / Gap Unit) — bagian ini MENGASUMSIKAN briefing juga berfungsi sebagai "daftar SPPB yang akan dikerjakan hari ini", sebuah konsep BERBEDA (lebih dekat ke "rencana kerja harian per depot") yang:
- Butuh cara mengaitkan Shipment/SPPB ke sesi — mekanismenya (pivot) SUDAH ADA di data model tapi UI pengisiannya MATI.
- Faktanya TIDAK diisi konsisten (dikonfirmasi MP-08, masih berlaku), sehingga metrik yang bergantung padanya (Expected Unit, Gap Unit) berisiko tidak mencerminkan kenyataan.

**Tidak ditemukan bukti kode** bahwa briefing pernah didesain per-Shift eksplisit (tidak ada kolom `shift`), per-Tim (tidak ada model Tim independen dari attendance), atau per-Kendaraan (bukan konsep di model manapun). **Belum ada solusi diusulkan di sini** sesuai instruksi eksplisit sprint ("Belum perlu memberikan solusi") — murni pencatatan perbedaan.

---

## 7. Impact Analysis — Jika FC Menjadi Unit-Centric (Scope 5, TEMUAN BARU)

Sprint OPS-09 (kemarin) sudah mengubah `OperationalTasks` (Tugas Operasional) dari satu-baris-satu-Shipment menjadi satu-baris-satu-Unit. **Ini adalah bukti langsung, bukan hipotesis** — berikut dampaknya terhadap Briefing, diverifikasi dari perubahan kode OPS-09 yang sesungguhnya terjadi:

### Apa yang TETAP AMAN
- `StuffingService::checkPreconditions()` — tidak disentuh OPS-09, tidak perlu disentuh: ia membaca `$shipment->assigned_depot_id`, field level-Shipment yang ADA dan IDENTIK terlepas dari bagaimana `OperationalTasks` menampilkan barisnya. **Terbukti langsung**: OPS-09 selesai kemarin tanpa satu baris pun kode Briefing perlu diubah.
- Card "Setup Hari Ini" (`getDailySetup()`) — query `depot_id`+`date`, sepenuhnya independen dari representasi baris tabel manapun.
- Seluruh alur `BriefingAttendance`/manpower/APD — sama sekali tidak menyentuh Shipment/Unit.

### Apa yang BISA RUSAK (potensial, bukan pasti) jika Unit-Centric diperluas lebih jauh
- **Jika suatu saat "Ringkasan Beban Kerja" diperbaiki untuk benar-benar dipakai** (bukan sekadar dibiarkan basi seperti sekarang) DAN logikanya ditulis mengasumsikan "satu shipment = satu unit kerja" (mis. via pivot `shipments()`), maka **akan langsung tidak konsisten** dengan realita Unit-Centric di mana satu shipment bisa mewakili banyak unit dengan progres berbeda-beda. Pivot saat ini beroperasi di level Shipment, bukan Unit — jika Briefing suatu saat perlu tahu "unit MANA SAJA yang sudah dibriefing," struktur pivot saat ini tidak cukup granular.
- **Tidak ada risiko terhadap kode yang SUDAH ADA hari ini** — karena (dikonfirmasi §5) tidak ada modul eksekusi (Pickup/Handover/Inspection/sebagian besar Stuffing) yang bergantung pada Briefing sama sekali. Risiko hanya muncul untuk FITUR MASA DEPAN yang belum dibangun.

### Modul yang bergantung pada Briefing (ringkasan ulang dari §5, untuk kelengkapan Scope 5)
Pickup (tidak), Gate/domain guard (tidak), MP Readiness (Briefing ADALAH sumber MP Readiness, bukan konsumen), Inspection (tidak), ShipmentTrack (tidak), StuffingWorkspace (ya, soft-gate).

---

## 8. Rekomendasi Arsitektur untuk Phase Berikutnya (Scope 6 — 3 Opsi, Objektif, TIDAK Memilih)

### Option A — Briefing Tetap Shipment-Centric
Kembalikan/perkuat pivot `briefing_session_shipments` sebagai mekanisme utama: aktifkan lagi UI "Shipment Kandidat" di `BriefingSessionResource`, jadikan "Expected Unit"/"Gap Unit" metrik yang benar-benar dipakai keputusan.

| | |
|---|---|
| **Keuntungan** | Selaras dengan diagram yang sudah lama diasumsikan tim (brief sprint ini pun masih menganggap ini modelnya); tidak perlu migrasi skema baru — pivot sudah ada; "SPPB apa saja yang dibriefing hari ini" jadi bisa dijawab lagi. |
| **Kekurangan** | Bertentangan dengan bukti operasional (§6) bahwa briefing K3 secara alami per-kru/per-depot, bukan per-SPPB; harus membangun ulang UI pemilihan shipment yang sengaja dimatikan (kemungkinan dimatikan karena tidak dipakai FC di lapangan — perlu konfirmasi operasional sebelum diaktifkan lagi, bukan diasumsikan); tidak menyelesaikan mismatch granularitas Unit yang muncul di §7. |
| **Dampak implementasi** | Sedang — UI form + rework metrik existing, tidak perlu migration baru (skema sudah ada), tapi confirm dulu ke tim lapangan (rekomendasi #1 MP-08, masih belum terjawab). |

### Option B — Briefing Menjadi Unit-Centric
Briefing menyimpan referensi ke Unit spesifik yang "sudah dibriefing" (via pivot baru `briefing_session_units` atau relasi transitif lewat shipment→unit).

| | |
|---|---|
| **Keuntungan** | Konsisten granularitas dengan arah OPS-09 (Unit-Centric Operational Task); memungkinkan "Tahap Operasional Saat Ini" per unit suatu saat menyertakan status briefing per unit jika dibutuhkan. |
| **Kekurangan** | **Tidak didukung realita operasional (§6)** — briefing K3 tidak dilakukan per unit kendaraan, jadi granularitas Unit tidak mencerminkan bagaimana briefing SUNGGUHAN terjadi di lapangan; berisiko menciptakan model data yang "benar secara arsitektur konsisten" tapi salah secara domain nyata — pola yang sama persis yang MP-08 sudah peringatkan (diagram vs kenyataan). |
| **Dampak implementasi** | Besar — migration baru, model baru, tidak ada fondasi yang bisa dipakai ulang dari pivot Shipment yang sudah ada. |

### Option C — Briefing Menjadi Operational Session Murni (Depot + Hari), Melepas Total Ikatan ke Shipment/Unit
Formalkan apa yang SUDAH menjadi arsitektur aktif sejak 17 Juni 2026: hapus/nonaktifkan permanen kolom `shipment_id` (deprecated) dan pivot `briefing_session_shipments` (tidak reliable), jadikan Briefing murni "sesi kerja harian per depot" tanpa pretensi terhubung ke SPPB/Shipment/Unit tertentu. "Ringkasan Beban Kerja" didesain ulang total (atau dihapus) karena premisnya sendiri sudah tidak berlaku.

| | |
|---|---|
| **Keuntungan** | **Paling jujur terhadap bukti kode DAN operasional** — mengakui secara eksplisit apa yang sudah terjadi de facto (§1, §6), bukan mempertahankan ilusi keterhubungan yang sudah lama tidak berfungsi (temuan MP-08 §Inkonsistensi). Menghapus risiko data basi (Expected Unit/Gap Unit) alih-alih membiarkannya diam-diam salah. Paling sedikit kode baru — banyak yang **dihapus**, bukan ditambah. |
| **Kekurangan** | Kehilangan (secara resmi, bukan hanya secara praktik) kemampuan menjawab "SPPB apa saja yang dibriefing hari ini" — meski saat ini kemampuan itu sudah tidak reliable, menghapusnya berarti tidak ada jalan mundur tanpa membangun ulang dari nol jika suatu saat dibutuhkan. |
| **Dampak implementasi** | Kecil-sedang — sebagian besar PENGHAPUSAN kode mati (comment block, kolom deprecated, metrik tidak reliable), bukan penambahan; migration untuk drop kolom/tabel opsional (bisa dibiarkan sebagai dead schema jika ingin lebih hati-hati). |

**Tidak ada opsi yang dipilih di sini** sesuai instruksi Scope 6 ("Jangan langsung memilih"). Ketiganya membutuhkan **konfirmasi operasional terlebih dahulu** (rekomendasi #1 MP-08, masih terbuka): dari mana pivot `briefing_session_shipments` sebenarnya diisi hari ini (jika benar-benar terisi oleh sesuatu di luar kode yang ditelusuri sprint ini/MP-08), dan apakah tim lapangan benar-benar butuh granularitas per-SPPB di briefing atau selama ini sudah cukup dengan per-depot.

---

## Konfirmasi Batas (Scope 7)

- ✅ Tidak ada file model/migration/service/Blade yang diubah — seluruh sprint ini murni `Read`/`Grep`/`Bash` (read-only).
- ✅ Tidak ada migration dibuat.
- ✅ Tidak ada PR dibuat.
- ✅ Seluruh temuan disertai bukti kode (path + line/snippet), bukan asumsi dari nama kolom/label.
