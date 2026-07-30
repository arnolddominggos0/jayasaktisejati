# Sprint OPS-06 — Wire Planning Loading into Operational Flow

**Status:** IMPLEMENTED — tervalidasi struktural + fungsional read-only terhadap data nyata (lihat §Validasi).
**Tanggal:** 23 Juli 2026
**Rujukan:** `AUDIT-PLANNING-LOADING-VS-STUFFING-EXECUTION.md` (temuan yang menjadi dasar sprint ini)

---

## Ringkasan

Sprint ini **tidak membangun modul baru** — persis seperti diminta. Yang dikerjakan murni menyambungkan dua halaman yang sudah lengkap sejak CA-01.5 dan ST-01 (`ContainerAllocationWorkspace`, `StuffingWorkspace`) ke alur operasional yang sedang hidup di `OperationalTasks.php`, yang sebelumnya sama sekali tidak punya jalan masuk (dikonfirmasi lewat `grep`: nol referensi ke `::getUrl()` keduanya di luar filenya sendiri).

---

## Scope 1+2 — Entry Point Planning Loading

**File:** `app/Filament/FC/Pages/OperationalTasks.php`

Ditambahkan satu `Action::make('planningLoading')` di dalam `ActionGroup` yang sama dengan `handover`/`stuffing`/dst (posisi: tepat setelah `handover`, sebelum `stuffing`) — bukan halaman baru, bukan Resource baru, murni `->url(fn () => ContainerAllocationWorkspace::getUrl())`.

**Visibility mengikuti business rule yang SUDAH ADA, tanpa membuat sinyal baru:**
```php
->visible(function (Shipment $record) {
    if (! ShipmentOwnership::canEdit(...)) return false;
    if ($record->latest_track_status?->value !== TrackStatus::Handover->value) return false;
    if (! $isVehicle) return false;
    return ((int) ($record->unassigned_container_count ?? 0)) > 0;
})
```
`unassigned_container_count` adalah kolom raw-SQL yang SAMA PERSIS yang sudah menggerbangi tombol "Stuffing & Segel" — dihitung dari `container_display IS NULL` (Jalur legacy). Tombol baru ini muncul PERSIS pada kondisi "Handover selesai, container belum lengkap ditandai" — begitu selesai (`unassigned_container_count = 0`), tombol "Planning Loading" otomatis hilang dan "Stuffing & Segel" otomatis muncul (logic lama, tidak diubah).

---

## Scope 3 — Audit ContainerAllocationWorkspace

**Tidak ada gap.** Diaudit ulang seluruh 7 kebutuhan yang diminta:

| Kebutuhan | Sudah ada di |
|---|---|
| Daftar container | `getContainers()` |
| Service | `Container.type`, di-set via `configureContainerAction()` |
| Unit pool | `getUnallocatedUnits()` |
| Assign | `assignToContainerAction()` → `ContainerAllocationService::assign()` |
| Move | `moveToContainerAction()` → `::move()` |
| Remove | `removeFromContainerAction()` → `::remove()` |
| Mark ready | `markContainerReadyAction()` / `unmarkContainerReadyAction()` |

Tidak ada perubahan pada file ini di luar Scope 4 di bawah.

---

## Scope 4 — Action "Mulai Stuffing"

**Files:** `app/Filament/FC/Pages/ContainerAllocationWorkspace.php`, `resources/views/filament/fc/pages/container-allocation-workspace.blade.php`

Ditambahkan method baru `getShipmentsReadyForStuffing(): Collection` — MURNI MEMBACA (tidak ada penulisan data): mengelompokkan container hari ini per Shipment (lewat `Container::shipment()`, sudah ada sejak ST-01), lalu memfilter hanya shipment yang **SELURUH** containernya sudah `is_ready_for_stuffing = true`. Blade menampilkan section baru "Siap untuk Stuffing" di bawah grid Unit/Container yang sudah ada, berisi tombol "Mulai Stuffing" per shipment → `StuffingWorkspace::getUrl(['shipment' => $shipment->id])`.

Tidak ada planning yang dibuat di titik ini — murni derajat baca dari status yang sudah tersimpan (`is_ready_for_stuffing`), sesuai instruksi "Stuffing hanya menjalankan planning."

---

## Scope 5 — Source of Truth Tidak Diubah

Dikonfirmasi: `container_display` dan `unassigned_container_count` (raw SQL) **tidak disentuh sama sekali** — dipakai APA ADANYA sebagai sinyal visibility untuk tombol baru. Tidak ada migrasi ke `container_id`. Modal Handover Depo tidak diubah.

---

## Validasi

| Uji | Hasil |
|---|---|
| `php -l` pada 3 file yang diubah | ✅ Bersih |
| `composer dump-autoload` + `view:cache`/`view:clear` | ✅ Sukses, tanpa error |
| Reflection: `getShipmentsReadyForStuffing()` ada di `ContainerAllocationWorkspace` | ✅ |
| Simulasi algoritma grouping (mock data: shipment semua-ready / sebagian-ready / satu-container / tanpa-shipment) | ✅ Tepat: hanya shipment dengan SELURUH container ready yang muncul |
| `ContainerAllocationWorkspace::getUrl()` & `StuffingWorkspace::getUrl(['shipment'=>1])` (dengan konteks panel FC di-set) | ✅ Resolve ke URL benar, termasuk query string `?shipment=1` |
| `OperationalTasks::table()` dibangun penuh, `planningLoading` dikonfirmasi terdaftar di posisi yang benar dalam ActionGroup (setelah `handover`, sebelum `stuffing`) | ✅ |
| **Panggilan langsung (read-only) ke `getUnallocatedUnits()`, `getContainers()`, `getShipmentsReadyForStuffing()` terhadap database nyata** | ✅ Berjalan tanpa error: 4 unit, 4 container, 0 shipment siap stuffing (hasil masuk akal, tidak ada shipment yang seluruh container-nya sudah ready saat ini) |

Validasi kali ini lebih dalam dari sprint-sprint sebelumnya karena **tidak melibatkan migrasi** — seluruh perubahan adalah wiring/read-only, sehingga bisa diuji langsung terhadap data nyata tanpa menyentuh blocker migrasi yang masih berlaku untuk sprint-sprint lain.

---

## Konfirmasi Batas

- ✅ Tidak membangun Planning Loading/Stuffing baru — murni wiring ke `ContainerAllocationWorkspace`/`StuffingWorkspace` yang sudah ada.
- ✅ Tidak ada tabel/enum baru.
- ✅ Tidak migrasi `container_display`.
- ✅ Tidak mengubah Shipment Track — tombol "Stuffing & Segel" lama (yang memanggil `appendTrack()`) dibiarkan 100% utuh, tidak disentuh.
- ✅ Tidak mengubah business rule — `unassigned_container_count` dipakai apa adanya sebagai sinyal, bukan diganti.

## Acceptance Criteria — Tercapai

```
Handover ── [tombol baru] ──→ Planning Loading (ContainerAllocationWorkspace)
                                        ↓
                              Container Ready (status yang sudah ada)
                                        ↓
                        [tombol baru, di dalam Workspace] "Mulai Stuffing"
                                        ↓
                              StuffingWorkspace (ST-01, sudah ada)
```
Operator kini bisa mencapai kedua halaman tanpa tahu URL-nya secara manual — keduanya reachable murni lewat klik dari alur yang sudah mereka jalani.
