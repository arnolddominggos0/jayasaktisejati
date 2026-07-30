# Audit BRF-STATUS-01 — Business Rule "READY" vs APD

**Status:** AUDIT SELESAI. Tidak ada kode yang diubah.
**Tanggal:** 29 Juli 2026
**Tipe:** Audit domain / business rule (bukan UI)

---

## Ringkasan Eksekutif

Temuan dikonfirmasi: **Status Kesiapan Operasional sama sekali tidak memperhitungkan Stok APD.**

Bug berhasil direproduksi pada **data produksi nyata** — kedua sesi briefing yang ada saat ini menampilkan `READY` sementara seluruh 4 baris APD berstatus `belum_diisi`, dan gate operasional dalam kondisi `OPEN`:

```
session=1 | mp_status=cleared | sufficient=true | READY=YES | apd_rows=4 belum_diisi=4 | gate=OPEN
session=5 | mp_status=cleared | sufficient=true | READY=YES | apd_rows=4 belum_diisi=4 | gate=OPEN
```

Namun temuan ini **lebih halus dari dugaan awal**. Sistem sebenarnya *sudah* memperhitungkan APD — tetapi **APD perorangan** (`briefing_attendances.has_ppe`), bukan **stok APD depot** (`stock_apd_checks`). Keduanya adalah konsep berbeda yang mudah tertukar. Detail di §4 dan §5.

Ditemukan juga bahwa **`Approved`, `Failed`, dan `WaitingAction` secara praktis tidak dapat dicapai** oleh kode aplikasi (§4), dan terdapat **kolom APD yang mati total** di schema (`apd_request_status`, `apd_request_note`) yang mengindikasikan rancangan APD yang tidak pernah diselesaikan (§6).

---

## 1. Current Flow

Workflow yang dirancang (menurut brief):

```
Briefing → MP Check → APD Check → Pickup
```

Workflow yang **benar-benar diberlakukan sistem**:

```
Briefing → MP Check ─────────────────► (gate) → Stuffing
                      APD Check
                      (tidak terhubung ke gate mana pun)
```

Dua koreksi penting terhadap asumsi brief:

1. **Gate operasional melindungi Stuffing, bukan Pickup.** Satu-satunya konsumen `DailyBriefingGate` adalah aksi Stuffing:
   - `OperationalTasks.php:557` — di dalam `recordFieldNotes`, hanya bila `$status === TrackStatus::Stuffing`
   - `OperationalTasks.php:861` — aksi `stuffing`
   - `StuffingWorkspace.php:84`
   - `StuffingService.php:35`

   **Pickup tidak pernah di-gate oleh briefing sama sekali.** Aksi `startPickup` di `OperationalTasks.php` tidak memanggil `DailyBriefingGate`.

2. **APD Check bukan tahap yang mengunci apa pun.** Ia murni pencatatan data; tidak ada satu pun percabangan logika di aplikasi yang membacanya untuk mengizinkan/melarang aksi.

---

## 2. Current Dependency Graph

```
Status Kesiapan Operasional  (badge di View Briefing)
  ViewBriefingSession.php:34-41
        │
        ├── summary_sufficient  (boolean, kolom DB)
        │        └── dihitung di DUA tempat yang identik:
        │              • BriefingSessionEvaluator::evaluate()
        │              • BriefingSession::booted() → saving()
        │                    │
        │                    └── readyManpowerCount()   ← SATU-SATUNYA sumber angka
        │                          BriefingSession.php:274-287
        │                             SELECT COUNT(*) FROM briefing_attendances
        │                             WHERE attendance_status = 'present'
        │                               AND has_ppe = true          ← APD PERORANGAN
        │                               AND ( recheck_result = 'FIT'
        │                                     OR (fit_status = 'FIT' AND recheck_result IS NULL) )
        │                          dibandingkan dengan:
        │                             summary_headcount  (target SOP, input manual)
        │
        └── mp_check_status === 'cleared'   (enum MPCheckStatus)
                 └── di-set oleh evaluator yang sama, dari perbandingan
                     readyManpowerCount() >= summary_headcount

stock_apd_checks  (APD DEPOT)
        │
        └── getComputedStatusAttribute() → 'cukup' | 'kurang' | 'belum_diisi'
                 │
                 ├──► Relation Manager APD (tampilan tabel)          [display only]
                 ├──► Widget FcOperationalReadiness ($issues)        [advisory only]
                 ├──► Widget FcTodayBriefingSummary                  [display only]
                 └──► ✗ TIDAK PERNAH masuk ke summary_sufficient
                      ✗ TIDAK PERNAH masuk ke mp_check_status
                      ✗ TIDAK PERNAH masuk ke DailyBriefingGate

Trigger re-evaluasi:
  BriefingAttendance::saved()  → BriefingSessionEvaluator::evaluate($session)
  BriefingAttendance::deleted() → BriefingSessionEvaluator::evaluate($session)
  BriefingSession::saving()     → logika evaluator inline (duplikat)
  ✗ StockApdCheck TIDAK memiliki observer/hook apa pun
```

