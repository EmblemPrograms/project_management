<?php
/**
 * submission_list_page.php - The submission list (who has paid, and how much).
 *
 * Shared by admin/view_payments.php (all departments) and
 * department/view_payments.php (one department). Include it from a thin page
 * that has already required config.php, checked the role, and set:
 *
 *   $scope_department_id   int to lock the page to one department, or null
 *                          for the whole school. When set, the department
 *                          filter is applied server-side and the selector is
 *                          not shown — a department admin cannot widen it.
 *   $back_url              where the "Back to Dashboard" button goes.
 *   $scope_label           optional heading suffix, e.g. the department name.
 *
 * The important detail: an ND pair is ONE Paystack transaction, but it writes
 * TWO student rows, each carrying the FULL amount and the SAME reference (see
 * student/process_payment.php). Summing students.payment_amount therefore
 * double-counts every pair. Rows are grouped into transactions first, so a
 * pair shows as a single line for a single amount.
 */

$scope_department_id = $scope_department_id ?? null;
$back_url            = $back_url ?? 'dashboard.php';
$scope_label         = $scope_label ?? '';

$level   = $_GET['level'] ?? '';
$session = trim($_GET['session'] ?? '');
$status = $_GET['status'] ?? 'paid';   // paid | unpaid | all
$search = trim($_GET['search'] ?? '');

// A scoped page ignores any department_id in the query string. Trusting it
// would let a department admin read another department's payments by editing
// the URL.
$department_id = $scope_department_id !== null
    ? (int) $scope_department_id
    : ($_GET['department_id'] ?? '');

$departments = $scope_department_id === null
    ? $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll()
    : [];

// Offer only sessions that exist in the data being listed, so the dropdown
// never shows a session with nothing behind it.
if ($scope_department_id === null) {
    $sessions = $pdo->query(
        "SELECT DISTINCT session FROM students WHERE session <> '' ORDER BY session DESC"
    )->fetchAll(PDO::FETCH_COLUMN);
} else {
    $st = $pdo->prepare(
        "SELECT DISTINCT session FROM students WHERE department_id = ? AND session <> '' ORDER BY session DESC"
    );
    $st->execute([$scope_department_id]);
    $sessions = $st->fetchAll(PDO::FETCH_COLUMN);
}

$sql = "SELECT s.id, s.matric_no, s.name, s.email, s.level, s.session, s.pair_id, s.department_id,
               s.payment_reference, s.payment_status, s.payment_amount, s.payment_date,
               d.name AS department_name
        FROM students s
        LEFT JOIN departments d ON d.id = s.department_id
        WHERE 1=1";
$params = [];

if ($status === 'paid' || $status === 'unpaid') {
    $sql .= " AND s.payment_status = ?";
    $params[] = $status;
}
if ($department_id) {
    $sql .= " AND s.department_id = ?";
    $params[] = $department_id;
}
if ($level) {
    $sql .= " AND s.level = ?";
    $params[] = $level;
}
if ($session) {
    $sql .= " AND s.session = ?";
    $params[] = $session;
}
if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.matric_no LIKE ? OR s.payment_reference LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Newest payment first; rows never paid fall to the bottom.
$sql .= " ORDER BY s.payment_date IS NULL, s.payment_date DESC, s.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ---------------------------------------------------------------------------
// Collapse student rows into transactions.
//
// Grouping key, in order of preference:
//   ref:<reference>  a real Paystack transaction — the only reliable grouping
//   pair:<pair_id>   pre-migration ND pair whose reference was never recorded
//   stu:<id>         a lone student with no reference
// ---------------------------------------------------------------------------
$transactions = [];

foreach ($rows as $r) {
    if (!empty($r['payment_reference'])) {
        $key = 'ref:' . $r['payment_reference'];
    } elseif (!empty($r['pair_id'])) {
        $key = 'pair:' . $r['pair_id'];
    } else {
        $key = 'stu:' . $r['id'];
    }

    if (!isset($transactions[$key])) {
        $transactions[$key] = [
            'reference'  => $r['payment_reference'],
            'status'     => $r['payment_status'],
            'date'       => $r['payment_date'],
            'department' => $r['department_name'],
            'level'      => $r['level'],
            'session'    => $r['session'],
            'amounts'    => [],
            'students'   => [],
        ];
    }

    $transactions[$key]['students'][] = $r;

    if ($r['payment_amount'] !== null) {
        $transactions[$key]['amounts'][] = (float) $r['payment_amount'];
    }
    // Keep the earliest recorded date for the transaction.
    if ($r['payment_date'] && (!$transactions[$key]['date'] || $r['payment_date'] < $transactions[$key]['date'])) {
        $transactions[$key]['date'] = $r['payment_date'];
    }
}

// ---------------------------------------------------------------------------
// Per-transaction totals.
//
// amount = MAX of the member rows, not SUM: both halves of a pair record the
// same full figure. A mismatch between the two means the data is inconsistent,
// so it is flagged in the table rather than silently averaged away.
// ---------------------------------------------------------------------------
$total_collected = 0.0;
$paid_students   = 0;
$pair_count      = 0;
$single_count    = 0;
$unpaid_students = 0;

foreach ($transactions as $key => &$t) {
    $t['amount']   = $t['amounts'] ? max($t['amounts']) : null;
    $t['mismatch'] = count(array_unique($t['amounts'])) > 1;
    $t['is_pair']  = count($t['students']) > 1;

    if ($t['status'] === 'paid') {
        $total_collected += (float) $t['amount'];
        $paid_students   += count($t['students']);
        $t['is_pair'] ? $pair_count++ : $single_count++;
    } else {
        $unpaid_students += count($t['students']);
    }
}
unset($t);

