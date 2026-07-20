# Product Decision — Primary Shipment Resi

**Status:** DITETAPKAN (keputusan produk, bukan implementasi PDF)
**Tanggal:** 20 Juli 2026
**Prasyarat:** Audit Resi & Dokumen Cetak (read-only)
**Target:** Menetapkan peran Resi sebagai dokumen utama (Primary Shipment Document) untuk seluruh Shipment di Jaya Sakti Sejati, dan menetapkan filosofi/hierarki informasi yang harus diikuti oleh seluruh penyempurnaan PDF berikutnya.

---

## Ringkasan Keputusan

> **Resi adalah Primary Shipment Document.** Satu Shipment hanya memiliki satu Resi — identitas administrasi utamanya. Waybill dan Packing List adalah Supporting Operational Document — dipakai sesuai kebutuhan operasional, bukan representasi default sebuah Shipment, dan tidak menggantikan Resi.

```
Buat Permintaan
        ↓
Shipment dibuat
        ↓
Cetak Resi
        ↓
Dokumen diserahkan
        ↓
Operasional dimulai
```

Waybill dan Packing List **bukan** bagian dari alur wajib ini.

---

## Problem Statement

Hasil audit read-only sebelumnya menunjukkan:

- Terdapat tiga dokumen cetak berbasis Shipment — Resi, Waybill, Packing List — dengan banyak informasi yang saling *overlap*.
- Office Admin tidak punya petunjuk yang jelas: **dokumen mana yang menjadi representasi utama dari sebuah Shipment?**
- Akibatnya:
  - Identitas dokumen kabur — ketiganya terasa setara.
  - UX print tidak konsisten antar halaman (kombinasi dokumen yang tersedia berbeda-beda tergantung sedang berada di list atau halaman mana).
  - Pengguna harus memutuskan sendiri dokumen mana yang harus dicetak lebih dulu — beban kognitif yang seharusnya tidak perlu ada.

---

## Product Decision

- ✅ **Resi** adalah **Primary Shipment Document**.
- ✅ **Waybill** adalah **Supporting Operational Document**.
- ✅ **Packing List** adalah **Supporting Operational Document**.
- ✅ Seluruh penyempurnaan PDF berikutnya **harus mengikuti hierarki ini**.
- ❌ Tidak ada lagi fitur baru yang menjadikan Waybill atau Packing List sebagai representasi utama Shipment tanpa keputusan bisnis baru.

Semua fitur utama sistem harus mengacu kepada Resi.

---

## Mengapa Resi Menjadi Primary Document

Tiga alasan — satu berbasis kondisi kode yang sudah ada (fakta dari audit), dua lainnya berbasis sifat dokumen (product reasoning):

### a. Resi sudah menjadi satu-satunya dokumen yang universal secara cakupan (fakta dari audit)
Resi berlaku untuk **semua mode** (sea maupun land), sementara Waybill dan Packing List **hanya berlaku untuk shipment moda laut** (`abort(404)` untuk land di controller). Waybill/Packing List secara struktural **tidak mungkin** menjadi dokumen wajib per-Shipment — mereka tidak eksis untuk sebagian shipment. Resi adalah satu-satunya kandidat yang bisa "selalu ada."

### b. Resi merepresentasikan Shipment sebagai identitas, bukan sebagai proses pengapalan
Isi Resi (nomor resi, rute asal–tujuan, pengirim–penerima, QR tracking, ringkasan muatan) menjawab pertanyaan "pengiriman apa ini dan bagaimana melacaknya" — berlaku untuk **setiap** shipment tanpa syarat. Waybill (vessel/voyage/container/POL-POD) dan Packing List (rincian cargo per unit/koli) menjawab pertanyaan operasional pengapalan laut yang lebih spesifik dan teknis. Identitas administratif harus lebih generik daripada detail operasional — pola umum di freight forwarding (nomor resi/tracking number sebagai identitas utama, B/L dan packing list sebagai dokumen pendukung pengapalan).

### c. Multi-user relevance
Resi adalah satu-satunya dokumen yang punya audiens penuh: Office Admin, Field Coordinator, Customer, dan arsip internal. Waybill relevan terutama untuk pihak pelayaran/pelabuhan; Packing List relevan terutama untuk pemeriksaan cargo. Dokumen dengan audiens paling luas adalah kandidat paling natural untuk peran primary.

