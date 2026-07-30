# Sprint OPS-11A — Notification Architecture Audit Report

**Status:** AUDIT SELESAI. Tidak ada kode aplikasi yang diubah (sesuai constraint sprint).
**Tanggal:** 28 Juli 2026
**Tipe sprint:** 100% audit & dokumentasi — dasar untuk implementasi OPS-11B.

---

## Ringkasan Eksekutif

Backend notification **memang sudah dibangun dan sudah benar** (OPS-11, Workflow Step 3.1, dan bugfix terkait — lihat §10). Bell notification di panel FC (`->databaseNotifications()`) sudah aktif, recipient resolution sudah benar, dan isi notifikasi sudah sesuai spesifikasi bisnis.

**Root cause paling mungkin dari laporan "FC tidak melihat notifikasi saat Office Admin membuat Shipment baru" bukan di kode notifikasi itu sendiri, melainkan di infrastruktur queue:**

> `Filament\Notifications\DatabaseNotification` **implements `ShouldQueue`**. `QUEUE_CONNECTION=database` (`.env:49`). Artinya setiap `sendToDatabase()` **tidak menulis baris ke tabel `notifications` secara langsung** — ia mendorong job ke tabel `jobs`, dan baris `notifications` baru tertulis setelah sebuah **queue worker** (`php artisan queue:work`) memprosesnya.
>
> Audit tidak menemukan satu pun mekanisme yang menjalankan queue worker sebagai proses berjalan-terus di deployment ini: tidak ada `Procfile`, tidak ada file `supervisor*.conf`, dan `app/Console/Kernel.php` / `routes/console.php` **tidak menjadwalkan `queue:work` atau `queue:listen`** (hanya ada `vessel-check:sync` per jam dan `shipments:send-eta-notifications` per menit — keduanya command lain, tidak memproses queue `jobs`).
>
> Ketiga dokumen sprint sebelumnya (OPS-11, Workflow Step 3.1, Bugfix Notification API) **seluruhnya memvalidasi perilaku dengan menjalankan `queue:work --stop-when-empty` secara manual** selama testing — bukan bukti bahwa worker berjalan otomatis di production. Jika di production tidak ada proses queue worker yang hidup terus-menerus, setiap job notifikasi akan **menumpuk tak terproses di tabel `jobs`** dan FC tidak akan pernah melihatnya di bell — persis gejala yang dilaporkan.

Ini adalah **temuan #1** dari audit ini dan harus diverifikasi operasional (cek `SELECT COUNT(*) FROM jobs` dan apakah proses `queue:work`/Supervisor benar-benar berjalan di server) sebelum OPS-11B mulai membangun UI apa pun. Detail lengkap di §9 (Gap Analysis) dan §11 (Rekomendasi).

---

## 1. Notification Inventory

