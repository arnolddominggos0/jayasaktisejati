# OpenCode CLI Playbook — Fokus Shipment Laut

Dokumen ini berisi langkah praktis untuk menjalankan prompting di CLI agar hemat token dan tetap terarah pada domain **shipment laut**.

## 0) Plan dulu atau langsung build?

**Jawaban singkat: Plan dulu (wajib), lalu build bertahap.**

Urutan yang disarankan:
1. **Plan**: definisikan scope kecil + acceptance criteria.
2. **Build**: implement 1 vertical slice.
3. **Review**: cek kesesuaian dengan FR-05 + role/scope.
4. **Test**: jalankan test minimum.
5. **Iterasi**: lanjut slice berikutnya.

Kapan boleh langsung build?
- Hanya jika task sangat kecil, dampak terbatas, dan acceptance criteria sudah jelas.
- Untuk perubahan shipment API/data, tetap mulai dari plan singkat (5-10 bullet).

## 1) Perubahan yang Perlu Dilakukan Sekarang

1. Tetapkan fokus scope ke **FR-05 Manajemen Shipment** sebagai prioritas utama.
2. Gunakan `prd.md` untuk konteks awal, lalu panggil section spesifik dari `docs/PRD.md` saat butuh detail.
3. Eksekusi pekerjaan secara bertahap (vertical slices), bukan sekali jalan semua fitur.
4. Terapkan format prompt standar agar output konsisten dan mudah direview.

## 2) Prioritas Implementasi Shipment (Sprint-Ready)

### P0 — Wajib (MVP Shipment)
- Shipment CRUD inti.
- Shipment tracking history.
- Cetak dokumen operasional (waybill/resi/packing list).
- Validasi role + branch/depot scope pada endpoint shipment.

### P1 — Penting
- SLA status evaluator untuk shipment terlambat/on-time.
- Notifikasi event penting (ETA reminder/status kritikal).

### P2 — Lanjutan
- Dashboard shipment KPI per branch.
- Optimasi query dan observability (log event shipment).

## 3) Template Prompt OpenCode (Hemat Token)

### A. Planning prompt
```txt
Refer to @prd.md.
Focus only on Shipment domain (FR-05).
Return:
1) smallest deliverable
2) files to modify
3) acceptance criteria
Limit to 12 bullets.
```

### B. Implement prompt (per vertical slice)
```txt
Refer to @docs/PRD.md section "6. Kebutuhan Fungsional", focus FR-05 only.
Implement shipment tracking history API with role + branch scoping.
Do not modify unrelated modules.
Return patch + test commands.
```

### C. Review prompt
```txt
Review current diff against FR-05 shipment requirements.
Report:
- missing validations
- missing authorization checks
- data consistency risks
Max 10 bullets.
```

## 4) Checklist Done per Task Shipment

- [ ] Endpoint memiliki validasi input yang jelas.
- [ ] Otorisasi role diterapkan.
- [ ] Branch/depot scoping diterapkan.
- [ ] Event/status shipment tercatat di histori.
- [ ] Test minimal happy-path + authorization edge-case.

## 5) Guardrails Prompting

- Jangan minta "build all" dalam satu prompt.
- Batasi output: bullet/line limit.
- Selalu sebut domain: **Shipment only**.
- Jika ambigu, minta AI membaca section spesifik PRD, bukan seluruh dokumen.


## 6) SOP Step-by-Step Prompting di CLI

### Step 1 — Scope lock
Prompt:
```txt
Refer to @prd.md.
Lock scope to Shipment domain only (FR-05).
List assumptions and acceptance criteria in max 10 bullets.
```

### Step 2 — Plan kecil
Prompt:
```txt
Refer to @docs/PRD.md section FR-05.
Create implementation plan for 1 smallest vertical slice.
Return: files to modify, risks, and test commands.
```

### Step 3 — Implement
Prompt:
```txt
Implement only the approved vertical slice.
Do not modify unrelated modules.
Return patch only.
```

### Step 4 — Self review
Prompt:
```txt
Review the diff against FR-05 + role authorization + branch/depot scoping.
Report gaps in max 10 bullets.
```