**Catatan arsitektur:** `StockApdCheck` tidak punya `booted()`, tidak punya observer, dan tidak memicu evaluasi ulang sesi. Mengisi atau mengubah stok APD **tidak pernah** menyebabkan status briefing dihitung ulang.

---

## 3. Current READY Logic

Badge di `ViewBriefingSession.php:34-41`:

```php
$sufficient = (bool) $record->summary_sufficient;
$cleared    = ($record->mp_check_status ...) === 'cleared';

return ($sufficient && $cleared) ? 'ready' : 'not_ready';
```

Kedua variabel berasal dari sumber yang sama (`BriefingSessionEvaluator`), sehingga secara efektif:

```
READY  ⇔  readyManpowerCount() >= summary_headcount
          AND summary_headcount > 0
          AND status bukan Failed/Approved
          AND pending_activity = false
```

Definisi READY ini **direplikasi di 3 tempat berbeda** dengan formula identik namun tidak berbagi kode:

| Lokasi | Formula |
|---|---|
| `ViewBriefingSession.php:34-41` | `summary_sufficient && mp_check_status === 'cleared'` |
| `BriefingSessionResource.php:302-310` (kolom tabel) | sama persis |
| `DailyBriefingGate::isReady()` | `summary_sufficient && status IN (Cleared, Approved)` |

⚠️ **Perbedaan halus:** `DailyBriefingGate` menerima **`Approved`** sebagai READY, sedangkan badge UI **tidak**. Artinya sesi ber-status `Approved` akan menampilkan **`✗ NOT READY` di layar tetapi gate Stuffing tetap OPEN** — inkonsistensi nyata, meski saat ini tidak terpicu karena `Approved` tidak pernah di-set (lihat §4).

Selain itu `BriefingSessionResource.php` dan `MpReadinessMonitoring.php:511` menggunakan definisi READY yang **lebih longgar lagi** — hanya `summary_sufficient` tanpa cek status:

```php
'status' => $row->summary_sufficient ? 'READY' : 'NOT READY',   // MpReadinessMonitoring:511
```

Jadi ada **tiga definisi READY yang berbeda** di sistem.

---

## 4. Current APD Logic

### 4a. Dua konsep "APD" yang berbeda — ini akar kebingungannya

| | APD Perorangan | Stok APD Depot |
|---|---|---|
| Lokasi data | `briefing_attendances.has_ppe` (boolean per MP) | tabel `stock_apd_checks` (per jenis APD) |
| Arti | "MP ini memakai APD lengkap" | "depot punya cukup stok helm/rompi/sepatu/sarung tangan" |
| Masuk ke READY? | ✅ **YA** — wajib `has_ppe = true` di `readyManpowerCount()` | ❌ **TIDAK** — tidak pernah dibaca logika status |
| Terlihat di UI | kolom "APD" di tabel MP Check | Relation Manager "Stok APD" |

Jadi pernyataan "READY tidak mempertimbangkan APD" **tidak sepenuhnya tepat**: READY *mewajibkan* setiap MP yang dihitung memiliki APD perorangan lengkap. Yang benar-benar diabaikan adalah **stok APD depot**.

### 4b. Apakah APD punya evaluator sendiri?

**Tidak ada.** Dikonfirmasi:
- Tidak ada file `app/Services/*Apd*` atau `*Ppe*`
- Tidak ada kolom `apd_check_status` di database
- `StockApdCheck` tidak punya `booted()`, observer, atau service

Yang ada hanyalah **accessor per-baris** di `StockApdCheck.php:52-59`:

```php
public function getComputedStatusAttribute(): string
{
    if ($this->stock_available === null || $this->required_quantity === null) {
        return 'belum_diisi';
    }
    return $this->stock_available >= $this->required_quantity ? 'cukup' : 'kurang';
}
```

