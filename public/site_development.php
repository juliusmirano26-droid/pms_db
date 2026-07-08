<?php
session_start();

// Strict Access Control: Project Manager only
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Project Manager') {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
        header("Location: admin_dashboard.php");
    } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Team Member') {
        header("Location: developer_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

// Database Connection
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

$user_name = $_SESSION['user_name'] ?? 'Project Manager';
$user_role = $_SESSION['user_role'] ?? 'Project Manager';
$current_page = basename($_SERVER['PHP_SELF']);

$msg = "";

// Handle Form Submission for Adding Material Cost
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_material'])) {
    $project_name = $conn->real_escape_string($_POST['project_name']);
    $material_name = $conn->real_escape_string($_POST['material_name']);
    $quantity = intval($_POST['quantity']);
    $unit_cost = floatval($_POST['unit_cost']);

    $insert_query = "INSERT INTO material_costs (project_name, material_name, quantity, unit_cost) 
                     VALUES ('$project_name', '$material_name', $quantity, $unit_cost)";
    
    if ($conn->query($insert_query)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-check me-2'></i>Material cost added successfully!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-exclamation me-2'></i>Error: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// Handle Form Submission for Editing Material Cost
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_material'])) {
    $id = intval($_POST['id']);
    $project_name = $conn->real_escape_string($_POST['project_name']);
    $material_name = $conn->real_escape_string($_POST['material_name']);
    $quantity = intval($_POST['quantity']);
    $unit_cost = floatval($_POST['unit_cost']);

    $update_query = "UPDATE material_costs SET 
                     project_name = '$project_name', 
                     material_name = '$material_name', 
                     quantity = $quantity, 
                     unit_cost = $unit_cost 
                     WHERE id = $id";
    
    if ($conn->query($update_query)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-check me-2'></i>Material log updated successfully!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-exclamation me-2'></i>Error updating log: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// Handle Record Deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_query = "DELETE FROM material_costs WHERE id = $delete_id";
    
    if ($conn->query($delete_query)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-check me-2'></i>Material log removed successfully!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-circle-exclamation me-2'></i>Error deleting log: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// Fetch dynamic active project options
$projects_query = "SELECT DISTINCT assigned_project FROM onsite_personnel WHERE assigned_project IS NOT NULL AND assigned_project != 'Bench / No Project'";
$projects_result = $conn->query($projects_query);
$project_options = [];
if ($projects_result) {
    while ($row = $projects_result->fetch_assoc()) {
        $project_options[] = $row['assigned_project'];
    }
}
if (empty($project_options)) {
    $project_options = ['Project Core-Alpha', 'Project Delta-West'];
}

// Fetch logs and grand expenses summary
$logs_result = $conn->query("SELECT * FROM material_costs ORDER BY date_added DESC");
$total_res = $conn->query("SELECT SUM(quantity * unit_cost) as grand_total FROM material_costs");
$grand_total = ($total_res) ? $total_res->fetch_assoc()['grand_total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Site Development</title>
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

<div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
    <div>
        <div class="p-4 d-flex align-items-center gap-2">
            <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
            <span class="fs-5 fw-bold text-white">ProjectMS</span>
        </div>
        <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
        <ul class="nav nav-pills flex-column mt-3">
            <li><a href="manager_dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a href="work_personnel.php" class="nav-link"><i class="fa-solid fa-person-digging"></i> Work Personnel</a></li>
             <li><a href="site_development.php" class="nav-link active-accent"><i class="fa-solid fa-trowel-bricks"></i> Site Development</a></li>
            <li><a href="vault_documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Vault Documents</a></li>
            <li><a href="assignments.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Assignments</a></li>
            <li><a href="developer_submissions.php" class="nav-link"><i class="fa-solid fa-file-import"></i> Developer Submissions</a></li>
            <li><a href="manager_expenses.php" class="nav-link"><i class="fa-solid fa-receipt"></i> Project Expenses</a></li>
        </ul>
    </div>
    <div>
        <hr class="mx-3" style="border-color: rgba(255,255,255,0.15);">
        <a href="logout.php" class="nav-link text-danger m-3 p-0 d-flex align-items-center gap-2" style="text-decoration: none;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <header class="top-navbar">
        <h5 class="mb-0 text-secondary fw-semibold">Site Development</h5>
        <span class="text-muted small">Logged in as: <strong><?= htmlspecialchars($user_name); ?></strong></span>
    </header>

    <main class="p-4 container-fluid">
        <?= $msg; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Construction Materials & Logistics</h3>
                <p class="text-muted mb-0">Track material deployment and structural costs across all field operations.</p>
            </div>
            <button class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#costModal">
                <i class="fa-solid fa-plus"></i> Record Material Cost
            </button>
        </div>

        <div class="row mb-4">
            <div class="col-12 col-md-4">
                <div class="card border shadow-sm p-3" style="border-radius: 12px; background: white;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small text-uppercase fw-bold">Total Material Outlay</span>
                            <h2 class="fw-bold text-success mb-0 mt-1">₱<?= number_format($grand_total, 2); ?></h2>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
            <div class="card-header bg-transparent border-bottom p-4">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-calculator text-secondary me-2"></i>Expense Ledger Summary</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Project Assignment</th>
                                <th>Material Item</th>
                                <th>Quantity</th>
                                <th>Unit Cost</th>
                                <th>Calculated Total</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                                <?php while ($log = $logs_result->fetch_assoc()): ?>
                                    <tr class="border-bottom">
                                        <td class="ps-4 py-3 fw-bold text-dark"><?= htmlspecialchars($log['project_name']); ?></td>
                                        <td class="text-secondary"><?= htmlspecialchars($log['material_name']); ?></td>
                                        <td><?= number_format($log['quantity']); ?></td>
                                        <td>₱<?= number_format($log['unit_cost'], 2); ?></td>
                                        <td class="fw-bold text-primary">₱<?= number_format($log['total_cost'], 2); ?></td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1 btn-edit" 
                                                    data-id="<?= $log['id']; ?>" 
                                                    data-project="<?= htmlspecialchars($log['project_name']); ?>"
                                                    data-material="<?= htmlspecialchars($log['material_name']); ?>"
                                                    data-qty="<?= $log['quantity']; ?>"
                                                    data-cost="<?= $log['unit_cost']; ?>">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </button>
                                            <a href="site_development.php?delete_id=<?= $log['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this log entry?');">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center p-4 text-muted"><i class="fa-solid fa-circle-info me-1"></i> No recorded construction material expenses tracked.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="costModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>Record Material Metrics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Project Assignment</label>
                        <select class="form-select" name="project_name" required>
                            <?php foreach ($project_options as $proj): ?>
                                <option value="<?= htmlspecialchars($proj); ?>"><?= htmlspecialchars($proj); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Material Description</label>
                        <input type="text" class="form-control" name="material_name" placeholder="e.g. Portland Cement Bags" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" name="quantity" min="1" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Unit Cost (₱)</label>
                            <input type="number" step="0.01" class="form-control" name="unit_cost" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_material" class="btn btn-primary px-4 fw-semibold">Save Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i>Modify Material Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Project Assignment</label>
                        <select class="form-select" name="project_name" id="edit-project" required>
                            <?php foreach ($project_options as $proj): ?>
                                <option value="<?= htmlspecialchars($proj); ?>"><?= htmlspecialchars($proj); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Material Description</label>
                        <input type="text" class="form-control" name="material_name" id="edit-material" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" class="form-control" name="quantity" id="edit-qty" min="1" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Unit Cost (₱)</label>
                            <input type="number" step="0.01" class="form-control" name="unit_cost" id="edit-cost" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_material" class="btn btn-warning text-dark px-4 fw-semibold">Update Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Grab elements and distribute row details cleanly inside edit fields 
    const editButtons = document.querySelectorAll('.btn-edit');
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit-id').value = this.dataset.id;
            document.getElementById('edit-project').value = this.dataset.project;
            document.getElementById('edit-material').value = this.dataset.material;
            document.getElementById('edit-qty').value = this.dataset.qty;
            document.getElementById('edit-cost').value = this.dataset.cost;
            editModal.show();
        });
    });
</script>
</body>
</html>
<?php $conn->close(); ?>