-- ============================================================================
-- Which migrations has this database had?
--
-- Read-only. Run it first, on the LIVE database, to see what is still
-- outstanding before running anything else.
--
-- 1 = already applied, do NOT run it again
-- 0 = missing, run the matching file
-- ============================================================================

SELECT
    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME   = 'students'
        AND COLUMN_NAME  = 'payment_reference')      AS add_payment_tracking,

    (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME   = 'departments'
        AND COLUMN_NAME  = 'level')                  AS add_department_level,

    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME   = 'password_resets')        AS add_password_resets,

    (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME   = 'payment_settings')       AS add_payment_settings;


-- ---------------------------------------------------------------------------
-- If add_payment_settings came back 1, check the fees row actually exists.
-- The table can be there with no row in it, in which case registration quietly
-- falls back to the old hardcoded prices and logs that the migration is
-- incomplete. Expect exactly one row, id = 1.
-- ---------------------------------------------------------------------------
SELECT id, fee_nd, fee_hnd, updated_at FROM payment_settings;


-- ---------------------------------------------------------------------------
-- If add_department_level came back 1, confirm the levels are right.
-- Computer Science should be ND; the others HND.
-- ---------------------------------------------------------------------------
SELECT id, name, code, level FROM departments ORDER BY level, name;
