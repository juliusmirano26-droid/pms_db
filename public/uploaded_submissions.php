<?php
session_start();

// Strict Access Control: Project Manager only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
        header("Location: admin_dashboard.php");
    } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Team Member') {
        header("Location: developer_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

// Database Connection
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

$user_name = $_SESSION['user_name'] ?? 'Patrick Labayog';
$user_role = $_SESSION['user_role'] ?? 'Project Manager';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle Status Updates (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $task_id = intval($_POST['task_id']);
    $new_status = $_POST['status']; // 'Successful' or 'Rejected'
    
    if (in_array($new_status, ['Successful', 'Rejected'])) {
        $update_stmt = $conn->prepare("UPDATE assignments SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $task_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    header("Location: uploaded_submissions.php");
    exit;
}

// Normalized schema keys mapping to assignments data structure
$query = "SELECT 
            a.id AS task_id, 
            a.task_name AS task_requirement, 
            a.priority AS task_priority, 
            a.file_path AS file_location, 
            a.status AS task_status,
            u.name AS developer_name 
          FROM assignments a
          INNER JOIN users u ON a.developer_id = u.id 
          WHERE a.file_path IS NOT NULL AND a.file_path != ''
          ORDER BY a.id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Developer Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --pms-dark-slate: #1e293b; --pms-electric-blue: #3b82f6; --pms-bg: #f8fafc; --pms-border: #e2e8f0; }
        body { background-color: var(--pms-bg); color: #0f172a; overflow-x: hidden; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #ffffff; height: 70px; border-bottom: 1px solid var(--pms-border); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .custom-sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--pms-dark-slate); z-index: 100; }
        .custom-sidebar .nav-link { color: #94a3b8 !important; padding: 12px 20px; border-radius: 8px; margin: 4px 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; transition: all 0.2s; }
        .custom-sidebar .nav-link:hover { color: #ffffff !important; background-color: rgba(255, 255, 255, 0.05) !important; }
        .custom-sidebar .nav-link.active-accent { color: #ffffff !important; background-color: var(--pms-electric-blue) !important; }
        
        .action-container { max-width: 160px; margin: 0 auto; }
    </style>
</head>
<body>

    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group fs-4" style="color: var(--pms-electric-blue);"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li><a href="manager_dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
                <li><a href="vault_documents.php" class="nav-link"><i class="fa-solid fa-file-shield"></i> Vault Documents</a></li>
                <li><a href="assignments.php" class="nav-link"><i class="fa-solid fa-list-check"></i> Assignments</a></li>
                <li><a href="uploaded_submissions.php" class="nav-link active-accent"><i class="fa-solid fa-arrow-up-right-from-square"></i> Developer Submissions</a></li>
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
            <h5 class="mb-0 text-secondary fw-semibold">Task Submissions</h5>
            <span class="text-muted small">Logged in as: <strong><?= htmlspecialchars($user_name); ?></strong> (<?= htmlspecialchars($user_role); ?>)</span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Developer Submissions</h3>
                <p class="text-muted mb-0">Review and manage submissions from allocated team members.</p>
            </div>

            <div class="card border shadow-sm mb-4" style="border-radius: 12px; background: white;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 10%;">Task ID</th>
                                    <th style="width: 25%;">Task Milestone Requirement</th>
                                    <th style="width: 20%;">Submitted By</th>
                                    <th style="width: 15%;">Priority</th>
                                    <th style="width: 15%;">Review Progress</th>
                                    <th class="text-center" style="width: 15%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): 
                                        $file_path = $row['file_location'];
                                        $is_file_valid = file_exists($file_path);
                                    ?>
                                        <tr>
                                            <td class="ps-4 font-monospace text-secondary">#TSK-<?= htmlspecialchars($row['task_id']); ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['task_requirement'] ?? 'N/A'); ?></td>
                                            <td class="text-secondary">
                                                <i class="fa-regular fa-user-circle me-1 text-primary"></i> 
                                                <?= htmlspecialchars($row['developer_name'] ?? 'Unknown Developer'); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $priority = $row['task_priority'] ?? 'Medium';
                                                $badge_class = 'bg-warning text-dark';
                                                if (strcasecmp($priority, 'High') === 0) $badge_class = 'bg-danger text-white';
                                                if (strcasecmp($priority, 'Low') === 0) $badge_class = 'bg-info text-white';
                                                ?>
                                                <span class="badge <?= $badge_class; ?> rounded-2 px-2.5 py-1.5" style="font-size: 0.75rem;">
                                                    <?= htmlspecialchars($priority); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $row['task_status'] ?? 'Pending Review';
                                                $status_badge = 'bg-secondary text-white';
                                                if ($status === 'Successful') $status_badge = 'bg-success text-white';
                                                if ($status === 'Rejected') $status_badge = 'bg-danger text-white';
                                                ?>
                                                <span class="badge <?= $status_badge; ?> px-2 py-1" style="font-size: 0.75rem;"><?= htmlspecialchars($status); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="action-container d-flex flex-column gap-2 py-2">
                                                    <?php if ($is_file_valid): ?>
                                                        <a href="<?= htmlspecialchars($file_path); ?>" download class="btn btn-sm btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-1 py-1.5" style="font-size: 0.8rem; border-radius: 6px; font-weight: 500;">
                                                            <i class="fa-solid fa-cloud-arrow-down"></i> View File
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-secondary w-100" disabled style="font-size: 0.8rem; border-radius: 6px;">
                                                            <i class="fa-solid fa-file-circle-xmark"></i> Missing
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <div class="d-flex gap-2 w-100">
                                                        <form method="POST" action="" class="w-50 m-0">
                                                            <input type="hidden" name="task_id" value="<?= $row['task_id']; ?>">
                                                            <input type="hidden" name="status" value="Successful">
                                                            <button type="submit" name="update_status" class="btn btn-sm btn-success w-100 d-inline-flex align-items-center justify-content-center gap-1 py-1.5 text-white" style="font-size: 0.75rem; border-radius: 6px; font-weight: 500;" title="Mark Successful">
                                                                <i class="fa-solid fa-check"></i> Approve
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="" class="w-50 m-0">
                                                            <input type="hidden" name="task_id" value="<?= $row['task_id']; ?>">
                                                            <input type="hidden" name="status" value="Rejected">
                                                            <button type="submit" name="update_status" class="btn btn-sm btn-danger w-100 d-inline-flex align-items-center justify-content-center gap-1 py-1.5 text-white" style="font-size: 0.75rem; border-radius: 6px; font-weight: 500;" title="Reject Deliverable">
                                                                <i class="fa-solid fa-xmark"></i> Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">No developer task submission uploads found in the archive channels.</td>
                                    </tr>
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