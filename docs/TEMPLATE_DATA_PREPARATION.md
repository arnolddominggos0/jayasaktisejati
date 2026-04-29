# Template Data Preparation - 1 Bulan Test
## PT Jaya Sakti Sejati - Field Coordinator Module

**Periode:** ____________
**Cabang:** ____________
**Disiapkan oleh:** ____________

---

## PETUNJUK PENGISIAN

1. Buat salinan file ini untuk setiap bulan testing
2. Isi data sesuai kolom yang tersedia
3.忌忌忌 Hapus baris contoh sebelum import
4. Pastikan format tanggal: YYYY-MM-DD
5. Semua field bertanda * wajib diisi

---

## 1. DATA CUSTOMER

| code* | name* | type* | email | phone | pic_name | pic_phone | city | address |
|-------|-------|-------|-------|-------|----------|-----------|------|---------|
| CUST-001 | PT Maju Jaya | company | logistik@majukaya.co.id | 021-1234567 | Budi Santoso | 081234567890 | Jakarta | Jl. Sudirman No. 123 |
| CUST-002 | PT Steel Indo | company | ops@steelindo.co.id | 021-9876543 | Ahmad Rizki | 081298765432 | Surabaya | Jl. Pahlawan No. 45 |
| CUST-003 | CV Karyalog | individual | karya.log@gmail.com | 031-5551234 | Sri Wahyuni | 081567890123 | Makassar | Jl. Pettarani No. 78 |

*Keterangan:*
- `type`: company / individual
- `city`: nama kota sesuai master data

---

## 2. DATA SHIPMENT

### 2.1 Header Shipment

| code* | customer_code* | origin_city* | dest_city* | mode* | service_type | container_size | container_qty | container_no | seal_no | packages | cbm | weight | pol* | pod* | vessel_name | voyage_no | etd | eta | priority | notes |
|-------|----------------|-------------|------------|-------|--------------|---------------|--------------|-------------|---------|----------|-----|--------|------|-----|-------------|-----------|-----|-----|----------|-------|

### 2.2 Detail per Status

**Untuk setiap shipment, isi tracking timeline:**

| shipment_code* | status* | tracked_date* | tracked_time | location* | note |
|----------------|---------|---------------|--------------|-----------|------|
| JSS0426SH0001 | pickup | 2026-03-01 | 08:30 | Depo Tanjung Priok | Penjemputan dimulai |
| JSS0426SH0001 | handover | 2026-03-01 | 14:00 | Depo Tanjung Priok | handover_to_depo |
| JSS0426SH0001 | stuffing | 2026-03-02 | 09:00 | Depo Tanjung Priok | stuffing_segel |
| JSS0426SH0001 | delivery_to_port | 2026-03-02 | 16:00 | Tanjung Priok Port | arrive_port |
| JSS0426SH0001 | stacking | 2026-03-03 | 07:00 | Tanjung Priok Port | stacking_yard |
| JSS0426SH0001 | unit_loading | 2026-03-03 | 10:00 | Tanjung Priok Port | loaded_vessel |
| JSS0426SH0001 | onship | 2026-03-03 | 12:00 | KM Dharmawan | on_board |
| ... | ... | ... | ... | ... | ... |

*Track Status Options:*
- pickup
- handover
- stuffing
- delivery_to_port
- stacking
- unit_loading
- onship
- vessel_depart
- vessel_arrival
- unloading
- delivery_to_customer
- delivered
- hold
- cancelled

---

## 3. DATA LOADING SESSION

* Untuk setiap shipment yang melewati proses loading (stuffing/unit_loading):*

| code* | shipment_code* | operation_type* | depot_code* | status* | mp_required | mp_present | mp_sick | mp_absent | mp_fit | apd_complete | equipment_safe | rack_safe | unit_ok | final_decision | started_at | completed_at |
|-------|----------------|----------------|-------------|---------|-------------|------------|---------|-----------|--------|---------------|----------------|-----------|---------|----------------|------------|--------------|

*Loading Status Options:*
- draft
- in_progress
- mp_attendance_check
- health_check
- apd_check
- equipment_check
- rack_container_check
- unit_check
- final_decision
- completed
- stopped

*Operation Type Options:*
- loading
- unloading
- rack_handling

*Final Decision Options:*
- go
- warning
- stop

### 3.1 Detail Pemeriksaan Rack & Container

*Isi jika loading session melewati rack_container_check:*

| loading_session_code* | pillar_a_cond | pillar_a_pulley | pillar_a_tie | pillar_b_cond | pillar_b_pulley | pillar_b_tie | pillar_c_cond | pillar_c_pulley | pillar_c_tie | pillar_d_cond | pillar_d_pulley | pillar_d_tie | drop_floor_front_cond | drop_floor_front_strength | drop_floor_rear_cond | drop_floor_rear_strength | container_wall | container_floor | container_roof |
|-----------------------|---------------|-----------------|-------------|---------------|-----------------|--------------|---------------|-----------------|--------------|---------------|-----------------|-------------|----------------------|--------------------------|---------------------|-------------------------|----------------|-----------------|----------------|

