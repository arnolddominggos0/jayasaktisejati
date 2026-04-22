# Product Requirements Document (PRD)
## Sistem Operasional Logistik Laut & Distribusi

## 1. Ringkasan Produk

Produk ini adalah platform operasional berbasis web untuk mengelola alur distribusi logistik yang mencakup:

1. Manajemen shipment end-to-end.
2. Perencanaan voyage/jadwal kapal.
3. Monitoring KPI operasional (termasuk TAM/kapal).
4. Manajemen resource lapangan (armada, manpower, APD/PPE).
5. Kontrol akses berbasis peran dan cakupan cabang/depot.

Platform dibangun di atas Laravel + Filament untuk kebutuhan panel admin/internal, serta API berbasis token untuk kebutuhan integrasi aplikasi/kanal lain.

---

## 2. Latar Belakang & Masalah

Operasional logistik umumnya menghadapi masalah berikut:

- Data shipment, jadwal kapal, dan resource tersebar di banyak sumber.
- Sulit memastikan visibilitas status pengiriman secara real-time.
- Proses koordinasi antar peran (super admin, office admin, field coordinator) tidak seragam.
- Monitoring performa operasional (ketepatan jadwal, SLA, pencapaian KPI) kurang terstruktur.

Sistem ini ditujukan untuk menyatukan proses operasional ke satu platform dengan kontrol akses yang jelas, jejak audit, dan kemampuan monitoring yang actionable.

---

## 3. Tujuan Produk

### 3.1 Tujuan Bisnis

- Menurunkan keterlambatan pengiriman dengan perencanaan & monitoring lebih baik.
- Meningkatkan akurasi data operasional lintas cabang/depot.
- Meningkatkan produktivitas tim operasional melalui otomasi proses rutin.

### 3.2 Tujuan Produk

- Menyediakan satu sumber data operasional terpusat.
- Mempercepat pengambilan keputusan berbasis dashboard & KPI.
- Menyediakan API aman untuk autentikasi, manajemen pengguna, dan data master cabang.

---

## 4. Ruang Lingkup

### 4.1 In-Scope (MVP/Current Scope)

1. **Autentikasi & Otorisasi**
   - Login/register user API.
   - Token-based auth (Sanctum).
   - Role-based access control (RBAC).
   - Scoping data berbasis branch/depot.

2. **Manajemen Master Data**
   - User.
   - Branch & depot.
   - Customer, port, shipping line, vessel, voyage.

3. **Operasional Shipment**
   - Pencatatan shipment.
   - Tracking & histori status shipment.
   - Cetak dokumen operasional (waybill, resi, packing list).

4. **Perencanaan & Jadwal Kapal**
   - Kelola shipping schedule/voyage.
   - Vessel plan dan review/finalisasi.
   - Sinkronisasi/otomasi jadwal via command.

5. **Monitoring & Dashboard**
   - Widget dan halaman monitoring (termasuk monitoring kapal TAM).
   - KPI berbasis data operasional.

6. **Resource Lapangan**
   - Armada, manpower, assignment.
   - APD/PPE assignment & inspeksi.
   - Briefing session & attendance.

### 4.2 Out-of-Scope (Sementara)

- Aplikasi mobile native.
- Billing/invoicing pelanggan.
- Integrasi pembayaran.
- Integrasi IoT/telemetri kendaraan real-time tingkat lanjut.

---

## 5. Persona Utama

1. **Super Admin**
   - Mengelola konfigurasi global, branch, pengguna, dan kontrol akses.
2. **Office Admin**
   - Memantau operasional harian, update data shipment, verifikasi jadwal.
3. **Field Coordinator**
   - Menjalankan eksekusi lapangan (resource, shipment status, briefing).
4. **Manajemen Operasional**
   - Melihat dashboard KPI untuk pengambilan keputusan.

---

## 6. Kebutuhan Fungsional

### FR-01 Autentikasi & Session API
- User dapat register dan login via API.
- Login dibatasi throttle untuk mitigasi brute-force.
- User dapat melihat profil "me" dan logout.

### FR-02 Otorisasi Berbasis Peran
- Endpoint tertentu hanya dapat diakses role tertentu.
- Hak akses minimal:
  - Super admin: full branch CRUD.
  - Office admin + field coordinator: akses internal read/update sesuai aturan.

### FR-03 Scoping Data Cabang/Depot
- Semua data operasional yang sensitif harus tersaring sesuai branch/depot user.
- User tidak boleh mengakses data lintas cakupan tanpa hak.

### FR-04 Manajemen Branch
- CRUD branch sesuai hak akses.
- Validasi branch id numerik untuk endpoint terkait.

### FR-05 Manajemen Shipment
- Pencatatan data shipment dan relasi customer/voyage/branch.
- Riwayat status shipment dapat ditelusuri (tracking/histori).
- Dokumen operasional dapat dicetak (waybill, resi, packing list).

### FR-06 Perencanaan Voyage & Schedule
- Input dan update jadwal kapal/voyage.
- Penyusunan vessel plan per periode.
- Finalisasi rencana untuk dieksekusi operasional.

### FR-07 Monitoring KPI
- Dashboard menampilkan metrik utama:
  - Shipment aktif/selesai/terlambat.
  - Ketepatan jadwal voyage.
  - Pencapaian KPI bulanan operasional.

