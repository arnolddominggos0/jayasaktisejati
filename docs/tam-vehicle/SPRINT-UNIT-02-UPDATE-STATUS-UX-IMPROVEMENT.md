# Sprint UNIT-02 — UX Improvement for Unit Update Status

**Status:** IMPLEMENTED & tervalidasi dengan data nyata.
**Tanggal:** 24 Juli 2026

---

## 1. Perbaikan UX Modal Update Status

Placeholder `inspection_notice` (dibuat di Sprint UNIT-01) diganti total tampilannya — dari satu baris teks polos yang terbaca sebagai pesan error, menjadi kotak informasi bergaya "warning ringan" (border/background amber, konsisten dengan pola visual yang sudah dipakai di halaman lain seperti `StuffingWorkspace`) berisi:

1. Judul: **"Pemeriksaan Diperlukan"** — bukan lagi menyebut kata "belum selesai" sebagai judul.
2. Kalimat panduan operasional, dengan nama status berikutnya **disisipkan dinamis** (bukan hardcode "Penjemputan" — akan menyesuaikan status apa pun: Handover, Stuffing, dst.).
3. Kalimat kedua yang mengarahkan ke tombol **Inspeksi** pada baris unit.
4. Tautan langsung **"Buka Inspeksi Unit"** menuju `InspectUnitPage` untuk unit yang sedang dibuka — memakai `InspectUnitPage::getUrl()`, mekanisme routing yang **sudah ada** (identik dengan yang dipakai action "Inspeksi" di tabel) — tidak ada route baru.

Urutan field dalam modal **tidak berubah** dari UNIT-01, dan sudah sesuai Modal Layout yang diminta: Unit → Status Berikutnya → Informasi Pemeriksaan (hanya bila diperlukan) → Catatan → Action Button.

Notifikasi peringatan di `action()` (muncul jika user memaksa klik Kirim) juga diperbarui teksnya agar konsisten dengan nada baru — judul dan isi berubah, **kondisi pemicu dan `->warning()`-nya tidak berubah**.

---

## 2. Copywriting Baru

| | Sebelum | Sesudah |
|---|---|---|
| Judul notice di form | *(tidak ada judul, hanya satu kalimat)* | **Pemeriksaan Diperlukan** |
| Isi notice di form | "Inspeksi unit ini belum selesai. Selesaikan inspeksi unit terlebih dahulu sebelum melanjutkan status." | "Sebelum melanjutkan ke status **{Status Berikutnya}**, unit ini harus menyelesaikan pemeriksaan pada tahap ini. Gunakan tombol Inspeksi pada baris unit ini, atau klik tautan berikut." + tautan "Buka Inspeksi Unit" |
| Judul notifikasi (saat submit ditolak) | "Inspeksi unit belum selesai" | "Pemeriksaan Diperlukan" |
| Isi notifikasi | "Selesaikan inspeksi unit ini sebelum melanjutkan status." | "Selesaikan pemeriksaan unit pada tahap ini melalui menu Inspeksi sebelum melanjutkan ke status berikutnya." |

Kata "belum selesai" sebagai pembuka kalimat (kesan error) dihindari — diganti kalimat yang menjelaskan **apa yang harus dilakukan** (pola panduan operasional, sesuai instruksi sprint).

---

## 3. File yang Diubah

| File | Perubahan |
|---|---|
| `app/Filament/FC/Pages/OperationalTasks.php` | Satu-satunya file yang diubah. `Placeholder::make('inspection_notice')->content(...)` diganti dari string statis menjadi closure yang membangun kotak info amber + tautan Inspeksi. Teks `Notification` pada `action()` diperbarui. Kondisi `->visible()` **tidak diubah** — tetap memanggil `unitInspectionIncomplete()` yang persis sama dari UNIT-01. |

**Tidak ada file lain yang diubah. Tidak ada migration.**

---

## 4. Screenshot Before / After

