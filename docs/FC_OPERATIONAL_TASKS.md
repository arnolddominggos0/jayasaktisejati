# FC Operational Tasks — Step by Step

Dokumen ini memecah pekerjaan role **Field Coordinator (FC)** menjadi task kecil berurutan agar bisa dieksekusi satu per satu.

## 1) Scope FC

Fokus role FC di project ini:
- Melihat shipment yang ditugaskan.
- Update progres tracking lapangan.
- Melihat detail shipment + timeline.
- Monitoring KPI dasar di dashboard FC.

## 2) Urutan Task (One by One)

### Task FC-01 — Hardening akses data FC
Target:
- FC hanya melihat shipment yang assigned sesuai branch/depot/coordinator scope.
- Tidak ada akses lintas cabang/depot.

Prompt:
```txt
Refer to @docs/PRD.md and @docs/ALIGNMENT_MATRIX.md.
Implement FC-01 hardening for field coordinator data access scope.
Do not modify land shipment logic.
Return patch + test commands.
```

### Task FC-02 — Hardening form update tracking
Target:
- Validasi note/checksheet/attachment/override_reason konsisten.
- Mencegah transisi status yang tidak valid.

Prompt:
```txt
Implement FC-02 tracking workflow hardening in FC shipment tracking form.
Focus on note/checksheet/attachment/override_reason validations.
Do not touch unrelated modules.
Return patch + risk notes + test commands.
```

### Task FC-03 — Detail shipment FC
Target:
- Halaman detail menampilkan data penting lapangan (route, service, ETD/ETA, timeline).
- Data tampil stabil meski ada field null.

Prompt:
```txt
Implement FC-03 detail shipment improvements for field coordinator usability.
Keep scope only FC ViewShipment page/components.
Return patch + manual verification checklist.
```

### Task FC-04 — Dashboard FC minimal actionable
Target:
- Dashboard FC menampilkan status summary + aktivitas terbaru.
- Fokus data yang membantu eksekusi harian.

Prompt:
```txt
Implement FC-04 dashboard minimal actionable widgets for field coordinator.
Keep changes inside FC dashboard/widgets scope.
Return patch + KPI output checks.
```

### Task FC-05 — Dokumen operasional untuk FC (jika dibutuhkan role)
Target:
- Akses cetak dokumen untuk kasus operasional FC (sesuai kebijakan role).
- Guard mode sea + guard authorization.

Prompt:
```txt
Implement FC-05 print access flow for authorized FC use-cases.
Enforce role policy and mode sea guard.
Return patch + auth test commands.
```

## 3) Definition of Done per Task

- Scope tidak melebar dari task aktif.
- Test minimal dijalankan.
- Tidak mengubah logic shipment land.
- Ringkasan perubahan + risiko tercatat.

## 4) Suggested Commands

```bash
git status --short
php artisan test --filter=FC
php artisan test --filter=Shipment
php artisan test --filter=Tracking
```


## 5) Detail Tugas FC (Operasional Lapangan)

Tugas FC yang harus tertangkap di sistem:

1. **Briefing harian**
   - Cek kehadiran MP.
   - Cek kondisi kesehatan tim.
   - Cek kecukupan MP terhadap kebutuhan shipment harian.

2. **Cek APD/PPE**
   - Verifikasi APD wajib per personel sebelum eksekusi.
   - Catat status APD (lengkap/tidak lengkap) dan bukti foto jika perlu.

3. **Checkpoint unit saat loading/unloading**
   - Catat checkpoint di fase loading.
   - Catat checkpoint di fase unloading.
   - Simpan jam, lokasi, petugas, dan catatan anomali.

4. **Checksheet kendaraan lintas titik**
   - PDC.
   - Depo Asal (saat status handover).
   - Depo Tujuan.
   - Supir (saat handover ke supir/self-drive).

## 6) Mapping Proses ke Status Shipment

- **Handover** → checksheet awal unit + validasi APD + kesiapan MP.
- **Loading** → checkpoint unit loading + update catatan kondisi.
- **Vessel/Transit** → monitoring event & exception.
- **Unloading** → checkpoint unit unloading + checksheet tujuan.
- **Delivery/Handover Supir** → checksheet serah-terima ke supir + bukti final.

## 7) Integrasi ke AppSheet (Rencana Implementasi)

Tujuan integrasi: memudahkan input lapangan via mobile dengan form terstruktur, offline-friendly, dan sinkron ke sistem inti.

### 7.1 Modul Form AppSheet

1. **Form Briefing Harian**
   - Tanggal, lokasi, tim, jumlah hadir, jumlah fit to work, kecukupan MP, catatan.

2. **Form Cek APD**
   - Personel, checklist item APD, status, bukti foto, catatan.

3. **Form Checkpoint Unit**
   - Shipment code, status (loading/unloading), timestamp, lokasi, kondisi, foto.

4. **Form Checksheet Kendaraan**
   - Data kendaraan (model/no rangka/no mesin/warna/registrasi/cabang).
   - Checklist kondisi per area (eksterior, interior, perlengkapan, dokumen, aksesori).
   - Sign-off pihak terkait (PDC, Depo Asal, Depo Tujuan, Supir, Terima Cabang).

### 7.2 Integrasi Data ke Backend

- AppSheet menyimpan record ke tabel staging/endpoint API.
- Backend melakukan validasi role/scope + validasi status shipment.
- Data tervalidasi di-attach ke `ShipmentTrack` / entity checksheet terkait.
- Audit log disimpan untuk setiap submit/update.

### 7.3 Guardrails Integrasi

- Scope user tetap branch/depot-based.
- Form hanya aktif untuk status shipment yang relevan.
- Perubahan checksheet setelah status final dibatasi (lock policy).

## 8) Tambahan Definition of Done untuk FC

Task FC dianggap selesai jika:

- Briefing, APD, checkpoint, dan checksheet kendaraan sudah tercatat di flow status terkait.
- Bukti lapangan (foto/catatan/sign-off) tersimpan dan bisa diaudit.
- Integrasi AppSheet tidak melanggar role + branch/depot scope.
- Tim FC dapat submit data lapangan dengan effort minimal (mobile-first).
