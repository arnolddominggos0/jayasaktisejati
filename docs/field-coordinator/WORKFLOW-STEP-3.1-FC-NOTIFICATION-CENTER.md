# Workflow Step 3.1 — Enable Filament Database Notifications (FC Panel)

**Status:** IMPLEMENTED & tervalidasi end-to-end dengan data nyata + queue worker.
**Tanggal:** 24 Juli 2026
**Depends on:** OPS-11 (FC New Operational Task Notification) — tidak disentuh.

---

## 1. File yang Berubah

| File | Perubahan |
|---|---|
| `app/Providers/Filament/FieldCoordinatorPanelProvider.php` | Tambah **satu baris**: `->databaseNotifications()` pada method chain `panel()`. Tidak ada parameter kustom — memakai default bawaan Filament sepenuhnya. |

**Tidak ada file lain yang diubah.** `NewOperationalTaskNotifier.php`, `ShipmentObserver.php`, `OperationalTasks.php`, `DailyBriefingGate.php`, `Shipment.php`, Inspection — semuanya **tidak disentuh** (dikonfirmasi lewat `git status`, hanya 1 file berubah sprint ini). Tidak ada migration.

---

## 2. Cara Mengaktifkan Notification Center Filament

**Satu pemanggilan method resmi Filament v3**, tidak ada implementasi kustom apa pun:

```php
->databaseNotifications()
```

Ini memanggil `Filament\Panel\Concerns\HasNotifications::databaseNotifications(bool $condition = true, bool $isLazy = true)` — trait bawaan `Filament\Panel`. Dikonfirmasi dari kode Filament sendiri (`vendor/filament/filament/resources/views/components/topbar/index.blade.php:171`): topbar SELALU merender trigger bell + badge unread secara otomatis begitu `filament()->hasDatabaseNotifications()` bernilai `true` — **tidak ada komponen/view/route tambahan yang perlu dibuat manual.** Daftar notifikasi, badge unread count, dan mekanisme mark-as-read seluruhnya sudah built-in di paket `filament/notifications` yang sudah ter-install.

**Konfigurasi yang SENGAJA tidak diubah** (memakai default framework, bukan diabaikan):
- `databaseNotificationsPolling`: default `'30s'` — tidak di-override. Brief mengecualikan "Polling" dari scope; dibaca sebagai **larangan membangun mekanisme polling/realtime kustom sendiri**, bukan instruksi mematikan polling bawaan Filament — karena instruksi Technical Requirements eksplisit berbunyi "Gunakan mekanisme resmi Filament v3. Jangan membuat implementasi sendiri apabila Filament sudah menyediakan." Mengubah `databaseNotificationsPolling(null)` justru berarti MENYIMPANG dari default resmi, bukan mengikutinya. **Ini judgment call yang saya sadari berpotensi ambigu** — jika interpretasi ini salah, tinggal tambah `->databaseNotificationsPolling(null)` satu baris untuk mematikannya, tanpa mengubah apa pun yang lain.
- `isLazy`: default `true` — komponen notifikasi dimuat lazy (tidak membebani initial page load), juga bawaan.

---

## 3. Konfigurasi Panel Sebelumnya

**Audit dilakukan lebih dulu, sesuai instruksi**, terhadap seluruh 4 `PanelProvider` (`FieldCoordinatorPanelProvider`, `AdminPanelProvider`, `CustomerPanelProvider`, `CmsPanelProvider`):

| Panel | `databaseNotifications()` sebelumnya? |
|---|---|
| **FC** (`fc`) | ❌ Tidak pernah dipanggil — dikonfirmasi lewat grep menyeluruh, nol hasil di seluruh `app/` sebelum sprint ini. |
| Admin (`admin`) | ❌ Juga tidak pernah dipanggil — **di luar cakupan sprint ini** (brief eksplisit "FC Panel"), tidak diubah. |
| Customer, CMS | ❌ Sama, tidak diubah (di luar cakupan). |

**Temuan tambahan (dilaporkan, tidak ditindaklanjuti karena di luar cakupan):** `AdminPanelProvider` sudah punya render hook kustom yang menampilkan **ikon bell dekoratif statis** (`resources/views/filament/topbar/actions.blade.php` — `<x-filament::icon-button icon="heroicon-o-bell" .../>`, tanpa badge/dropdown/klik). Ikon ini **bukan** Notification Center Filament — murni placeholder visual, tidak terhubung ke tabel `notifications` sama sekali. Render hook ini **tidak dipakai** oleh FC panel (tidak didaftarkan di `FieldCoordinatorPanelProvider`), jadi tidak ada konflik/duplikasi bell di FC panel. Dicatat sebagai observasi untuk Admin panel di masa depan, bukan diubah sekarang.

---

## 4. Validasi Menggunakan Data Nyata

Dijalankan end-to-end: cek konfigurasi panel → buat shipment nyata (transaksi, sama seperti OPS-11) → proses queue worker → baca notifikasi **persis seperti yang dilakukan komponen bell Filament** (`auth()->user()->unreadNotifications()`) → simulasikan klik (`markAsRead()`) → verifikasi route tujuan → cleanup.

