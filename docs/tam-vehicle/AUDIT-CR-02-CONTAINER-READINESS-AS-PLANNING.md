# Audit CR-02 — Container Readiness as Stuffing Planning

**Status:** AUDIT ONLY — tidak ada kode yang diubah.
**Tanggal:** 23 Juli 2026
**Rujukan:** [`DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md`](DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md), [`SPRINT-CA-01.5-REFACTOR-REPORT.md`](SPRINT-CA-01.5-REFACTOR-REPORT.md), [`SPRINT-UX-02-CONSOLIDATION-REPORT.md`](SPRINT-UX-02-CONSOLIDATION-REPORT.md), [`SPRINT-ST-01-STUFFING-WORKFLOW.md`](SPRINT-ST-01-STUFFING-WORKFLOW.md)

---

## Ringkasan Eksekutif — baca ini dulu

**Temuan utama: hampir seluruh kebutuhan fungsional CR-02 SUDAH ADA di kode — tapi tersebar di dua konstruksi yang tidak pernah disatukan**, dan penyatuannya **sudah pernah didesain lengkap di Sprint UX-02 (23 Juli 2026, sesi yang sama) namun sengaja tidak dibangun**, karena dua blocker yang **masih terbuka sampai sekarang**:

1. Migrasi database masih diblokir (`APP_ENV=production`, belum diverifikasi).
2. **Sebuah kontradiksi domain yang disurfacekan di UX-02 §6 belum pernah dikonfirmasi**: apakah Container Allocation (planning Unit→Container) terjadi **SEBELUM** atau **SETELAH** Inspection. Ini bukan detail kecil — ia menentukan apa artinya "Container Readiness selesai" untuk sprint ini.

Saya menemukan bahwa kode SAAT INI sudah berjalan dengan asumsi opsi yang lebih longgar (Allocation sebelum Inspection, gate hanya menolak unit Return-to-PDC) — dan Sprint ST-01 (Stuffing) dibangun di atas asumsi itu tanpa masalah. Tapi **belum pernah ada konfirmasi eksplisit** dari Anda atas pilihan itu. CR-02 tidak bisa selesai didesain tanpa keputusan ini dikunci lebih dulu — lihat §4.

---

## 1. Bagian mana yang SUDAH sesuai dengan keputusan CR-02?

| Kebutuhan CR-02 | Sudah ada di | Bukti |
|---|---|---|
| Service (Rack/Regular) adalah keputusan eksplisit, bukan keputusan operator Stuffing | `ContainerAllocationType` enum + `configureContainerAction()` di `ContainerAllocationWorkspace` | Type di-set FC saat planning, disimpan di `containers.type`, dibaca (read-only) oleh `StuffingWorkspace` — Stuffing tidak pernah menulis/memilih type. |
| Container dipilih sebelum unit ditempatkan | `Container::resolveForSession()` + `assignToContainerAction()` | Container harus sudah terdaftar di Readiness (`container_numbers`) dan dilengkapi type/capacity sebelum bisa menerima unit (`guardContainerConfigured()`). |
| Unit → Container adalah keputusan planning, tersimpan | `ContainerAllocationService::assign/move/remove()` | Satu-satunya pintu tulis ke `units.container_id`. Stuffing (`StuffingService`) **tidak punya method untuk mengubah ini** — hanya membaca. |
| "Apakah container siap?" adalah keputusan eksplisit | `Container.is_ready_for_stuffing` + `markContainerReadyAction()`/`unmarkContainerReadyAction()` | Persis field Ready/Belum Ready yang diminta CR-02 poin 4. |
| Stuffing TIDAK boleh planning ulang | `StuffingService::guardUnitReadyToStuff()` | Menolak unit dengan `container_id === null` secara eksplisit: *"Stuffing tidak membuat rencana baru."* Tidak ada method assign/move/remove di `StuffingService`. |
| Stuffing hanya: buka container, lihat unit, tandai, selesai | `StuffingWorkspace` + `markUnitStuffedAction()`/`unmarkUnitStuffedAction()` | Diverifikasi ulang barusan (grep internal ST-01): nol logic pemilihan/pemindahan unit di seluruh domain Stuffing. |
| Container Readiness (harian) TIDAK melakukan scan/konfirmasi/progress/waktu stuffing | `ContainerReadinessSessionResource` | Form hanya: tanggal, jumlah unit, kebutuhan/tersedia container, catatan, daftar nomor container. Tidak ada field waktu/progress stuffing. |

