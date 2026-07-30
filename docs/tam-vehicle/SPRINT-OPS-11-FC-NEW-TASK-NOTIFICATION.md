# Sprint OPS-11 — FC New Operational Task Notification

**Status:** IMPLEMENTED & tervalidasi end-to-end dengan data nyata + queue worker.
**Tanggal:** 24 Juli 2026
**Depends on:** OPS-10 (Daily Briefing Session) — tidak disentuh.

---

## 1. Business Rule yang Diterapkan

1. **Setiap Shipment yang berhasil dibuat → tepat 1 notifikasi operasional.** Dipicu dari model event `created` (sekali per insert), tidak pernah pada edit/delete.
2. **Recipient = hanya Field Coordinator yang bertanggung jawab atas depot shipment.** Super Admin / Office Admin / role lain **tidak pernah** menerima (dibatasi `User::role('field_coordinator')`).
3. **Bahasa operasional**, bukan istilah teknis. Judul "Pekerjaan Operasional Baru"; body menampilkan customer, jumlah unit, nomor SPPB, "Siap diproses." Tidak ada string "Shipment Created" di mana pun.
4. **Klik notifikasi → Operational Task** (`filament.fc.pages.operational-tasks`), bukan halaman Shipment.
5. **Mekanisme bawaan Filament database notification saja** — tanpa websocket/polling/push/email/whatsapp/sound/realtime toast.

---

## 2. File yang Berubah

| File | Perubahan |
|---|---|
| `app/Services/NewOperationalTaskNotifier.php` | **Baru.** Berisi seluruh LOGIKA: resolusi recipient FC per depot, penyusunan body bahasa operasional, dan `sendToDatabase()`. Satu tempat, dipanggil dari satu trigger. |
| `app/Observers/ShipmentObserver.php` | `created()` — ditambahkan **satu** blok: `DB::afterCommit(fn() => NewOperationalTaskNotifier::notifyForNewShipment($s))`. Tidak ada logika notifikasi di sini, hanya trigger. |

**Tidak ada migration, tidak ada perubahan schema, tidak ada file lain.** `migrate:status` terakhir tetap dari INS-03.

---

## 3. Workflow Sebelum vs Sesudah

### Sebelum
```
Admin → Input SPPB → Shipment dibuat → (TIDAK ada notifikasi)
                                        → FC harus buka halaman Tugas Operasional sendiri
```

### Sesudah
```
Admin → Input SPPB → Shipment dibuat (transaksi Filament: create + units + status Pending)
                     → COMMIT
                     → ShipmentObserver::created → DB::afterCommit
                     → NewOperationalTaskNotifier::notifyForNewShipment()
                     → 1 Filament DB notification (queued) untuk FC depot terkait
                     → FC melihat bell notification "Pekerjaan Operasional Baru"
                     → klik → halaman Tugas Operasional
```

---

## 4. Titik Pembuatan Notification (Single Trigger)

**`ShipmentObserver::created()` + `DB::afterCommit()`** — dipilih setelah menelusuri seluruh jalur create:

- Admin (`CreateShipment::handleRecordCreation`), FC panel, dan API **semuanya** melewati `Shipment::create()`/`->save()`, yang memicu model event `created` **tepat satu kali** per insert. Menaruh trigger di sini otomatis mencakup semua jalur tanpa menyebar logika (memenuhi "jangan meletakkan logika di banyak tempat") — berbeda dengan menaruhnya di satu Create page (akan miss jalur lain + menyalahi instruksi).
- `created` **tidak pernah** menyala pada update (`updated`) atau delete (`deleted`) — memenuhi "edit/delete tidak membuat notifikasi baru" secara struktural, bukan lewat pengecekan manual.

**Kenapa `afterCommit`, bukan langsung di `created`:** dikonfirmasi dari `vendor/.../CreateRecord::create()` bahwa Filament membungkus seluruh proses create dalam **satu transaksi DB** (`beginDatabaseTransaction` → `commitDatabaseTransaction`). Pada saat event `created` menyala, **unit belum ter-attach** (di `handleRecordCreation`, unit dibuat SETELAH `Shipment::create()`) dan status masih transisi ke Pending. `DB::afterCommit` menunda hingga transaksi commit, sehingga jumlah unit / customer / SPPB sudah final saat body disusun. (Dikonfirmasi framework: `afterCommit` menunda bila ada transaksi, jalan langsung bila tidak — `DatabaseTransactionsManager::addCallback`.)

---

## 5. Cara Menentukan Recipient FC

`NewOperationalTaskNotifier::resolveDepotFieldCoordinators()` — union dari tiga sinyal kepemilikan yang **persis mencerminkan `ShipmentOwnership::isOriginFC()`**, seluruhnya dibatasi role `field_coordinator`:

1. FC dengan `scope_unit_type = 'depot'` DAN `scope_unit_id = shipment.assigned_depot_id` (FC yang di-scope ke depot).
2. `Depot.coordinator_user_id` depot tersebut (koordinator depot).
3. `shipment.coordinator_id` (koordinator yang langsung ditugaskan — jalur legacy).

```php
User::role('field_coordinator')            // ⇐ menjamin BUKAN super_admin/office_admin
    ->where(function ($q) use ($depotId, $directIds) {
        $q->where(fn ($w) => $w->where('scope_unit_type','depot')->where('scope_unit_id',$depotId));
        if ($directIds) $q->orWhereIn('id', $directIds);   // depotCoordinator + shipment.coordinator_id
    })->get();
```

