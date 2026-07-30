# Domain Validation — Container Requirement Planning vs Container Allocation (FC, TAM Vehicle)

**Status:** VALIDASI HIPOTESIS → kandidat DOMAIN FREEZE
**Tanggal:** 20 Juli 2026
**Pendamping:** [`DOMAIN-FREEZE-BP-FC-CONTAINER-PLANNING.md`](DOMAIN-FREEZE-BP-FC-CONTAINER-PLANNING.md)
**Sifat:** Business process operasional. Bukan software.

---

## Verdict

**Hipotesis Anda BENAR.** Terdapat **dua proses operasional yang berbeda**, bukan satu:

| | **Container Requirement Planning** | **Container Allocation** |
|---|---|---|
| Pertanyaan | *Berapa & jenis apa container yang saya butuhkan?* | *Unit ini masuk container yang mana?* |
| Output | **Kebutuhan** (angka + tipe) — mis. 1 Rack + 1 Regular | **Assignment** — Unit A→Container 01 |
| Sifat data | Estimasi / rencana (agregat) | Aktual (per unit fisik) |
| Waktu | Awal (dekat OCR), difinalkan menjelang hari stuffing | Setelah unit ada di depo, dieksekusi saat stuffing |
| Reversibilitas | Sangat fleksibel | Fluid sampai segel, lalu final |
| Untuk siapa | **Input Morning Briefing / Container Readiness** | **Input Stuffing** |
| Actor | FC (perencana) | FC (perencana) + Tim Stuffing (pelaksana) |

Keduanya beda **tujuan, waktu, kematangan data, dan konsumen output**. Menyatukannya = kesalahan domain yang akan merusak desain Readiness. **Pisahkan.**

Contoh Anda (`8 Unit → 1 Rack + 1 Regular`) tepat: itu **Requirement** (tipe + jumlah), bukan assignment. Dan pemakaian **"Rack"** mengonfirmasi TAM memakai sistem rak (mobil ditumpuk dalam container) — ini menetapkan bahwa **kapasitas unit per container bergantung tipe (Rack vs Regular)**, yang menjadi dasar rumus Requirement.

---

## Koreksi & Penajaman (bagian hipotesis yang perlu disesuaikan)

Hipotesis Anda benar secara struktur; tiga penajaman berdasarkan praktik nyata:

**1. Requirement bukan sekali-jadi saat OCR — ia mengeras bertahap.**
Estimasi pertama bisa segera setelah OCR (jumlah + model unit sudah diketahui). Namun Requirement yang **dipakai briefing** adalah requirement untuk **hari stuffing tertentu / voyage tertentu**, dan baru **firm** setelah: (a) unit dipastikan masuk voyage itu, (b) kondisi unit diketahui (ada yang mungkin ditolak). Jadi: *estimasi di OCR → dikonfirmasi menjelang briefing hari-stuffing.*

**2. Requirement bersifat "per-shipment, tetapi diagregasi per hari/voyage."**
FC menghitung kebutuhan per shipment, lalu **menjumlahkan seluruh shipment yang akan di-stuff pada hari itu (untuk voyage terkait)** menjadi total kebutuhan hari itu → itulah yang masuk Morning Briefing. Requirement bukan angka yang berdiri sendiri per shipment; ia diakumulasi ke tingkat **hari/voyage**.

**3. Allocation praktis bekerja pada "kolam unit hari itu," bukan satu shipment terisolasi.**
Saat allocation, FC melihat **semua unit yang hadir & lolos di depo untuk voyage/hari itu**. Bila kebijakan mengizinkan, satu container bisa berisi unit dari **beberapa shipment** (tujuan/voyage sama) demi mengisi penuh. Jadi allocation lebih dekat ke **per-voyage pool** daripada per-shipment murni. → *Perlu dikonfirmasi ke ops (lihat §Konfirmasi).*

---

## Jawaban atas 6 Pertanyaan

### 1. Dua proses berbeda atau satu proses sama?
**Dua proses berbeda.** Requirement = perencanaan **kapasitas/pengadaan** (agregat, ke depan, untuk briefing). Allocation = perencanaan **eksekusi** (per-unit fisik, real-time, untuk stuffing). Beda tujuan, waktu, dan konsumen.

### 2. Kapan FC mulai menghitung kebutuhan container?
**Segera setelah OCR (estimasi awal), difinalkan menjelang hari stuffing.** Bukan setelah Pickup/Handover — Anda tidak menunggu unit fisik untuk *menghitung kebutuhan* (jumlah + model sudah cukup). Tetapi angka final dikunci setelah set unit & voyage pasti (sebelum briefing hari-H).
*Catatan: Handover mempengaruhi **Allocation**, bukan **Requirement**.*

### 3. Apakah Requirement menjadi input Morning Briefing & Container Readiness?
**Ya — dan Container Readiness BERGANTUNG padanya.** Container Readiness = **rekonsiliasi kebutuhan (Requirement) vs pasokan (container tersedia) + manpower**, yang ditetapkan **di** Morning Briefing. Tanpa Requirement, Readiness tidak punya sisi "kebutuhan" untuk direkonsiliasi. Jadi:
```
Requirement (permintaan)  ─┐
                           ├─→ Morning Briefing ─→ Container Readiness (status siap/kurang)
Pasokan container + MP  ───┘
```
Readiness **tidak** independen dari Requirement.