**Kesimpulan Q1:** Kontrak Stuffing (execution-only, menerima hasil planning) **sudah terpenuhi 100%** — ini sudah benar sejak CA-01/CA-01.5 dan tidak disentuh ST-01. Bagian yang "sudah sesuai" justru adalah bagian terbesar dari brief CR-02.

---

## 2. Bagian mana yang masih berperan sebagai *readiness* padahal seharusnya *planning*?

Ini pertanyaan yang perlu dijawab hati-hati, karena jawabannya **bukan** "readiness melakukan planning yang salah" — melainkan **"readiness (secara nama/entry-point) TIDAK melakukan planning sama sekali, sementara planning yang benar sudah ada tapi hidup di luar konsep Readiness"**:

### 2a. `ContainerReadinessSession` (modul yang secara harfiah bernama "Container Readiness") murni **agregat harian**, bukan planning

- Satu baris **per hari** (bukan per Shipment/SPPB): `unit_count`, `container_need`, `container_available`, `gap`.
- Hanya menjawab **"apakah jumlah container cukup?"** (`summary_sufficient = available >= need`) — sebuah cek kecukupan angka, bukan keputusan Service/Container/Unit apa pun.
- `container_numbers` hanyalah **daftar string nomor container** — tidak ada Service, tidak ada kapasitas, tidak ada keterkaitan ke Shipment atau Unit tertentu.
- **Tidak punya konsep Shipment/SPPB sama sekali** — field `shipment_id` tidak ada di model ini.

Jadi modul yang secara nama paling cocok disebut "Container Readiness" **belum pernah menjadi tempat planning** — ia tetap alat cek kecukupan supply, persis sesuai nama lamanya, bukan sesuai peran baru yang diminta CR-02.

### 2b. Planning yang sesungguhnya (Service, Container, Unit→Container) sudah ada — tapi di modul **terpisah dan tersembunyi**, bukan bagian dari "Container Readiness"

- `ContainerAllocationWorkspace` — halaman **terpisah**, `shouldRegisterNavigation = false`, dicapai lewat Monitoring Operasional, bukan bagian dari alur/form Container Readiness.
- Sprint UX-02 (di sesi yang sama, 23 Juli 2026) **sudah mengidentifikasi persis masalah ini** dan mendesain solusinya: alur 4-langkah tunggal (*Review Requirement → Input Container → Tipe & Kapasitas → Alokasi Unit→Container*) yang akan menyatukan kedua modul secara UX. **Tapi wizard ini sengaja tidak pernah dibangun** — hanya spesifikasi (lihat `SPRINT-UX-02-CONSOLIDATION-REPORT.md` §3, §8, §10).

### 2c. Planning saat ini **tidak dikelompokkan per Shipment/SPPB** — gap nyata terhadap contoh Output CR-02

Domain Freeze Container Allocation (§2, dikutip di docblock `ContainerAllocationWorkspace`) menyatakan eksplisit:

> *"Cara kerja FC bukan per-container, bukan per-shipment — melainkan lintas seluruh unit hari itu sekaligus."*

Ini adalah **keputusan desain yang disengaja saat itu**, tapi **bertentangan langsung** dengan format Output yang diminta CR-02 sekarang:

```
Shipment Toyota
  Service: Regular
  Container: REG001
  Unit: Avanza, Brio, Agya
```