$paid_transactions = $pair_count + $single_count;

// A scoped page has one fewer filter, so the row splits differently.
$col_search = $scope_department_id === null ? 'col-md-3' : 'col-md-5';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission List - School of Computing</title>
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card { border-left: 4px solid #198754; }
        .stat-card .value { font-size: 1.6rem; font-weight: 700; }
        .ref { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .8rem; }
        .student-line + .student-line { margin-top: .35rem; padding-top: .35rem; border-top: 1px dashed #dee2e6; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5" style="max-width: 1400px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0">Submission List</h3>
            <?php if ($scope_label): ?>
                <small class="text-muted"><?= safe_output($scope_label) ?></small>
            <?php endif; ?>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-outline-secondary">Print</button>
            <a href="<?= safe_output($back_url) ?>" class="btn btn-secondary">&larr; Back to Dashboard</a>
        </div>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Collected</div>
                    <div class="value text-success">&#8358;<?= number_format($total_collected, 2) ?></div>
                    <div class="text-muted small">across <?= $paid_transactions ?> transaction(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Students Covered</div>
                    <div class="value"><?= $paid_students ?></div>
                    <div class="text-muted small">people paid for</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="text-muted small">ND Pairs</div>
                    <div class="value"><?= $pair_count ?></div>
                    <div class="text-muted small">two students, one payment</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stat-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Single Payments</div>
                    <div class="value"><?= $single_count ?></div>
                    <div class="text-muted small">
                        <?php if ($unpaid_students): ?>
                            <span class="text-danger"><?= $unpaid_students ?> unpaid student(s) listed</span>
                        <?php else: ?>
                            one student, one payment
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="<?= $col_search ?>">
                    <input type="text" name="search" class="form-control"
                           placeholder="Name, matric number or payment reference"
                           value="<?= safe_output($search) ?>">
                </div>
                <?php if ($scope_department_id === null): ?>
                    <div class="col-md-3">
                        <select name="department_id" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= $department_id == $dept['id'] ? 'selected' : '' ?>>
                                    <?= safe_output($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <select name="session" class="form-select">
                        <option value="">All Sessions</option>
                        <?php foreach ($sessions as $sess): ?>
                            <option value="<?= safe_output($sess) ?>" <?= $session === $sess ? 'selected' : '' ?>>
                                <?= safe_output($sess) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="level" class="form-select">
                        <option value="">All Levels</option>
                        <option value="ND"  <?= $level === 'ND'  ? 'selected' : '' ?>>ND</option>
                        <option value="HND" <?= $level === 'HND' ? 'selected' : '' ?>>HND</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="paid"   <?= $status === 'paid'   ? 'selected' : '' ?>>Paid only</option>
                        <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>Unpaid only</option>
                        <option value="all"    <?= $status === 'all'    ? 'selected' : '' ?>>All</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <p class="text-center text-muted py-5">No submissions match these filters.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:15%;">Date</th>
                                <th style="width:10%;">Type</th>
                                <th>Student(s)</th>
                                <th style="width:10%;">Session</th>
                                <?php if ($scope_department_id === null): ?>
                                    <th style="width:14%;">Department</th>
                                <?php endif; ?>
                                <th style="width:14%;">Reference</th>
                                <th style="width:12%;" class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td>
                                    <?php if ($t['date']): ?>
                                        <?= date('d M Y', strtotime($t['date'])) ?><br>
                                        <small class="text-muted"><?= date('g:i a', strtotime($t['date'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['is_pair']): ?>
                                        <span class="badge bg-primary">ND Pair</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= safe_output($t['level'] ?: 'Single') ?></span>
                                    <?php endif; ?>
                                    <?php if ($t['status'] !== 'paid'): ?>
                                        <br><span class="badge bg-danger mt-1">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php foreach ($t['students'] as $s): ?>
                                        <div class="student-line">
                                            <strong><?= safe_output($s['name']) ?></strong><br>
                                            <small class="text-muted">
                                                <?= safe_output($s['matric_no']) ?>
                                                <?php if ($s['email']): ?>
                                                    &middot; <?= safe_output($s['email']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($t['is_pair']): ?>
                                        <small class="text-primary d-block mt-2">
                                            Paid together as one transaction
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?= safe_output($t['session'] ?: '—') ?></td>
                                <?php if ($scope_department_id === null): ?>
                                    <td><?= safe_output($t['department'] ?: '—') ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($t['reference']): ?>
                                        <span class="ref"><?= safe_output($t['reference']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">no record</span><br>
                                        <small class="text-muted">registered before tracking</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($t['amount'] !== null): ?>
                                        <strong>&#8358;<?= number_format($t['amount'], 2) ?></strong>
                                        <?php if ($t['is_pair']): ?>
                                            <br><small class="text-muted">for 2 students</small>
                                        <?php endif; ?>
                                        <?php if ($t['mismatch']): ?>
                                            <br><span class="badge bg-warning text-dark mt-1"
                                                      title="The two student rows recorded different amounts">
                                                amounts differ
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="<?= $scope_department_id === null ? 6 : 5 ?>" class="text-end">
                                    Total collected (filtered)
                                </th>
                                <th class="text-end">&#8358;<?= number_format($total_collected, 2) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <p class="text-muted small mb-0 mt-3">
                    An ND pair is one payment covering two students, so it appears once here.
                    Counting each student row separately would double the reported revenue.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-5"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
