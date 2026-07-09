<?php
session_start();

// Strict Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Database Connection Hook
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

$user_role = $_SESSION['user_role'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);

// Create/Update sponsors table structure automatically if it doesn't exist
$table_init = "CREATE TABLE IF NOT EXISTS sponsors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sponsor_name VARCHAR(255) NOT NULL,
    contact_no VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    allocated_budget DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_init);

// Handle Delete Sponsor
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // Kunin muna ang pangalan ng sponsor bago burahin para malinis din ang maps
    $sp_res = $conn->query("SELECT sponsor_name FROM sponsors WHERE id = $delete_id");
    if ($sp_res && $sp_row = $sp_res->fetch_assoc()) {
        $sp_name = mysqli_real_escape_string($conn, $sp_row['sponsor_name']);
        $conn->query("DELETE FROM project_sponsors_map WHERE sponsor_name = '$sp_name'");
    }
    
    $conn->query("DELETE FROM sponsors WHERE id = $delete_id");
    header("Location: sponsors.php");
    exit();
}

// Handle Form Submission (Add Sponsor with Budget Capital)
$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_sponsor'])) {
    $s_name = mysqli_real_escape_string($conn, trim($_POST['sponsor_name']));
    $s_contact = mysqli_real_escape_string($conn, trim($_POST['contact_no']));
    $s_email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $s_budget = floatval($_POST['allocated_budget'] ?? 0.00);

    if (!empty($s_name)) {
        // Check duplicate entry
        $check = $conn->query("SELECT id FROM sponsors WHERE sponsor_name = '$s_name'");
        if ($check && $check->num_rows > 0) {
            $msg = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                        <i class='fa-solid fa-triangle-exclamation me-2'></i> Ang sponsor na iyan ay rehistrado na sa system.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
        } else {
            $insert_query = "INSERT INTO sponsors (sponsor_name, contact_no, email, allocated_budget) 
                             VALUES ('$s_name', '$s_contact', '$s_email', '$s_budget')";
            if ($conn->query($insert_query)) {
                $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <i class='fa-solid fa-circle-check me-2'></i> Sponsor successfully registered with an initial capital funding of <strong>₱" . number_format($s_budget, 2) . "</strong>!
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            Error: " . $conn->error . "
                            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                        </div>";
            }
        }
    }
}

// Get Dynamic Operational KPI Data Metrics
$total_sponsors_count = 0;
$grand_pooled_capital = 0.00;

$stats_res = $conn->query("SELECT COUNT(id) as total_sp, SUM(IFNULL(allocated_budget, 0.00)) as total_cap FROM sponsors");
if ($stats_res && $s_row = $stats_res->fetch_assoc()) {
    $total_sponsors_count = intval($s_row['total_sp'] ?? 0);
    $grand_pooled_capital = floatval($s_row['total_cap'] ?? 0.00);
}

// Fetch all registered elements
$sponsors_list = $conn->query("SELECT * FROM sponsors ORDER BY sponsor_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProjectMS - Corporate Sponsors Directory</title>
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
                <li><a href="projects.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Documents</a></li>
                <li><a href="developer_budget.php" class="nav-link"><i class="fa-solid fa-code text-info"></i> Dev Budget</a></li>
                <li><a href="crew_budget_control.php" class="nav-link"><i class="fa-solid fa-helmet-safety text-warning"></i> Crew Budget</a></li>
                <li><a href="sponsors.php" class="nav-link active-accent"><i class="fa-solid fa-hand-holding-dollar text-success"></i> Sponsors</a></li>
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
                <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-hand-holding-dollar text-success me-2"></i>Corporate Sponsors Master List</h3>
                <p class="text-muted mb-0">Manage project partners, contract communication lines, and structural investment portfolio budget sizes.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card stat-card p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small fw-semibold text-uppercase">Total Registered Partners</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= number_format($total_sponsors_count); ?></h2>
                            </div>
                            <div class="p-3 bg-primary-subtle text-primary rounded-circle"><i class="fa-solid fa-building fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card p-3 bg-success-subtle border border-success-subtle">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-success small fw-bold text-uppercase">Total Capital Funding Pooled</span>
                                <h2 class="fw-bold text-success mb-0 mt-1">₱<?= number_format($grand_pooled_capital, 2); ?></h2>
                            </div>
                            <div class="p-3 bg-success text-white rounded-circle"><i class="fa-solid fa-wallet fs-4"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card stat-card bg-white p-4">
                        <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-plus-circle text-success me-2"></i>Register New Sponsor</h5>
                        <form action="sponsors.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Sponsor / Corporate Entity Name</label>
                                <input type="text" name="sponsor_name" class="form-control" placeholder="e.g. Nexus Logistics Corp" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Initial Budget Asset (Capital Funding)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">₱</span>
                                    <input type="number" step="0.01" min="0" name="allocated_budget" class="form-control text-end fw-bold text-success" placeholder="0.00" value="0.00">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">Contact Number</label>
                                <input type="text" name="contact_no" class="form-control" placeholder="e.g. +63 917 123 4567">
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary">Corporate Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="e.g. funding@nexus.com">
                            </div>
                            <button type="submit" name="add_sponsor" class="btn btn-success w-100 fw-semibold py-2">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Save Sponsor Profile
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card stat-card bg-white border-0">
                        <div class="card-header bg-transparent border-0 pt-4 ps-4 pb-0">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-address-book text-muted me-2"></i>Active Corporate Entities Table</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table align-middle border-0 mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="py-3">Sponsor Entity</th>
                                            <th class="py-3 text-end">Total Capital Budget</th>
                                            <th class="py-3">Contact No.</th>
                                            <th class="py-3">Email Address</th>
                                            <th class="py-3 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($sponsors_list && $sponsors_list->num_rows > 0): ?>
                                            <?php while ($row = $sponsors_list->fetch_assoc()): 
                                                $s_budget_val = floatval($row['allocated_budget'] ?? 0.00);
                                            ?>
                                                <tr class="border-bottom">
                                                    <td class="fw-bold text-dark">
                                                        <i class="fa-solid fa-building-shield text-secondary me-2"></i>
                                                        <?= htmlspecialchars($row['sponsor_name']); ?>
                                                    </td>
                                                    <td class="text-end fw-bold text-success">
                                                        ₱<?= number_format($s_budget_val, 2); ?>
                                                    </td>
                                                    <td class="text-secondary small fw-medium"><?= htmlspecialchars($row['contact_no'] ?: 'N/A'); ?></td>
                                                    <td class="text-secondary small fw-medium"><?= htmlspecialchars($row['email'] ?: 'N/A'); ?></td>
                                                    <td class="text-center">
                                                        <a href="sponsors.php?delete=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger px-2 py-1" onclick="return confirm('Sigurado ka ba na gusto mong burahin ang sponsor profile na ito?');">
                                                            <i class="fa-solid fa-trash-can me-1"></i>Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">No corporate sponsors registered in the database yet.</td>
                                            </tr>
                                        <?php endif; ?>
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