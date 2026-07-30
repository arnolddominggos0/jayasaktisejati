# Sprint OPS-09 — Unit-Centric Operational Task (Phase 1)

**Status:** IMPLEMENTED & tervalidasi penuh, termasuk regression INS-03/INS-04/OPS-08.
**Tanggal:** 24 Juli 2026

---

## 1. File yang Berubah

| File | Perubahan |
|---|---|
| `app/Filament/FC/Pages/OperationalTasks.php` | Ditulis ulang total: `getTableQuery()` beralih dari `Shipment::query()` ke `Unit::query()`; seluruh 23 Action (`updateTrack` + 20 aksi status di grup "Aksi Status" + 3 aksi cetak) diretipe dari `Shipment $record` menjadi `Unit $record`; 1 Action baru ditambahkan (`inspeksi`); kolom tabel disusun ulang mengikuti struktur unit-sentris. |

**Tidak ada file lain yang diubah.** `app/Models/Shipment.php`, `app/Models/UnitInspection.php`, `app/Filament/FC/Pages/InspectUnitPage.php`, `app/Filament/FC/Resources/ShipmentResource.php` — semuanya **tidak disentuh** (Scope 6). Tidak ada migration baru — `migrate:status` terakhir tetap dari INS-03.

---

## 2. Query Lama vs Query Baru

### Lama (Shipment-centric)
```php
Shipment::query()
    ->select('shipments.*')
    ->addSelect($this->unitStatusSubqueries())   // 4 subquery agregat per shipment
    ->where('mode', 'sea')
    ->whereNotIn('status', ['draft', 'delivered', 'cancelled'])
    ->with(['voyage', 'voyage.vessel', 'units:id,shipment_id', 'latestTrack', 'customer'])
    ->where(function ($outer) { /* ARM A/B/C: ownership + phase */ })
    ->orderByDesc(/* tracked_at shipment terakhir */);
```
Satu baris = satu Shipment. Filter kepemilikan (ARM A/B/C) langsung terhadap kolom Shipment (`assigned_depot_id`, `coordinator_id`, `pod_id`, dst.).

### Baru (Unit-centric)
```php
Unit::query()
    ->select('units.*')
    ->with(['shipment', 'shipment.voyage', 'shipment.voyage.vessel', 'shipment.latestTrack', 'shipment.customer'])
    ->whereHas('shipment', function ($s) {
        $s->where('mode', 'sea')
            ->whereNotIn('status', ['draft', 'delivered', 'cancelled'])
            ->where(function ($outer) { /* ARM A/B/C — PERSIS SAMA, hanya dibungkus whereHas */ });
    })
    ->orderByDesc(/* tracked_at shipment terakhir, dikorelasikan lewat units.shipment_id */);
```
Satu baris = satu Unit. **Filter ARM A/B/C tidak diubah satu kondisi pun** — hanya dipindah ke dalam `whereHas('shipment', ...)` karena kolom-kolom itu (`assigned_depot_id`, dst.) adalah milik Shipment, bukan Unit. `unitStatusSubqueries()` (4 subquery agregat) **dihapus** — lihat §3 untuk alasannya.

**Bukti kesetaraan filter:** diuji terhadap data nyata — FC pemilik shipment #229 (coordinator_id match) menghasilkan tepat 13 Unit, cocok 100% dengan hitungan manual `Unit::whereHas('shipment', fn($s) => ...)` yang sama.

---

## 3. Struktur Tabel Sebelum & Sesudah

### Sebelum (12 kolom, 1 baris = 1 Shipment)
Gate · Shipment (code) · Pengirim · Status · Tahap · Menunggu Inspeksi (count) · Unit Bermasalah (count) · Readiness (%) · Container (status) · Voyage · ETA · Unit (count) · Diperbarui

### Sesudah (10 kolom, 1 baris = 1 Unit)
Gate · **No. SPPB** *(kolom pertama, Scope 2)* · Shipment (code, internal) · **Unit** (Chassis No + Model) · **Customer** · Status · **Tahap** *(Scope 3: "Tahap Operasional Saat Ini")* · Voyage · ETA · Diperbarui

