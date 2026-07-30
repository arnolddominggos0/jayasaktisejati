# Audit MP-08 — Peran Briefing dalam Workflow Operasional

**Status:** AUDIT ONLY — tidak ada kode/UI yang diubah.
**Tanggal:** 23 Juli 2026
**Metode:** Seluruh temuan ditelusuri langsung dari model, migration, dan service yang berjalan — bukan asumsi dari label/layout.

---

## Ringkasan Eksekutif

**Temuan paling penting:** diagram alur `Briefing → Container Readiness → Stuffing → Monitoring → Delivery` **menyiratkan rantai pewarisan** ("kumpulan SPPB yang dipilih di Briefing mengalir ke tahap berikutnya"). **Ini tidak terjadi di kode yang berjalan.** Setiap tahap sebenarnya menurunkan objek acuannya **sendiri-sendiri** — sebagian besar dari **Shipment/Unit**, bukan dari "apa yang dipilih di Briefing". Briefing dan Shipment yang di-attach padanya adalah **cabang paralel**, bukan sumber tunggal yang diwariskan ke bawah.

Temuan kedua: mekanisme **untuk memilih SPPB ke dalam Briefing sudah tidak aktif di UI** (kode form-nya di-comment total), sementara model data untuk itu (pivot) masih ada. Ini membuat beberapa angka di "Ringkasan Beban Kerja" berisiko diam-diam tidak akurat.

---

## 1. Apa Objek Kerja Briefing Sebenarnya?

**Jawaban: Briefing berdasarkan jadwal kerja harian (per depot, per hari) — bukan SPPB, bukan Shipment, bukan Unit Handover.**

**Bukti langsung dari kode** (bukan interpretasi):

- `BriefingSession` disimpan dengan anchor `date` + `depot_id`. Kolom `shipment_id` (1:1 lama) secara eksplisit ditandai:
  ```php
  // app/Models/BriefingSession.php:24
  'shipment_id', // @deprecated legacy 1:1 key, superseded by briefing_session_shipments pivot
  ```
- Komentar pada method **deprecated** `Shipment::ensureBriefingSession()` menyatakan arsitektur aktif secara eksplisit, dari penulis kode itu sendiri:
  > *"The active path is **sendToFc()**, which uses **firstOrCreate(['depot_id','date'])** and attaches shipments via the briefing_session_shipments pivot (BelongsToMany). **One session per depot per day, many shipments per session.**"* (`app/Models/Shipment.php:362-368`)
- Constraint unik `(date, depot_id)` pernah ditegakkan di DB (`2025_05_25_171508_add_unique_date_depot_to_briefing_sessions_table`), lalu **dihapus** (`2026_06_17_120001_drop_date_depot_unique_from_briefing_sessions`) — anchor konseptualnya tetap depot+hari, hanya keunikannya yang tidak lagi dipaksakan DB.

**Shipment bukan objek pemicu — ia dilampirkan (attached) ke sesi yang sudah ada**, via `briefing_session_shipments` (many-to-many). "Unit Handover" **bukan objek Briefing** — ia hanya sumber data untuk SATU metrik turunan (`actual_unit_masuk_yard`, lihat §3), dihitung independen dari sesi/shipment mana pun yang di-attach.

**Catatan penting:** mekanisme attach shipment (pivot + `scopeReadyForBriefing()`) memang ada di model, tetapi **UI untuk melakukannya secara manual sudah di-nonaktifkan total** — seluruh blok "Shipment Kandidat" di form `BriefingSessionResource` di-comment (`app/Filament/FC/Resources/BriefingSessionResource.php`, baris ~202-236, `// Section::make('Shipment Kandidat') ... //`). Tidak ditelusuri lebih jauh di audit ini dari mana pivot terisi hari ini bila bukan dari form ini (kemungkinan AppSheet import, mengingat kolom `appsheet_id` ada di banyak tabel domain ini) — **ini pertanyaan terbuka yang perlu dikonfirmasi ke tim, bukan diasumsikan.**

---

## 2. "Source of Work" Setelah Briefing — Ditelusuri per Tahap

**Jawaban: TIDAK, workflow TIDAK konsisten mengacu pada kumpulan SPPB yang dipilih di Briefing.** Berikut objek acuan nyata per tahap, ditelusuri dari kode:

| Tahap | Objek acuan nyata | Bukti |
|---|---|---|
| **Briefing** | Depot + Tanggal (sesi), Shipment di-attach (many) | §1 |
| **Container Readiness** | **Tanggal SAJA — tidak ada `depot_id`, tidak ada relasi apa pun ke BriefingSession** | Migration `container_readiness_sessions`: kolom hanya `session_date` (unique), `unit_count`, `container_need`, `container_available`, `gap`, `notes` — **tidak ada FK ke `briefing_sessions`**. Digrep di seluruh `app/Models/BriefingSession.php` & `ContainerReadinessSession.php`: **nol referensi silang**. |
| **Container Allocation** | **Unit** (entity utama), terhubung ke `ContainerReadinessSession` (bukan ke Briefing) | Sesuai Domain Freeze Container Allocation yang sudah dibekukan — `containers` (anotasi alokasi) ber-FK ke `container_readiness_session_id`, sama sekali tidak menyentuh `briefing_sessions`. |
| **Stuffing (LoadingSession)** | **Shipment** — dipicu otomatis dari `ShipmentTrack`, bukan dari Briefing | `LoadingSessionAutoCreate::forShipment()` — satu-satunya jalur pembuatan otomatis — SELALU mengisi `shipment_id`/`depot_id`/`branch_id`, **TIDAK PERNAH mengisi `briefing_session_id`** (dikonfirmasi baca penuh method-nya). `briefing_session_id` ADA di kolom & fillable, tapi **hanya bisa terisi manual** lewat dropdown terpisah di form `LoadingSessionResource` (`app/Filament/FC/Resources/LoadingSessionResource.php:116`) — bukan bagian dari alur otomatis. |
| **Monitoring** | **Unit** (dibekukan di prinsip Status-Driven Workspace — entitas eksekusi), Shipment sebagai bingkai | Konsisten dengan dokumen frozen sebelumnya di initiative ini. |
| **Delivery** | **Shipment** (via `ShipmentTrack`/`TrackStatus`) | Mesin tracking yang sudah berjalan sejak awal proyek, independen dari Briefing. |

**Kesimpulan:** hanya **Briefing↔Shipment** yang benar-benar terhubung via FK/pivot. **Container Readiness terputus total dari Briefing** (bahkan tidak per-depot). **Stuffing terhubung ke Shipment, bukan ke Briefing**, dan link opsionalnya ke Briefing tidak reliable (tidak pernah diisi jalur otomatis). Diagram di brief sprint ini **menggambarkan alur yang diinginkan, bukan alur yang benar-benar terimplementasi.**

---

## 3. Audit Ringkasan Beban Kerja (5 Item)

Ditelusuri dari accessor `BriefingSession` (`app/Models/BriefingSession.php:201-227`):

| Item | Berasal dari data apa | Dipakai untuk keputusan apa | Benar-benar dibutuhkan FC? |
|---|---|---|---|
| **Shipment Terpilih** | `COUNT` shipment via pivot `briefing_session_shipments` | Tidak dipakai keputusan apa pun di halaman ini (murni angka informatif) | **Diragukan.** UI untuk mengisinya nonaktif (§1) — FC tidak punya cara mempengaruhi angka ini dari mana pun yang jelas. |
| **Expected Unit** | `SUM(units_count)` dari shipment yang sama (pivot) | Baseline pembanding untuk Gap | **Berisiko tidak akurat** — bergantung pivot yang sama dengan di atas. Jika pivot tidak lagi terisi rutin sejak UI dinonaktifkan, angka ini bisa diam-diam salah/basi tanpa ada indikasi ke user. |
| **Actual Unit Handover** | **BERBEDA SUMBER TOTAL**: `COUNT` unit dengan `ShipmentTrack.status='handover'` pada tanggal tsb, di-scope ke **`assigned_depot_id`** (bukan ke shipment yang di-pivot!) | Angka real-time kedatangan unit ke yard | **Ya, ini yang paling andal** — tidak bergantung pivot yang mati, langsung dari mesin tracking yang aktif. |
| **Gap Unit** | `Expected − Actual` (floor 0) | Menentukan badge Status (Siap/Belum) | **Berpotensi tidak bermakna** — lihat Inkonsistensi §Inti di bawah. Membandingkan dua angka dari **populasi yang berbeda**. |
| **Status** (`workload_status`) | Turunan langsung dari Gap | Badge "Siap Operasional" / "Belum Semua Unit Masuk" | Mewarisi ketidakandalan Gap. |