| Langkah | Hasil |
|---|---|
| `Filament::getPanel('fc')->hasDatabaseNotifications()` | ✅ `true` |
| Panel lain (admin/customer/cms) `hasDatabaseNotifications()` | ✅ tetap `false` — tidak ikut berubah |
| `filament()->hasDatabaseNotifications()` saat FC panel aktif | ✅ `true` — kondisi yang dibaca topbar Filament untuk merender bell |
| Shipment nyata dibuat → job notifikasi ter-queue | ✅ 1 job |
| Queue diproses (`queue:work --stop-when-empty`) | ✅ selesai tanpa error |
| `auth()->user()->unreadNotifications()->count()` (login sebagai FC Jakarta, penerima) | ✅ `1` |
| Isi notifikasi (title/body) sesuai OPS-11, **tidak berubah** | ✅ "Pekerjaan Operasional Baru" / Toyota Astra Motor \| 1 Unit \| SPPB \| WF31-TEST/07/2026 \| Siap diproses. |
| `read_at` sebelum klik | ✅ `NULL` (akan terhitung di badge unread) |
| Action URL | ✅ `http://.../fc/operational-tasks` |
| Route `filament.fc.pages.operational-tasks` benar-benar terdaftar | ✅ `fc/operational-tasks` |
| Flag `shouldMarkAsRead` pada action | ✅ ada |
| Simulasi klik → `markAsRead()` | ✅ `read_at` terisi timestamp |
| `unreadNotifications()->count()` setelah itu | ✅ `0` (turun tepat 1) |

**Cleanup diverifikasi:** `notifications`=0, `jobs`=0, shipment test terhapus, DB kembali ke 1 shipment (baseline).

---

## 5. Screenshot Bell Notification Bekerja

**Catatan jujur (sama seperti sprint UI sebelumnya):** sesi ini tidak memiliki tool browser/screenshot aktif — saya tidak bisa mengambil screenshot piksel sungguhan dari topbar FC panel di browser. Sebagai gantinya, bukti yang saya berikan adalah **bukti mekanisme yang lebih kuat daripada screenshot**: saya melacak kode sumber Filament sendiri (`topbar/index.blade.php:171`) yang membuktikan bell **akan** dirender kapan pun `hasDatabaseNotifications()` bernilai true, lalu membuktikan nilai itu memang `true` untuk panel FC, lalu membaca notifikasi persis dengan cara yang sama seperti komponen Livewire bell membacanya (`unreadNotifications()`), dan mengonfirmasi angka unread berubah 1→0 setelah aksi "klik" disimulasikan. Kombinasi ini membuktikan bell akan berfungsi tanpa perlu asumsi visual. Jika screenshot sungguhan tetap dibutuhkan, saya bisa mengambilnya begitu ada akses browser (mis. Chrome MCP tersambung).

---

## 6. Regression Check

| Area dilarang diubah | Status |
|---|---|
| `ShipmentObserver` | ✅ Tidak disentuh sprint ini (perubahan OPS-11 sebelumnya tetap ada, tidak ditambah/dikurangi) |
| `NewOperationalTaskNotifier` | ✅ Tidak disentuh — isi notifikasi identik dengan OPS-11 (dibuktikan §4, body sama persis) |
| Business Rule OPS-11 (1 notifikasi per create, hanya FC depot, dst.) | ✅ Tidak diubah — sprint ini murni menyalakan UI-nya |
| Operational Task | ✅ `OperationalTasks.php` tidak disentuh |
| Briefing (OPS-10) | ✅ `DailyBriefingGate`/Briefing tidak disentuh |
| Shipment Workflow | ✅ `Shipment.php` tidak disentuh |
| Inspection | ✅ tidak disentuh |
| Transition Guard | ✅ `Shipment::runTransitionGuards()` tidak disentuh |

### OPS-08 baseline
```
Shipment #1 (JSS0726SH0001): handoverCleared=false, loadingCleared=false
```
Konsisten dengan baseline OPS-11 (satu-satunya shipment nyata di lingkungan saat ini sejak reset DB eksternal yang sudah dilaporkan di OPS-11).

---

## Konfirmasi Batas

- ✅ Notification Center bawaan Filament aktif di FC Panel — 1 baris konfigurasi, nol implementasi kustom.
- ✅ Bell + badge unread akan tampil di topbar (dibuktikan lewat kondisi render Filament sendiri + data nyata).
- ✅ Klik notifikasi membuka Operational Task (payload OPS-11, tidak diubah).
- ✅ Mark-as-read memakai mekanisme Filament (`shouldMarkAsRead` + `markAsRead()`), bukan implementasi baru.
- ✅ Tidak ada websocket/polling kustom/toast realtime/push/WhatsApp/email/styling kustom yang dibangun.
- ✅ Isi notifikasi OPS-11 tidak berubah satu karakter pun.