*Pillar Condition Options:* strong_and_straight / not_straight / damaged
*Pulley/Hook Options:* present_and_strong / not_present / loose / damaged
*Tie Status Options:* tied_strong / not_tied / loose
*Drop Floor Condition:* straight / bent
*Drop Floor Strength:* strong / weak
*Container Structure:* good / damaged / leaking

### 3.2 Detail Pemeriksaan Equipment

*Isi jika loading session melewati equipment_check:*

| loading_session_code* | pulley_top | pulley_bottom | mono_rope | chain | bolt_nut | bamboo | ladder | sponds | overall_safe |
|-----------------------|------------|--------------|-----------|-------|----------|--------|--------|---------|--------------|

*Equipment Status Options:* ok / not_ok / new / worn / strong / loose / thick / cracked / stable / unstable / clean / dirty / present / not_present / tight

---

## 4. DATA BRIEFING SESSION

| date* | depot_code* | coordinator_email* | headcount | sufficient | notes | mp_check_status |
|--------|-------------|-------------------|-----------|------------|-------|----------------|

*MP Check Status:* draft / on_check / waiting_action / failed / cleared / approved

### 4.1 Briefing Attendance

| session_date* | depot_code* | manpower_name* | status* | temperature | bp_systolic | bp_diastolic | health_complaint |
|---------------|-------------|---------------|---------|------------|-------------|--------------|-----------------|

*Attendance Status:* present / absent / sick / leave

---

## 5. DATA VOYAGE (Untuk Shipment Sea)

| voyage_no* | vessel_name* | shipping_line* | pol* | pod* | etd* | eta* | cargo_plan |
|------------|-------------|---------------|------|------|------|------|------------|

---

## 6. CHECKLIST TEST

*Gunakan checklist ini untuk memverifikasi setiap shipment:*

### 6.1 Checklist Pembuatan Shipment
- [ ] Shipment dibuat di Admin panel
- [ ] Kode shipment tergenerate otomatis
- [ ] Customer ter-assign
- [ ] Route (origin-destination) benar
- [ ] Container info terisi (jika sea)
- [ ] Status awal: Draft

### 6.2 Checklist Kirim ke FC
- [ ] Klik "Kirim ke FC" / status berubah ke Pending
- [ ] Depot ter-assign otomatis
- [ ] Track skeleton terbuat (pickup track)
- [ ] Shipment muncul di FC panel

### 6.3 Checklist Tracking Update
- [ ] FC bisa update status satu per satu
- [ ] Timestamps tersimpan dengan benar
- [ ] Notes/location tersimpan
- [ ] Status shipment utama update otomatis
- [ ] History tracking lengkap terlihat

### 6.4 Checklist Loading Session
- [ ] FC bisa buat loading session dari shipment
- [ ] 9-step checklist bisa dilalui
- [ ] Final decision (GO/WARNING/STOP) berfungsi
- [ ] Critical issues ter-detect
- [ ] Data tersimpan dengan benar

### 6.5 Checklist Dashboard FC
- [ ] KPI stats menampilkan angka yang benar
- [ ] Chart tren berfungsi
- [ ] Recent activities update real-time
- [ ] Filter berfungsi

---

## 7. TEMPLATE IMPORT CSV

Jika menggunakan import CSV, gunakan struktur berikut:

### shipments.csv
```
code,customer_code,origin_city,dest_city,mode,service_type,container_size,container_qty,pol,pod,vessel_name,voyage,etd,eta,priority,notes
```

### shipment_tracks.csv
```
shipment_code,status,tracked_date,location,note
```

### loading_sessions.csv
```
code,shipment_code,operation_type,depot_code,status,mp_required,final_decision
```

---

## 8. CONTOH DATA 1 MINGGU

### Shipments (7 shipments untuk 1 minggu)

| code | customer | origin | dest | mode | pol | pod | status | priority |
|------|----------|--------|------|------|-----|-----|--------|----------|
| JSS0326SH0001 | CUST-001 | Jakarta | Makassar | sea | TPRI | MAK | delivered | normal |
| JSS0326SH0002 | CUST-002 | Jakarta | Bitung | sea | TPRI | BTG | transit | urgent |
| JSS0326SH0003 | CUST-001 | Surabaya | Jakarta | sea | TPK | TPRI | delivered | normal |
| JSS0326SH0004 | CUST-003 | Makassar | Surabaya | sea | MAK | TPK | pending | normal |
| JSS0326SH0005 | CUST-002 | Jakarta | Bitung | sea | TPRI | BTG | pickup | urgent |
| JSS0326SH0006 | CUST-001 | Jakarta | Manado | sea | TPRI | MDO | stuffing | normal |
| JSS0326SH0007 | CUST-003 | Surabaya | Jakarta | sea | TPK | TPRI | handover | normal |

### Loading Sessions

| code | shipment | status | mp_req | mp_present | final_decision |
|------|----------|--------|--------|------------|----------------|
| LD-0326-001 | JSS0326SH0001 | completed | 8 | 8 | go |
| LD-0326-002 | JSS0326SH0002 | completed | 10 | 9 | warning |
| LD-0326-003 | JSS0326SH0003 | completed | 6 | 6 | go |
| LD-0326-006 | JSS0326SH0006 | in_progress | 12 | 12 | pending |

---

*Dokumen ini dibuat untuk mendukung testing sistem FC module*
*PT Jaya Sakti Sejati - Sistem Manajemen Logistik*