**Kolom yang DIHAPUS dari versi lama:** *Menunggu Inspeksi*, *Unit Bermasalah*, *Readiness %*, *Container* — 4 kolom ini adalah **agregat per-shipment** (mis. "% unit yang lulus inspeksi di shipment ini") yang tidak punya padanan 1:1 yang jelas untuk satu baris = satu unit tanpa membuat keputusan bisnis baru (mis. apakah "Unit Bermasalah" sekarang berarti boolean per unit, dan apakah itu funsgi status yang sama atau berbeda). **Keputusan sprint ini: dihapus, bukan ditransformasi**, sesuai instruksi "Tidak ada perubahan database maupun business rule" dan Scope 6 (jangan mengubah Workflow). Kolom *Unit (count)* juga dihapus karena tidak relevan lagi — setiap baris sudah tepat satu unit.

**Kolom yang DITAMBAH:** *No. SPPB* (Scope 2, wajib pertama), *Unit* (Chassis No + Model, Scope 3), *Customer* (Scope 3 — sebelumnya berlabel "Pengirim", disamakan namanya dengan istilah di brief).

### Action — TIDAK ADA yang dihapus (Scope 4)
Seluruh 23 action lama (`updateTrack`, `toPending`, `startPickup`, `handover`, `planningLoading`, `stuffing`, `stuffingViaAppSheet`, `deliveryToPort`, `stacking`, `unitLoadingAuto`, `unitLoadingInfo`, `onShip`, `vesselDepart`, `vesselArrival`, `unloading`, `handoverTrucking`, `deliveryToCustomer`, `markDelivered`, `hold`, `cancel`, `viewDetail`, `printWaybill`, `printPackingList`, `printResi`) **tetap ada, dengan guard/kondisi/logic identik** — hanya mengambil `$shipment = $record->shipment` lebih dulu (karena `$record` sekarang Unit), lalu memanggil persis method yang sama (`appendTrack()`, `ShipmentOwnership::canEdit()`, dst.) terhadap `$shipment`.

**1 action baru ditambahkan: `inspeksi`.** Sebelumnya, membuka inspeksi hanya bisa lewat daftar unit di dalam modal "Update" (`ShipmentResource::inspectionStatusFields()` — **masih ada, tidak dihapus**). Karena satu baris sekarang sudah tepat satu unit, ditambahkan tombol langsung "Inspeksi" per baris yang menuju `InspectUnitPage` (Single Entry Point INS-04, **tidak disentuh**) — visibility-nya meniru persis logic `InspectUnitPage::resolveStage()` (current lalu fallback next) supaya tombol tidak pernah menuju 404. Ini murni kemudahan navigasi yang jadi masuk akal karena bentuk baris berubah, **bukan business rule baru**.

---

## 4. Dampak Performa (Eager Loading)

Eager load: `shipment`, `shipment.voyage:id,voyage_no,eta`, `shipment.voyage.vessel:id,name`, `shipment.latestTrack`, `shipment.customer:id,name` — mencakup seluruh relasi yang dibaca kolom maupun action (Scope 7).

**Diuji dengan `DB::enableQueryLog()` terhadap 13 Unit nyata** (shipment coordinator sungguhan, bukan simulasi):
```
Query untuk fetch 13 unit + seluruh eager load: 5 query
Setelah itu, mengakses shipment/voyage/vessel/customer/latestTrack untuk SETIAP baris: 0 query tambahan
```
**Tidak ada N+1** — 5 query total untuk seluruh halaman terlepas dari jumlah baris (akan tetap 5 query untuk 13 unit maupun 130 unit, karena eager load bukan per-baris).

Dibandingkan versi lama: versi lama melakukan 1 query utama + 4 subquery SQL mentah TERKORELASI PER BARIS (`unitStatusSubqueries()` — dieksekusi sebagai bagian SELECT, bukan N+1 dalam pengertian Eloquent, tapi tetap 4× SELECT-scalar-subquery per baris di level database). Versi baru **menghapus subquery-subquery itu** (kolom agregat terkait juga dihapus, §3) — net performa per baris kemungkinan **lebih ringan**, bukan lebih berat, meski jumlah baris fisik bertambah (satu shipment ber-N unit kini N baris, bukan 1).

---

## 5. Regression

### INS-03 (Finalization/Approval/Signature)
`InspectUnitPage.php` **tidak disentuh** sprint ini. `submit()`, validasi PIC/Jabatan/Signature, gate decision, lock, audit log — semuanya identik.

### INS-04 (Single Entry Point)
Diverifikasi ulang: hanya `InspectUnitPage.php` yang menulis data inspeksi di seluruh aplikasi — **tidak berubah**. Action baru `inspeksi` di `OperationalTasks.php` hanya MENAUTKAN ke halaman itu (URL), tidak menduplikasi logic apa pun. Diuji langsung: `InspectUnitPage::mount()` dipanggil dengan `(shipment_id, unit_id)` yang PERSIS sama dengan yang dihasilkan URL action baru — berhasil, resolve ke inspection #280 (sama seperti pengujian sprint-sprint sebelumnya), `isReadOnly=false`.

