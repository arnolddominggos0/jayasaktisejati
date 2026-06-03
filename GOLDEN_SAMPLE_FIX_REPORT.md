# Dashboard TAM - Golden Sample Fix Report
## KM Tanto Cahaya V.384 (Shipment ID=3)

---

## 1. CUSTOMER AUDIT

### Finding
| Item | Value |
|------|-------|
| Toyota Astra Motor ID | **1** |
| Config customer_ids (before) | [31, 32] ❌ |
| Config customer_ids (after) | [1] ✅ |

### Root Cause
`config/jss_kpi.php` had hardcoded customer IDs [31, 32] that don't exist in the database. Toyota Astra Motor is actually customer_id = 1.

### Fix Applied
```php
// config/jss_kpi.php
'customer_ids' => [1],  // Changed from [31, 32]
```

---

## 2. TRACK AUDIT (Shipment ID=3)

### Current State
| Track ID | Status | tracked_at (current) | tracked_at (needed) | KPI Role |
|----------|--------|---------------------|---------------------|----------|
| 31 | pickup | 2026-05-18 11:49:10 | **2026-05-05 08:00:00** | Dwelling START |
| 36 | unit_loading | NULL | **2026-05-09 14:00:00** | Dwelling END + Sailing START |
| 39 | vessel_arrival | NULL | **2026-05-17 09:00:00** | Sailing END + Dooring START |
| 43 | delivered | NULL | **2026-05-19 10:00:00** | Dooring END |

### Issues Found
1. ✅ pickup has tracked_at but wrong date (2026-05-18 instead of 2026-05-05)
2. ❌ unit_loading tracked_at is NULL
3. ❌ vessel_arrival tracked_at is NULL
4. ❌ delivered tracked_at is NULL
5. ❌ Shipment status is 'draft' but has delivered_at (inconsistent)

---

## 3. SQL UPDATE STATEMENTS

### File: `fix_golden_sample.sql`

```sql
-- Fix shipment status
UPDATE shipments 
SET status = 'delivered'
WHERE id = 3;

-- Fix shipment delivered_at
UPDATE shipments 
SET delivered_at = '2026-05-19 10:00:00'
WHERE id = 3;

-- Update track timestamps
UPDATE shipment_tracks 
SET tracked_at = '2026-05-05 08:00:00'
WHERE id = 31 AND shipment_id = 3 AND status = 'pickup';

UPDATE shipment_tracks 
SET tracked_at = '2026-05-09 14:00:00'
WHERE id = 36 AND shipment_id = 3 AND status = 'unit_loading';

UPDATE shipment_tracks 
SET tracked_at = '2026-05-17 09:00:00'
WHERE id = 39 AND shipment_id = 3 AND status = 'vessel_arrival';

UPDATE shipment_tracks 
SET tracked_at = '2026-05-19 10:00:00'
WHERE id = 43 AND shipment_id = 3 AND status = 'delivered';
```

### Execution Command
```bash
# DO NOT execute yet - review first
# psql -U your_user -d your_database -f fix_golden_sample.sql
# OR
# php artisan tinker --execute="DB::unprepared(file_get_contents('fix_golden_sample.sql'));"
```

---

## 4. VERIFICATION QUERY

### File: `verify_kpi.sql`

```sql
WITH kpi_tracks AS (
    SELECT 
        st.shipment_id,
        st.status,
        st.tracked_at
    FROM shipment_tracks st
    WHERE st.shipment_id = 3
      AND st.status IN ('pickup', 'unit_loading', 'vessel_arrival', 'delivered')
      AND st.tracked_at IS NOT NULL
),
milestones AS (
    SELECT 
        shipment_id,
        MIN(CASE WHEN status = 'pickup' THEN tracked_at END) AS dwelling_start,
        MIN(CASE WHEN status = 'unit_loading' THEN tracked_at END) AS dwelling_end,
        MIN(CASE WHEN status = 'vessel_arrival' THEN tracked_at END) AS sailing_end,
        MAX(CASE WHEN status = 'delivered' THEN tracked_at END) AS dooring_end
    FROM kpi_tracks
    GROUP BY shipment_id
)
SELECT 
    s.id,
    s.code,
    s.vessel_name,
    s.voyage,
    c.name AS customer_name,
    DATEDIFF(m.dwelling_end, m.dwelling_start) AS dwelling_days,
    DATEDIFF(m.sailing_end, m.dwelling_end) AS sailing_days,
    DATEDIFF(m.dooring_end, m.sailing_end) AS dooring_days,
    DATEDIFF(m.dooring_end, m.dwelling_start) AS total_days
FROM shipments s
JOIN customers c ON s.customer_id = c.id
JOIN milestones m ON s.id = m.shipment_id
WHERE s.id = 3;
```

