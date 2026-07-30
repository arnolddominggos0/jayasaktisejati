# Domain Freeze — Business Process Field Coordinator (TAM Vehicle)

**Status:** DRAFT untuk validasi operasional → target **DOMAIN FREEZE**
**Tanggal:** 20 Juli 2026
**Lingkup:** Proses operasional FC **setelah** Shipment + Unit terbentuk dari OCR SPPB, sampai unit termuat ke kapal. **Bukan** desain software.
**Basis:** Praktik freight forwarding kendaraan (RORO / Container Vehicle), disandingkan dengan konteks operasional TAM yang sudah ada (MP Briefing, Container Readiness).

> Dokumen ini murni **business process**. Tidak membahas kode, database, UI, atau class. Istilah tahap (Pickup, Handover, Stuffing, dst.) dipakai sebagai **konsep operasional**, bukan artefak sistem.

---

## 0. Percabangan Fundamental — Metode Pengiriman

Sebelum apa pun, satu fakta domain menentukan seluruh proses: **kendaraan dikirim dengan salah satu dari dua metode**, dan keduanya punya proses yang berbeda.

| Metode | Cara muat | Container Planning? | Stuffing? |
|---|---|---|---|
| **Container Vehicle (FCL)** | Unit di-*stuff* ke dalam container, container dimuat ke kapal | **YA** | YA |
| **RORO / Self-drive / Rack** | Unit dikendarai langsung naik ke kapal (atau via rak), tanpa container | **TIDAK** | TIDAK (skip) |

**Implikasi:** Container Planning **hanya relevan untuk metode Container**. Untuk RORO, "planning" yang setara adalah *booking slot kendaraan di kapal* + urutan drive-on — bukan pengelompokan ke container.

> **Ambiguitas #1 (konfirmasi ops):** Apakah metode (Container vs RORO) sudah pasti dari SPPB/booking saat Shipment dibuat, atau FC yang menentukan? Ini menentukan kapan cabang proses dipilih.

Sisa dokumen berfokus pada **jalur Container** (karena di situ Container Planning hidup), dengan catatan jalur RORO di tiap langkah bila berbeda.

---

## 1. Business Process Lengkap — Langkah demi Langkah

### Langkah 1 — Perencanaan Penjemputan (Pickup Planning)

- **Tujuan:** Menentukan kapan & bagaimana unit dijemput dari lokasi asal (PDC/dealer/lokasi SPPB) menuju depo TAM. Menetapkan armada & driver.
- **Actor:** FC (koordinasi), dibantu Office Admin (data SPPB), Driver (pelaksana).
- **Input:** Shipment + daftar Unit (hasil OCR), lokasi asal, target voyage/ETD.
- **Output:** Jadwal penjemputan, penugasan armada/driver.
- **Decision point:** Cakupan layanan (door-to-door vs unit diserahkan langsung di depo) → menentukan apakah langkah jemput perlu.
- **State:** Shipment → *menunggu penjemputan*. Unit → *belum bergerak*.

### Langkah 2 — Penjemputan + Inspeksi Asal (Pickup + PDC Asal)

- **Tujuan:** Memindahkan unit fisik ke depo **dan** merekam kondisi awal unit (bukti serah kondisi).
- **Actor:** Driver (angkut), FC/Inspektur (inspeksi kondisi).
- **Input:** Unit di lokasi asal.
- **Output:** Unit dalam perjalanan/tiba di depo; **hasil inspeksi Pickup** (kondisi + temuan).
- **Decision point (gate):** Kondisi unit → *accept* / *allow with remark* / *return to PDC* (jika kerusakan major).
- **State:** Shipment → *Pickup*. Unit → *terjemput, kondisi tercatat*.

### Langkah 3 — Serah Terima di Depo (Handover Depo)

- **Tujuan:** Menerima unit secara resmi di depo & memverifikasi kondisi saat tiba (pembanding dengan inspeksi asal).
- **Actor:** FC / Koordinator Depo, Inspektur.
- **Input:** Unit tiba di depo.
- **Output:** Unit resmi masuk inventori depo (yard); **hasil inspeksi Handover** + gate decision.
- **Decision point (gate):** Sama seperti Langkah 2 — unit dengan kerusakan major bisa *return to PDC* dan **keluar dari rencana muat**.
- **State:** Shipment → *Handover*. Unit → *di yard depo, siap direncanakan*.

> Setelah Langkah 3, sistem/FC tahu **unit mana yang benar-benar ada & lolos** di depo. Ini prasyarat penting untuk **Container Planning final** (Langkah 5).

### Langkah 4 — MP Briefing & Container Readiness (harian)

