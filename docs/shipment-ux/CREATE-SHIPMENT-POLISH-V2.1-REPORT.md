# UX Polish — Buat Permintaan Pengiriman v2.1

**Status:** SELESAI (UX polish, presentasi murni)
**Tanggal:** 20 Juli 2026
**Prasyarat:** [Recompose v2](CREATE-SHIPMENT-RECOMPOSE-V2-REPORT.md)
**Filosofi:** *Less interface, more workflow* — hierarki lewat spacing & typography, bukan border. Mengurangi visual noise, bukan menambah dekorasi.

---

## Ringkasan

Iterasi penyempurnaan di atas struktur v2 (Hero → Customer → Dokumen → Rute → Review). Struktur **tidak berubah**; halaman dibuat lebih ringan: subtitle dipangkas, border dikurangi, whitespace ditambah, Cabang Asal & Review jadi lebih ringan, primary action lebih dominan. Semua presentasi — nol perubahan workflow/validasi/logic.

---

## File yang Diubah (3)

| File | Perubahan |
|---|---|
| `app/Filament/Resources/ShipmentResource.php` | Subtitle 4 section dipangkas jadi satu kalimat; helper upload dipersingkat; Review → summary card (label di atas nilai); Cabang Asal → markup ringan; segmented control: hapus warna mencolok, tambah class scoping; class `jss-section` untuk whitespace. |
| `app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php` | Hero dipangkas satu kalimat + badge 🟢; primary action → "Buat Permintaan" (size lg + ikon), cancel → "Batal". |
| `resources/css/filament/admin/theme.css` | Blok `.jss-*` ditulis ulang: card ringan (border→typography), Review stacked, segmented lebih nyaman, whitespace section, badge hijau. |

---

## Before → After per Deliverable (+ alasan UX)

### 1. Hero lebih ringkas
- **Before:** 3 baris menjelaskan seluruh workflow ("…pickup, stuffing, pengiriman, hingga delivery").
- **After:** 1 baris — "Buat permintaan baru berdasarkan **SPPB** atau **Delivery Order**." + badge kecil **🟢 Draft Baru**.
- **Alasan:** Hero cukup memberi orientasi; workflow lengkap sudah tercermin di urutan section. Operator paham dalam <3 detik tanpa membaca paragraf.

### 2. Subtitle dipersingkat (konsistensi)
- **Before:** Tiap section satu paragraf panjang.
- **After:** Satu kalimat: Customer "Pilih pengirim dan penerima." · Dokumen "Unggah SPPB atau Delivery Order." · Rute & Moda "Tentukan tujuan dan moda pengiriman." · Review "Pastikan data sudah benar."
- **Alasan:** Operator tidak membaca paragraf. Pola seragam (ikon → judul → 1 subtitle → isi) di semua section = ritme visual konsisten.

### 3. Cabang Asal — informasi sistem yang ringan
- **Before:** Kartu ber-border, background terisi, seperti panel besar.
- **After:** Tanpa border/panel — aksen tipis di kiri, label kecil uppercase "📍 Cabang Asal", nama cabang tebal, baris "Kota Asal • JAKARTA". Hierarki lewat typography + whitespace.
- **Alasan:** Ini info readonly, bukan input. Menurunkan "berat" visualnya menegaskan sifatnya sebagai informasi, bukan field — tanpa menarik perhatian berlebih.

### 4. Upload dokumen
- **Before:** Helper text 2 kalimat menyebut "sprint OCR berikutnya…".
- **After:** "Unggah SPPB atau Delivery Order. Dokumen ini akan digunakan pada proses OCR." + dropzone sedikit lebih tinggi (min 9.5rem).
- **Alasan:** Upload tetap fokus utama section Dokumen; penjelasan OCR cukup satu frasa. Area lebih besar = lebih mudah dikenali & di-drop.

