# PRD Khusus Field Coordinator (FC)
## Modul Operasional Lapangan — Sea Shipment

## 1. Tujuan Dokumen
Dokumen ini menjadi acuan untuk mengecek apakah implementasi **role Field Coordinator (FC)** di project ini sudah sesuai ekspektasi operasional. Fokusnya adalah tugas yang bisa dilakukan FC di sistem saat ini, guardrails akses data, dan acceptance criteria per alur kerja.

## 2. Ringkasan Peran FC
FC adalah eksekutor operasional lapangan yang bertugas menjalankan proses shipment laut harian, memastikan kesiapan tim dan unit, serta memperbarui progres lapangan secara tepat waktu agar dashboard operasional selalu aktual.

## 3. Scope FC (Current Product Scope)
### 3.1 In Scope
1. Melihat dashboard operasional FC (ringkasan KPI, perhatian, aktivitas, tren status).
2. Melihat daftar shipment **mode sea** yang memang ditugaskan dalam scope branch/depot/coordinator.
3. Melihat detail shipment dan histori tracking.
4. Melakukan update status tracking lapangan sesuai workflow.
5. Menjalankan proses briefing harian dan kehadiran manpower.
6. Menjalankan sesi loading (termasuk check progres per sub-proses).
7. Mencatat catatan/finding operasional untuk audit jejak eksekusi.

### 3.2 Out of Scope
1. Akses shipment mode land.
2. Create/delete shipment dari panel FC.
3. Akses data lintas branch/depot di luar scope user.
4. Global configuration (master data strategis, role management, dsb).

## 4. Persona & Kebutuhan
### Persona Primer
- **Field Coordinator**: butuh tampilan cepat untuk shipment prioritas, gate validasi operasional, dan input progres lapangan yang minim friction.

### Pain Point yang Harus Diselesaikan
- Sulit memprioritaskan shipment urgent/on-hold/ETA dekat.
- Update lapangan tidak konsisten jika form tidak menegakkan validasi workflow.
- Potensi data bocor jika scoping branch/depot longgar.

## 5. Kebutuhan Fungsional FC

### FC-FR-01 — Akses Panel FC
- FC dapat login ke panel `/fc`.
- Hanya user dengan role `field_coordinator` yang bisa akses halaman dan resource FC.

### FC-FR-02 — Scope & Otorisasi Data
- Semua data yang terlihat di FC harus tunduk ke scope branch/depot.
- Listing shipment dibatasi ke shipment **sea** dan assignment relevan (`assigned_depot` atau `coordinator`).
- Jika scope branch/depot tidak valid, data tidak boleh ditampilkan.

### FC-FR-03 — Dashboard Operasional Harian
Dashboard FC minimal menyediakan:
1. Ringkasan angka: ditugaskan, berjalan, on-hold, urgent, ETA dekat.
2. Daftar “butuh perhatian hari ini” untuk prioritisasi.
3. Aktivitas tracking terbaru yang bisa dipantau real-time.
4. Tren update status 14 hari untuk melihat ritme operasional.

### FC-FR-04 — Manajemen Shipment Ditugaskan
- FC dapat melihat list shipment ditugaskan (read-only create/delete).
- FC dapat membuka detail shipment yang memuat data route, status, timeline, dan konteks operasional.

### FC-FR-05 — Workflow Update Tracking Lapangan
- FC bisa mengubah status tracking berdasarkan urutan status sea.
- Sistem menampilkan opsi next status dengan guard terhadap loncat alur.
- Field `note`, `checksheet`, `attachment`, dan `override_reason` mengikuti aturan wajib sesuai konteks status.
- Status hold/cancel harus memiliki catatan yang memadai.

### FC-FR-06 — Briefing Harian & MP Check
- FC bisa membuat/mengelola sesi briefing harian di scope depot.
- FC dapat mencatat target headcount, kehadiran, catatan briefing.
- FC dapat menjalankan approve gate MP check untuk membuka tahap operasional berikutnya.

### FC-FR-07 — Sesi Loading & Checkpoint Operasional
- FC dapat melihat/mengelola sesi loading per shipment.
- Sesi menampilkan progres checklist lintas domain:
  - kehadiran MP,
  - cek kesehatan,
  - cek APD,
  - cek alat,
  - cek rack/container,
  - cek unit,
  - kecukupan MP,
  - final decision.
