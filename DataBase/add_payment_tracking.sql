-- ============================================================================
-- Payment tracking migration
-- Run once, on the live database, before deploying the updated PHP files.
--
-- Until now the Paystack reference was verified and then discarded, so there was
-- no way to match a student to a transaction. These columns close that gap.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Transactions must be able to roll back.
--    students is already InnoDB; these two are MyISAM, which silently ignores
--    beginTransaction()/rollBack(). A half-failed ND pair registration cannot
--    currently be undone.
-- ---------------------------------------------------------------------------
ALTER TABLE nd_pairs              ENGINE=InnoDB;
ALTER TABLE pending_registrations ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- 2. Payment columns on students.
--    payment_reference is deliberately a plain KEY, not UNIQUE: one ND pair
--    payment covers two student rows and they share the same reference.
-- ---------------------------------------------------------------------------
ALTER TABLE students
    ADD COLUMN payment_reference VARCHAR(100) DEFAULT NULL AFTER approved,
    ADD COLUMN payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid' AFTER payment_reference,
    ADD COLUMN payment_amount DECIMAL(10,2) DEFAULT NULL AFTER payment_status,
    ADD COLUMN payment_date DATETIME DEFAULT NULL AFTER payment_amount,
    ADD KEY idx_payment_reference (payment_reference);

-- ---------------------------------------------------------------------------
-- 3. Backfill students who registered before this migration.
--    Their reference is genuinely lost, so it stays NULL — the admin payments
--    page shows those as "no record". Amount and date come from the
--    pending_registrations row they were created from.
-- ---------------------------------------------------------------------------

-- 3a. HND students and ND "student 1" — matched directly on matric_no.
UPDATE students s
JOIN pending_registrations p
  ON p.matric_no = s.matric_no
 AND p.status = 'paid'
SET s.payment_status = 'paid',
    s.payment_amount = p.amount,
    s.payment_date   = p.created_at
WHERE s.payment_status = 'unpaid';

-- 3b. ND "student 2" — lives inside the pair_data JSON blob.
--     Requires MySQL 5.7+ / MariaDB 10.2+. If your server rejects this, skip it;
--     those rows simply stay 'unpaid' until you correct them by hand.
UPDATE students s
JOIN pending_registrations p
  ON JSON_UNQUOTE(JSON_EXTRACT(p.pair_data, '$.matric_no2')) = s.matric_no
 AND p.status = 'paid'
SET s.payment_status = 'paid',
    s.payment_amount = p.amount,
    s.payment_date   = p.created_at
WHERE s.payment_status = 'unpaid';

-- ---------------------------------------------------------------------------
-- 4. Check the result.
-- ---------------------------------------------------------------------------
-- SELECT payment_status, COUNT(*) FROM students GROUP BY payment_status;
