# Sprint ARCH-01 — Centralize Shipment Transition Guards

**Status:** IMPLEMENTED — tervalidasi struktural penuh (php -l, autoload, reflection, table-schema build, diff-verifikasi manual logika). **Satu langkah validasi (cross-check baca-saja terhadap data nyata) diblokir oleh auto-mode classifier** karena constraint DB produksi yang belum terverifikasi, konsisten dengan batasan yang berlaku sepanjang sesi ini — lihat §Validasi.
**Tanggal:** 23 Juli 2026
**Rujukan:** `AUDIT-SHIPMENTTRACK-TRANSITION-GATES.md` (temuan yang menjadi dasar sprint ini)

---

## Ringkasan Eksekutif

Audit sebelumnya menemukan gate `->visible()` untuk transisi Stuffing/DeliveryToPort(rack) menduplikasi logic yang SEHARUSNYA berada di domain layer, dan bahwa jalur `updateTrack` generik bisa melewati cek itu sama sekali. Investigasi lebih dalam untuk sprint ini menemukan sesuatu yang penting: **`Shipment::appendTrack()` SUDAH punya pola guard terpusat yang benar** (`ensureHandoverInspectionCleared()`, `ensureLoadingInspectionCleared()`, `ensureLoadingSessionCompleted()`) — tiga dari lima business rule SUDAH tersentralisasi sejak sebelum sprint ini. **Hanya SATU guard yang bermasalah: `ensureContainerAssigned()` — sudah ditulis lengkap, tapi SELURUH isinya di-comment (no-op total)**, sehingga satu-satunya penegakan aturan "container harus diassign sebelum Stuffing" hanya ada di UI (`unassigned_container_count`), sepenuhnya bisa dilewati lewat `updateTrack`.

Sprint ini: (1) mengaktifkan kembali guard yang sudah ditulis tapi mati itu, (2) mengekspos SEMUA guard bisnis sebagai method publik read-only yang bisa ditanya UI (menghilangkan duplikasi logic), dan (3) menambahkan audit log sementara di `appendTrack()`.

---

## Scope 1 — Klasifikasi `->visible()` di OperationalTasks.php

| Predikat | Klasifikasi | Tindakan |
|---|---|---|
| `latest_track_status === X` (di semua Action) | **B — Pure UI** ("status sebelumnya") | Tidak disentuh — sesuai Scope 4 |
| `ShipmentOwnership::canEdit()` | **B — Pure UI** (authorization) | Tidak disentuh |
| `LoadingSessionAutoCreate::isRackShipment()` | **B — Pure UI** (klasifikasi/routing varian tombol, BUKAN keputusan boleh/tidak — penegakan sesungguhnya sudah di `ensureLoadingSessionCompleted()`) | Tidak disentuh |
| `waiting_inspection_count` / `bermasalah_count` (raw SQL, dipakai di 3 tempat: `stuffing`, `stuffingViaAppSheet`, `deliveryToPort` rack-branch) | **A — Business Rule** (duplikat dari `ensureHandoverInspectionCleared()` yang sudah ada) | **Dipindah** — sekarang memanggil `Shipment::isHandoverInspectionCleared()` |
| `unassigned_container_count` (raw SQL, dipakai di 2 tempat: `planningLoading`, `stuffing`) | **A — Business Rule** (satu-satunya penegakan nyata, karena guard domain-nya mati) | **Dipindah** — sekarang memanggil `Shipment::isContainerAssignmentComplete()`, dan guard domainnya **diaktifkan kembali** |

Kolom TextColumn ("Menunggu Inspeksi", "Bermasalah", dst.) yang JUGA membaca raw SQL yang sama **tidak disentuh** — itu murni tampilan informasi/laporan untuk operator, bukan gate keputusan, di luar cakupan sprint ini (Out of Scope: "Tidak mengubah UX").

---

## Scope 2 — Perubahan Domain Layer (`app/Models/Shipment.php`)

