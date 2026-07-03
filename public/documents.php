<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

$user_role = $_SESSION['user_role'] ?? 'Admin';

// --- DYNAMIC SCHEMA CHECKER ---
$table_name = "documents";
$table_check = $conn->query("SHOW TABLES LIKE 'documents'");

if ($table_check && is_object($table_check) && $table_check->num_rows == 0) {
    $table_check_alt = $conn->query("SHOW TABLES LIKE 'files'");
    if ($table_check_alt && is_object($table_check_alt) && $table_check_alt->num_rows > 0) { 
        $table_name = "files"; 
    }
}

// --- SEARCH LOGIC ---
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
}

// --- FILE ACTIONS HANDLER ---
if (isset($_GET['download_id'])) {
    $id = intval($_GET['download_id']);
    $stmt = $conn->prepare("SELECT file_path FROM $table_name WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result_file = $stmt->get_result()->fetch_assoc();

    if ($result_file && file_exists($result_file['file_path'])) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($result_file['file_path']) . '"');
        readfile($result_file['file_path']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM $table_name WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: documents.php");
    exit;
}

$field_name = "name";
$columns = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'document_name'");

if ($columns && is_object($columns) && $columns->num_rows > 0) {
    $field_name = "document_name";
} else {
    $columns_alt = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'file_name'");
    if ($columns_alt && is_object($columns_alt) && $columns_alt->num_rows > 0) { 
        $field_name = "file_name"; 
    }
}

// Pinahusay na Query na may Search Filter
$query = "SELECT d.id, d.$field_name as doc_title, d.file_size, u.name as uploaded_by 
          FROM $table_name d 
          LEFT JOIN users u ON d.user_id = u.id 
          WHERE d.$field_name LIKE '%$search_query%' 
          ORDER BY d.id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Admin Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --pms-dark-slate: #1e293b;
            --pms-electric-blue: #3b82f6;
            --pms-bg: #f8fafc;
            --pms-border: #e2e8f0;
        }
        body { background-color: var(--pms-bg); color: #0f172a; overflow-x: hidden; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #ffffff; height: 70px; border-bottom: 1px solid var(--pms-border); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .custom-sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--pms-dark-slate); z-index: 100; }
        .custom-sidebar .nav-link { color: #94a3b8 !important; padding: 12px 20px; border-radius: 8px; margin: 4px 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: all 0.2s; }
        .custom-sidebar .nav-link:hover { color: #ffffff !important; background-color: rgba(255, 255, 255, 0.05) !important; }
        .custom-sidebar .nav-link.active-accent { color: #ffffff !important; background-color: var(--pms-electric-blue) !important; }
    </style>
</head>
<body>

    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-screwdriver-wrench fs-4" style="color: var(--pms-electric-blue);"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li><a href="admin_dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="nav-link"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="projects.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link active-accent"><i class="fa-solid fa-folder-tree"></i> Documents</a></li>
            </ul>
        </div>
        <div>
            <hr class="mx-3" style="border-color: rgba(255,255,255,0.15);">
            <a href="logout.php" class="nav-link text-danger m-3 p-0 d-flex align-items-center gap-2" style="text-decoration: none;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Global File Repository</h5>
            <span class="text-muted small">Authority: <strong class="text-danger"><?= htmlspecialchars($user_role); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Administrative Asset Vault</h3>
                    <p class="text-muted mb-0">Audit, inspect, and trace uploaded project charters, data parameters, and structural resources.</p>
                </div>
                <!-- Search Bar -->
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search documents..." value="<?= htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Resource ID</th>
                                    <th>Asset Document Title</th>
                                    <th>Size Verification</th>
                                    <th>Uploader Link Origin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && is_object($result) && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 text-muted font-monospace">#DOC-<?= $row['id']; ?></td>
                                            <td class="fw-semibold text-dark">
                                                <i class="fa-solid fa-file-shield text-primary me-2 fs-5"></i>
                                                <?= htmlspecialchars($row['doc_title']); ?>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['file_size'] ?? 'N/A'); ?></span></td>
                                            <td><span class="text-secondary small fw-bold"><?= htmlspecialchars($row['uploaded_by'] ?? 'System Process'); ?></span></td>
                                            <td>
                                                <a href="documents.php?download_id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this file?');">
                                                    <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No resources discovered inside the storage engine table <code><?= htmlspecialchars($table_name) ?></code>.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>