# Domain Freeze — Container Allocation (FINAL)

**Status:** 🧊 **DOMAIN FREEZE** — Container Allocation
**Tanggal:** 23 Juli 2026
**Menggantikan:** draf sebelumnya di file ini (versi eksploratif dengan tiga-tingkat lifecycle). Dokumen ini adalah versi final, disederhanakan sesuai konfirmasi operasional.
**Prasyarat (tidak dibahas ulang — semua FROZEN):** Requirement Planning, Morning Briefing, Container Readiness, Inspection.
**Sifat:** Desain operasional. Bukan software, bukan database, bukan UI.

---

## 1. Objective

**Container Allocation menjawab TEPAT SATU pertanyaan:**

> **"Unit ini masuk ke Container yang mana?"**

Output-nya adalah **pemetaan Unit → Container** yang menjadi **panduan resmi tim MP saat Stuffing**. Tidak lebih dari itu — tidak menghitung kebutuhan, tidak menilai kelayakan unit, tidak menyatakan ketersediaan container, tidak melakukan pemuatan fisik. Semua itu domain lain yang sudah final.

---

## 2. Entity yang Dikerjakan FC

**Unit adalah satu-satunya entity yang dikerjakan.** Container adalah **wadah tujuan** (tempat unit diarahkan), bukan sesuatu yang "dikerjakan" FC.

**Cara kerja FC bukan per-container, bukan per-shipment — melainkan lintas seluruh unit hari itu sekaligus.** FC melihat satu kumpulan unit (semua unit yang sudah lolos Inspection dan menunggu dialokasikan hari itu), lalu menentukan tujuan container untuk **masing-masing**, sampai seluruh unit punya tempat. Ini sesuai gambaran Anda:

```
Container Rack 01     → Unit A, Unit B, Unit C
Container Regular 01  → Unit D, Unit E
Container Regular 02  → Unit F
```

Unit adalah subjeknya; container tabel tujuannya.

---

## 3. Informasi yang Perlu Ditampilkan

**Sisi Unit** (yang menunggu dialokasikan — input dari Inspection, apa adanya, tidak dinilai ulang):
- No. Unit & Model
- Tujuan (kota/voyage)
- Tipe kebutuhan (Rack / Regular) — **diambil dari Requirement, tidak dihitung ulang di sini**

**Sisi Container** (yang tersedia — input dari Container Readiness, apa adanya, tidak dipertanyakan ulang):
- Identitas & tipe container (Rack / Regular)
- Kapasitas total & sisa slot saat ini

Kedua sisi harus tampil **bersamaan** — FC menempatkan unit sambil melihat sisa kapasitas container secara langsung (begitu satu unit masuk, sisa slot ter-update).

---

## 4. Workflow FC

1. FC membuka pekerjaan hari itu → melihat **seluruh unit siap alokasi** berdampingan dengan **seluruh container tersedia** beserta sisa kapasitasnya.
2. FC menempatkan unit satu per satu (atau mengisi container satu per satu — dua arah kerja yang sama-sama valid) sampai setiap unit punya container tujuan.
3. Selama proses berlangsung, FC bebas memindahkan atau mengeluarkan unit dari container jika rencana perlu berubah (rencana masih cair sampai dinyatakan siap).
4. Setelah seluruh unit yang direncanakan hari itu sudah punya container, FC menandai container tersebut **siap** — pemetaan ini menjadi panduan resmi tim MP.
5. Tim MP menjalankan stuffing fisik mengikuti pemetaan ini — **di luar cakupan Container Allocation** (domain Stuffing berikutnya).

---

## 5. Validasi Action

| Action | Validasi |
|---|---|
| **Masukkan ke Container** | **Perlu.** Aksi inti — unit dari kumpulan "belum masuk" ditempatkan ke satu container. |
| **Pindahkan Container** | **Perlu**, sebagai **satu gerakan langsung** (bukan keluarkan-lalu-masukkan dua langkah terpisah) — supaya tidak ada momen unit "tanpa container" yang membingungkan. |
| **Keluarkan dari Container** | **Perlu.** Untuk unit yang perlu keluar dari rencana tanpa langsung berpindah ke container lain. |
| **Tandai Siap Stuffing** | **Perlu, satu penajaman granularitas:** ditandai **per Container**, bukan per unit individual. Alasannya operasional murni — tim MP mengerjakan satu container sekaligus (memuat semua isinya), jadi yang perlu dinyatakan "siap" adalah **container beserta seluruh isinya**, bukan status unit satu-satu. FC menandai container siap ketika seluruh unit di dalamnya sudah pasti. Tanda ini **harus bisa dibatalkan** oleh FC (bukan aksi tersendiri — cukup menandai ulang "belum siap") selama stuffing fisik belum benar-benar dimulai. |

