# Sprint CA-01.5 — Container Allocation Architecture Refactor Report

**Status:** ARCHITECTURE REFACTOR — kode ditulis & tervalidasi sintaks; **migrasi belum dijalankan** (blocker sama seperti CA-01, lihat §Risiko).
**Tanggal:** 23 Juli 2026
**Rujukan:** [`DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md`](DOMAIN-FREEZE-CONTAINER-ALLOCATION-WORKSPACE.md), [`SPRINT-CA-01-IMPLEMENTATION-REPORT.md`](SPRINT-CA-01-IMPLEMENTATION-REPORT.md)

---

## 1. Review Implementasi CA-01

CA-01 memperkenalkan tabel `containers` yang **diisi via `Container::syncFromReadiness()`** — sebuah *mirror massal*: setiap kali workspace dibuka, seluruh `container_numbers` dari Container Readiness disalin (firstOrCreate) menjadi baris `containers`.

**Diagnosis kritis terhadap 4 pertanyaan review:**

**Q1 — Apakah entity `Container` benar-benar diperlukan?**
**Sebagian.** Yang IRREDUCIBLE (tidak bisa dihilangkan) adalah **gerbang siap-stuffing** — keputusan eksplisit FC per-container yang murni milik Allocation dan tidak ada di domain mana pun. Ditambah **tipe/kapasitas** yang Readiness memang tidak simpan. Jadi *sebuah* record per-container milik Allocation diperlukan. Yang TIDAK diperlukan adalah record yang **mengklaim keberadaan** container — itu milik Readiness.

**Q2 — Apakah `syncFromReadiness()` sehat jangka panjang?**
**Tidak.** Ini akar masalahnya. Sync = menyalin fakta ("container X ada hari ini") ke tempat kedua → **dua source of truth untuk keberadaan container**. Rentan drift: bila Readiness mengubah daftarnya, baris `containers` lama menjadi salinan basi. Ini persis tension yang saya tandai sendiri di CA-01 §8.

**Q3 — Apakah relationship mencerminkan business process?**
Sebagian benar (Unit→Container unit-centric, tidak ke Shipment — bagus). Tapi arah *provenance* container salah: CA-01 memperlakukan `containers` seolah sumber, padahal operasionalnya container "lahir" di Readiness.

**Q4 — Boundary belum bersih?**
Ya. `syncFromReadiness` membuat Allocation **terlihat menciptakan** container. Melanggar prinsip "Allocation hanya membaca hasil domain sebelumnya."

**Kriteria user yang menentukan scope:** *"tidak boleh punya source data sendiri APABILA source tersebut berasal dari Container Readiness."* → Hanya **keberadaan container** yang berasal dari Readiness. **Tipe/kapasitas/gerbang** TIDAK berasal dari Readiness (Readiness tak punya), jadi menyimpannya di Allocation **bukan** pelanggaran SSOT menurut kriteria ini — meski idealnya kelak pindah ke hulu.

---

## 2. Keputusan: DIREFACTOR (fokus, minimal)

Bukan rombak total. Bedah tepat pada sumber duplikasi:

| Aspek | CA-01 (sebelum) | CA-01.5 (sesudah) |
|---|---|---|
| Keberadaan container | Disalin ke `containers` via sync massal (SSOT ganda) | **Dibaca langsung dari Readiness** (`container_number_list`). SSOT tunggal. |
| Baris `containers` | Mirror penuh semua nomor | **Anotasi SPARSE** — hanya untuk container yang benar-benar disentuh alokasi |
| Cara baris tercipta | `syncFromReadiness()` eager, tak tervalidasi | `resolveForSession()` **lazy + tervalidasi** (nomor wajib ada di Readiness, else ditolak) |
| FK sesi Readiness | nullable, `nullOnDelete` | **wajib**, `cascadeOnDelete` (anotasi tak bermakna tanpa sesinya) |
| Data yang disimpan | existence + type + capacity + gate | **hanya** type + capacity + gate (yang Readiness tak punya) |
| Rule "hanya container Readiness" | ditegakkan lewat salinan | ditegakkan **langsung dari SSOT** saat resolve |

---

