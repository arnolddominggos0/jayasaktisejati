# Root Cause Analysis — 500 Error: `Call to undefined method Closure::isEmpty()`

**Status:** RCA SELESAI — **belum ada perbaikan diterapkan** (sesuai instruksi).
**Tanggal:** 23 Juli 2026
**Konteks:** 500 error saat membuka View/Edit Briefing Session di FC Panel.

---

## Jawaban Ringkas

**Root cause SUDAH ditemukan dengan pasti (bukan dugaan), dan BUKAN disebabkan oleh Sprint MP-06 (Foto Briefing).** Ini adalah bug laten pra-eksisting di section lain (Riwayat Pemeriksaan Kesehatan), yang kebetulan baru sekarang ter-trigger karena section itu baru sekarang punya data untuk ditampilkan. Detail lengkap & bukti di bawah.

---

## 1. File yang Melempar Error

**File:** `resources/views/filament/fc/pages/partials/health-log-timeline.blade.php`
**Baris:** 4

```blade
{{-- Health Log Timeline --}}
{{-- $state = Collection<AttendanceHealthLog> with attendance.manpower eager-loaded --}}

@if($state->isEmpty())
```

Baris 2 (komentar) menunjukkan **asumsi penulis kode**: `$state` dikira sudah berupa Collection yang siap dipakai. Asumsi ini **salah** — dijelaskan di §3.

**Dipanggil dari:** `app/Filament/FC/Resources/BriefingSessionResource/Pages/ViewBriefingSession.php`, baris 627-643 (Section 8 — "Riwayat Pemeriksaan Kesehatan"):

```php
Section::make('Riwayat Pemeriksaan Kesehatan')
    ->icon('heroicon-o-clipboard-document-list')
    ->collapsible()
    ->schema([
        ViewEntry::make('health_log_timeline')
            ->label('')
            ->view('filament.fc.pages.partials.health-log-timeline')
            ->columnSpanFull()
            ->state(function ($record) {                    // ← baris 631
                $attIds = $record->attendances->pluck('id');
                if ($attIds->isEmpty()) {
                    return collect();
                }
                return AttendanceHealthLog::query()
                    ->whereIn('attendance_id', $attIds)
                    ->with('attendance.manpower')
                    ->orderByDesc('created_at')
                    ->get();
            }),
    ])
    ->visible(function ($record) {                            // ← baris 645
        $attIds = $record->attendances->pluck('id');
        return $attIds->isNotEmpty()
            && AttendanceHealthLog::whereIn('attendance_id', $attIds)->exists();
    }),
```

---

## 2. Komponen Filament Penyebab

**`Filament\Infolists\Components\ViewEntry`** (bukan `ImageEntry`, `RepeatableEntry`, atau `ViewField`). Spesifiknya bukan `ViewEntry` itu sendiri yang bermasalah — `ViewEntry` hanyalah `class ViewEntry extends Entry {}` (kosong, tidak override apa pun; dikonfirmasi baca langsung `vendor/filament/infolists/src/Components/ViewEntry.php`). Sumber masalah ada di trait dasar yang dipakai `Entry`: **`Filament\Infolists\Components\Concerns\HasState`**.

---

## 3. Asal Closure — Mekanisme Persis (Ditelusuri, Bukan Ditebak)

**Alur yang dikonfirmasi lewat baca source Filament langsung:**

1. `Filament\Support\Components\ViewComponent::render()` (baris 118-129) membangun array variabel untuk Blade view custom via:
   ```php
   return view($this->getView(), [
       'attributes' => new ComponentAttributeBag,
       ...$this->extractPublicMethods(),   // ← sumbernya di sini
       ...
   ]);
   ```

2. `extractPublicMethods()` memanggil `ComponentManager::extractPublicMethods()` (`vendor/filament/support/src/Components/ComponentManager.php`, baris 118-136):
   ```php
   foreach ($this->methodCache[$component::class] as $method) {
       $values[$method] = $component->$method(...);   // first-class callable syntax
   }
   ```
   **Setiap method PUBLIC di komponen dipetakan ke closure, memakai NAMA METHOD PERSIS APA ADANYA** — tidak ada strip prefix "get", tidak ada transformasi nama. Jadi `getState()` → variabel `$getState` (closure yang HARUS dipanggil `$getState()`).