- **Tujuan:** Pada briefing harian, menetapkan **kesiapan manpower** dan **kesiapan container secara agregat** (kebutuhan vs ketersediaan container hari itu untuk depo).
- **Actor:** FC / Koordinator Depo (memimpin briefing), tim MP.
- **Input:** Agregat kebutuhan container dari seluruh shipment yang akan di-stuff (turunan dari perencanaan kebutuhan — lihat Langkah 5a), stok/pasokan container.
- **Output:** **Container Readiness** — pernyataan "cukup / kurang N container tipe X hari ini"; kesiapan MP.
- **Decision point:** Jika container **kurang** → eskalasi pengadaan / geser sebagian unit ke voyage berikutnya.
- **State:** Tidak mengubah Unit/Container secara fisik; menetapkan **konteks harian** untuk eksekusi stuffing.

### Langkah 5 — Container Planning (fokus utama)

Container Planning punya **dua level** yang terjadi di waktu berbeda:

**5a. Perencanaan Kebutuhan (Requirement) — lebih awal**
- **Tujuan:** Menghitung **berapa** container & **tipe/ukuran** apa yang dibutuhkan untuk unit-unit shipment (mis. jumlah unit ÷ kapasitas per container, dengan/ tanpa rak).
- **Actor:** FC.
- **Input:** Jumlah & karakteristik unit (dimensi/berat bila tersedia), tujuan, target voyage.
- **Output:** **Kebutuhan container per shipment** (mis. "2 × 40HC") → diagregasi ke Container Readiness (Langkah 4).
- **Kapan:** Bisa segera setelah unit diketahui (pasca-OCR), disempurnakan saat unit tiba di depo.

**5b. Penetapan Pengelompokan (Assignment/Grouping) — mendekati stuffing**
- **Tujuan:** Menentukan **unit mana masuk container mana** (Unit A,B → Container 1) dengan pertimbangan: tujuan sama, distribusi berat, dimensi, urutan bongkar, penggunaan rak.
- **Actor:** FC (perencana), Tim Stuffing (pelaksana).
- **Input:** Daftar unit yang **lolos & ada** di depo (pasca-Handover), Container Readiness (container tersedia), kendala voyage (cut-off, tujuan).
- **Output:** **Rencana grouping unit → container (provisional)**.
- **Decision point:** Apakah semua unit muat sesuai rencana kebutuhan? Jika ada unit *return to PDC* / tambahan → **re-grouping**.
- **State:** Container → *direncanakan (belum final)*. Unit → *punya rencana container (provisional)*.

> **Kunci:** Level 5a menghasilkan **kebutuhan** (angka), level 5b menghasilkan **pengelompokan** (unit↔container) yang **masih boleh berubah** sampai stuffing.

### Langkah 6 — Stuffing (Pemuatan ke Container) + Inspeksi Loading

- **Tujuan:** Memuat unit fisik ke dalam container sesuai rencana, memasang rak (bila ada), lalu **menyegel** container.
- **Actor:** Tim Stuffing (pelaksana), FC (pengawas/verifikator), Inspektur (inspeksi Loading).
- **Input:** Unit di depo, container tersedia, rencana grouping (5b).
- **Output:** Unit **fisik di dalam container**; **nomor segel** tercatat; hasil inspeksi Loading.
- **Decision point (gate):** Kondisi unit saat muat; kesesuaian dengan rencana.
- **State:** **Unit ↔ Container menjadi FINAL** saat container disegel. Shipment → *Stuffing*. Container → *terisi & tersegel*.

### Langkah 7 — Pengiriman ke Pelabuhan (Delivery to Port) & Stacking

- **Tujuan:** Membawa container terisi ke pelabuhan/terminal, menumpuk (stacking) menunggu muat kapal.
- **Actor:** Driver/trucking, FC (koordinasi), terminal.
- **Input:** Container tersegel.
- **Output:** Container di terminal (stacking), siap dimuat sesuai voyage.
- **State:** Shipment → *DeliveryToPort → Stacking*. Container → *di terminal*.

### Langkah 8 — Muat ke Kapal (Unit Loading → On Ship → Vessel Depart)

- **Tujuan:** Container/unit dimuat ke kapal sesuai voyage & cut-off; kapal berangkat.
- **Actor:** Terminal/pelayaran; FC hanya konfirmasi keberangkatan.
- **Input:** Container di terminal, alokasi slot voyage.
- **Output:** Container di kapal; kapal berangkat.
- **State:** Shipment → *UnitLoading → OnShip → VesselDepart*. Ini **akhir tanggung jawab utama FC asal** (fase pra-transfer selesai).

> Fase pasca-transfer (VesselArrival → Unloading → Dooring/PDC Tujuan) menjadi tanggung jawab **FC/depo tujuan**, di luar fokus Container Planning.