**Guard tanpa depot:** bila `assigned_depot_id` null (mis. shipment non-sea, yang memang tidak punya depot — `ShipmentObserver::saving` menull-kannya untuk non-sea), notifier langsung no-op. Bila tidak ada FC yang cocok, juga no-op (aman untuk seeding/import yang FC-nya belum di-scope).

---

## 6. Validasi Menggunakan Data Nyata

Dijalankan end-to-end: buat shipment test (transaksi, mirror persis `handleRecordCreation`: create → 2 unit → status Pending), **proses queue worker** (`queue:work database --stop-when-empty`), lalu inspeksi baris `notifications`, kemudian cleanup penuh.

**Notifikasi yang benar-benar tertulis ke tabel `notifications`:**
```
notifiable : App\Models\User #2 (FC Jakarta, roles=field_coordinator)
title      : Pekerjaan Operasional Baru
body       : Toyota Astra Motor
             2 Unit
             SPPB
             OPS11-TEST/07/2026
             Siap diproses.
icon       : heroicon-o-clipboard-document-list
opens      : operational-tasks  (bukan halaman shipment)
```

| Acceptance criterion | Hasil |
|---|---|
| Shipment dibuat → notifikasi otomatis dibuat | ✅ 1 job ter-push saat create, tertulis 1 baris notifikasi setelah worker jalan |
| Hanya FC depot terkait yang menerima | ✅ notifiable #2 FC Jakarta, role=field_coordinator |
| Tidak bocor ke super_admin/office_admin | ✅ dibatasi `role('field_coordinator')` |
| Muncul di bell notification Filament | ✅ baris tertulis di tabel `notifications` (sumber bell Filament) |
| Bahasa operasional | ✅ "Pekerjaan Operasional Baru", tanpa "Shipment Created" |
| Menampilkan customer + jumlah unit + No. SPPB | ✅ Toyota Astra Motor / 2 Unit / OPS11-TEST/07/2026 |
| Klik → Operational Task | ✅ url `operational-tasks`, bukan `resources/shipments` |
| Edit shipment tidak membuat notifikasi | ✅ edit → 0 job ter-push |
| Delete shipment tidak membuat notifikasi | ✅ delete → 0 job ter-push |

**Cleanup diverifikasi:** setelah validasi, `notifications`=0, `jobs`=0, shipment test terhapus, DB kembali ke kondisi semula.

**Catatan mekanisme (jujur):** `Filament\Notifications\DatabaseNotification implements ShouldQueue` dan `QUEUE_CONNECTION=database`, jadi `sendToDatabase()` **mengantre** penulisan (baris muncul saat queue worker memproses job) — ini **persis** perilaku listener notifikasi yang sudah ada (`NotifyAdminOnShipmentUpdate`), bukan sesuatu yang baru diperkenalkan sprint ini. Di produksi worker berjalan kontinu, jadi notifikasi muncul di bell dalam hitungan detik. (Selama validasi, worker dijalankan manual `--stop-when-empty` untuk membuktikan baris benar-benar tertulis.)

---

## 7. Regression Check

| Area dilarang diubah | Status |
|---|---|
| Shipment Workflow | ✅ `Shipment.php` tidak disentuh; hanya `ShipmentObserver::created` +1 blok afterCommit |
| OCR SPPB (IntakePrefill) | ✅ tidak disentuh |
| Operational Task | ✅ `OperationalTasks.php` tidak disentuh sprint ini |
| Briefing (OPS-10) | ✅ `DailyBriefingGate`/briefing tidak disentuh |
| Inspection | ✅ tidak disentuh |
| ShipmentTrack | ✅ tidak disentuh |
| Transition Guard | ✅ `Shipment::runTransitionGuards()` tidak disentuh |
| MP Readiness | ✅ tidak disentuh |

### OPS-08 baseline
```
Shipment #1 (JSS0726SH0001): handoverCleared=false, loadingCleared=false
```
Konsisten dengan seluruh baseline sebelumnya — `Shipment.php` (Guard) tidak disentuh.

> **Catatan lingkungan (bukan disebabkan kode sprint ini):** database `jss_db` tampak **di-seed ulang secara eksternal** di antara sprint OPS-10 dan OPS-11 — kini berisi 1 shipment (#1 `JSS0726SH0001`), sebelumnya 8 (#228–235). Seluruh script validasi OPS-11 **membersihkan dirinya sendiri** (diverifikasi: kembali ke 1 shipment, 0 notifikasi, 0 job). Reset itu **bukan** dari kode/skrip sprint ini.

---

## 8. Konfirmasi: Notifikasi Hanya Dibuat Sekali Saat Shipment Pertama Kali Dibuat

- Trigger = model event **`created`** — secara definitif menyala **tepat sekali** per baris shipment yang di-insert, oleh Eloquent, tidak peduli jalur create-nya.
- **`updated`** (edit) dan **`deleted`** (delete) adalah event terpisah yang **tidak** memanggil notifier — dibuktikan empiris: edit → 0 job, delete → 0 job.
- `DB::afterCommit` didaftarkan sekali di dalam `created`, sehingga tidak ada penggandaan.
- Save internal setelahnya (`forceFill(['status'=>Pending])->saveQuietly()` di Admin flow) memakai `saveQuietly()` yang **tidak** memicu event apa pun → tidak ada notifikasi kedua.

**Kesimpulan:** tepat 1 notifikasi per shipment baru, nol pada edit, nol pada delete — terpenuhi secara struktural (sifat model event) dan terbukti secara empiris.
