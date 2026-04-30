# PRD Ringkas — Current Branch

## Arah saat ini
Fokus branch saat ini adalah stabilisasi transisi canonical scope:
- `effective_branch_id` menjadi source of truth untuk scoping client,
- `branch_id` tetap legacy fallback sementara,
- eksekusi setelah FC-05 wajib lewat gate stabilisasi dan migration gate.

## Fokus sekarang (jawaban singkat)
Fokus aktif saat ini masuk ke **stabilisasi transisi scope FC**:
1. **FC-05A Stabilization Gate** (audit scope/authorization + bereskan blocker).
2. **FC-05B Client Migration Gate** (client read scoping pindah ke `effective_branch_id`).

Belum masuk ekspansi fitur FC-06/FC-07 sebelum dua gate di atas hijau.
## Keputusan eksekusi sekarang
- **Mode sekarang: Prompting (OpenCode CLI)** untuk FC-05A/FC-05B.
- **Mode next task fitur baru** ditunda sampai gate stabilisasi + migration dinyatakan hijau.

## Dokumen utama (struktur baru)

1. `docs/PRD.md` → PRD lengkap produk.
2. `docs/FC_PRD_SETUP_REBUILD.md` → panduan setup ulang + build ulang + prompt final OpenCode CLI.
3. `docs/FC_OPERATIONAL_TASKS.md` → breakdown task FC bertahap.
4. `docs/FC_SCOPE_TRANSITION.md` → gate transisi + contoh response auth + troubleshooting 500.
5. `docs/FC_NEXT_PROMPT.md` → prompt pack operasional tambahan.


## Cara pakai

- Gunakan `docs/FC_PRD_SETUP_REBUILD.md` sebagai entrypoint eksekusi branch ini.
- Lakukan plan & build di OpenCode CLI, bukan patch langsung di chat.
- Audit duplikasi dokumen FC ada di `docs/FC_DOCS_AUDIT.md` (acuan keputusan keep/deprecate/hapus).
- Prompt eksekusi langsung OpenCode CLI: `docs/OPENCODE_EXECUTE_NOW.md`.