Tidak ada agregasi level-sesi ("apakah SEMUA APD cukup?") di mana pun.

### 4c. Kenapa READY tetap muncul walau APD "Belum Diisi"

Karena tidak ada satu baris kode pun yang menghubungkan keduanya. Rantai `summary_sufficient` (§2) hanya menyentuh `briefing_attendances`. Tabel `stock_apd_checks` tidak pernah muncul dalam query maupun percabangan yang menghasilkan status.

Diperkuat oleh data produksi: 8 dari 8 baris `stock_apd_checks` punya `stock_available = NULL` (semua "Belum Diisi"), namun kedua sesi tetap `READY` dan gate `OPEN`.

### 4d. Bug sekunder pada widget (di luar scope, dilaporkan saja)

`FcOperationalReadiness.php:57` dan `:82` mendeteksi kekurangan APD dengan:

```php
$c->status === 'kurang' || ($c->stock_available !== null && ... )
```

Dua masalah:
1. Membaca kolom mentah `status` — padahal `StockApdCheck.php:47-49` secara eksplisit memperingatkan: *"ALWAYS use this instead of the raw `status` DB column to avoid the AppSheet inconsistency bug (status='cukup' but stock < required)"*. Widget ini melanggar peringatan modelnya sendiri.
2. Kondisi `stock_available !== null` menyebabkan APD **"belum diisi" tidak pernah dihitung sebagai masalah**. Jadi meski FC belum mengisi APD sama sekali, widget tidak menampilkan peringatan apa pun.

### 4e. Kolom APD yang mati total

`briefing_sessions` memiliki `apd_request_status` dan `apd_request_note` (migration `2026_05_26_160543`, bagian bertanda `// APD request`). Kolom ini:
- ada di schema ✅
- ada di `$fillable` ✅
- **tidak pernah dibaca atau ditulis oleh kode aplikasi mana pun** ❌ (0 hasil di luar `$fillable`, migration, dan seeder)
- 0 baris terisi di produksi

Ini bukti kuat bahwa **alur APD memang dirancang untuk terhubung ke status sesi, tetapi implementasinya tidak pernah diselesaikan.**

---

## 5. Relationship MP ↔ APD ↔ READY

```
MP Check ──────────────► READY  ✅ (jalur penuh, otomatis, re-evaluasi via observer)
   │
   └── has_ppe (APD perorangan) ✅ ikut diperhitungkan

Stok APD ─ ─ ─ ─ ─ ─X─► READY  ❌ (tidak ada jalur sama sekali)
   │
   └──► issues[] di FcOperationalReadiness  (hanya teks peringatan, tidak mengunci)

READY ──────────────────► DailyBriefingGate ──► Stuffing  ✅
READY ─ ─ ─ ─ ─ ─ ─ ─X─► Pickup                            ❌ (tidak di-gate)
```

**Apakah dirancang independen, atau business rule yang belum selesai?**

Bukti kuat mengarah ke **belum selesai**, bukan keputusan desain:

1. Kolom `apd_request_status`/`apd_request_note` dibuat khusus untuk alur APD, lalu tidak pernah dipakai (§4e).
2. `FcOperationalReadiness` **sudah menghitung** kekurangan APD dan memasukkannya ke daftar `$issues`, tetapi variabel `$isReady` (baris 63) sengaja hanya membaca `mp_check_status` — seperti pekerjaan yang berhenti di tengah.
3. Enum status bernama `MPCheckStatus` (khusus MP), dan tidak pernah dibuat padanan untuk APD — sedangkan `ContainerReadinessSession` justru **punya** pola lengkapnya (`summary_sufficient` dihitung otomatis di `booted()`, plus accessor `READY/NOT READY`). Pola yang benar sudah ada di codebase, hanya belum diterapkan ke APD.

---

## 6. Business Rule Gap