### Step 5 — Test gate
Prompt:
```txt
Provide and run minimal tests for:
- happy path
- authorization/scoping edge case
If any test fails, propose smallest fix.
```

### Step 6 — Commit summary
Prompt:
```txt
Summarize changes in 5 bullets:
- what changed
- why
- risk
- tests
- next slice
```

## 7) End-to-End Step-by-Step (Lengkap)

Berikut alur lengkap yang bisa diikuti setiap kali menjalankan OpenCode CLI untuk shipment.

### Step 0 — Siapkan konteks
1. Pastikan branch kerja benar (mis. `feature/dashboard-fix`).
2. Pastikan file referensi tersedia:
   - `prd.md`
   - `docs/PRD.md`
   - `docs/OPENCODE_SHIPMENT_PLAYBOOK.md`
3. Tentukan target task kecil (contoh: "tracking history API").

### Step 1 — Scope lock (wajib)
Tujuan: mencegah scope melebar.

Prompt:
```txt
Refer to @prd.md and @docs/PRD.md.
Lock scope to Shipment sea only (FR-04/FR-05/FR-06).
List assumptions, constraints, and acceptance criteria.
Max 10 bullets.
```

Output yang harus keluar:
- Daftar asumsi.
- Batasan scope.
- Acceptance criteria terukur.

### Step 2 — Rencana implementasi (mini-plan)
Tujuan: tentukan vertical slice paling kecil.

Prompt:
```txt
Create implementation plan for ONE smallest vertical slice only.
Return:
1) files to change
2) data flow summary
3) risks
4) test commands
Max 12 bullets.
```

Output yang harus keluar:
- Daftar file target.
- Risiko utama.
- Test command awal.

### Step 3 — Implementasi slice #1
Tujuan: eksekusi patch minimal, tidak lintas domain.

Prompt:
```txt
Implement only the approved slice.
Do not change unrelated modules.
Return patch + migration impact (if any).
```

Output yang harus keluar:
- Patch kode terbatas pada scope.
- Catatan dampak schema (jika ada).

### Step 4 — Self-review berbasis PRD
Tujuan: validasi sebelum test.

Prompt:
```txt
Review the diff against FR-05 requirements and role/branch/depot scoping.
Report:
- missing validation
- missing authorization checks
- data consistency risks
Max 10 bullets.
```

Output yang harus keluar:
- Daftar gap + rekomendasi perbaikan.

### Step 5 — Test gate
Tujuan: memastikan perubahan aman secara minimum.

Prompt:
```txt
Run/provide tests for:
1) happy path
2) authorization/scoping edge case
3) regression risk around shipment tracking
If failed, propose smallest fix.
```

Output yang harus keluar:
- Command test.
- Ringkasan hasil pass/fail.
- Perbaikan kecil jika ada fail.

### Step 6 — Ringkasan commit
Tujuan: dokumentasi perubahan yang mudah direview.

Prompt:
```txt
Summarize final change in 6 bullets:
- what changed
- why
- affected files
- risk
- tests
- next smallest slice
```

Output yang harus keluar:
- Ringkasan siap masuk PR description.

### Step 7 — Lanjut ke slice berikutnya
Tujuan: iterasi bertahap sampai scope selesai.

Aturan:
- Jika slice sekarang belum lolos test/review, jangan lanjut.
- Kalau lolos, kembali ke Step 2 untuk slice berikutnya.

---

## 8) Anti-Pattern yang Harus Dihindari

1. Prompt "build semua fitur shipment sekaligus".
2. Meminta AI membaca seluruh PRD di setiap step.
3. Tidak menyebut role/scoping pada task data sensitif.
4. Tidak menuliskan acceptance criteria sebelum implementasi.
5. Menjalankan implementasi tanpa review/test gate.

---

## 9) Definisi Selesai (Definition of Done)

Task dianggap selesai jika seluruh kondisi berikut terpenuhi:

- Scope sesuai FR target (tidak melebar).
- Role + branch/depot scoping tervalidasi.
- Histori tracking/aktivitas tetap konsisten.
- Test minimum dijalankan dan tercatat.
- Ringkasan perubahan siap untuk PR.
