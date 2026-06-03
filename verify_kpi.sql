-- ============================================================================
-- VERIFICATION QUERY: KPI Calculation for Shipment ID=3
-- ============================================================================
-- This query demonstrates the expected KPI after SQL updates are applied
-- Expected: Dwelling=4, Sailing=8, Dooring=2, Total=14
-- ============================================================================

WITH kpi_tracks AS (
    SELECT 
        st.shipment_id,
        st.status,
        st.tracked_at,
        st.status_normalized
    FROM shipment_tracks st
    WHERE st.shipment_id = 3
      AND st.status IN ('pickup', 'unit_loading', 'vessel_arrival', 'delivered')
      AND st.tracked_at IS NOT NULL
),
milestones AS (
    SELECT 
        shipment_id,
        -- Dwelling START: pickup
        MIN(CASE WHEN status = 'pickup' THEN tracked_at END) AS dwelling_start,
        -- Dwelling END / Sailing START: unit_loading
        MIN(CASE WHEN status = 'unit_loading' THEN tracked_at END) AS dwelling_end,
        -- Sailing END / Dooring START: vessel_arrival
        MIN(CASE WHEN status = 'vessel_arrival' THEN tracked_at END) AS sailing_end,
        -- Dooring END: delivered
        MAX(CASE WHEN status = 'delivered' THEN tracked_at END) AS dooring_end
    FROM kpi_tracks
    GROUP BY shipment_id
)
SELECT 
    s.id AS shipment_id,
    s.code,
    s.vessel_name,
    s.voyage,
    s.customer_id,
    c.name AS customer_name,
    
    -- Milestone dates
    m.dwelling_start,
    m.dwelling_end,
    m.sailing_end,
    m.dooring_end,
    
    -- KPI Calculations (days)
    DATEDIFF(m.dwelling_end, m.dwelling_start) AS dwelling_days,
    DATEDIFF(m.sailing_end, m.dwelling_end) AS sailing_days,
    DATEDIFF(m.dooring_end, m.sailing_end) AS dooring_days,
    DATEDIFF(m.dooring_end, m.dwelling_start) AS total_days,
    
    -- Thresholds from config
    6 AS dwelling_threshold,
    10 AS sailing_threshold,
    3 AS dooring_threshold,
    19 AS total_threshold,
    
    -- Status
    CASE WHEN DATEDIFF(m.dwelling_end, m.dwelling_start) <= 6 THEN 'OK' ELSE 'LATE' END AS dwelling_status,
    CASE WHEN DATEDIFF(m.sailing_end, m.dwelling_end) <= 10 THEN 'OK' ELSE 'LATE' END AS sailing_status,
    CASE WHEN DATEDIFF(m.dooring_end, m.sailing_end) <= 3 THEN 'OK' ELSE 'LATE' END AS dooring_status,
    CASE WHEN DATEDIFF(m.dooring_end, m.dwelling_start) <= 19 THEN 'On Time' ELSE 'Late' END AS overall_badge
    
FROM shipments s
JOIN customers c ON s.customer_id = c.id
JOIN milestones m ON s.id = m.shipment_id
WHERE s.id = 3;

-- ============================================================================
-- EXPECTED OUTPUT (after SQL updates):
-- ============================================================================
-- shipment_id: 3
-- code: SHP-VOY151TSLMNDJSS-1
-- vessel_name: KM Tanto Cahaya V.384
-- voyage: VOY151TSLMNDJSS
-- customer_id: 1
-- customer_name: Toyota Astra Motor
-- 
-- dwelling_start: 2026-05-05 08:00:00
-- dwelling_end: 2026-05-09 14:00:00
-- sailing_end: 2026-05-17 09:00:00
-- dooring_end: 2026-05-19 10:00:00
-- 
-- dwelling_days: 4 (threshold: 6) -> OK
-- sailing_days: 8 (threshold: 10) -> OK
-- dooring_days: 2 (threshold: 3) -> OK
-- total_days: 14 (threshold: 19) -> On Time
-- ============================================================================
