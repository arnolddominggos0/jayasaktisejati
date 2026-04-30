# OpenCode CLI — Execute Now (Based on Current FC Docs)

Dokumen ini berisi prompt siap pakai untuk **langsung eksekusi di OpenCode CLI**.

## Cara pakai di CLI (jawaban langsung)

**Pakai dua-duanya:**
1. Pilih Prompt #1 / #2 / #3 sesuai tahap.
2. Di dalam prompt, tetap gunakan `refer to @...` (atau tulis path dokumen) supaya konteksnya jelas.

Format yang benar saat kirim ke CLI:

```txt
Refer to @docs/FC_PRD_SETUP_REBUILD.md @docs/FC_SCOPE_TRANSITION.md @docs/FC_OPERATIONAL_TASKS.md @prd.md.
[Tempel isi Prompt #1 (FC-05A) dari dokumen ini]
```

Jadi, **bukan cuma kirim "#1 (FC-05A)" saja**.
Yang dikirim adalah isi prompt lengkap + referensi file.

---

## 1) Prompt #1 — FC-05A Stabilization Gate

```txt
Refer to docs/FC_PRD_SETUP_REBUILD.md, docs/FC_SCOPE_TRANSITION.md, docs/FC_OPERATIONAL_TASKS.md, and prd.md.

Goal: execute FC-05A Stabilization Gate only (no feature expansion).

Do:
1. Audit FC/Admin scope and authorization consistency.
2. Apply minimal patch only if blocker/bug is found.
3. Run lint and tests exactly:
   - ./vendor/bin/pint --test
   - php artisan test --filter=FC
   - php artisan test --filter=Shipment
   - php artisan test --filter=Tracking
   - php artisan test --filter=Print

Return format:
- Unified patch summary
- Changed files
- Risks
- Exact commands and real results (pass/fail)

Rules:
- Do not claim pass if command did not run.
- Do not modify land shipment logic.
- Stay within FC-05A scope.
```

## 2) Prompt #2 — FC-05B Client Migration Gate

```txt
Refer to docs/FC_PRD_SETUP_REBUILD.md and docs/FC_SCOPE_TRANSITION.md.

Goal: execute FC-05B migration gate for client scoping transition.

Do:
1. Verify API contract for AuthController@login and AuthController@me.
   Required fields in response.user:
   - branch_id (legacy)
   - scope_branch_id
   - effective_branch_id (source of truth)
2. Validate client-read scoping uses effective_branch_id.
3. Run targeted regression:
   - php artisan test --filter=ShipmentPolicyScopeTest
   - php artisan test --filter=CanonicalScopeModelTest
   - php artisan test --filter=AppSheetCanonicalScopeTest
   - php artisan test --filter=AppSheetBriefingIngestionTest

Return format:
- JSON evidence snippet for login/me
- Changed files
- Migration risk notes
- Exact commands and real results (pass/fail)

Rules:
- Do not remove branch_id yet.
- No feature expansion beyond migration gate.
```

## 3) Prompt #3 — Triage bila ada 500 `/fc/briefing-sessions`

```txt
Run incident triage for 500 on /fc/briefing-sessions using docs/FC_PRD_SETUP_REBUILD.md.

Do in order:
1. Check migration status.
2. Check canonical scope/backfill status for FC users.
3. Check application logs.
4. Propose smallest safe fix.

Commands:
- php artisan migrate:status
- tail -n 200 storage/logs/laravel.log

Return:
- probable root cause
- minimal patch
- verification commands
- rollback note
```

## 4) Kapan lanjut ke next task (FC-06/FC-07)

Lanjut hanya jika:
- FC-05A lint/test gate hijau,
- FC-05B migration gate hijau,
- tidak ada blocker 500 scope.
