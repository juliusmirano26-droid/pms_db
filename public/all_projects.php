<?php
session_start();

// 1. Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Updated Access Control: Allow Admin, Project Manager, and Team Member (Developer)
$allowed_roles = ['Admin', 'Project Manager', 'Team Member'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Team Member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - All Projects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --primary-color: #4e73df; --secondary-bg: #f8f9fc; --dark-sidebar: #1e293b; }
        body { background-color: var(--secondary-bg); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--dark-sidebar); color: #fff; z-index: 100; }
        .sidebar .nav-link { color: rgba(255,255,255,0.75); padding: 12px 20px; border-radius: 8px; margin: 4px 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { background-color: var(--primary-color); }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #fff; height: 70px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

    <?php 
    // Fixes the include stream error by checking if sidebar.php is in the same directory or one level up
    if (file_exists('sidebar.php')) {
        include 'sidebar.php';
    } else if (file_exists('../sidebar.php')) {
        include '../sidebar.php';
    } else {
        // Fallback structural rendering of your sidebar if layout file missing from folder scope
        ?>
        <div class="sidebar d-flex flex-column justify-content-between pb-3">
            <div>
                <div class="p-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group text-primary fs-4"></i>
                    <span class="fs-5 fw-bold text-white tracking-wide">ProjectMS</span>
                </div>
                <hr class="text-secondary mx-3 my-0">
                <ul class="nav nav-pills flex-column mt-3">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                    <li><a href="all_projects.php" class="nav-link active"><i class="fa-solid fa-folder"></i> Projects</a></li>
                    <?php if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'Project Manager'): ?>
                    <li><a href="expenses.php" class="nav-link"><i class="fa-solid fa-wallet"></i> Expenses</a></li>
                    <?php endif; ?>
                    <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-file-lines"></i> Documents</a></li>
                    <li><a href="reports.php" class="nav-link"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
                </ul>
            </div>
            <div>
                <hr class="text-secondary mx-3"><a href="logout.php" class="nav-link text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
        <?php
    }
    ?>

    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Global Project Trackers</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: 600;"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div class="d-none d-sm-block">
                    <div class="fw-semibold text-dark fs-7" style="line-height: 1.2;"><?php echo htmlspecialchars($user_name); ?></div>
                    <span class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($user_role); ?></span>
                </div>
            </div>
        </header>

        <main class="p-4 container-fluid">
            <div class="card stat-card bg-white p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h3 class="fw-bold text-dark mb-1">Global Project Operations</h3>
                        <p class="text-muted mb-0">High-level enterprise overview of all active blueprints, milestones, and assignments.</p>
                    </div>
                </div>
            </div>

            <div class="card stat-card bg-white p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th>Project Title</th>
                                <th>Assigned Manager</th>
                                <th>Operational Progress</th>
                                <th>Status Badge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">E-Commerce App Redesign</td>
                                <td>John Manager</td>
                                <td style="width: 30%;">
                                    <div class="progress" style="height: 6px;"><div class="progress-bar bg-success" style="width: 75%"></div></div>
                                </td>
                                <td><span class="badge bg-success-subtle text-success">In Progress</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Database Schema Optimization</td>
                                <td>John Manager</td>
                                <td>
                                    <div class="progress" style="height: 6px;"><div class="progress-bar bg-warning" style="width: 90%"></div></div>
                                </td>
                                <td><span class="badge bg-warning-subtle text-warning">Review Panel</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>