| # | Gap | Bukti | Dampak |
|---|---|---|---|
| G1 | Stok APD tidak masuk perhitungan READY | §4c, direproduksi di produksi | Sesi dinyatakan "Operasional Dapat Dimulai" padahal stok APD belum pernah diperiksa |
| G2 | `StockApdCheck` tidak memicu re-evaluasi sesi | tidak ada observer/`booted()` | Bahkan bila G1 diperbaiki, mengisi APD tidak akan memperbarui status tanpa hook baru |
| G3 | Tiga definisi READY berbeda | §3 | `MpReadinessMonitoring` bisa menampilkan READY sementara badge View menampilkan NOT READY |
| G4 | Gate menerima `Approved`, badge UI tidak | `DailyBriefingGate:34` vs `ViewBriefingSession:38` | Sesi `Approved` → layar NOT READY tetapi Stuffing tetap terbuka |
| G5 | Logika evaluator terduplikasi | `BriefingSessionEvaluator` + `BriefingSession::booted()` | Perubahan aturan harus diedit di 2 tempat; risiko divergensi |
| G6 | `Approved` / `Failed` / `WaitingAction` tidak dapat dicapai | tidak ada kode yang menulisnya; `pending_activity` tidak pernah di-set | State machine yang didokumentasikan sebagian besar mati |
| G7 | Kolom `apd_request_*` mati | §4e | Schema menyesatkan pembaca berikutnya |
| G8 | Widget memakai kolom `status` mentah + mengabaikan `belum_diisi` | §4d | FC tidak diberi peringatan apa pun saat APD kosong |
| G9 | Pickup tidak di-gate briefing | §1 | Bila niat bisnisnya "briefing sebelum operasional", Pickup adalah lubang |

### State machine aktual (jawaban pertanyaan 4)

```
                    Shipment.php:401
                          │  (set 'draft' saat sesi dibuat otomatis)
                          ▼
                       Draft
                          │  evaluator berjalan pada save pertama
                          ▼
        ┌─────────────────┴─────────────────┐
        │                                   │
   ready < required                   ready >= required
        ▼                                   ▼
    OnCheck  ◄──────── bolak-balik ──────► Cleared
   (otomatis)                            (otomatis)

    WaitingAction ── HANYA jika pending_activity = true
                     → tidak pernah di-set kode mana pun  ✗ UNREACHABLE

    Approved / Failed ── terminal, dihormati evaluator sebagai guard
                     → tidak pernah di-set kode mana pun  ✗ UNREACHABLE
```

Syarat transisi (dari `BriefingSessionEvaluator::evaluate()`):

| Ke state | Syarat |
|---|---|
| `WaitingAction` | `pending_activity === true` (unreachable) |
| `Cleared` | `summary_headcount > 0` DAN `readyManpowerCount() >= summary_headcount` |
| `OnCheck` | selain di atas |
| `Approved`/`Failed` | hanya guard — bila sudah bernilai ini, `mp_check_status` tidak diubah, tapi `summary_sufficient` tetap dihitung ulang |

**APD tidak muncul dalam satu pun syarat transisi.**

---

## 7. Recommendation

### Option A — READY hanya berdasarkan MP (pertahankan perilaku saat ini)

Artinya: menegaskan bahwa "READY" = **kesiapan manpower**, bukan kesiapan operasional menyeluruh.

**Konsekuensi arsitektur:**
- Tidak ada perubahan logika; risiko regresi nol.
- **Wajib** mengubah label agar tidak menyesatkan: `"✓ READY — Operasional Dapat Dimulai"` → mis. `"✓ MP READY — Manpower Siap"`. Label saat ini adalah sumber utama kebingungan, bukan logikanya.
- Stok APD tetap sebagai pencatatan/audit, bukan kontrol.
- G3/G4/G5 sebaiknya tetap dirapikan (definisi READY tunggal), karena itu bug konsistensi terlepas dari keputusan APD.
- Kolom `apd_request_*` sebaiknya dihapus atau didokumentasikan sebagai deprecated.
- **Risiko bisnis:** bila SOP K3 mensyaratkan APD tersedia sebelum kerja, sistem tetap tidak menegakkannya — kepatuhan bergantung pada disiplin manual.

### Option B — READY berdasarkan MP + APD

Artinya: "Operasional Dapat Dimulai" benar-benar berarti manpower **dan** APD siap.

**Konsekuensi arsitektur — lebih besar dari sekadar menambah satu kondisi:**

1. **Perlu definisi agregat APD level-sesi** yang saat ini belum ada. Harus diputuskan aturannya:
   - Apakah `belum_diisi` = tidak siap? (kemungkinan besar ya)
   - Apakah wajib keempat jenis APD ada barisnya, atau cukup yang tercatat?
   - Contoh: `apdReady = stockApdChecks.count() === 4 && semua computed_status === 'cukup'`
   - Pola rujukan sudah ada di `ContainerReadinessSession::booted()` — bisa ditiru.

2. **Perlu hook re-evaluasi baru pada `StockApdCheck`** (G2). Tanpa ini, mengisi APD tidak akan mengubah status. Ini berarti observer/`booted()` baru — yaitu **membuat komponen baru**, bukan sekadar mengubah kondisi.

