# Architecture Review — Planning Loading vs Stuffing Execution

**Status:** REVIEW ONLY — tidak ada kode yang diubah.
**Tanggal:** 23 Juli 2026
**Rujukan:** `DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md`, `SPRINT-CA-01.5-REFACTOR-REPORT.md`, `SPRINT-UX-02-CONSOLIDATION-REPORT.md`, `SPRINT-ST-01-STUFFING-WORKFLOW.md`, `AUDIT-CR-02-CONTAINER-READINESS-AS-PLANNING.md`

---

## Temuan Utama — baca ini dulu

Sebelum menjawab 6 pertanyaan, ada satu temuan yang mengubah bentuk seluruh jawaban: **codebase saat ini punya DUA implementasi "container planning + stuffing execution" yang lengkap tapi tidak pernah tersambung satu sama lain.**

**Jalur A — LEGACY, tapi HIDUP (dipakai operator hari ini):**
```
Handover Depo (modal Update Track)
  → Repeater bebas isi container_display (teks bebas, tanpa validasi Service/kapasitas/keunikan)
  → menggerbangi unassigned_container_count (raw SQL: WHERE container_display IS NULL)
  → menggerbangi tombol "Stuffing & Segel"
  → klik tombol → appendTrack(TrackStatus::Stuffing) — HANYA mencatat status, tidak ada eksekusi terstruktur apa pun
```
Dikonfirmasi lewat kode: komentar di `OperationalTasks.php:960` bahkan secara literal menyebut ini `// ── Planning Loading: assign container per unit`.

**Jalur B — TERSTRUKTUR, tapi TERISOLASI (dibangun lengkap, tidak pernah dipakai):**
```
ContainerReadinessSession (CR-02: container + service per hari)
  → ContainerAllocationWorkspace + ContainerAllocationService (CA-01/CA-01.5): assign/move/remove/markContainerReady
  → Container.is_ready_for_stuffing, Unit.container_id, Unit.allocation_status
  → StuffingWorkspace + StuffingService (ST-01): markUnitStuffed/unmarkUnitStuffed
```
Dikonfirmasi lewat `grep` menyeluruh: **`ContainerAllocationWorkspace::getUrl()` dan `StuffingWorkspace::getUrl()` TIDAK PERNAH direferensikan di file lain mana pun di aplikasi ini.** Kedua halaman itu `shouldRegisterNavigation = false` DAN tidak punya entry-point apa pun — routable tapi betul-betul tidak bisa dicapai operator dari alur kerja manapun saat ini.

**Konsekuensi:** tiga sprint (CA-01, CA-01.5, ST-01) membangun mesin yang justru menjawab TEPAT PERTANYAAN yang Anda ajukan sekarang — tapi mesin itu belum "dicolokkan" ke listrik. Pertanyaan Anda bukan "bagaimana membangun Planning Loading" — jawabannya sudah ada. Pertanyaannya adalah "bagaimana menyambungkan yang sudah dibangun ke alur yang hidup, dan apa nasib mekanisme lama."

---

## 1. Tempat paling tepat untuk memilih Container/Service/Unit Assignment setelah Handover Depo

**Jawaban: B — `ContainerAllocationWorkspace` yang sudah ada.**

- **Opsi A** (modal Update Handover) memang SECARA NIAT sudah "Planning Loading" (lihat komentar kode di atas) — tapi implementasinya adalah `TextInput` bebas per unit di dalam Repeater, tanpa validasi apa pun: tidak dicek terhadap daftar container yang benar-benar terdaftar di Container Readiness, tidak ada Service, tidak ada kapasitas, tidak ada cek "container ini sudah penuh". Ini snapshot cepat, bukan alat planning.
- **Opsi B** (`ContainerAllocationWorkspace` + `ContainerAllocationService`) SUDAH mengimplementasikan persis tiga hal yang Anda tanyakan, dengan validasi penuh:
  - *Pilih container*: `Container::resolveForSession()` — hanya container yang benar-benar terdaftar di `ContainerReadinessSession` (SSOT) yang bisa dipakai.
  - *Pilih service*: `configureContainerAction()` — set `Container.type` (Rack/Regular).
  - *Masukkan unit*: `ContainerAllocationService::assign()` — dengan guard kapasitas (`guardCapacity()`), guard container harus sudah dikonfigurasi (`guardContainerConfigured()`), guard container belum di-lock (`guardContainerReady()`).
