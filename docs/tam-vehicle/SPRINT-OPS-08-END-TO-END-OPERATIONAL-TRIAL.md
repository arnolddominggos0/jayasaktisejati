# Sprint OPS-08 — End-to-End Operational Trial

**Status:** SELESAI — trial nyata dijalankan penuh terhadap `jss_db` (izin eksplisit Anda), memakai 1 shipment vehicle trial khusus + 1 shipment negative-test, tidak mengganggu 2 shipment asli yang sudah ada.
**Tanggal:** 23 Juli 2026
**Data trial:** dibiarkan apa adanya di database sesuai instruksi Anda (tidak dihapus) — shipment utama `OPS08-TRIAL-154113` (#230), catatan pada setiap record eksplisit menyebut "OPS-08 TRIAL"/"NEGATIVE TEST"/"capacity-guard supplementary test" agar mudah dikenali dan dipisahkan dari data nyata kapan pun.

---

## Ringkasan Eksekutif

**36 dari 36 langkah fungsional PASS. Nol bug aplikasi ditemukan.** Satu bug murni di script trial saya sendiri (PHP tidak mengizinkan enum sebagai array key) — ditemukan, diperbaiki, tidak menyebabkan kerusakan data apa pun (gagal sebelum sempat menjalankan aksi apa pun). Satu gap cakupan uji negatif (guard kapasitas tertutupi oleh guard lock container yang lebih dulu berlaku) — diidentifikasi jujur, langsung ditutup dengan uji tambahan terisolasi yang mengonfirmasi guard kapasitas bekerja benar.

---

## Tabel Deliverable

| Step | Result | Evidence |
|---|---|---|
| **Step 1 — Container Readiness** | 🟢 PASS | Session hari ini (#147) sudah ada, `summary_sufficient=true`, `container_available=4`, `container_need=4`, container service "regular" terdaftar |
| **Step 2 — Planning Loading** | 🟢 PASS | `ContainerAllocationWorkspace` menampilkan unit trial di pool unallocated; `Container::resolveForSession()` → Container #1 (TAKU123456); `assign()` → `unit.container_id=1`, `allocation_status=in_container`; `markContainerReady()` → `is_ready_for_stuffing=true`, `allocation_status=ready_for_stuffing` |
| **Step 3 — Stuffing** | 🟢 PASS | `StuffingWorkspace` preconditions **ok=true** (shipment_active, briefing_done, mp_ready, container_readiness_done, container_available — semua true); `markUnitStuffed()` → `stuffed_at`/`stuffed_by=2`/`stuffing_remarks` terisi; Container auto-complete → `stuffing_status=full`; `shipmentStuffingSummary()` → **state=ready_loading** (pertama kali dalam sejarah sistem ini derived state ini benar-benar tercapai) |
| **Step 4 — ShipmentTrack (11 transisi)** | 🟢 PASS | Pickup→Handover→Stuffing→DeliveryToPort→Stacking→UnitLoading→OnShip→VesselDepart→VesselArrival→Unloading→HandoverTrucking→DeliveryToCustomer→Delivered — **seluruhnya berhasil**, `shipment.status` akhir = `delivered` |
| **Step 5 — Loading** | 🟢 PASS | `isRackShipment()=false`, `LoadingSession` **tidak** dibuat — sesuai temuan Audit OPS-07 (jalur Regular murni transisi Track) |
| **Step 6 — Audit Konsistensi** | 🟢 PASS* | `shipment.status=delivered`; `unit.allocation_status=stuffed`; `container.stuffing_status=full`; kedua inspeksi (handover_depot, loading) `passed/accept`. *Catatan: audit saya sendiri memunculkan "hold"/"cancelled" di `track_sequence` — ini BUKAN bug, itu baris skeleton (`tracked_at=null`) yang dibuat `ensureTrackSkeleton()` untuk seluruh kemungkinan status, tidak pernah benar-benar ditandai. Query saya lupa filter `whereNotNull('tracked_at')` — kesalahan pelaporan saya, bukan temuan sistem.* |
| **Step 7 — Log Verification** | 🟢 PASS | 13 baris log `SHIPMENT TRANSITION GUARD` ditemukan untuk shipment #230 — setiap baris mencatat kelima guard dievaluasi (`guardInvalidStatusTransition`, `ensureHandoverInspectionCleared`, `ensureContainerAssigned`, `ensureLoadingInspectionCleared`, `ensureLoadingSessionCompleted`), status `passed`, `result=allowed` — log ARCH-01 Scope 6 terbukti bekerja & informatif |
| **Step 8 — Negative Test** | 🟢 PASS (5/5 + 1 uji tambahan) | Lihat tabel detail di bawah |

### Detail Negative Test

| Skenario | Hasil | Pesan Penolakan |
|---|---|---|
| Stuffing tanpa container | ✅ Ditolak | "Unit ini belum dialokasikan ke container mana pun — Stuffing tidak membuat rencana baru." |
| Loading sebelum Stuffing (skip langsung ke UnitLoading) | ✅ Ditolak | "Status hanya dapat dilanjutkan ke tahap berikutnya secara berurutan. Status berikutnya yang diharapkan: Penjemputan." |
| Assign container melebihi kapasitas (percobaan pertama) | ⚠️ Ditolak, tapi oleh guard LAIN | "Container ini sudah ditandai Siap Stuffing" — container sudah ter-lock dari Step 2e, sehingga guard lock bicara duluan, bukan guard kapasitas. **Gap cakupan uji, bukan bug** — ditutup dengan uji tambahan di bawah. |
| Stuffing dua kali (unit sama) | ✅ Ditolak | "Unit ini sudah ditandai selesai stuffing sebelumnya." |
| Loading dua kali | ✅ Ditolak | "Tidak dapat mengubah status ke tahap sebelumnya atau yang sudah pernah dicapai." |
| **[Uji tambahan]** Assign melebihi kapasitas pada container yang BELUM di-lock (isolasi guard kapasitas) | ✅ Ditolak, guard yang benar | "Container TAKU2123 sudah penuh (1 unit). Pilih container lain." — `guardCapacity()` dikonfirmasi bekerja benar. |

---

## Bug/Temuan

**Tidak ada bug aplikasi ditemukan.** Satu catatan proses:

- **Lokasi:** `storage/app/ops08-trial.php` (script trial saya sendiri, bukan kode aplikasi).
- **Severity:** N/A (bug tooling trial, bukan bug produk).
- **Reproducible steps:** Menulis `[TrackStatus::Enum => 'label']` sebagai array asosiatif — PHP tidak mengizinkan instance enum sebagai array key.
- **Root cause:** Kesalahan penulisan script saya, bukan kode aplikasi.
- **Dampak:** Nol — script gagal SEBELUM baris pertama loop transisi dieksekusi, tidak ada write yang sempat terjadi akibat bug ini. Diperbaiki (diubah jadi array list `[status, label]`), trial dilanjutkan dari state yang sama persis tanpa mengulang langkah yang sudah sukses.

---

## Final Verdict

# 🟢 READY FOR PILOT

**Bukti pendukung:**
1. Seluruh 8 Step di brief sprint dijalankan dengan data nyata (bukan simulasi/mock), terhadap `jss_db`, memakai kode aplikasi yang sesungguhnya (`ContainerAllocationService`, `StuffingService`, `Shipment::appendTrack()`, halaman Workspace) — bukan bypass.
2. Pipeline lengkap Pickup → ... → Delivered berhasil **untuk pertama kalinya** menggunakan mesin terstruktur penuh (Container Allocation + Stuffing) sejak seluruh sprint fondasi (CA-01 s/d DB-01) dibangun.
3. `shipmentStuffingSummary()` mencapai state `ready_loading` untuk pertama kalinya dalam sejarah sistem ini — bukti bahwa DB-01 (aktivasi skema) benar-benar menyelesaikan masalah yang ditemukan.
4. Log audit ARCH-01 (`SHIPMENT TRANSITION GUARD`) terbukti berfungsi dan informatif secara real — bisa dipakai untuk investigasi produksi nyata kapan pun dibutuhkan.
5. Seluruh 5 negative test inti + 1 uji tambahan (total 6) berhasil ditolak dengan pesan yang jelas dan tepat — tidak ada "silent success" pada aksi yang seharusnya diblokir.
6. Dua shipment asli (`JSS0726SH0001`, `JSS0726SH0002`) **tidak tersentuh sama sekali** oleh trial ini.

**Catatan untuk pilot sesungguhnya (bukan blocker, murni pengingat arsitektural yang sudah diketahui dari sprint-sprint sebelumnya):**
- Trial ini memakai jalur `container_display` (legacy) untuk memenuhi gate Stuffing yang MASIH hidup hari ini (ARCH-02 belum mengubahnya, disengaja) — operator pilot akan tetap perlu mengisi `container_display` di modal Handover SEPERTI BIASA, di samping memakai Container Allocation Workspace. Ini bukan bug, ini realita arsitektur dua-jalur yang sudah didokumentasikan lengkap di ARCH-02/DATA-02.

---

## Konfirmasi Batas

- ✅ Tidak membuat fitur baru, tidak refactor, tidak migration, tidak redesign.
- ✅ Tidak mengubah business rule atau Transition Guard — trial murni MEMANGGIL kode yang sudah ada.
- ✅ Ditemukan 1 hal untuk dilaporkan (gap cakupan uji kapasitas) — dilaporkan dan ditutup dengan uji tambahan, **tidak** memperbaiki kode aplikasi apa pun (sesuai instruksi "jangan langsung memperbaiki").
- ✅ Data trial dibiarkan di database sesuai instruksi eksplisit Anda, dengan label jelas di setiap catatan agar mudah dikenali/dipisahkan dari data nyata.
