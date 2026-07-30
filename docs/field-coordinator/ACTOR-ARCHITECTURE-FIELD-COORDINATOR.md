# Actor Architecture — Field Coordinator (v1.0)

**Status:** 🧊 **ACTOR ARCHITECTURE FROZEN** (blueprint — fondasi implementasi berikutnya)
**Tanggal:** 20 Juli 2026
**Sifat sprint:** Definisi arsitektur actor. **Bukan** implementasi UI/CRUD/query/engine.
**Pertanyaan inti workspace FC:** *"Pekerjaan apa yang harus saya selesaikan sekarang?"* — bukan *"Apa saja data unit ini?"*

> Dokumen ini **memformalkan arsitektur yang sebagian besar sudah ada** di panel FC (`app/Filament/FC/`) — bukan mengarang yang baru. Setiap keputusan ditautkan ke artefak nyata di codebase agar developer dapat mengimplementasikan lanjutan tanpa ambiguitas. Tidak ada business logic / query / engine baru yang diperkenalkan.

---

## 0. Peta Filosofi Modul

| Modul | Menjawab | Actor utama |
|---|---|---|
| Dashboard Operasional | Apa yang sedang terjadi hari ini? | Office / Management |
| Pelacakan & Monitoring | Unit mana yang butuh perhatian? | Office Admin |
| Detail Unit Workspace | Apa kondisi unit ini? | Semua (observasi) |
| **Field Coordinator Workspace** | **Pekerjaan apa yang harus saya selesaikan sekarang?** | **Field Coordinator** |

---

## 1. Actor Definition

**Primary Actor:** Field Coordinator (role `field_coordinator`).
**Bukan:** Super Admin, Office Admin, Management.

**Scope (dikunci sistem, sudah ada):** Seorang FC adalah koordinator dari **tepat satu** Depo **atau** Pool (`depots.coordinator_user_id` / `pools.coordinator_user_id`).
- 0 penugasan → `403` ("belum ditetapkan sebagai Koordinator").
- >1 penugasan → `409` ("konfigurasi dobel").
- Ref: [`ScopeByBranchAndDepot`](../../app/Http/Middleware/ScopeByBranchAndDepot.php) → mengikat `scope.depot_id` / `scope.pool_id`, `scope.branch_id`, `scope.mode`.

**Konsekuensi arsitektural:** FC **tidak mencari unit**. Sistem memberi pekerjaan yang **sudah ter-scope** ke depo/pool-nya. FC melihat pekerjaan miliknya, bukan seluruh armada.

---

## 2. Actor Journey

```
Login
  ↓
Panel FC (/fc)                     ← auth: canAccessPanel('fc') = isFieldCoordinator || isSuperAdmin
  ↓                                   middleware: EnsurePanelRole + ScopeByBranchAndDepot
Tugas Operasional ("My Tasks")     ← titik mulai kerja (OperationalTasks page)
  ↓
[pilih 1 task]
  ↓
Detail / Aksi Operasional          ← informasi unit + aksi sesuai tahap
  ↓
Complete Action                    ← appendTrack / input inspeksi / upload bukti
  ↓
Next Task                          ← kembali ke My Tasks (task selesai keluar dari daftar aktif)
```

FC **tidak** menjadikan Dashboard Operasional sebagai halaman utama. Titik masuk kerja = **Tugas Operasional**.

---

## 3. Navigation

Panel FC (sudah ada, [`FieldCoordinatorPanelProvider`](../../app/Providers/Filament/FieldCoordinatorPanelProvider.php)):

- `id='fc'`, `path='/fc'`, `home = Dashboard`.
- Nav groups: **Operasional Lapangan** · Armada & K3 · Laporan & Notifikasi.

| Item nav (existing) | Grup | Peran dalam actor arch |
|---|---|---|
| Dashboard | (default) | Ringkasan pagi (briefing, readiness) — **bukan** titik mulai kerja |
| **Tugas Operasional** (`OperationalTasks`) | Operasional Lapangan | **Entry point pekerjaan = "My Tasks"** |
| Yard Inventory (`YardDashboard`) | Operasional Lapangan | Inventori unit di yard (fokus `handover_depot`) |
| Kesiapan MP (`MpReadinessMonitoring`) | Operasional Lapangan | Kesiapan manpower |
| Riwayat Pengiriman (FC `ShipmentResource`) | — | **Read-only** (canCreate=false, canDeleteAny=false) |
| Loading / Container Readiness / Briefing (Resources) | Operasional Lapangan | Form operasional per aktivitas |