### 4. Kapan FC menentukan Unit A → Container X?
**Mulai ketika unit sudah berada di depo (pasca-Handover); dikunci saat Stuffing.**
- Bukan saat planning awal (unit belum tentu hadir/lolos).
- Boleh ada **grouping tentatif** begitu unit hadir & lolos di depo.
- **Assignment definitif = di lantai stuffing, final saat container disegel.**
Alasan: Anda tidak bisa mengikat unit spesifik ke container spesifik sebelum unit itu fisik ada dan lolos inspeksi.

### 5. Boleh berubah sebelum stuffing? Dalam kondisi apa?
**Ya — allocation fluid sampai segel.** Perubahan lazim karena:
- Unit gagal inspeksi (Handover/Loading) → *return to PDC* → keluar, regroup.
- Unit datang terlambat / tidak datang → geser ke container/voyage lain.
- Container rencana tidak tersedia/rusak → substitusi container.
- Realita berat/dimensi ≠ rencana → penyeimbangan ulang.
- Rak tidak siap → floor-load atau tunda.
- Tekanan cut-off / sisa ruang → resequence agar container terisi penuh.
- Tambahan unit dadakan untuk mengisi ruang kosong.

### 6. Opsi A atau B?
**Opsi A yang benar** (dengan satu penyisipan: aliran fisik unit).

```
Shipment + Unit
      ↓
Container Requirement Planning        (hitung kebutuhan → agregasi per hari/voyage)
      ↓
Morning Briefing                      (kebutuhan bertemu pasokan + manpower)
      ↓
Container Readiness                   (status: siap / kurang N)
      ↓                                ┌───────────────────────────────┐
(paralel: Pickup → Handover)  ────────►│ unit hadir & lolos di depo    │
      ↓                                └───────────────────────────────┘
Container Allocation                  (Unit → Container, provisional)
      ↓
Stuffing                              (muat + segel → ikatan FINAL)
```

**Opsi B salah.** Allocation **tidak boleh** mendahului Briefing/Readiness karena: (a) Anda belum tahu container tersedia (itu output Readiness), dan (b) unit belum tentu hadir/lolos di depo. Allocation secara logis berada **setelah** Readiness mengonfirmasi pasokan **dan** setelah unit tiba.

---

## Business Process Ringkas (urutan kerja FC)

| Langkah | Tujuan | Input | Output | Actor | Decision Point |
|---|---|---|---|---|---|
| **Requirement Planning** | Hitung kebutuhan container (jumlah + Rack/Regular) | Unit (jumlah+model), tujuan, target voyage | Kebutuhan per shipment → diagregasi per hari/voyage | FC | — |
| **Morning Briefing** | Pertemukan kebutuhan dgn pasokan & manpower | Total kebutuhan hari itu, stok container, MP | Rencana kerja harian | FC / Koord. Depo | Kebutuhan vs kapasitas |
| **Container Readiness** | Nyatakan kesiapan container (agregat) | Kebutuhan vs pasokan | Status siap / kurang N tipe X | FC | **Kurang → pengadaan / geser voyage** |
| *(Pickup → Handover)* | Hadirkan unit fisik di depo + inspeksi | Unit di asal | Unit di yard, lolos gate | Driver / Inspektur / FC | **return_to_pdc → keluar rencana** |
| **Container Allocation** | Tentukan Unit → Container (provisional) | Unit hadir+lolos, container siap, kendala voyage | Rencana grouping unit↔container | FC | Semua unit muat? → regroup |
| **Stuffing** | Muat unit ke container, segel | Unit + container + rencana grouping | Unit dlm container, **nomor segel** | Tim Stuffing / FC | **Segel → ikatan FINAL** |

---

## Ringkasan State

- **Requirement** hidup di ranah *rencana/agregat* — tidak mengubah unit fisik.
- **Container Readiness** = gerbang go/no-go agregat sebelum eksekusi.
- **Allocation** = rencana per-unit yang **provisional** sampai stuffing.
- **Final Unit↔Container = saat segel** (di Stuffing), bukan saat planning maupun setelah muat kapal.

---

## Yang Masih Perlu Dikonfirmasi ke Operasional (sebelum freeze)

1. **Konsolidasi multi-shipment:** bolehkah satu container berisi unit dari **beberapa shipment** (tujuan/voyage sama)? → menentukan apakah Allocation bersifat per-shipment atau per-voyage pool.
2. **Kapasitas per tipe:** berapa unit per **Rack** dan per **Regular** (standar TAM)? → dasar rumus Requirement.
3. **Grouping tentatif:** apakah "rencana grouping" sebelum stuffing adalah artefak resmi yang dicatat, atau sekadar catatan FC di lapangan?
4. **Pemicu final:** apakah "final" = segel fisik + pencatatan nomor segel? Bagaimana menangani **unstuffing** eksepsional?
5. **Requirement lock:** pada jam berapa (relatif briefing/cut-off) angka Requirement dikunci agar Readiness akurat?

---

## Kesimpulan untuk Freeze

Model dua-proses Anda **valid dan siap dibekukan** dengan urutan **Opsi A**:

> **Requirement Planning → Morning Briefing → Container Readiness → (unit tiba di depo) → Container Allocation → Stuffing (final saat segel).**

Requirement menjawab *"butuh apa hari ini"* (input briefing). Allocation menjawab *"unit ini ke container mana"* (input stuffing). Container Readiness adalah **jembatan** keduanya: rekonsiliasi kebutuhan vs pasokan. Setelah lima konfirmasi di atas ditutup, jadikan ini **Domain Freeze**.