| Mekanisme | Ditemukan? | Detail |
|---|---|---|
| Laravel native `Illuminate\Notifications\Notification` classes | ❌ Tidak ada | `app/Notifications/` tidak berisi kelas notification native. |
| **Filament Database Notification** (`Filament\Notifications\Notification::sendToDatabase()`) | ✅ Ada, 2 titik pemakaian | [`NewOperationalTaskNotifier.php`](../../app/Services/NewOperationalTaskNotifier.php), [`NotifyAdminOnShipmentUpdate.php`](../../app/Listeners/NotifyAdminOnShipmentUpdate.php) |
| **Filament Toast Notification** (`->send()`, transient, session-based) | ✅ Ada, dipakai luas | 40+ lokasi di halaman/resource Filament untuk feedback UI (lihat §6) — **bukan** bagian dari sistem notifikasi antar-user, murni UX form. |
| Event | ✅ 1 event | `App\Events\ShipmentStatusUpdated` |
| Listener | ✅ 1 listener | `App\Listeners\NotifyAdminOnShipmentUpdate` (terdaftar di `EventServiceProvider`) |
| Observer yang memicu notifikasi | ✅ 1 dari 6 observer | `ShipmentObserver::created()` → `NewOperationalTaskNotifier` |
| Broadcast (websocket/Pusher/Echo) | ❌ Tidak ada | Tidak ada `config/broadcasting.php`, tidak ada channel, tidak ada `ShouldBroadcast` di manapun. |
| Queue | ✅ Ada, tapi implisit | `QUEUE_CONNECTION=database`; `Filament\Notifications\DatabaseNotification` mengimplementasikan `ShouldQueue` sehingga `sendToDatabase()` **selalu** melewati queue — bukan pilihan sadar developer aplikasi, melainkan perilaku bawaan package. |
| Custom Notification Service | ✅ 1 | `App\Services\NewOperationalTaskNotifier` (bukan Laravel service/repository pattern formal, hanya static method class) |
| Notification Repository | ❌ Tidak ada | Tidak ada abstraksi repository; akses notifikasi seluruhnya lewat trait `Notifiable` bawaan Laravel (`$user->notifications`, `$user->unreadNotifications`). |
| Notification Widget (Filament Widget) | ❌ Tidak ada | Digrep di seluruh `app/Filament/**/Widgets/*.php` — tidak satu pun widget menampilkan notifikasi/badge unread. |
| Bell Notification — **FC Panel** | ✅ Aktif & fungsional | `FieldCoordinatorPanelProvider.php:39` → `->databaseNotifications()`. Ini memakai komponen resmi Filament v3 (`Filament\Panel\Concerns\HasNotifications`), bukan implementasi kustom. |
| Bell Notification — **Admin Panel** | ⚠️ Dead code (placeholder visual) | `resources/views/filament/topbar/actions.blade.php` — ikon lonceng statis (`heroicon-o-bell`), **tidak terhubung** ke tabel `notifications`, tidak ada badge, tidak ada dropdown, tidak bisa diklik untuk membuka apa pun. Lihat §8. |
| Bell Notification — Customer / CMS Panel | ❌ Tidak ada | `->databaseNotifications()` tidak pernah dipanggil di `CustomerPanelProvider` maupun `CmsPanelProvider`. |
| Email Notification (channel terpisah) | ✅ Ada, mekanisme berbeda | `App\Console\Commands\SendShipmentEtaNotifications` → `Mail::send(new ShipmentEtaReminderMail)`, dicatat di tabel custom `shipment_email_notifications`. **Tidak terhubung** ke bell/database notification sama sekali — jalur terpisah total. |
| WhatsApp | ❌ Bukan notification, murni formatter | `App\Services\WhatsappMessageBuilder` hanya menyusun teks pesan untuk dibagikan manual (voyage schedule); tidak mengirim apa pun secara otomatis, tidak terhubung ke sistem notifikasi. |

---

## 2. Trigger Inventory

| Trigger | Notification Class/Mechanism | Recipient | Status |
|---|---|---|---|
| **Shipment Created** | `NewOperationalTaskNotifier::notifyForNewShipment()` via `Notification::make()->sendToDatabase()` | Field Coordinator pemilik depot asal (`role('field_coordinator')` + scope depot / koordinator depot / `shipment.coordinator_id`) | **Active** (kode benar; **kirim tertunda bila queue worker tidak berjalan** — lihat Ringkasan Eksekutif) |
| **Shipment Assigned** (ke koordinator tertentu) | ❌ Tidak ada trigger terpisah | — | **Not implemented.** Penetapan `coordinator_id`/`assigned_depot_id` terjadi diam-diam di `ShipmentObserver::saving()`/`tryAssignDepot()` — tidak memicu notifikasi apa pun sendiri (hanya notifikasi "Shipment Created" yang menyusul bila ini kebetulan create pertama). |
| **Shipment Status Updated** (generik, dari Admin Edit Shipment) | `NotifyAdminOnShipmentUpdate` listener via `ShipmentStatusUpdated` event | Seluruh user `role('super_admin')` | **Active**, tapi trigger-nya lebih luas dari namanya — event ditembak di **setiap** `afterSave()` pada `EditShipment` (Admin panel), bukan hanya saat field `status` benar-benar berubah, dan **hanya** dari jalur Admin `EditShipment` (edit dari FC panel / API / observer lain tidak memicu ini). |
| **Pickup Completed** | ❌ Tidak ada | — | **Not implemented.** `ShipmentTrackObserver` mencatat seluruh perubahan status track ke activity log (`app/Observers/ShipmentTrackObserver.php`), tapi tidak pernah memanggil notifikasi apa pun. |
| **Stuffing** | ❌ Tidak ada | — | **Not implemented.** `LoadingSessionObserver::updated()` mengotomasi transisi status track terkait stuffing, tapi tidak mengirim notifikasi. |
| **Vessel Arrival** | ❌ Tidak ada | — | **Not implemented.** Tidak ditemukan observer/listener/command yang mengirim notifikasi terkait kedatangan kapal. (`vessel-check:sync` command hanya menyinkronkan data jadwal, tidak mengirim notifikasi ke user manapun — perlu verifikasi terpisah bila diperlukan.) |
| **Delivery Complete** | ❌ Tidak ada | — | **Not implemented.** Tidak ada trigger notifikasi untuk status pengiriman selesai. |
| **Shipment ETA Reminder** (trigger tambahan, di luar tabel brief) | `SendShipmentEtaNotifications` command → `Mail::send()` | Branch users + customer users (email, bukan bell) | **Active** — dijadwalkan `everyMinute()` di `routes/console.php` (dijalankan oleh Laravel Scheduler, bukan queue). Jalur data & channel terpisah total dari bell notification (lihat §4). |

