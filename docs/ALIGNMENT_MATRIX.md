# Alignment Matrix — PRD & Dokumen Pendukung

Tujuan dokumen ini adalah memastikan implementasi selalu sinkron dengan PRD dan playbook, terutama saat fokus ke **shipment sea**.

## 1) Urutan Dokumen Acuan (Source of Truth)

1. `docs/PRD.md` → definisi requirement produk (apa yang harus dibangun).
2. `docs/OPENCODE_SHIPMENT_PLAYBOOK.md` → SOP cara kerja prompting (bagaimana membangun).
3. `docs/OPERATIONAL_SPRINT_PACKAGE.md` → paket eksekusi cepat (template prompt + urutan slice).
4. `prd.md` → ringkasan cepat untuk context prompt.

## 2) Scope Lock Wajib

Selalu gunakan scope lock berikut di awal:

```txt
Focus only on Shipment sea operational core (FR-04/FR-05/FR-06).
Do not modify land shipment logic.
```

## 3) Mapping Requirement ke Eksekusi

- **FR-04** → Listing & visibility shipment sea (scoping role/branch/depot).
- **FR-05** → Tracking workflow hardening (note/checksheet/attachment/override reason).
- **FR-06** → Dokumen operasional (waybill/resi/packing list).

## 4) Checklist Alignment sebelum Build

- [ ] Scope lock sudah ditulis di prompt.
- [ ] Slice hanya menyentuh modul terkait shipment sea.
- [ ] Tidak ada perubahan pada logic shipment land.
- [ ] Acceptance criteria diturunkan dari PRD.
- [ ] Test gate sudah ditentukan sebelum implementasi.

## 5) Checklist Alignment sebelum Merge

- [ ] Diff sesuai FR target (04/05/06).
- [ ] Role/scoping checks tetap aktif.
- [ ] Dokumen operasional hanya bisa diakses role yang berhak.
- [ ] Ringkasan perubahan konsisten dengan PRD.