---

## 4. Klasifikasi Informasi — Decision / Operational Detail / Audit

Dinilai terhadap struktur halaman **saat ini** (pasca-MP-07):

| Section | Posisi saat ini | Kategori yang tepat | Verdict |
|---|---|---|---|
| Status Operasional (badge atas) | #1 | **A. Decision** | ✅ Tepat |
| Informasi Briefing (tanggal/depot/PIC/catatan) | #2 | **B. Operational Detail** | ⚠️ Saat ini di posisi atas seolah decision info, padahal ini metadata orientasi — bukan salah, tapi bukan "5-10 detik pertama" juga |
| Ringkasan Beban Kerja (5 item §3) | #3 | Campuran — hanya *Actual Unit Handover* layak **A**; sisanya **B** (bahkan sebagian berisiko) | ⚠️ **Salah tempat.** Diposisikan sebagai info keputusan utama, padahal 4 dari 5 datanya tidak reliabel/tidak dipakai keputusan (§3) |
| Status MP Check (badge AppSheet mentah) | #4 | **B. Operational Detail** | ⚠️ Ini detail PROSES (kenapa belum ready), bukan keputusan itu sendiri — sebaiknya di bawah decision info, bukan sejajar dengannya |
| Ringkasan Manpower (Need/Hadir/Siap Kerja/Tidak Fit) | #5 | **A. Decision** | ✅ Tepat (hasil MP-07) |
| Ringkasan Kesehatan (badge all-clear / 3 angka) | #6 | **A. Decision** | ✅ Tepat (hasil MP-07) |
| Ringkasan APD (ringkas per jenis) | #7 | **A. Decision** | ✅ Tepat (hasil MP-07) |
| Bukti Briefing (foto) | #8 | Kategori tersendiri — **Evidence**, bukan murni A/B/C | Netral — dipertahankan sesuai instruksi eksplisit sprint sebelumnya |
| Shipment Terpilih (daftar SPPB detail) | #9 | **C. Audit Information** | ⚠️ **Salah tempat** — ini daftar rinci per-SPPB (kode, status, customer, unit), persis kategori yang sudah dipindahkan keluar untuk Riwayat Kesehatan di MP-07, tapi belum diterapkan konsisten di sini |

---

## 5. Audit Bahasa Domain

| Istilah saat ini | Sesuai bahasa operasional JSS? | Usulan |
|---|---|---|
| **Shipment Terpilih** | ❌ Menyiratkan ada tindakan "memilih" yang aktif — padahal mekanisme pemilihannya sudah tidak berfungsi di UI (§1). Istilah ini berpotensi menyesatkan, bukan sekadar soal gaya bahasa. | Perlu klarifikasi operasional dulu (dari mana pivot ini sebenarnya terisi hari ini) sebelum menamai ulang — jangan menamai proses yang belum dipahami sumbernya. |
| **Ringkasan Beban Kerja** | ✅ Istilah wajar, sudah familiar gaya operasional lapangan (mirip "beban shift"). Masalahnya di data-nya (§3), bukan labelnya. | Tidak perlu diganti — perbaiki data, bukan nama. |
| **Expected Unit** | ❌ Istilah Inggris, tidak konsisten dengan bahasa operasional Indonesia yang sudah dibakukan di Monitoring (UX Polish v1.5 — semua label operasional sudah di-Indonesia-kan: "Terlambat", "Perlu Tindak Lanjut", dst.) | **"Unit Direncanakan"** atau **"Target Unit"** |
| **Gap Unit** | ❌ Sama — istilah Inggris, tidak konsisten dengan preseden Monitoring | **"Selisih Unit"** atau **"Kekurangan Unit"** |

**Catatan konsistensi:** perbaikan bahasa di Monitoring (UX Polish v1.5) sudah menetapkan preseden bahwa seluruh istilah operasional FC harus bahasa Indonesia natural. Halaman Detail Briefing belum mengikuti preseden ini secara penuh.

