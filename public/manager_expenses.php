<?php
session_start();
// Binago para sa Project Manager role mo
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    header("Location: login.php"); exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
$manager_id = $_SESSION['user_id'];

// I-check kung may connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update status sa bawat page load upang manatiling synchronized sa metrics
$conn->query("UPDATE users SET is_online = 1, last_active = NOW() WHERE id = '$manager_id'");

$user_name = $_SESSION['user_name'] ?? 'John Manager';
$user_role = $_SESSION['user_role'] ?? 'Project Manager';
$current_page = basename($_SERVER['PHP_SELF']);

// Handle Expense Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $task_name = $_POST['task_name']; // Gagamitin ang task_name bilang title sa expenses
    $category = $_POST['category'];   // Idinagdag para sa category column mo
    $amount = $_POST['amount'];

    // INAYOS DITO: Isinama ang user_id para ma-save kung sinong manager ang nag-add ng expense
    $stmt = $conn->prepare("INSERT INTO expenses (title, category, amount, user_id) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssdi", $task_name, $category, $amount, $manager_id);
        $stmt->execute();
        $stmt->close();
        
        // I-refresh ang page para pumasok agad ang data
        header("Location: manager_expenses.php");
        exit;
    } else {
        die("Prepare failed: " . $conn->error);
    }
}

// Fetch Assignments at i-JOIN sa Expenses gamit ang pangalan (task_name = title)
$query = "SELECT p.*, COALESCE(SUM(e.amount), 0) as total_expenses 
          FROM assignments p 
          LEFT JOIN expenses e ON p.task_name = e.title 
          GROUP BY p.id";

$projects = $conn->query($query);

if (!$projects) {
    die("Database Query Failed! Error description: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Project Expenses</title>
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
    </style>
</head>
<body>

    <!-- Sidebar Layout Integration -->
    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group fs-4" style="color: var(--pms-electric-blue);"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item">
                    <a href="manager_dashboard.php" class="nav-link">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="work_personnel.php" class="nav-link">
                        <i class="fa-solid fa-person-digging"></i> Work Personnel
                    </a>
                </li>
                <li>
                    <a href="site_development.php" class="nav-link">
                        <i class="fa-solid fa-trowel-bricks"></i> Site Development
                    </a>
                </li>
                <li>
                    <a href="vault_documents.php" class="nav-link">
                        <i class="fa-solid fa-folder-tree"></i> Vault Documents
                    </a>
                </li>
                <li>
                    <a href="assignments.php" class="nav-link">
                        <i class="fa-solid fa-sitemap"></i> Assignments
                    </a>
                </li>
                <li>
                    <a href="developer_submissions.php" class="nav-link">
                        <i class="fa-solid fa-file-import"></i> Developer Submissions
                    </a>
                </li>
                <li>
                    <a href="manager_expenses.php" class="nav-link active-accent">
                        <i class="fa-solid fa-receipt"></i> Project Expenses
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

    <!-- Main Content Wrapper to shift layout right past the sidebar -->
    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Project Expenses</h5>
            <span class="text-muted small">Logged in as: <strong><?= htmlspecialchars($user_name); ?></strong> (<?= htmlspecialchars($user_role); ?>)</span>
        </header>

        <main class="p-4 container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Project Expense Logging</h3>
                    <p class="text-muted mb-0">Track project milestones alongside their respective fiscal parameters.</p>
                </div>
            </div>

            <div class="row g-4">
                <!-- Form Card -->
                <div class="col-12 col-md-4">
                    <div class="card shadow-sm border p-4" style="border-radius: 12px; background: white;">
                        <h5 class="fw-bold mb-3 text-dark">Log New Expense</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Select Project / Task</label>
                                <select name="task_name" class="form-select" required>
                                    <option value="">-- Choose Project --</option>
                                    <?php 
                                    $projects->data_seek(0);
                                    while($p = $projects->fetch_assoc()): 
                                        $p_name = $p['task_name'] ?? 'Unnamed Task';
                                    ?>
                                        <option value="<?= htmlspecialchars($p_name); ?>"><?= htmlspecialchars($p_name); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Category</label>
                                <input type="text" name="category" class="form-control" placeholder="e.g., Software, Hardware, Marketing" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Amount (PHP)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                            </div>
                            <button type="submit" name="add_expense" class="btn btn-success w-100">Submit Expense</button>
                        </form>
                    </div>
                </div>

                <!-- Monitoring Table -->
                <div class="col-12 col-md-8">
                    <div class="card shadow-sm border" style="border-radius: 12px; background: white; overflow: hidden;">
                        <div class="card-header bg-transparent border-bottom p-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-receipt text-success me-2"></i>Expense Budget Monitoring</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Project Name</th>
                                            <th>Allocated Budget</th>
                                            <th>Logged Expense</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $projects->data_seek(0);
                                        while($p = $projects->fetch_assoc()): 
                                            $p_name = $p['task_name'] ?? 'Unnamed Task';
                                            $allocated_budget = $p['allocated_budget'] ?? 0;
                                            $remaining = $allocated_budget - $p['total_expenses'];
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($p_name); ?></td>
                                            <td class="text-primary fw-semibold">₱<?= number_format($allocated_budget, 2); ?></td>
                                            <td class="text-danger fw-semibold">₱<?= number_format($p['total_expenses'], 2); ?></td>
                                            <td>
                                                <?php if($remaining < 0): ?>
                                                    <span class="badge bg-danger">Over Budget</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Within Budget</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
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