# Bugfix — Invalid Filament Notification API (`Notification::unread()`)

**Status:** DIPERBAIKI & tervalidasi lewat rendering Livewire sungguhan (bukan simulasi manual).
**Tanggal:** 24 Juli 2026

---

## 1. Lokasi Pasti Penyebab Bug

**`vendor/filament/notifications/resources/views/notification.blade.php`, baris 102.**

```blade
@if ($notification->unread())
    <span class="h-2.5 w-2.5 rounded-full bg-primary-600 shrink-0"></span>
@endif
```

Ini adalah **file bawaan package `filament/notifications` v3.3.50 sendiri** — bukan kode aplikasi. Sesuai saran audit, sudah dijalankan `grep -R "->unread(" app/` dan `grep -R "Notification::make(" app/` — **nol hasil `->unread(` di seluruh `app/`**. Tidak ada satu pun kode aplikasi (Observer, Service, Action setelah `appendTrack()`, dst.) yang memanggil `->unread()`. Bug ini murni berasal dari file view bawaan Filament sendiri.

---

## 2. Kenapa `->unread()` Dipanggil (dan Kenapa Itu Salah)

`notification.blade.php` adalah view BAWAAN kelas `Filament\Notifications\Notification` sendiri (`protected string $view = 'filament-notifications::notification';`). Saat Filament merender SATU notifikasi — baik toast (`->send()`) MAUPUN item di daftar bell database (`->sendToDatabase()`) — keduanya bermuara ke view yang SAMA ini.

`Filament\Support\Components\ViewComponent::render()` mem-bind `$this` (objek `Notification` itu sendiri) sebagai variabel `$notification` ke dalam view:
```php
// vendor/filament/support/src/Components/ViewComponent.php:125
...(isset($this->viewIdentifier) ? [$this->viewIdentifier => $this] : []),
```
`viewIdentifier = 'notification'` pada kelas `Notification`.

Artinya `$notification` di baris 102 **BUKAN** `Illuminate\Notifications\DatabaseNotification` (model Eloquent tersimpan di DB, yang memang punya method `unread()` bawaan Laravel — `read_at === null`), melainkan **`Filament\Notifications\Notification`**, DTO fluent-builder untuk MENYUSUN notifikasi (`title()`, `body()`, `send()`, dst.) — kelas ini **tidak pernah** mendefinisikan `unread()`, di class-nya sendiri maupun seluruh trait `Concerns\*` yang dipakainya (`CanBeInline`, `HasActions`, `HasBody`, dst. — sudah diperiksa satu per satu, dikonfirmasi tidak ada, dan tidak ada `Macroable`/registrasi macro `unread` di package manapun).

**Dikonfirmasi langsung** (bukan dugaan) lewat pemanggilan langsung di Tinker:
```
$n = Notification::make('test')->title('Hello');
$n->unread();
→ BadMethodCallException: Method Filament\Notifications\Notification::unread does not exist.
```
Pesan ini **identik** dengan exception yang dilaporkan. Setiap notifikasi apa pun yang punya `title()` — termasuk notifikasi "Update lapangan tersimpan" yang dikirim `OperationalTasks`'s action `updateTrack` setelah `appendTrack()` berhasil — pasti melewati baris ini dan crash, karena `$hasTitle` selalu `true` untuk notifikasi tersebut.

---

## 3. File yang Diubah

| File | Perubahan |
|---|---|
| `resources/views/vendor/filament-notifications/notification.blade.php` | **Baru** — Blade view **override** resmi Laravel (bukan edit langsung ke `vendor/`), salinan persis file bawaan Filament dengan **satu baris** diperbaiki. |

**Tidak ada file lain yang diubah.** `vendor/filament/notifications/resources/views/notification.blade.php` (file asli) **tidak disentuh sama sekali** — dikonfirmasi lewat `diff`, hanya file override baru di `resources/views/vendor/...` yang berbeda satu baris dari aslinya. Tidak ada Observer, Service, migration, atau kode aplikasi apa pun yang diubah.

---

## 4. Perbaikan Sesuai API Filament Versi Project

