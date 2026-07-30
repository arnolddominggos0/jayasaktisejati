# Architecture Review — Operational Workspace Inspection UX

**Status:** REVIEW ONLY — tidak ada kode yang diubah.
**Tanggal:** 23 Juli 2026
**File yang diaudit:** `resources/views/filament/fc/shipments/partials/unit-card.blade.php`, `daftar-unit.blade.php`, `app/Models/UnitInspection.php`, `app/Services/InspectionDraftAutoCreate.php`, `app/Filament/FC/Pages/InspectUnitPage.php`, `app/Filament/FC/Pages/OperationalShipmentPage.php`.

---

## Ringkasan Jawaban

| # | Pertanyaan | Jawaban singkat |
|---|---|---|
| 1 | Sengaja atau konsekuensi engine? | **Bukan keduanya secara murni** — engine datanya sudah progresif/lazy; yang menampilkan 6 slot penuh adalah keputusan **layout Blade**, bukan aturan bisnis maupun keterbatasan engine. |
| 2 | Tampilkan hanya sampai tahap aktif? | **Ya — dan ini bukan sekadar lebih rapi, ini adalah refleksi yang BENAR dari data yang sudah ada.** Draft inspeksi memang hanya tercipta sampai tahap aktif; UI saat ini justru "melawan" bentuk data-nya sendiri. |
| 3 | Ada business rule yang butuh lihat semua Pending? | **Tidak ditemukan.** Bahkan widget ringkasan di halaman yang sama (`$stageSummary`) sudah membatasi diri ke tahap aktif saja — kartu unit tidak konsisten dengan pola yang sudah dipakai di halaman yang sama. |
| 4 | Info yang duplikat/kelewat detail? | **Gate terakhir** (ambigu, lintas-tahap tanpa label), **Progress "X/6"** (menghitung tahap yang belum tersentuh sebagai "Pending"), **signature/timestamp lengkap/PDF** (detail audit, bukan keputusan kerja). |
| 5 | Selalu terlihat vs di balik "Detail"? | Lihat §5 — prinsipnya: **card menjawab "apa yang harus saya kerjakan sekarang", Detail menjawab "apa yang sudah terjadi".** |

---

## 1. Apakah tampilan seluruh inspection lifecycle memang disengaja, atau konsekuensi engine?

**Temuan kunci — engine-nya sebenarnya SUDAH progresif, bukan "menampilkan semua karena semua ada":**

`InspectionDraftAutoCreate::ensureForTrack()` (dipanggil dari `ShipmentTrackObserver::creating()`) membuat baris draft `UnitInspection` **hanya untuk SATU stage yang cocok dengan status Track saat itu** — `resolveStage(TrackStatus): ?string` memetakan `TrackStatus::Pickup → 'pickup'`, `Handover → 'handover_depot'`, dst. Baris untuk stage lain **belum tercipta sama sekali di database** sampai Track benar-benar mencapai status itu.

Artinya: pada shipment yang baru sampai tahap Stuffing, secara data **hanya ada 3 baris `UnitInspection`** (pickup, handover_depot, loading) — `unloading`, `selfdrive`, `dooring` **tidak eksis di DB sama sekali**, bukan "ada tapi berstatus pending".

Tapi `unit-card.blade.php` (baris 132) melakukan:
```php
@foreach (UnitInspection::STAGE_LABELS as $stageKey => $stageName)   // selalu 6 kali, apa pun kondisinya
    ...
    if (! $stageInsp) { $chipStatus = '—'; }               // stage belum tercipta
    elseif ($stageInsp->submitted_at === null) { $chipStatus = 'Pending'; }  // stage tercipta tapi belum submit
```

Jadi **secara visual** bukan berarti "Pending" muncul di 4 chip sekaligus — untuk stage yang datanya belum ada, chip menampilkan "—" (abu-abu redup), bukan "Pending" (amber). **Tapi baris 6-chip itu sendiri SELALU dirender penuh**, terlepas relevan atau tidak. Inilah sumber "ramai" yang Anda rasakan: bukan soal warna Pending vs dash, tapi soal **selalu ada 6 slot tampil di setiap kartu**, padahal untuk unit yang baru di tahap Pickup, 5 dari 6 slot itu murni tidak informatif (belum ada apa pun untuk dilaporkan).

**Kesimpulan Q1:** Ini adalah **keputusan rendering** (iterasi tetap atas `STAGE_LABELS` penuh) yang **tidak selaras** dengan model data yang sebenarnya progresif. Bukan business rule, bukan keterbatasan engine — murni pilihan tampilan yang belum disesuaikan dengan bentuk data aslinya.