3. `Filament\Infolists\Components\Concerns\HasState` (dipakai `Entry`, jadi dipakai semua `ViewEntry`) punya **DUA method publik berbeda**:
   ```php
   public function state(mixed $state): static      // baris 32 — SETTER fluent (dipakai di schema())
   public function getState(): mixed                  // baris 63 — GETTER (resolve nilai sebenarnya)
   ```

4. Karena `state()` adalah method PUBLIC (dipakai untuk syntax `->state(fn ($record) => ...)` di schema), ia **ikut ter-ekstrak** oleh `extractPublicMethods()` — menghasilkan variabel `$state` di Blade view yang berisi **closure pembungkus method setter `state()` itu sendiri**, BUKAN nilai hasil resolusi `getState()`.

**Kesimpulan mekanisme:** `$state` di custom Blade view Filament infolist **BUKAN nilai state yang sudah di-resolve**. Konvensi Filament v3 yang benar adalah **`$getState()`** (dipanggil dengan tanda kurung). File `health-log-timeline.blade.php` salah asumsi memakai `$state` langsung sebagai Collection — padahal `$state` berisi closure (referensi ke method setter `state()`), sehingga `$state->isEmpty()` = `Closure::isEmpty()` = **method tidak ada** → 500.

Ini **bukan bug Filament** — ini **kesalahan pemakaian API** di blade custom milik aplikasi ini, sudah ada sejak file ini pertama ditulis.

---

## 4. Apakah Akibat Perubahan Terbaru (Foto Briefing)?

**TIDAK.** Bukti:

- **Sprint MP-06 sama sekali tidak menyentuh** `ViewBriefingSession.php` maupun `health-log-timeline.blade.php`. Satu-satunya file yang diubah sprint itu adalah `BriefingSessionResource.php` (menambah field `FileUpload` di `form()` dan `IconColumn` di `table()`).
- Section terkait foto briefing (**Section 6 — "Bukti Briefing"**, baris 557-571 di `ViewBriefingSession.php`) memakai view **berbeda**: `components/briefing-evidence.blade.php`. View itu **sudah diverifikasi bersih** — memakai `$getRecord()` dengan benar (dipanggil dengan kurung), **tidak pernah** mereferensikan `$state` atau memanggil `.isEmpty()`.
- Bug di `health-log-timeline.blade.php` bersifat **struktural & deterministik** — akan gagal SETIAP KALI Section 8 ("Riwayat Pemeriksaan Kesehatan") menjadi visible, **tidak peduli** apa pun terkait foto briefing. Section ini visible hanya jika ada record `AttendanceHealthLog` untuk sesi tsb. — kemungkinan besar **baru sekarang ada data kesehatan/recheck tercatat untuk sesi yang sedang dibuka**, sehingga bug laten ini baru sekarang ter-trigger. **Kebetulan waktu, bukan hubungan sebab-akibat** dengan perubahan Foto Briefing.

**Mengapa "Edit" juga tampak 500:** `EditBriefingSession.php` sendiri **trivial** — hanya `extends EditRecord`, tidak punya infolist custom, hanya memakai `form()` (yang saya ubah, tapi field baru tidak punya nama/method yang bentrok seperti `state()` — pola `FileUpload` yang dipakai identik dengan yang sudah berjalan di `RackContainerCheckPage::pillar_a_photo`). **Namun** `EditBriefingSession::getRedirectUrl()` eksplisit **redirect ke halaman View setelah simpan**:
  ```php
  protected function getRedirectUrl(): string
  {
      return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
  }
  ```
  Jadi skenario paling mungkin: user menyimpan perubahan di Edit → berhasil → **di-redirect ke View** → View inilah yang 500 karena bug §1-3. Dari sudut pandang user, ini terasa seperti "Edit juga rusak," padahal error sesungguhnya terjadi di halaman View setelah redirect. **Saya belum punya stack trace terpisah yang membuktikan Edit gagal di titik lain** — jika ternyata Edit gagal SEBELUM redirect (bukan sesudah), itu bug berbeda yang perlu stack trace tersendiri untuk dikonfirmasi.