### Expected Output
```
id: 3
code: SHP-VOY151TSLMNDJSS-1
vessel_name: KM Tanto Cahaya V.384
voyage: VOY151TSLMNDJSS
customer_name: Toyota Astra Motor
dwelling_days: 4  (threshold: 6)  → OK
sailing_days: 8   (threshold: 10) → OK
dooring_days: 2   (threshold: 3)  → OK
total_days: 14    (threshold: 19) → On Time
```

---

## 5. DASHBOARD PERIOD FILTER

### Current Date Context
- Today: 2026-06-03
- Golden Sample delivered_at: **2026-05-19**

### Required Filter Selection
| Filter Option | Value | Will Show Shipment? |
|---------------|-------|---------------------|
| Bulan ini (default) | this_month (June 2026) | ❌ NO |
| **Tahun ini** | this_year (2026) | ✅ **YES** |
| **Per bulan → Mei 2026** | by_month (2026-05) | ✅ **YES** |

### Recommendation
User must change period filter from "Bulan ini" to **"Tahun ini"** or **"Per bulan → Mei 2026"** to see the golden sample.

---

## 6. EXPECTED DASHBOARD OUTPUT

### After SQL Execution + Correct Period Filter

#### KPI Cards
| Metric | Value | Target | Status |
|--------|-------|--------|--------|
| **Dwelling** | 4.0 hari | 6 hari | ✅ OK |
| **Sailing** | 8.0 hari | 10 hari | ✅ OK |
| **Dooring** | 2.0 hari | 3 hari | ✅ OK |

#### Lead Time Chart (Monthly Breakdown)
- May 2026: Dwelling=4.0, Sailing=8.0, Dooring=2.0
- Other months: — (no data)

#### KPI On Time Chart
- On Time: 1 (100%)
- Late: 0 (0%)

#### Pencapaian Lead Time
| Metric | OK % | NG % |
|--------|------|------|
| Dwelling | 100% | 0% |
| Sailing | 100% | 0% |
| Dooring | 100% | 0% |
| Total | 100% | 0% |

#### Ongoing Metrics
| Metric | Value |
|--------|-------|
| Unit di Port | 0 |
| Rata-rata Port (Hari) | 0.0 |
| Total Delivered | 1 |
| On Time (%) | 100.0% |
| Late | 0 |
| Over 3 Hari di Port | 0 |

---

## 7. FILES MODIFIED

| File | Change |
|------|--------|
| `config/jss_kpi.php` | customer_ids: [31, 32] → [1] |

## 8. FILES GENERATED (Not Executed)

| File | Purpose |
|------|---------|
| `fix_golden_sample.sql` | SQL updates for shipment + tracks |
| `verify_kpi.sql` | Verification query for KPI calculation |

---

## 9. NEXT STEPS

1. **Review SQL files** - Ensure updates are correct
2. **Execute SQL** - Run `fix_golden_sample.sql`
3. **Clear cache** - `php artisan cache:clear` (KPI uses 30s cache)
4. **Open Dashboard** - Navigate to `/admin`
5. **Change period filter** - Select "Tahun ini" or "Per bulan → Mei 2026"
6. **Verify KPI** - Check cards show 4.0, 8.0, 2.0

---

## 10. ROLLBACK INSTRUCTIONS

If needed, revert config change:
```php
// config/jss_kpi.php
'customer_ids' => [31, 32],  // Revert from [1]
```

And revert SQL:
```sql
UPDATE shipments SET status = 'draft', delivered_at = '2026-05-18 11:49:10' WHERE id = 3;
UPDATE shipment_tracks SET tracked_at = '2026-05-18 11:49:10' WHERE id = 31;
UPDATE shipment_tracks SET tracked_at = NULL WHERE id IN (36, 39, 43);
```