**Rekomendasi freeze:** Home FC sebaiknya mengarah ke **Tugas Operasional** sebagai pusat kerja (Dashboard tetap ada untuk ringkasan pagi). Perubahan `homeUrl` = keputusan implementasi, di luar sprint ini.

---

## 4. Responsibility Matrix

FC bertanggung jawab atas **eksekusi operasional lapangan pada depo/pool-nya**, bukan monitoring menyeluruh.

| Bertanggung jawab (YA) | Bukan tanggung jawab (TIDAK) |
|---|---|
| Pickup unit | Melihat KPI bulanan |
| Serah terima (handover depo) | Monitoring seluruh unit lintas depo |
| Loading / stuffing | Approval administratif |
| Pemeriksaan unit (inspeksi per tahap) | Perencanaan voyage |
| Upload bukti (dokumen/foto) | Manajemen customer |
| Update tahap (tracking) | Pelaporan manajemen |
| Menambah catatan operasional | |

Batas: FC hanya menyentuh unit yang **ter-scope ke depo/pool-nya** (via `assigned_depot_id` / `coordinator_id`, lihat §6).

---

## 5. Permission Matrix

Ground: `User::canAccessPanel('fc')`, `ScopeByBranchAndDepot`, FC `ShipmentResource` read-only, `Shipment::appendTrack()` (satu-satunya jalur mutasi tracking resmi), `InspectUnitPage` + `InspectionGateEvaluator`.

| Aksi | FC | Mekanisme existing |
|---|:---:|---|
| Update Tahap (tracking) | ✅ (tahap miliknya) | `Shipment::appendTrack()` via halaman operasional |
| Input Pemeriksaan | ✅ | `InspectUnitPage` + `InspectionGateEvaluator` |
| Upload Dokumen / Bukti | ✅ | Halaman operasional (attachments existing) |
| Menambah Catatan | ✅ | Field note pada track/inspeksi |
| Input Loading / Container Readiness | ✅ | `LoadingSessionResource` / `ContainerReadinessSessionResource` |
| Mengubah Shipment (create/edit inti) | ❌ | FC `ShipmentResource::canCreate() = false`; edit inti bukan di panel FC |
| Menghapus Shipment | ❌ | `canDeleteAny() = false` |
| Mengubah Customer | ❌ | Tidak ada resource Customer di panel FC |
| Mengubah Voyage | ❌ | Tidak ada resource Voyage di panel FC |
| Menghapus / mengedit Timeline & History | ❌ | Track bersifat append-only; tidak ada delete/edit di FC |
| Akses unit lintas depo/pool | ❌ | `ScopeByBranchAndDepot` mengunci ke satu unit |

Prinsip: **FC menambah kemajuan (append), tidak pernah mengubah/menghapus keputusan atau riwayat.**

---

## 6. Task Model & Lifecycle

**Sumber tugas (sudah ada — `OperationalTasks::getTableQuery()`):** sistem menurunkan tugas, FC tidak membuat/mencarinya. Sebuah shipment menjadi tugas FC bila:

- **Fase pra-transfer** (outbound): `assigned_depot_id = depot FC` **atau** `coordinator_id = FC` **dan** tahap ∈ {Pickup, Handover, Stuffing, DeliveryToPort, Stacking, UnitLoading, OnShip, VesselDepart}; **atau**
- **Fase pasca-transfer** (inbound): `pod` shipment mengarah ke port depo FC **dan** tahap ∈ {VesselArrival, Unloading, HandoverTrucking, DeliveryToCustomer}; **atau**
- **Eksepsi**: tahap `Hold` pada unit dalam scope-nya.

**Anatomi tugas (Assignment Model):**

```
Assigned To      → depot FC (assigned_depot_id) / coordinator_id
Due Time         → berbasis prioritas/estimasi (data existing; belum ada SLA per-task)
Current Stage    → TrackStatus tahap terakhir (latestTrack)
Required Action  → aksi FC sesuai tahap (lihat §9)
Completion Status→ tahap dilanjutkan via appendTrack → tugas keluar dari daftar aktif
```

**Task Lifecycle:**

```
Muncul (masuk scope + tahap aktif)
   ↓
Dikerjakan (FC buka task → aksi sesuai tahap)
   ↓
Selesai (appendTrack ke tahap berikutnya / inspeksi submitted)
   ↓
Keluar dari "My Tasks" aktif → tercatat di "Completed Today"
```

**Kapan tugas dianggap selesai:** ketika aksi tahap tuntas dan tahap dimajukan (`appendTrack`) atau inspeksi tahap ter-*submit* (gate decision tercatat). Tidak ada status "selesai" terpisah — kemajuan tahap = penyelesaian tugas.