### OPS-08
```
Shipment #228 (JSS0726SH0001):         handoverCleared=false, loadingCleared=false
Shipment #229 (JSS0726SH0002):         handoverCleared=false, loadingCleared=false
Shipment #230 (OPS08-TRIAL-154113):    handoverCleared=false, loadingCleared=false
Shipment #231 (OPS08-NEG-154323):      handoverCleared=false, loadingCleared=false
Shipment #232 (OPS08-CAP-154323):      handoverCleared=false, loadingCleared=false
Shipment #233 (OPS08-CAPFIX-A-154435): handoverCleared=false, loadingCleared=false
Shipment #234 (OPS08-CAPFIX-B-154436): handoverCleared=false, loadingCleared=false
Shipment #235 (JSS0726SH0003):         handoverCleared=false, loadingCleared=false
```
**Identik byte-for-byte** dengan seluruh baseline sebelumnya — `Shipment.php` (tempat Guard hidup) tidak pernah disentuh sprint ini.

---

## 6. Temuan Penting — "No. SPPB" (Scope 2)

**Tidak ada kolom `sppb_no` terpisah di skema saat ini** (diverifikasi lewat pencarian menyeluruh di seluruh migration). Field yang paling dekat maknanya adalah `shipments.doc_number` — tapi field ini **generik**, bukan SPPB murni:

| `request_type` | Isi `doc_number` |
|---|---|
| `sppb_do` | Nomor SPPB **asli** (dikonfirmasi data nyata: shipment #229/#235 berisi `"0666/LOG-SBR/07/2026"` / `"0654/LOG-SBR/07/2026"`) |
| `wa_telp` / `walk_in` | Placeholder auto-generate `"AUTO-{tanggal}-{jam}"` — **BUKAN nomor SPPB**, karena shipment jenis ini memang tidak lahir dari dokumen SPPB |

Kolom "No. SPPB" pada tabel baru menampilkan `doc_number` apa adanya sesuai instruksi sprint. Untuk shipment yang bukan `sppb_do` (mis. order lewat WA/telepon), kolom ini akan menampilkan teks `"AUTO-..."`, bukan nomor SPPB sungguhan — ini **bukan bug**, murni cerminan bahwa shipment tersebut memang tidak punya SPPB. Direkomendasikan untuk didiskusikan di Phase 2 apakah perlu pembedaan visual (mis. label berbeda) untuk shipment non-SPPB — **tidak diubah sekarang** untuk menghormati "Sprint ini murni perubahan query dan presentasi".

---

## 7. Keputusan Scope Lain yang Perlu Diketahui (untuk Phase 2)

1. **4 kolom agregat dihapus** (§3) — jika masih dibutuhkan dalam bentuk per-unit (mis. "unit ini bermasalah: ya/tidak"), perlu keputusan produk eksplisit di sprint terpisah (bukan sekadar "perubahan query dan presentasi").
2. **`ShipmentTrack` tetap shipment-level** (Scope 6, tidak diubah) — artinya kolom "Status"/"Tahap" akan SAMA untuk semua baris unit milik satu shipment yang sama. Ini konsisten dengan arsitektur saat ini, bukan bug.
3. Action grup "Aksi Status" (Update/Handover/Stuffing/dst.) tetap beroperasi per-SHIPMENT, bukan per-unit — mengklik dari baris unit mana pun pada shipment yang sama akan memicu transisi yang identik untuk seluruh shipment itu, sesuai Scope 5.

---

## Konfirmasi Batas

- ✅ Satu baris = satu Unit (Scope 1) — diverifikasi terhadap data nyata (13 unit, bukan 6 shipment).
- ✅ Kolom pertama No. SPPB (Scope 2) — dengan catatan sumber data pada §6.
- ✅ Informasi unit minimal (No. SPPB, Chassis, Model, Customer, Tahap) tampil tanpa membuka Shipment (Scope 3).
- ✅ Seluruh action tetap berjalan, guard tetap sama, tidak ada workflow baru (Scope 4+6).
- ✅ Shipment tetap parent, relasi tidak diubah (Scope 5).
- ✅ Query eager-load penuh, 0 N+1 terbukti lewat pengujian nyata (Scope 7).
- ✅ Tidak ada perubahan `Shipment.php`, `UnitInspection.php`, `InspectUnitPage.php`, Guard, atau Database.
