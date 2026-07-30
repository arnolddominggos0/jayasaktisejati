# Sprint UNIT-01 — Complete Unit-Centric Update Status

**Status:** IMPLEMENTED & tervalidasi dengan data nyata.
**Tanggal:** 24 Juli 2026

---

## 1. Refactor Update Status Menjadi Unit-Centric

Modal "Update" pada Operational Tasks tidak lagi memanggil `ShipmentResource::trackUpdateForm()` + `ShipmentResource::inspectionStatusFields()` (yang menampilkan daftar seluruh unit dalam shipment). Sebagai gantinya, modal memanggil `OperationalTasks::unitTrackUpdateForm()` — form baru yang khusus dibangun untuk satu Unit, berisi hanya: info Unit, Status Berikutnya, dan Catatan — persis sesuai UI Requirement.

Validasi inspeksi kini dihitung terhadap **Unit yang diklik**, lewat method baru `OperationalTasks::unitInspectionIncomplete(Unit $record, ?string $stage)`, bukan lagi lewat partial `inspection-status-list` yang menampilkan status inspeksi seluruh unit shipment.

---

## 2. Pemisahan Form Unit dan Shipment

`ShipmentResource::trackUpdateForm()`, `::inspectionStatusFields()`, dan `::optionalChecksheetSchema()` **tidak diubah dan tidak dihapus** — dibiarkan sebagaimana adanya untuk workflow Admin/Shipment jika masih dibutuhkan di tempat lain (sesuai instruksi eksplisit "Tidak mengubah Shipment workflow yang digunakan oleh Admin jika memang masih diperlukan"). `OperationalTasks.php` **berhenti memanggil** method-method tersebut sama sekali, dan sekarang punya form sendiri:

- `ShipmentResource::trackUpdateForm()` → tetap khusus domain Shipment (Admin).
- `OperationalTasks::unitTrackUpdateForm()` → baru, khusus domain Unit (Field Coordinator).

Tidak ada satu form yang melayani dua domain sekaligus lagi.

---

## 3. File yang Diubah

| File | Perubahan |
|---|---|
| `app/Filament/FC/Pages/OperationalTasks.php` | Satu-satunya file yang diubah. Import `ShipmentResource` dihapus (tidak dipakai lagi di file ini). Import `HtmlString` ditambahkan. Dua method privat baru: `unitInspectionIncomplete()`, `unitTrackUpdateForm()`. Action `updateTrack`: `->form()` diganti memanggil `unitTrackUpdateForm()`, `->modalHeading('Update Status Unit')` ditambahkan, `->action()` ditambah satu pemeriksaan inspeksi per-unit sebelum `appendTrack()`. |

**Tidak ada file lain yang diubah.** `ShipmentResource.php`, `Shipment.php`, `UnitInspection.php`, `InspectUnitPage.php`, observer, notification — semuanya tidak disentuh sama sekali sprint ini (dikonfirmasi: tidak ada satu pun `Edit`/`Write` dijalankan terhadap file-file itu). Tidak ada migration.

---

## 4. Ringkasan Perubahan Teknis

### `unitTrackUpdateForm(): array` — form baru
6 field: `unit_display` (Placeholder — chassis + model unit), `track_status` (Hidden), `inspection_stage` (Hidden), `track_status_display` (Placeholder — label status berikutnya), `inspection_notice` (Placeholder, muncul hanya saat inspeksi unit belum selesai), `note` (Textarea). Tidak ada Repeater atau Section — dikonfirmasi lewat pengujian langsung terhadap schema-nya.

### `unitInspectionIncomplete(Unit $record, ?string $stage): bool`
Mengembalikan `false` bila `$stage` null (status tanpa tahap inspeksi). Selain itu, mencari `UnitInspection` milik **unit ini saja** (`where('unit_id', $record->id)`) untuk stage tersebut, dan mengembalikan `true` bila belum ada atau belum `is_finalized`. Dipakai di tiga tempat tanpa duplikasi logic: `visible()` dan `content()` Placeholder `inspection_notice`, serta di `action()` sebagai validasi sebelum `appendTrack()`.

### `fillForm()` — tidak berubah logic-nya
Tetap menghitung `nextTrackStatus()`/`resolveStage()` dan memanggil `InspectionDraftAutoCreate::ensureForShipmentAndStage()` — dipertahankan karena ini infrastruktur idempotent (memastikan draft inspeksi ada untuk seluruh unit shipment agar tombol "Inspeksi" di baris unit lain tetap berfungsi), bukan validasi/tampilan seluruh shipment yang dilarang UI Requirement.