- Tidak perlu modul baru. Yang dibutuhkan HANYA entry-point (tombol/link) dari titik "Handover Depo selesai" menuju `ContainerAllocationWorkspace::getUrl()` — murni routing, bukan logic baru.

**Rekomendasi:** jangan perluas Opsi A lebih jauh (jangan tambah Service/validasi ke Repeater `container_display`). Biarkan `container_display` tetap snapshot lama untuk konsumen lamanya (docblock `Unit.php` sudah eksplisit soal ini), dan arahkan operator ke Opsi B untuk planning yang sesungguhnya.

---

## 2. Apakah Planning Stuffing (Container Allocation) sudah cukup, tanpa UI baru?

**Ya.** Diaudit ulang `ContainerAllocationService`:

| Method | Fungsi | Guard |
|---|---|---|
| `assign()` | Container ← Unit | eligible, container belum lock, container sudah dikonfigurasi, kapasitas cukup |
| `move()` | Pindah ke container lain | sama + container asal belum lock |
| `remove()` | Keluarkan dari container | container belum lock |
| `markContainerReady()` | Kunci container, expose ke Stuffing | container tidak kosong |
| `unmarkContainerReady()` | Buka kunci (jalur koreksi) | — |

`ContainerAllocationWorkspace` sudah merender kedua sisi sekaligus (pool Unit belum teralokasi + daftar Container beserta sisa slot) — persis bentuk yang dibutuhkan untuk kerja "Container ↓ Unit Assignment". Satu-satunya gap yang pernah teridentifikasi (di audit CR-02 sebelumnya) adalah tampilannya belum dikelompokkan per-Shipment — itu murni penyesuaian query/tampilan (data pendukungnya, `Container::shipment()`, sudah ada sejak ST-01), bukan kapabilitas yang hilang. **Tidak perlu UI baru untuk fungsi intinya.**

---

## 3. Saat operator klik "Stuffing & Segel" — membuat planning atau menjalankan planning?

**Sesuai arsitektur yang dimaksud (niat desain): HANYA menjalankan planning yang sudah ada.** Ini bahkan ditegakkan sebagai hard rule di kode: `StuffingService::guardUnitReadyToStuff()` menolak keras unit yang `container_id === null` dengan pesan eksplisit *"Stuffing tidak membuat rencana baru."*

**Tapi — ini bagian yang perlu Anda sadari:** tombol "Stuffing & Segel" yang SEKARANG hidup di `OperationalTasks.php` **tidak memanggil mesin itu sama sekali.** Actionnya persis:
```php
->action(function (Shipment $record, array $data) {
    $record->appendTrack(TrackStatus::Stuffing, $data['note'] ?? null);
})
```
Hanya mencatat perubahan status Track. Tidak membuka `StuffingWorkspace`, tidak mengecek `Container.is_ready_for_stuffing`, tidak menyentuh `ContainerAllocationService` sama sekali. Visibility tombolnya pun digerbangi oleh `unassigned_container_count` yang dihitung dari `container_display` (Jalur A/legacy) — bukan dari `Unit.container_id` (Jalur B/terstruktur).

**Kesimpulan:** niat arsitektur sudah benar dan sudah ditegakkan DI DALAM mesin terstruktur (ST-01) — tapi mesin itu belum jadi yang dijalankan saat tombol "Stuffing & Segel" diklik pada alur yang hidup hari ini.

---

## 4. Apakah sudah ada pemisahan Planning Container vs Actual Container?