Workspace saat ini menampilkan **pool Unit lintas-shipment** (kiri) berdampingan dengan **daftar Container** (kanan) — tidak ada satu pun tampilan yang mengelompokkan "per Shipment: service + container + unit". Data untuk membentuk tampilan itu **sudah tersedia** (via `Container::shipment()` yang ditambahkan di ST-01, dan `Unit.shipment_id`), tapi **belum pernah dirender sebagai satu kesatuan**.

### 2d. "Container Readiness selesai" yang dipakai ST-01 sebagai precondition Stuffing **belum benar-benar berarti "planning selesai"**

Temuan tambahan (menyentuh pekerjaan saya sendiri di ST-01, perlu saya sampaikan jujur): `StuffingService::checkPreconditions()` memeriksa dua hal yang **berbeda level** dari yang CR-02 maksud:

- `container_readiness_done` → hanya cek `ContainerReadinessSession.summary_sufficient` (cek kecukupan ANGKA harian, §2a di atas) — **bukan** "apakah seluruh unit shipment ini sudah selesai di-planning ke container".
- `container_available` → hanya cek **minimal SATU** unit shipment ini sudah masuk container Ready (`->exists()`) — bukan "SELURUH unit shipment ini sudah punya container".

Artinya: sebuah Shipment **bisa lolos precondition Stuffing hari ini** walau baru 1 dari 5 unitnya sudah di-planning ke container — karena precondition-nya hanya mengecek "ada minimal satu", bukan "seluruh rencana untuk shipment ini sudah tuntas". Ini **konsisten dengan definisi lama** ("Container tersedia" secara harfiah), tapi **tidak konsisten** dengan visi CR-02 bahwa Container Readiness harus menghasilkan *Stuffing Plan* yang lengkap sebelum eksekusi dimulai.

**Kesimpulan Q2:** Tidak ada bagian yang salah arah (over-reach) — sebaliknya, modul yang bernama "Container Readiness" **kurang** (under-reach): ia belum menjalankan peran planning yang diminta CR-02. Planning yang benar sudah ada, tapi terputus secara organisasi (modul terpisah, tidak per-Shipment, dan precondition-nya di ST-01 masih longgar terhadap level Shipment).

---

## 3. Apakah sudah ada tempat untuk menentukan Service / Container / Unit→Container?

**Ya — tapi bukan di "Container Readiness".** Tempatnya adalah `ContainerAllocationWorkspace` + `ContainerAllocationService`, yang sudah menjawab ketiga hal itu (lihat tabel §1). Yang **belum ada**:

1. **Satu entry-point/nama tunggal** yang membuat FC melihat ini semua sebagai "Container Readiness" (bukan dua alat terpisah). Sudah didesain di UX-02, belum dibangun.
2. **Tampilan yang dikelompokkan per Shipment/SPPB** sesuai contoh Output CR-02 — saat ini dikelompokkan per Container/pool Unit lintas-shipment.
3. **Definisi "Planning selesai" per Shipment** yang tegas dan dipakai sebagai gate Stuffing (bukan hanya cek kecukupan angka harian + cek "minimal satu unit").
4. **Keputusan atas kontradiksi UX-02 §6** (Allocation sebelum/sesudah Inspection) — ini pondasi yang menentukan kapan "planning selesai" itu boleh dianggap final, dan CR-02 tidak bisa dirancang tuntas tanpa ini terkunci.

---

## 4. Kontradiksi Terbuka yang Menghambat CR-02 (wajib dibaca)

Sprint UX-02 (di sesi yang sama) menyurfacekan pertanyaan berikut dan **belum pernah menerima jawaban eksplisit** dari Anda:

> Business Process baru menaruh Alokasi (planning Unit→Container) **SEBELUM Inspection**, membalik urutan yang sebelumnya dibekukan di Domain Freeze ("Container Allocation adalah proses yang terjadi SETELAH Inspection", "hanya unit yang lolos Inspeksi boleh masuk pool alokasi"). Dua opsi diajukan:
> 1. **Ordering memang berubah** — alokasi = planning provisional pagi hari, Inspeksi mengonfirmasi belakangan, unit yang gagal dikeluarkan dari plan.
> 2. **Inspeksi tetap prasyarat** — Alokasi harus tetap setelah Inspeksi.

