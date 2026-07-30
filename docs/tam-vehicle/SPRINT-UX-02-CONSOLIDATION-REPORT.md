# Sprint UX-02 — Integrate Container Allocation into Container Readiness

**Status:** ARCHITECTURE + UX CONSOLIDATION — perubahan aman diterapkan; **satu kontradiksi domain disurfacekan untuk keputusan Anda** (§6); **migrasi masih diblokir** (§10).
**Tanggal:** 23 Juli 2026
**Rujukan:** [`SPRINT-CA-01.5-REFACTOR-REPORT.md`](SPRINT-CA-01.5-REFACTOR-REPORT.md), [`UX-FREEZE-FC-WORKSPACE.md`](UX-FREEZE-FC-WORKSPACE.md)

---

## ⚠️ Ringkasan Eksekutif — baca ini dulu

Konsolidasi UX yang diminta **masuk akal dan sebagian besar sudah didukung arsitektur** (tabel `containers` sudah ber-FK ke `container_readiness_session_id` sejak CA-01). Tiga hal:

1. **Menu "Alokasi Container" dihapus** dari sidebar ✔ (perubahan aman, diterapkan).
2. **Stuffing sudah murni execution** ✔ — `LoadingSession` tidak punya logic alokasi apa pun; tidak ada yang perlu dihapus.
3. **SATU KONTRADIKSI DOMAIN muncul dan HARUS Anda konfirmasi:** Business Process baru menaruh Alokasi **di dalam Readiness pagi hari, SEBELUM Inspection**. Ini **membalik** ordering yang sebelumnya dibekukan ("alokasi SETELAH inspeksi") dan **memaksa penghapusan gate inspeksi**. Saya lakukan relaksasi gate yang new-BP haruskan, tapi menandainya keras di sini karena ini mengubah domain yang dulu di-freeze. Lihat §6.

Saya **tidak** membangun wizard 4-langkah penuh di resource Readiness — alasan di §10 (arsitektur & skema belum bisa diuji; membangun UI besar yang tak teruji di atas kontradiksi domain yang belum Anda putuskan = tidak bertanggung jawab). Rencana build-nya dispesifikasikan lengkap di §3 untuk dieksekusi setelah blocker & keputusan §6 beres.

---

## 1. Review Architecture Setelah Consolidation

Bagian yang menganggap Allocation sebagai module sendiri, dan statusnya:

| Bagian | Sebelum | Sesudah |
|---|---|---|
| `ContainerAllocationWorkspace` (FC page) | Menu sidebar "Alokasi Container" (sort 11) | **`shouldRegisterNavigation = false`** — halaman tetap routable (dicapai dari alur Readiness), tapi bukan menu. Konsisten dgn `ContainerReadinessSessionResource` yang juga `false`. |
| Skema `containers` | Sudah ber-FK `container_readiness_session_id` (CA-01) | **Tidak berubah** — ternyata sudah "anak" dari Readiness. Konsolidasi = natural, bukan pemindahan data. |
| `LoadingSession` (Stuffing) | Tanpa logic alokasi | **Tetap** tanpa logic alokasi — sudah murni execution. |
| Gate inspeksi di Allocation | Wajib handover_depot lolos (CA-01.5) | **Direlaksasi** (lihat §6) — konsekuensi BP baru. |

**Kesimpulan:** secara data, allocation SUDAH tergantung pada Readiness session (FK). Konsolidasi sesungguhnya adalah soal **UX/navigasi/entry-point**, bukan re-arsitektur data.

---

## 2. Perubahan UX & 3. Perubahan Navigation

**Diterapkan sekarang:**
- Sidebar FC tidak lagi menampilkan "Alokasi Container". Allocation berhenti menjadi tujuan mandiri.

**Rencana entry-point (belum dibangun — lihat §10):** Container Readiness (dicapai via Monitoring Operasional) menjadi alur 4-langkah tunggal. Karena `containers.container_readiness_session_id` sudah ada, langkah 3–4 (tipe/kapasitas + alokasi) tinggal ditautkan sebagai kelanjutan dari langkah 1–2 (data readiness + input nomor container yang SUDAH ada di form resource).