**Catatan penting:** Dari 6 trigger operasional yang dibrief, hanya **1 dari 6** ("Shipment Created") yang benar-benar memiliki implementasi notifikasi backend. "Shipment Assigned", "Pickup Completed", "Stuffing", "Vessel Arrival", dan "Delivery Complete" **tidak memiliki mekanisme notifikasi apa pun** di codebase saat ini — bukan "inactive" dalam arti dimatikan, melainkan **belum pernah dibangun sama sekali**.

---

## 3. Delivery Flow Mapping

### Alur 1 — Shipment Created (yang relevan dengan bug yang dilaporkan)

```
Office Admin membuat Shipment (CreateShipment / handleRecordCreation)
        │
        ▼
Shipment::create()  →  model event `created`  (Eloquent, tepat 1x per insert)
        │
        ▼
ShipmentObserver::created()
        │
        ▼
DB::afterCommit(fn () => NewOperationalTaskNotifier::notifyForNewShipment($s))
        │  (menunggu transaksi DB commit dulu, supaya unit/customer/SPPB sudah final)
        ▼
NewOperationalTaskNotifier::resolveDepotFieldCoordinators()
        │  (query User::role('field_coordinator') dengan scope depot)
        ▼
Filament\Notifications\Notification::make()->sendToDatabase($recipients)
        │
        ▼
Notification::sendToDatabase()  →  foreach ($users) $user->notify($this->toDatabase())
        │
        ▼
   *** Illuminate Notifiable::notify() ***
        │
        ▼
   DatabaseNotification implements ShouldQueue
        │  → job di-dispatch ke QUEUE_CONNECTION=database
        ▼
   Tabel `jobs`  ◄── ⚠️  TITIK RAWAN: baris menumpuk di sini SAMPAI diproses worker
        │
        ▼  (hanya terjadi jika queue worker aktif memproses)
   Tabel `notifications`  (baris baru ditulis, notifiable_type=User, read_at=NULL)
        │
        ▼
   Filament Bell (topbar FC panel, `databaseNotifications()`)
        │  → membaca `auth()->user()->unreadNotifications()`, polling 30 detik (default Filament)
        ▼
   FC melihat "Pekerjaan Operasional Baru" di bell, klik → redirect ke
   `filament.fc.pages.operational-tasks` + `markAsRead()`
```

**Halaman Operational Tasks itu sendiri (`OperationalTasks.php`) TIDAK membaca notifikasi apa pun** — ia murni menampilkan daftar shipment/unit aktif lewat query langsung ke tabel `shipments`/`units` (lihat §6). Jadi "FC tidak melihat notifikasi di halaman Operational Tasks" bisa berarti dua hal berbeda yang perlu dipisahkan saat OPS-11B:
1. Bell notification (topbar) tidak muncul — kemungkinan besar karena job macet di tabel `jobs` (lihat di atas).
2. Halaman Operational Tasks tidak punya kartu/indikator notifikasi built-in — ini **memang belum pernah dibangun** (bukan bug, murni fitur yang belum ada — lihat §9 Gap Analysis).

### Alur 2 — Shipment Status Updated (Admin → Super Admin)