## 3. Alasan · Dampak · Migration Plan · Backward Compatibility

**Alasan:** menghapus duplikasi source-of-truth keberadaan container + menghilangkan mekanisme sync yang rawan drift, sebelum Sprint Stuffing mewarisi technical debt ini.

**Dampak:** internal saja. **UX tidak berubah** (FC tetap melihat daftar container di kanan, interaksi sama). **Business Process tidak berubah**. 4 action tetap sama. Lifecycle Unit (3 status) tetap sama.

**Migration plan:** Tidak ada migrasi data. Migrasi `create_containers_table` **diedit di tempat** (FK sesi nullable→wajib, docblock) karena **belum pernah dijalankan** (diblokir sejak CA-01). Migrasi `units` (container_id, allocation_status) **tidak berubah**.

**Backward compatibility:** **N/A — tidak ada yang perlu kompatibel.** Skema CA-01 tidak pernah diterapkan ke database (migrasi diblokir). Jadi tidak ada data lama, tidak ada deployment lama. Saat migrasi akhirnya dijalankan, ia langsung menerapkan skema CA-01.5 — tidak ada state antara. Ini keuntungan besar: refactor dilakukan sebelum apa pun menyentuh produksi.

---

## 4. Diagram Architecture — SEBELUM (CA-01)

```
ContainerReadinessSession.container_numbers  ── (mirror massal) ──►  containers (tabel)
        │  "container X, Y, Z ada hari ini"      syncFromReadiness()      │  X, Y, Z (SALINAN)
        │  = SSOT keberadaan                                              │  + type, capacity, gate
        ▼                                                                 ▼
    (Readiness)                                                     Unit.container_id ──► containers
                                                                    ▲
                            ⚠ DUA source of truth untuk keberadaan container (Readiness & containers)
                            ⚠ sync = rawan drift; Allocation "terlihat membuat" container
```

## 5. Diagram Architecture — SESUDAH (CA-01.5)

```
ContainerReadinessSession.container_numbers   =  SSOT TUNGGAL keberadaan container
        │  "container X, Y, Z ada hari ini"       (dibaca langsung, tanpa salinan)
        │
        ├──────────── dibaca (existence) ───────────► Workspace menampilkan X, Y, Z
        │
        │        resolveForSession(sesi, "X")           containers (tabel) = ANOTASI SPARSE
        └── validasi "X ∈ daftar Readiness" ──────────►   hanya baris untuk container yang
                 (lazy, hanya saat disentuh)                disentuh; simpan HANYA type/capacity/gate
                                                                 ▲
                                                    Unit.container_id ──► containers (anotasi)

    ✔ satu SSOT keberapaan (Readiness). containers tidak pernah mengklaim keberadaan.
    ✔ tanpa sync; baris lazy & tervalidasi — mustahil ada container yang tak ada di Readiness.
```

---

## 6. Daftar File yang Berubah

| File | Perubahan |
|---|---|
| `app/Models/Container.php` | Hapus `syncFromReadiness()`; tambah `resolveForSession()` (lazy+tervalidasi); `filledCount()` guard `$exists` (aman untuk instance transient); docblock ditulis ulang (anotasi sparse, bukan SSOT keberadaan). |
| `database/migrations/2026_07_23_090000_create_containers_table.php` | FK `container_readiness_session_id` nullable→**wajib** + `cascadeOnDelete`; docblock diperbaiki. (Diedit di tempat — belum pernah dijalankan.) |
| `app/Filament/FC/Pages/ContainerAllocationWorkspace.php` | `getContainers()` baca Readiness (existence) + join anotasi sparse, **tanpa sync**; tambah `currentSession()` + `resolveContainer()`; semua action pakai `containerNo` (bukan id) + resolve lazy; `availableContainerOptions()` berbasis container_no. |
| `resources/views/filament/fc/pages/container-allocation-workspace.blade.php` | Loop container: pass `containerNo`; guard `$container->exists` sebelum akses relasi `units` (cegah query relasi pada instance transient). |

**Tidak berubah:** `ContainerAllocationService.php` (semua guard/rule tetap valid apa adanya), `Unit.php`, migrasi `units`, enum-enum, `ContainerReadinessSession` (hanya dibaca — nol tulisan).