---

## 7. Workspace Hierarchy ("My Tasks")

Urutan informasi workspace FC (task-based, **bukan** daftar seluruh unit):

```
1. Today's Tasks      → ringkasan: "Hari Ini · N Tugas" + breakdown per jenis
                         (Pickup 2 · Loading 1 · Inspeksi 2)
2. Priority Tasks     → tugas mendesak / Hold / mendekati deadline di atas
3. Assigned Units     → daftar task card (unit yang menjadi pekerjaan FC)
4. Operational Action → aksi per task, mengikuti tahap (§9)
5. Completed Today    → tugas yang sudah diselesaikan hari ini
```

Ini memperluas `OperationalTasks` yang ada (yang sudah menyediakan daily setup + daftar tugas ter-scope) menjadi hierarki task-based yang eksplisit. **Bukan** Shipment List.

---

## 8. Task Card (contract)

Setiap task card minimal memuat (dapat dipindai dalam hitungan detik):

| Field | Sumber (existing) |
|---|---|
| Nomor Unit | `units.reg_no` |
| Model | `units.model_no` |
| Lokasi | depo/pool aktif · `pickup_location` |
| Tahap Saat Ini | `latestTrack.status` (TrackStatus) |
| Action Berikutnya | derivasi tahap → aksi (§9) |
| Prioritas | `shipments.priority` (Normal / Mendesak) |
| Deadline (jika ada) | estimasi existing (`eta` / `estimated_ready_at`) |

Tidak menambah kolom database — seluruhnya dari data yang sudah ada.

---

## 9. Operational Action per Stage

Workspace **tidak** menampilkan semua tombol — hanya aksi relevan dengan tahap. Aksi = jalur existing (bukan engine baru).

| Tahap (TrackStatus) | Aksi FC | Halaman/mekanisme existing |
|---|---|---|
| Pickup | Update Pickup + Inspeksi `pickup` | `InspectUnitPage` → `appendTrack(Pickup)` |
| Handover | Serah Terima Depo + Inspeksi `handover_depot` | `InspectUnitPage` / `YardDashboard` |
| Stuffing / Loading | Input Loading + Inspeksi `loading` | `LoadingSessionResource` / `ContainerReadinessSessionResource` |
| DeliveryToPort / Stacking / UnitLoading | Update Tahap | `appendTrack(...)` |
| VesselDepart | Konfirmasi Keberangkatan | `User::canUpdateVesselDepart()` (guard existing) |
| VesselArrival / Unloading | Terima di Depo Tujuan | halaman operasional (inbound) |
| HandoverTrucking / DeliveryToCustomer | Inspeksi `dooring` + Konfirmasi Dooring | `InspectUnitPage` → `appendTrack(...)` |

Gate inspeksi (existing, tidak berubah): `accept` · `allow_with_remark` · `return_to_pdc` ([`UnitInspection`](../../app/Models/UnitInspection.php)).
Urutan tahap = `TrackStatus` — **tidak boleh diubah** oleh sprint ini.

---

## 10. Integrasi dengan Detail Unit Workspace

**Prinsip kunci sprint:** *"Role menentukan action, bukan layout."*

Yang **dibekukan** dan **dibagikan** lintas actor adalah **UX Architecture** (bukan satu halaman terikat panel):

1. **Information Hierarchy** (Detail Unit Workspace FROZEN v1.1): Hero → Timeline → Informasi Operasional → Pemeriksaan → Riwayat → Dokumen.
2. **Data layer** (`DetailUnitProvider` → `UnitDetailData`) — **role-agnostic**, tanpa query baru.

Yang **berbeda per role** hanyalah **Operational Action layer**:

| Aspek | Office (admin panel) | Field Coordinator (fc panel) |
|---|---|---|
| Layout / hierarki informasi | Sama (frozen) | Sama (frozen) |
| Data | `DetailUnitProvider` | `DetailUnitProvider` (sama) |
| Aksi | Observasi + "Update Tahap" (link ke manage) | Aksi operasional sesuai tahap (§9) |
| Surface hari ini | `UnitWorkspace` (admin) | `OperationalShipmentPage` + `InspectUnitPage` (fc) |

**Kondisi saat ini (jujur):** hierarki informasi 6-section sudah diimplementasikan di panel **admin** (`UnitWorkspace`). Di panel **FC**, surface aksi operasional sudah ada sebagai `OperationalShipmentPage` + `InspectUnitPage`. Keduanya mengonsumsi domain data yang sama.

