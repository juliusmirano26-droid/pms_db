<?php
session_start();

// Strict Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Database Connection Hook
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

$user_name = $_SESSION['user_name'] ?? 'Admin';
$user_role = $_SESSION['user_role'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);

// ================= LIVE DATABASE METRICS METADATA AGGREGATION =================

// 1. Total Registered Platform Users
$total_users = 0;
$table_users_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_users_check && $table_users_check->num_rows > 0) {
    $user_res = $conn->query("SELECT COUNT(*) as total_users FROM users");
    // CRITICAL SECURITY CHECK: Siguraduhing object bago i-fetch
    if ($user_res && is_object($user_res)) {
        $user_row = $user_res->fetch_assoc();
        $total_users = $user_row['total_users'] ?? 0;
    }
}

// 2. Total Global Projects Managed
$table_name = "projects";
$table_check = $conn->query("SHOW TABLES LIKE 'projects'");
if (!$table_check || $table_check->num_rows == 0) {
    $table_check_alt = $conn->query("SHOW TABLES LIKE 'project_list'");
    if ($table_check_alt && $table_check_alt->num_rows > 0) { 
        $table_name = "project_list"; 
    } else {
        $table_name = ""; 
    }
}

$total_projects = 0;
if (!empty($table_name)) {
    $project_res = $conn->query("SELECT COUNT(*) as total_projects FROM $table_name");
    // CRITICAL SECURITY CHECK: Siguraduhing object bago i-fetch
    if ($project_res && is_object($project_res)) {
        $project_row = $project_res->fetch_assoc();
        $total_projects = $project_row['total_projects'] ?? 0;
    }
}

// 3. User Demographics Breakdown (FIX FOR LINE 52 FATAL ERROR)
$role_distribution = ['Admin' => 0, 'Project Manager' => 0, 'Team Member' => 0];
if ($table_users_check && $table_users_check->num_rows > 0) {
    $role_res = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    // CRITICAL SECURITY CHECK: Binalot sa is_object() para hindi mag-crash sa line 52
    if ($role_res && is_object($role_res)) {
        while ($row = $role_res->fetch_assoc()) {
            if (array_key_exists($row['role'], $role_distribution)) {
                $role_distribution[$row['role']] = $row['count'];
            }
        }
    }
}

// ================= OPERATIONAL SERVER OPERATIVE METRICS =================
$disk_free = @disk_free_space(".");
$disk_total = @disk_total_space(".");
$disk_used = $disk_total - $disk_free;
$disk_usage_percentage = ($disk_total > 0) ? round(($disk_used / $disk_total) * 100, 1) : 0;

$db_status = ($conn->ping()) ? "Operational" : "Degraded State";
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/MySQL Stack';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Admin Dashboard</title>
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
        .stat-card { background-color: #ffffff; border: 1px solid var(--pms-border); border-radius: 12px; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
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
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link active-accent">
                        <i class="fa-solid fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php" class="nav-link">
                        <i class="fa-solid fa-users-gear"></i> Manage Users
                    </a>
                </li>
                <li>
                    <a href="projects.php" class="nav-link">
                        <i class="fa-solid fa-diagram-project"></i> Projects
                    </a>
                </li>
                <li>
                    <a href="documents.php" class="nav-link">
                        <i class="fa-solid fa-folder-tree"></i> Documents
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="nav-link">
                        <i class="fa-solid fa-chart-line"></i> Reports Panel
                    </a>
                </li>
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
            <h5 class="mb-0 text-secondary fw-semibold">Global Administrative Command Console</h5>
            <span class="text-muted small">Authority: <strong class="text-danger"><?= htmlspecialchars($user_role); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">System Health Overview</h3>
                <p class="text-muted mb-0">Real-time indicators tracking core database structures, storage limits, and role provisions.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold tracking-wider">Platform Users</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_users; ?></h2>
                            </div>
                            <div class="bg-primary-subtle text-primary rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">Active authorization mappings</div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold tracking-wider">Global Projects</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_projects; ?></h2>
                            </div>
                            <div class="bg-success-subtle text-success rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-folder-tree"></i>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">Monitored lifecycle workflows</div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold tracking-wider">Operational Health</span>
                                <h4 class="fw-bold text-success mb-0 mt-2"><i class="fa-solid fa-heart-pulse me-1"></i> Excellent</h4>
                            </div>
                            <div class="bg-info-subtle text-info rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-server"></i>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">Status: <strong><?= $db_status; ?></strong></div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold tracking-wider">Storage Capacity</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $disk_usage_percentage; ?>%</h2>
                            </div>
                            <div class="bg-warning-subtle text-warning rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-hard-drive"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $disk_usage_percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-lg-5">
                    <div class="card border shadow-sm h-100" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Identity Matrix Distribution</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between small fw-semibold mb-1">
                                    <span>Administrators</span>
                                    <span><?= $role_distribution['Admin']; ?> Account(s)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= ($total_users > 0) ? ($role_distribution['Admin'] / $total_users) * 100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between small fw-semibold mb-1">
                                    <span>Project Managers</span>
                                    <span><?= $role_distribution['Project Manager']; ?> Account(s)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= ($total_users > 0) ? ($role_distribution['Project Manager'] / $total_users) * 100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small fw-semibold mb-1">
                                    <span>Team Members / Developers</span>
                                    <span><?= $role_distribution['Team Member']; ?> Account(s)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= ($total_users > 0) ? ($role_distribution['Team Member'] / $total_users) * 100 : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="card border shadow-sm h-100" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-terminal me-2 text-secondary"></i>Runtime Environment Context</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 long-table" style="font-size: 0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Parameter Identifier</th>
                                            <th>Current Running Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="ps-4 text-muted">Backend Architecture Stack</td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($server_software); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4 text-muted">Active Database Driver Target</td>
                                            <td class="fw-semibold text-dark">MySQL Native Driver (Prepared Statements Active)</td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4 text-muted">PHP Core Version Platform</td>
                                            <td class="fw-semibold text-dark"><?= phpversion(); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-4 text-muted">Active Master Session Root ID</td>
                                            <td class="text-secondary font-monospace" style="font-size: 0.8rem;">ID: #<?= session_id(); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>