3. **Dampak langsung ke gate Stuffing.** `DailyBriefingGate::isReady()` dipakai `StuffingService` dan 2 aksi di `OperationalTasks`. Bila APD ikut dihitung, **Stuffing akan langsung terblokir** untuk sesi yang APD-nya belum diisi. Pada data produksi saat ini, **kedua sesi akan berubah dari OPEN → blocked seketika**. Ini perubahan perilaku operasional yang harus dikomunikasikan ke FC lebih dulu, bukan di-deploy diam-diam.

4. **Data historis akan berubah makna.** `summary_sufficient` sudah tersimpan di ribuan baris historis dan dipakai laporan (`MonthlyBriefingSummaryWidget`, `OperationalStatsWidget`, `MpReadinessMonitoring`). Bila formulanya berubah, angka historis menjadi tidak sebanding antar-periode. Perlu diputuskan: hitung ulang backfill, atau pisahkan kolom baru (mis. `apd_sufficient`) agar metrik lama tetap utuh.

5. **Kemungkinan besar butuh kolom/enum baru** (`apd_check_status` atau `apd_sufficient`) agar bisa membedakan "gagal karena MP" vs "gagal karena APD" di UI dan laporan. Menggabungkan keduanya ke satu boolean akan menghilangkan informasi diagnostik.

**Rekomendasi bertahap bila memilih Option B:** pisahkan menjadi dua status berdampingan (`MP Ready` dan `APD Ready`) yang ditampilkan terpisah lebih dulu, baru kemudian menggabungkannya ke gate setelah FC terbiasa mengisi APD. Ini menghindari pemblokiran mendadak pada alur kerja yang sedang berjalan.

### Perbaikan yang layak dilakukan pada opsi mana pun

- **G3/G4/G5** — satukan definisi READY ke satu sumber (mis. method di `BriefingSession`), hapus duplikasi evaluator. Ini murni bug konsistensi.
- **G8** — perbaiki `FcOperationalReadiness` agar memakai `computed_status` dan memperlakukan `belum_diisi` sebagai isu. Perbaikan kecil, langsung memberi FC sinyal yang hilang saat ini.
- **G6/G7** — putuskan nasib state & kolom yang tidak terpakai (hidupkan atau hapus).
- **G9** — konfirmasi apakah Pickup memang sengaja tidak di-gate.

---

## Daftar File yang Diaudit

**Inti logika status:**
- `app/Services/BriefingSessionEvaluator.php`
- `app/Models/BriefingSession.php` (`readyManpowerCount`, `isOperationallyReady`, `booted`, `isTerminal`)
- `app/Enums/MPCheckStatus.php`
- `app/Services/DailyBriefingGate.php`

**APD:**
- `app/Models/StockApdCheck.php`
- `app/Filament/FC/Resources/BriefingSessionResource/RelationManagers/StockApdChecksRelationManager.php`
- `database/migrations/2026_05_26_160543_add_flow_columns_to_briefing_sessions_table.php`

**Konsumen status:**
- `app/Filament/FC/Resources/BriefingSessionResource/Pages/ViewBriefingSession.php`
- `app/Filament/FC/Resources/BriefingSessionResource.php`
- `app/Filament/FC/Pages/MpReadinessMonitoring.php`
- `app/Filament/FC/Pages/Dashboard/Dashboard.php`
- `app/Filament/FC/Pages/OperationalTasks.php`
- `app/Filament/FC/Pages/StuffingWorkspace.php`
- `app/Services/Stuffing/StuffingService.php`
- `app/Filament/FC/Widgets/FcOperationalReadiness.php`
- `app/Filament/FC/Widgets/FcTodayBriefingSummary.php`
- `app/Filament/FC/Widgets/MonthlyBriefingSummaryWidget.php`
- `app/Filament/FC/Widgets/OperationalStatsWidget.php`

**Trigger / integrasi:**
- `app/Models/BriefingAttendance.php` (`saved`/`deleted` → evaluator)
- `app/Services/AppSheetService.php`
- `app/Services/AppSheet/Concerns/RecalculatesBriefingSession.php`

**Rujukan pola pembanding:**
- `app/Models/ContainerReadinessSession.php` (contoh evaluasi otomatis yang lengkap)

---

*Akhir audit BRF-STATUS-01. Tidak ada perubahan kode, migration, atau data dalam sprint ini.*
