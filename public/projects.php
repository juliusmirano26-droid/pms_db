<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$user_role = $_SESSION['user_role'] ?? 'Admin';

$query = "SELECT a.id, a.task_name AS project_name, a.deadline, a.status, 'Admin' AS manager_name, u.name AS completed_developer
          FROM assignments a LEFT JOIN users u ON a.developer_id = u.id ORDER BY a.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Operational Assignments Matrix Flow</title>
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
        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-progress { background-color: #e0f2fe; color: #0284c7; }
        .status-completed { background-color: #dcfce7; color: #15803d; }
    </style>
</head>
<body>

    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li><a href="admin_dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="nav-link"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="projects.php" class="nav-link active-accent"><i class="fa-solid fa-sitemap"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Documents</a></li>
                <li><a href="admin_budget.php" class="nav-link"><i class="fa-solid fa-code text-info"></i> Dev Budget</a></li>
                <li><a href="crew_budget_control.php" class="nav-link"><i class="fa-solid fa-helmet-safety text-warning"></i> Crew Budget</a></li>
                <li><a href="sponsors.php" class="nav-link"><i class="fa-solid fa-hand-holding-dollar text-success"></i> Sponsors</a></li>
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
            <h5 class="mb-0 text-secondary fw-semibold">Task Assignments</h5>
            <span class="text-muted small">Authority: <strong class="text-danger"><?= htmlspecialchars($user_role); ?></strong></span>
        </header>

        <main class="p-4">
            <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Project ID</th>
                                <th>Project Description</th>
                                <th>Assigned PM</th>
                                <th>Completed By</th>
                                <th>Deadline</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">#TSK-<?= $row['id']; ?></td>
                                        <td><?= htmlspecialchars($row['project_name']); ?></td>
                                        <td><?= htmlspecialchars($row['manager_name']); ?></td>
                                        <td>
                                            <?php 
                                            // Ginawang mas flexible ang status check[cite: 10]
                                            $status = strtolower(trim($row['status'])); 
                                            if ($status === 'completed' || $status === 'successful'): ?>
                                                <span class="text-success"><i class="fa-solid fa-check-double"></i> <?= htmlspecialchars($row['completed_developer'] ?? 'N/A'); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Pending...</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= !empty($row['deadline']) ? date("M d, Y", strtotime($row['deadline'])) : 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                            $badgeClass = (strtolower($row['status']) === 'completed' || strtolower($row['status']) === 'successful') ? 'status-completed' : 'status-progress';
                                            ?>
                                            <span class="badge px-3 py-2 rounded-pill <?= $badgeClass ?>"><?= ucfirst($row['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5">Walang data na makita sa assignments table.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conn->close(); ?>