**Status saat ini:** kode SUDAH berjalan dengan opsi 1 (`ContainerAllocationService::isUnitEligible()` hanya menolak `return_to_pdc`, tidak lagi mewajibkan `handover_depot` lolos), dan ST-01 dibangun di atasnya tanpa isu. Tapi ini adalah **kondisi de-facto dari kode**, bukan keputusan yang tercatat sudah Anda konfirmasi secara eksplisit di percakapan ini.

**Kenapa ini relevan untuk CR-02:** jika opsi 1 benar, maka "Planning selesai" (dan karenanya "Container Readiness selesai" versi baru) **bisa berubah lagi** setelah Inspeksi berjalan (unit gagal inspeksi harus dikeluarkan dari plan — siapa yang melakukan itu? Allocation, lagi?). Jika opsi 2 benar, maka urutan hari kerja FC berbeda total dari yang sekarang berjalan. **Usulan arsitektur di §5 saya susun mengasumsikan opsi 1 (kondisi kode saat ini)** — mohon konfirmasi eksplisit sebelum implementasi, karena ini menentukan bentuk gate "Planning selesai".

---

## 5. Usulan Arsitektur (belum diimplementasikan)

Prinsip: **jangan bangun ulang** apa yang sudah benar (Service/Container/Unit→Container logic di `ContainerAllocationService` sudah solid dan diuji-alur sejak CA-01.5) — **satukan secara organisasi & tambahkan lapisan "per Shipment"** yang saat ini hilang.

1. **Reframing, bukan rewrite:** `ContainerAllocationWorkspace` secara konsep MENJADI langkah planning dari "Container Readiness" (persis seperti yang sudah dispesifikasikan UX-02 §3/§8) — tanpa membangun ulang service/model. Entry-point disatukan; logic tetap.
2. **Tambahan baru — tampilan "Stuffing Plan" per Shipment**: satu view/query baru (read-only, mirip pola `shipmentStuffingSummary()` di `StuffingService`) yang mengelompokkan Container+Unit **per Shipment**, persis format Output CR-02. Ini murni tampilan derived — tidak perlu kolom baru, karena `Container::shipment()` (ST-01) dan `Unit.shipment_id` sudah cukup.
3. **Perketat definisi "Planning selesai" per Shipment**, dipakai untuk menggantikan/melengkapi precondition `container_readiness_done` + `container_available` di `StuffingService::checkPreconditions()` — dari "minimal satu unit di container Ready" menjadi "seluruh unit shipment ini (yang eligible) sudah punya `container_id`, dan seluruh container yang dipakainya sudah `is_ready_for_stuffing`". Ini mengubah kontrak precondition ST-01 sedikit — perlu disetujui eksplisit karena menyentuh sprint yang baru selesai.
4. **Tidak menyentuh:** `ContainerReadinessSession` (tetap sebagai cek kecukupan angka harian — perannya sah dan berbeda level dari planning per-Shipment, dua-duanya tetap dibutuhkan berdampingan), `StuffingService`/`StuffingWorkspace` (tetap execution-only, hanya precondition-nya yang diperketat di poin 3).
5. **Prasyarat sebelum implementasi:** keputusan §4 dikunci, dan status blocker migrasi (masih sama seperti seluruh sprint TAM Vehicle sebelumnya).

---

## Menunggu Keputusan Anda

Sesuai instruksi sprint ini, **belum ada kode yang diubah**. Yang saya butuhkan sebelum lanjut ke implementasi:

1. Konfirmasi eksplisit atas kontradiksi §4 (opsi 1 atau opsi 2) — ini fondasi.
2. Persetujuan atas arah usulan arsitektur §5 (reframing + tampilan per-Shipment + pengetatan precondition ST-01), atau arahan lain jika Anda melihatnya berbeda.
