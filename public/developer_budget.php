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

// Create table if it doesn't exist (Self-healing structure fallback)
$table_init = "CREATE TABLE IF NOT EXISTS developer_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_name VARCHAR(255) NOT NULL,
    allocated_budget DECIMAL(15,2) DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'Active',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($table_init);

// Handle inline actions (Form processing)
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save_budget') {
        $project_name = $conn->real_escape_string($_POST['project_name']);
        $allocated = floatval($_POST['allocated_budget']);
        $status = $conn->real_escape_string($_POST['status']);
        $source = $_POST['source'] ?? 'Admin Created';
        
        if (isset($_POST['budget_id']) && !empty($_POST['budget_id'])) {
            $id = intval($_POST['budget_id']);
            
            // Dynamics update bases on resource origin
            if ($source === 'Manager Assigned') {
                $stmt = $conn->prepare("UPDATE assignments SET allocated_budget=? WHERE id=?");
                $stmt->bind_param("di", $allocated, $id);
            } else {
                $stmt = $conn->prepare("UPDATE developer_budgets SET project_name=?, allocated_budget=?, status=? WHERE id=?");
                $stmt->bind_param("sdsi", $project_name, $allocated, $status, $id);
            }
        } else {
            // Insert New Record
            $stmt = $conn->prepare("INSERT INTO developer_budgets (project_name, allocated_budget, status) VALUES (?, ?, ?)");
            $stmt->bind_param("sds", $project_name, $allocated, $status);
        }
        
        if ($stmt->execute()) {
            $success_msg = "Allocated funds updated successfully!";
        } else {
            $error_msg = "Database Error: Failed to save record.";
        }
        $stmt->close();
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete_budget') {
        $id = intval($_POST['budget_id']);
        $stmt = $conn->prepare("DELETE FROM developer_budgets WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success_msg = "Budget metric removed.";
        }
        $stmt->close();
    }
}

// ACCURATE METRICS: Kinukuha ang sum ng real-time expenses ng manager
$metrics_query = "
    SELECT 
        (SELECT COALESCE(SUM(allocated_budget), 0) FROM developer_budgets) + 
        (SELECT COALESCE(SUM(allocated_budget), 0) FROM assignments) AS total_alloc,
        COALESCE((
            SELECT SUM(amount) FROM expenses 
            WHERE title IN (SELECT project_name FROM developer_budgets)
               OR title IN (SELECT task_name FROM assignments)
        ), 0) AS total_spent";

$metrics_res = $conn->query($metrics_query);
$metrics = $metrics_res->fetch_assoc();
$global_allocated = floatval($metrics['total_alloc'] ?? 0.00);
$global_spent = floatval($metrics['total_spent'] ?? 0.00);
$global_remaining = $global_allocated - $global_spent;

// ACCURATE BUDGET STREAM: Naka-LEFT JOIN sa expenses table para makuha ang live sum per project name
$budgets_query = "
    SELECT 
        b.id, 
        b.project_name, 
        b.allocated_budget, 
        b.status, 
        b.updated_at,
        COALESCE((SELECT SUM(amount) FROM expenses WHERE title = b.project_name), 0) AS spent_budget,
        'Admin Created' AS source
    FROM developer_budgets b
    
    UNION ALL
    
    SELECT 
        a.id, 
        a.task_name AS project_name, 
        a.allocated_budget, 
        'Active' AS status, 
        NOW() AS updated_at,
        COALESCE((SELECT SUM(amount) FROM expenses WHERE title = a.task_name), 0) AS spent_budget,
        'Manager Assigned' AS source
    FROM assignments a
    
    ORDER BY updated_at DESC";