**Target alur (spesifikasi build):**
```
Container Readiness (satu sesi FC):
  Step 1  Review Requirement (read-only)     ← dari Requirement Planning
  Step 2  Input nomor container tersedia     ← SUDAH ADA: Repeater container_numbers
  Step 3  Lengkapi tipe & kapasitas          ← CA-01.5: configureContainer (anotasi Container)
  Step 4  Alokasi Unit → Container           ← CA-01.5: assign/move/remove
  Submit  → Readiness "selesai" bila 4 langkah tuntas (§ status di bawah)
```
Langkah 3–4 memakai **logic yang sudah ada** (service + page CA-01.5) tanpa perubahan aturan; yang berubah hanyalah *tempat masuk*-nya.

---

## 4. Daftar File yang Berubah

| File | Perubahan |
|---|---|
| `app/Filament/FC/Pages/ContainerAllocationWorkspace.php` | `shouldRegisterNavigation = false` (hapus dari menu); relaksasi query `getUnallocatedUnits()` (tidak lagi wajib inspeksi lolos — hanya kecualikan return_to_pdc). |
| `app/Services/ContainerAllocation/ContainerAllocationService.php` | `isUnitEligible()` direlaksasi (tidak wajib handover inspeksi; hanya tolak return_to_pdc); pesan guard diperbarui. |

**Tidak berubah:** `Container.php`, migrasi, `Unit.php`, `ContainerReadinessSessionResource`, `LoadingSession` — semua tetap. Logic alokasi (assign/move/remove/mark) **utuh**.

---

## 5. Business Rule yang DIPERTAHANKAN

- ✅ **Unit hanya boleh satu container** — FK tunggal `units.container_id`, ditegakkan penuh.
- ✅ **Container tidak boleh melebihi kapasitas** — `guardCapacity()` tetap.
- ✅ **Mapping Unit → Container adalah output** — tetap output utama, kini bagian dari "hasil Readiness".
- ✅ **Hanya container hasil Readiness yang dipakai** — `resolveForSession()` validasi ke SSOT (CA-01.5).
- ✅ **Unit return_to_pdc tidak masuk plan** — invarian ini tetap.

## 6. Business Rule yang DIHAPUS/DIRELAKSASI — ⚠️ PERLU KONFIRMASI ANDA

- ❌ **"Hanya unit yang LOLOS Inspeksi Handover yang bisa dialokasikan"** → **direlaksasi menjadi** "unit yang BELUM ditandai Return to PDC".

**Kenapa:** BP baru (hasil validasi ulang) menaruh Alokasi **di Container Readiness pagi hari, SEBELUM Inspection**. Pada saat itu inspeksi belum terjadi — jadi mewajibkan "inspeksi lolos" akan **memblokir seluruh alokasi** (tidak ada unit yang lolos di pagi hari). Gate lama tidak kompatibel dengan ordering baru.

**Yang harus Anda sadari & konfirmasi:**
Ini **membalik ordering yang sebelumnya DIBEKUKAN**. Dokumen freeze terdahulu (`DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md` §-Business-Process) menyatakan tegas: *"Container Allocation adalah proses yang terjadi SETELAH Inspection"* dan *"hanya unit yang lolos (accept/allow_with_remark) yang boleh muncul di pool ini."* Sprint ini menempatkannya **sebelum** Inspection.

Saya **tidak memutuskan ini sepihak** — saya menerapkan relaksasi yang new-BP haruskan agar fitur bisa berjalan, TAPI ini menyentuh domain beku. **Mohon konfirmasi salah satu:**
1. **Ya, ordering memang berubah** (alokasi = planning provisional pagi hari, inspeksi mengonfirmasi belakangan; unit yang gagal inspeksi dikeluarkan dari plan kemudian). → relaksasi ini benar; saya perbarui dokumen freeze lama agar konsisten. **(Ini interpretasi yang paling masuk akal & selaras dgn model "alokasi provisional sampai stuffing" yang sudah divalidasi.)**
2. **Tidak, inspeksi tetap prasyarat** → maka Alokasi TIDAK bisa berada di Readiness pagi (harus tetap setelah Inspection), dan konsolidasi UX ini perlu dipikir ulang.

