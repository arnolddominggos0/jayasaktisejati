# Product & UX Architecture Review — Pelacakan & Monitoring

**Status:** DRAFT — untuk diskusi & keputusan product/engineering leadership
**Tanggal:** 19 Juli 2026
**Lingkup:** Product Architecture & UX Architecture. Tidak berisi kode Laravel, Filament Resource, atau wireframe.
**Dibaca bersama:** [`SPRINT-5-TECHNICAL-ARCHITECTURE.md`](SPRINT-5-TECHNICAL-ARCHITECTURE.md), [`ADR-009-domain-constraint-monitoring-v1.md`](ADR-009-domain-constraint-monitoring-v1.md)

---

## Daftar Isi

1. [Executive Summary](#1-executive-summary)
2. [Current Problems](#2-current-problems)
3. [Domain Analysis](#3-domain-analysis)
4. [User Analysis](#4-user-analysis)
5. [Architecture Proposal](#5-architecture-proposal)
6. [Information Hierarchy](#6-information-hierarchy)
7. [Navigation Proposal](#7-navigation-proposal)
8. [Shipment Monitoring Concept](#8-shipment-monitoring-concept)
9. [Unit Monitoring Concept](#9-unit-monitoring-concept)
10. [Future Scalability](#10-future-scalability)
11. [Final Recommendation](#11-final-recommendation)
12. [Lampiran: Referensi Kode](#12-lampiran-referensi-kode)

---

## 1. Executive Summary

Modul "Pelacakan & Monitoring" hari ini sebenarnya adalah **dua setengah sistem yang memakai satu nama**:

- `ShipmentTrackingResource` — daftar shipment dengan progress stepper (grain: Shipment).
- Halaman index-nya sendiri (`WorkspaceShell`) ternyata merender workspace exception-band yang grain-nya adalah **Unit (kendaraan)**, bukan Shipment — logika yang aslinya ditulis sebagai halaman terpisah (`PelacakanMonitoring.php`, kini tidak lagi terdaftar di navigasi).
- `MonitoringKapalTam` — command center voyage/kapal, hidup di grup navigasi lain ("Manajemen Kapal"), dengan model data yang sama sekali berbeda (milestone kapal, bukan track shipment).

Rekayasa di baliknya **berkualitas tinggi** — `SPRINT-5-TECHNICAL-ARCHITECTURE.md` menunjukkan pemisahan layer yang disiplin (Presentation → Application → Domain Service → Query Layer → Model), DTO immutable, forbidden-knowledge matrix, dan strategi N+1 yang matang. **Ini bukan masalah kualitas kode.**

Masalahnya ada di satu keputusan domain yang mendasari semuanya: **atom monitoring dipilih sebagai Unit (kendaraan), bukan Shipment.** Keputusan ini kemudian baru saja diperkuat lewat `ADR-009` (diterima 27 Juni 2026 — hanya ±3 minggu sebelum tanggal dokumen ini), yang secara eksplisit **menghapus** dukungan mode Land dan mengunci workspace ke domain sea-freight/TAM/vehicle, dengan alasan "tidak pernah ada shipment Land yang dibuka lewat workspace ini."

Pada saat yang sama, domain model sebenarnya **sudah siap sebagian**: `Shipment` sudah punya kolom `cargo_type` (`Vehicle` | `General`) dan field agregat general cargo (`packages_total`, `cbm_total`, `weight_total`). Bahkan ditemukan potongan kode yang secara eksplisit mencoba melakukan percabangan berdasarkan `cargo_type` di gate lifecycle shipment ([`Shipment.php:735-757`](../../app/Models/Shipment.php)) — **namun kodenya di-comment-out**, tidak pernah diaktifkan. Artinya kebutuhan ini sudah pernah disadari tim, tapi belum pernah dituntaskan.

**Rekomendasi inti dokumen ini:** jadikan **Shipment** sebagai primary object monitoring secara konsisten di semua permukaan, perkenalkan konsep **Cargo Line** (polymorphic: Unit hari ini, General Cargo/Container/Project Cargo nanti) sebagai secondary object, dan ubah exception engine dari "6 tipe tetap" menjadi **rule set yang terdaftar per kombinasi CargoType × Mode**. Ini adalah evolusi *additive* di atas arsitektur Sprint 5 yang sudah ada — bukan rewrite — dan sejalan dengan disiplin "extension protocol" yang sudah dicontohkan oleh `ADR-009` sendiri.

---

## 2. Current Problems

### 2.1 Unit adalah atom monitoring, bukan Shipment

`MonitoringRowData` (view model utama workspace) punya `unit_reg_no`, `unit_chassis_no`, `unit_model_no`, `unit_color` sebagai field inti — bukan field opsional di dalam sub-objek "cargo detail". Grain default (`group_mode = 'flat'`) adalah **satu baris per Unit**. Query builder-nya bahkan bernama `UnitMonitoringQueryBuilder`.

**Dampak:** Shipment General Cargo (tanpa baris `units`) akan tampil kosong atau berisi baris yang secara struktural tidak masuk akal di workspace utama. Belum ada bentuk baris untuk "jumlah colli" atau "berat total" sama sekali.

### 2.2 Kosakata exception adalah kosakata QA kendaraan, bukan kosakata universal

Enam tipe exception yang dikunci (`ExceptionEvaluator`): Delay, **NG** (`finding_type = major_damage`, istilah inspeksi kondisi fisik kendaraan), Hold, Demurrage, Missing Voyage, **PDI Pending** (*Pre-Delivery Inspection* — istilah baku industri otomotif).

**Dampak:** Container shipment tidak punya "PDI" — yang relevan adalah keutuhan segel atau suhu reefer. General Cargo tidak punya "kondisi unit" — yang relevan adalah rekonsiliasi jumlah colli atau klaim kerusakan/kekurangan.

### 2.3 ADR-009 baru saja mempersempit domain, tiga minggu sebelum sprint ini

Ini fakta yang tidak boleh diabaikan: `ADR-009` (27 Juni 2026) **menghapus** parameter `$mode` dari `AgeCalculator`, mengganti `TrackStatus::orderForMode($mode)` dengan `TrackStatus::orderSea()` langsung di `StageResolver`, dan **menghilangkan** pilihan "Land" dari form. Alasannya valid untuk konteks saat itu (dead code, tidak ada traffic Land nyata) — tapi ini berarti arah "mempersempit ke satu domain" adalah keputusan *aktif dan terbaru*, tepat berlawanan arah dengan permintaan sprint ini ("dukung Vehicle, General Cargo, Container, Project Cargo sekaligus"). Proposal apa pun di dokumen ini harus secara eksplisit berhadapan dengan ADR-009, bukan mengabaikannya (lihat §5.4).

### 2.4 Indikasi workspace duplikat

`app/Filament/Pages/PelacakanMonitoring.php` (`shouldRegisterNavigation = false`) berisi state, form schema, dan pemanggilan service (`MonitoringQueryService`, dst.) yang **hampir identik** dengan `ShipmentTrackingResource\Pages\WorkspaceShell` — yang merupakan halaman index resmi saat ini dan memakai title yang sama persis: **"Pelacakan & Monitoring"**. Pola ini — dua kelas, satu nama, tanggung jawab tumpang tindih — adalah pelanggaran langsung prinsip *No Duplicate Workspace* / *Single Source of Truth*. Kemunculan nama **"Monitoring Unit Harian"** sebagai item navigasi terpisah di brief sprint ini (lihat §7) kemungkinan besar adalah gejala kebingungan yang sama dari sisi bisnis — dua nama untuk sesuatu yang secara konsep sebenarnya satu destinasi dengan dua mode tampilan.

### 2.5 Monitoring Voyage hidup di semesta navigasi yang terpisah dari monitoring Shipment

`MonitoringKapalTam` berada di grup navigasi **"Manajemen Kapal"**, sedangkan `ShipmentTrackingResource` berada di grup **"Pengiriman"** — dua grup top-level yang berbeda. Deep-link dari detail Unit ke Voyage sudah ada di level baris (bagus), tapi tidak ada jalur yang dirancang di level navigasi. Supervisor/Manager yang ingin menjawab "apakah keterlambatan shipment saya disebabkan voyage yang delay?" harus berpindah model mental sepenuhnya — dari cargo-sentris ke kapal-sentris.

### 2.6 `cargo_type` sudah dimodelkan, tapi tidak pernah benar-benar dipakai lapisan monitoring

Ini temuan paling penting secara teknis: `Shipment.cargo_type` (`CargoType::Vehicle` / `CargoType::General`) sudah menjadi kolom sungguhan, sudah di-cast ke enum, dan bahkan sudah **pernah dicoba dipakai** untuk percabangan gate (`ensureContainerAssigned()` di `Shipment.php:735-757`) — namun seluruh blok logikanya di-comment-out dan tidak pernah menyala. Tidak satu pun dari `MonitoringDomain`, query builder monitoring, atau `ExceptionEvaluator` yang membaca `cargo_type` sama sekali hari ini.

**Ini kabar baik yang tersembunyi di balik masalah:** perbaikannya bukan "menciptakan field baru", tapi "menyambungkan field yang sudah ada ke lapisan yang selama ini mengabaikannya".

### 2.7 Default workspace berpusat pada satu customer, bukan pada bisnis

`RouteResolver::default()` mengarah ke `'tam'`. Threshold KPI dinamai `jss_kpi.manado`. Ada `TamShipmentResource` dan perintah import bulanan khusus (`ImportTamMay2026Units`, `ImportTamJanuary2026Units`, `ImportTamJune2026Units`). Ini konsisten dan masuk akal secara operasional (TAM kemungkinan besar kontributor volume terbesar hari ini) — tapi untuk modul yang diklaim sebagai *control tower* perusahaan, default-nya justru dijahit ke satu koridor customer. Admin baru di cabang/customer lain mewarisi default yang tidak relevan untuk mereka.

---

## 3. Domain Analysis

### 3.1 Objek utama monitoring: Shipment, Unit, Container, atau kombinasi?

Menjawab pertanyaan ini butuh menguji tiga kandidat, bukan langsung mengasumsikan salah satu.

| Kandidat | Argumen jika dijadikan primary object | Kenapa ditolak / diterima |
|---|---|---|
| **Unit sebagai primary** | Ini yang berlaku hari ini; granular, cocok untuk kebutuhan lapangan (PDI per kendaraan). | **Ditolak sebagai primary.** Unit tidak eksis untuk General/Project Cargo. SLA, KPI, dokumen (SPPB/DO), dan hubungan customer semuanya melekat ke Shipment, bukan ke Unit individual — customer bertanya "shipment saya di mana", bukan "unit #4821 di mana". Unit tetap penting, tapi sebagai *secondary object*. |
| **Container sebagai primary** | Untuk FCL/LCL, container terasa seperti unit fisik paling nyata yang bergerak. | **Ditolak sebagai primary.** Container adalah *resource pengepakan/pengangkutan* — satu Shipment vehicle bisa memakai beberapa Container (lihat `Unit.container_display`), dan satu Container bisa membawa muatan dari beberapa Shipment sekaligus (konsolidasi LCL). Relasi N:M ini adalah ciri khas *resource*, bukan *root object* — persis seperti Voyage terhadap Shipment. |
| **Shipment sebagai primary, Cargo Line sebagai secondary polymorphic** | Shipment adalah satuan janji ke customer: satu SPPB/DO, satu status, satu timeline, satu SLA. | **Diterima.** Ini satu-satunya objek yang secara alami ada di *semua* jenis cargo — vehicle, general cargo, container, project cargo semuanya dibungkus dalam satu Shipment. "Apa isinya secara fisik" (Unit/koli/container/item proyek) adalah detail sekunder yang bisa berbeda bentuk tanpa mengubah makna Shipment itu sendiri. |

**Kesimpulan:** **Shipment adalah primary object.** Objek fisik di dalamnya (Unit hari ini) harus digeneralisasi menjadi konsep **Cargo Line** — sebuah *slot polymorphic* di bawah Shipment yang bentuk konkretnya tergantung `cargo_type`.

### 3.2 Catatan penting: Container punya peran ganda

Satu nuansa yang mudah terlewat: Container **bukan selalu** cargo line, dan **bukan selalu** resource — tergantung konteks bisnisnya.

> - Saat Container membungkus **Unit kendaraan** (kondisi hari ini, lihat `Unit.container_display`), Container adalah **resource pengepakan** — sekadar "kendaraan ini dimuat di container mana", murni informasi logistik, bukan objek yang punya identitas bisnis sendiri.
> - Saat Container membawa **general cargo FCL** (satu container = satu line item di SPPB, tanpa Unit di dalamnya), Container **adalah** cargo line itu sendiri — ia yang disegel, ia yang diinspeksi, ia yang menjadi objek klaim.

Implikasinya: arsitektur tidak boleh memperlakukan Container sebagai satu jenis objek tunggal. Ia harus bisa berperan sebagai **atribut dari sebuah Cargo Line** (kasus pertama) *maupun* sebagai **Cargo Line itu sendiri** (kasus kedua), tergantung `cargo_type` shipment induknya. Ini persis kelas masalah yang sudah pernah coba diselesaikan tim di kode yang di-comment-out (§2.6) — sinyal bahwa kompleksitas ini sudah pernah terasa nyata, walau belum tuntas dimodelkan.

### 3.3 Kenapa Voyage/Armada bukan bagian dari hierarki Shipment

Voyage (laut) dan Armada+Driver (darat) adalah **resource bersama** — satu Voyage membawa banyak Shipment (`Voyage.hasMany(Shipment)`), satu Armada dipakai bergiliran oleh banyak shipment dari waktu ke waktu. Ini bukan "bagian dari" Shipment, melainkan "dipinjam oleh" Shipment untuk satu perjalanan. Memaksakannya ke dalam hierarki Shipment akan membuat model data rancu (siapa pemilik siapa). Perlakuan yang benar — dan ini sudah benar di kode saat ini — adalah **resource link**, bukan child object. Yang belum benar hanyalah *keterhubungan navigasinya* (§2.5, §7).

---

## 4. User Analysis

### 4.1 Persona bisnis vs. role sistem hari ini

Brief sprint ini menyebut lima persona (Office Admin, Field Coordinator, Supervisor, Manager, Customer Service). Role yang benar-benar terimplementasi hari ini (`User.php`) adalah: `super_admin`, `office_admin`, `field_coordinator`, `customer` (portal self-service), `cms`. **Dua tidak cocok 1:1** — ini gap yang perlu diputuskan secara sadar sebelum navigasi/permission dirancang untuk mereka, bukan diasumsikan diam-diam.

| Persona (bisnis) | Role sistem terdekat | Kesenjangan yang perlu diputuskan |
|---|---|---|
| Office Admin | `office_admin` (branch-scoped) | Cocok. |
| Field Coordinator | `field_coordinator` (depot-scoped) | Cocok. |
| **Supervisor** | *Tidak ada role terpisah* | Kemungkinan besar ini bukan role baru, melainkan **default view berbeda** untuk `office_admin` senior — lintas beberapa cabang, fokus ke exception & aging, bukan operasional harian. Perlu diputuskan: role baru, atau mode tampilan? |
| **Manager** | Paling dekat ke `super_admin`, tapi `super_admin` didefinisikan di kode sebagai *"God Mode... bukan daily business user"* | **Ketidakcocokan konsep**, bukan sekadar penamaan. Manager adalah pengguna bisnis harian yang butuh pandangan agregat lintas cabang/cargo type — bukan admin sistem yang mengelola user & konfigurasi. Menyamakan keduanya berisiko: Manager akan punya akses jauh lebih luas dari yang ia butuhkan (atau sebaliknya, `super_admin` sungguhan jadi terlalu sering dipakai untuk kerja harian). |
| **Customer Service (future)** | Role `customer` sudah ada — tapi itu portal **self-service untuk customer itu sendiri** (`customer_id` wajib terisi) | CS adalah staf **internal** yang mencari status atas nama customer yang menelepon — kebutuhan UX-nya terbalik: cari-dulu (search-first), bahasa status yang sudah diterjemahkan (bukan kode operasional seperti "Stuffing"), lintas-customer. Ini role baru, bukan reuse dari `customer`. |

### 4.2 Pertanyaan utama per persona

- **Office Admin** — "Shipment mana di cabang saya yang perlu saya tindak lanjuti hari ini?" · "Apakah dokumen & voyage sudah lengkap untuk shipment minggu ini?"
- **Field Coordinator** — "Cargo apa yang harus saya proses hari ini di depot saya (pickup/handover/stuffing/loading)?" · "Ada inspeksi yang tertunda dan menghambat saya?" — ini kebutuhan *worklist operasional*, bukan dashboard monitoring.
- **Supervisor** — "Cabang/tim mana yang di bawah standar?" · "Shipment mana yang butuh eskalasi saya, bukan cukup ditangani FC?"
- **Manager** — "Bagaimana performa perusahaan bulan ini, lintas semua jenis cargo dan cabang?" · "Customer/rute mana yang paling sering delay?" — berorientasi tren & perbandingan, bukan detail per-baris.
- **Customer Service (future)** — "Apa update terakhir yang bisa saya sampaikan ke customer yang baru saja menelepon?" — butuh pencarian cepat satu shipment dengan bahasa yang customer-friendly.

### 4.3 Daftar Business Questions

Dikelompokkan berdasarkan jenis kebutuhan, melampaui empat contoh di brief:

**Status & posisi**
- Shipment saya sudah sampai di tahap apa?
- Kapan estimasi tiba (ETA), dan apakah masih sesuai jadwal?

**Exception & perhatian**
- Shipment mana yang butuh perhatian saat ini (exception apa pun jenisnya)?
- Shipment mana yang *stuck* (tidak ada progress lebih dari N hari)?
- Shipment mana yang sedang di-*hold*, dan kenapa?
- Cargo mana yang gagal inspeksi dan butuh keputusan (lanjut / return)?
- Container/cargo mana yang berisiko demurrage?
- Shipment mode laut mana yang belum ada voyage padahal seharusnya sudah?

**Performa & penyelesaian**
- Shipment mana yang selesai (delivered) hari ini / minggu ini?
- Berapa persen shipment on-time vs. late bulan ini (KPI/SLA)?
- Cabang / customer / rute mana yang performanya paling buruk?

**Lintas objek**
- Voyage mana yang membawa shipment saya, dan apakah voyage itu sendiri delay?
- Berapa banyak cargo yang harus diproses hari ini di depot saya?
- Apakah dokumen (SPPB/DO) shipment ini sudah lengkap?

**Spesifik per jenis cargo** *(inilah yang tidak terjawab oleh desain saat ini)*
- *(Vehicle)* Apakah semua unit sudah lolos PDI sebelum dikirim ke customer?
- *(General Cargo)* Apakah jumlah colli yang diterima sesuai dengan yang dikirim?
- *(Container)* Apakah segel container masih utuh sampai tujuan? Apakah suhu (reefer) tetap terjaga?
- *(Project Cargo)* Apakah izin/permit untuk muatan over-dimension sudah lengkap? *(kebutuhan ini butuh discovery lanjutan — lihat §11.4)*

**Customer-facing (future)**
- *(Customer Service)* Apa status terakhir yang bisa disampaikan ke customer yang menelepon?
- *(Customer Portal)* Bisakah customer memeriksa status sendiri tanpa menelepon?

---

## 5. Architecture Proposal

### 5.1 Kerangka universal

```
Shipment  (business root — kontrak, SLA, status, timeline)
   │
   ├── Track / Timeline                 [SUDAH universal hari ini — pertahankan, lihat §5.3]
   │
   ├── Cargo Line[]  — polymorphic, bentuk konkret ditentukan Shipment.cargo_type
   │      ├── UnitCargoLine        (Vehicle — implementasi hari ini: tabel `units`)
   │      ├── GeneralCargoLine     (colli/berat/CBM — agregat sudah ada di Shipment)
   │      ├── ContainerCargoLine   (FCL general cargo — container = cargo line itu sendiri)
   │      └── ProjectCargoLine     (masa depan — field belum diketahui, butuh discovery)
   │
   ├── Exception[]                       [dievaluasi per Shipment DAN per Cargo Line — lihat §5.2]
   │
   ├── Resource Links                    [bukan anak hierarki — hanya tertaut]
   │      ├── Voyage          (sea)
   │      ├── Armada + Driver (land)
   │      └── Container slot  (packaging — beda dari ContainerCargoLine di atas, lihat §3.2)
   │
   └── Documents & Audit
```

Diagram ini disengaja terlihat mirip dengan hierarki yang sudah ada di `SPRINT-5-TECHNICAL-ARCHITECTURE.md` §19 ("Core Domain vs Delivery Layer") — bukan kebetulan. Tujuannya memang **menyisipkan** konsep Cargo Line ke dalam layer yang sudah dipisahkan dengan baik (Domain Service, Query Layer, View Model), bukan membangun ulang layer tersebut.

### 5.2 Exception Engine: dari daftar tetap menjadi rule registry

Bukan semua exception berlaku universal. Sebagian bersifat Shipment-level (berlaku apa pun isinya), sebagian bersifat Cargo-Line-level dan spesifik per tipe:

| Level | Contoh exception | Berlaku untuk |
|---|---|---|
| **Shipment-level (universal)** | Delay, Hold, Stuck (tanpa progress > N hari), Missing Voyage | Semua cargo type |
| **Cargo-Line-level (spesifik tipe)** | Vehicle: PDI Pending, NG (major damage) | Hanya `UnitCargoLine` |
| | General Cargo: Shortage/Overage Colli, Klaim Kerusakan | Hanya `GeneralCargoLine` |
| | Container: Segel Rusak, Penyimpangan Suhu (reefer) | Hanya `ContainerCargoLine` |
| | Project Cargo: Permit Belum Lengkap, Over-dimension Clearance Pending | Hanya `ProjectCargoLine` *(indikatif — perlu validasi bisnis)* |

Secara arsitektur, ini berarti `ExceptionEvaluator` yang ada berevolusi menjadi **orchestrator** yang mendelegasikan ke evaluator kecil per CargoType — bukan satu blok enam-case yang terus tumbuh. Pola ini sebenarnya sudah dipuji oleh tim sendiri di §16.1 dokumen teknis Sprint 5 ("Open/Closed — new exception types can be added without modifying existing services") — proposal ini hanya memakai prinsip itu untuk sumbu yang lebih luas (CargoType), bukan sekadar menambah tipe baru dalam sumbu yang sama.

### 5.3 Yang TIDAK perlu diubah: Track/Timeline sudah benar

Kabar baik yang jarang disebut dalam kritik arsitektur: `ShipmentTrack` **sudah** berada di level Shipment, bukan level Unit. Ini adalah satu-satunya bagian dari hierarki hari ini yang sudah sesuai prinsip Shipment-first sejak awal. Progress bar, skeleton track, dan status derivation semuanya bisa dipertahankan apa adanya. Rekomendasi di dokumen ini **tidak** menyentuh `ShipmentTrack`/`TrackStatus` untuk sea-vehicle — hanya meminta agar mekanisme percabangan per-mode yang sudah pernah ada (`orderForMode()`, dihapus sebagian oleh ADR-009) dibuka kembali dan diperluas ke sumbu CargoType juga.

### 5.4 Hubungan eksplisit dengan ADR-009

Dokumen ini **tidak** meminta pembatalan ADR-009. Keputusan itu valid untuk kondisi faktualnya saat ditulis (seluruh traffic adalah TAM/sea/vehicle). Yang direkomendasikan: begitu ada Shipment dengan `cargo_type = General` atau `mode = Land` pertama kali perlu benar-benar dipantau lewat workspace ini, tim membuat ADR baru yang secara eksplisit mengaktifkan kembali **"Extension Protocol (v2 — Adding Land Mode)"** yang sudah didokumentasikan ADR-009 sendiri — diperluas dari sumbu Mode saja menjadi sumbu **Mode × CargoType**. ADR-009 sudah menyiapkan jalan untuk ini (field vestigial `MonitoringFilter::$mode` sengaja dipertahankan, `TrackStatus::orderForMode()` sengaja tidak dihapus dari enum) — disiplin yang sama harus diterapkan untuk `cargo_type`.

### 5.5 Resource objects tetap terpisah, tapi tertaut secara konsisten

Voyage dan Armada tetap menjadi control tower tersendiri (§3.3) — ini keputusan yang benar untuk dipertahankan, bukan digabung. Yang perlu ditambahkan hanyalah **kontrak deep-link yang seragam**: setiap Cargo Line, apa pun tipenya, yang memiliki resource link (Voyage untuk sea, Armada untuk land, Container slot untuk yang dikemas) harus bisa menampilkan status ringkas resource tersebut dan tautan ke control tower-nya — memperluas pola deep-link yang sudah ada (`SPRINT-5-TECHNICAL-ARCHITECTURE.md` §3.7) agar berlaku merata, bukan hanya untuk Voyage.

---

## 6. Information Hierarchy

| Tingkat | Objek | Sifat |
|---|---|---|
| **Primary** | **Shipment** | Business root. Satu-satunya objek yang wajib ada di semua jenis cargo. Pemilik status, SLA, timeline. |
| **Secondary** | **Cargo Line** (polymorphic: Unit \| General Cargo \| Container \| Project Cargo) | "Apa yang diangkut secara fisik." Jumlahnya bisa nol-atau-lebih per Shipment. Bentuk konkretnya mengikuti `cargo_type`. |
| **Supporting** | Inspection/Condition Event (per Cargo Line, tipe-dependent), Documents, Audit Trail | Detail yang memperkaya Cargo Line/Shipment, tapi tidak berdiri sendiri sebagai objek yang dicari pengguna. |
| **Resource (tertaut, bukan bagian hierarki)** | Voyage, Armada+Driver, Container slot, Depot/Office/Port | Objek yang **dipinjam** oleh Shipment/Cargo Line untuk satu perjalanan, dimiliki oleh domain lain (Manajemen Kapal, Manajemen Armada). |

Hierarki di brief (`Shipment ↓ Unit ↓ Inspection ↓ Events`) **benar untuk kasus Vehicle**, tapi keliru jika diperlakukan sebagai hierarki universal — kesalahan yang sama persis dengan Current Problem #2.1. Koreksinya bukan mengganti urutannya, melainkan menyadari bahwa baris kedua ("Unit") adalah **satu dari beberapa kemungkinan bentuk** baris kedua, bukan baris kedua itu sendiri.

---

## 7. Navigation Proposal

### 7.1 Kritik terhadap struktur di brief

```
Pengiriman
├── Permintaan Pengiriman
├── Pelacakan & Monitoring
├── Monitoring Unit Harian      ← masalah
└── Riwayat Pengiriman
```

**"Monitoring Unit Harian" sebagai item terpisah dari "Pelacakan & Monitoring" adalah instance nyata dari masalah §2.4.** Baik nama ini merujuk pada rencana halaman baru, atau sekadar nama sehari-hari yang dipakai orang bisnis untuk workspace exception-band yang grain-nya Unit (yang hari ini memang tersembunyi di dalam `WorkspaceShell`) — keduanya menunjukkan hal yang sama: **dua kebutuhan (ringkasan tenang vs. worklist harian yang mendesak) sedang dipahami sebagai dua destinasi**, padahal seharusnya dua *mode tampilan* dari satu destinasi. Memisahkannya jadi dua menu memaksa pengguna menghafal "yang mana untuk apa" — melanggar *Simple Navigation* dan *No Duplicate Workspace*.

### 7.2 Struktur yang diusulkan

```
Pengiriman
├── Permintaan Pengiriman        (tidak berubah — job-nya: inisiasi/intake)
├── Pelacakan & Monitoring        (SATU destinasi, dua mode di dalamnya:)
│      │                            • "Ringkasan"  — grain Shipment, tenang, untuk Office Admin/Manager
│      │                            • "Hari Ini"   — grain Cargo Line, exception-first, untuk FC/Supervisor
│      │                            (menyerap kebutuhan yang selama ini disebut "Monitoring Unit Harian")
│      │                          Filter lintas cargo type (Vehicle / General Cargo / Container / Project Cargo)
│      │                          tersedia di kedua mode begitu tipe cargo lain benar-benar mulai dipantau.
└── Riwayat Pengiriman            (tidak berubah — job-nya: arsip/audit shipment selesai)

Manajemen Kapal                   (tidak digabung — persona & pertanyaannya beda, lihat §3.3)
└── Monitoring Kapal              Ditambah: badge/cross-link yang lebih terlihat dari Pelacakan & Monitoring
                                   ("N voyage yang mempengaruhi shipment Anda sedang delay") — cross-pollination
                                   ringan, bukan peleburan dua control tower.
```

Alasan **tidak** menggabungkan "Manajemen Kapal" ke dalam "Pengiriman": personanya berbeda (nakhoda-operasi/vessel-ops vs. cargo-ops), pertanyaannya berbeda ("apakah kapal saya tepat waktu" vs "apakah kargo saya tiba"), dan datanya sudah punya model yang berbeda pula (milestone kapal vs. track shipment). Yang salah bukan pemisahannya — yang kurang hanyalah *keterhubungannya* (§2.5).

---

## 8. Shipment Monitoring Concept

### 8.1 Default grain: Shipment, bukan Cargo Line

Karena tesis dokumen ini adalah "Shipment sebagai business root", tampilan **default** dari sebuah control tower yang business-first seharusnya menunjukkan objek bisnis (Shipment) — dengan indikator ringkas jumlah Cargo Line dan badge exception terburuk di dalamnya — bukan langsung meledakkan setiap Cargo Line jadi baris sendiri-sendiri.

Catatan penting yang perlu digarisbawahi secara jujur: default `flat` (grain Unit) yang berlaku hari ini kemungkinan besar adalah **hasil iterasi nyata bersama pengguna** (terlihat dari jejak "Sprint 6.4.x" di `WorkspaceShell.php` dan alasan pemakaian riil di ADR-009). Rekomendasi mengubah default ini **harus divalidasi ulang dengan Office Admin/FC yang sebenarnya memakainya setiap hari**, bukan dibalik sepihak berdasarkan argumen arsitektur semata. Preferensi tim untuk grain granular saat ini boleh jadi memang benar secara operasional untuk kondisi single-cargo-type — argumen dokumen ini berlaku penuh justru pada saat cargo type kedua mulai butuh dipantau.

### 8.2 Detail Hierarchy — urutan isi saat sebuah Shipment dibuka

Diurutkan berdasarkan bagaimana operator sungguhan men-triase sebuah shipment, bukan alfabetis:

1. **Identitas & Status** — kode, customer, rute, mode, cargo type, badge tahap saat ini, indikator exception (jika ada). Orientasi cepat.
2. **Timeline/Progress** — track Shipment yang sudah universal (§5.3). Konten utama, tetap di posisi atas.
3. **Exception / Perlu Perhatian** — apa yang salah *sekarang*, jika ada. Diletakkan tinggi karena ini yang paling cepat menjawab "apakah saya perlu bertindak" — konsisten dengan filosofi *exception-first* yang sudah divalidasi tim di level daftar, sekarang diterapkan juga konsisten di level detail.
4. **Cargo Line[]** — daftar polymorphic (Unit, atau ringkasan General Cargo, atau daftar Container, atau item Project Cargo). Di hierarki baru, ini **satu bagian** dari detail Shipment — bukan lagi satu-satunya grain seluruh workspace.
5. **Inspection/Condition per Cargo Line** — nested, dapat di-expand per baris, tidak lagi "diangkat" ke level workspace global.
6. **Resource Links** — kartu ringkas Voyage (dengan status & tautannya) untuk sea; Armada/Driver untuk land; slot Container jika dikemas.
7. **Documents** — SPPB, DO, foto, lampiran.
8. **Administrative/Audit** — pembuat, riwayat edit, pembatalan, timestamp. Tetap penting untuk akuntabilitas, tapi prioritas terendah untuk penjelajahan sehari-hari.

### 8.3 Exception band tetap ada, tapi menjadi cargo-type-aware

Pola exception band (hitungan per tipe, klik untuk filter) yang ada hari ini adalah pola yang baik dan sudah tervalidasi — dipertahankan. Yang berubah: begitu ada lebih dari satu cargo type aktif, band ini perlu bisa disegmentasi/difilter per cargo type, karena "6 hitungan universal" akan mencampur exception yang tidak sepadan (mis. NG kendaraan dan Shortage Colli General Cargo dalam satu angka yang sama sekali tidak bisa dibandingkan).

---

## 9. Unit Monitoring Concept

Ini bukan permintaan untuk membongkar apa yang sudah berfungsi. **Semua yang baik tentang monitoring Unit hari ini dipertahankan utuh**: enam tahap inspeksi (`pickup` → `dooring`), `gate_decision` (accept/allow_with_remark/return_to_pdc), PDI, sibling unit, `container_display`. Pengguna TAM/vehicle **tidak boleh merasakan regresi fungsional sama sekali**.

Yang berubah murni **framing arsitektural**: "Unit Monitoring" berhenti menjadi *default implisit yang harus dilewati semua tipe cargo lain*, dan menjadi **implementasi pertama dan paling matang** dari konsep umum Cargo Line (§5.1) — yakni `UnitCargoLine`. Semua logika vehicle-specific (inspeksi 6-tahap, PDI, NG major damage) pindah sepenuhnya menjadi modul khusus tipe `Vehicle`, alih-alih tersebar sebagai asumsi implisit di lapisan yang mengaku umum (`ExceptionEvaluator`, `MonitoringRowData`). Ini kerja scaffolding tambahan di sekitar Unit, bukan pengurangan apa pun darinya.

---

## 10. Future Scalability

Mengikuti gaya penilaian reusability yang sudah dipakai tim sendiri (`SPRINT-5-TECHNICAL-ARCHITECTURE.md` §20), tapi pada sumbu cargo-type/kanal baru alih-alih sumbu delivery-channel:

| Kebutuhan masa depan | Dampak ke fondasi (Shipment-primary + Cargo Line + Resource Link) |
|---|---|
| **GPS Tracking / IoT** | Jadi sumber sinyal lokasi baru yang memperkaya tampilan posisi Shipment/Cargo Line saat ini. Track manual tetap jadi SSOT status (punya makna bisnis eksplisit — "handover dikonfirmasi" — yang koordinat GPS mentah tidak punya); GPS jadi lapisan pengayaan, bukan pengganti. Hierarki tidak berubah. |
| **Container Tracking** | Container naik status jadi resource kelas satu dengan feed statusnya sendiri (segel, suhu) — ditautkan persis seperti Voyage ditautkan hari ini. Sudah diakomodasi oleh peran ganda Container di §3.2. |
| **Warehouse / Cross Dock** | Jadi resource baru sekeluarga dengan Depot/Office/Port. Shipment atau Cargo Line mendapat pointer "fasilitas saat ini". Tidak menyentuh primary hierarchy. |
| **Realtime ETA** | Pengayaan pada field ETA yang sudah ada, dengan penanda sumber (manual vs. terhitung otomatis). Ini soal presentasi, bukan hierarki. |
| **Customer Portal** | Sudah secara eksplisit direncanakan reusable di `SPRINT-5-TECHNICAL-ARCHITECTURE.md` §20.1 (tinggal ganti scope `branch_id` → `customer_id`). Manfaat ini didapat **hampir gratis** begitu refactor Shipment-primary selesai — dan sebaliknya makin sulit jika kosakata operasional internal (`gate_decision`, kode `TrackStatus`) terus bocor ke lapisan yang seharusnya customer-facing. Butuh satu lapis "status Shipment versi customer" yang terpisah dari `TrackStatus` internal — kebutuhan yang sama persis dengan persona Customer Service (§4.1). |
| **Public Tracking (tanpa login)** | **Sudah ada bentuk awalnya** — `Http\Controllers\Public\TrackingController` mencari Shipment berdasarkan `code`, bukan berdasarkan nomor rangka/unit. Ini justru **bukti nyata di produksi** bahwa pengalamatan Shipment-first sudah valid dan berfungsi. Perluasannya ke seluruh sistem tinggal soal cakupan data yang diekspos, bukan soal fondasi baru. |

---

## 11. Final Recommendation

### 11.1 Keputusan inti

1. **Shipment adalah primary object monitoring, di semua permukaan** — daftar, detail, exception, navigasi.
2. **Perkenalkan konsep Cargo Line** sebagai secondary object polymorphic. `UnitCargoLine` (Vehicle) adalah implementasi pertama, dibangun dari kode Unit yang sudah ada — bukan ditulis ulang.
3. **Pertahankan `ShipmentTrack`/Timeline di level Shipment apa adanya** (§5.3) — ini satu-satunya bagian yang sudah benar sejak awal.
4. **Ubah `ExceptionEvaluator` dari daftar tetap menjadi rule registry per CargoType × Mode** (§5.2), dengan exception universal (Delay/Hold/Stuck/Missing Voyage) tetap di level Shipment.
5. **Satukan "Pelacakan & Monitoring" dan "Monitoring Unit Harian"** menjadi satu destinasi navigasi dengan dua mode tampilan (§7.2).
6. **Pertahankan pemisahan Manajemen Kapal**, perkuat hanya keterhubungannya (§2.5, §7.2) — jangan digabung.
7. **Jangan batalkan ADR-009** — susun ADR susulan yang mengaktifkan kembali extension protocol-nya untuk sumbu CargoType, tepat pada saat cargo type kedua pertama kali butuh benar-benar dipantau (§5.4).

### 11.2 Urutan pengerjaan yang disarankan

Tidak semua harus dibangun sekaligus — justru sebaliknya, prinsip *future-proof* di sini berarti membuka seam-nya dulu, baru mengisi kontennya:

1. **Fase 1 — Buka seam-nya, belum menambah cargo type baru.** Sambungkan `cargo_type` ke `MonitoringDomain`/query layer/`ExceptionEvaluator` (mengangkat kembali niat yang sudah tertulis-lalu-di-comment-out di `Shipment.php:735-757`), meski yang lewat baru `Vehicle`. Ini membuat langkah berikutnya jadi perubahan aditif, bukan rombak ulang — persis semangat "Extension Protocol" ADR-009.
2. **Fase 2 — General Cargo Line.** Cargo type termurah untuk didukung berikutnya karena field agregatnya (`packages_total`, `cbm_total`, `weight_total`) **sudah ada** di `Shipment`. Tidak perlu tabel baru untuk versi awal.
3. **Fase 3 — Container sebagai resource + Cargo Line ganda.** Butuh pemodelan peran ganda dari §3.2 secara eksplisit sebelum diimplementasikan.
4. **Fase 4 — Project Cargo.** **Ditandai secara jujur sebagai belum siap dirancang.** Belum ada bukti di codebase atau dokumen bisnis yang menunjukkan field/proses Project Cargo sudah dipahami (dimensi, izin, prosedur over-weight). Sebelum masuk backlog implementasi, ini butuh sesi discovery/PRD terpisah bersama tim operasional — dokumen ini sengaja tidak mengarang spesifikasinya.

### 11.3 Yang secara sadar TIDAK direkomendasikan

- **Tidak** merombak lapisan Presentation/Application/Domain Service/Query Layer yang sudah dipisahkan dengan baik di Sprint 5 — kualitas rekayasanya bagus, masalahnya murni di pemodelan domain di atasnya.
- **Tidak** menggabungkan Monitoring Kapal ke dalam Pengiriman.
- **Tidak** mengubah default grain workspace secara sepihak tanpa validasi pengguna nyata (§8.1).
- **Tidak** membangun Project Cargo tanpa discovery lebih dulu.

---

## 12. Lampiran: Referensi Kode

Dokumen ini disusun berdasarkan pembacaan langsung kode dan dokumen berikut, bukan asumsi:

| Area | File |
|---|---|
| Domain model inti | `app/Models/Shipment.php`, `app/Models/Voyage.php`, `app/Models/Unit.php`, `app/Models/Armada.php` |
| Tracking & inspeksi | `app/Models/ShipmentTrack.php`, `app/Models/UnitInspection.php`, `app/Models/UnitInspectionItem.php` |
| Enum domain | `app/Enums/ShipmentStatus.php`, `app/Enums/TrackStatus.php`, `app/Enums/CargoType.php` |
| Kode cargo_type yang di-comment-out | `app/Models/Shipment.php:735-757` (`ensureContainerAssigned`) |
| Workspace monitoring saat ini | `app/Filament/Resources/ShipmentTrackingResource.php`, `app/Filament/Resources/ShipmentTrackingResource/Pages/WorkspaceShell.php`, `app/Filament/Pages/PelacakanMonitoring.php` |
| Monitoring kapal | `app/Filament/Pages/MonitoringKapalTam.php` |
| Layanan monitoring | `app/Services/Monitoring/*.php` (16 kelas), `app/ViewModels/Monitoring/*.php` (13 DTO) |
| Navigasi | `app/Providers/Filament/AdminPanelProvider.php` |
| Peran pengguna | `app/Models/User.php`, `app/Policies/MonitoringWorkspacePolicy.php` |
| Public tracking (bukti Shipment-first sudah berjalan) | `app/Http/Controllers/Public/TrackingController.php` |
| Dokumen arsitektur terkait | `docs/pelacakan-monitoring/SPRINT-5-TECHNICAL-ARCHITECTURE.md`, `docs/pelacakan-monitoring/ADR-009-domain-constraint-monitoring-v1.md` |