---

## Peran Dokumen

### 1. Resi — Primary Document
**Digunakan oleh:** Office Admin · Field Coordinator · Customer · Arsip Internal
- Selalu tersedia, untuk semua mode.
- Selalu dapat dicetak.
- Menjadi representasi resmi Shipment.

### 2. Waybill — Supporting Operational Document
- Digunakan hanya apabila memang dibutuhkan pada proses pengiriman laut.
- Tidak menggantikan Resi.
- Tidak menjadi identitas Shipment.

### 3. Packing List — Supporting Operational Document
- Digunakan untuk proses operasional muatan.
- Tidak menjadi dokumen utama.

Resi bukan "dokumen yang lebih penting" dalam arti menggantikan fungsi Waybill/Packing List — perannya berbeda. Resi menjawab "siapa dan ke mana"; Waybill/Packing List menjawab "bagaimana secara teknis diangkut." Ketiganya tetap dibutuhkan; yang berubah hanya mana yang jadi *default* dan mana yang *by-need*.

---

## Filosofi Resi

Resi harus mampu menjawab lima pertanyaan dalam beberapa detik pertama:

1. **Pengiriman apa ini?** → Nomor Resi
2. **Milik siapa?** → Customer, Dealer
3. **Dari mana ke mana?** → Asal → Tujuan
4. **Apa yang dikirim?** → Unit kendaraan atau Cargo
5. **Bagaimana melacaknya?** → QR, Barcode

---

## Information Hierarchy

Prioritas informasi pada Resi harus menjadi:

| Level | Kategori | Isi |
|---|---|---|
| **1** | Identity | Logo, Nomor Resi, QR, Barcode |
| **2** | Shipment Summary | Customer, Dealer, Asal, Tujuan, ETA |
| **3** | Shipment Content | Daftar Unit, Cargo, Jumlah Unit |
| **4** | Operational Detail | Kontak, Moda, Jenis Layanan, No Dokumen, Catatan |

Ini adalah **acuan hierarki untuk redesign PDF Resi berikutnya** — belum diimplementasikan pada layout PDF yang berjalan saat ini. Layout Resi eksisten (hasil audit) sudah memuat sebagian besar elemen ini, namun urutan/bobot visualnya belum eksplisit disusun mengikuti 4 level di atas.

---

## Office Admin Workflow

**Sebelum (kondisi awal, hasil audit):**
```
Create Shipment
        ↓
Office Admin melihat kombinasi dokumen berbeda tergantung
sedang di halaman/list mana (list utama: Resi saja;
halaman detail: Waybill+Packing saja; history: ketiganya) →
tidak ada satu tujuan yang jelas
```

**Sesudah (workflow administrasi resmi):**
```
Buat Permintaan
        ↓
Shipment dibuat
        ↓
Cetak Resi
        ↓
Dokumen diserahkan
        ↓
Operasional dimulai
```

Waybill dan Packing List bukan bagian dari alur wajib — keduanya tetap harus mudah diakses **saat dibutuhkan** secara operasional, bukan dihilangkan.

> **Status implementasi UI (Filament):** hierarki visual "Resi selalu primary/standalone, Waybill+Packing dikelompokkan sebagai supporting action" dan notifikasi CTA "Cetak Resi" pasca-create sudah diimplementasikan pada sprint UX sebelumnya, di layer Filament Resource/Action saja — lihat bagian Rekomendasi Implementasi.

---

## Design Principle

Resi bukan sekadar PDF. Resi adalah **Shipment Summary**.

Ketika seseorang membuka Resi, ia harus memahami Shipment tanpa perlu membuka halaman aplikasi.

---

## Future Direction

Semua fitur baru akan mengikuti keputusan ini. Contoh:

- **QR Tracking** → mengarah ke Resi.
- **Customer Portal** → membuka Resi terlebih dahulu.
- **Email** → melampirkan Resi.
- **WhatsApp** → membagikan Resi.
- **Arsip** → menggunakan Nomor Resi sebagai referensi utama.

Waybill dan Packing List tetap dipertahankan sebagai dokumen pendukung sesuai kebutuhan operasional.

---

## Dampak Terhadap UX

