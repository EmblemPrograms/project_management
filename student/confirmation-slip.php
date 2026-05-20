<?php
// confirmation-slip.php
require_once '../includes/config.php'; // Contains PDO connection and session_start()

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$project_id = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);

if (!$project_id) {
    die("Invalid project ID.");
}

// Secure query with proper joins (students + departments)
$stmt = $pdo->prepare("
    SELECT 
        p.id, p.title, p.file_path, p.status, p.supervisor, 
        p.uploaded_at, p.approved_at,
        s.name AS student_name, 
        s.matric_no,
        d.name AS department_name
    FROM projects p
    JOIN students s ON p.student_id = s.id
    LEFT JOIN departments d ON s.department_id = d.id
    WHERE p.id = ? 
      AND p.student_id = ?
");
$stmt->execute([$project_id, $_SESSION['user_id']]);
$slip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slip || $slip['status'] !== 'approved') {
    die("Project not found or has not been approved yet.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Approval Slip - <?php echo htmlspecialchars($slip['title']); ?></title>
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .slip-container {
            max-width: 800px;
            margin: 40px auto;
            border: 3px solid #198754;
            border-radius: 12px;
            padding: 40px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        .slip-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://res.cloudinary.com/dxpbjxzfv/image/upload/NNL_ywik9w');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.08;
            z-index: 0;
            pointer-events: none;
        }
        .slip-header {
            text-align: center;
            border-bottom: 4px solid #198754;
            padding-bottom: 20px;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        .header-logo {
            max-width: 100px;
            margin-bottom: 15px;
        }
        .official-stamp {
            position: absolute;
            top: 80px;
            right: 80px;
            transform: rotate(-15deg);
            font-size: 2.8rem;
            color: rgba(25, 135, 84, 0.15);
            font-weight: bold;
            pointer-events: none;
            user-select: none;
            z-index: 2;
        }
        .content {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body class="bg-light">

<div class="slip-container position-relative">
    <div class="official-stamp">APPROVED</div>

    <div class="slip-header">
        <!-- Network Image as Header Logo -->
        <img src="https://res.cloudinary.com/dxpbjxzfv/image/upload/NNL_ywik9w" 
             alt="Institution Logo" 
             class="header-logo img-fluid">
        
        <h2 class="text-success">PROJECT APPROVAL SLIP</h2>
        <p class="text-muted">School of Computing Technology • <?php echo date("Y"); ?></p>
    </div>

    <div class="content">
        <table class="table table-borderless">
            <tr>
                <th width="32%">Student Name:</th>
                <td><?php echo htmlspecialchars($slip['student_name']); ?></td>
            </tr>
            <tr>
                <th>Matriculation Number:</th>
                <td><strong><?php echo htmlspecialchars($slip['matric_no']); ?></strong></td>
            </tr>
            <tr>
                <th>Department:</th>
                <td><?php echo htmlspecialchars($slip['department_name'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Project Reference ID:</th>
                <td><strong>#PRJ-<?php echo str_pad($slip['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
            </tr>
            <tr>
                <th>Project Title:</th>
                <td><strong><?php echo htmlspecialchars($slip['title']); ?></strong></td>
            </tr>
            <tr>
                <th>Supervisor:</th>
                <td><?php echo htmlspecialchars($slip['supervisor'] ?? 'Not Assigned'); ?></td>
            </tr>
            <tr>
                <th>Date Approved:</th>
                <td>
                    <?php 
                    $approvedDate = $slip['approved_at'] ?? $slip['uploaded_at'];
                    echo date("d F, Y", strtotime($approvedDate)); 
                    ?>
                </td>
            </tr>
            <tr>
                <th>Status:</th>
                <td><span class="badge bg-success fs-6">APPROVED</span></td>
            </tr>
        </table>

        <div class="text-center mt-5">
            <a href="<?php echo htmlspecialchars($slip['file_path']); ?>" 
               class="btn btn-success btn-lg" download>
                <i class="bi bi-download"></i> Download Approved Project File
            </a>
        </div>

        <hr class="my-5">

        <div class="text-center text-muted small">
            This is an official approval document.<br>
            Generated on: <?php echo date("d M Y \a\\t h:i A"); ?><br>
            <strong>School of Computing Technology</strong>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>