---

## 5. Custom Blade yang Memanggil `isEmpty()` Bermasalah

**Ya, satu lokasi, sudah ditemukan pasti:**
`resources/views/filament/fc/pages/partials/health-log-timeline.blade.php:4` — `@if($state->isEmpty())`

Sudah ditelusuri seluruh codebase (`app/` dan `resources/views/`) untuk pola `$state->isEmpty()` / `$getState()->isEmpty()` — **ini satu-satunya kecocokan** yang menggabungkan pola view-data Filament (`$state`) dengan `.isEmpty()`. Semua pemakaian `.isEmpty()` lain di codebase adalah pada Collection Eloquent biasa (`$records->isEmpty()`, dst.) di konteks PHP murni (Service/Command/Model), bukan di dalam view Filament entry — jadi tidak berisiko sama.

---

## 6. Apakah Cache Blade Menggunakan View Lama?

**Diperiksa, bukan penyebab.** Ada ~204 file compiled di `storage/framework/views`, tapi ini **normal** (Laravel meng-compile view secara otomatis saat pertama diakses, bukan tanda "stale"). Yang lebih penting: saya membaca **source `.blade.php` yang hidup langsung** (bukan compiled cache), dan baris bermasalah **sudah ada di source saat ini**. Bahkan jika cache dihapus total dan di-compile ulang dari nol, bug yang sama akan tetap muncul — karena masalahnya ada di source, bukan di cache. (Catatan: Sprint MP-06 sempat menjalankan `view:cache` lalu `view:clear` untuk validasi — jadi cache tidak dalam keadaan "dibekukan.")

---

## 7. Root Cause, File Terdampak, Alasan Teknis — Ringkasan

| | |
|---|---|
| **Root cause** | `health-log-timeline.blade.php` memakai `$state` seolah nilai Collection yang sudah di-resolve, padahal Filament menyediakan `$state` sebagai closure-wrapper method setter `Entry::state()` (bukan getter). Nilai yang sudah di-resolve seharusnya diakses via `$getState()`. |
| **File terdampak** | `resources/views/filament/fc/pages/partials/health-log-timeline.blade.php` (baris 4, sumber crash). Dipicu dari `app/Filament/FC/Resources/BriefingSessionResource/Pages/ViewBriefingSession.php` (Section 8, baris 623-649) — file ini TIDAK diubah, hanya pemicu render. |
| **Alasan teknis** | `ComponentManager::extractPublicMethods()` memetakan SEMUA method publik komponen ke closure dengan nama method literal. `HasState` trait punya method publik `state()` (setter) DAN `getState()` (getter) — keduanya sama-sama diekstrak. `$state` di Blade view = closure atas `state()`, bukan hasil `getState()`. |
| **Terkait Sprint MP-06 (Foto Briefing)?** | **Tidak.** File yang bug tidak disentuh sprint itu; section yang error (Riwayat Kesehatan) berbeda total dari section foto (Bukti Briefing, sudah diverifikasi bersih). Kemunculan sekarang = kebetulan waktu (section baru sekarang punya data untuk ditampilkan), bukan regresi dari perubahan foto. |

---

## Usulan Solusi (BELUM DITERAPKAN — menunggu instruksi lanjut)

Sesuai permintaan, **tidak ada perbaikan yang diterapkan** di RCA ini. Jika/ketika diminta memperbaiki, perbaikan yang benar (bukan workaround) adalah pada satu baris:

```diff
- @if($state->isEmpty())
+ @if($getState()->isEmpty())
```

beserta seluruh referensi `$state` lain di file yang sama (baris 9: `@foreach($state as $log)`) juga perlu diubah ke `$getState()` — **atau**, alternatif yang lebih aman-terhadap-perubahan-di-masa-depan: assign sekali di awal file (`@php $items = $getState(); @endphp`) lalu pakai `$items` konsisten, supaya tidak lagi bergantung nama variabel Filament yang ambigu dengan method internal.

Baik `components.briefing-evidence.blade.php` (Sprint MP-06) maupun `EditBriefingSession.php` **tidak perlu perubahan apa pun** — keduanya sudah benar.