1. **`handoverInspectionBlockReason(): ?string`** (baru, protected) — logic diekstrak VERBATIM dari `ensureHandoverInspectionCleared()` (3 validasi, urutan sama, pesan sama persis) — dulu langsung `throw`, sekarang `return` alasan atau `null`.
2. **`isHandoverInspectionCleared(): bool`** (baru, public) — `return $this->handoverInspectionBlockReason() === null;`. Inilah yang sekarang dipanggil UI.
3. **`ensureHandoverInspectionCleared()`** — disederhanakan jadi wrapper: cek kondisi gate (Stuffing non-rack / DeliveryToPort rack) lalu `throw` kalau `handoverInspectionBlockReason()` mengembalikan alasan. **Tidak ada perubahan perilaku.**
4. **`containerAssignmentBlockReason(): ?string`** (baru, protected) — logic diambil VERBATIM dari kode yang sebelumnya di-comment di `ensureContainerAssigned()`.
5. **`isContainerAssignmentComplete(): bool`** (baru, public).
6. **`ensureContainerAssigned()`** — **DIAKTIFKAN KEMBALI** (sebelumnya 100% comment/no-op). Sekarang benar-benar men-throw `DomainException` kalau ada unit vehicle-cargo yang belum punya `container_display` saat transisi ke `TrackStatus::Stuffing`.
7. **`runTransitionGuards(TrackStatus $status): void`** (baru, protected) — satu titik yang menjalankan seluruh 5 guard (`guardInvalidStatusTransition`, `ensureHandoverInspectionCleared`, `ensureContainerAssigned`, `ensureLoadingInspectionCleared`, `ensureLoadingSessionCompleted`) **dalam urutan yang SAMA PERSIS dan short-circuit-pada-kegagalan-pertama yang SAMA PERSIS** seperti lima pemanggilan berurutan yang sebelumnya ada langsung di `appendTrack()`. Ini murni relokasi struktural, plus logging (lihat Scope 6).
8. **`appendTrack()`** — lima baris `$this->ensure*()`/`guardInvalidStatusTransition()` berurutan diganti satu baris `$this->runTransitionGuards($status)`.

**Tidak ada guard baru yang diciptakan.** `ensureLoadingInspectionCleared()` dan `ensureLoadingSessionCompleted()` **tidak disentuh sama sekali** — keduanya sudah benar sejak awal (contoh nyata pola yang ditiru sprint ini).

---

## Scope 3 — Konsistensi Antar Jalur

Karena `runTransitionGuards()` dipanggil dari DALAM `appendTrack()` (bukan dari `->action()` closure masing-masing tombol), dan `updateTrack` (modal generik) **juga** memanggil `appendTrack()` di ujung actionnya — kelima guard, termasuk `ensureContainerAssigned()` yang baru diaktifkan, **otomatis berlaku untuk SEMUA jalur** (`Shortcut Action`, `Update Track`, dan API/automation apa pun di masa depan yang memanggil `appendTrack()`) tanpa perlu menyentuh jalur-jalur itu satu per satu. Ini secara struktural menjamin "keputusan identik" — bukan janji yang perlu dijaga manual, tapi konsekuensi otomatis dari satu titik masuk.

**⚠️ Satu perubahan perilaku yang disengaja, perlu Anda ketahui secara eksplisit:** sebelum sprint ini, jalur `updateTrack` generik BISA memindahkan shipment ke `TrackStatus::Stuffing` walau ada unit vehicle-cargo yang belum diassign container (karena `ensureContainerAssigned()` mati total) — sekarang TIDAK BISA lagi (akan menerima `DomainException`, ditampilkan sebagai notifikasi). **Ini bukan efek samping tak disengaja** — ini persis tujuan eksplisit Scope 3 sprint ini (seluruh jalur harus identik). Perilaku tombol shortcut "Stuffing & Segel" **tidak berubah sama sekali** (sudah selalu digerbangi kondisi yang sama, sekarang lewat method bersama alih-alih SQL terpisah).

---

## Scope 4 — UI Hanya Bertanya, Tidak Lagi Mendefinisikan

Di `OperationalTasks.php`, 5 titik `->visible()` diubah dari membaca kolom raw-SQL langsung menjadi memanggil method domain:

```php
// SEBELUM (contoh, action 'stuffing')
if (((int) ($record->waiting_inspection_count ?? 0)) > 0) return false;
if (((int) ($record->bermasalah_count ?? 0)) > 0) return false;
...
if ($isVehicle && ((int) ($record->unassigned_container_count ?? 1)) > 0) return false;

// SESUDAH
if (! $record->isHandoverInspectionCleared()) return false;
...
return $record->isContainerAssignmentComplete();
```

`latest_track_status`, `ShipmentOwnership::canEdit()`, `isRackShipment()` **tetap di UI** — sesuai Scope 4, ini bukan business rule ("status sebelumnya", "authorization", "routing varian UI").

---

## Scope 5 — Tidak Ada Perubahan Workflow

