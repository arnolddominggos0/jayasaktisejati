# PRD Planning — Perbaikan UI Field Coordinator (FC) Berbasis Branch

## 1) Latar Belakang
UI FC saat ini sudah memiliki fungsi operasional inti, tetapi pengalaman pengguna belum sepenuhnya dioptimalkan per kebutuhan branch (cabang) yang berbeda. Perbedaan volume shipment, pola kerja depot, dan prioritas harian antar branch menuntut UI yang lebih kontekstual.

Dokumen ini fokus pada **planning** (belum implementasi) untuk penyempurnaan UI FC agar:
- lebih relevan terhadap scope branch pengguna,
- lebih cepat dipakai untuk keputusan operasional harian,
- dan tetap konsisten dengan guard role + branch/depot.

---

## 2) Tujuan Planning
1. Menentukan area UI FC yang perlu ditingkatkan berbasis konteks branch.
2. Menetapkan prioritas fase implementasi agar perubahan aman dan terukur.
3. Menyusun acceptance criteria UI yang bisa dipakai saat review/QA.
4. Menyiapkan backlog task engineering yang kecil dan bisa dieksekusi bertahap.

---

## 3) Problem Statement (UI)
1. FC di branch dengan shipment tinggi kesulitan memprioritaskan task paling kritis dari tampilan yang sama untuk semua branch.
2. Informasi dashboard belum menonjolkan perbedaan kondisi antar branch (mis. beban tinggi vs normal).
3. Navigasi dan urutan informasi belum sepenuhnya “action-first” untuk kebutuhan lapangan yang dinamis.
4. Konsistensi komponen (badge status, warna prioritas, empty state, wording) perlu distandardisasi agar onboarding FC baru lebih cepat.

---

## 4) Scope Planning

### In Scope
- Dashboard FC (KPI cards, attention list, activity list, trend chart).
- List shipment FC (hierarki informasi, quick actions, badge/status clarity).
- Detail shipment FC (ringkasan informasi penting di atas fold).
- Komponen UI umum: warna status, label, icon, empty/loading state.
- Pengaturan tampilan berbasis branch context (tanpa melanggar data scoping backend).

### Out of Scope
- Perubahan business logic shipment land.
- Perubahan RBAC fundamental.
- Re-arsitektur backend besar.
- Mobile native app.

---

## 5) Prinsip Desain (Guiding Principles)
1. **Actionable First**: informasi paling menentukan keputusan tampil paling awal.
2. **Branch Contextual**: UI menyesuaikan karakteristik operasional branch.
3. **Low Cognitive Load**: kurangi kebingungan melalui label dan visual konsisten.
4. **Safe by Default**: tidak mengendurkan guard role/scope.
5. **Progressive Disclosure**: detail lanjutan muncul saat dibutuhkan.

---

## 6) Persona & Skenario

### Persona Primer
- Field Coordinator aktif yang memonitor banyak shipment per hari.

### Skenario Kunci
1. FC login pagi hari untuk menentukan prioritas shipment kritis.
2. FC memonitor aktivitas tracking terbaru untuk verifikasi update tim lapangan.
3. FC menindaklanjuti shipment urgent/hold dengan cepat dari dashboard.
4. FC branch volume tinggi butuh ringkasan lebih padat tanpa kehilangan konteks.

---

## 7) Requirement Planning (UI)

### UI-FR-01: Branch Header Context
- Dashboard menampilkan konteks branch aktif secara jelas (nama branch/depot scope).
- Tersedia indikator scope agar user paham data yang sedang dilihat.

### UI-FR-02: KPI Prioritization by Branch
- KPI cards diurutkan berdasarkan prioritas operasional branch.
- Branch volume tinggi dapat menonjolkan metrik urgent/on-hold lebih dulu.

### UI-FR-03: Attention List Optimization
- Tabel “Butuh Perhatian” mendukung visual severity yang tegas (warna/badge).
- Sorting default tetap memprioritaskan urgent lalu ETA terdekat.