Sampai Anda pilih, saya menahan diri dari mem-freeze ulang ordering.

---

## 7. Diagram Workflow — SEBELUM

```
Container Readiness (menu tersembunyi) ─── data harian + input nomor container
        (tidak menyentuh alokasi)

Alokasi Container (MENU TERPISAH) ─────── Unit → Container
        gate: WAJIB lolos Inspeksi Handover

Inspection ──► Stuffing
```

## 8. Diagram Workflow — SESUDAH (target)

```
Container Readiness (satu sesi, dicapai via Monitoring):
   Step1 Requirement(RO) → Step2 Input Container → Step3 Tipe&Kapasitas → Step4 Alokasi Unit→Container → Submit
        (langkah 3–4 = logic CA-01.5, tanpa menu terpisah)
        gate: hanya kecualikan Return to PDC (inspeksi belum terjadi)
        ↓
Inspection (mengonfirmasi kondisi; unit gagal → dikeluarkan dari plan)
        ↓
Stuffing = EKSEKUSI plan (baca-only): tampilkan isi container, konfirmasi pemuatan fisik
        — TIDAK memilih/memindahkan unit, TIDAK membuat plan
        ↓
Loading
```

---

## 9. Konfirmasi: Stuffing = Murni Execution Workspace

**Sudah terpenuhi tanpa perubahan.** `LoadingSession` (domain Stuffing) diperiksa: **nol** fungsi alokasi/pemilihan/pemindahan unit (grep `container_id|allocat|assign|pilih unit|select unit` = kosong). Stuffing memang hanya: cek kesiapan MP/APD/rack, keputusan final, pemuatan fisik. Deliverable #7 & #9 terpenuhi apa adanya — tidak ada fungsi allocation yang perlu dicabut dari Stuffing.

---

## Status "Container Readiness Selesai" (deliverable #6-status)

Definisi baru output Readiness = **Container + Planning Unit→Container**. "Selesai" bila:
- ✓ nomor container diinput (ada di `container_numbers`)
- ✓ tiap container dipakai punya tipe & kapasitas (anotasi Container terisi)
- ✓ tiap unit (non-return_to_pdc) sudah dialokasikan (`units.container_id` terisi)

**Catatan:** kriteria "selesai" ini adalah **derivasi baca** (bisa dihitung dari data yang sudah ada) — belum saya jadikan status tersimpan/enforced, karena itu menyentuh definisi "selesai" pada model Readiness (butuh keputusan §6 dulu, dan idealnya diuji). Dispesifikasikan, belum di-hard-code.

---

## 10. Yang Sengaja BELUM Dibangun + Blocker

- **Wizard 4-langkah terpadu di resource Readiness** — TIDAK dibangun sprint ini. Alasan: (a) skema CA-01/CA-01.5 **belum pernah dimigrasikan** (diblokir, `APP_ENV=production`), jadi UI apa pun tak bisa diuji fungsional; (b) ada **kontradiksi domain terbuka (§6)** yang mengubah bentuk gate — membangun wizard besar di atas keputusan yang belum Anda ambil = berisiko rework. Spesifikasi build lengkap ada di §3/§8 dan siap dieksekusi begitu §6 diputuskan & migrasi bisa jalan.
- **Entry-point link Readiness→Alokasi** — dispesifikasikan, belum diwiring (butuh edit page resource yang tak bisa diuji).
- **Migrasi & uji fungsional** — tetap terblokir (sama seperti CA-01/CA-01.5).

**Tervalidasi sekarang:** `php -l` bersih (2 file); nav workspace = tidak terdaftar; Stuffing = nol alokasi (grep). **Belum tervalidasi:** perilaku fungsional (blocker migrasi).

---

## Konfirmasi Batas

- **Tidak menambah fitur** — hanya menghapus menu + merelaksasi gate + review.
- **Business Process:** yang berubah adalah ordering (per BP baru yang Anda tetapkan), bukan invensi saya — tapi ordering itu **berkonflik dengan freeze lama**, disurfacekan di §6 untuk keputusan.
- **Domain lain (Requirement/Readiness data/Inspection/Stuffing):** tidak disentuh logic-nya.
