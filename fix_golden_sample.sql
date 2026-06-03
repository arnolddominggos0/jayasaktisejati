-- ============================================================================
-- GOLDEN SAMPLE FIX: KM Tanto Cahaya V.384 (Shipment ID=3)
-- ============================================================================
-- Purpose: Update shipment tracks to enable KPI calculation
-- Expected KPI: Dwelling=4, Sailing=8, Dooring=2, Total=14
-- ============================================================================

-- ============================================================================
-- STEP 1: Fix shipment status (currently 'draft' but has delivered_at)
-- ============================================================================
UPDATE shipments 
SET status = 'delivered'
WHERE id = 3;

-- ============================================================================
-- STEP 2: Update shipment delivered_at to match golden sample timeline
-- ============================================================================
UPDATE shipments 
SET delivered_at = '2026-05-19 10:00:00'
WHERE id = 3;

-- ============================================================================
-- STEP 3: Update shipment_tracks with golden sample timeline
-- ============================================================================

-- 3a. Pickup (Dwelling START) - SPPB date
UPDATE shipment_tracks 
SET tracked_at = '2026-05-05 08:00:00'
WHERE id = 31 AND shipment_id = 3 AND status = 'pickup';

-- 3b. Unit Loading (Dwelling END + Sailing START) - Sailing date
UPDATE shipment_tracks 
SET tracked_at = '2026-05-09 14:00:00'
WHERE id = 36 AND shipment_id = 3 AND status = 'unit_loading';

-- 3c. Vessel Arrival (Sailing END + Dooring START) - Arrival date
UPDATE shipment_tracks 
SET tracked_at = '2026-05-17 09:00:00'
WHERE id = 39 AND shipment_id = 3 AND status = 'vessel_arrival';

-- 3d. Delivered (Dooring END) - Dooring completion
UPDATE shipment_tracks 
SET tracked_at = '2026-05-19 10:00:00'
WHERE id = 43 AND shipment_id = 3 AND status = 'delivered';

-- ============================================================================
-- VERIFICATION: Check updated records
-- ============================================================================

-- Verify shipment
SELECT id, code, customer_id, vessel_name, voyage, status, delivered_at
FROM shipments 
WHERE id = 3;

-- Verify tracks
SELECT id, shipment_id, status, tracked_at, status_normalized
FROM shipment_tracks 
WHERE shipment_id = 3 
  AND status IN ('pickup', 'unit_loading', 'vessel_arrival', 'delivered')
ORDER BY status_normalized;
