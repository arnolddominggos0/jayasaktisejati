# Gap Analysis

## G1 - vessel_checks.voyage_id missing

Severity: Medium

Problem:
Relasi voyage() ada tetapi kolom tidak tersedia.

Recommendation:
Tambah migration voyage_id.

Status: Migration sudah ada (2026_05_16_100000), kolom sudah tersedia.

## G2 - VesselCheckCase tidak terhubung ke Monitoring Vessel

Severity: High

Problem:
VesselCheckCase (Tindak Lanjut) workflow terpisah dari Monitoring Vessel. Operator harus buka halaman terpisah untuk follow-up.

Recommendation:
Integrasikan case status ke matrix di Monitoring Vessel.

## G3 - Monitoring UX masih voyage-centric

Severity: High

Problem:
UX monitoring masih berfokus pada individual voyage (card per voyage), bukan periode operasional.

Recommendation:
Refactor ke period-centric monitoring. Matrix sebagai satu-satunya tampilan. Hapus Priority View, Dashboard, dan view switching.

Status: Selesai diimplementasikan.

## G4 - Data Voyage diposisikan sebagai monitoring workspace

Severity: Medium

Problem:
VoyageResource table dan ViewVoyage bisa disalahgunakan sebagai operational workspace oleh operator.

Recommendation:
Jadikan Data Voyage sebagai data management / audit only. Tambah link "Kembali ke Monitoring" di ViewVoyage.

Status: Selesai diimplementasikan.
