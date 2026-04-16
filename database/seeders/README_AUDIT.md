# Data Audit April 2026 Seeder

Seeder ini menyediakan data audit operasional yang realistis untuk periode 1-16 April 2026.

## Manpower Aktif (8 Orang)

| No | Nama           |
|----|----------------|
| 1  | Tri Mulya      |
| 2  | Suryadi        |
| 3  | Odih           |
| 4  | Rustam         |
| 5  | Markus         |
| 6  | Soleh Wahidin  |
| 7  | Habi           |
| 8  | Cemen          |

**PIC:** Bpk. Tri

## Dashboard Summary

| Indikator             | Nilai        |
|----------------------|--------------|
| Briefing Sessions    | 12 sesi      |
| Kehadiran MP         | 7.4/8        |
| Total MP Aktif       | 8 orang      |
| APD Layak Pakai      | 94/96        |
| Rata-rata Suhu       | 36.6°C       |
| Rata-rata TD         | 120/80       |
| Loading Selesai      | 14           |
| Total Loading        | 17           |
| Persentase Selesai   | 82%          |

## Cara Penggunaan

### 1. Jalankan Migration

```bash
php artisan migrate
```

### 2. Jalankan Seeder

```bash
php artisan db:seed --class=AuditApril2026Seeder
```

Atau jalankan semua seeder:

```bash
php artisan db:seed
```

### 3. Hanya Jalankan Seeder Ini

```bash
php artisan migrate:fresh --seed
```

## Tabel yang Dibuat

1. **briefings** - Data briefing harian
2. **kehadirans** - Data kehadiran manpower
3. **kesehatans** - Data pemeriksaan kesehatan (suhu & tekanan darah)
4. **loadings** - Data aktivitas loading
5. **apds** - Data pemeriksaan APD

## Data File

Data juga tersedia dalam format JSON di: `database/seeders/data/audit_april_2026.json`

## Periode

- **Tanggal:** 1 - 16 April 2026
- **Hari Kerja:** 12 hari (tidak termasuk weekend)

## Konsistensi Data

- Tidak ada nilai 0
- Semua angka saling konsisten
- Data terlihat seperti operasional nyata
- Kehadiran: 7-8 orang per hari
- Briefing: hampir setiap hari kerja
- APD: 7-8 orang layak pakai
- Loading: 70-90% selesai
- Suhu: 36.4-36.9°C
- TD: 115/75 - 125/85