- FC dapat menambah catatan umum sesi loading.

### FC-FR-08 — Integrasi Input Lapangan (AppSheet-Ready)
- Data lapangan dari mobile form (webhook) harus bisa masuk ke backend dan muncul di dashboard/record FC.
- Semua submit/update harus tercatat log sinkronisasi dan audit trail.

## 6. Daftar Tugas FC yang Harus Bisa Dilakukan (Checklist Validasi)
Gunakan checklist ini untuk mencocokkan apakah FC sudah sesuai harapan:

1. **Buka Dashboard FC** dan lihat shipment prioritas hari ini.
2. **Filter mental prioritas**: urgent, on-hold, ETA ≤ 24 jam.
3. **Buka daftar shipment ditugaskan** (sea-only).
4. **Masuk ke detail shipment** untuk cek route dan histori track.
5. **Update status tracking** sesuai kejadian lapangan (tanpa skip tidak valid).
6. **Isi checksheet + bukti** pada status yang mewajibkan.
7. **Input note wajib** saat status exception (hold/cancel).
8. **Jalankan briefing harian** dan isi data kehadiran/target MP.
9. **Approve MP check** bila syarat operasional terpenuhi.
10. **Jalankan sesi loading** dan cek progres tiap domain pemeriksaan.
11. **Simpan catatan/finding** untuk isu operasional.
12. **Monitor aktivitas terbaru** di dashboard untuk verifikasi update berhasil.

## 7. Non-Fungsional Requirement (FC)
1. **Security**: role check + scope check harus berlaku di semua query FC.
2. **Data Integrity**: update tracking mengikuti state transition yang tervalidasi.
3. **Auditability**: perubahan status dan hasil check tersimpan dengan jejak waktu/pelaku.
4. **Usability**: panel FC harus mobile-friendly secara layout operasional (khusus AppSheet input).
5. **Freshness**: widget FC dipolling periodik untuk near real-time monitoring.

## 8. Acceptance Criteria per Modul

### A. Dashboard FC
- Menampilkan minimal 4 blok informasi: KPI, perhatian, aktivitas, tren.
- Data hanya berasal dari shipment sea dalam scope FC.

### B. Shipment FC
- FC tidak bisa create/delete shipment.
- FC hanya melihat shipment dalam branch/depot/coordinator scope.
- FC bisa melihat detail shipment tanpa error saat ada field null.

### C. Tracking FC
- Opsi status menunjukkan urutan progres dan menandai step current/next.
- Validasi mandatory field berjalan sesuai status.
- Exception status wajib punya alasan/catatan.

### D. Briefing FC
- FC dapat membuat briefing harian di depot-nya.
- Status MP check bisa bergerak sampai approved, beserta timestamp & approver.

### E. Loading Session FC
- Progres sub-checklist terlihat jelas per sesi.
- Catatan umum sesi tersimpan.
- Link dari shipment ke loading session konsisten.

## 9. KPI Keberhasilan untuk Role FC
1. ≥ 95% shipment assigned punya update tracking tepat waktu.
2. 100% kasus hold/cancel memiliki catatan alasan.
3. ≥ 90% sesi briefing harian aktif memiliki data kehadiran.
4. Lead time dari kejadian lapangan ke input sistem < 30 menit (target operasional).

## 10. Gap Check Template (Untuk Audit Cepat)
Gunakan tabel ini saat UAT / evaluasi:

| Area | Ekspektasi | Status Saat Ini | Gap | Aksi Lanjut |
|---|---|---|---|---|
| Dashboard FC | KPI + attention + activity + trend tersedia |  |  |  |
| Scope Data | Sea-only + branch/depot enforced |  |  |  |
| Tracking Workflow | Validasi status & mandatory field |  |  |  |
| Briefing MP | Buat/edit/approve berjalan |  |  |  |
| Loading Session | Checklist progres lintas domain |  |  |  |
| Audit Trail | Jejak update jelas |  |  |  |

## 11. Referensi Internal
- `docs/PRD.md`
- `docs/FC_OPERATIONAL_TASKS.md`
- `docs/APPSHEET_INTEGRATION.md`
- `app/Filament/FC/*`
