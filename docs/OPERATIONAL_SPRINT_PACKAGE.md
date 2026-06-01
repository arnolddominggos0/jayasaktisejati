# Operational Sprint Package (Shipment Sea Core)

Paket ini dipakai untuk eksekusi cepat di branch `feature/dashboard-fix` dengan fokus operasional: **FR-04, FR-05, FR-06**.

## Scope Paket

1. FR-04 — Shipment listing & visibility (sea-only + scoping).
2. FR-05 — Tracking workflow hardening (note/checksheet/attachment/override).
3. FR-06 — Dokumen operasional (waybill/resi/packing list).

---

## A. Prompt Pack (Copy-Paste)

### A1) Scope Lock
```txt
Refer to @prd.md and @docs/PRD.md.
Lock scope to Shipment sea operational core only (FR-04/FR-05/FR-06).
Do not modify land shipment logic.
Return assumptions + constraints + acceptance criteria (max 10 bullets).
```

### A2) Mini Plan (1 slice)
```txt
Create implementation plan for ONE smallest operational slice.
Return:
1) files to change
2) data flow summary
3) risks
4) test commands
Max 10 bullets.
```

### A3) Build
```txt
Implement the approved slice only.
Do not touch unrelated modules.
Keep role/branch/depot scoping intact.
Return unified patch + affected files + test commands.
```

### A4) Review
```txt
Review the diff against FR-04/FR-05/FR-06.
Report:
- missing validation
- missing scoping/auth checks
- operational data consistency risks
Max 10 bullets.
```

### A5) Test Gate
```txt
Provide and run tests for:
1) sea-only listing behavior
2) tracking workflow validations
3) print document authorization + mode guard
4) no regression on land logic
If failed, propose smallest fix.
```

---

## B. Sprint Slice Order

### Slice 1 (P0)
- Authorization/scoping hardening untuk shipment list/history/track.

### Slice 2 (P0)
- Tracking workflow hardening (note/checksheet/attachment/override).

### Slice 3 (P0)
- Dokumen operasional: waybill/resi/packing list (sea-only guard).

### Slice 4 (P1)
- Dashboard operasional ringkas (kpi + aktivitas tracking).

---

## C. Execution Commands

```bash
# 1) cek status branch
 git status --short

# 2) jalankan test targeted
 php artisan test --filter=Shipment
 php artisan test --filter=Tracking
 php artisan test --filter=Print

# 3) syntax check cepat (opsional)
 php -l app/Http/Controllers/ShipmentPrintController.php
 php -l app/Filament/FC/Resources/ShipmentResource.php
```

---

## D. Definition of Done (Operational)

Task dianggap selesai jika:

- Scope tetap di FR-04/05/06.
- Role + branch/depot scoping lolos verifikasi.
- Tracking update tidak loncat status tidak valid.
- Dokumen hanya bisa dicetak oleh user berhak.
- Test minimum dijalankan dan hasil tercatat.


## E. FC Task Breakdown

Untuk eksekusi role field coordinator satu per satu:
- `docs/FC_OPERATIONAL_TASKS.md`