---

## 2. Apakah lebih tepat menampilkan hanya sampai tahap aktif?

**Ya, dan ini didukung bukti struktural, bukan hanya preferensi estetika:**

`UnitInspection::STAGES` (array terurut: `pickup, handover_depot, loading, unloading, selfdrive, dooring`) sudah **persis** urutan operasional yang sama dipakai `InspectionDraftAutoCreate::resolveStage()` untuk memetakan `TrackStatus` saat ini ke satu stage aktif. Karena baris draft **hanya tercipta sampai stage aktif** (temuan §1), maka:

> **"Stage yang punya data" dan "stage yang relevan sampai titik kerja sekarang" adalah HIMPUNAN YANG SAMA PERSIS di level data.**

Pola yang Anda contohkan (Pickup → [Pickup]; Handover → [Pickup, Handover]; Stuffing → [Pickup, Handover, Loading]) **bukan usulan desain baru** — itu adalah bentuk asli data yang sudah ada di database saat ini. Mengubah tampilan menjadi cumulative-sampai-aktif berarti UI berhenti melawan bentuk data-nya sendiri, bukan menambah logic baru.

Secara implementasi (untuk referensi Anda saja, **bukan usulan kode sekarang**): batas irisan tinggal `array_slice(UnitInspection::STAGES, 0, array_search($activeStage, UnitInspection::STAGES) + 1)` — tidak perlu query tambahan, tidak perlu aturan bisnis baru.

---

## 3. Apakah ada business rule yang membutuhkan operator melihat seluruh Pending?

**Tidak ditemukan bukti aturan bisnis semacam itu di codebase.** Sebaliknya, ditemukan bukti bahwa **halaman ini sendiri sudah menerapkan prinsip "hanya tahap aktif yang relevan"** di tempat lain:

- Widget **Ringkasan Inspeksi** (`$stageSummary` di `daftar-unit.blade.php`, baris 26-39) — dihitung **HANYA dari `$activeStage`**: `$activeInspections = $units->flatMap(fn($u) => $u->inspections->filter(fn($i) => $i->stage === $activeStage))`. Total/Selesai/Signed/Pending semua dihitung untuk SATU tahap aktif saja, bukan gabungan 6 tahap.
- `OperationalShipmentPage::getHandoverWaitingCount()` juga hanya menghitung untuk kondisi tahap Handover, bukan seluruh lifecycle.

**Artinya kartu unit (6-chip penuh) justru TIDAK KONSISTEN dengan widget ringkasan yang tampil tepat di atasnya di halaman yang sama** — satu bagian halaman sudah "tahap-aktif-saja", bagian lain (kartu) masih "seluruh lifecycle". Ini bukan cuma soal selera UI, ini adalah **inkonsistensi arsitektural internal** yang sudah bisa dibuktikan dari kode yang ada.

**Rekomendasi:** informasi lengkap seluruh riwayat 6 tahap (termasuk yang belum terjadi) memang **sebaiknya tidak perlu ditampilkan** di Workspace sama sekali (bukan "dipindah ke Detail" — karena stage yang belum terjadi memang belum ada apa pun untuk ditampilkan di Detail juga). Yang **sudah terjadi** (tahap-tahap sebelumnya yang selesai) cukup direpresentasikan sebagai indikator ringkas (mis. centang hijau kecil berurutan), bukan chip penuh berlabel.

---

## 4. Audit Kartu Unit — info duplikat / kelewat detail / sebaiknya di Detail

Ditelusuri baris per baris `unit-card.blade.php`:

| Elemen | Baris | Temuan |
|---|---|---|
| SJKB, Model, Warna, Chassis, Mesin, No. Pol | 36-70 | **Tepat di Workspace** — identitas unit, dibutuhkan untuk tahu "unit yang mana ini". |
| Badge Selesai/Menunggu + tombol Inspeksi/Lihat | 74-105 | **Tepat di Workspace** — langsung actionable, jawaban atas "apa yang harus saya lakukan". |
| Progress summary "Selesai: X/6, Passed, Failed, Pending" | 108-128 | **Bermasalah.** `$pendingCount = $totalStages - $submittedCount` (baris 19) adalah **pengurangan dari angka tetap 6**, bukan hitungan baris Pending yang benar-benar ada. Akibatnya "Pending: 5" bisa muncul padahal hanya 1 tahap yang benar-benar sedang menunggu aksi (4 sisanya bahkan belum punya baris di DB). Ini **informasi yang menyesatkan**, bukan cuma "terlalu detail" — sebaiknya disederhanakan jadi progres bertahap ("Tahap 3 dari 6") atau dihapus karena sudah terwakili oleh chip. |
| 6-chip status per stage | 130-158 | **Subjek utama §1/§2** — sebaiknya dipersempit ke cumulative-sampai-aktif. |
| **"Gate terakhir"** | 161-181 | **Paling berpotensi membingungkan.** `$latestSubmitted` diambil dari **`$unit->inspections` TANPA filter stage** — jadi bisa saja yang tampil adalah gate decision dari tahap Handover (`Accept`) padahal operator sedang melihat kartu ini di tahap Loading. Tidak ada label "gate untuk tahap apa" di sebelah badge ini — berisiko disalahartikan sebagai gate tahap SAAT INI. Duplikat sebagian dengan status di chip stage yang bersangkutan. **Sebaiknya: scope ke gate decision tahap aktif saja (jika ada), atau pindah ke Detail dengan label tahap eksplisit.** |
| Signature, nama penandatangan, timestamp lengkap, link PDF | 184-221 | **Kelewat detail untuk Workspace.** Ini murni metadata audit (siapa, kapan persis, dokumen apa) — tidak membantu operator memutuskan langkah berikutnya, hanya relevan saat SEDANG memverifikasi/audit satu inspeksi tertentu. `InspectUnitPage` (Detail) **sudah menampilkan data yang sama secara penuh** (signature, item checklist, dst.) — jadi ini murni duplikasi, bukan informasi baru. |
| Checklist per kategori item inspeksi | Tidak ada di kartu (sudah hanya di `InspectUnitPage`) | **Sudah benar** — tidak ditemukan duplikasi checklist item di kartu. Poin ini sudah sesuai prinsip yang Anda maksud. |

---

## 5. Rekomendasi UX (prinsip, bukan implementasi)

**Prinsip pemisah:** Workspace (kartu unit) menjawab **"apa yang harus saya kerjakan SEKARANG"**. Detail (`InspectUnitPage`) menjawab **"apa yang sudah terjadi, secara lengkap"**. Setiap elemen di kartu yang tidak membantu keputusan langkah berikutnya adalah kandidat pindah ke Detail.

### Selalu terlihat di Workspace
- Identitas unit (SJKB/model/chassis/plat) — untuk mengenali unit.
- Tahap aktif saat ini + status tahap itu (Selesai/Menunggu) — tunggal, bukan gabungan 6.
- Tombol aksi langsung (Inspeksi/Lihat) — jalan pintas mengerjakan.
- Progres ringkas **sampai tahap aktif saja** (mis. deretan centang hijau kecil: Pickup✓ Handover✓ Loading→) — menjawab "sejauh mana unit ini sudah maju", bukan "apa saja yang MASIH akan terjadi".
- **Indikator Failed/Return-to-PDC dari tahap MANA PUN** (bukan hanya tahap aktif) — ini satu-satunya kasus di mana informasi "lintas tahap" tetap operasional penting: unit yang pernah ditolak di tahap sebelumnya butuh perhatian terlepas di tahap mana operator sedang bekerja. Ini beda dari "Gate terakhir" yang sekarang (yang menampilkan gate APA PUN, bukan khusus yang bermasalah) — sebaiknya disempitkan jadi "tampilkan HANYA jika ada gate bermasalah", bukan selalu menampilkan gate terakhir apa pun hasilnya.

### Cukup muncul di balik "Detail"
- Checklist item inspeksi per kategori (sudah benar, sudah di sana).
- Signature, nama penandatangan, timestamp presisi, link PDF.
- Riwayat gate decision seluruh tahap (dengan label tahap eksplisit, sesuatu yang saat ini TIDAK ada bahkan di kartu).
- Catatan/notes inspeksi.
- Tahap-tahap yang belum tercapai (tidak perlu placeholder apa pun — baru muncul di kartu ketika benar-benar jadi tahap aktif berikutnya).

---

## Catatan Penutup

Review ini murni berbasis kode yang sudah ada — tidak ada asumsi. Temuan paling penting: **engine inspeksi (`InspectionDraftAutoCreate`) dan widget ringkasan tahap (`$stageSummary`) di halaman yang sama SUDAH menerapkan prinsip "tahap aktif saja"** — kartu unit adalah satu-satunya bagian yang belum mengikuti pola itu. Ini memperkuat bahwa penyederhanaan yang Anda usulkan bukan perubahan arah baru, melainkan **menyelesaikan konsistensi yang sudah setengah jalan diterapkan di tempat lain pada halaman yang sama**.

Menunggu arahan Anda sebelum masuk ke implementasi.