### UI-FR-04: Activity Feed Clarity
- Aktivitas terbaru menampilkan informasi ringkas: waktu, kode shipment, status, route, pelaku.
- Status badge menggunakan skema warna seragam lintas widget.

### UI-FR-05: Shipment List Readability   
- Kolom inti (kode, status, prioritas, ETA, route) lebih mudah dipindai cepat.
- Empty state branch-specific (mis. “Tidak ada shipment dalam scope branch ini”).

### UI-FR-06: Detail Page Action Panel
- Detail shipment memiliki panel aksi cepat (update tracking, buka loading session, lihat dokumen terkait bila berhak).
- Informasi critical (status saat ini, next step, ETA) tampil di bagian atas.

### UI-FR-07: Design Consistency
- Standarisasi badge/status color token di seluruh area FC.
- Standarisasi istilah UI agar tidak ada label ganda untuk konteks sama.

### UI-FR-08: Accessibility & Responsiveness
- Kontras warna minimum memenuhi readability internal.
- Layout tetap usable pada resolusi laptop operasional standar.

---

## 8) Rencana Eksekusi Bertahap (Planning)

### Phase 0 — Discovery & Baseline (1 sprint)
- Audit komponen UI FC yang sudah ada.
- Kumpulkan pain points dari minimal 2 branch berbeda (high volume vs medium/low).
- Tetapkan baseline metric: waktu identifikasi prioritas, click depth ke aksi utama.

### Phase 1 — Dashboard Quick Wins (1 sprint)
- Tambah branch context header.
- Rapikan prioritas KPI + attention list visual hierarchy.
- Standarisasi badge warna pada dashboard.

### Phase 2 — Shipment List & Detail UX (1–2 sprint)
- Tingkatkan readability list shipment.
- Tambah action panel di detail shipment.
- Samakan istilah dan empty state message.

### Phase 3 — Consistency & Hardening (1 sprint)
- Sweep konsistensi komponen lintas FC pages/widgets.
- QA akses role/scope dan regression visual.
- Finalisasi guideline UI FC berbasis branch.

---

## 9) Acceptance Criteria Planning
1. User FC dapat langsung mengenali branch/depot scope dari dashboard tanpa membuka halaman lain.
2. Shipment prioritas (urgent/hold/ETA dekat) lebih cepat teridentifikasi dibanding baseline awal.
3. Tidak ada inkonsistensi warna/status label antar widget utama FC.
4. Empty state dan helper text tidak ambigu untuk konteks branch.
5. Seluruh perubahan UI tidak mengubah batasan akses data branch/depot.

---

## 10) Backlog Task Teknis (Draft)
1. Audit komponen FC dan mapping token warna/status.
2. Implement branch context header di dashboard FC.
3. Refactor KPI card ordering berbasis prioritas branch.
4. Perbaikan kolom dan spacing di attention list + shipment list.
5. Tambah section “aksi cepat” di detail shipment FC.
6. Sinkronisasi wording/label lintas widget FC.
7. Tambah checklist QA visual + scope regression.

---

## 11) Risiko & Mitigasi
1. **Risiko:** Over-custom branch membuat UI sulit dirawat.
   **Mitigasi:** Gunakan konfigurasi ringan + komponen reusable, bukan fork tampilan total.

2. **Risiko:** Perubahan visual mengganggu alur user lama.
   **Mitigasi:** Rilis bertahap per phase + feedback loop mingguan.

3. **Risiko:** Inkonstensi style antar halaman.
   **Mitigasi:** Definisikan UI token dan checklist review komponen.

---

## 12) Output yang Diharapkan dari Planning Ini
1. Dokumen PRD planning UI FC berbasis branch (dokumen ini).
2. Daftar phase implementasi yang bisa langsung dipecah ke task engineering.
3. Acceptance criteria yang siap dipakai reviewer untuk validasi hasil implementasi.