---

## 6. Audit Hubungan dengan Stuffing

**Jawaban: Stuffing seharusnya — dan SECARA DE FACTO SUDAH, dalam kode yang benar-benar berjalan — menerima Shipment sebagai input utama. Bukan Briefing, bukan SPPB (sebagai dokumen terpisah), bukan Container Readiness.**

**Bukti:**
- `LoadingSessionAutoCreate::forShipment(Shipment $shipment)` adalah **satu-satunya jalur pembuatan otomatis** `LoadingSession`, dipicu dari event `ShipmentTrack` (`ensureForTrack()`). Ia SELALU mengisi `shipment_id`, `depot_id`, `branch_id` — **tidak pernah mengisi `briefing_session_id`** (dikonfirmasi baca penuh source).
- `briefing_session_id` **ada** di kolom/fillable `LoadingSession`, tapi satu-satunya tempat ia bisa diisi adalah **dropdown manual** di form `LoadingSessionResource` — sebuah tindakan human-dependent terpisah, bukan bagian arsitektur otomatis.
- Container Readiness (§2) sudah terbukti terputus dari Briefing dan bahkan dari konsep depot — sehingga tidak layak jadi input utama Stuffing juga.

**Konsistensi dengan arsitektur yang sudah dibekukan di initiative ini:**
- Container Allocation (Domain Freeze) sudah menetapkan **Unit** sebagai entity kerja, dengan **Shipment sebagai bingkai konteks** — bukan Briefing.
- Actor Architecture FC menetapkan Unit sebagai entitas eksekusi atomik.
- Prinsip Status-Driven Workspace menegaskan: entitas pemilik status berbeda per modul, tapi Shipment/Unit konsisten jadi backbone di semua modul eksekusi (Inspection, Allocation) — Briefing/Requirement/Readiness konsisten berperan sebagai **artefak perencanaan harian**, bukan pemilik rantai eksekusi.

**Rekomendasi input Stuffing** (konsisten, bukan keputusan terisolasi): **Shipment sebagai input utama** (mewarisi pola Container Allocation persis), dengan **Unit sebagai entity yang benar-benar dikerjakan di dalamnya**, dan **Container Readiness sebagai gate/konteks** (bukan input struktural) — persis pola yang sudah terbukti bekerja di Container Allocation (Inspection sebagai gate, bukan input).

---

## Temuan

1. Briefing = sesi harian per depot (`date` + `depot_id`), bukan objek per-SPPB/Shipment/Unit. Didukung komentar arsitektur eksplisit di kode.
2. Mekanisme attach Shipment ke Briefing (pivot) ada di model, tapi **UI-nya sudah dinonaktifkan total** (di-comment) — sumber pengisian pivot saat ini tidak diketahui/belum ditelusuri.
3. Container Readiness **tidak terhubung sama sekali** ke Briefing (tidak ada FK, tidak ada `depot_id`).
4. Stuffing (LoadingSession) **de facto** sudah dianchor ke Shipment, bukan Briefing — `briefing_session_id` adalah field yang jarang/tidak pernah terisi dari jalur otomatis.
5. "Expected Unit" dan "Actual Unit Handover" berasal dari **dua populasi data yang berbeda** — satu dari pivot Briefing↔Shipment, satu dari mesin tracking depot+tanggal — sehingga "Gap" hasil pengurangannya berisiko tidak bermakna secara operasional.
6. Dua istilah ("Expected Unit", "Gap Unit") tidak konsisten dengan bahasa Indonesia operasional yang sudah dibakukan di Monitoring.

## Inkonsistensi

- **Inti:** `Gap Unit = Expected Unit − Actual Unit Handover`, padahal Expected diambil dari shipment yang di-*attach secara manual* (pivot, UI mati) sementara Actual diambil dari *seluruh* unit yang handover di depot tsb hari itu (tanpa peduli status attach). Ini bukan perbandingan apple-to-apple.
- Diagram alur workflow (Briefing→Readiness→Stuffing→Monitoring→Delivery) menyiratkan pewarisan objek kerja, padahal implementasi nyata menunjukkan setiap tahap mandiri dengan anchor sendiri (§2).
- "Shipment Terpilih" sebagai label mengasumsikan ada tindakan pemilihan aktif yang, faktanya, tidak lagi bisa dilakukan lewat form ini.
- Section "Shipment Terpilih" (daftar rinci) masih berada di Detail Briefing meski sifatnya audit/referensi — bertentangan dengan prinsip yang baru saja diterapkan pada Riwayat Kesehatan di MP-07 (dipindah keluar karena "bukan informasi pengambilan keputusan").

