# AppSheet Integration Guide

## Overview
Integrasi AppSheet dengan Laravel menggunakan pendekatan **Webhook API**.

```
AppSheet (Mobile Form)
    ↓ Webhook POST
Laravel API (/api/appsheet/webhook)
    ↓ Process
Database (MySQL/PostgreSQL)
    ↓ Display
Filament Dashboard
```

---

## 1. Konfigurasi Environment

Tambahkan ke file `.env`:

```env
# AppSheet Integration
APPSHEET_API_KEY=your_appsheet_api_key_here
APPSHEET_APP_ACCESS_KEY=your_app_access_key_here
APPSHEET_APPLICATION_ID=your_application_id_here
APPSHEET_WEBHOOK_SECRET=your_webhook_secret_here
APPSHEET_SYNC_MODE=webhook
APPSHEET_POLLING_INTERVAL=5
APPSHEET_LOGGING_ENABLED=true
APPSHEET_LOG_CHANNEL=appsheet
```

---

## 2. Setup Webhook di AppSheet

### Step 1: Buka AppSheet Editor
1. Login ke [AppSheet](https://www.appsheet.com/)
2. Buka aplikasi yang akan diintegrasikan
3. Klik menu **Automation** → **Bots**

### Step 2: Create New Bot
1. Klik **+ New Bot**
2. Beri nama: `Sync to Laravel`
3. Pilih trigger: **When this happens** → "Data Change"
4. Pilih events: **ADD**, **UPDATE**, **DELETE**

### Step 3: Configure Process
1. Pilih **Do this** → "Call a webhook"
2. **Webhook URL**: 
   ```
   https://your-domain.com/api/appsheet/webhook
   ```
   Untuk local development (gunakan ngrok):
   ```
   https://abc123.ngrok.io/api/appsheet/webhook
   ```

3. **HTTP Method**: POST

4. **HTTP Body** (JSON):
   ```json
   {
     "table": "loading_sessions",
     "operation": "<<[Action]>>",
     "data": {
       "Code": "<<[Code]>>",
       "Jenis Operasi": "<<[Jenis Operasi]>>",
       "Status": "<<[Status]>>",
       "Depot ID": "<<[Depot ID]>>",
       "Koordinator ID": "<<[Koordinator ID]>>",
       "Branch ID": "<<[Branch ID]>>",
       "MP Dibutuhkan": "<<[MP Dibutuhkan]>>",
       "MP Hadir": "<<[MP Hadir]>>",
       "Latitude": "<<[Latitude]>>",
       "Longitude": "<<[Longitude]>>",
       "Catatan": "<<[Catatan]>>"
     }
   }
   ```

5. **HTTP Headers** (opsional untuk security):
   ```
   X-AppSheet-Signature: your_webhook_secret
   Content-Type: application/json
   ```

### Step 4: Test Webhook
1. Klik **Test** di AppSheet
2. Cek log di Laravel: `storage/logs/appsheet.log`
3. Verifikasi data masuk ke database

---

## 3. Struktur Data AppSheet

### Table: Loading Sessions

| AppSheet Field | Laravel Field | Type | Required |
|---------------|---------------|------|----------|
| Code | code | string | Yes |
| Jenis Operasi | operation_type | enum | Yes |
| Status | status | enum | Yes |
| Depot ID | depot_id | integer | Yes |
| Koordinator ID | coordinator_user_id | integer | Yes |
| Branch ID | branch_id | integer | Yes |
| MP Dibutuhkan | mp_required | integer | No |
| MP Hadir | mp_present | integer | No |
| Latitude | gps_latitude | decimal | No |
| Longitude | gps_longitude | decimal | No |
| Catatan | general_notes | text | No |

### Table: Rack Container Checks

| AppSheet Field | Laravel Field | Type | Required |
|---------------|---------------|------|----------|
| Loading Session ID | loading_session_id | integer | Yes |
| Pilar A Kondisi | pillar_a_condition | enum | Yes |
| Pilar A Pengait | pillar_a_pulley_hook | enum | Yes |
| Pilar A Ikatan | pillar_a_tie_status | enum | Yes |
| ... | ... | ... | ... |

---

## 4. Testing Integration

### Test Endpoint
```bash
curl -X GET https://your-domain.com/api/appsheet/test
```

### Test Webhook Manual
```bash
curl -X POST https://your-domain.com/api/appsheet/webhook \
  -H "Content-Type: application/json" \
  -H "X-AppSheet-Signature: your_secret" \
  -d '{
    "table": "loading_sessions",
    "operation": "create",
    "data": {
      "Code": "LD-TEST-001",
      "Jenis Operasi": "loading",
      "Status": "draft",
      "Depot ID": 1,
      "Koordinator ID": 1,
      "Branch ID": 1
    }
  }'
```

---

## 5. Monitoring & Logging

### Check Logs
```bash
# View AppSheet logs
tail -f storage/logs/appsheet.log

# View sync logs
docker-compose exec db psql -U jss_user -d jss_db -c "SELECT * FROM appsheet_sync_logs ORDER BY created_at DESC LIMIT 10;"
```

### Check Sync Status
```bash
php artisan tinker
>>> AppSheetSyncLog::recent()->get();
```

---

## 6. Troubleshooting

### Issue: 404 Not Found
**Solution**: 
- Pastikan route terdaftar: `php artisan route:list | grep appsheet`
- Clear route cache: `php artisan route:clear`

### Issue: 401 Unauthorized
**Solution**:
- Cek webhook secret di .env dan AppSheet
- Pastikan header `X-AppSheet-Signature` sesuai

### Issue: Data tidak masuk
**Solution**:
- Cek log: `storage/logs/laravel.log`
- Pastikan field mapping benar di config/appsheet.php
- Verifikasi tabel dan model ada

### Issue: Validation Error
**Solution**:
- Cek required fields di AppSheet webhook body
- Pastikan data type sesuai (string, integer, enum)

---

## 7. Security Best Practices

1. **Always use HTTPS** di production
2. **Set webhook secret** untuk validasi request
3. **IP Whitelist** jika AppSheet menyediakan IP range
4. **Rate Limiting**: Implementasi throttling di Laravel
5. **Audit Log**: Semua sync tercatat di appsheet_sync_logs

---

## 8. Field Coordinator Mobile Workflow

```
1. FC login ke AppSheet mobile app
2. Pilih Shipment yang akan di-check
3. Isi form Loading Session
4. Upload foto dokumentasi
5. Submit → Data kirim ke Laravel via webhook
6. FC bisa cek status di Filament dashboard
```

---

## Support

Jika ada issue, cek:
1. Log file: `storage/logs/appsheet.log`
2. Sync logs table: `appsheet_sync_logs`
3. Test endpoint: `/api/appsheet/test`
