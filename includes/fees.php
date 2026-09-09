<?php
/**
 * fees.php - Registration fees, set by the admin in admin/payment_settings.php.
 *
 * Always read the fee on the server. It must never come from the browser: a
 * posted amount could be edited to one naira.
 */

/** The naira sign. Kept here so the source files stay ASCII. */
const NAIRA = "\u{20A6}";

// What the fees were before they became editable. Used only as a fallback so a
// missing table cannot take registration offline — see get_fees().
const FEE_FALLBACK_ND  = 4000.00;   // per PAIR: one payment registers two students
const FEE_FALLBACK_HND = 2000.00;   // per student

/**
 * Current fees as ['ND' => float, 'HND' => float].
 *
 * Falls back to the previous hardcoded values if payment_settings is missing
 * or empty, which happens if the code is deployed before the migration is run.
 * Registration keeps working at the old prices instead of fataling, and the
 * reason is logged.
 */
function get_fees(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    try {
        $row = $pdo->query("SELECT fee_nd, fee_hnd FROM payment_settings WHERE id = 1")->fetch();
        if ($row) {
            return $cache = [
                'ND'  => (float) $row['fee_nd'],
                'HND' => (float) $row['fee_hnd'],
            ];
        }
        error_log('payment_settings has no row with id = 1; using fallback fees. '
                . 'Run DataBase/add_payment_settings.sql.');
    } catch (Throwable $e) {
        error_log('payment_settings unavailable (' . $e->getMessage() . '); using fallback fees. '
                . 'Run DataBase/add_payment_settings.sql.');
    }

    return $cache = ['ND' => FEE_FALLBACK_ND, 'HND' => FEE_FALLBACK_HND];
}

/** The fee for one registration of the given level. */
function get_fee(PDO $pdo, string $level): float {
    $fees = get_fees($pdo);
    return $fees[$level] ?? $fees['HND'];
}

/**
 * Validates a fee typed by an admin.
 * Returns an error string, or '' when the value is acceptable.
 *
 * The ceiling is a typo guard: an extra zero on a four-figure fee would
 * otherwise silently overcharge every student who registers next.
 */
function validate_fee($raw, string $label): string {
    $raw = trim((string) $raw);

    if ($raw === '') {
        return "$label is required.";
    }
    if (!is_numeric($raw)) {
        return "$label must be a number, for example 4000 or 4000.50.";
    }

    $value = (float) $raw;

    if ($value < 100) {
        // Paystack rejects anything under 1 naira; 100 is a sane floor for a
        // real registration fee and catches a misplaced decimal point.
        return "$label must be at least " . NAIRA . "100.";
    }
    if ($value > 1000000) {
        return "$label looks wrong — it cannot be more than " . NAIRA . "1,000,000.";
    }
    if (round($value, 2) != $value) {
        return "$label cannot have more than 2 decimal places.";
    }

    return '';
}
