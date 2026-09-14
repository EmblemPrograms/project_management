-- ============================================================================
-- Find ND pairs that submitted more than one project
--
-- Read-only. Nothing here changes data.
--
-- Before the shared-profile change, each half of an ND pair had its own
-- dashboard and saw no sign of what the other had done, so both partners
-- sometimes uploaded the same project. Those duplicates are still in the
-- database, and now that a pair reads BOTH members' projects, they show up
-- twice on the shared dashboard and twice in the department's review queue.
--
-- Run section 1 first for the scale of it, then section 2 for the detail.
-- ============================================================================


-- ---------------------------------------------------------------------------
-- 1. How many pairs are affected?
--
--    A duplicate means more than one LIVE project — pending or approved.
--    Counting every row instead would wrongly flag a pair that was rejected
--    once and correctly resubmitted, which is two rows but only one live
--    submission and nothing to clean up.
-- ---------------------------------------------------------------------------
SELECT
    SUM(live_projects > 1)                          AS pairs_with_duplicates,
    SUM(CASE WHEN live_projects > 1 THEN live_projects ELSE 0 END)
                                                    AS live_projects_involved,
    SUM(CASE WHEN live_projects > 1 THEN live_projects - 1 ELSE 0 END)
                                                    AS projects_to_resolve,
    SUM(live_projects <= 1 AND total_projects > 1)  AS pairs_ok_resubmitted
FROM (
    SELECT
        s.pair_id,
        COUNT(*)                                    AS total_projects,
        SUM(p.status IN ('pending', 'approved'))    AS live_projects
    FROM students s
    JOIN projects p ON p.student_id = s.id
    WHERE s.pair_id IS NOT NULL
    GROUP BY s.pair_id
) AS per_pair;


-- ---------------------------------------------------------------------------
-- 2. The detail: every project belonging to a pair that has more than one.
--
--    Only pairs with more than one live project appear. Rows are grouped by
--    pair and ordered oldest first, so within each pair the first row is the
--    original submission and the rest are the repeats. Any rejected project
--    the pair also has is listed for context and marked to ignore.
--    "keep_suggestion" is only a hint — an approved project is almost always
--    the one to keep, and otherwise the earliest. Read the titles before
--    acting: two genuinely different projects under one pair is a different
--    problem, and this query cannot tell the difference.
-- ---------------------------------------------------------------------------
SELECT
    s.pair_id,
    d.name                                          AS department,
    s.matric_no,
    s.name                                          AS uploaded_by,
    p.id                                            AS project_id,
    p.title,
    p.supervisor,
    p.status,
    p.uploaded_at,
    CASE
        WHEN p.status = 'approved' THEN 'KEEP (approved)'
        WHEN p.status = 'rejected' THEN 'ignore (rejected)'
        WHEN p.id = (
            SELECT MIN(p3.id)
            FROM students s3
            JOIN projects p3 ON p3.student_id = s3.id
            WHERE s3.pair_id = s.pair_id
              AND p3.status IN ('pending', 'approved')
        ) THEN 'keep (earliest live)'
        ELSE 'DUPLICATE - review'
    END                                             AS keep_suggestion
FROM students s
JOIN projects p    ON p.student_id = s.id
JOIN departments d ON d.id = s.department_id
WHERE s.pair_id IN (
    -- pairs holding more than one LIVE project
    SELECT s2.pair_id
    FROM students s2
    JOIN projects p2 ON p2.student_id = s2.id
    WHERE s2.pair_id IS NOT NULL
    GROUP BY s2.pair_id
    HAVING SUM(p2.status IN ('pending', 'approved')) > 1
)
ORDER BY s.pair_id, p.uploaded_at, p.id;


-- ---------------------------------------------------------------------------
-- 3. Cleaning up — NOT automated, on purpose.
--
-- These are real student submissions. Deleting the wrong row loses a project
-- and the file it points at, so no DELETE runs here. Decide per pair from
-- section 2, then remove one project at a time by its id:
--
--     DELETE FROM projects WHERE id = <project_id>;
--
-- The PDF stays in uploads/projects/ after the row goes; remove it by hand
-- only once you are sure, since two rows can point at the same file if a
-- partner re-uploaded the identical document.
--
-- A gentler alternative that keeps the evidence: mark the repeat rejected
-- instead of deleting it, so it drops out of the review queue but stays on
-- the record.
--
--     UPDATE projects
--        SET status = 'rejected',
--            remark = 'Duplicate of the pair submission - no action needed'
--      WHERE id = <project_id>;
--
-- Note this also unblocks the pair's upload form, since a rejected project
-- does not count as a live submission. Prefer the DELETE if that matters.
-- ---------------------------------------------------------------------------
