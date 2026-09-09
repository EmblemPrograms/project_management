-- ============================================================================
-- Department level migration
-- Run once, on the live database, before deploying the updated PHP files.
--
-- Departments were level-agnostic, so the registration form offered every
-- department on both the HND and the ND tab. Computer Science runs ND only and
-- the other two run HND only, which meant a student could register into a
-- department that does not admit their level at all.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. The column.
--    ENUM rather than a lookup table: there are exactly two levels and the
--    rest of the schema already spells them out this way (students.level).
--    If a department ever needs to admit both, widen it with
--        ALTER TABLE departments MODIFY level ENUM('ND','HND','BOTH') NOT NULL;
--    and the PHP filter picks it up without further change.
-- ---------------------------------------------------------------------------
ALTER TABLE departments
    ADD COLUMN level ENUM('ND','HND') NOT NULL DEFAULT 'HND' AFTER code;

-- ---------------------------------------------------------------------------
-- 2. Backfill the three existing departments.
--    Matched on code, which is stable, rather than on the display name.
-- ---------------------------------------------------------------------------
UPDATE departments SET level = 'ND'  WHERE code = 'CS';   -- Computer Science
UPDATE departments SET level = 'HND' WHERE code IN ('SW', 'NT');

-- The default only existed to let the ALTER run against populated rows.
ALTER TABLE departments ALTER COLUMN level DROP DEFAULT;

-- ---------------------------------------------------------------------------
-- 3. Check for students already sitting in a department that does not match
--    their level. These are pre-existing registrations, not something this
--    migration creates — but they are worth knowing about, because the
--    registration form will no longer allow the combination.
--
--    Run it; an empty result means nothing to clean up.
-- ---------------------------------------------------------------------------
SELECT s.id, s.matric_no, s.name, s.level AS student_level,
       d.name AS department, d.level AS department_level
FROM students s
JOIN departments d ON d.id = s.department_id
WHERE s.level <> d.level
ORDER BY s.level, s.matric_no;

-- ---------------------------------------------------------------------------
-- 4. Confirm the result.
-- ---------------------------------------------------------------------------
-- SELECT id, name, code, level FROM departments ORDER BY level, name;