---

## 2. Jawaban Eksplisit atas 10 Pertanyaan

| # | Pertanyaan | Jawaban domain |
|---|---|---|
| 1 | Langkah FC setelah Shipment+Unit terbentuk? | **Perencanaan & pelaksanaan penjemputan → serah terima di depo (dengan inspeksi/gate)**. Membawa unit fisik ke depo lebih dulu; container planning menyusul setelah unit diketahui & konvergen di depo. |
| 2 | FC melakukan Container Planning **sebelum** Stuffing? | **Ya, untuk metode Container.** Stuffing tidak bisa dieksekusi tanpa tahu unit→container. RORO: tidak ada container planning. |
| 3 | Tujuan Container Planning? | **Kombinasi:** (a) menghitung **kebutuhan** container (jumlah), (b) menentukan **tipe/ukuran** container (20/40/HC, rak vs non-rak), (c) **mengelompokkan** unit (tujuan sama, berat, dimensi, urutan bongkar), (d) **optimasi/kelayakan** (minimalkan jumlah container & risiko demurrage, hormati batas berat). |
| 4 | Kapan dilakukan? | **Bertingkat:** *kebutuhan* dapat diestimasi **segera setelah OCR** (jumlah unit diketahui) dan disempurnakan **setelah unit tiba di depo**; *pengelompokan final* dilakukan **mendekati Stuffing** (setelah Handover, saat unit yang lolos sudah pasti), dalam konteks **MP Briefing** harian. |
| 5 | Per Shipment / Voyage / hari? | **Kombinasi berlapis:** *pengelompokan unit* bersifat **per-shipment**; *ketersediaan & eksekusi* container diagregasi **per depo per hari & per voyage** (briefing + cut-off). Satu shipment direncanakan, tetapi dieksekusi dalam pasokan container bersama untuk voyage hari itu. |
| 6 | Di tahap planning: Unit A→Container X atau baru kebutuhan? | **Dua level.** Awal = **kebutuhan saja** ("butuh 2×40HC"). Mendekati stuffing = **pengelompokan provisional** (Unit A,B→Container 1). Ikatan definitif belum terjadi di planning. |
| 7 | Assignment Unit→Container boleh berubah sebelum Stuffing? | **Ya — provisional sampai stuffing.** Bisa berubah karena unit *return to PDC*, unit tambahan/kurang, perubahan ketersediaan container, atau penyeimbangan berat. |
| 8 | Kapan Unit↔Container FINAL? | **Saat Stuffing — tepatnya saat container disegel.** Setelah unit fisik masuk container dan segel terpasang (nomor segel tercatat), ikatan final. Mengubah setelah itu = *unstuffing* fisik (eksepsional). **Bukan** saat planning, **bukan** setelah loading ke kapal. |
| 9 | Hubungan Container Planning ↔ Container Readiness? | **Tidak independen — Planning memberi makan Readiness.** Alur: *Perencanaan kebutuhan (per shipment)* → **diagregasi** → *Container Readiness (kebutuhan vs pasokan, di briefing harian)* → jika cukup, lanjut *pengelompokan & stuffing*; jika kurang, eskalasi/geser. Readiness = **gerbang go/no-go agregat** bagi eksekusi Planning. |
| 10 | Voyage mempengaruhi Planning, atau Planning dulu baru masuk Voyage? | **Keduanya, tapi berurutan.** Voyage (jadwal, cut-off, tujuan, alokasi slot) **membingkai** planning — unit direncanakan **untuk** voyage tertentu. Lalu output planning (container terisi) **masuk secara fisik** ke voyage (stacking → muat kapal). Jadi: **Voyage membingkai → Planning mengisi → Container masuk Voyage.** |

---

## 3. Ringkasan Perubahan State

### Shipment
```
(dibuat via OCR) → Menunggu Penjemputan → Pickup → Handover
   → [Container: Stuffing → DeliveryToPort → Stacking → UnitLoading → OnShip → VesselDepart]
   → [RORO: DeliveryToPort → drive-on → VesselDepart]
```

### Unit
```
Terbentuk (OCR) → Terjemput (kondisi tercatat) → Di yard depo (lolos gate)
   → Punya rencana container (provisional) → Termuat & tersegel (FINAL) → Di kapal
   (cabang: return_to_pdc → keluar dari rencana muat)
```

### Container
```
(konsep kebutuhan: "butuh N×tipe") → Tersedia (Readiness) → Direncanakan (grouping provisional)
   → Terisi & tersegel (FINAL) → Di terminal (stacking) → Di kapal (masuk voyage)
```

