# Detail Unit Workspace — UX Architecture (FROZEN v1.1)

**Status:** 🧊 **UX ARCHITECTURE FROZEN**
**Tanggal:** 20 Juli 2026
**Prasyarat:** Detail Unit Workspace v1.0 (layout build)
**Prinsip inti:** Workspace = *tempat menyelesaikan pekerjaan*, bukan halaman CRUD / database / detail model.

---

## Ringkasan

Sprint v1.1 mengaudit implementasi v1.0 dan membekukan arsitektur UX-nya. **Tidak ada** perubahan business logic, tracking/inspection/timeline engine, query, domain model, atau workflow. Hanya penyempurnaan presentasi berdasarkan hasil audit. Setelah sprint ini, urutan section dan struktur informasi **dikunci**.

**File:**
- `app/Filament/Resources/ShipmentTrackingResource/Pages/UnitWorkspace.php` (page, reuse `DetailUnitProvider`)
- `resources/views/filament/resources/shipment-tracking-resource/pages/unit-workspace.blade.php` (layout)
- Route: `admin/shipment-trackings/unit/{unitId}`

---

## Final Information Hierarchy (LOCKED)

Satu alur baca, atas → bawah, tanpa tab:

```
1. Hero Summary          → kondisi unit dalam 5 detik
2. Timeline Operasional  → pusat & fokus visual terbesar
3. Informasi Operasional → grouping bisnis (Pengiriman / Perjalanan / Unit)
4. Pemeriksaan Unit      → ringkasan status per tahap
5. Riwayat Aktivitas     → kronologis, bahasa operasional
6. Dokumen               → paling akhir
```

Urutan ini **tidak boleh diubah**.

---

## Final Layout per Section

| # | Section | Isi final | Sumber data (reuse, no new query) |
|---|---|---|---|
| 1 | **Hero Summary** | Nomor Unit · Model · Status Operasional (badge) · grid Tahap/Dwelling/Voyage/ETA/Rute · Quick Action | `UnitDetailData` (reg_no, model, stage, age, admin, exceptions) |
| 2 | **Timeline Operasional** | Progress bar + timeline partial (✓ selesai · ● berlangsung · ○ berikutnya) | `UnitTimeline` via `unit-timeline` partial |
| 3 | **Informasi Operasional** | 3 kartu: **Pengiriman** (Resi/SPPB/Customer/Moda/Prioritas), **Perjalanan** (Voyage/Kapal/ETD/ETA/Rute), **Unit** (No/Model/Warna). Data teknis (Chassis/Engine/SJKB/Container) di `<details>` collapsible | `UnitDetailData` + `AdministrativeInfo` |
| 4 | **Pemeriksaan Unit** | Status per tahap: Selesai / Belum / Temuan·N (+ tanggal & inspektur bila ada). Bukan checklist penuh | `InspectionSummary` |
| 5 | **Riwayat Aktivitas** | Tahap selesai (terbaru dulu): "Tahap diperbarui menjadi X" + tanggal·jam + catatan | Diturunkan dari `UnitTimeline` (completed stages) |
| 6 | **Dokumen** | Resi + SPPB + Lampiran **saja** | `route('shipments.resi')` + `deep_links` (difilter ke dokumen) |

---

## Keputusan Audit v1.1 (perubahan dari v1.0)

1. **Quick Action → hanya aksi operasional.** Menghapus "Lihat Dokumen" & "Lihat Riwayat" (keduanya sekadar anchor ke section yang sudah ada di halaman yang sama — bukan aksi). Tersisa **"Update Tahap"** (→ halaman manage tracking yang sudah ada). Aksi operasional lain (Input Pemeriksaan, Upload Dokumen) sengaja *belum* ditambahkan — flow-nya di luar scope freeze dan tidak boleh menambah logic baru.

2. **Dokumen → dokumen saja.** v1.0 menampilkan seluruh `deep_links`, termasuk **Voyage** & **Customer** yang bukan dokumen. Kini difilter ke ikon dokumen (`document-text`, `paper-clip`) → hanya **SPPB + Lampiran**, ditambah **Resi**. Voyage & Customer tetap tampil sebagai teks di Informasi Operasional (Perjalanan / Pengiriman), jadi tidak hilang.
   *Diverifikasi (unit 224): 4 deep_links → 2 dokumen (SPPB `0666/LOG-SBR/07/2026`, `1 Lampiran`); Voyage `312` & Customer `Toyota Astra Motor` dikeluarkan.*

3. **Bahasa Riwayat** → "Tahap **diperbarui** menjadi X" (menyesuaikan contoh audit).

Section lain (Hero, Timeline, Informasi, Pemeriksaan) sudah memenuhi kriteria audit di v1.0 → tidak diubah.

---

## Navigasi

```
Dashboard → Monitoring → [pilih unit] → slide-over ringkas
                                          └─ "Buka Workspace Lengkap" → Detail Unit Workspace → Update Tahap
```

Row-click Monitoring tetap membuka slide-over (workflow Monitoring beku, tidak disentuh). Workspace penuh dijangkau via link additif di slide-over. Mengubah row-click agar langsung ke workspace **sengaja tidak dilakukan** — itu perubahan workflow Monitoring yang di luar scope.

---

## Constraint yang Dihormati

Tidak ada perubahan: Business Logic · Tracking/Inspection/Timeline Engine · Query · Domain Model · Workflow. Tidak ada fitur/logic baru. Seluruh komponen memakai Design System beku (`.jss-detail-*`, `.mon-*`, `.jss-timeline`) — **tidak ada CSS/card/button/badge/table baru**. Data 100% dari `DetailUnitProvider` yang sudah ada.

---

## Deferral yang Diketahui (bukan bug)

- **Actor pada Riwayat** ("oleh Coordinator X") — tidak ada di `UnitDetailData`; menampilkannya butuh query baru → ditunda sesuai instruksi audit. Riwayat kini menampilkan perubahan tahap + waktu tanpa actor.
- **Aksi operasional** (Input Pemeriksaan, Upload Dokumen, Update Tracking) — hanya "Update Tahap" yang tertaut (ke halaman existing). Sisanya menyusul saat flow dibangun.
- **Tanggal di timeline partial** masih `format('d M Y')` (Inggris) karena partial dipakai bersama slide-over; tanggal milik workspace sendiri sudah `translatedFormat` (Indonesia). Konsistensi penuh = perbaikan partial bersama, di luar scope.

---

## Validasi

- `php -l` bersih; seluruh blade compile.
- Route terdaftar (`admin/shipment-trackings/unit/{unitId}`); `shipments.resi` ada.
- Smoke test provider (unit 224): `2607-0121 · Penjemputan · 11 Hari · Jakarta → Ternate · Laut`; 13 tahap timeline, 6 tahap inspeksi, filter dokumen benar.
- **Catatan:** environment `APP_ENV=production` — verifikasi visual (klik) belum dilakukan; disarankan uji di staging sebelum menjadikannya pusat aktivitas harian.

---

## Definition of Done — Terpenuhi

Coordinator membuka Detail Unit Workspace dan **dalam satu halaman scroll** (tanpa pindah tab) memahami: unit apa · kondisi saat ini · tahap berjalan · lama di tahap · voyage & ETA · riwayat aktivitas · dokumen tersedia · aksi berikutnya (Update Tahap).

→ **Detail Unit Workspace: UX ARCHITECTURE FROZEN.**
