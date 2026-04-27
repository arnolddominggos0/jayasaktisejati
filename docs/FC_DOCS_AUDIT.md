# Audit Struktur Dokumen FC (Branch Saat Ini)

Tujuan dokumen ini: menjawab apakah file `.md` perlu dihapus atau dipertahankan.

## Ringkasan cepat

- **Belum perlu hapus langsung** sekarang.
- Ada overlap antar dokumen FC, jadi lebih aman pakai pendekatan **deprecate bertahap**.
- Rekomendasi: tetapkan 1 entrypoint utama lalu merger konten file pendukung.

## Inventaris + keputusan

| File | Fungsi saat ini | Overlap | Keputusan |
|---|---|---|---|
| `docs/PRD.md` | PRD lengkap produk | Rendah | **Keep** |
| `prd.md` | PRD ringkas + pointer dokumen | Sedang (dengan FC_PRD_SETUP_REBUILD) | **Keep (ringkas/index)** |
| `docs/FC_PRD_SETUP_REBUILD.md` | Runbook setup + staged execution + prompt final | Tinggi (dengan FC_NEXT_PROMPT & FC_SCOPE_TRANSITION) | **Keep (jadikan canonical FC runbook)** |
| `docs/FC_OPERATIONAL_TASKS.md` | Breakdown task FC-01..FC-07 | Rendah-sedang | **Keep** |
| `docs/FC_SCOPE_TRANSITION.md` | Gate lint/test + contoh auth + triage | Tinggi (sudah ada di FC_PRD_SETUP_REBUILD) | **Deprecate (merge ke canonical runbook)** |
| `docs/FC_NEXT_PROMPT.md` | Prompt pack A/B/C | Tinggi (prompt final sudah ada di runbook) | **Deprecate (simpan sementara 1 sprint)** |

## Struktur target (disarankan)

1. `prd.md` → index singkat (arah branch + link utama).
2. `docs/PRD.md` → PRD produk lengkap.
3. `docs/FC_PRD_SETUP_REBUILD.md` → **satu-satunya runbook FC transisi + setup + prompt final**.
4. `docs/FC_OPERATIONAL_TASKS.md` → detail eksekusi task per fase.

## Rencana cleanup aman (tanpa putus konteks)

### Phase 1 (sekarang)
- Pertahankan semua file.
- Tambahkan label "deprecated" pada `FC_SCOPE_TRANSITION.md` dan `FC_NEXT_PROMPT.md`.
- Arahkan pembaca ke `FC_PRD_SETUP_REBUILD.md`.

### Phase 2 (setelah 1 sprint stabil)
- Hapus `FC_SCOPE_TRANSITION.md` dan `FC_NEXT_PROMPT.md`.
- Pastikan semua referensi lama sudah dipindah.

## Kriteria boleh hapus

- Tidak ada referensi aktif dari PR/template/checklist.
- Konten sudah 100% dimigrasi ke runbook canonical.
- Tim reviewer setuju (minimal 1 approval dokumentasi).