**Titik final kritis:** ikatan **Unit↔Container** = **saat segel**. Ikatan **Container↔Voyage** = **saat muat ke kapal** (didahului booking/alokasi slot).

---

## 4. Decision Points (ringkas)

| Titik | Keputusan | Konsekuensi |
|---|---|---|
| Awal | Metode: Container vs RORO | Menentukan ada/tidaknya Container Planning |
| Inspeksi Pickup/Handover | accept / allow_with_remark / **return_to_pdc** | Unit major-damage keluar dari rencana muat |
| Briefing (Readiness) | Container cukup / kurang | Kurang → pengadaan atau geser ke voyage berikutnya |
| Pengelompokan (5b) | Semua unit muat sesuai kebutuhan? | Tidak → re-grouping / tambah container |
| Stuffing | Segel container | Mengunci Unit↔Container (final) |

---

## 5. Actor per Fungsi

| Fungsi | Actor utama | Pendukung |
|---|---|---|
| Penjemputan | Driver | FC (koordinasi) |
| Inspeksi (Pickup/Handover/Loading/Dooring) | Inspektur / FC | — |
| Container Planning (kebutuhan & grouping) | **FC** | Koordinator Depo |
| MP Briefing & Container Readiness | FC / Koordinator Depo | Tim MP |
| Stuffing & segel | Tim Stuffing | FC (verifikasi) |
| Booking voyage / alokasi slot | **Office Admin** (bukan FC) | — |
| Pengiriman ke port | Driver/trucking | FC |

> Catatan: **Booking voyage & keputusan komersial = Office Admin**, bukan FC. FC mengeksekusi operasional di depo terhadap voyage yang sudah ditetapkan.

---

## 6. Ambiguitas yang Perlu Dikonfirmasi ke Operasional

Sebelum di-freeze, poin berikut **harus divalidasi tim lapangan** karena mengubah bentuk proses:

1. **Metode per shipment** — Container vs RORO: ditentukan dari SPPB/booking (Office) atau oleh FC? Bisakah satu shipment campur?
2. **Racking** — Apakah TAM memakai rak (mobil ditumpuk dalam container) atau floor-loaded? Ini menentukan **kapasitas unit per container** dan rumus kebutuhan.
3. **Level assignment saat planning** — Apakah FC menetapkan unit→container **tentatif** saat planning, atau **baru di lantai stuffing**? (Praktik bervariasi; menentukan apakah "rencana grouping" adalah artefak resmi atau sekadar estimasi.)
4. **Konsolidasi multi-shipment** — Bolehkah **satu container berisi unit dari beberapa shipment** (mis. tujuan sama, voyage sama)? Ini mengubah Container Planning dari per-shipment murni menjadi per-voyage.
5. **Pemilik pengadaan container** — Siapa mengadakan container saat Readiness "kurang": FC, Office, atau pihak pelayaran/depo? 
6. **Data dimensi/berat unit** — Apakah OCR SPPB menyediakan dimensi/berat, atau FC memakai standar model? Menentukan akurasi perhitungan kebutuhan.
7. **Definisi "final"** — Apakah "final" dipicu oleh **segel fisik** (dan pencatatan nomor segel), atau ada konfirmasi terpisah? Bagaimana menangani **unstuffing** eksepsional?
8. **Timing cut-off vs stuffing** — Seberapa dekat stuffing dengan cut-off voyage? Apakah planning harus mengunci sebelum briefing hari-H atau bisa H-0?
9. **RORO planning** — Untuk RORO, apakah ada perencanaan setara (urutan drive-on, slot dek) yang perlu dimodelkan, atau cukup booking slot?
10. **Batas tanggung jawab FC asal vs FC tujuan** — Titik serah tanggung jawab (VesselDepart?) perlu ditegaskan agar kepemilikan tugas jelas.

---

## 7. Rekomendasi Domain Freeze

Jika tim operasional mengonfirmasi jawaban §2 dan menutup ambiguitas §6, maka **model proses berikut siap dibekukan**:

1. **Container Planning = proses dua-level milik FC**: *kebutuhan* (awal, per-shipment, memberi makan Container Readiness) dan *pengelompokan* (mendekati stuffing, provisional).
2. **Container Readiness = gerbang agregat harian** antara kebutuhan (Planning) dan eksekusi (Stuffing).
3. **Ikatan Unit↔Container final saat segel (Stuffing)** — bukan saat planning.
4. **Voyage membingkai Planning; output Planning masuk Voyage saat muat kapal.**
5. **RORO tidak melewati Container Planning** — jalur terpisah.

→ Setelah konfirmasi, dokumen ini menjadi **Domain Freeze** dan basis desain sistem berikutnya.