$budgets_res = $conn->query($budgets_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Developer Budget Control</title>
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
        .stat-card { background-color: #ffffff; border: 1px solid var(--pms-border); border-radius: 12px; position: relative; }
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
                <li class="nav-item"><a href="admin_dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="nav-link"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="projects.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Documents</a></li>
                <li><a href="developer_budget.php" class="nav-link active-accent"><i class="fa-solid fa-code text-white"></i> Dev Budget</a></li>
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
            <h5 class="mb-0 text-secondary fw-semibold">Project Management System</h5>
            <span class="text-muted small">Authority: <strong class="text-danger"><?= htmlspecialchars($user_role); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Developer Software Budgets</h3>
                    <p class="text-muted mb-0">Track engineering resources, infrastructure pipeline financial limits, and dev costs.</p>
                </div>
                </div>

            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?= $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3 h-100">
                        <span class="text-muted small text-uppercase fw-bold">Total Allocated Dev Fund</span>
                        <h3 class="fw-bold text-primary mb-0 mt-1">₱<?= number_format($global_allocated, 2); ?></h3>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3 h-100">
                        <span class="text-muted small text-uppercase fw-bold">Total Dev Resources Spent</span>
                        <h3 class="fw-bold text-danger mb-0 mt-1">₱<?= number_format($global_spent, 2); ?></h3>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3 h-100 <?= ($global_remaining < 0) ? 'border-danger bg-danger-subtle' : ''; ?>">
                        <span class="text-muted small text-uppercase fw-bold">Available Safe-Margin Balance</span>
                        <h3 class="fw-bold mb-0 mt-1 <?= ($global_remaining < 0) ? 'text-danger' : 'text-success'; ?>">
                            ₱<?= number_format($global_remaining, 2); ?>
                        </h3>
                        <?php if($global_remaining < 0): ?>
                            <span class="position-absolute top-0 end-0 badge bg-danger m-2">Deficit Detected</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border mb-4" style="border-radius: 12px; background: white;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-secondary small text-uppercase font-monospace fw-bold">
                                <tr>
                                    <th class="ps-4">Project Scope Name</th>
                                    <th>Origin Source</th>
                                    <th>Allocated Funds</th>
                                    <th>Logged Expense</th>
                                    <th>Status Flag</th>
                                    <th class="pe-4 text-end">Action Interface</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($budgets_res && $budgets_res->num_rows > 0): ?>
                                    <?php while ($row = $budgets_res->fetch_assoc()): 
                                        $is_manager_project = ($row['source'] === 'Manager Assigned');
                                        $allocated_val = floatval($row['allocated_budget']);
                                        $spent_val = floatval($row['spent_budget']);
                                        
                                        // DYNAMIC OVERRUN EVALUATION
                                        $is_overrun = ($spent_val > $allocated_val);
                                        $display_status = $is_overrun ? 'Frozen (Overrun)' : $row['status'];
                                    ?>
                                        <tr class="<?= $is_overrun ? 'table-danger-subtle' : ''; ?>">
                                            <td class="ps-4 fw-semibold text-dark">
                                                <?= htmlspecialchars($row['project_name']); ?>
                                                <?php if($is_overrun): ?>
                                                    <span class="badge bg-danger ms-1 small font-monospace"><i class="fa-solid fa-triangle-exclamation"></i> OVERRUN</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $is_manager_project ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary'; ?>">
                                                    <?= $row['source']; ?>
                                                </span>
                                            </td>
                                            <td>₱<?= number_format($allocated_val, 2); ?></td>
                                            <td class="<?= $is_overrun ? 'text-danger fw-bold' : 'text-secondary'; ?>">
                                                ₱<?= number_format($spent_val, 2); ?>
                                            </td>
                                            <td>
                                                <?php if($is_overrun): ?>
                                                    <span class="badge rounded-pill px-2.5 py-1 bg-danger text-white animate-pulse">
                                                        <i class="fa-solid fa-snowflake me-1"></i> <?= htmlspecialchars($display_status); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill px-2.5 py-1 <?= $row['status'] === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'; ?>">
                                                        <?= htmlspecialchars($display_status); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editBudget(<?= htmlspecialchars(json_encode(array_merge($row, ['status' => $display_status]))); ?>)">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                
                                                <?php if (!$is_manager_project): ?>
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Drop this metric permanently?');">
                                                        <input type="hidden" name="action" value="delete_budget">
                                                        <input type="hidden" name="budget_id" value="<?= $row['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-receipt fs-2 mb-2 d-block text-secondary"></i>
                                            No active developer architectural budgets found in records.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div class="modal fade" id="budgetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalTitle">Manage Dev Project Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="budgetForm" method="POST" action="">
                    <input type="hidden" name="action" value="save_budget">
                    <input type="hidden" name="budget_id" id="budget_id" value="">
                    <input type="hidden" name="source" id="project_source" value="Admin Created">
                    
                    <div class="modal-body">
                        <div class="mb-3" id="nameContainer">
                            <label class="form-label text-secondary small fw-bold">Project / Architecture Domain Scope</label>
                            <input type="text" class="form-control" name="project_name" id="project_name" required placeholder="e.g., Cloud Sync Engine Upgrade">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Allocated Budget (PHP)</label>
                            <input type="number" step="0.01" class="form-control" name="allocated_budget" id="allocated_budget" required placeholder="0.00">
                        </div>
                        <div class="mb-3" id="statusContainer">
                            <label class="form-label text-secondary small fw-bold">Operational Status Flag</label>
                            <select class="form-select" name="status" id="status">
                                <option value="Active">Active Track</option>
                                <option value="Suspended">Suspended / Frozen</option>
                                <option value="Completed">Completed Phase</option>
                                <option value="Frozen (Overrun)" id="overrun_option" disabled>Frozen (Overrun)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Dismiss</button>
                        <button type="submit" class="btn btn-primary btn-sm">Commit Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let bModal = new bootstrap.Modal(document.getElementById('budgetModal'));

        function editBudget(data) {
            document.getElementById('budget_id').value = data.id;
            document.getElementById('project_name').value = data.project_name;
            document.getElementById('allocated_budget').value = data.allocated_budget;
            document.getElementById('project_source').value = data.source;
            
            if(data.status === 'Frozen (Overrun)') {
                document.getElementById('overrun_option').disabled = false;
            } else {
                document.getElementById('overrun_option').disabled = true;
            }
            document.getElementById('status').value = data.status;
            
            if(data.source === 'Manager Assigned' || data.status === 'Frozen (Overrun)') {
                document.getElementById('project_name').disabled = true;
                document.getElementById('status').disabled = true;
                document.getElementById('modalTitle').innerText = data.status === 'Frozen (Overrun)' ? 'Budget Overrun - Locked File' : 'Set Budget for Manager Project';
            } else {
                document.getElementById('project_name').disabled = false;
                document.getElementById('status').disabled = false;
                document.getElementById('modalTitle').innerText = 'Modify Dev System Target Allocation';
            }
            
            bModal.show();
        }
        
        document.getElementById('budgetForm').addEventListener('submit', function() {
            document.getElementById('project_name').disabled = false;
            document.getElementById('status').disabled = false;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>