```
Admin mengedit Shipment via Admin Panel (EditShipment::afterSave())
        │
        ▼
event(new ShipmentStatusUpdated($shipment, 'fc'))
        │
        ▼
EventServiceProvider::$listen  →  NotifyAdminOnShipmentUpdate::handle()
        │
        ▼
User::role('super_admin')->get()
        │
        ▼
Notification::make()->sendToDatabase($recipients)
        │
        ▼
   (sama seperti Alur 1: lewat ShouldQueue → tabel jobs → tabel notifications)
        │
        ▼
   Bell notification — TAPI hanya tampil bila Super Admin membuka **FC panel**,
   karena Admin panel TIDAK memanggil ->databaseNotifications() (lihat §8).
   ⚠️  Super Admin biasanya bekerja di Admin panel, bukan FC panel — jadi
   notifikasi ini secara praktis nyaris tidak pernah terlihat oleh penerimanya.
```

### Alur 3 — Email ETA Reminder (jalur terpisah, tidak melalui bell)

```
Laravel Scheduler (routes/console.php, everyMinute())
        │
        ▼
SendShipmentEtaNotifications command
        │
        ▼
Query shipment dengan ETA H-3/H-2/H-1/H-0
        │
        ▼
Mail::send(new ShipmentEtaReminderMail)  →  email langsung (synchronous, tanpa queue)
        │
        ▼
Tabel custom `shipment_email_notifications` (tracking status kirim, cegah duplikat)
        │
        ▼
Email masuk ke inbox user — TIDAK muncul di bell, TIDAK masuk tabel `notifications`.
```

---

## 4. Notification Storage

| Tabel | Jenis | Skema | Dipakai oleh |
|---|---|---|---|
| `notifications` | Laravel default polymorphic notifications table | `id (uuid)`, `type`, `notifiable_type`, `notifiable_id`, `data` (**JSONB** — dikonversi dari TEXT oleh migration `2026_07_24_152713_alter_notifications_data_to_jsonb.php`), `read_at`, `timestamps` | Bell notification FC panel (Alur 1 & 2 di atas) |
| `jobs` | Laravel default queue table (`QUEUE_CONNECTION=database`) | Standar Laravel (`queue`, `payload`, `attempts`, `available_at`, dst.) | Menampung job `DatabaseNotification` sebelum diproses worker — **titik rawan utama** |
| `shipment_email_notifications` | Custom table, **bukan** bagian dari sistem bell notification | `shipment_id`, `user_id`, `user_email`, `shipment_code`, `eta_date`, `days_before_eta`, `status (pending/sent/failed)`, `error_message`, `sent_at` + unique constraint `(shipment_id, user_id, days_before_eta)` | `SendShipmentEtaNotifications` command — tracking anti-duplikasi email ETA |
| Cache | ❌ Tidak dipakai untuk notifikasi | — | — |
| Session | ✅ Dipakai untuk **toast**, bukan bell | `session()->push('filament.notifications', ...)` (`Notification::send()` bawaan Filament) | Seluruh toast UI feedback (§1, §6) — bersifat sekali-tampil, hilang setelah request berikutnya, tidak persisten. |
| Broadcast | ❌ Tidak ada | — | — |

---

## 5. Consumer Inventory

| Consumer | Notifikasi apa yang dibaca | Source data | Unread mechanism | Mark-as-read mechanism |
|---|---|---|---|---|
| **Filament Bell — FC Panel** (`databaseNotifications()`) | Seluruh baris `notifications` milik `auth()->user()` (via `Notifiable` trait) | `$user->notifications` / `$user->unreadNotifications()` — Livewire component bawaan Filament (`DatabaseNotifications`) | `read_at IS NULL` (standar Laravel `DatabaseNotification::unread()`) | Bawaan Filament: klik action dengan `->markAsRead()` (dipakai di kedua notifier), atau tombol "tandai semua dibaca" bawaan komponen bell |
| **Filament Bell — Admin Panel** | ❌ Tidak membaca apa pun (dead code, §8) | — | — | — |
| **OperationalTasks page (FC)** | ❌ Tidak membaca notifikasi sama sekali | Query langsung `Shipment`/`Unit` (eager load shipment, voyage, vessel, track terakhir, customer) | N/A | N/A |
| **Toast notifications** (40+ lokasi Filament pages/resources) | Bukan notifikasi tersimpan — data langsung dari hasil aksi (create/update/delete/error) yang baru saja dijalankan | `session('filament.notifications')`, sekali pakai | N/A (bukan konsep unread) | N/A (hilang otomatis setelah ditampilkan) |
| Dashboard Admin / Dashboard FC / Widget apa pun | ❌ Tidak ada satu pun dashboard/widget yang membaca tabel `notifications` | — | — | — |
| Livewire component lain (`MonitoringDetailSlide`, dll.) | Hanya toast UI feedback, bukan bell | — | — | — |