**Empat action ini sudah cukup.** Tidak perlu ditambah — action lain (buat container, ubah requirement, nilai kelayakan unit) memang sengaja tidak ada di sini karena bukan tanggung jawab Container Allocation (lihat §7).

---

## 6. Validasi Status Unit

**Lifecycle yang diusulkan sudah tepat, dan sudah benar ukurannya untuk Unit** (berbeda dari draf sebelumnya yang sempat mencampur status rollup ke level unit):

```
Belum Masuk Container
      ↓ (Masukkan ke Container)
Sudah Masuk Container         ← masih bisa Pindahkan / Keluarkan
      ↓ (Container ditandai siap)
Siap Stuffing                 ← batas akhir tanggung jawab Container Allocation
```

Tiga keadaan ini **cukup dan tidak ambigu** untuk satu unit — tidak ada keadaan "sebagian" yang perlu disisipkan, karena satu unit fisik memang hanya bisa berada di salah satu dari tiga keadaan ini pada satu waktu.

**Batas cakupan:** lifecycle Container Allocation **berhenti** di "Siap Stuffing". Apa yang terjadi setelahnya (unit benar-benar dimuat, container disegel) adalah **status milik domain Stuffing**, bukan diperpanjang di sini.

---

## 7. Exception — Pemisahan Domain yang Tegas

### Milik Container Allocation (diselesaikan sendiri, pakai action yang sudah ada)

| Situasi | Penyelesaian |
|---|---|
| Container yang dituju sudah penuh, tapi container lain (dalam daftar yang sama) masih ada slot | Pindahkan unit ke container lain — pakai *Pindahkan Container* |
| Unit perlu keluar dari rencana (dibatalkan operasional, rencana disusun ulang) | *Keluarkan dari Container* |
| Perlu menyusun ulang pengelompokan sebelum ditandai siap | Kombinasi Masukkan/Pindahkan/Keluarkan — bukan eksepsi baru |

### BUKAN milik Container Allocation (harus dieskalasi ke domain lain)

| Situasi | Domain yang menangani | Kenapa bukan Allocation |
|---|---|---|
| Seluruh container dalam daftar penuh, tidak ada yang bisa menampung sisa unit | **Container Readiness / Requirement** | Ini kekurangan pasokan, bukan soal penempatan. Allocation tidak boleh "membuat container baru" untuk menutupi ini. |
| Jumlah kebutuhan Rack/Regular ternyata salah dari awal | **Requirement Planning** | Allocation memakai angka kebutuhan apa adanya, tidak menghitung ulang. |
| Unit ternyata tidak layak (rusak/bermasalah) — termasuk bila baru ketahuan saat proses alokasi berlangsung | **Inspection** | Kelayakan unit murni keputusan Inspection. Allocation hanya *mengeluarkan* unit dari rencana setelah Inspection menyatakan tidak layak — Allocation tidak pernah menilai layak/tidaknya sendiri. |
| Proses memuat unit ke container secara fisik, penyegelan | **Stuffing** | Allocation hanya menghasilkan rencana/peta; pelaksanaan fisiknya domain berikutnya. |

**Prinsip pemisah:** jika penyelesaiannya adalah *"pindahkan/keluarkan unit di antara container yang sudah ada"* → itu Allocation. Jika penyelesaiannya menyentuh *jumlah/jenis container, kelayakan unit, atau tindakan fisik* → itu domain lain, Allocation hanya **melapor**, tidak menyelesaikan sendiri.

---

## Kesimpulan

Container Allocation dibekukan sebagai domain yang **sengaja sempit**: satu entity (Unit), satu pertanyaan ("masuk container mana"), empat action (Masukkan/Pindahkan/Keluarkan/Tandai Siap), tiga status (Belum Masuk → Sudah Masuk → Siap Stuffing). Tidak tumpang tindih dengan Requirement, Readiness, Inspection, maupun Stuffing — batasnya sudah eksplisit di §7. Siap dijadikan dasar implementasi.