- **Konsistensi ketersediaan dokumen** — sebelumnya berbeda tergantung list/halaman yang dibuka (Resi-saja di list utama, Waybill+Packing-saja di halaman detail, ketiganya di history). Dengan Resi sebagai primary, seluruh entry point Office Admin harus *selalu* menyediakan Resi secara menonjol; Waybill/Packing List boleh tetap kondisional (mis. hanya tampil untuk sea-mode) tapi statusnya jelas sebagai aksi sekunder.
- **Hierarki visual tombol** — audit menemukan skema warna tombol print sebelumnya tidak konsisten antar halaman (mis. `success`/`warning` di satu halaman, `gray` di halaman lain). Prinsip "satu primary, dua supporting" menstandarkan: Resi mendapat penekanan visual sebagai aksi utama; Waybill/Packing List konsisten sebagai aksi sekunder di semua tempat.
- **Mengurangi beban keputusan** — Office Admin tidak perlu memahami perbedaan ketiga dokumen sebelum bisa menyelesaikan alur "shipment dibuat, sekarang apa?". Satu jawaban jelas (cetak Resi) menurunkan friksi.
- **Tidak menghilangkan akses ke Waybill/Packing List** — keputusan ini bukan pembatasan akses, hanya reprioritas visual/alur.

---

## Rekomendasi Implementasi

1. ✅ **Standarkan entry point Office Admin** agar Resi selalu hadir dan menonjol secara konsisten di list utama, halaman detail, dan history. *(Diimplementasikan — sprint UX Filament.)*
2. ✅ **Reklasifikasi visual Waybill/Packing List sebagai supporting action**, dikelompokkan dalam action group sekunder ("Dokumen Lain") di semua lokasi. *(Diimplementasikan — sprint UX Filament.)*
3. ✅ **Notifikasi sukses pasca-Create Shipment menyertakan aksi cetak Resi langsung**, agar alur "Create → Print Resi" jadi satu gerakan. *(Diimplementasikan — sprint UX Filament.)*
4. ⏳ **Redesign layout PDF Resi mengikuti Information Hierarchy (Level 1–4)** di atas — belum dikerjakan, ini pekerjaan sprint PDF berikutnya.
5. ⏳ **Tinjau ulang temuan teknis dari audit**: relasi `originBranch`/`destinationBranch`/`assigned_depot` yang gagal ter-eager-load, `$warnings` konsistensi data yang dihitung tapi tidak pernah ditampilkan, duplikasi kode branding/QR/barcode di tiga method controller. Relevan saat redesign PDF Resi dikerjakan.
6. ⏳ **Waybill dan Packing List tidak perlu redesign prioritas tinggi** — fokus redesign PDF diarahkan ke Resi terlebih dahulu.
7. **Pertahankan gating operasional yang sudah ada** (sea-mode only untuk Waybill/Packing List, Draft tidak bisa dicetak) — keputusan primary/supporting adalah lapisan produk/UX, bukan perubahan pada aturan bisnis yang sudah berjalan.

---

## Freeze Decision

Mulai sprint ini ditetapkan bahwa:

- ✅ Resi adalah Primary Shipment Document.
- ✅ Waybill adalah Supporting Operational Document.
- ✅ Packing List adalah Supporting Operational Document.
- ✅ Seluruh penyempurnaan PDF berikutnya harus mengikuti hierarki ini.
- ❌ Tidak ada lagi fitur baru yang menjadikan Waybill atau Packing List sebagai representasi utama Shipment tanpa keputusan bisnis baru.

---

## Referensi

- Audit dasar: hasil audit read-only "Resi & Dokumen Cetak" — mencakup navigation map, document inventory, architecture map, dan technical findings yang menjadi dasar bagian "Mengapa Resi Menjadi Primary Document" dan "Dampak Terhadap UX".
- Implementasi UI (sudah berjalan): sprint UX Filament — perubahan hanya pada layer Resource/Action Filament (label, warna, pengelompokan action, notifikasi), tanpa mengubah PDF, Blade, controller, route, permission, atau policy.
- Constraint dokumen ini: keputusan produk mengenai peran dan hierarki informasi, bukan implementasi PDF. Redesign layout PDF Resi (Information Hierarchy Level 1–4) adalah pekerjaan sprint berikutnya, belum dikerjakan.
