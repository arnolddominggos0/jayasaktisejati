# Micro UX Polish — Buat Permintaan Pengiriman v2.2

**Status:** SELESAI (micro polish, presentasi murni)
**Tanggal:** 20 Juli 2026
**Prasyarat:** [Polish v2.1](CREATE-SHIPMENT-POLISH-V2.1-REPORT.md)
**Target:** Kurangi kompleksitas visual + empty state Review yang cerdas. Bukan redesign, bukan fitur baru, tidak memindahkan section.

---

## Ringkasan

Struktur halaman (Hero → Customer → Dokumen → Rute & Moda → Review → Action) **tidak berubah**. Fokus: hilangkan nested card, ringankan Cabang Asal, dan — prioritas utama — buat panel Review berkembang mengikuti progress user alih-alih menampilkan 4× "Belum dipilih".

**2 file:** `ShipmentResource.php`, `theme.css`. Nol perubahan workflow/validasi/logic.

---

## Before → After per Deliverable (+ alasan UX)

### 1. Nested card "Detail Permintaan" dihapus
- **Before:** `Section` bersarang (card di dalam card) di section Dokumen.
- **After:** Heading kecil "Detail Permintaan" (typography + garis tipis) lalu field langsung. Urutan field **tidak berubah** (request_type → doc_number → priority → requested_at).
- **Alasan:** Card di dalam card menambah berat visual tanpa nilai informasi. Pemisahan area cukup lewat heading + spacing.

### 2. Upload area lebih menonjol
- **Before:** Dropzone ~9.5rem, helper 1 kalimat menyebut OCR.
- **After:** Dropzone 11rem + padding lebih lega; helper dipangkas → "Unggah SPPB atau Delivery Order untuk diproses."
- **Alasan:** Upload adalah titik masuk workflow (pintu OCR). Area lebih besar + helper singkat = lebih mudah dikenali, tanpa penjelasan berlebih.

### 3. Cabang Asal lebih ringan
- **Before:** Aksen garis vertikal (border-left) di kiri.
- **After:** Murni typography — "📍 Cabang Asal" (label kecil), "Jakarta" (tebal), "Kota Asal • JAKARTA". Tanpa border/ornament.
- **Alasan:** Garis vertikal tidak membawa informasi. Menghapusnya menegaskan ini sekadar info sistem, bukan panel.

### 4. Review empty state cerdas ⭐ (prioritas)
- **Before:** Selalu 4 baris "Belum dipilih" walau form kosong total.
- **After (progressive):**
  - **Kosong total** → *"Ringkasan belum tersedia. Lengkapi Customer dan Tujuan terlebih dahulu. Ringkasan akan diperbarui secara otomatis."*
  - **Sebagian terisi** → tampilkan nilai yang sudah ada + "Belum dipilih" hanya untuk yang tersisa (mis. Customer = PT Hasjrat Abadi, Tujuan = Belum dipilih).
  - **Lengkap** → semua nilai + Status 🟢 Draft Baru.
- **Alasan:** Placeholder berulang = noise tanpa makna. Empty state yang mengarahkan memberi tahu operator *langkah berikutnya*; ringkasan lalu tumbuh mengikuti progres — terasa hidup, bukan statis.
- **Diverifikasi 3 state** (kosong/sebagian/penuh) menghasilkan output yang benar.

### 5. Spacing Detail Permintaan
- **After:** `row-gap` 1rem antar field — lebih mudah dipindai, tidak rapat. Jumlah & urutan field tetap.

### 6. Action area lebih lega
- **After:** Jarak (`margin-top` 0.75rem) sebelum checkbox konfirmasi — checkbox & submit tidak menempel ke panel ringkasan. Primary button tetap fokus.

### 7. Border audit
- **Dihapus:** border nested card (via #1), border-left Cabang Asal (#3).
- **Dipertahankan (fungsional):** garis tipis antar-item Review (memisahkan item), border section Filament (mendefinisikan section), garis bawah heading "Detail Permintaan" (memisahkan area).
- **Prinsip:** whitespace sebagai pemisah utama; border hanya bila membantu memahami informasi.

---

## Validation (nyata, read-only — `APP_ENV=production`)

| Uji | Hasil |
|---|---|
| `php -l` | ✅ bersih |
| **Build skema form** | ✅ 6 komponen top-level — struktur utuh, de-card tidak merusak Dokumen; field order tetap |
| **Review — kosong total** | ✅ "Ringkasan belum tersedia. Lengkapi Customer dan Tujuan…" |
| **Review — sebagian** | ✅ `Customer=PT Hasjrat Abadi, Tujuan=Belum dipilih, Moda=Laut, Jenis=Belum dipilih` |
| **Review — penuh** | ✅ semua nilai + Status Draft Baru |

Tidak ada data ditulis/diubah.

---

## Catatan Transparansi

- **Screenshot Before/After:** tidak dilampirkan sebagai gambar — konsisten dengan v2/v2.1: environment `APP_ENV=production` + `APP_URL` remote (`103.55.37.130`); otomasi browser ter-autentikasi ke produksi tidak pantas. Rendering divalidasi programatik (build form + 3 state Review). Screenshot resmi diambil di staging/lokal: `/{panel}/permintaan-pengiriman/create` — buka kosong (lihat empty state Review), lalu isi Customer & Tujuan bertahap (lihat ringkasan tumbuh).
- **Build tema:** `theme.css` perlu `npm run build` agar kelas `.jss-*` baru (`.jss-subheading`, `.jss-review__empty-*`, `.jss-field-grid`, `.jss-confirm`) aktif. Markup HTML sudah benar tanpa build. CSS upload/segmented tetap bergantung pada class internal Filament (`.fi-fo-file-upload-dropzone`, dll.) — periksa ulang bila Filament di-upgrade.

---

## Konfirmasi Constraint

Tidak ada perubahan pada workflow, business logic, Smart Origin, OCR pipeline, validation, database, model, service, permission, event. Struktur & urutan section dipertahankan; field Detail Permintaan tidak dipindah/ditambah/dikurang. Seluruh diff = de-card (Section→heading), teks presentasi, logika-render empty state, dan CSS.