**Kesimpulan:** hanya **1 consumer** yang benar-benar membaca sistem notifikasi persisten: bell Filament bawaan di panel FC. Tidak ada consumer kustom sama sekali (tidak ada widget, tidak ada card di OperationalTasks, tidak ada dashboard summary).

---

## 6. Recipient Resolution

| Trigger | Mekanisme resolusi | File |
|---|---|---|
| Shipment Created | Union 3 sinyal, dibatasi `role('field_coordinator')`: (1) `scope_unit_type='depot' AND scope_unit_id=assigned_depot_id`, (2) `Depot.coordinator_user_id`, (3) `shipment.coordinator_id` (fallback legacy) | `NewOperationalTaskNotifier::resolveDepotFieldCoordinators()` |
| Shipment Status Updated | `User::role('super_admin')->get()` — global, tanpa scope branch/depot (didokumentasikan sengaja: super_admin adalah role global) | `NotifyAdminOnShipmentUpdate::handle()` |
| Email ETA Reminder | Branch users + customer users terkait shipment (query terpisah di command) | `SendShipmentEtaNotifications` |

**Kesesuaian dengan model scope kanonik** (`docs/SCOPING.md`): resolusi recipient FC di `NewOperationalTaskNotifier` **konsisten** dengan `ShipmentOwnership::isOriginFC()` dan pola 3-layer fallback (canonical scope → depot coordinator → legacy `coordinator_id`) yang juga dipakai `ShipmentPolicy`. Ini bukan gap — sudah selaras by design, meski `SCOPING.md` sendiri mencatat 3-layer fallback sebagai risiko kompleksitas jangka panjang (Appendix A.3), bukan spesifik untuk notifikasi.

**Guard penting:** bila `shipment.assigned_depot_id` kosong (shipment non-`sea`, di-null-kan otomatis oleh `ShipmentObserver::saving()`) atau tidak ada FC yang cocok scope-nya, notifier **no-op diam-diam** (tidak error, tidak log, tidak fallback ke siapa pun). Ini valid untuk shipment darat, tapi juga berarti **shipment sea yang depot-nya salah/kosong akan gagal notifikasi tanpa jejak apa pun** — potensi silent-failure kedua yang perlu dicek di data produksi (lihat §11).

---

## 7. Read / Unread Flow

- **Unread dihitung:** murni `read_at IS NULL` pada tabel `notifications` — 100% mekanisme bawaan `Illuminate\Notifications\DatabaseNotification` (Laravel default), **bukan** implementasi kustom.
- **Mark as read:** dipicu lewat `Action::make(...)->markAsRead()` pada action notifikasi (dipasang di kedua notifier, §2/§3) — juga bawaan Filament (`shouldMarkAsRead` flag pada `Filament\Notifications\Actions\Action`). Komponen bell Filam juga menyediakan tombol "tandai semua dibaca" bawaan tanpa kode tambahan.
- **Kapan dianggap selesai:** tidak ada konsep "resolved"/"selesai" — hanya `read`/`unread` biner standar Laravel. Tidak ada status ketiga (mis. "archived", "acknowledged").
- **Custom implementation?** Tidak ada sama sekali — seluruh read/unread flow 100% memakai mekanisme resmi Laravel `DatabaseNotification` + Filament `HasNotifications`, sesuai instruksi teknis "gunakan mekanisme resmi Filament, jangan buat sendiri" dari sprint OPS-11/Workflow 3.1 sebelumnya.

---

## 8. Dead Code Audit