## Bagian yang Sudah Benar

- Briefing sebagai sesi harian per-depot adalah desain yang **masuk akal dan terdokumentasi jelas** di kode — bukan kebetulan, ada jejak keputusan arsitektur eksplisit.
- "Actual Unit Handover" adalah metrik yang **andal dan bernilai keputusan nyata** — sumbernya langsung dari mesin tracking aktif, tidak bergantung pivot yang mati.
- Status Operasional, Ringkasan Manpower, Ringkasan Kesehatan, Ringkasan APD (hasil MP-07) sudah tepat sebagai Decision Information — presisi, ringkas, sesuai kategori.
- Stuffing (LoadingSession) **secara arsitektur sudah benar** menganchor ke Shipment — ini tidak perlu "diperbaiki", hanya perlu **diakui secara eksplisit** sebagai keputusan resmi (bukan kebetulan implementasi) sebelum desain Stuffing lanjutan dibangun di atasnya.

## Bagian yang Perlu Direvisi

1. **Ringkasan Beban Kerja** — perlu audit data lanjutan (di luar cakupan dokumen ini) untuk memastikan Expected Unit benar-benar mencerminkan realita, atau direvisi agar Expected & Actual mengukur populasi yang sama.
2. **Sumber pengisian pivot Shipment↔Briefing** — perlu dikonfirmasi ke tim operasional: apakah memang sengaja tidak lagi manual (digantikan proses lain), atau ini regresi yang belum disadari.
3. **Posisi "Shipment Terpilih" (daftar rinci)** — kandidat kuat untuk dipindah keluar dari Detail Briefing, konsisten dengan prinsip yang sudah diterapkan pada Riwayat Kesehatan.
4. **Istilah "Expected Unit"/"Gap Unit"** — kandidat untuk di-Indonesia-kan, mengikuti preseden Monitoring.
5. **Relasi Container Readiness ↔ Briefing** — jika memang dimaksudkan berhubungan (sesuai diagram sprint ini), perlu keputusan arsitektur eksplisit (tambah `depot_id`? tambah FK ke Briefing?) — bukan diasumsikan sudah terhubung.

## Rekomendasi Sebelum Implementasi Ulang

1. **Konfirmasi ke operasional dulu**, bukan tebak: dari mana `briefing_session_shipments` terisi hari ini jika bukan dari form (yang sudah mati)? Ini menentukan apakah "Shipment Terpilih"/"Expected Unit" punya makna sama sekali di masa depan.
2. **Putuskan secara sadar** apakah Container Readiness memang seharusnya per-depot dan terhubung ke Briefing (sesuai semangat diagram), atau tetap global-per-hari seperti sekarang (dan diagramnya yang perlu diperbarui). Jangan biarkan diagram dan kode terus berbeda tanpa keputusan eksplisit.
3. **Tetapkan Shipment sebagai input resmi Stuffing** — bukan hanya karena "sudah begitu di kode", tapi karena ini konsisten dengan Container Allocation, Actor Architecture, dan prinsip Status-Driven Workspace yang sudah dibekukan di initiative ini. Jadikan keputusan sadar, bukan warisan kebetulan.
4. **Redesain Ringkasan Beban Kerja** hanya setelah #1 terjawab — memperbaiki tampilan sebelum sumber datanya jelas hanya memindahkan masalah, bukan menyelesaikannya (persis kekhawatiran yang mendasari sprint audit ini).
5. **Selaraskan bahasa** ("Expected Unit"→"Unit Direncanakan", "Gap Unit"→"Selisih Unit") sebagai perubahan terpisah, ringan, setelah data-nya dipastikan benar — jangan gabungkan perbaikan bahasa dengan perbaikan data dalam satu langkah agar mudah divalidasi terpisah.
