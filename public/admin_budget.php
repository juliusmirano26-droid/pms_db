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
    if ($user_res && is_object($user_res)) {
        $user_row = $user_res->fetch_assoc();
        $total_users = $user_row['total_users'] ?? 0;
    }
}

// 2. Total Global Projects Managed
$table_name = "assignments"; 
$total_projects = 0;
$project_res = $conn->query("SELECT COUNT(*) as total_projects FROM $table_name");
if ($project_res && is_object($project_res)) {
    $project_row = $project_res->fetch_assoc();
    $total_projects = $project_row['total_projects'] ?? 0;
}

// Additional Metrics
$c_res = $conn->query("SELECT id FROM assignments WHERE status IN ('completed', 'successful')");
$completed_projects = $c_res ? $c_res->num_rows : 0;

$d_res = $conn->query("SELECT id FROM documents");
$total_docs = $d_res ? $d_res->num_rows : 0;

// 3. FINANCIAL METRICS AGGREGATION
$grand_allocated_budget = 0.00;
$grand_labor_cost = 0.00;
$grand_material_cost = 0.00;
$grand_net_profit = 0.00;

$table_crew_check = $conn->query("SHOW TABLES LIKE 'onsite_personnel'");
if ($table_crew_check && $table_crew_check->num_rows > 0) {
    // 1. Total Operations Budget pooled directly from saved project rows
    $budget_res = $conn->query("SELECT SUM(IFNULL(project_budget, 0.00)) as total_budget FROM crew_project_details");
    if ($budget_res && $b_row = $budget_res->fetch_assoc()) {
        $grand_allocated_budget = floatval($b_row['total_budget'] ?? 0.00);
    }

    // 2. Total Active Labor Costs
    $labor_query = "SELECT SUM(op.daily_rate * (CAST(REGEXP_REPLACE(IFNULL(cpd.project_duration, '0'), '[^0-9]', '') AS UNSIGNED) * 26)) as total_labor
                    FROM onsite_personnel op
                    JOIN crew_project_details cpd ON op.assigned_project = cpd.project_name
                    WHERE LOWER(op.status) = 'active'";
    $labor_res = $conn->query($labor_query);
    if ($labor_res && $l_row = $labor_res->fetch_assoc()) {
        $grand_labor_cost = floatval($l_row['total_labor'] ?? 0.00);
    }

    // 3. Total Material Costs
    $table_mat_check = $conn->query("SHOW TABLES LIKE 'material_costs'");
    if ($table_mat_check && $table_mat_check->num_rows > 0) {
        $mat_res = $conn->query("SELECT SUM(quantity * unit_cost) as total_mat FROM material_costs");
        if ($mat_res && $m_row = $mat_res->fetch_assoc()) {
            $grand_material_cost = floatval($m_row['total_mat'] ?? 0.00);
        }
    }
}

// FIX: Pure float subtraction using our dedicated metric numeric variables instead of using table columns directly
$grand_net_profit = $grand_allocated_budget - $grand_labor_cost - $grand_material_cost;

// 4. User Demographics Breakdown
$role_distribution = ['Admin' => 0, 'Project Manager' => 0, 'Team Member' => 0];
if ($table_users_check && $table_users_check->num_rows > 0) {
    $role_res = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    if ($role_res && is_object($role_res)) {
        while ($row = $role_res->fetch_assoc()) {
            if (array_key_exists($row['role'], $role_distribution)) {
                $role_distribution[$row['role']] = $row['count'];
            }
        }
    }
}

// ================= OPERATIONAL SERVER METRICS =================
$disk_free = @disk_free_space(".");
$disk_total = @disk_total_space(".");
$disk_used = $disk_total - $disk_free;
$disk_usage_percentage = ($disk_total > 0) ? round(($disk_used / $disk_total) * 100, 1) : 0;