| Item | Lokasi | Status | Catatan |
|---|---|---|---|
| Bell icon Admin Panel | `resources/views/filament/topbar/actions.blade.php:2` (`<x-filament::icon-button icon="heroicon-o-bell" ... />`) di-render lewat `AdminPanelProvider.php:48-50` (`renderHook(USER_MENU_BEFORE, ...)`) | ⚠️ **Dead/placeholder** | Ikon statis, tanpa badge, tanpa dropdown, tanpa link ke tabel `notifications`. Sudah didokumentasikan sebagai temuan (bukan ditindaklanjuti) di `docs/field-coordinator/WORKFLOW-STEP-3.1-FC-NOTIFICATION-CENTER.md:45`. Berpotensi membingungkan user Admin yang mengklik ikon ini mengharapkan notifikasi. |
| `->databaseNotifications()` di panel lain | Customer, CMS panel | ❌ Tidak pernah dipanggil | Bukan dead code — memang belum pernah dibangun, konsisten dengan brief OPS-11/Workflow 3.1 yang eksplisit membatasi scope ke FC panel. |
| Event listener yang tidak pernah dipanggil | — | ✅ Tidak ditemukan | `ShipmentStatusUpdated` → `NotifyAdminOnShipmentUpdate` adalah satu-satunya pasangan event/listener di aplikasi, dan keduanya aktif dipakai. |
| Notification class yang tidak dipakai | — | ✅ Tidak ditemukan | Hanya 2 pemanggil `sendToDatabase()` di seluruh `app/`, keduanya aktif punya trigger nyata. |
| Observer tidak aktif | — | ✅ Tidak ditemukan (untuk notifikasi) | Seluruh 6 observer terdaftar & aktif di `AppServiceProvider.php:44-49`; 5 dari 6 memang tidak memicu notifikasi (bukan berarti "mati", memang scope-nya bukan notifikasi — logging/auto-progres/kode generation). |
| Widget notification | — | ✅ Tidak ditemukan | Tidak ada widget notifikasi sama sekali (aktif maupun mati) — kategori ini kosong total. |
| Notification Service obsolete | — | ✅ Tidak ditemukan | `NewOperationalTaskNotifier` adalah satu-satunya service, dan masih aktif dipakai. |
| View override untuk bug `->unread()` | `resources/views/vendor/filament-notifications/notification.blade.php` | ✅ Aktif, bukan dead code | Bugfix sengaja (§10) — override resmi view Filament, sudah tervalidasi. |

**Kesimpulan dead code:** hanya **1 item dead code** ditemukan (bell placeholder di Admin panel). Tidak ada Event/Listener/Observer/Notification class/Service yang "mati" — arsitektur backend notifikasi cukup ramping dan seluruhnya aktif dipakai.

---

## 9. Gap Analysis

| Feature | Backend | UI | Catatan |
|---|---|---|---|
| Shipment Created → notifikasi FC | ✅ | ✅ (bell FC panel) | **Kode benar**, tapi bergantung penuh pada queue worker berjalan (lihat Ringkasan Eksekutif) — **ini yang paling mungkin menjelaskan laporan bug**. |
| Bell Counter / badge unread (FC panel) | ✅ | ✅ | Bawaan Filament, berfungsi selama baris `notifications` benar-benar tertulis. |
| Bell Counter (Admin panel) | ❌ | ⚠️ Placeholder dead code | Ikon ada secara visual tapi tidak terhubung apa pun — berisiko menyesatkan user. |
| OperationalTasks Notification Card / indikator on-page | ❌ | ❌ | Belum pernah dibangun. Halaman ini murni tabel data, tidak ada elemen notifikasi builtin. |
| Mark as Read | ✅ | ✅ | Bawaan Filament, terpasang di kedua notifier lewat `markAsRead()`. |
| Shipment Assigned → notifikasi | ❌ | ❌ | Tidak ada trigger backend sama sekali. |
| Pickup Completed → notifikasi | ❌ | ❌ | Tidak ada trigger backend sama sekali. |
| Stuffing → notifikasi | ❌ | ❌ | Tidak ada trigger backend sama sekali. |
| Vessel Arrival → notifikasi | ❌ | ❌ | Tidak ada trigger backend sama sekali. |
| Delivery Complete → notifikasi | ❌ | ❌ | Tidak ada trigger backend sama sekali. |
| Notifikasi Status Updated → Super Admin | ✅ | ⚠️ Nyaris tak terlihat | Backend benar, tapi Super Admin biasanya di Admin panel yang **tidak** punya bell — notifikasi ini secara praktis tersembunyi dari penerimanya sendiri. |
| Queue worker / job processing di production | ❓ **Belum terverifikasi** | — | **Blocker tertinggi.** Tidak ditemukan bukti file/konfigurasi (Procfile/Supervisor/scheduled `queue:work`) bahwa job notifikasi benar-benar diproses secara kontinu di luar sesi testing manual. Harus dicek langsung di server sebelum OPS-11B. |
| Notifikasi untuk shipment `sea` tanpa depot ter-assign / FC scope kosong | ❓ **Silent failure, tidak ter-log** | — | `resolveDepotFieldCoordinators()` no-op tanpa jejak bila tidak ada FC cocok — berpotensi menjelaskan sebagian laporan "notifikasi tidak muncul" untuk shipment tertentu meski worker berjalan normal. |