**Target konvergensi (implementasi berikutnya, bukan sprint ini):** panel FC merender **hierarki informasi yang sama** (reuse `DetailUnitProvider` + struktur 6-section) dengan **action layer FC**. Tidak ada layout/CRUD/query baru — hanya menautkan action layer per-role ke workspace yang sama. Kontrak ini yang dibekukan sekarang.

---

## 11. Future Scalability (Actor Reuse)

Arsitektur ini **satu kerangka**, dipakai ulang oleh actor lain **tanpa membuat workspace baru**. Yang berbeda hanya **Task · Permission · Action**:

| Actor | Task (scope) | Permission | Action |
|---|---|---|---|
| Coordinator (office) | Unit lintas depo (cabang) | Observasi + koordinasi | Update tahap, koordinasi |
| **Field Coordinator** | Unit depo/pool-nya | Append tahap/inspeksi/bukti | Pickup, Loading, Inspeksi, Dooring |
| PDI Inspector (future) | Unit menunggu inspeksi | Input inspeksi saja | Input PDI, gate decision |
| Driver (future) | Unit yang ia bawa | Update posisi/serah terima | Konfirmasi pickup/delivery |

Kerangka tetap: **My Tasks (ter-scope) → Detail (hierarki frozen) → Action (per-role) → Complete → Next.** Menambah actor = mendefinisikan (scope tugas, permission, action set) — **bukan** membangun ulang workspace, data, atau engine.

---

## 12. Definition of Done — Jawaban Eksplisit

| Pertanyaan | Jawaban |
|---|---|
| Tujuan utama FC? | Menyelesaikan pekerjaan operasional lapangan (pickup, handover, loading, inspeksi, dooring) pada depo/pool-nya. |
| Halaman pertama setelah login? | Panel FC → **Tugas Operasional** ("My Tasks") sebagai pusat kerja (Dashboard = ringkasan pagi). |
| Dari mana FC memulai pekerjaan? | Dari daftar **Tugas Operasional** yang sudah ter-scope ke depo/pool-nya. |
| Bagaimana tugas diberikan? | **Sistem menurunkan** dari `assigned_depot_id`/`coordinator_id` + tahap aktif (§6). FC tidak mencari/membuat tugas. |
| Kapan tugas dianggap selesai? | Saat tahap dimajukan (`appendTrack`) atau inspeksi tahap ter-submit → tugas keluar dari daftar aktif ke "Completed Today". |
| Action yang boleh? | Update tahap, input pemeriksaan, upload dokumen/bukti, menambah catatan, input loading/container (§5). |
| Action yang tidak boleh? | Ubah/hapus shipment, ubah customer/voyage, hapus/edit timeline & history, akses lintas depo (§5). |
| Hubungan My Tasks ↔ Detail Unit Workspace? | My Tasks = pintu masuk pekerjaan (ter-scope); Detail = hierarki informasi frozen yang sama; role FC menambahkan action layer operasional (§10). |
| Reuse untuk actor lain? | Kerangka My Tasks → Detail → Action dipakai ulang; hanya Task/Permission/Action yang berbeda (§11). |

---

## 13. Constraint yang Dihormati

Tidak ada: CRUD baru · tracking/inspection/timeline engine baru · workflow baru · query baru · business logic baru · perubahan UI. Dokumen ini murni **blueprint arsitektur** yang memformalkan struktur FC yang sudah ada + kontrak integrasi & scalability.

---

## 14. Referensi Codebase (grounding)

| Area | Artefak |
|---|---|
| Panel & scope | `FieldCoordinatorPanelProvider`, `ScopeByBranchAndDepot`, `User::canAccessPanel('fc')` |
| My Tasks | `app/Filament/FC/Pages/OperationalTasks.php` ("Tugas Operasional") |
| Aksi operasional | `InspectUnitPage`, `OperationalShipmentPage`, `YardDashboard`, `LoadingSessionResource`, `ContainerReadinessSessionResource`, `BriefingSessionResource` |
| Read-only history | FC `ShipmentResource` ("Riwayat Pengiriman", canCreate/canDeleteAny = false) |
| Tahap & inspeksi | `App\Enums\TrackStatus`, `App\Models\UnitInspection` (STAGES, GATE_*), `Shipment::appendTrack()`, `InspectionGateEvaluator` |
| Data workspace | `DetailUnitProvider` → `UnitDetailData` (role-agnostic) |
| Assignment | `shipments.assigned_depot_id`, `shipments.coordinator_id`, `depots/pools.coordinator_user_id` |

→ **Field Coordinator Workspace v1.0 (Actor Architecture): FROZEN.**
