# Bugfix — 404 saat membuka "Buka Inspection"

**Status:** DIPERBAIKI & tervalidasi penuh dengan data nyata dari laporan bug, termasuk regression OPS-08.
**Tanggal:** 23 Juli 2026

---

## 1. Root Cause Sebenarnya

**Bukan bug routing.** `php artisan route:list` mengonfirmasi route `fc/operational-inspections/{record}/{unit}` **terdaftar dengan benar** dan `InspectUnitPage::getUrl()` menghasilkan URL yang **persis cocok** dengan route itu. Request benar-benar sampai ke `InspectUnitPage::mount()` — lalu method itu **sengaja** memanggil `abort_if(! $stage, 404, ...)`.

**Root cause nyata: dua caller `InspectUnitPage` memakai konvensi berbeda untuk menentukan "stage" yang relevan, dan tidak pernah disinkronkan.**

| Caller | Sumber stage | Sejak |
|---|---|---|
| `unit-card.blade.php` (Workspace FC, tombol "Inspeksi"/"Lihat") | `currentTrackStatus()` — tahap yang **sudah tercapai** | Sebelum INS-03 |
| Modal Update, `OperationalTasks` (`ShipmentResource::inspectionStatusFields()`, tombol "Buka Inspection") | `nextTrackStatus()` — tahap **berikutnya** yang sedang disiapkan | INS-04 |

`InspectUnitPage::resolveStage()` **hanya pernah memakai `currentTrackStatus()`** — cocok untuk caller pertama (memang begitu didesain sejak awal), tapi menghasilkan `null` untuk caller kedua setiap kali `currentTrackStatus()` tidak memetakan ke stage manapun (shipment belum punya track sama sekali, atau sedang transit di status tanpa inspeksi seperti DeliveryToPort/Stacking/OnShip/VesselDepart) — padahal `nextTrackStatus()`-nya valid dan itulah yang ditampilkan tombolnya.

**Dibuktikan dengan data nyata dari laporan bug** (shipment #235 `JSS0726SH0003`, unit #233):
```
currentTrackStatus: NULL (belum ada track sama sekali)
nextTrackStatus:    pickup
resolveStage(current): NULL   ← inilah yang dipakai mount(), menyebabkan 404
resolveStage(next):    pickup ← inilah yang dipakai modal untuk menampilkan tombol
```

**Bug ini SUDAH ADA sejak INS-04** (bukan disebabkan UX-03) — lihat §4.

---

## 2. File yang Diubah

Hanya **satu file**: `app/Filament/FC/Pages/InspectUnitPage.php` — method `resolveStage()`.

---

## 3. Bagaimana Diperbaiki

```php
private function resolveStage(): ?string
{
    $status = $this->record?->currentTrackStatus();
    $stage = $status ? InspectionDraftAutoCreate::resolveStage($status) : null;

    if ($stage) {
        return $stage;
    }

    // Fallback ke nextTrackStatus() HANYA kalau currentTrackStatus() tidak
    // menghasilkan stage.
    $nextStatus = $this->record?->nextTrackStatus();

    return $nextStatus ? InspectionDraftAutoCreate::resolveStage($nextStatus) : null;
}
```

`currentTrackStatus()` tetap dicoba **lebih dulu** dan dikembalikan langsung begitu berhasil — `nextTrackStatus()` hanya jadi **fallback**. Ini membuat perbaikan 100% backward-compatible untuk caller asli (`unit-card.blade.php`): dikonfirmasi lewat pembacaan `daftar-unit.blade.php` bahwa tombol ke `InspectUnitPage` di sana **hanya pernah muncul** di dalam `@if ($activeStage)` — yaitu tepat ketika `currentTrackStatus()`-based stage SUDAH valid — sehingga fallback ini **tidak akan pernah tersentuh** untuk caller tersebut, nol risiko regresi di sana.

Murni perbaikan di layer navigasi (menentukan stage mana yang harus dibuka) — **tidak menyentuh** Guard, `UnitInspection`, database, atau logic Finalization.

---

## 4. Audit UX-03 Rollback (Scope 4) — Dikonfirmasi BUKAN Penyebab

Diperiksa seluruh perubahan UX-03: hanya menyentuh `$returnTo`/`returnUrl()` (dihapus), label tombol/badge, dan target redirect setelah submit/reset. **UX-03 tidak pernah menyentuh** `getSlug()`, `getRoutePath()`, atau `resolveStage()` — method yang menjadi akar masalah **sama persis** sebelum dan sesudah UX-03.

**Kenapa baru terlihat sekarang:** sepanjang sesi pengembangan ini (INS-03 → INS-04 → UX-01 → UX-02 → UX-03), validasi terhadap `InspectUnitPage` selalu dilakukan lewat **pemanggilan method PHP langsung** (mis. `$service->markUnitStuffed()`, render Blade langsung) — **belum pernah** lewat klik navigasi HTTP sungguhan sampai laporan bug ini. Bug-nya sudah ada sejak `inspectionStatusFields()` pertama kali dibuat di INS-04, hanya baru sekarang benar-benar terpicu oleh klik nyata.

---

## 5. Hasil `route:list`

```
GET|HEAD   fc/operational-inspections/{record}/{unit} › filament.fc.pages.operational-inspections
```
Terdaftar dengan benar, tidak berubah — mengonfirmasi ini memang bukan masalah routing.

---

## 6. Validasi & Regression

| Uji | Hasil |
|---|---|
| `php -l` pada `InspectUnitPage.php` | ✅ Bersih |
| `route:list --path=operational-inspections` | ✅ Route terdaftar benar |
| **Reproduksi bug PERSIS** dengan shipment/unit dari laporan (#235/#233) — sebelum fix | ✅ Dikonfirmasi `resolveStage()` lama menghasilkan `NULL` |
| **`InspectUnitPage::mount(Shipment::find(235), 233)` dipanggil langsung setelah fix** | ✅ **BERHASIL, TIDAK 404** — resolve ke stage `pickup`, inspection #280, `isReadOnly=false` |
| Draft inspeksi untuk unit #233 stage=pickup sudah ada (prasyarat `firstOrFail()` berhasil) | ✅ Dikonfirmasi ada |
| `composer dump-autoload` + `view:cache`/`view:clear` | ✅ Sukses, tidak ada regresi blade |
| **Regression OPS-08**: guard terhadap seluruh shipment nyata (termasuk #235 yang baru) | ✅ Identik dengan seluruh baseline sebelumnya |

---

## Konfirmasi Batas

- ✅ Tidak ada perubahan Transition Guard, `Shipment.php`, `UnitInspection`, database, atau workflow Inspeksi.
- ✅ Tidak ada perubahan arsitektur INS-03/INS-04 — Single Entry Point, Finalize, Lock, Audit Log semua utuh.
- ✅ Perbaikan murni di layer navigasi (`resolveStage()`), additive (fallback), nol dampak ke caller yang sudah bekerja benar sebelumnya.