### FR-08 Manajemen Resource Lapangan
- Kelola armada dan status maintenance.
- Kelola manpower & assignment.
- Kelola APD/PPE assignment, kondisi, dan inspeksi.

### FR-09 Notifikasi Operasional
- Notifikasi email/WhatsApp untuk event penting (mis. pengingat ETA, perubahan status kritikal).

### FR-10 Auditability
- Aktivitas penting user terdokumentasi di activity log.
- Perubahan status shipment memiliki jejak event.

---

## 7. Kebutuhan Non-Fungsional

1. **Keamanan**
   - Auth token, middleware role, dan scoping wajib aktif di endpoint sensitif.
   - Proteksi brute-force pada login API.

2. **Kinerja**
   - Waktu respons API p95 < 500 ms untuk endpoint list utama (tanpa beban puncak ekstrem).
   - Dashboard utama load < 3 detik untuk data bulan berjalan.

3. **Keandalan**
   - Job sinkronisasi terjadwal harus idempotent dan memiliki logging kegagalan.

4. **Skalabilitas**
   - Struktur data dan service layer siap untuk pertumbuhan jumlah branch/shipment.

5. **Maintainability**
   - Pemisahan concern controller/service/action.
   - Konvensi enum/model/resource konsisten.

6. **Lokalisasi**
   - Bahasa default Indonesia.
   - Timezone operasional Asia/Jakarta.

---

## 8. User Journey (Ringkas)

### 8.1 Alur Internal Shipment
1. Office admin membuat/validasi data shipment.
2. Sistem menentukan depot/cakupan sesuai aturan.
3. Field coordinator mengupdate progres lapangan.
4. Status shipment tersimpan pada histori tracking.
5. Dokumen shipment dicetak saat diperlukan.
6. Manajemen memonitor KPI & SLA di dashboard.

### 8.2 Alur Perencanaan Voyage Bulanan
1. Tim operasional menyiapkan draft jadwal/vessel plan bulanan.
2. Draft direview dan direvisi.
3. Finalisasi dilakukan, lalu dipakai sebagai acuan eksekusi shipment.
4. Monitoring menilai deviasi jadwal vs realisasi.

---

## 9. Data & Integrasi Utama

### 9.1 Entitas Data Kunci
- User, Role, Branch, Depot.
- Customer, Shipment, ShipmentTrack, ShipmentEvent.
- Vessel, Voyage, ShippingSchedule, VesselPlan.
- Armada, Manpower, PpeItem/PpeAssignment.

### 9.2 Integrasi/Komponen
- API autentikasi berbasis Sanctum.
- Filament panel untuk operasional internal.
- Command scheduler untuk sinkronisasi dan notifikasi.
- Email dan WhatsApp service untuk pengingat/notifikasi.

---

## 10. Metrik Keberhasilan

### 10.1 Product Metrics
- ≥ 95% shipment memiliki tracking status lengkap.
- Penurunan keterlambatan shipment (bulan ke bulan) minimal 15% setelah 2 kuartal.
- ≥ 90% branch aktif menggunakan dashboard minimal 3x per minggu.

### 10.2 Engineering Metrics
- Error rate endpoint kritikal < 1%.
- Uptime aplikasi > 99.5% (target operasional).
- Keberhasilan job terjadwal > 98%.

---

## 11. Risiko & Mitigasi

1. **Kualitas data input rendah**
   - Mitigasi: validasi form ketat, enum status, audit log.
2. **Konflik proses antar cabang**
   - Mitigasi: SOP role matrix dan branch/depot scoping default.
3. **Adopsi pengguna rendah**
   - Mitigasi: pelatihan role-based, dashboard fokus kebutuhan harian.
4. **Ketergantungan integrasi eksternal (WA/email)**
   - Mitigasi: retry policy dan fallback notifikasi.

---

## 12. Rencana Rilis Bertahap

### Fase 1 (Fondasi)
- Auth + role + scope branch/depot.
- Master data inti (branch, user, customer, port, vessel).

### Fase 2 (Core Ops)
- Shipment management + tracking + print dokumen.
- Schedule & voyage planning dasar.

### Fase 3 (Operational Excellence)
- Dashboard KPI lanjutan + SLA.
- Otomasi notifikasi + peningkatan workflow review/finalisasi.

### Fase 4 (Scale)
- Optimasi performa multi-branch.
- Integrasi tambahan sesuai prioritas bisnis.

---

## 13. Open Questions (Untuk Workshop Stakeholder)

1. Definisi SLA resmi per jenis shipment dan rute?
2. Prioritas KPI utama yang menjadi target triwulan?
3. Notifikasi WhatsApp: trigger event mana yang wajib vs opsional?
4. Batasan edit historis shipment setelah status tertentu (lock policy)?
5. Kebutuhan akses pelanggan eksternal ke tracking publik (scope & keamanan)?
6. Integrasi AppSheet untuk input lapangan FC (briefing/APD/checkpoint/checksheet) pada fase berapa?

---

## 14. Lampiran: Peta Fitur Saat Ini (Berdasarkan Struktur Kode)

- API user auth + branch/user management tersedia.
- Route web untuk cetak dokumen shipment tersedia.
- Resource Filament mencakup shipment, voyage, vessel plan, resource lapangan, serta dashboard/monitoring.
- Service/action/command menunjukkan dukungan otomasi penjadwalan, sinkronisasi data voyage, evaluasi SLA, dan notifikasi.
