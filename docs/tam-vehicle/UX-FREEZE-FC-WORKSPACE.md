# UX Freeze Validation — FC Coordinator Workspace (TAM Vehicle)

**Status:** VALIDASI UX → kandidat UX FREEZE
**Tanggal:** 20 Juli 2026
**Pendamping:** [`DOMAIN-VALIDATION-REQUIREMENT-VS-ALLOCATION.md`](DOMAIN-VALIDATION-REQUIREMENT-VS-ALLOCATION.md), [`DOMAIN-FREEZE-BP-FC-CONTAINER-PLANNING.md`](DOMAIN-FREEZE-BP-FC-CONTAINER-PLANNING.md)
**Sifat:** UX operasional. Bukan software.

---

## Verdict Ringkas

Hipotesis **satu workspace** = **benar**. Tetapi susunannya perlu dikoreksi: proposal Anda menyusun workspace sebagai **pipeline linear 8-langkah** yang meniru business process. Padahal FC tidak bekerja linear — FC mengerjakan **banyak shipment pada tahap berbeda secara bersamaan**, sepanjang hari.

**Wawasan kunci: FC punya DUA ritme kerja yang berbeda, bukan satu urutan.**

| Ritme | Sifat | Kapan | Aktivitas |
|---|---|---|---|
| **Perencanaan Harian** | Agregat, sekali/hari | Pagi | Requirement → Briefing → Readiness |
| **Eksekusi** | Per-shipment/unit, terus-menerus | Sepanjang hari | Kedatangan → Inspeksi → Allocation → lepas ke Stuffing |

Workspace harus mencerminkan **dua ritme** ini, bukan tangga 8-anak. Business process itu *urutan*; workspace itu *papan kerja* (task board) yang bisa dinavigasi bebas.

---

## Struktur Workspace yang Direkomendasikan (untuk freeze)

Dari 8 section → **3 zona koheren + status** (bukan 8 tujuan terpisah):

```
┌──────────────────────────────────────────────────────────────┐
│  ZONA 0 — TODAY'S OPERATION  (beranda / "apa kerja saya hari  │
│  ini?")  ringkasan: shipment, unit, requirement, container    │
│  ready/gap, menunggu kedatangan, siap stuffing, EKSEPSI       │
└──────────────────────────────────────────────────────────────┘
        │ pagi (sekali)                    │ sepanjang hari
        ▼                                  ▼
┌───────────────────────────┐   ┌──────────────────────────────┐
│  ZONA 1 — DAILY PLANNING   │   │  ZONA 2 — EKSEKUSI           │
│  (agregat, ritual pagi)    │   │  (per-shipment, papan kerja) │
│  · Requirement Planning    │   │  Shipment Queue (tulang      │
│    (input, editable)       │   │  punggung) — tiap shipment    │
│  · Morning Briefing        │   │  punya STATUS:               │
│    (ringkasan event)       │   │   Planning Pending →         │
│  · Container Readiness      │   │   Planning Complete →        │
│    (Need/Available/Gap,     │   │   Menunggu Kedatangan (x/y) →│
│     readonly)              │   │   Siap Allocation →          │
│  → satu halaman, 3 zona    │   │   Allocating →               │
│                            │   │   Siap Stuffing              │
│  Decision: Gap → adakan /  │   │  Drill shipment → Allocation │
│  geser voyage              │   │  canvas (pool-aware)         │
└───────────────────────────┘   └──────────────┬───────────────┘
                                                │ (semua unit datang
                                                │  + teralokasi)
                                                ▼
                                 ┌──────────────────────────────┐
                                 │  WORKSPACE STUFFING (TERPISAH,│
                                 │  tim/aktivitas berbeda)       │
                                 └──────────────────────────────┘
```

---

## Jawaban atas 8 Pertanyaan

### 1. Apakah urutan sudah mengikuti cara FC bekerja?
**Sebagian.** *Urutan business process* Anda benar. Tetapi sebagai *workspace*, jangan jadikan pipeline linear (wizard 8-langkah). FC tidak melewati 8 layar sekali jalan; FC berpindah bebas antar shipment di tahap berbeda. **Koreksi: workspace = papan kerja berbasis status, bukan tangga.** Pisahkan berdasarkan **ritme** (planning pagi vs eksekusi harian), bukan berdasarkan langkah proses.

