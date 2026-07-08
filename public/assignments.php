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

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'] ?? 'Team Member';

// Update online status
$conn->query("UPDATE users SET is_online = 1, last_active = NOW() WHERE id = '$current_user_id'");

// Handle Task Dispatch Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    if (!in_array($current_user_role, ['Admin', 'Project Manager'])) {
        die("Unauthorized access.");
    }

    $task_name = trim($_POST['task_name']);
    $developer_id = !empty($_POST['developer_id']) ? intval($_POST['developer_id']) : null;
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];

    if (!empty($task_name) && !empty($deadline) && !empty($priority)) {
        $stmt = $conn->prepare("INSERT INTO assignments (task_name, developer_id, deadline, priority) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $task_name, $developer_id, $deadline, $priority);
        $stmt->execute();
        $stmt->close();
        
        header("Location: assignments.php");
        exit;
    }
}

// Fetch Team Members for dropdown
$devs_res = $conn->query("SELECT id, name FROM users WHERE role = 'Team Member' ORDER BY name ASC");

// Fetch Assignments based on Role
if (in_array($current_user_role, ['Admin', 'Project Manager'])) {
    $query = "SELECT a.id, a.task_name, a.deadline, a.priority, u.name as developer_name 
              FROM assignments a 
              LEFT JOIN users u ON a.developer_id = u.id 
              ORDER BY a.id DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT a.id, a.task_name, a.deadline, a.priority, u.name as developer_name 
              FROM assignments a 
              LEFT JOIN users u ON a.developer_id = u.id 
              WHERE a.developer_id = ? 
              ORDER BY a.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $current_user_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Operational Assignments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --pms-dark-slate: #1e293b; --pms-electric-blue: #3b82f6; --pms-bg: #f8fafc; }
        body { background-color: var(--pms-bg); }
        .custom-sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--pms-dark-slate); z-index: 100; }
        .main-content { margin-left: var(--sidebar-width); padding: 20px; }
        .custom-sidebar .nav-link { color: #94a3b8 !important; padding: 12px 20px; border-radius: 8px; margin: 4px 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .custom-sidebar .nav-link:hover { color: #ffffff !important; background-color: rgba(255, 255, 255, 0.05) !important; }
        .custom-sidebar .nav-link.active-accent { color: #ffffff !important; background-color: var(--pms-electric-blue) !important; }
    </style>
</head>
<body>

    <!-- Sidebar -->
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
                <li class="nav-item"><a href="vault_documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Vault Documents</a></li>
                <li class="nav-item"><a href="assignments.php" class="nav-link active-accent"><i class="fa-solid fa-sitemap"></i> Assignments</a></li>
                 <li><a href="developer_submissions.php" class="nav-link"><i class="fa-solid fa-file-import"></i> Developer Submissions</a></li>
                <li><a href="manager_expenses.php" class="nav-link"><i class="fa-solid fa-receipt"></i> Project Expenses</a></li>
            </ul>
        </div>
        <a href="logout.php" class="nav-link text-danger m-3 p-0" style="text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark"><i class="fa-solid fa-list-check text-warning me-2"></i>Operational Assignments</h2>
            <?php if (in_array($current_user_role, ['Admin', 'Project Manager'])): ?>
                <button class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#dispatchTaskModal" style="background-color: #ffc107; border: none;">
                    <i class="fa-solid fa-thumbtack me-1"></i> Dispatch Task
                </button>
            <?php endif; ?>
        </div>

        <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Task ID</th>
                                <th>Task Milestone Requirement</th>
                                <th>Assigned Developer</th>
                                <th>Target Deadline</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-monospace">#TSK-<?= $row['id']; ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['task_name']); ?></td>
                                        <td><?= htmlspecialchars($row['developer_name'] ?? 'Unassigned'); ?></td>
                                        <td><?= date("M d, Y", strtotime($row['deadline'])); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['priority']); ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No assignments currently.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (in_array($current_user_role, ['Admin', 'Project Manager'])): ?>
    <div class="modal fade" id="dispatchTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form action="assignments.php" method="POST">
                    <input type="hidden" name="action" value="add_task">
                    <div class="modal-header"><h5 class="modal-title">Dispatch New Milestone Task</h5></div>
                    <div class="modal-body p-4">
                        <input type="text" name="task_name" class="form-control mb-3" required placeholder="Task Name">
                        <select name="developer_id" class="form-select mb-3">
                            <option value="">-- Select Team Member --</option>
                            <?php while($dev = $devs_res->fetch_assoc()): ?>
                                <option value="<?= $dev['id']; ?>"><?= htmlspecialchars($dev['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <input type="date" name="deadline" class="form-control mb-3" required>
                        <select name="priority" class="form-select" required>
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-warning">Dispatch Task</button></div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>