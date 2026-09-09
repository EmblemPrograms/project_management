<?php
// payment_settings.php - Admin sets the ND and HND registration fees.
//
// The fees used to be constants in student/register.php, so a price change
// meant editing and re-uploading code.

require_once '../includes/config.php';
require_once '../includes/fees.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: index.php");
    exit;
}

$error   = '';
$success = '';

$current = get_fees($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_fees'])) {
    // Strip a typed naira sign, commas and spaces before validating, so
    // "N4,000" and "4000" are both accepted rather than rejected on a technicality.
    $clean = static function ($v) {
        return str_replace([',', ' ', "\u{20A6}", 'N', 'n'], '', trim((string) $v));
    };

    $nd  = $clean($_POST['fee_nd'] ?? '');
    $hnd = $clean($_POST['fee_hnd'] ?? '');

    $error = validate_fee($nd, 'ND fee') ?: validate_fee($hnd, 'HND fee');

    if ($error === '') {
        $nd  = (float) $nd;
        $hnd = (float) $hnd;

        $stmt = $pdo->prepare(
            "INSERT INTO payment_settings (id, fee_nd, fee_hnd, updated_by)
             VALUES (1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE fee_nd = ?, fee_hnd = ?, updated_by = ?"
        );
        $stmt->execute([$nd, $hnd, $_SESSION['admin_id'], $nd, $hnd, $_SESSION['admin_id']]);

        // A price change is worth a log line: it decides what every student
        // pays from this moment, and nothing else records who changed it.
        error_log(sprintf(
            'Registration fees changed by admin #%d: ND %s -> %s, HND %s -> %s',
            $_SESSION['admin_id'],
            number_format($current['ND'], 2), number_format($nd, 2),
            number_format($current['HND'], 2), number_format($hnd, 2)
        ));

        $success = sprintf(
            'Fees updated. ND pair: %s%s (was %s%s). HND student: %s%s (was %s%s).',
            NAIRA, number_format($nd, 2),  NAIRA, number_format($current['ND'], 2),
            NAIRA, number_format($hnd, 2), NAIRA, number_format($current['HND'], 2)
        );

        $current = ['ND' => $nd, 'HND' => $hnd];
    }
}

// Who changed them last, for the footnote.
$meta = null;
try {
    $meta = $pdo->query(
        "SELECT p.updated_at, a.full_name
         FROM payment_settings p
         LEFT JOIN admins a ON a.id = p.updated_by
         WHERE p.id = 1"
    )->fetch();
} catch (Throwable $e) {
    // Table not migrated yet; get_fees() already logged and fell back.
}

// How many students are mid-payment right now: their amount is already fixed,
// so a change here will not affect them.
$in_flight = 0;
try {
    $in_flight = (int) $pdo->query(
        "SELECT COUNT(*) FROM pending_registrations WHERE status = 'pending_payment'"
    )->fetchColumn();
} catch (Throwable $e) {
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Fees - Admin</title>
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .fee-now { font-size: 1.9rem; font-weight: 700; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 820px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Registration Fees</h3>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= safe_output($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= safe_output($error) ?></div>
    <?php endif; ?>

    <!-- What students are being charged right now -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-body">
                    <div class="text-muted small">ND — charged per PAIR</div>
                    <div class="fee-now text-success">&#8358;<?= number_format($current['ND'], 2) ?></div>
                    <div class="text-muted small">One payment registers two students.</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">HND — charged per student</div>
                    <div class="fee-now text-success">&#8358;<?= number_format($current['HND'], 2) ?></div>
                    <div class="text-muted small">One payment registers one student.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Change the fees</h5>
        </div>
        <div class="card-body">

            <form method="POST" onsubmit="return confirmFees();">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">ND fee (per pair)</label>
                        <div class="input-group">
                            <span class="input-group-text">&#8358;</span>
                            <input type="text" inputmode="decimal" name="fee_nd" id="fee_nd"
                                   class="form-control form-control-lg"
                                   value="<?= safe_output(number_format($current['ND'], 2, '.', '')) ?>" required>
                        </div>
                        <div class="form-text">
                            This is the total for BOTH students in the pair, not per student.
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">HND fee (per student)</label>
                        <div class="input-group">
                            <span class="input-group-text">&#8358;</span>
                            <input type="text" inputmode="decimal" name="fee_hnd" id="fee_hnd"
                                   class="form-control form-control-lg"
                                   value="<?= safe_output(number_format($current['HND'], 2, '.', '')) ?>" required>
                        </div>
                        <div class="form-text">Charged to each HND student individually.</div>
                    </div>
                </div>

                <button type="submit" name="update_fees" value="1"
                        class="btn btn-success btn-lg mt-4">Save Fees</button>
            </form>

            <hr class="my-4">

            <p class="text-muted small mb-1">
                New fees apply to registrations started from the moment you save.
                <?php if ($in_flight > 0): ?>
                    <strong><?= $in_flight ?> student(s)</strong> are part-way through payment right now;
                    they keep the price they were quoted, because the amount is recorded
                    when they begin and verified against that figure.
                <?php else: ?>
                    Students already part-way through payment keep the price they were
                    quoted, because the amount is recorded when they begin.
                <?php endif; ?>
            </p>
            <?php if ($meta && !empty($meta['updated_at'])): ?>
                <p class="text-muted small mb-0">
                    Last changed <?= safe_output(date('d M Y, g:i a', strtotime($meta['updated_at']))) ?>
                    <?= $meta['full_name'] ? 'by ' . safe_output($meta['full_name']) : '' ?>.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-5"></div>
</div>

<script>
// A misplaced zero here overcharges every student who registers next, so a
// large jump asks for confirmation before it is saved.
function confirmFees() {
    var current = { nd: <?= json_encode((float) $current['ND']) ?>,
                    hnd: <?= json_encode((float) $current['HND']) ?> };
    var next = { nd:  parseFloat(document.getElementById('fee_nd').value.replace(/[^0-9.]/g, '')),
                 hnd: parseFloat(document.getElementById('fee_hnd').value.replace(/[^0-9.]/g, '')) };

    var warn = [];
    [['nd', 'ND pair'], ['hnd', 'HND student']].forEach(function (pair) {
        var key = pair[0], label = pair[1];
        if (!isFinite(next[key]) || current[key] === 0) return;
        var ratio = next[key] / current[key];
        if (ratio >= 3 || ratio <= 0.34) {
            warn.push(label + ': N' + current[key].toLocaleString() + '  ->  N' + next[key].toLocaleString());
        }
    });

    if (warn.length === 0) return true;
    return confirm('That is a big change:\n\n' + warn.join('\n') +
                   '\n\nSave these fees?');
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