**Tidak — dan ini penting: yang ada BUKAN pemisahan yang disengaja, melainkan DUPLIKASI YANG TIDAK SINKRON.**

| Field | Ditulis di | Dibaca oleh | Sifat |
|---|---|---|---|
| `Unit.container_display` | Modal Handover Depo (teks bebas) | `daftar-unit.blade.php` (pengelompokan kartu Workspace), `unassigned_container_count` (gate tombol Stuffing) | Legacy snapshot, tanpa validasi |
| `Unit.container_id` (FK) + tabel `containers` | `ContainerAllocationService::assign()/move()` | `StuffingService` (ST-01), `ContainerAllocationWorkspace` sendiri | Terstruktur, tervalidasi, sudah ber-Service/kapasitas |

Kedua field ini **independen — tidak ada mekanisme apa pun yang menyinkronkan keduanya.** Operator bisa saja mengisi `container_display = "REG005"` di modal Handover, lalu (kalau Jalur B pernah dipakai) `container_id` menunjuk ke container `"REG002"` — dan sistem tidak akan tahu/memperingatkan keduanya berbeda. Ini bukan arsitektur Plan-vs-Actual yang disengaja; ini adalah dua sumber kebenaran yang kebetulan hidup berdampingan karena Jalur B dibangun belakangan dan belum menggantikan Jalur A.

Juga perlu dicatat: **Stuffing (ST-01) sendiri TIDAK punya kolom "actual container" yang terpisah dari "planned container".** `markUnitStuffed()` hanya mengonfirmasi bahwa `Unit.container_id` yang SUDAH ada itu benar secara fisik — ia tidak membedakan "kontainer yang direncanakan" vs "kontainer tempat unit benar-benar dimasukkan". Kalau keduanya berbeda secara fisik, hari ini **tidak ada tempat untuk mencatatnya** — ini relevan langsung ke Pertanyaan 5.

---

## 5. Jika container berubah saat Stuffing (Planning=A → Actual=B) — pendekatan paling konsisten?

**Rekomendasi: overwrite `Unit.container_id`, JANGAN buat field "actual" terpisah.** Alasan berbasis preseden yang sudah ada di codebase ini, bukan preferensi baru:

1. **Preseden pola "state yang berubah + butuh histori" di codebase ini SELALU: satu field current-truth + tabel log terpisah** — bukan dua field paralel. Contoh: `Shipment.status` (satu nilai aktif) + `ShipmentTrack` (log perubahan lewat waktu), bukan `planned_status` vs `actual_status`. Menambah `container_id_planned` + `container_id_actual` akan menjadi konsep baru yang belum pernah dipakai di mana pun di sistem ini — bertentangan dengan instruksi Anda untuk tidak menciptakan konsep baru.
2. **`ContainerAllocationService::move()` sudah ada** persis untuk "pindahkan unit ke container lain" — sebelum container di-lock (`is_ready_for_stuffing`). Untuk perubahan SEBELUM stuffing fisik dimulai, jalur ini sudah cukup, tanpa perubahan apa pun.
3. **Untuk perubahan SAAT container sudah di-lock/sedang di-stuffing** — jalur pembalikan yang sudah ada (`unmarkContainerReady()` → `move()` → `markContainerReady()` lagi di sisi Allocation; `unmarkUnitStuffed()` di sisi Stuffing yang otomatis membuka kembali container dari Full ke Stuffing) sudah cukup untuk mengoreksi `container_id` ke nilai baru — tanpa field tambahan.
4. **Kalau jejak audit ("kenapa berubah") tetap diinginkan** — field yang PALING kecil perubahannya dan SUDAH ADA untuk kebutuhan ini adalah `Unit.stuffing_remarks` (ditambahkan di ST-01, teks bebas) — operator bisa mencatat "dipindahkan dari REG001 ke REG002 karena X" di sana, tanpa kolom baru.

