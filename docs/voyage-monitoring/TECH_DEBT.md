# Technical Debt

## ShippingSchedule indirection

Current:
VesselCheck → ShippingSchedule

Concern:
adds unnecessary complexity

Future consideration:
direct voyage relation

Status:
Migration voyage_id sudah ditambahkan ke vessel_checks (2026_05_16_100000).

## ShippingSchedule Dependency

Saat ini ShippingSchedule masih digunakan oleh:
- VesselCheck
- TAM submission flow
- beberapa export process

Namun Voyage telah menjadi source of truth operasional.

Strategi:
- kurangi dependency baru ke ShippingSchedule
- gunakan Voyage sebagai operational relation utama
- pertahankan ShippingSchedule sebagai snapshot/integration layer sementara

## Period-Centric Migration

Refactor UX dari voyage-centric ke period-centric selesai.

Changes:
- Matrix menjadi satu-satunya tampilan monitoring
- Priority View dan Dashboard dihapus
- VoyageResource diposisikan sebagai detail record / audit only
- Monitoring Vessel menjadi single operational workspace
- Summary strip compact horizontal
- Warna hanya untuk delayed, overdue, risk, NG
- Normal state = plain text
- Actions fixed visible (not hover-only)

## VesselCheckCase Model Bugs

1. `vesselCheckCase()` self-referencing — perlu dihapus
2. `vesselChecks()` HasMany tanpa FK — perlu ditambahkan FK atau dihapus
3. Missing models: VesselCheckEtdLog, VesselCheckEvaluation — perlu dibuat
