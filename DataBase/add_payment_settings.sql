-- ============================================================================
-- Admin-editable registration fees
-- Run once, on the live database, before deploying the updated PHP files.
--
-- The fees were constants at the top of student/register.php:
--     const FEE_HND = 2000.00;
--     const FEE_ND  = 4000.00;
-- so changing a price meant editing and re-uploading code. They now live here
-- and are edited from admin/payment_settings.php.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Single-row settings table, following the submission_settings convention
-- already used for the submission period (id fixed at 1, upserted with
-- ON DUPLICATE KEY UPDATE).
--
-- fee_nd is charged PER PAIR — one ND payment registers two students — while
-- fee_hnd is charged per student. The admin page spells this out, because the
-- difference is not obvious from the numbers alone.
--
-- Changing a fee does NOT affect payments already under way: register.php
-- copies the amount into pending_registrations.amount when the student starts,
-- and process_payment.php verifies Paystack against that stored figure.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_settings` (
    `id`         INT NOT NULL DEFAULT 1,
    `fee_nd`     DECIMAL(10,2) NOT NULL,
    `fee_hnd`    DECIMAL(10,2) NOT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` INT DEFAULT NULL,          -- admins.id, for "who changed the price"
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- Seed with the values that were hardcoded, so prices do not change on deploy.
-- INSERT IGNORE leaves an existing row alone if this is re-run.
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO `payment_settings` (`id`, `fee_nd`, `fee_hnd`)
VALUES (1, 4000.00, 2000.00);

-- ---------------------------------------------------------------------------
-- Confirm.
-- ---------------------------------------------------------------------------
-- SELECT * FROM payment_settings;