**Trade-off yang jujur:** overwrite berarti sistem TIDAK BISA menjawab pertanyaan analitik "seberapa sering container berubah selama stuffing" secara terstruktur/query-able di masa depan — hanya lewat teks bebas di `stuffing_remarks`. Tapi tidak ada satu pun bagian codebase saat ini yang meminta atau mendukung kemampuan analitik semacam itu; membangunnya sekarang berarti menciptakan konsep baru (field/tabel histori planned-vs-actual) yang eksplisit ingin Anda hindari. Simpan opsi itu untuk nanti kalau kebutuhannya benar-benar muncul secara eksplisit.

---

## 6. Reuse, service tersedia, field tersedia, dan perubahan minimum

**Bisa langsung dipakai ulang (tidak perlu diubah):**
- Halaman `ContainerAllocationWorkspace` — lengkap, tervalidasi (CA-01.5).
- Service `ContainerAllocationService` — assign/move/remove/markContainerReady, guard lengkap.
- Model `Container` (+ `resolveForSession()`), `ContainerReadinessSession` (+ field Service dari CR-02).
- Field `Unit.container_id`, `Unit.allocation_status` — sudah jadi struktur planning yang benar.
- Halaman `StuffingWorkspace` + `StuffingService` — lengkap, tervalidasi (ST-01), sudah menolak keras unit tanpa planning.

**Perubahan minimum yang dibutuhkan — MURNI WIRING ENTRY-POINT, tanpa model/enum/business rule baru:**

1. Tambahkan tombol/link menuju `ContainerAllocationWorkspace::getUrl()` — muncul begitu Track Handover selesai (pola visibility-nya sudah ada presedennya: `getHandoverWaitingCount()` di `OperationalShipmentPage`). Tidak perlu logic baru, murni menambahkan satu `Action::make()` atau header action yang mengarah ke URL yang sudah routable.
2. Tambahkan tombol/link menuju `StuffingWorkspace::getUrl(['shipment' => ...])` — sebagai pendamping (atau pengganti sebagian) tombol "Stuffing & Segel" yang sekarang hanya `appendTrack()`.
3. **Jangan ubah** modal Handover/`container_display` — biarkan tetap snapshot lama untuk konsumen lamanya.

**Satu keputusan yang SENGAJA tidak saya ambil sepihak** (butuh konfirmasi Anda, karena ini menyentuh business rule yang sedang hidup di production):
> Apakah gerbang `unassigned_container_count`/tombol "Stuffing & Segel" perlu dipindah membaca dari `Unit.container_id`/`Container.is_ready_for_stuffing` (Jalur B) alih-alih `container_display` (Jalur A)? Ini BUKAN perubahan routing/wiring — ini mengganti sumber kebenaran yang menggerbangi kapan operator boleh mulai Stuffing di alur yang sekarang hidup. Saya tidak mengubah ini karena review ini diminta "tanpa perubahan kode", dan keputusan ini punya dampak operasional langsung yang perlu Anda setujui secara eksplisit.

---

## Ringkasan Alur yang Direkomendasikan (tanpa membuat apa pun baru)

```
Handover Depo selesai (appendTrack sudah ada, tidak diubah)
   ↓
[BARU: tombol/link] → ContainerAllocationWorkspace (sudah ada, CA-01.5)
   → pilih Container (dari Readiness/CR-02) → set Service (Container.type)
   → assign Unit → Container (assign/move/remove)
   → markContainerReady (kunci, expose ke Stuffing)
   ↓
[BARU: tombol/link] → StuffingWorkspace (sudah ada, ST-01)
   → precondition check (sudah ada)
   → markUnitStuffed per unit (sudah ada, menolak unit tanpa planning)
   → auto-complete Container (sudah ada)
   ↓
appendTrack(TrackStatus::Stuffing) — tetap seperti sekarang, atau dipindah
   jadi konsekuensi otomatis setelah shipmentStuffingSummary() = ready_loading
   (opsional, di luar cakupan review ini — flagged, bukan diputuskan)
```

Tidak ada modul baru. Tidak ada field baru. Tidak ada enum baru. Yang hilang hanyalah kabelnya.
