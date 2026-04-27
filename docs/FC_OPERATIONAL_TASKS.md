@@ -52,57 +52,92 @@ ### Task FC-03 — Detail shipment FC

-0

# FC Operational Tasks — Step by Step

Dokumen ini memecah pekerjaan role **Field Coordinator (FC)** menjadi task kecil berurutan agar bisa dieksekusi satu per satu.

> Dokumen PRD khusus FC untuk validasi ekspektasi tugas tersedia di `docs/PRD_FC.md`.

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


### Setelah FC-05: apakah ganti tujuan?

Tidak ganti tujuan besar. Setelah FC-05, jalankan **stabilisasi + transisi scope** dulu sebelum loncat ke inisiatif baru.

Urutan yang disarankan:

1. **FC-05A — Stabilization gate (wajib)**
   - Tutup bug blocker dari FC-01 s/d FC-05.
   - Jalankan lint + test gate.
   - Verifikasi policy/scope pada data existing.

2. **FC-05B — Client migration gate (wajib)**
   - Client read path pindah ke `effective_branch_id`.
   - `branch_id` tetap dikirim sebagai fallback sementara (legacy).
   - Monitor error 4xx/5xx dan mismatch scope selama masa transisi.

3. **FC-06 — AppSheet hardening lanjutan (opsional setelah gate hijau)**
   - Fokus reliability ingest + idempotency + observability.
   - Tidak menambah fitur besar sebelum gate FC-05A/05B hijau.

4. **FC-07 — UX/performance polish (opsional)**
   - Perbaikan UX FC briefing/tracking berdasarkan feedback lapangan.
   - Query/index tuning jika ada bottleneck nyata.

Rule sederhana sebelum lanjut jauh:
- Jika FC-05A/05B belum hijau, **jangan** mulai FC-06/FC-07.
- Jika ada 500 di flow FC, rollback ke stabilization checklist dulu.

## 3) Definition of Done per Task

- Scope tidak melebar dari task aktif.
- Test minimal dijalankan.
- Tidak mengubah logic shipment land.
- Ringkasan perubahan + risiko tercatat.

## 3.1) Prompt siap pakai dari hasil terakhir

Kalau mau lanjut **prompting-only** dari status implementasi terakhir (Fixes 6–9), pakai:
- `docs/FC_NEXT_PROMPT.md`
- Eksekusi dilakukan di **OpenCode CLI** (plan + build di CLI), bukan patch langsung di chat.

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