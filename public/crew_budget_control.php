<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php"); exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$current_page = basename($_SERVER['PHP_SELF']);
$msg = "";

// ================= PHP BACKEND: ADMIN UPDATE FOR PROJECT DETAILS (AUTOMATIC POOLED BUDGET) =================
if (isset($_POST['update_crew_project'])) {
    $p_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $p_duration = mysqli_real_escape_string($conn, $_POST['project_duration']);

    // Awtomatikong kukunin ang kabuuang pondo mula sa master sponsors na naka-map sa project na ito
    $budget_query = "SELECT SUM(IFNULL(s.allocated_budget, 0.00)) as pooled_budget 
                     FROM project_sponsors_map psm
                     JOIN sponsors s ON psm.sponsor_name = s.sponsor_name
                     WHERE psm.project_name = '$p_name'";
    $budget_res = $conn->query($budget_query);
    $calculated_total_budget = 0.00;
    
    if ($budget_res && $b_row = $budget_res->fetch_assoc()) {
        $calculated_total_budget = floatval($b_row['pooled_budget'] ?? 0.00);
    }

    // I-update ang baseline project details gamit ang awtomatikong pondo
    $save_query = "INSERT INTO crew_project_details (project_name, project_duration, project_budget) 
                   VALUES ('$p_name', '$p_duration', '$calculated_total_budget')
                   ON DUPLICATE KEY UPDATE 
                   project_duration = '$p_duration', 
                   project_budget = '$calculated_total_budget'";
    
    if ($conn->query($save_query)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-check me-2'></i> Successfully updated configuration parameters! Automatically synchronized total pooled funding of <strong>₱" . number_format($calculated_total_budget, 2) . "</strong> for $p_name!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    Error updating records: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// 1. OVERALL METRICS CARD COUNTERS
$metrics_query = "SELECT 
                    COUNT(id) as total_crew,
                    SUM(daily_rate) as total_daily_budget,
                    SUM(daily_rate * 6) as total_weekly_budget
                  FROM onsite_personnel
                  WHERE LOWER(status) = 'active'";
$metrics_result = $conn->query($metrics_query)->fetch_assoc();

$total_crew = $metrics_result['total_crew'] ?? 0;
$total_daily = $metrics_result['total_daily_budget'] ?? 0.00;
$total_weekly = $metrics_result['total_weekly_budget'] ?? 0.00;


// 2. DETAILED PROJECT QUERY WITH ACCURATE MANY-TO-MANY SPONSOR BREAKDOWN & FUNDING VALUES FROM MASTER
$project_budget_query = "SELECT 
                            cpd.project_name as assigned_project,
                            cpd.project_duration as project_duration,
                            
                            -- Dinamiko nating kinukuha ang sum ng allocated_budget mula sa sponsors table para laging tugma
                            IFNULL((SELECT SUM(s.allocated_budget) 
                             FROM project_sponsors_map psm 
                             JOIN sponsors s ON psm.sponsor_name = s.sponsor_name 
                             WHERE psm.project_name = cpd.project_name), 0.00) as project_budget,
                            
                            -- CONCATS SPONSORS WITH THEIR MASTER DIRECTORY BUDGET ENTRIES
                            (SELECT IFNULL(GROUP_CONCAT(CONCAT(s.sponsor_name, ' (₱', FORMAT(s.allocated_budget, 2), ')') SEPARATOR '<br>'), 'No Sponsor Assigned') 
                             FROM project_sponsors_map psm
                             JOIN sponsors s ON psm.sponsor_name = s.sponsor_name
                             WHERE psm.project_name = cpd.project_name) as sponsored_by,
                            
                            (CAST(REGEXP_REPLACE(IFNULL(cpd.project_duration, '0'), '[^0-9]', '') AS UNSIGNED) * 26) as total_project_days,
                            
                            (SELECT COUNT(*) FROM onsite_personnel WHERE assigned_project = cpd.project_name AND LOWER(status) = 'active') as crew_count,
                            
                            (SELECT IFNULL(SUM(daily_rate), 0.00) FROM onsite_personnel WHERE assigned_project = cpd.project_name AND LOWER(status) = 'active') as project_daily,
                            
                            (SELECT IFNULL(SUM(quantity * unit_cost), 0.00) FROM material_costs WHERE project_name = cpd.project_name) as total_material_cost,
                            
                            ((SELECT IFNULL(SUM(daily_rate), 0.00) FROM onsite_personnel WHERE assigned_project = cpd.project_name AND LOWER(status) = 'active') * (CAST(REGEXP_REPLACE(IFNULL(cpd.project_duration, '0'), '[^0-9]', '') AS UNSIGNED) * 26)
                            ) as total_labor_cost
                         FROM crew_project_details cpd
                         ORDER BY project_budget DESC";
$project_budget_result = $conn->query($project_budget_query);

// 3. GRAND TOTAL PROFIT (SUM OF ALL PROJECTS KINITA)
$grand_net_revenue = 0.00;
if ($project_budget_result && $project_budget_result->num_rows > 0) {
    while ($calc_row = $project_budget_result->fetch_assoc()) {
        $p_budget = floatval($calc_row['project_budget'] ?? 0.00);
        $p_labor  = floatval($calc_row['total_labor_cost'] ?? 0.00);
        $p_mat    = floatval($calc_row['total_material_cost'] ?? 0.00);
        
        $p_profit = $p_budget - $p_labor - $p_mat;
        $grand_net_revenue += $p_profit;
    }
    $project_budget_result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProjectMS - Crew Budget Control</title>
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
        .stat-card { border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group fs-4" style="color: #3b82f6;"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li><a href="admin_dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="nav-link"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="matrix_flow.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Documents</a></li>
                <li><a href="admin_budget.php" class="nav-link"><i class="fa-solid fa-code text-info"></i> Dev Budget</a></li>
                <li><a href="crew_budget_control.php" class="nav-link active-accent"><i class="fa-solid fa-helmet-safety text-warning"></i> Crew Budget</a></li>
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
            <span class="text-muted small">Authority: <strong class="text-danger">Admin</strong></span>
        </header>

        <main class="p-4 container-fluid">
            <?= $msg; ?>

            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-scale-balanced text-primary me-2"></i>Field Crew Budget Control</h3>
                <p class="text-muted mb-0">Live labor cost tracking allocation and structural budget controls for Field Crew projects.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div><span class="text-muted small fw-semibold text-uppercase">Active Onsite Crew</span><h2 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_crew); ?></h2></div>
                            <div class="p-3 bg-primary-subtle text-primary rounded-circle"><i class="fa-solid fa-helmet-safety fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div><span class="text-muted small fw-semibold text-uppercase">Total Daily Labor Cost</span><h2 class="fw-bold text-dark mb-0 mt-1">₱<?= number_format($total_daily, 2); ?></h2></div>
                            <div class="p-3 bg-warning-subtle text-warning rounded-circle"><i class="fa-solid fa-money-bill-wave fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div><span class="text-muted small fw-semibold text-uppercase">Est. Weekly Budget (6-Days)</span><h2 class="fw-bold text-dark mb-0 mt-1">₱<?= number_format($total_weekly, 2); ?></h2></div>
                            <div class="p-3 bg-info-subtle text-info rounded-circle"><i class="fa-solid fa-sack-dollar fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-3 bg-success-subtle border border-success-subtle">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success small fw-bold text-uppercase">Total Profit (Kinita)</span>
                                <h2 class="fw-bold text-success mb-0 mt-1">₱<?= number_format($grand_net_revenue, 2); ?></h2>
                            </div>
                            <div class="p-3 bg-success text-white rounded-circle"><i class="fa-solid fa-hand-holding-dollar fs-4"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card stat-card bg-white border-0">
                <div class="card-header bg-transparent border-0 pt-4 ps-4 pb-0">
                    <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-building-user text-muted me-2"></i>Project Financial Metrics & Crew Roster</h5>
                    <p class="text-muted small mb-0">Overview of total allocated budget, length of duration, client sponsorship, and active manpower cost.</p>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table align-middle border-0 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3">Project Title</th>
                                    <th class="py-3">Sponsors & Contribution</th>
                                    <th class="py-3 text-center">Duration</th>
                                    <th class="py-3 text-center">Deployed Personnel</th>
                                    <th class="py-3 text-end">Daily Labor Cost</th>
                                    <th class="py-3 text-end">Calculated Budget</th>
                                    <th class="py-3 text-end text-success">Net Profit (Kinita)</th>
                                    <th class="py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($project_budget_result && $project_budget_result->num_rows > 0): ?>
                                    <?php $modal_counter = 0; ?>
                                    <?php while ($row = $project_budget_result->fetch_assoc()): 
                                        $modal_counter++;
                                        $project_name = $row['assigned_project'] ?? 'Unassigned';
                                        $sponsor_list = $row['sponsored_by'];
                                        $duration = $row['project_duration'] ?? 'Click Manage to Set';
                                        $count = $row['crew_count'];
                                        $daily = $row['project_daily'];
                                        
                                        $budget = floatval($row['project_budget'] ?? 0.00);
                                        $total_project_days = intval($row['total_project_days'] ?? 0);
                                        $mat_cost = floatval($row['total_material_cost'] ?? 0.00);
                                        $total_labor = floatval($row['total_labor_cost'] ?? 0.00);
                                        
                                        $is_unassigned = ($project_name === 'Unassigned' || $project_name === 'General Pool (No Project)');
                                        $net_revenue = $budget - $total_labor - $mat_cost;
                                    ?>
                                    <tr class="border-bottom">
                                        <td class="fw-bold <?= $is_unassigned ? 'text-danger' : 'text-dark'; ?>">
                                            <i class="fa-solid <?= $is_unassigned ? 'fa-triangle-exclamation' : 'fa-building-shield text-primary'; ?> me-2"></i>
                                            <?= htmlspecialchars($project_name); ?>
                                        </td>
                                        <td class="text-secondary small fw-medium" style="max-width: 260px; white-space: normal; line-height: 1.5;">
                                            <?= $sponsor_list; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-2 py-1 small fw-medium">
                                                <i class="fa-regular fa-calendar-days text-muted me-1"></i><?= htmlspecialchars($duration); ?>
                                            </span>
                                        </td>
                                        <td class="text-center fw-semibold text-dark">
                                            <span class="badge bg-blue-subtle text-primary border border-primary-subtle px-2 py-1"><?= $count; ?> Crew(s)</span>
                                        </td>
                                        <td class="text-end fw-semibold text-muted">₱<?= number_format($daily, 2); ?></td>
                                        <td class="text-end fw-bold text-dark">₱<?= number_format($budget, 2); ?></td>
                                        
                                        <td class="text-end fw-bold <?= $net_revenue >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php if ($is_unassigned || $budget == 0): ?>
                                                <span class="text-muted small">₱0.00</span>
                                            <?php else: ?>
                                                ₱<?= number_format($net_revenue, 2); ?>
                                                <div class="text-muted mt-1" style="font-size: 10px; font-weight: normal; line-height: 1.4;">
                                                    Labor: -₱<?= number_format($total_labor, 2); ?> (<?= $total_project_days; ?> days)<br>
                                                    Materials: -₱<?= number_format($mat_cost, 2); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="text-center">
                                            <?php if (!$is_unassigned): ?>
                                                <button class="btn btn-sm btn-outline-dark px-2 py-1" data-bs-toggle="modal" data-bs-target="#editCrewProjModal<?= $modal_counter; ?>">
                                                    <i class="fa-solid fa-pen-to-square me-1"></i>Manage
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <?php if (!$is_unassigned): ?>
                                    <div class="modal fade" id="editCrewProjModal<?= $modal_counter; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-md modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-3">
                                                <div class="modal-header bg-dark text-white">
                                                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-sliders text-warning me-2"></i>Project Setup Dashboard</h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <input type="hidden" name="project_name" value="<?= htmlspecialchars($project_name); ?>">
                                                    <div class="modal-body p-4">
                                                        <p class="text-muted small mb-3">I-configure ang runtime parameters para sa proyektong: <strong><?= htmlspecialchars($project_name); ?></strong>.</p>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Project Duration (Dapat may numero hal. "6 Months")</label>
                                                            <input type="text" name="project_duration" class="form-control" value="<?= htmlspecialchars($duration); ?>" placeholder="e.g. 6 Months" required>
                                                        </div>
                                                        
                                                        <div class="mb-1">
                                                            <label class="form-label small fw-bold text-secondary">Total Allocated Budget (Auto-Sum Master Sponsors Fund)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-light text-muted">₱</span>
                                                                <input type="text" class="form-control fw-bold bg-light" value="<?= number_format($budget, 2); ?>" readonly style="pointer-events: none;">
                                                            </div>
                                                            <div class="form-text text-muted" style="font-size: 11px;">Ang budget na ito ay awtomatikong sumusunod sa nakalagay na pondo sa Sponsors Page.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_crew_project" class="btn btn-sm btn-primary px-4 fw-semibold">Save Parameters</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center p-5 text-muted">No operational field records found.</td></tr>
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