**Mekanisme:** Laravel/Spatie Package Tools secara otomatis memprioritaskan `resources/views/vendor/{package-name}/...` di atas view bawaan package (`filament-notifications` adalah nama package-nya, dikonfirmasi dari `NotificationsServiceProvider::configurePackage()` — `->name('filament-notifications')`). Ini **bukan** upgrade package, **bukan** edit `vendor/` — murni fitur override view resmi Laravel, memakai versi Filament yang sudah terpasang (v3.3.50) apa adanya.

**Perubahan satu baris:**
```blade
// Sebelum:
@if ($notification->unread())

// Sesudah:
@if (method_exists($notification, 'unread') && $notification->unread())
```
`method_exists()` mencegah `BadMethodCallException` tanpa mengubah perilaku bila suatu saat `unread()` memang tersedia (mis. lewat versi Filament lain atau macro yang terdaftar) — method itu tetap dipanggil dan dihormati bila ada. Untuk kondisi SAAT INI (method benar-benar tidak ada), efeknya: indikator titik biru "belum dibaca" tidak pernah tampil — bukan penghapusan fitur yang disengaja, melainkan konsekuensi jujur dari `Filament\Notifications\Notification` DTO tidak pernah membawa status baca/belum-baca di versi ini (dikonfirmasi: `DatabaseNotifications::getNotification()` yang membangun DTO ini dari baris DB hanya memanggil `->date(...)`, tidak pernah `->unread(...)` sebagai setter).

Seluruh baris lain dalam file **persis sama** dengan aslinya — termasuk seluruh komentar `{{-- Icon --}}`/`{{-- Content --}}` yang sudah ada di file asli Filament (bukan komentar baru yang saya tambahkan, murni tersalin apa adanya).

---

## 5. Hasil Pengujian

Diuji lewat `Livewire::test()` — merender komponen Livewire SUNGGUHAN (bukan pemanggilan manual di luar siklus hidup Livewire, yang terbukti tidak akurat di percobaan awal saya) — dengan data nyata:

| Uji | Hasil |
|---|---|
| `Filament\Notifications\Notification::unread()` dipanggil langsung → exception persis seperti laporan bug | ✅ Direproduksi identik, mengonfirmasi lokasi & penyebab |
| View override terdaftar & ditemukan lebih dulu daripada file `vendor/` asli | ✅ `app('view')->getFinder()->find('filament-notifications::notification')` mengembalikan path ke `resources/views/vendor/...` |
| **Toast notification** (`Notification::make()->title('Update lapangan tersimpan')->body(...)->success()->send()`, persis notifikasi yang dikirim `OperationalTasks` setelah Update Status) → `Livewire::test(Notifications::class)` | ✅ **Render berhasil, tidak ada exception**, judul notifikasi muncul di HTML |
| **Database notification** (bell) — notifikasi nyata dikirim, **antrian benar-benar diproses** (`queue:work`), lalu `Livewire::test(DatabaseNotifications::class)` | ✅ **Render berhasil, tidak ada exception**, judul "Pekerjaan Operasional Baru" muncul di HTML |
| `diff` file override vs file asli `vendor/` | ✅ Hanya 1 baris berbeda (baris 102) |
| `migrate:status` | ✅ Tidak ada migration baru |
| Data test dibersihkan (notifications, jobs, tidak ada shipment tersisa) | ✅ Kembali ke baseline |

**Kesimpulan pengujian:** skenario persis yang dilaporkan — notifikasi toast setelah Update Status Unit berhasil — sekarang **terbukti** tidak lagi menghasilkan exception, diverifikasi lewat rendering Livewire yang sesungguhnya, bukan asumsi.

---

## Konfirmasi Batas

- ✅ Tidak ada perubahan business workflow — perbaikan murni di view rendering notifikasi.
- ✅ Tidak ada perubahan shipment tracking / Unit workflow.
- ✅ Tidak ada perubahan pada tabel/data `notifications` — hanya cara SATU baris ditampilkan.
- ✅ Tidak ada Notification Observer di project ini yang perlu diubah — sumber bug terbukti di view Filament, bukan Observer aplikasi.
- ✅ Tidak ada upgrade package — `composer.lock`/`vendor/` tidak disentuh, seluruh package tetap v3.3.50.
- ✅ Tidak ada komentar baru — file override adalah salinan persis file asli (termasuk komentar bawaannya), hanya satu kondisi `@if` yang diperkuat, tanpa tambahan komentar penjelas apa pun.