### 5. Review → summary card
- **Before:** Gaya tabel (label kiri, nilai kanan, garis antar-baris).
- **After:** Kartu ringkas — judul "Ringkasan", tiap item **label di atas, nilai di bawah**, pemisah tipis; nilai kosong "Belum dipilih" (italic, muted); Status = 🟢 Draft Baru.
- **Alasan:** Format stacked lebih mudah dipindai daripada tabel; readability diutamakan. Menghapus kesan "membaca data grid".

### 6. Segmented control lebih nyaman
- **Before:** Jenis Muatan berwarna mencolok (primary/warning); area klik standar.
- **After:** Netral (ikon saja, tanpa warna mencolok pada Moda & Jenis), padding tombol lebih besar (klik lebih nyaman). Di-scope via `jss-segmented` — toggle di form lain tidak terpengaruh.
- **Alasan:** Brief: "Tidak perlu warna mencolok." Ikon sudah cukup untuk pindai cepat; area klik lebih besar = nyaman dipakai harian.

### 7. Whitespace
- **Before:** Padat, section berdempetan.
- **After:** Padding isi section 1.25rem×1.5rem, jarak antar-section +0.5rem, line-height subtitle 1.55.
- **Alasan:** Ruang bernapas > garis. Halaman terasa lebih tenang.

### 8. Border cleanup
- **Before:** Border di card Cabang Asal & tiap baris Review.
- **After:** Border dihapus/diminimalkan; pemisahan lewat whitespace + typography (Cabang Asal borderless; Review hanya garis pemisah tipis antar item).
- **Alasan:** Hierarki via spacing/alignment, bukan garis — mengurangi visual noise.

### 9. Tombol aksi
- **Before:** "Create" default (label generik), ukuran standar.
- **After:** **"Buat Permintaan"** — primary, size **lg**, ikon paper-airplane; **"Batal"** sekunder (gray).
- **Alasan:** Aksi utama jelas & dominan; secondary tidak bersaing. Handler submit bawaan CreateRecord tidak diubah.

### 10. Konsistensi visual
- Semua section kini: **ikon → judul → subtitle 1 baris → isi**. Tidak ada lagi campuran paragraf-panjang vs satu-baris.

---

## Validation (nyata, read-only — `APP_ENV=production`)

| Uji | Hasil |
|---|---|
| `php -l` (ShipmentResource, CreateShipment) | ✅ bersih |
| **Build skema form** | ✅ 6 komponen top-level — struktur v2 utuh (`extraAttributes` pada Section & ToggleButtons valid) |
| **getSubheading** | ✅ "Buat permintaan baru berdasarkan SPPB atau Delivery Order." + 🟢 Draft Baru |
| **Primary/secondary action** (via reflection) | ✅ label "Buat Permintaan" / "Batal" |
| **Review closure** (render) | ✅ summary card stacked; kosong → "Belum dipilih" |
| **Origin card closure** (render) | ✅ "📍 Cabang Asal / Jakarta / Kota Asal • JAKARTA" |

Tidak ada data ditulis/diubah selama validasi.

---

## Catatan Transparansi

- **Screenshot Before/After:** tidak dilampirkan sebagai gambar — environment `APP_ENV=production` dengan `APP_URL` remote (`103.55.37.130`); mengotomasi browser ter-autentikasi ke server produksi tidak pantas & butuh kredensial admin. Rendering divalidasi programatik (build form + eksekusi closure atas data nyata), dan Before→After didokumentasikan struktural + alasan UX di atas. Screenshot resmi sebaiknya diambil di staging/lokal: `/{panel}/permintaan-pengiriman/create` sebagai Office Admin (info card & Review) dan Super Admin (dropdown Cabang Asal).
- **Build tema:** `theme.css` perlu di-compile (mis. `npm run build`) agar kelas `.jss-*`, `.jss-section`, `.jss-segmented` aktif. Markup HTML sudah benar tanpa build; hanya styling yang menunggu kompilasi.

---

## Konfirmasi Constraint

Tidak ada perubahan pada workflow, validasi, Smart Origin, OCR pipeline, business logic, database, model, service, event, permission. Seluruh diff = teks presentasi + CSS + label/ukuran tombol. Struktur Hero → Customer → Dokumen → Rute & Moda → Review → Action dipertahankan.
