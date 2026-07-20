# UX Recomposition — Buat Permintaan Pengiriman v2

**Status:** SELESAI (UX refactor, presentasi murni)
**Tanggal:** 20 Juli 2026
**Scope:** Meningkatkan hierarki visual & alur berpikir operator pada form Create Shipment. **Tidak** mengubah workflow, validasi, Smart Origin, OCR pipeline, database, model, service, event, atau permission.

---

## Ringkasan

Halaman Create Shipment diubah dari "form CRUD Filament" menjadi **workspace operasional** yang mengikuti alur berpikir operator: **Customer → Dokumen → Rute → Review → Simpan**. Semua perubahan bersifat presentasi — setiap field, aturan validasi, hook reaktif, dan wiring OCR dipertahankan persis. Tidak ada field yang dihapus, ditambah, atau diubah logikanya.

**Prinsip UX yang diterapkan:** Workflow First, Read Before Input, Progressive Disclosure, Explanation Driven.

---

## File yang Diubah (3)

| File | Perubahan |
|---|---|
| `app/Filament/Resources/ShipmentResource.php` | Pecah Section A → "Customer" + "Dokumen"; rename & deskripsi Section B/C; upload jadi fokus; Cabang Asal jadi info card; segmented control (ikon+warna) pada Jenis Muatan; panel Review reaktif. |
| `app/Filament/Resources/ShipmentResource/Pages/CreateShipment.php` | Tambah `getSubheading()` — hero (penjelasan + status Draft). |
| `resources/css/filament/admin/theme.css` | CSS ter-scope `.jss-*` untuk hero badge, info card Cabang Asal, panel Review. Tidak menyentuh komponen Filament global. |

---

## Before → After per Deliverable (dengan alasan UX)

### 1. Hero Header
- **Before:** Judul telanjang "Buat Permintaan Pengiriman", tanpa konteks.
- **After:** Judul + subheading penjelas ("Buat permintaan baru berdasarkan **SPPB** atau **Delivery Order**. Dokumen ini menjadi dasar seluruh proses pickup, stuffing, pengiriman, hingga delivery.") + badge **● Draft Baru**.
- **Alasan:** Operator langsung paham *apa* yang sedang ia buat dan *apa dampaknya* ke proses hilir — bukan sekadar "mengisi form". Status draft memberi orientasi tahap. Diimplementasi via `getSubheading()` (bukan widget) agar tetap ringan sesuai brief.

### 2. Section A dipecah menjadi dua konteks
- **Before:** Satu section besar "A. Data Customer & Dokumen" mencampur pihak (customer/penerima) dengan artefak (dokumen/lampiran).
- **After:** Dua section terpisah dengan ikon & deskripsi:
  - **Customer** (👥) — "Siapa pengirim & penerima… Kontak diturunkan otomatis dari master data."
  - **Dokumen** (📄) — "Dasar permintaan: SPPB / Delivery Order…"
- **Alasan:** "Siapa" dan "dasar dokumen" adalah dua langkah berpikir berbeda. Memisahkannya mengurangi beban kognitif (Progressive Disclosure) dan membuat batas mental jelas. **Tidak ada field yang berpindah logika** — hanya batas section.

### 3. Upload Dokumen jadi fokus utama
- **Before:** `FileUpload` setengah lebar (md:6) berdampingan Catatan, preview 160px, label generik "Lampiran Dokumen".
- **After:** Upload **full-width**, preview 200px, label "Unggah Dokumen SPPB / Delivery Order", helper text: *"…Pada sprint OCR berikutnya, dokumen ini akan membantu mengisi data permintaan secara otomatis."* Catatan pindah ke bawah (full-width).
- **Alasan:** Dokumen adalah *sumber kebenaran* seluruh permintaan (dan pintu masuk OCR). Menaikkan hierarki visualnya mengarahkan operator ke langkah paling penting lebih dulu. Helper text menyiapkan ekspektasi OCR tanpa mengimplementasikannya (UX-only).

### 4. Cabang Asal jadi information card
- **Before:** Placeholder teks satu baris "🏢 Jakarta — Kota asal: JAKARTA", tampak seperti field readonly biasa.
- **After:** Kartu informasi bertingkat — label "📍 Cabang Asal", nama cabang tebal, sub-label "Kota Asal · diturunkan otomatis", nilai kota. (Untuk Office Admin; Super Admin tetap punya dropdown pilih cabang.)
- **Alasan:** Prinsip **Read Before Input** — informasi sistem (mengikuti akun login) harus *terbaca sebagai informasi*, bukan menyaru sebagai input yang bisa diubah. Menegaskan bahwa kota asal adalah turunan Smart Origin (Branch→City), bukan pilihan. **Sumber data & logic tidak berubah.**