---

## 7. Analisis Risiko

| Risiko | Tingkat | Mitigasi |
|---|---|---|
| Instance Container transient (unsaved) mengakses relasi `units()` → query dgn parent key null salah hitung | Sedang→**Rendah** | `filledCount()` guard `$exists`; blade guard `$isPersisted` sebelum akses `$container->units`. |
| Referensi container via string `container_no` (bukan FK) kurang kaku dibanding id | Rendah | Validasi ketat di `resolveForSession` (nomor wajib ∈ Readiness). Referensi FK sesungguhnya (`units.container_id`) tetap ada — string hanya untuk lookup di action layer. |
| Nomor container dobel di daftar Readiness → anotasi ambigu | Rendah | Unique `(session_id, container_no)`; `container_number_list` sudah `->unique()` di Readiness model. |
| **Belum diuji fungsional** (assign/move/mark) terhadap data nyata | **Sedang** | Migrasi masih diblokir (produksi). Sama seperti CA-01: hanya tervalidasi sintaks/blade/autoload. Uji fungsional menyusul setelah blocker DB dibereskan. |
| Gap tipe/kapasitas belum punya pemilik hulu | Rendah (diketahui) | Disimpan di Allocation sebagai interim, ditandai eksplisit untuk dipindah ke Readiness/container-master di sprint terpisah (lihat §8). |

---

## 8. Boundary yang MASIH Perlu Keputusan (diserahkan, bukan diputus sepihak)

Refactor ini menutup pelanggaran SSOT yang konkret (keberadaan container). Namun satu boundary **belum sepenuhnya bersih** dan sengaja saya biarkan terbuka karena menyentuh domain beku:

> **Tipe & kapasitas container** kini disimpan di anotasi Allocation. Itu bukan pelanggaran SSOT (Readiness tak punya data ini), TAPI secara domain, "spesifikasi fisik container" lebih tepat menjadi milik **Container Readiness** (atau container-master), bukan Allocation.

**Ideal jangka panjang:** Readiness menyimpan `{no, type, capacity}` per container (bukan hanya nomor). Maka Allocation cukup menyimpan **gerbang siap-stuffing** saja, dan anotasi `containers` menjadi sepenuhnya milik Allocation tanpa data spesifikasi. **Ini perubahan domain Readiness yang dibekukan → di luar scope sprint ini.** Saya tidak melakukannya; saya menandainya untuk keputusan tim (unfreeze Readiness singkat, atau terima interim ini sampai Sprint Readiness berikutnya).

---

## 9. Konfirmasi: Tidak Ada Perubahan Business Process / Domain Freeze

- **Business Process:** SPPB→OCR→Requirement→Readiness→Inspection→Allocation→Stuffing — **tidak disentuh**.
- **Domain Freeze Allocation:** satu pertanyaan ("unit masuk container mana"), Unit sebagai entity, 4 action, 3 status Unit — **semua identik**. Refactor hanya mengubah *dari mana keberadaan container dibaca*, bukan apa yang Allocation lakukan.
- **Readiness / Requirement / Inspection:** hanya dibaca, nol tulisan, nol perubahan skema.
- **UX:** identik — daftar container di kanan, 4 action yang sama, interaksi FC tidak berubah.

Refactor ini murni memindahkan SSOT keberadaan container ke tempat yang benar (Readiness) tanpa menambah fitur, mengubah alur, atau menyentuh domain lain.

---

## 10. Verifikasi & Blocker (sama seperti CA-01)

**Tervalidasi:** `php -l` bersih (3 file diubah); seluruh blade compile; `Container::syncFromReadiness` hilang & `resolveForSession` ada (reflection); semua class autoload.

**Belum tervalidasi:** uji fungsional nyata (assign/move/remove/mark) — **migrasi masih diblokir** karena `APP_ENV=production` + tugas verifikasi keamanan DB (`task_08720161`) belum selesai. Begitu blocker dibereskan, saya jalankan migrasi CA-01.5 (langsung, tanpa state antara) + uji fungsional penuh dan lampirkan hasilnya.