### `action()` — satu penambahan
Setelah pemeriksaan Rack/Daily Briefing Gate yang sudah ada (tidak diubah), ditambahkan satu blok: jika `unitInspectionIncomplete($record, $data['inspection_stage'])`, tampilkan notifikasi dan hentikan proses — sebelum mencoba `appendTrack()`. Guard shipment-wide di dalam `appendTrack()` (`ensureHandoverInspectionCleared()`, dst., di `Shipment.php`) **tidak disentuh** dan tetap berjalan sebagai lapisan terakhir.

### Field yang dihapus dari form generik
`plan_loading_time_at`/`plan_closing_time_at` (khusus Handover — sudah dikumpulkan lengkap oleh action "Handover Depo" yang terpisah) dan `checkseet` (Repeater checklist unit — termasuk kategori "shipment checklist" yang eksplisit dilarang UI Requirement). Keduanya tetap diteruskan sebagai `null` ke `appendTrack()` lewat `$data[...] ?? null` yang sudah ada — pola yang sama seperti hampir seluruh action lain di file ini yang memanggil `appendTrack($status, $note)` tanpa parameter tambahan.

---

## 5. Alasan Desain

- **`unitTrackUpdateForm()` tanpa parameter `Unit $record`** — mengikuti pola `ShipmentResource::trackUpdateForm(): array` yang sudah ada (tanpa parameter, seluruh data dinamis diambil lewat closure `Get $get`/`Unit $record` yang di-inject Filament ke tiap field). Menjaga konsistensi gaya kode.
- **Status Berikutnya sebagai Placeholder (bukan Select)** — mockup UI Requirement menampilkan satu nilai tetap ("Pickup"), bukan dropdown. Aksi Hold/Batalkan sudah punya tombol tersendiri di grup "Aksi Status" dengan form masing-masing; `updateTrack` tidak perlu lagi menyediakan jalur alternatif ke status yang sama.
- **`ShipmentResource.php` tidak dihapus** — hanya berhenti dipanggil dari `OperationalTasks.php`. Menghapusnya adalah keputusan terpisah yang lebih besar dari cakupan sprint ini dan berisiko memutus jalur lain yang belum tentu tidak memakainya di masa depan; instruksi eksplisit ("jika memang masih diperlukan") mengarahkan untuk berhati-hati, bukan menghapus.
- **Pemeriksaan inspeksi ditambahkan sebagai lapisan UI, bukan mengganti guard** — Coding Rules melarang mengubah authorization/shipment tracking di luar kebutuhan. Guard `Shipment::runTransitionGuards()` tetap satu-satunya sumber kebenaran akhir; pemeriksaan baru murni memberi pesan yang lebih spesifik untuk unit yang sedang diklik, sebelum mencoba transisi.

---

## 6. Hasil Pengujian

| Uji | Hasil |
|---|---|
| `php -l` | ✅ Bersih |
| Schema `unitTrackUpdateForm()`: 6 field (unit_display, track_status, inspection_stage, track_status_display, inspection_notice, note) | ✅ |
| Tidak ada `Repeater`/`Section` (bekas daftar unit shipment) di schema | ✅ dikonfirmasi lewat inspeksi tipe komponen |
| `unitInspectionIncomplete(unit, null)` | ✅ `false` |
| `unitInspectionIncomplete(unit, 'pickup')` tanpa inspeksi | ✅ `true` |
| `unitInspectionIncomplete(unit, 'pickup')` dengan draft belum final | ✅ `true` |
| `unitInspectionIncomplete(unit, 'pickup')` setelah difinalisasi (submitted+signed) | ✅ `false` |
| End-to-end nyata: shipment #1 / unit #1, `nextTrackStatus=pickup`, stage=`pickup`, unit belum ada inspeksi final → `unitInspectionIncomplete=true` | ✅ sesuai kondisi data nyata |
| `OperationalTasks::getTableQuery()` tetap mengembalikan baris Unit tanpa error (regresi tabel) | ✅ |
| `migrate:status` — tidak ada migration baru dari sprint ini | ✅ |

### Regression
`isHandoverInspectionCleared()`/`isLoadingInspectionCleared()` terhadap shipment nyata: identik dengan seluruh baseline sprint-sprint sebelumnya (`false`/`false`) — `Shipment.php` tidak disentuh.

---

## 7. Konfirmasi: Tidak Ada Komentar Baru

Seluruh kode yang ditambahkan (`unitInspectionIncomplete()`, `unitTrackUpdateForm()`, penyesuaian `form()`/`action()` pada `updateTrack`, perubahan import) ditulis **tanpa satu baris komentar pun** — dikonfirmasi lewat pembacaan ulang penuh setiap blok yang diubah setelah implementasi selesai. Baris `// ── Table definition ──...` dan komentar serupa lain di sekitar kode yang diubah adalah komentar **project yang sudah ada sebelumnya**, tidak disentuh, bukan tambahan sprint ini.