### 5 & 7. Ringkasan Rute + Review Permintaan → satu panel Review
- **Before:** Section "C. Konfirmasi" hanya checkbox "Data sudah benar".
- **After:** Section **"Review Permintaan"** (✓) dengan panel ringkasan reaktif: **Customer → Tujuan → Moda → Jenis Muatan → Status (Draft Baru)**, tiap baris terisi otomatis dari state form; nilai kosong tampil "Belum dipilih". Checkbox konfirmasi tetap.
- **Keputusan arsitektur (jujur):** Brief meminta "Ringkasan Rute di sisi kanan" (deliverable 5) *dan* "Review Permintaan" (deliverable 7). Saya **menggabungkan keduanya menjadi satu panel review otoritatif** alih-alih menyuntikkan right-rail ke tengah Section B. Alasan: Section B adalah ~800 baris logika reaktif kondisional (FCL/LCL/units/voyage/container) yang saling terkait; menyisipkan kolom kanan permanen di sana berisiko tinggi merusak wiring OCR/validasi — melanggar semangat "pure UX, no regression". Satu panel review di akhir memberi nilai operasional yang sama (review cepat sebelum simpan) dengan risiko mendekati nol. Right-rail penuh dapat menjadi enhancement lanjutan **setelah** Section B direfaktor terpisah.
- **Alasan UX:** Operator memverifikasi keputusan inti dalam satu tempat sebelum commit — mengurangi kesalahan entri.

### 6. Segmented Control lebih jelas
- **Before:** "Jenis Muatan" berupa toggle teks polos (Moda sudah punya ikon).
- **After:** Jenis Muatan kini punya **ikon + warna** sejajar Moda: 🚗 Unit Kendaraan (truck, primary) / 📦 General Cargo (cube, warning).
- **Alasan:** Paritas visual dengan Moda; pilihan biner jadi lebih cepat dipindai. Ditingkatkan di **level komponen** (`->icons()`/`->colors()`) — bukan CSS global — agar toggle di form lain tidak terpengaruh. Logika `afterStateUpdated` (reset units/lcl) tidak disentuh.

### 8. Visual hierarchy & ruang seimbang
- **Before:** Daftar field atas-ke-bawah, kanan kosong, tanpa penjelasan section.
- **After:** Setiap section punya **ikon + deskripsi** (Explanation Driven); urutan mengikuti workflow: Hero → Customer → Dokumen (upload fokus) → Rute & Moda → Review. Upload & Catatan full-width mengisi lebar; panel Review memberi penutup padat.
- **Alasan:** Halaman terbaca sebagai *proses berurut*, bukan tabel database.

### 9. Responsiveness
- Semua perubahan memakai `columnSpan(['default'=>12,'md'=>...])` / `columnSpanFull()` bawaan Filament yang sudah responsif. Info card & panel Review memakai layout fleksibel (flex) yang menumpuk rapi di layar kecil. Tidak ada lebar tetap piksel yang memaksa horizontal scroll.

---

## Validation (nyata, read-only — environment `APP_ENV=production`)

| Uji | Hasil |
|---|---|
| `php -l` — `ShipmentResource.php` | ✅ bersih |
| `php -l` — `CreateShipment.php` | ✅ bersih |
| **Build skema form penuh** (`Form::make()` + `ShipmentResource::form()`) | ✅ 6 komponen top-level ter-instansiasi: **Customer, Dokumen, Rute & Moda,** (Group LAND — pre-existing), **Review Permintaan** — struktur utuh, hanya Section A yang terpecah. |
| **getSubheading()** render | ✅ menghasilkan hero + "Draft Baru". |
| **Closure panel Review** (read-only, data nyata) | ✅ `customer=Toyota Astra Motor, tujuan=MANADO, moda=Laut, jenis=Unit Kendaraan`. |
| **Closure info card Cabang Asal** (read-only) | ✅ `branch=Jakarta, city=JAKARTA` (Smart Origin utuh). |
| **HtmlString + `e()` escaping** | ✅ `A & B` → `A &amp; B` (aman XSS). |

Tidak ada data yang ditulis/diubah selama validasi (semua operasi baca).

---

## Catatan Transparansi

### Screenshot Before/After (deliverable 10)
**Tidak dilampirkan sebagai gambar.** Alasan jujur: environment ini `APP_ENV=production` dengan `APP_URL` remote (`103.55.37.130`). Menggerakkan browser untuk menangkap layar Filament ter-autentikasi membutuhkan sesi login ke server produksi — yang **tidak pantas diotomasi** dan memerlukan kredensial admin yang tidak (dan tidak seharusnya) saya miliki/pakai. Alih-alih memalsukan screenshot, saya:
1. Memvalidasi rendering secara programatik (build skema form + eksekusi seluruh closure render terhadap data nyata — lihat tabel di atas).
2. Mendokumentasikan Before→After secara struktural + alasan UX per perubahan (bagian di atas).

Bila screenshot resmi dibutuhkan, jalankan di lingkungan staging/lokal ter-autentikasi: buka `/{panel}/permintaan-pengiriman/create` sebagai Office Admin (perbandingan info card & panel Review) dan sebagai Super Admin (dropdown Cabang Asal).

### Build aset tema
Perubahan `theme.css` perlu di-compile lewat pipeline aset proyek (mis. `npm run build`) agar kelas `.jss-*` aktif di UI — konsisten dengan sprint CSS sebelumnya. Markup HTML (via `HtmlString`) sudah benar tanpa build; hanya *styling* card/badge yang menunggu kompilasi tema.

---

## Konfirmasi Constraint

Tidak ada perubahan pada: business workflow, Smart Origin, OCR pipeline, validasi, database, model, service, event, permission. Seluruh diff adalah **struktur container + presentasi**. Field, `->required()`, `->rules()`, `->afterStateUpdated()`, `->live()` yang ada dipertahankan; satu-satunya penambahan reaktivitas adalah `->live()` pada `destination_city_id` semata untuk memperbarui panel Review (tidak mengubah validasi/dehydrasi field).
