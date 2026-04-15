# AppSheet Google Sheets Import Guide

## Cara Import ke Google Sheets

### Langkah 1: Buat Google Sheets Baru
1. Buka Google Drive
2. Klik New > Google Sheets
3. Beri nama: "JSS Loading System"

### Langkah 2: Import Setiap CSV File
Untuk setiap file CSV di folder ini:

1. Buka sheet baru (klik + di bawah)
2. Klik File > Import
3. Pilih Upload
4. Pilih file CSV dari folder ini
5. Pilih Replace current sheet
6. Klik Import data

### Langkah 3: Urutan Import
Import dalam urutan ini:

1. **10_Cities.csv** - Data kota
2. **08_Ports.csv** - Data pelabuhan
3. **07_Customers.csv** - Data customer
4. **09_Users.csv** - Data user/FC
5. **06_Depots.csv** - Data depo
6. **05_Shipments.csv** - Data shipment
7. **01_Loading_Sessions.csv** - Data loading session
8. **02_Rack_Container_Checks.csv** - Data rack container
9. **03_Equipment_Checks.csv** - Data equipment
10. **04_Unit_Checks.csv** - Data unit
11. **11_Loading_Findings.csv** - Data findings

### Langkah 4: Setup AppSheet
1. Buka appsheet.com
2. Klik Create > Google Sheets
3. Pilih spreadsheet "JSS Loading System"
4. AppSheet akan otomatis mendeteksi tabel

### Langkah 5: Konfigurasi Kolom di AppSheet

#### Loading_Sessions
- ID: Key (Auto-generated)
- Code: Text (Auto-generated)
- Shipment_ID: Ref (ke Shipments)
- Depot_ID: Ref (ke Depots)
- Coordinator_User_ID: Ref (ke Users)
- Status: Enum (draft,in_progress,completed,stopped)
- Operation_Type: Enum (loading,unloading,rack_handling)
- Current_Step: Enum (mp_attendance_check,health_check,apd_check,equipment_check,rack_container_check,unit_check,final_decision)
- GPS_Latitude: Decimal
- GPS_Longitude: Decimal
- MP_Required, MP_Present, MP_Fit_Count, MP_Unfit_Count: Number
- All checkbox fields: Yes/No
- Timestamps: DateTime

#### Rack_Container_Checks
- Semua pillar_condition: Enum (strong_and_straight,not_straight,damaged)
- Semua pulley_hook: Enum (present_and_strong,not_present,loose,damaged)
- Semua tie_status: Enum (tied_strong,not_tied,loose)
- Drop_floor_condition: Enum (straight,bent)
- Drop_floor_strength: Enum (strong,weak)
- Iron_hook: Enum (present,not_present,damaged)
- Container_status: Enum (good,damaged,leaking)
- All _safe fields: Yes/No

### Langkah 6: Setup Webhook
1. Di AppSheet, buka Automation > Bots
2. Buat Bot baru: "Sync to Laravel"
3. Event: When data changes (ADD, UPDATE, DELETE)
4. Process: Call webhook
5. URL: https://your-domain.com/api/appsheet/webhook
6. Method: POST
7. Body: JSON sesuai dokumentasi

### Kontak Support
Jika ada masalah, cek log di Laravel: storage/logs/appsheet.log
