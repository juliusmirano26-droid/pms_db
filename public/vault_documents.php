<?php
session_start();

// Access Control
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// Update user status
$uid = $_SESSION['user_id'];
$conn->query("UPDATE users SET is_online = 1, last_active = NOW() WHERE id = '$uid'");

// Ensure the local storage directory exists with proper permissions
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --- HANDLE FILE DELETION OPERATION ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $file_stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ?");
    $file_stmt->bind_param("i", $delete_id);
    $file_stmt->execute();
    $file_res = $file_stmt->get_result();
    
    if ($file_res && $row = $file_res->fetch_assoc()) {
        $target_path = $row['file_path'];
        // I-delete ang file kung ito ay exist sa server
        if (!empty($target_path) && file_exists($target_path)) {
            unlink($target_path);
        }
    }
    $file_stmt->close();

    $del_stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
    $del_stmt->bind_param("i", $delete_id);
    $del_stmt->execute();
    $del_stmt->close();

    header("Location: vault_documents.php");
    exit;
}

// --- HANDLE REAL FILE UPLOAD SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_doc') {
    $doc_display_name = trim($_POST['document_name']);
    $user_id = $_SESSION['user_id'];

    if (!empty($doc_display_name) && isset($_FILES['vault_file']) && $_FILES['vault_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['vault_file']['tmp_name'];
        $original_name = basename($_FILES['vault_file']['name']);
        $raw_size = $_FILES['vault_file']['size'];
        
        $file_size_formatted = ($raw_size >= 1048576) ? round($raw_size / 1048576, 2) . " MB" : round($raw_size / 1024, 2) . " KB";

        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        // Gumamit ng secure na naming convention
        $secure_file_name = 'vault_doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension;
        $destination_path = $upload_dir . $secure_file_name;

        if (move_uploaded_file($file_tmp_name, $destination_path)) {
            $stmt = $conn->prepare("INSERT INTO documents (document_name, file_path, file_size, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $doc_display_name, $destination_path, $file_size_formatted, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        header("Location: vault_documents.php");
        exit;
    }
}

$query = "SELECT d.id, d.document_name, d.file_path, d.file_size, u.name as uploaded_by 
          FROM documents d 
          LEFT JOIN users u ON d.user_id = u.id 
          ORDER BY d.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Vault Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --pms-dark-slate: #1e293b; --pms-electric-blue: #3b82f6; --pms-bg: #f8fafc; }
        body { background-color: var(--pms-bg); overflow-x: hidden; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 20px; }
        .custom-sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--pms-dark-slate); z-index: 100; }
        .custom-sidebar .nav-link { color: #94a3b8 !important; padding: 12px 20px; border-radius: 8px; margin: 4px 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .custom-sidebar .nav-link:hover { color: #ffffff !important; background-color: rgba(255, 255, 255, 0.05) !important; }
        .custom-sidebar .nav-link.active-accent { color: #ffffff !important; background-color: var(--pms-electric-blue) !important; }
    </style>
</head>
<body>

    <!-- Sidebar (Hindi binago) -->
    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group fs-4" style="color: var(--pms-electric-blue);"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item"><a href="manager_dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
                <li class="nav-item"><a href="work_personnel.php" class="nav-link"><i class="fa-solid fa-person-digging"></i> Work Personnel</a></li>
                <li class="nav-item"><a href="site_development.php" class="nav-link"><i class="fa-solid fa-trowel-bricks"></i> Site Development</a></li>
                <li class="nav-item"><a href="vault_documents.php" class="nav-link active-accent"><i class="fa-solid fa-folder-tree"></i> Vault Documents</a></li>
                <li><a href="assignments.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Assignments</a></li>
                <li><a href="developer_submissions.php" class="nav-link"><i class="fa-solid fa-file-import"></i> Developer Submissions</a></li>
                <li><a href="manager_expenses.php" class="nav-link"><i class="fa-solid fa-receipt"></i> Project Expenses</a></li>
            </ul>
        </div>
        <a href="logout.php" class="nav-link text-danger m-3 p-0" style="text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Content (Hindi binago) -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fa-solid fa-folder-tree text-success me-2"></i>Vault Document Management</h2>
            <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload Document
            </button>
        </div>

        <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Resource ID</th>
                                <th>Document Asset Name</th>
                                <th>Size Allocation</th>
                                <th>Publisher Origin</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-monospace">#DOC-<?= $row['id']; ?></td>
                                        <td class="fw-semibold text-dark"><i class="fa-regular fa-file-lines text-primary me-2"></i><?= htmlspecialchars($row['document_name']); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['file_size']); ?></span></td>
                                        <td><?= htmlspecialchars($row['uploaded_by'] ?? 'System Core'); ?></td>
                                        <td class="text-center">
                                            <!-- Download action: siguruhing ang href ay valid path -->
                                            <a href="<?= htmlspecialchars($row['file_path']); ?>" class="btn btn-sm btn-outline-primary" download><i class="fa-solid fa-download"></i></a>
                                            <!-- Delete action -->
                                            <a href="vault_documents.php?delete_id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Confirm purge?');"><i class="fa-solid fa-trash-can"></i></a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">Vault structures are currently empty.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal (Hindi binago) -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="vault_documents.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_doc">
                    <div class="modal-header"><h5 class="modal-title">Index Document Entry</h5></div>
                    <div class="modal-body p-4">
                        <input type="text" name="document_name" class="form-control mb-3" required placeholder="File name">
                        <input type="file" name="vault_file" class="form-control" required>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-success">Upload Asset</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>