### 2. Adakah langkah operasional FC yang belum terakomodasi?
**Ada tiga:**
- **Inspeksi Handover / Gate** — saat unit tiba, FC/inspektur mengecek kondisi & memutuskan *accept / allow_with_remark / return_to_pdc*. Ini **aktivitas nyata** yang mengubah rencana (unit *return_to_pdc* keluar dari allocation). Proposal hanya menampilkan "Arrival 5/8" (angka) tanpa aktivitas inspeksi. **Wajib ditambahkan.**
- **Eksepsi** — unit *return_to_pdc*, unit tidak datang / terlambat, Gap container. Flow linear tidak punya tempat untuk "masalah", padahal FC menghabiskan waktu nyata di sini. **Butuh lajur eksepsi** (di Today's Operation + penanda di Shipment Queue).
- **Tindak lanjut Gap** — saat Readiness menunjukkan kekurangan, siapa & bagaimana menindak (pengadaan / geser voyage). Readiness readonly, tapi **aksi atas Gap** perlu titik masuk.

### 3. Workspace yang sebaiknya TIDAK dipisah (digabung)?
- **Requirement + Briefing + Readiness → satu "Daily Planning"** (lihat #5).
- **Waiting Unit Arrival → jadi status**, bukan workspace (lihat #6).
- **Ready for Stuffing → jadi status/gerbang**, bukan workspace penuh (lihat #8).

### 4. Workspace yang sebaiknya DIPISAH?
Masalah proposal justru **terlalu banyak pemisahan**, bukan kurang. Yang benar-benar harus **terpisah**:
- **Stuffing** dari FC allocation — tim & aktivitas berbeda (Anda sudah benar: pindah ke Workspace Stuffing).
- **Inspeksi Handover** sebagai aktivitas fokus tersendiri (per-unit) di dalam zona Eksekusi — beda dari allocation.
Selebihnya: **gabungkan**, jangan pisah.

### 5. Requirement + Briefing + Readiness → dipisah (A) atau satu "Daily Planning" (B)?
**B — satu "Daily Planning".** Alasan operasional: ketiganya terjadi **bersama, sekali, di pagi hari, sebagai satu ritual**. Requirement (hitung) → memberi makan Briefing (event) → menghasilkan Readiness (status Need/Available/Gap). Ini bukan tiga tujuan yang FC kunjungi terpisah; ini **satu aktivitas pagi**. Memisah = memaksa FC berpindah 3 tempat untuk satu pekerjaan. Dalam "Daily Planning": Requirement (editable) → Briefing (ringkasan) → Readiness (readonly) sebagai **tiga zona di satu halaman**, mengikuti alur alami.

### 6. Waiting Unit Arrival → workspace sendiri atau status di Shipment Queue?
**Status di Shipment Queue.** Alasan: "menunggu kedatangan" adalah **keadaan** shipment, bukan aktivitas yang FC kerjakan. Terlebih pickup dilakukan **CC External** (car carrier), jadi FC hanya **memantau**. Progress kedatangan (5/8) = indikator pada kartu shipment. **Namun** peristiwa kedatangan **memicu aktivitas** = Inspeksi Handover (#2). Jadi: *kedatangan = status; inspeksi saat tiba = aktivitas.*

### 7. Container Allocation → berbasis Shipment atau pool seluruh unit hari itu?
**Shipment-anchored, pool-aware.** Alasan:
- FC **berpikir & bertanggung jawab per-shipment** (tiap shipment = satu SPPB/customer). Titik masuk allocation = dari kartu shipment.
- Tetapi **realita fisik = pool** (container adalah pasokan bersama hari itu; bila konsolidasi diizinkan, satu container bisa memuat unit dari beberapa shipment tujuan/voyage sama).
- **Rekomendasi:** FC masuk **per-shipment**, tapi kanvas allocation **menampilkan pool container bersama** (container yang sudah terisi sebagian oleh shipment lain terlihat), dan **mengizinkan berbagi container lintas-shipment BILA kebijakan konsolidasi mengizinkan**.
- → Bergantung pada **konfirmasi konsolidasi multi-shipment** (item terbuka dari domain validation). Jika konsolidasi **tidak** diizinkan → murni per-shipment.

### 8. Ready for Stuffing → status atau workspace transisi?
**Status + gerbang serah-terima ringan, bukan workspace penuh.** Alasan: "Ready for Stuffing" = (semua unit datang + semua teralokasi) → shipment kini menjadi pekerjaan **tim Stuffing**. Ini **titik handoff**, bukan tempat FC bekerja. Cukup: status "Siap Stuffing" + **aksi konfirmasi "lepas ke Stuffing"** yang memindahkan shipment keluar dari antrean FC ke Workspace Stuffing. Workspace transisi berat = tidak perlu.

---

## Ritme Harian FC (justifikasi struktur)

```
PAGI  →  Daily Planning (sekali, agregat)
         Requirement → Briefing → Readiness → [Gap? adakan/geser]

SIANG →  Eksekusi (reaktif, sepanjang hari, per shipment)
SORE     unit tiba (CC External) → Inspeksi Handover/Gate
          → Allocation (pool-aware) → Siap Stuffing → lepas ke Stuffing
         (selingan: tangani eksepsi — return_to_pdc, no-show, gap)
```

Struktur workspace **mengikuti ritme ini**: Zona 1 (pagi/agregat) terpisah dari Zona 2 (harian/per-shipment), disatukan oleh Zona 0 (Today's Operation) yang menjawab *"apa kerja saya sekarang?"*.

---

## Ringkasan Rekomendasi Gabung/Pisah

| Section proposal | Rekomendasi |
|---|---|
| Today's Operation | **Pertahankan** (Zona 0, beranda) — tambah lajur **Eksepsi** |
| Shipment Queue | **Pertahankan** sebagai tulang punggung Zona 2 (papan kerja berstatus) |
| Requirement Planning | **Gabung** → Daily Planning |
| Morning Briefing | **Gabung** → Daily Planning |
| Container Readiness | **Gabung** → Daily Planning (readonly di dalamnya) |
| Waiting Unit Arrival | **Turunkan jadi status** di Shipment Queue (+ picu Inspeksi Handover) |
| Container Allocation | **Pertahankan** sebagai drill-down per-shipment, **pool-aware** |
| Ready for Stuffing | **Turunkan jadi status + aksi handoff** ke Workspace Stuffing (terpisah) |
| *(baru)* Inspeksi Handover/Gate | **Tambahkan** — aktivitas saat kedatangan |
| *(baru)* Lajur Eksepsi | **Tambahkan** — return_to_pdc, no-show, gap |

---

## Yang Masih Perlu Dikonfirmasi ke Operasional (sebelum UX freeze)

1. **Konsolidasi multi-shipment** — menentukan apakah kanvas Allocation murni per-shipment atau pool lintas-shipment (mempengaruhi #7 secara langsung).
2. **Pemilik aksi Gap** — apakah FC yang menindak kekurangan container, atau eskalasi ke Office/pengadaan (menentukan apakah "aksi Gap" ada di workspace FC).
3. **Inspeksi Handover: siapa** — FC sendiri atau inspektur terpisah (PDI Inspector)? Menentukan apakah inspeksi = aktivitas FC di Zona 2 atau handoff ke actor lain.
4. **Batas "Siap Stuffing"** — apakah butuh konfirmasi eksplisit FC (lepas ke Stuffing) atau otomatis saat syarat terpenuhi.
5. **Titik final voyage** — apakah FC melihat/menyentuh penugasan voyage, atau itu murni ranah Office (mempengaruhi apakah voyage muncul di workspace FC).

---

## Kesimpulan untuk UX Freeze

**Satu workspace, tiga zona (bukan pipeline 8-langkah):**

> **Today's Operation** (beranda + eksepsi) · **Daily Planning** (Requirement+Briefing+Readiness, ritual pagi) · **Eksekusi** (Shipment Queue berstatus → Inspeksi Handover → Allocation pool-aware → lepas ke Stuffing).

Ini mengikuti **ritme nyata FC** (planning pagi agregat vs eksekusi harian per-shipment), mengakomodasi **inspeksi & eksepsi** yang sebelumnya hilang, dan menghindari memaksa FC menaiki tangga linear. Setelah lima konfirmasi di atas ditutup, struktur ini siap **dibekukan** sebagai fondasi implementasi.
