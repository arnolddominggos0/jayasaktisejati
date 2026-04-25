-- Slice 1 Backfill & Validation Notes
-- ====================================
-- Run these AFTER `php artisan migrate` succeeds.
-- All statements are safe (UPDATEs are scoped; no destructive ops).

-- 1) Backfill users.scope_branch_id from existing branch_id.
UPDATE users SET scope_branch_id = branch_id WHERE scope_branch_id IS NULL AND branch_id IS NOT NULL;

-- 2) Backfill FC canonical scope from depots.
UPDATE users
SET
    scope_unit_id = depots.id,
    scope_unit_type = 'depot'
FROM depots
WHERE users.id = depots.coordinator_user_id
  AND users.scope_unit_id IS NULL;

-- 3) Backfill FC canonical scope from pools (only if not already filled).
UPDATE users
SET
    scope_unit_id = pools.id,
    scope_unit_type = 'pool'
FROM pools
WHERE users.id = pools.coordinator_user_id
  AND users.scope_unit_id IS NULL;

-- 4) Validation queries (must return 0 rows before proceeding to Slice 2):

-- a) Users without scope_branch_id (should be 0 after backfill).
SELECT COUNT(*) AS missing_scope_branch FROM users WHERE scope_branch_id IS NULL;

-- b) FCs mapped to more than one unit (should be 0 because guards now prevent it).
SELECT coordinator_user_id, COUNT(*)
FROM (
    SELECT coordinator_user_id FROM depots WHERE coordinator_user_id IS NOT NULL
    UNION ALL
    SELECT coordinator_user_id FROM pools WHERE coordinator_user_id IS NOT NULL
) u
GROUP BY coordinator_user_id
HAVING COUNT(*) > 1;

-- c) Canonical scope mismatch against live assignments (should be 0).
SELECT u.id
FROM users u
LEFT JOIN depots d ON d.coordinator_user_id = u.id
LEFT JOIN pools p ON p.coordinator_user_id = u.id
WHERE u.scope_unit_type = 'depot' AND u.scope_unit_id IS NOT NULL AND u.scope_unit_id <> d.id
   OR u.scope_unit_type = 'pool'  AND u.scope_unit_id IS NOT NULL AND u.scope_unit_id <> p.id;

-- d) Orphaned scope_unit_id where unit no longer exists.
SELECT u.id FROM users u
LEFT JOIN depots d ON u.scope_unit_type = 'depot' AND u.scope_unit_id = d.id
LEFT JOIN pools p ON u.scope_unit_type = 'pool'  AND u.scope_unit_id = p.id
WHERE u.scope_unit_id IS NOT NULL AND d.id IS NULL AND p.id IS NULL;

-- Rollback command (reversible):
-- php artisan migrate:rollback --step=1
-- This removes scope_* columns and the pools unique constraint, restoring prior state.
