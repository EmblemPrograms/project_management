<?php
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';
checkRole(['student']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $file = $_FILES['project_file'];

    if ($file['error'] == 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf','docx','pptx']) && $file['size'] <= 20*1024*1024) {
            $new_name = uniqid('proj_') . '.' . $ext;
            $path = 'uploads/projects/' . $new_name;
            
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $stmt = $pdo->prepare("INSERT INTO projects (student_id, title, file_path) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $title, $path]);
                $success = "Project uploaded successfully! Pending approval.";
            }
        } else {
            $error = "Invalid file type or size too large.";
        }
    }
}

include '../includes/header.php';
?>

<div class="max-w-2xl mx-auto mt-10 bg-white p-8 rounded-lg shadow">
    <h2 class="text-3xl font-bold mb-6">Upload Your Project</h2>
    <?php if (isset($success)) echo "<p class='text-green-600'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p class='text-red-600'>$error</p>"; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-6">
            <label class="block font-medium">Project Title</label>
            <input type="text" name="title" required class="w-full border p-3 rounded">
        </div>
        <div class="mb-6">
            <label class="block font-medium">Upload File (PDF, DOCX, PPTX - max 20MB)</label>
            <input type="file" name="project_file" required class="w-full border p-3 rounded">
        </div>
        <button type="submit" class="w-full bg-green-600 text-white py-3 rounded font-semibold hover:bg-green-700">
            Upload Project
        </button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>