Tidak ada `TrackStatus` baru, tidak ada enum baru, tidak ada tabel baru. Urutan Pickup→...→Delivered persis sama. `guardInvalidStatusTransition()` (yang menegakkan urutan) tidak disentuh sama sekali.

---

## Scope 6 — Audit Log Sementara

`runTransitionGuards()` mencatat SETIAP evaluasi guard (baik lolos maupun gagal) via `Log::info('SHIPMENT TRANSITION GUARD', [...])`, berisi: `shipment_id`, `shipment_code`, `requested_transition`, `current_status` (sebelum transisi), `guards_evaluated` (map nama guard → "passed" atau "failed: <pesan>"), `failed_guard`, `result` (`allowed`/`blocked`). Log ini **hanya mengamati** — tidak pernah mengubah hasil guard (dijalankan di dalam `try/catch` yang sama, exception yang sama tetap dilempar ulang persis). Ditandai eksplisit di komentar kode sebagai sementara dan aman dihapus setelah divalidasi.

---

## Validasi

| Uji | Hasil |
|---|---|
| `php -l` pada `Shipment.php` dan `OperationalTasks.php` | ✅ Bersih |
| `composer dump-autoload` | ✅ Sukses |
| `php artisan view:cache`/`view:clear` | ✅ Sukses (tidak ada blade yang disentuh sprint ini, dijalankan untuk memastikan tidak ada kerusakan tak terduga) |
| Reflection: seluruh method baru/di-refactor ada dengan visibility yang benar (`isHandoverInspectionCleared`/`isContainerAssignmentComplete` PUBLIC; `*BlockReason`/`runTransitionGuards`/`ensure*` PROTECTED) | ✅ |
| Verifikasi manual baris-per-baris: logic yang diekstrak ke `handoverInspectionBlockReason()`/`containerAssignmentBlockReason()` dibandingkan dengan kode ASLI (sebelum edit, masih ada di riwayat percakapan) | ✅ Identik — hanya `throw` diganti `return`, urutan/isi/pesan tidak berubah |
| `OperationalTasks::table()` dibangun penuh (schema-only, tanpa binding record/tanpa query DB) — seluruh 24 action (termasuk `stuffing`, `deliveryToPort`, `planningLoading` dengan `->visible()` yang sudah diubah) terdaftar tanpa error | ✅ |
| **Cross-check baca-saja terhadap data Shipment/UnitInspection nyata** (membandingkan `isHandoverInspectionCleared()` dengan query independen) | ❌ **Diblokir oleh auto-mode classifier** — alasan: baca langsung ke database produksi yang belum terverifikasi, tanpa persetujuan eksplisit Anda yang menyebut target produksi. Tidak dicoba dilewati. |

**Yang BELUM tervalidasi:** perilaku fungsional sungguhan terhadap data nyata (baik guard yang diaktifkan kembali maupun kesesuaian hasil `isHandoverInspectionCleared()`/`isContainerAssignmentComplete()` dengan data produksi sesungguhnya). Kombinasi `php -l` + reflection + verifikasi manual baris-per-baris memberi keyakinan tinggi bahwa logic-nya benar (karena murni ekstraksi/relokasi, bukan penulisan ulang), tapi ini bukan pengganti uji terhadap data nyata. **Jika Anda ingin saya menjalankan cross-check baca-saja itu, mohon konfirmasi eksplisit** (mis. menyebut nama database/target) agar classifier mengizinkannya.

---

## Konfirmasi Batas

- ✅ Tidak ada guard baru yang diciptakan — `ensureContainerAssigned()` mengaktifkan kembali kode yang SUDAH ditulis, bukan aturan baru.
- ✅ Tidak mengubah ActionGroup — jumlah, urutan, dan struktur Action di `OperationalTasks.php` sama persis; hanya ISI closure `->visible()` yang diubah.
- ✅ Tidak mengubah Workspace, tidak mengubah ShipmentTrack (skema/status), tidak membuat fitur baru.
- ⚠️ **Satu perubahan perilaku disengaja** (dijelaskan §Scope 3) — jalur `updateTrack` generik sekarang ikut menegakkan aturan container-assignment yang sebelumnya hanya ada di UI. Ini adalah tujuan eksplisit sprint, bukan efek samping — tapi dicatat dengan jelas sesuai prinsip "jangan diam-diam mengubah perilaku."

Tidak ada titik yang perlu dihentikan implementasinya — seluruh business rule yang ditemukan berhasil dipindahkan tanpa perlu mengubah domain melampaui apa yang sudah ditulis sebelumnya.
