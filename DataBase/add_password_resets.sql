-- ============================================================================
-- Password reset migration
-- Run once, on the live database, before deploying the forgot_password.php
-- pages under student/ and admin/.
--
-- The "Forgot Password?" link on the student login page had no page behind it,
-- and admins had no reset path at all. This table backs both OTP flows.
--
-- NOTE: if you already ran an earlier, student-only version of this file, do
-- not run section 1 — jump to section 2 for the upgrade statements instead.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. FRESH INSTALL
--
-- Why a separate table instead of reusing email_verification:
--
--   email_verification holds registration OTPs and stores them in plain text.
--   Sharing it would let an unused registration OTP be replayed to take over
--   an account, so reset codes get their own table.
--
--   otp_hash holds a password_hash() of the 6-digit code, not the code itself:
--   a leaked dump then cannot be used to reset anyone's password.
--
--   attempts caps guessing. Six digits is only a million combinations, which
--   is brute-forceable in minutes without a limit; the PHP side stops the row
--   at 5 wrong tries.
--
--   (account_type, account_id) rather than a foreign key, because one table
--   serves two unrelated ones: students (InnoDB) and admins (MyISAM, which
--   cannot take a foreign key at all). Section 3 covers the tidy-up that a
--   cascade would otherwise have handled.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`           INT NOT NULL AUTO_INCREMENT,
    `account_type` ENUM('student','admin') NOT NULL,
    `account_id`   INT NOT NULL,
    `otp_hash`     VARCHAR(255) NOT NULL,
    `expires_at`   DATETIME NOT NULL,
    `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `used`         TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_account` (`account_type`, `account_id`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------------
-- 2. UPGRADE FROM THE STUDENT-ONLY VERSION
--
-- Only if you already created password_resets with a student_id column and a
-- foreign key to students. Uncomment and run these instead of section 1.
-- ---------------------------------------------------------------------------
-- ALTER TABLE password_resets DROP FOREIGN KEY fk_password_resets_student;
-- ALTER TABLE password_resets DROP KEY idx_student;
-- ALTER TABLE password_resets
--     CHANGE student_id account_id INT NOT NULL,
--     ADD COLUMN account_type ENUM('student','admin') NOT NULL DEFAULT 'student' AFTER id,
--     ADD KEY idx_account (account_type, account_id);
-- ALTER TABLE password_resets ALTER COLUMN account_type DROP DEFAULT;

-- ---------------------------------------------------------------------------
-- 3. Housekeeping
--
-- The PHP side deletes an account's old rows whenever a new code is requested
-- and again once the password is changed, so this only sweeps up rows that
-- were abandoned mid-flow, plus any left behind by a deleted account.
-- Safe to run at any time.
-- ---------------------------------------------------------------------------
-- DELETE FROM password_resets WHERE expires_at < NOW() - INTERVAL 7 DAY;

-- ---------------------------------------------------------------------------
-- 4. Admin resets email the address in admins.email, so check yours is real
--    and current before you need it.
-- ---------------------------------------------------------------------------
-- SELECT id, full_name, email, role FROM admins;
