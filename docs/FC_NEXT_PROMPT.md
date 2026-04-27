# FC Next Prompt Pack (berdasarkan hasil prompting terakhir)

Dokumen ini dipakai kalau kita mau **lanjut via prompting** dari status implementasi terakhir (Fixes 6–9) tanpa loncat scope.

## A) Prompt verifikasi final sebelum merge

```txt
Gunakan baseline hasil terakhir Fixes 6–9 berikut sebagai konteks (sudah implemented):
- User model guard hardening pada canonical scope fields.
- Admin scope query alignment ke effectiveBranchId().
- AuthController response transition: branch_id + scope_branch_id + effective_branch_id.
- ShipmentResource fallback ke effectiveBranchId().

Tugas kamu sekarang hanya verifikasi dan hardening final, TANPA menambah fitur baru:
1) Audit ulang semua fallback branch di area FC/Admin agar tidak ada fallback ke legacy branch_id untuk read scoping.
2) Jalankan lint per-file untuk file yang terdampak.
3) Jalankan targeted regression tests berikut:
   - php artisan test --filter=ShipmentPolicyScopeTest
   - php artisan test --filter=CanonicalScopeModelTest
   - php artisan test --filter=AppSheetCanonicalScopeTest
   - php artisan test --filter=AppSheetBriefingIngestionTest
4) Berikan ringkasan hasil dalam format:
   - Unified patch summary
   - Changed files
   - Risk notes
   - Exact test commands + result (pass/fail + alasan)

Penting:
- Jangan klaim test "pass" jika command tidak benar-benar jalan.
- Jika environment tidak bisa install dependency/test, tulis blocker secara eksplisit.
- Jangan ubah logic shipment land.
```

## B) Prompt gate singkat (reviewer)

```txt
Buat gate singkat sebelum merge dari status terakhir Fixes 6–9.
Output wajib:
1) Checklist lint + test yang benar-benar dieksekusi.
2) Bukti response AuthController@login dan /me (contoh JSON) untuk field transisi.
3) Catatan migration client:
   - effective_branch_id sebagai source of truth
   - branch_id legacy dipertahankan sementara
4) Jika ada error 500 di /fc/briefing-sessions, tulis langkah triage berurutan + command yang dipakai.
```

## C) Prompt planning setelah FC-05 (tanpa loncat)

```txt
Kita tidak ganti tujuan besar setelah FC-05.
Buat rencana eksekusi berurutan:
- FC-05A Stabilization gate
- FC-05B Client migration gate
- FC-06/FC-07 hanya jika gate hijau

Sertakan:
- entry criteria
- exit criteria
- rollback trigger
- test gate per tahap
```

## D) Cara eksekusi (wajib via OpenCode CLI)

Gunakan prompt di dokumen ini sebagai **rencana kerja** untuk dijalankan di OpenCode CLI, bukan untuk langsung patch di sesi diskusi ini.

Alur eksekusi:
1. Buka OpenCode CLI pada repo target.
2. Tempel Prompt A/B/C sesuai kebutuhan tahap.
3. Biarkan agent CLI membuat patch + menjalankan command lint/test.
4. Review output (summary, risk, test result) sebelum merge.

Template instruksi singkat:

```txt
Eksekusi task ini di OpenCode CLI (plan + build di CLI), jangan lakukan perbaikan langsung di chat.
Gunakan konteks dari docs/FC_NEXT_PROMPT.md dan jalankan prompt [A/B/C].
```