---

## 10. Riwayat Sprint Terkait (bukti bahwa backend sudah "pernah dibangun")

Tiga dokumen sprint sebelumnya ditemukan di `docs/`, seluruhnya bertanggal 24 Juli 2026 dan konsisten dengan temuan audit ini:

1. **`docs/tam-vehicle/SPRINT-OPS-11-FC-NEW-TASK-NOTIFICATION.md`** — implementasi awal `NewOperationalTaskNotifier` + trigger di `ShipmentObserver::created()`. Divalidasi end-to-end dengan `queue:work --stop-when-empty` dijalankan manual.
2. **`docs/field-coordinator/WORKFLOW-STEP-3.1-FC-NOTIFICATION-CENTER.md`** — mengaktifkan `->databaseNotifications()` di `FieldCoordinatorPanelProvider`. Juga melaporkan (tapi tidak menindaklanjuti) bell placeholder dead code di Admin panel (§8 di atas). Divalidasi dengan cara sama (queue worker manual).
3. **`docs/tam-vehicle/BUGFIX-INVALID-FILAMENT-NOTIFICATION-API.md`** — memperbaiki `BadMethodCallException` pada `->unread()` di view bawaan `filament/notifications` lewat view override resmi Laravel (bukan edit `vendor/`).

**Pola yang konsisten di ketiganya:** validasi selalu dilakukan dengan menjalankan queue worker **secara manual** selama sesi kerja, dan tidak satu pun dari ketiga dokumen memverifikasi bahwa worker berjalan otomatis di lingkungan production yang sesungguhnya. Ini memperkuat hipotesis root cause di Ringkasan Eksekutif.

---

## 11. Daftar File Terkait Sistem Notifikasi

**Trigger & logika:**
- `app/Observers/ShipmentObserver.php` (baris 133-135)
- `app/Services/NewOperationalTaskNotifier.php`
- `app/Events/ShipmentStatusUpdated.php`
- `app/Listeners/NotifyAdminOnShipmentUpdate.php`
- `app/Providers/EventServiceProvider.php`
- `app/Filament/Resources/ShipmentResource/Pages/EditShipment.php` (baris 135)
- `app/Providers/AppServiceProvider.php` (baris 44-49, registrasi seluruh observer)

**UI/consumer:**
- `app/Providers/Filament/FieldCoordinatorPanelProvider.php` (baris 39, `->databaseNotifications()`)
- `app/Providers/Filament/AdminPanelProvider.php` (baris 44-55, render hooks termasuk bell placeholder)
- `resources/views/filament/topbar/actions.blade.php` (bell placeholder dead code)
- `resources/views/vendor/filament-notifications/notification.blade.php` (override bugfix)
- `app/Filament/FC/Pages/OperationalTasks.php` (tidak membaca notifikasi — relevan sebagai target OPS-11B)

**Storage:**
- `database/migrations/2025_09_02_165741_create_notifications_table.php`
- `database/migrations/2026_07_24_152713_alter_notifications_data_to_jsonb.php`
- `database/migrations/2025_12_27_180751_create_notifications_table.php` (tabel `shipment_email_notifications`, jalur terpisah)

**Channel terpisah (email, tidak terhubung ke bell):**
- `app/Console/Commands/SendShipmentEtaNotifications.php`
- `app/Models/ShipmentEmailNotification.php`
- `resources/views/emails/shipment-eta-reminder.blade.php`
- `routes/console.php` (jadwal `everyMinute()`)