**Catatan jujur (sama seperti sprint UI sebelumnya):** sesi ini tidak memiliki tool browser aktif untuk mengambil screenshot piksel sungguhan. Sebagai gantinya, berikut representasi tekstual dari HTML yang **benar-benar dihasilkan** closure baru terhadap data nyata (shipment #1, unit #1, status berikutnya = Penjemputan):

**Sebelum:**
```
Status Berikutnya
Penjemputan

Inspeksi unit ini belum selesai.

Catatan
[___________]
        [Kirim]
```

**Sesudah** (HTML nyata, diverifikasi lewat pemanggilan langsung closure terhadap unit #1):
```html
<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
  <p class="font-semibold text-amber-800 dark:text-amber-300">Pemeriksaan Diperlukan</p>
  <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
    Sebelum melanjutkan ke status Penjemputan, unit ini harus menyelesaikan pemeriksaan pada tahap ini.
    Gunakan tombol Inspeksi pada baris unit ini, atau klik tautan berikut.
  </p>
  <a href="http://.../fc/operational-inspections/1/1" target="_blank"
     class="mt-2 inline-block text-sm font-semibold text-amber-800 underline dark:text-amber-300">
    Buka Inspeksi Unit
  </a>
</div>
```
Representasi visual kasar:
```
Status Berikutnya
Penjemputan

┌────────────────────────────────────────────────────┐
│ Pemeriksaan Diperlukan                              │  ← kotak amber, bukan teks polos/merah
│ Sebelum melanjutkan ke status Penjemputan, unit ini │
│ harus menyelesaikan pemeriksaan pada tahap ini.     │
│ Gunakan tombol Inspeksi pada baris unit ini, atau   │
│ klik tautan berikut.                                │
│ Buka Inspeksi Unit →                                │  ← tautan langsung, bisa diklik
└────────────────────────────────────────────────────┘

Catatan
[___________]
        [Kirim]
```
Jika screenshot sungguhan tetap dibutuhkan, saya bisa mengambilnya begitu ada akses browser (mis. Chrome MCP tersambung).

---

## 5. Ringkasan Alasan UX

- **Judul + dua kalimat, bukan satu kalimat error** — memisahkan "apa yang terjadi" (Pemeriksaan Diperlukan) dari "apa yang harus dilakukan" (buka menu Inspeksi), sesuai instruksi "pesan harus terasa sebagai panduan operasional, bukan error."
- **Status berikutnya disebut eksplisit dan dinamis** — operator langsung tahu pemeriksaan itu untuk tahap APA, tanpa perlu menebak, dan teksnya tetap benar untuk status apa pun (tidak hardcode "Penjemputan").
- **Tautan langsung ke Inspeksi unit yang sama** — memenuhi permintaan "tampilkan tombol/action yang mengarahkan ke Inspection Unit yang sedang dipilih," sekaligus kalimatnya secara eksplisit menyebut "tombol Inspeksi pada baris unit ini" sebagai jalur alternatif — memenuhi kedua opsi yang diberikan brief sekaligus, bukan memilih salah satu.
- **Warna amber (bukan merah/danger)** — dipilih karena instruksi eksplisit "Information atau Warning ringan, bukan error besar." Warna ini juga konsisten dengan kotak peringatan serupa yang sudah dipakai di `StuffingWorkspace` (briefing belum ready), menjaga bahasa visual yang sama di seluruh aplikasi.
- **Tidak mengubah kondisi `->visible()`** — notice ini tetap hanya tampil ketika `unitInspectionIncomplete()` (logic dari UNIT-01, tidak disentuh) bernilai true; begitu inspeksi unit ini difinalisasi, kotak otomatis hilang — dikonfirmasi ulang lewat pengujian (draft → tampil, finalized → hilang).

---

## 6. Konfirmasi: Business Rule Tidak Berubah

| Yang dilarang diubah | Bukti tidak berubah |
|---|---|
| Business rule / validasi | `unitInspectionIncomplete()` — isi method **byte-for-byte sama** dengan UNIT-01 (dikonfirmasi lewat pencarian string persis di source), dipanggil dengan argumen yang sama di `visible()` maupun `action()`. |
| Transition guard | `Shipment::runTransitionGuards()`/`appendTrack()` — file `Shipment.php` tidak disentuh sprint ini. |
| Shipment | `Shipment.php` tidak disentuh. |
| Backend inspection | `UnitInspection.php`, `InspectionDraftAutoCreate.php`, `InspectUnitPage.php` tidak disentuh — hanya **memanggil** `InspectUnitPage::getUrl()` yang sudah ada, tidak mengubah isinya. |
| Migration | Tidak ada migration baru — `migrate:status` tidak bertambah. |
| Observer / Notification / Authorization | Tidak disentuh — `ShipmentOwnership::canEdit()` di `visible()` action `updateTrack` tidak diubah satu karakter pun. |

**Pengujian pembuktian:** dijalankan langsung terhadap data nyata — unit dengan inspeksi draft (belum final) tetap menghasilkan `incomplete=true` (notice tetap tampil), dan setelah inspeksi difinalisasi (submitted+signed) hasilnya `incomplete=false` (notice hilang) — perilaku identik dengan UNIT-01, hanya cara menampilkannya yang berubah.

---

## 7. Konfirmasi: Tidak Ada Komentar Baru

Seluruh kode yang diubah (closure `content()` pada `inspection_notice`, teks `Notification` di `action()`) ditulis **tanpa satu baris komentar pun** — dikonfirmasi lewat pembacaan ulang penuh region yang diubah setelah implementasi selesai. Tidak ada komentar jenis apa pun (termasuk yang mengindikasikan proses AI/refactor) ditambahkan di file ini maupun file lain.
