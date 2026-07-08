<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php"); exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_role = $_SESSION['user_role'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle Budget Allocation/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_budget'])) {
    $project_id = $_POST['project_id'];
    $budget = $_POST['allocated_budget'];
    
    $stmt = $conn->prepare("UPDATE assignments SET allocated_budget = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("di", $budget, $project_id);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Prepare failed: " . $conn->error);
    }
}

// Fetch Assignments with Total Expenses
$query = "SELECT p.*, COALESCE(SUM(e.amount), 0) as total_expenses 
          FROM assignments p 
          LEFT JOIN expenses e ON p.task_name = e.title 
          GROUP BY p.id";

$projects_result = $conn->query($query);

if (!$projects_result) {
    die("Database Query Failed! Error description: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Admin Budget Control</title>
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
        
        .clickable-project-row { cursor: pointer; transition: background-color 0.15s; }
        .clickable-project-row:hover { background-color: #f1f5f9 !important; }
        .expense-detail-box { background-color: #fafafa; border-radius: 6px; padding: 10px; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

    <!-- Sidebar Layout Integration -->
    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link <?= $current_page == 'admin_dashboard.php' ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php" class="nav-link <?= $current_page == 'manage_users.php' ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-users-gear"></i> Manage Users
                    </a>
                </li>
                <li>
                    <a href="projects.php" class="nav-link <?= $current_page == 'projects.php' ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-sitemap"></i> Matrix Flow
                    </a>
                </li>
                <li>
                    <a href="documents.php" class="nav-link <?= $current_page == 'documents.php' ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-folder-tree"></i> Documents
                    </a>
                </li>
               <li>
                    <a href="admin_budget.php" class="nav-link active-accent">
                       <i class="fa-solid fa-code text-info"></i> Dev Budget
                     </a>
                </li>
                <li>
                    <a href="crew_budget_control.php" class="nav-link">
                       <i class="fa-solid fa-helmet-safety text-warning"></i> Crew Budget
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

    <!-- Main Content Area Wrapper -->
    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Project Management System</h5>
            <span class="text-muted small">Authority: <strong class="text-danger"><?= htmlspecialchars($user_role); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-wallet text-primary me-2"></i>Project Budget Matrix</h3>
                <p class="text-muted mb-0">Manage budgets, control spending, and track remaining funds. <span class="text-primary fw-semibold">Click a project name to expand details.</span></p>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4">Project Name</th>
                                <th>Allocated Budget</th>
                                <th>Total Expenses</th>
                                <th>Remaining Balance</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $projects_result->fetch_assoc()): 
                                $project_name = $row['task_name'] ?? ($row['project_name'] ?? ($row['name'] ?? 'Unnamed Project'));
                                $allocated_budget = $row['allocated_budget'] ?? 0;
                                $balance = $allocated_budget - $row['total_expenses'];
                                $badge_class = ($balance < 0) ? 'bg-danger' : 'bg-success';
                                $row_id = "details_" . $row['id'];
                            ?>
                            <tr class="clickable-project-row" data-bs-toggle="collapse" data-bs-target="#<?= $row_id; ?>" aria-expanded="false" aria-controls="<?= $row_id; ?>">
                                <td class="ps-4 fw-bold text-dark">
                                    <i class="fa-solid fa-chevron-right me-2 text-muted small"></i>
                                    <?= htmlspecialchars($project_name); ?>
                                </td>
                                <td class="text-primary fw-semibold">₱<?= number_format($allocated_budget, 2); ?></td>
                                <td class="text-danger">₱<?= number_format($row['total_expenses'], 2); ?></td>
                                <td><span class="badge <?= $badge_class; ?>">₱<?= number_format($balance, 2); ?></span></td>
                                <td class="text-center" onclick="event.stopPropagation();">
                                    <form method="POST" class="d-flex gap-2 justify-content-center align-items-center mb-0">
                                        <input type="hidden" name="project_id" value="<?= $row['id']; ?>">
                                        <input type="number" step="0.01" name="allocated_budget" class="form-control form-control-sm" style="width: 130px;" placeholder="Set Budget" required>
                                        <button type="submit" name="update_budget" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Collapsible Table with Added Manager Column -->
                            <tr id="<?= $row_id; ?>" class="collapse bg-light" onclick="event.stopPropagation();">
                                <td colspan="5" class="p-3 ps-5">
                                    <div class="expense-detail-box shadow-inner">
                                        <h6 class="fw-bold mb-2 text-secondary px-2" style="font-size: 0.85rem;">
                                            <i class="fa-solid fa-receipt me-1 text-success"></i> Manager Expense Audit Logs for: "<?= htmlspecialchars($project_name); ?>"
                                        </h6>
                                        <?php 
                                        // BAGONG QUERY: Naka-LEFT JOIN na sa `users` table para mahugot ang tunay na pangalan ng manager
                                        $stmt_expenses = $conn->prepare("
                                            SELECT e.id, e.category, e.amount, u.username AS manager_name 
                                            FROM expenses e 
                                            LEFT JOIN users u ON e.user_id = u.id 
                                            WHERE e.title = ? 
                                            ORDER BY e.id DESC
                                        ");
                                        $stmt_expenses->bind_param("s", $project_name);
                                        $stmt_expenses->execute();
                                        $expenses_result = $stmt_expenses->get_result();

                                        if ($expenses_result && $expenses_result->num_rows > 0): 
                                        ?>
                                            <table class="table table-sm table-bordered mb-0 bg-white shadow-xs" style="font-size: 0.85rem;">
                                                <thead class="table-secondary text-muted">
                                                    <tr>
                                                        <th class="ps-2">Log ID</th>
                                                        <th>Expense Category</th>
                                                        <th>Logged By (Manager)</th>
                                                        <th class="text-end pe-2">Amount Logged</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($exp = $expenses_result->fetch_assoc()): 
                                                        // Kung walang nakitang user_id sa table, magfo-fallback ito sa 'System / Manager'
                                                        $manager = !empty($exp['manager_name']) ? $exp['manager_name'] : 'System / Manager';
                                                    ?>
                                                    <tr>
                                                        <td class="ps-2 text-muted font-monospace">#<?= $exp['id']; ?></td>
                                                        <td class="fw-medium"><?= htmlspecialchars($exp['category']); ?></td>
                                                        <td><span class="badge bg-info-subtle text-info border border-info-subtle px-2"><i class="fa-solid fa-user-tie me-1"></i><?= htmlspecialchars($manager); ?></span></td>
                                                        <td class="text-end pe-2 text-danger fw-bold">₱<?= number_format($exp['amount'], 2); ?></td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <div class="text-muted small ps-2 py-1"><i class="fa-solid fa-info-circle me-1"></i></div>The Project Manager has not assigned a budget for this task yet.
                                        <?php endif; ?>
                                        <?php $stmt_expenses->close(); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>