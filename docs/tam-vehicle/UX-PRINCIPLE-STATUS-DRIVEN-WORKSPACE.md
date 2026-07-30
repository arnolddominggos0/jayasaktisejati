# UX Principle — Status-Driven Workspace (Master Domain Freeze)

**Status:** VALIDASI PRINSIP → kandidat **UX DOMAIN FREEZE** (menguasai seluruh workspace TAM berikutnya)
**Tanggal:** 20 Juli 2026
**Pendamping:** [`UX-FREEZE-FC-WORKSPACE.md`](UX-FREEZE-FC-WORKSPACE.md), [`DOMAIN-VALIDATION-REQUIREMENT-VS-ALLOCATION.md`](DOMAIN-VALIDATION-REQUIREMENT-VS-ALLOCATION.md)
**Sifat:** Filosofi UX operasional. Bukan software.

---

## Verdict Ringkas

**Status-driven benar sebagai paradigma** — dan ini justru **mekanisme yang menghasilkan** struktur 3-zona yang sudah divalidasi sebelumnya (Today's Operation / Daily Planning / Eksekusi), bukan ide yang bersaing dengannya.

Namun contoh Anda ("Shipment → Status → Workspace") menyederhanakan satu hal penting yang perlu diluruskan **sebelum freeze**: **status tidak selalu milik Shipment.** Ada tahap di mana status sesungguhnya milik **Hari (agregat)**, dan ada tahap di mana status sesungguhnya milik **Unit** (bukan Shipment). Memaksa semua status menempel di Shipment akan pecah tepat di titik paling penting: **Allocation & Inspeksi**, karena satu Shipment berisi banyak Unit yang **bisa berada di status berbeda-beda secara bersamaan**.

Prinsip yang benar bukan *"Shipment punya status → Shipment punya workspace"*, melainkan:

> **Setiap ENTITAS memiliki status miliknya sendiri. Membuka entitas apa pun selalu memunculkan TEPAT SATU workspace yang cocok dengan status entitas itu saat ini.**

Ini lebih presisi, dan justru **inilah** yang membuat prinsip ini bisa dipakai ulang oleh Inspection, Stuffing, Loading, Monitoring — karena tiap modul punya entitas alaminya sendiri (Unit, Container, Voyage), bukan hanya Shipment.

---

## Mengapa Contoh Sederhana Perlu Diluruskan

Lihat kembali business process yang sudah dibekukan:

- **Requirement Planning** dihitung **per Shipment**, tetapi **Morning Briefing & Container Readiness adalah agregat satu Hari** (gabungan banyak Shipment). Tidak ada satu "Shipment" untuk dibuka di sini — entitasnya adalah **Hari Operasional**.
- **Handover Inspeksi & Container Allocation** pada dasarnya bekerja **per Unit** — satu Shipment berisi 8 Unit; 5 sudah tiba & lolos, 3 belum. Kalau status "menempel" di Shipment, Shipment ini statusnya **apa**? "Menunggu Kedatangan" (karena ada yang belum) atau "Siap Alokasi" (karena sebagian sudah bisa dikerjakan)? **Keduanya benar untuk unit berbeda, di waktu yang sama.**

Ini bukan detail kecil — ini **sumber kebingungan terbesar** kalau tidak diluruskan sebelum freeze, karena akan langsung membebani desain layar Allocation/Inspeksi begitu implementasi dimulai.

**Resolusi:** Status hidup di **tiga tingkat entitas berbeda**, dan setiap tingkat punya workspace status-driven-nya sendiri:

```
HARI OPERASIONAL (agregat)
   status: Belum Planning → Planning Selesai → Briefing Selesai → Readiness Terkonfirmasi
   → workspace: Daily Planning

SHIPMENT (dokumen/kontrak, rollup dari unit-unitnya)
   status: turunan dari kondisi unit-unit di dalamnya (rollup, bukan status independen)
   → workspace: Shipment Queue (kartu ringkas + indikator progres, BUKAN workspace kerja)

UNIT (fisik, atomik — tempat kerja nyata terjadi)
   status: Belum Tiba → Tiba, Menunggu Inspeksi → Lolos, Menunggu Alokasi
           → Teralokasi (provisional) → Siap Stuffing
   → workspace: Inspeksi Handover / Container Allocation (per unit atau kelompok unit)
```

Anda **membuka Shipment untuk orientasi** ("dari 8 unit, ini progresnya"), lalu **membuka Unit (atau kelompok unit yang siap) untuk bekerja**. Shipment = peta; Unit = tempat kerja sesungguhnya terjadi. Ini konsisten dengan keputusan yang sudah dibekukan sebelumnya di **Detail Unit Workspace** — Unit sudah ditetapkan sebagai entitas atomik untuk observasi kondisi; sekarang tervalidasi juga sebagai entitas atomik untuk **eksekusi** status-driven.

---

## Jawaban atas 5 Pertanyaan

### 1. Apakah status-driven lebih sesuai daripada feature-driven?

**Ya, jelas lebih sesuai** — dan ini konsisten dengan prinsip yang sudah dibekukan sejak awal ("FC tidak berpikir Shipment/Container/Briefing/Readiness, tapi berpikir 'apa yang harus saya kerjakan'"). Feature-driven memaksa FC **mengingat peta sistem** ("tahap ini ditangani modul mana?"). Status-driven **membalik beban itu ke sistem** — FC cukup membuka entitas yang relevan, sistem yang tahu apa yang perlu dikerjakan di situ.

**Namun dengan satu syarat penting:** status-driven **menggantikan navigasi**, bukan **menggantikan orientasi**. FC tetap butuh cara melihat "dari semua pekerjaan saya hari ini, mana yang butuh saya buka duluan" — itu adalah **daftar/antrean** (Shipment Queue dari validasi sebelumnya), bukan status-driven per-entitas. Jadi alurnya: **Antrean (orientasi lintas-entitas) → buka satu entitas → sistem tampilkan TEPAT SATU workspace sesuai statusnya.** Status-driven adalah mekanisme *drill-in*, bukan pengganti daftar kerja.

### 2. Apakah setiap status butuh workspace sendiri, atau hanya status dengan aktivitas?

**Hanya status dengan aktivitas nyata.** Aturan pemisahnya:

> **Status berhak punya workspace HANYA jika ia menuntut keputusan atau tindakan aktif dari FC.**

Status yang sifatnya **menunggu** (menunggu kedatangan unit, menunggu pengadaan container) **bukan** aktivitas — tidak ada yang bisa "dikerjakan" FC di sana selain menunggu. Status semacam ini cukup jadi **indikator** (badge/progress) di kartu entitas, bukan layar tersendiri. Ini sudah tervalidasi sebelumnya untuk "Waiting Unit Arrival."

Sebaliknya, status seperti Requirement Planning, Handover Inspeksi, dan Container Allocation **selalu** punya workspace karena masing-masing menuntut FC membuat keputusan (hitung kebutuhan, terima/tolak kondisi unit, tentukan unit→container).

### 3. Batas Status / Workspace / Action?

Tiga istilah ini perlu definisi tegas agar tidak dicampur:

| Istilah | Definisi | Analogi |
|---|---|---|
| **Status** | Fakta tentang **posisi** entitas dalam siklus hidupnya. Bukan layar — hanya keadaan. | "Sedang di mana?" |
| **Workspace** | Layar yang **muncul** ketika status menuntut kerja. Satu workspace = satu **konteks keputusan yang koheren**, bukan satu field atau satu tombol. | "Apa yang harus saya lihat & putuskan di sini?" |
| **Action** | Unit terkecil dari tindakan FC **di dalam** workspace yang mendorong entitas maju ke status berikutnya. | "Apa yang saya lakukan sekarang?" |

**Aturan anti-fragmentasi** (mencegah kebanyakan halaman): gabungkan beberapa status ke **satu** workspace bila statusnya mewakili **konteks kerja yang sama**, hanya beda tingkat kelengkapan (mis. Requirement+Briefing+Readiness = satu "Daily Planning", karena sama-sama bagian dari satu ritual pagi). **Pisahkan** menjadi workspace berbeda hanya bila **pertanyaan yang dijawab benar-benar berbeda** (Inspeksi menjawab "apa kondisi unit ini", Allocation menjawab "unit ini masuk container mana" — dua pertanyaan berbeda meski berurutan).

**Aturan anti-percampuran** (mencegah satu workspace terlalu penuh): jangan satukan dua status yang **actor atau objek keputusannya berbeda**. Inspeksi & Allocation tidak boleh digabung meski keduanya "tentang unit yang sama" — karena keduanya adalah keputusan yang berbeda sifat (kondisi fisik vs penempatan logistik).

Rangkaiannya: **Status menentukan workspace mana yang tampil → di dalam workspace, FC melakukan satu/lebih Action → Action yang selesai mendorong perubahan Status → workspace berikutnya (jika ada) muncul.** Status idealnya **berubah sebagai konsekuensi** dari action selesai, bukan tombol "ubah status" terpisah — kecuali pada titik **serah terima** yang memang butuh konfirmasi eksplisit (mis. "Siap Stuffing" yang sudah divalidasi sebagai gerbang handoff sadar, bukan otomatis).

### 4. Cara berpikir FC yang natural: buka Shipment lalu kerjakan tahap aktif, atau buka menu Requirement/Allocation/Inspection?

**Buka entitas, kerjakan tahap aktif** — ini sudah selaras dengan seluruh dokumen sebelumnya. Tapi presisinya: **entitas yang dibuka tidak selalu Shipment.** Untuk pekerjaan agregat pagi, FC "membuka" konteks **Hari** (bukan satu shipment spesifik). Untuk pekerjaan eksekusi (inspeksi, alokasi), FC pada akhirnya bekerja pada **Unit** (atau kelompok unit), dengan Shipment sebagai bingkai konteks di sekitarnya (nomor SPPB, customer, tujuan). Shipment jarang menjadi tempat *bekerja* — ia lebih sering menjadi tempat *melihat progres* sebelum masuk ke unit yang siap dikerjakan.

### 5. Prinsip UX untuk Domain Freeze (berlaku Inspection, Stuffing, Loading, Monitoring, dst.)

**Prinsip inti (satu kalimat, berlaku universal):**

> **Satu Entitas — Satu Status Aktif — Satu Workspace.**
> Entry point selalu berupa antrean lintas-entitas; membuka satu entitas selalu memunculkan tepat satu workspace yang cocok dengan status entitas itu saat ini; status hanya berhak atas workspace bila menuntut keputusan aktif; status berubah sebagai akibat dari action selesai, bukan tombol ubah-status manual.

**Lima aturan turunan yang menjaga konsistensi lintas modul:**

1. **Tentukan entitas pemilik status secara eksplisit per modul** — jangan asumsikan selalu Shipment. Inspection: entitasnya **Unit**. Loading/Stuffing: entitasnya **Container**. Monitoring: bukan status-driven sama sekali — ia justru **lintas-entitas** by design (menjawab "unit mana butuh perhatian", bukan "apa status satu entitas").
2. **Status pasif = indikator, bukan workspace.** Berlaku di modul mana pun — "menunggu" tidak pernah jadi layar sendiri.
3. **Workspace dibatasi oleh pertanyaan, bukan oleh field.** Satu workspace = satu pertanyaan yang perlu dijawab FC saat itu. Field-field pendukung boleh banyak, tapi pertanyaannya harus satu.
4. **Rollup ke atas, drill ke bawah.** Entitas yang lebih tinggi (Shipment, Hari) selalu menampilkan **ringkasan/rollup** dari entitas di bawahnya (Unit), tidak pernah mencampur status individual unit menjadi satu status Shipment yang dipaksakan tunggal.
5. **Transisi status = konsekuensi, bukan tombol.** Kecuali pada titik serah-terima antar tim/actor (mis. FC → Stuffing) yang memang butuh konfirmasi sadar sebagai gerbang tanggung jawab.

**Cara memvalidasi modul baru (checklist sebelum desain workspace apa pun ke depannya):**
- Apa entitas alami modul ini (bukan tabel — objek nyata yang dikerjakan)?
- Status apa saja yang **menuntut kerja**, dan status apa yang **sekadar menunggu**?
- Adakah level agregat di atasnya (seperti Hari di atas Shipment) yang butuh workspace-nya sendiri?
- Apakah entitas ini punya sub-entitas yang bisa berbeda status secara bersamaan (seperti Unit di dalam Shipment)?

---

## Bagaimana Ini Menyatukan Zona yang Sudah Divalidasi

Struktur 3-zona sebelumnya (Today's Operation / Daily Planning / Eksekusi) **adalah hasil penerapan prinsip ini**, bukan struktur terpisah:

- **Today's Operation** = antrean/orientasi lintas-entitas (bukan status-driven — ia justru tempat *memilih* entitas mana yang mau dibuka).
- **Daily Planning** = workspace status-driven milik entitas **Hari**.
- **Shipment Queue → Handover Inspeksi → Allocation** = drill-in status-driven milik entitas **Unit**, dengan Shipment sebagai bingkai konteks di sekitarnya.
- **Ready for Stuffing** = titik serah-terima sadar (bukan status pasif biasa) → keluar ke workspace Stuffing (entitas berikutnya: Container).

Tidak ada pertentangan antara "status-driven" dan "3 zona" — status-driven adalah **mesin**-nya; 3 zona adalah **bentuk yang muncul** ketika mesin itu diterapkan pada entitas Hari, Shipment, dan Unit sekaligus.

---

## Yang Masih Perlu Dikonfirmasi ke Operasional

1. **Definisi rollup status Shipment** — saat unit-unitnya berbeda status (5 tiba, 3 belum), apakah Shipment cukup menampilkan **progres** (5/8), atau operasional butuh satu label status tunggal untuk Shipment juga (untuk pelaporan/eskalasi)? Ini menentukan seberapa jauh Shipment perlu "berpura-pura" punya satu status.
2. **Siapa pemilik konfirmasi transisi sadar** (mis. "Siap Stuffing") — FC sendiri, atau perlu approval pihak lain sebelum serah-terima ke tim Stuffing?
3. **Konsistensi lintas modul di masa depan** — saat Inspection/Loading/Stuffing dirancang, siapa yang memvalidasi entitas pemiliknya (Unit vs Container) agar tidak terjadi drift dari prinsip ini seiring waktu?

---

## Kesimpulan untuk Domain Freeze

**Status-driven workspace divalidasi sebagai paradigma tunggal untuk seluruh TAM**, dengan koreksi presisi: prinsipnya bukan "Shipment berstatus," melainkan **"setiap entitas berstatus sendiri, dan setiap entitas yang dibuka memunculkan tepat satu workspace sesuai statusnya saat ini."** Hari, Shipment, dan Unit masing-masing adalah entitas dengan status sendiri di tingkat berbeda; Shipment merangkum (rollup), Unit yang sesungguhnya dikerjakan.

Dengan prinsip **"Satu Entitas — Satu Status Aktif — Satu Workspace"** sebagai Domain Freeze, seluruh workspace berikutnya (Inspection, Stuffing, Loading, Monitoring) tinggal menjawab checklist di atas — tanpa perlu menemukan filosofi UX baru setiap kali modul baru dirancang.
