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

// ================= PHP BACKEND: ADMIN UPDATE FOR PROJECT DETAILS =================
if (isset($_POST['update_crew_project'])) {
    $p_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $p_sponsor = mysqli_real_escape_string($conn, $_POST['sponsored_by']);
    $p_duration = mysqli_real_escape_string($conn, $_POST['project_duration']);
    $p_budget = floatval($_POST['project_budget']);
    $p_gross = floatval($_POST['gross_revenue']);

    $save_query = "INSERT INTO crew_project_details (project_name, sponsored_by, project_duration, project_budget, gross_revenue) 
                   VALUES ('$p_name', '$p_sponsor', '$p_duration', '$p_budget', '$p_gross')
                   ON DUPLICATE KEY UPDATE 
                   sponsored_by = '$p_sponsor', 
                   project_duration = '$p_duration', 
                   project_budget = '$p_budget',
                   gross_revenue = '$p_gross'";
    
    if ($conn->query($save_query)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-check me-2'></i> Successfully updated the operational settings for <strong>$p_name</strong>!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    Error: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// 1. OVERALL METRICS FOR ONSITE PERSONNEL
$metrics_query = "SELECT 
                    COUNT(id) as total_crew,
                    SUM(daily_rate) as total_daily_budget,
                    SUM(daily_rate * 6) as total_weekly_budget
                  FROM onsite_personnel WHERE LOWER(status) = 'active'";
$metrics_result = $conn->query($metrics_query)->fetch_assoc();

$total_crew = $metrics_result['total_crew'] ?? 0;
$total_daily = $metrics_result['total_daily_budget'] ?? 0.00;
$total_weekly = $metrics_result['total_weekly_budget'] ?? 0.00;

// 2. DETAILED PROJECT QUERY JOINED WITH OUR PARAMETERS TABLE
$project_budget_query = "SELECT 
                            op.assigned_project,
                            COUNT(op.id) as crew_count,
                            SUM(op.daily_rate) as project_daily,
                            SUM(op.daily_rate * 6) as project_weekly,
                            IFNULL(cpd.sponsored_by, 'Click Manage to Set') as sponsored_by,
                            IFNULL(cpd.project_duration, 'Click Manage to Set') as project_duration,
                            IFNULL(cpd.project_budget, 0.00) as project_budget,
                            IFNULL(cpd.gross_revenue, 0.00) as gross_revenue
                         FROM onsite_personnel op
                         LEFT JOIN crew_project_details cpd ON op.assigned_project = cpd.project_name
                         GROUP BY op.assigned_project
                         ORDER BY project_daily DESC";
$project_budget_result = $conn->query($project_budget_query);
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

    <!-- Sidebar Layout -->
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
                <li><a href="matrix_flow.php" class="nav-link"><i class="fa-solid fa-diagram-project"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-file-lines"></i> Documents</a></li>
                <li><a href="admin_budget.php" class="nav-link"><i class="fa-solid fa-code text-info"></i> Dev Budget</a></li>
                <li><a href="crew_budget_control.php" class="nav-link active-accent"><i class="fa-solid fa-helmet-safety text-warning"></i> Crew Budget</a></li>
            </ul>
        </div>
        <div>
            <hr class="mx-3" style="border-color: rgba(255,255,255,0.15);">
            <a href="logout.php" class="nav-link text-danger m-3 p-0 d-flex align-items-center gap-2" style="text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
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

            <!-- Top Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div><span class="text-muted small fw-semibold text-uppercase">Active Onsite Crew</span><h2 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_crew); ?></h2></div>
                            <div class="p-3 bg-primary-subtle text-primary rounded-circle"><i class="fa-solid fa-helmet-safety fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div><span class="text-muted small fw-semibold text-uppercase">Total Daily Labor Cost</span><h2 class="fw-bold text-dark mb-0 mt-1">₱<?= number_format($total_daily, 2); ?></h2></div>
                            <div class="p-3 bg-warning-subtle text-warning rounded-circle"><i class="fa-solid fa-money-bill-wave fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div><span class="text-muted small fw-semibold text-uppercase">Est. Weekly Budget (6-Days)</span><h2 class="fw-bold text-success mb-0 mt-1">₱<?= number_format($total_weekly, 2); ?></h2></div>
                            <div class="p-3 bg-success-subtle text-success rounded-circle"><i class="fa-solid fa-sack-dollar fs-4"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Labor Budget Table -->
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
                                    <th class="py-3">Sponsored By</th>
                                    <th class="py-3 text-center">Duration</th>
                                    <th class="py-3 text-center">Deployed Personnel</th>
                                    <th class="py-3 text-end">Daily Labor Cost</th>
                                    <th class="py-3 text-end">Allocated Budget</th>
                                    <th class="py-3 text-end text-success">Net Revenue (Kinita)</th>
                                    <th class="py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($project_budget_result && $project_budget_result->num_rows > 0): ?>
                                    <?php $modal_counter = 0; ?>
                                    <?php while ($row = $project_budget_result->fetch_assoc()): 
                                        $modal_counter++;
                                        $project_name = $row['assigned_project'] ?? 'Unassigned';
                                        $sponsor = $row['sponsored_by'];
                                        $duration = $row['project_duration'];
                                        $count = $row['crew_count'];
                                        $daily = $row['project_daily'];
                                        $budget = $row['project_budget'];
                                        $gross = $row['gross_revenue'];
                                        $is_unassigned = ($project_name === 'Unassigned' || $project_name === 'General Pool (No Project)');
                                        
                                        // Dynamic calculation para sa Net Profit / Kinita base sa Duration string
                                        preg_match_all('!\d+!', $duration, $matches); // Kukunin lang ang numero sa "6 Months"
                                        $months = isset($matches[0][0]) ? intval($matches[0][0]) : 0;
                                        
                                        // 26 days of operation per month (6-day work week)
                                        $total_project_days = $months * 26; 
                                        $estimated_labor_drain = $daily * $total_project_days;
                                        $net_revenue = $gross - $estimated_labor_drain;
                                    ?>
                                    <tr class="border-bottom">
                                        <!-- Project Title -->
                                        <td class="fw-bold <?= $is_unassigned ? 'text-danger' : 'text-dark'; ?>">
                                            <i class="fa-solid <?= $is_unassigned ? 'fa-triangle-exclamation' : 'fa-building-shield text-primary'; ?> me-2"></i>
                                            <?= htmlspecialchars($project_name); ?>
                                        </td>
                                        <!-- Sponsored By -->
                                        <td class="text-secondary small fw-semibold">
                                            <i class="fa-solid fa-handshake text-muted me-1"></i><?= htmlspecialchars($sponsor); ?>
                                        </td>
                                        <!-- Duration -->
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-2 py-1 small fw-medium">
                                                <i class="fa-regular fa-calendar-days text-muted me-1"></i><?= $duration; ?>
                                            </span>
                                        </td>
                                        <!-- Deployed Personnel -->
                                        <td class="text-center fw-semibold text-dark">
                                            <span class="badge bg-blue-subtle text-primary border border-primary-subtle px-2 py-1"><?= $count; ?> Crew(s)</span>
                                        </td>
                                        <!-- Labor Costs -->
                                        <td class="text-end fw-semibold text-muted">₱<?= number_format($daily, 2); ?></td>
                                        <!-- Project Total Budget -->
                                        <td class="text-end fw-semibold text-secondary">₱<?= number_format($budget, 2); ?></td>
                                        
                                        <!-- NET REVENUE (Mismong Kinita pagkatapos ng Deadline) -->
                                        <td class="text-end fw-bold text-success">
                                            <?php if ($is_unassigned || $gross == 0): ?>
                                                <span class="text-muted small">₱0.00</span>
                                            <?php else: ?>
                                                ₱<?= number_format($net_revenue, 2); ?>
                                                <div class="text-muted mt-1" style="font-size: 10px; font-weight: normal;">Net after <?= $total_project_days; ?> days</div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- ACTION BUTTON -->
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

                                    <!-- ================= POPUP MODAL FOR PROJECT ALLOCATION ================= -->
                                    <?php if (!$is_unassigned): ?>
                                    <div class="modal fade" id="editCrewProjModal<?= $modal_counter; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-3">
                                                <div class="modal-header bg-dark text-white">
                                                    <h6 class="modal-title fw-bold"><i class="fa-solid fa-sliders text-warning me-2"></i>Project Parameters Setup</h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <input type="hidden" name="project_name" value="<?= htmlspecialchars($project_name); ?>">
                                                    <div class="modal-body p-4">
                                                        <p class="text-muted small mb-3">I-update ang structural and financial controls para sa <strong><?= htmlspecialchars($project_name); ?></strong>.</p>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Sponsored By (Client Name)</label>
                                                            <input type="text" name="sponsored_by" class="form-control" value="<?= htmlspecialchars($sponsor); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Project Duration (Dapat may numero hal. "6 Months")</label>
                                                            <input type="text" name="project_duration" class="form-control" value="<?= htmlspecialchars($duration); ?>" placeholder="e.g. 6 Months" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Gross Contract Value (Buong Kontrata / Kabuuang Kita - ₱)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-light text-dark">₱</span>
                                                                <input type="number" step="0.01" min="0" name="gross_revenue" class="form-control fw-bold text-success" value="<?= $gross; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-bold text-secondary">Project Operations Budget (Inilaang Pondo - ₱)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-light text-dark">₱</span>
                                                                <input type="number" step="0.01" min="0" name="project_budget" class="form-control fw-bold text-primary" value="<?= $budget; ?>" required>
                                                            </div>
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