**Konfigurasi queue (relevan untuk root cause):**
- `.env` (baris 49, `QUEUE_CONNECTION=database`)
- `config/queue.php`
- `vendor/filament/notifications/src/DatabaseNotification.php` (implements `ShouldQueue` — bukan kode aplikasi, tapi menentukan perilaku)
- `app/Console/Kernel.php`, `routes/console.php` (tidak ada penjadwalan `queue:work`)

**Dokumentasi sprint sebelumnya:**
- `docs/tam-vehicle/SPRINT-OPS-11-FC-NEW-TASK-NOTIFICATION.md`
- `docs/field-coordinator/WORKFLOW-STEP-3.1-FC-NOTIFICATION-CENTER.md`
- `docs/tam-vehicle/BUGFIX-INVALID-FILAMENT-NOTIFICATION-API.md`
- `docs/SCOPING.md` (konteks resolusi scope FC yang dipakai recipient resolution)

---

## 12. Rekomendasi Implementasi OPS-11B

**Sebelum menyentuh UI sama sekali, verifikasi operasional (bukan implementasi kode) berikut wajib dilakukan lebih dulu** — ini murni pengecekan, tidak melanggar constraint "no code changes" karena tidak mengubah aplikasi:

1. **Cek apakah queue worker benar-benar berjalan di server production** (`ps aux | grep queue:work`, status Supervisor/systemd bila ada, atau tanyakan ke tim infra/DevOps). Bila tidak ada, ini blocker nomor satu — OPS-11B (atau sprint infra terpisah) harus memasang worker persisten (Supervisor config atau setara) sebelum UI apa pun akan terlihat berguna oleh FC.
2. **Cek isi tabel `jobs` dan `notifications` di production** (read-only) untuk mengonfirmasi apakah job notifikasi menumpuk tak terproses — bukti langsung untuk memvalidasi/membantah hipotesis root cause di atas.
3. **Cek data `assigned_depot_id` dan scope FC** untuk shipment yang dilaporkan tidak memicu notifikasi, guna menyingkirkan kemungkinan silent-failure di `resolveDepotFieldCoordinators()` (§6/§9).

**Untuk implementasi UI OPS-11B (setelah verifikasi di atas):**

4. **Manfaatkan backend yang sudah ada, jangan bangun ulang.** `NewOperationalTaskNotifier` dan bell `databaseNotifications()` di FC panel sudah benar dan sesuai spesifikasi bisnis — OPS-11B murni soal **menampilkan** data yang sudah tersimpan di tabel `notifications`, bukan membuat trigger/event/listener baru.
5. **Kartu/indikator notifikasi di halaman Operational Tasks** (yang secara eksplisit belum ada, §9) bisa dibangun dengan membaca `auth()->user()->unreadNotifications()` langsung di `OperationalTasks.php` — mekanisme resmi Laravel yang sama dipakai bell, tanpa tabel/kolom baru.
6. **Perbaiki atau hapus bell placeholder Admin panel** (§8) — saat ini menyesatkan karena terlihat seperti notifikasi fungsional. Keputusan (aktifkan `->databaseNotifications()` untuk Admin, atau hapus ikonnya) sebaiknya eksplisit di brief OPS-11B, bukan dibiarkan ambigu.
7. **Pertimbangkan me-review notifikasi "Shipment Status Updated → Super Admin"** — karena Super Admin biasanya bekerja di Admin panel yang tidak punya bell, notifikasi ini praktis tidak pernah dilihat penerimanya. Ini keputusan produk (aktifkan bell Admin, atau alihkan channel), bukan sesuatu yang bisa diputuskan sepihak oleh audit ini.
8. **5 trigger lain (Shipment Assigned, Pickup Completed, Stuffing, Vessel Arrival, Delivery Complete) tidak punya backend sama sekali** — bila OPS-11B dibrief untuk menampilkan notifikasi-notifikasi ini, itu berarti scope-nya bukan lagi "UI di atas backend yang ada" tapi butuh sprint backend baru (event/listener/observer baru) — di luar apa yang bisa dicapai murni dengan implementasi UI.

---

*Akhir dari Notification Architecture Report — OPS-11A.*