$db_status = ($conn->ping()) ? "Operational" : "Degraded State";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link active-accent"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="nav-link"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="projects.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Documents</a></li>
                <li><a href="admin_budget.php" class="nav-link"><i class="fa-solid fa-code text-info"></i> Dev Budget</a></li>
                <li><a href="crew_budget_control.php" class="nav-link"><i class="fa-solid fa-helmet-safety text-warning"></i> Crew Budget</a></li>
                <li><a href="sponsors.php" class="nav-link"><i class="fa-solid fa-hand-holding-dollar text-success"></i> Sponsors</a></li>
            </ul>
        </div>
        <div>
            <hr class="mx-3" style="border-color: rgba(255,255,255,0.15);">
            <a href="logout.php" class="nav-link text-danger m-3 p-0 d-flex align-items-center gap-2" style="text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Project Management System</h5>
            <span class="text-muted small">Authority: <strong class="text-danger"><?= htmlspecialchars($user_role); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Dashboard</h3>
                <p class="text-muted mb-0">Live updates on the database, storage space, and user access.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card shadow-sm p-3 h-100"><div class="d-flex align-items-center justify-content-between"><div><span class="text-muted small text-uppercase fw-bold">Platform Users</span><h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_users; ?></h2></div><div class="bg-primary-subtle text-primary rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="fa-solid fa-users"></i></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card shadow-sm p-3 h-100"><div class="d-flex align-items-center justify-content-between"><div><span class="text-muted small text-uppercase fw-bold">Projects</span><h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_projects; ?></h2></div><div class="bg-primary-subtle text-primary rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="fa-solid fa-sitemap"></i></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card shadow-sm p-3 h-100"><div class="d-flex align-items-center justify-content-between"><div><span class="text-muted small text-uppercase fw-bold">Completed</span><h2 class="fw-bold text-dark mb-0 mt-1"><?= $completed_projects; ?></h2></div><div class="bg-success-subtle text-success rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="fa-solid fa-circle-check"></i></div></div></div></div>
                <div class="col-12 col-sm-6 col-xl-3"><div class="card stat-card shadow-sm p-3 h-100"><div class="d-flex align-items-center justify-content-between"><div><span class="text-muted small text-uppercase fw-bold">Documents</span><h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_docs; ?></h2></div><div class="bg-warning-subtle text-warning rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="fa-solid fa-file-lines"></i></div></div></div></div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-5">
                    <div class="card border shadow-sm h-100" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Role Management</h5>
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
                    <div class="card shadow-sm border p-4 h-100" style="border-radius: 12px; background: white;">
                        <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-chart-bar me-2 text-primary"></i> System Analytics Graph</h5>
                        <canvas id="metricsChart" height="115"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card shadow-sm border p-4" style="border-radius: 12px; background: white;">
                        <div class="row align-items-center">
                            <div class="col-md-7">
                                <h5 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-hand-holding-dollar me-2 text-success"></i> Field Crew Profit Overview</h5>
                                <p class="text-muted small mb-4">Automatic calculation based on Operational Budget minus Labor Cost and Structural Material Logistics.</p>
                                
                                <div class="row g-2 mt-2">
                                    <div class="col-6 col-sm-3">
                                        <div class="p-3 border rounded">
                                            <span class="text-muted small fw-medium text-uppercase d-block">Total Budget</span>
                                            <span class="fw-bold fs-6 text-primary">₱<?= number_format($grand_allocated_budget, 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="p-3 border rounded">
                                            <span class="text-muted small fw-medium text-uppercase d-block">Est. Labor Cost</span>
                                            <span class="fw-bold fs-6 text-secondary">₱<?= number_format($grand_labor_cost, 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="p-3 border rounded">
                                            <span class="text-muted small fw-medium text-uppercase d-block">Material Cost</span>
                                            <span class="fw-bold fs-6 text-warning">₱<?= number_format($grand_material_cost, 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="p-3 border rounded bg-success-subtle border-success-subtle">
                                            <span class="text-success small fw-bold text-uppercase d-block">Net Profit (Kinita)</span>
                                            <span class="fw-bold fs-6 text-success">₱<?= number_format($grand_net_profit, 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 text-center mt-4 mt-md-0">
                                <div style="max-height: 180px; width: 100%; display: flex; justify-content: center;">
                                    <canvas id="profitDonutChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // System Metrics Bar Chart
        const ctx = document.getElementById('metricsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Accounts', 'Projects', 'Completed', 'Documents'],
                datasets: [{
                    data: [<?= $total_users ?>, <?= $total_projects ?>, <?= $completed_projects ?>, <?= $total_docs ?>],
                    backgroundColor: ['#1e293b', '#3b82f6', '#22c55e', '#eab308'],
                    borderRadius: 8
                }]
            },
            options: { plugins: { legend: { display: false } } }
        });

        // Professional Profit Donut Chart
        const profitCtx = document.getElementById('profitDonutChart').getContext('2d');
        new Chart(profitCtx, {
            type: 'doughnut',
            data: {
                labels: ['Net Profit (Kinita)', 'Labor Cost', 'Material Cost'],
                datasets: [{
                    data: [
                        <?= $grand_net_profit ?>, 
                        <?= $grand_labor_cost ?>, 
                        <?= $grand_material_cost ?>
                    ],
                    backgroundColor: ['#22c55e', '#cbd5e1', '#eab308'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, font: { size: 12, weight: '600' } }
                    }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>