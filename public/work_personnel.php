<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Project Manager' && $_SESSION['user_role'] !== 'Admin')) {
    header("Location: login.php"); exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_role = $_SESSION['user_role'] ?? 'Project Manager';
$current_page = basename($_SERVER['PHP_SELF']);
$msg = "";

// ================= PHP BACKEND: PAG-SAVE NG BAGONG PERSONNEL =================
if (isset($_POST['add_personnel'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']); 
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $daily_rate = floatval($_POST['daily_rate']);
    $assigned_project = mysqli_real_escape_string($conn, $_POST['assigned_project']);

    $check = $conn->query("SELECT id FROM onsite_personnel WHERE username='$username' OR contact_no='$contact_no'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-triangle-exclamation me-2'></i> The Username or Contact No. is already in use!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $insert_query = "INSERT INTO onsite_personnel (name, username, contact_no, role, daily_rate, assigned_project, status) 
                         VALUES ('$name', '$username', '$contact_no', '$role', '$daily_rate', '$assigned_project', 'Active')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class='fa-solid fa-circle-check me-2'></i> Successfully added $name!
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
        }
    }
}

// ================= PHP BACKEND: PAG-EDIT NG IMPORMASYON NG EMPLEYADO =================
if (isset($_POST['edit_personnel'])) {
    $personnel_id = intval($_POST['personnel_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $contact_no = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $daily_rate = floatval($_POST['daily_rate']);

    $update_info_query = "UPDATE onsite_personnel SET name='$name', contact_no='$contact_no', role='$role', daily_rate='$daily_rate' WHERE id=$personnel_id";
    if ($conn->query($update_info_query)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-user-check me-2'></i> Personnel profile details successfully updated!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    Error updating record: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// ================= PHP BACKEND: PAG-UPDATE NG PROJECT NG ISANG EMPLEYADO =================
if (isset($_POST['update_project'])) {
    $personnel_id = intval($_POST['personnel_id']);
    $new_project = mysqli_real_escape_string($conn, $_POST['new_project']);

    $update_query = "UPDATE onsite_personnel SET assigned_project='$new_project' WHERE id=$personnel_id";
    if ($conn->query($update_query)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                    <i class='fa-solid fa-building-circle-check me-2'></i> The new project has been successfully assigned!
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                    Error: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                </div>";
    }
}

// FETCH QUERY: Kukunin ang lahat ng active personnel data
$query_employees = "SELECT id, name, username, contact_no, role, daily_rate, assigned_project, status FROM onsite_personnel ORDER BY id DESC";
$employees_result = $conn->query($query_employees);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Work Personnel Directory</title>
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
        .section-title { font-size: 1.1rem; font-weight: 700; color: #475569; margin-top: 1.5rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 8px; }
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
                <li class="nav-item"><a href="manager_dashboard.php" class="nav-link <?= $current_page == 'manager_dashboard.php' ? 'active-accent' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="work_personnel.php" class="nav-link <?= $current_page == 'work_personnel.php' ? 'active-accent' : ''; ?>"><i class="fa-solid fa-person-digging"></i> Work Personnel</a></li>
                <li></li><a href="site_development.php" class="nav-link"><i class="fa-solid fa-trowel-bricks"></i> Site Development</a></li>
                <li><a href="vault_documents.php" class="nav-link <?= $current_page == 'vault_documents.php' ? 'active-accent' : ''; ?>"><i class="fa-solid fa-folder-tree"></i> Vault Documents</a></li>
                <li><a href="assignments.php" class="nav-link <?= $current_page == 'assignments.php' ? 'active-accent' : ''; ?>"><i class="fa-solid fa-sitemap"></i> Assignments</a></li>
                <li><a href="developer_submissions.php" class="nav-link <?= $current_page == 'developer_submissions.php' ? 'active-accent' : ''; ?>"><i class="fa-solid fa-file-import"></i> Developer Submissions</a></li>
                <li><a href="project_expenses.php" class="nav-link <?= $current_page == 'project_expenses.php' ? 'active-accent' : ''; ?>"><i class="fa-solid fa-receipt"></i> Project Expenses</a></li>
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
            <span class="text-muted small">Logged in as: <strong class="text-primary"><?= htmlspecialchars($_SESSION['username'] ?? 'Project Manager'); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <?= $msg; ?>

            <div class="mb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-person-digging text-primary me-2"></i>Work Personnel Directory</h3>
                    <p class="text-muted mb-0">Monitor onsite construction workers, tactical designations, and active personnel allocations.</p>
                </div>
                <button class="btn btn-primary d-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addPersonnelModal">
                    <i class="fa-solid fa-user-plus"></i> Add Personnel
                </button>
            </div>

            <div class="section-title">
                <i class="fa-solid fa-helmet-safety text-warning"></i> Onsite Employees & Field Crew
            </div>
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Full Name</th>
                                    <th>Designation / Trade</th>
                                    <th>Contact No.</th> 
                                    <th>Assigned Project</th>
                                    <th>Status</th>
                                    <th class="text-end">Daily Rate (Sahod)</th>
                                    <th class="text-end">Est. Weekly (6 Days)</th>
                                    <th class="text-center" style="width: 240px;">Actions</th>
                                    <th class="pe-4 text-center">User Identification</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($employees_result && $employees_result->num_rows > 0): ?>
                                    <?php while($row = $employees_result->fetch_assoc()): 
                                        $id = $row['id'];
                                        $fullname = $row['name'] ?? 'Unnamed Personnel';
                                        $role = $row['role'] ?? 'General Crew';
                                        $contact_no = $row['contact_no'] ?? 'No Contact No.'; 
                                        $project = $row['assigned_project'] ?? 'Unassigned';
                                        $status = strtolower($row['status']);
                                        $daily_rate = $row['daily_rate'];
                                        $badge_class = ($status === 'active' || $status === 'online') ? 'bg-success' : 'bg-secondary';
                                        $weekly_est = $daily_rate * 6; 
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($fullname); ?></td>
                                        <td>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2">
                                                <i class="fa-solid fa-hammer me-1"></i><?= htmlspecialchars($role); ?>
                                            </span>
                                        </td>
                                        <td class="text-secondary small fw-semibold"><?= htmlspecialchars($contact_no); ?></td>
                                        <td class="fw-semibold">
                                            <?php if ($project === 'Unassigned' || $project === 'General Pool (No Project)'): ?>
                                                <span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Unassigned</span>
                                            <?php else: ?>
                                                <span class="text-primary"><i class="fa-solid fa-building text-muted me-1"></i><?= htmlspecialchars($project); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?= $badge_class; ?> text-capitalize px-2"><?= htmlspecialchars($status); ?></span></td>
                                        <td class="text-end fw-semibold text-dark">₱<?= number_format($daily_rate, 2); ?></td>
                                        <td class="text-end fw-bold text-success">₱<?= number_format($weekly_est, 2); ?></td>
                                        
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-outline-secondary px-2 py-1" data-bs-toggle="modal" data-bs-target="#editPersonnelModal<?= $id; ?>">
                                                    <i class="fa-solid fa-user-pen me-1"></i>Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary px-2 py-1" data-bs-toggle="modal" data-bs-target="#assignProjectModal<?= $id; ?>">
                                                    <i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Assign
                                                </button>
                                            </div>
                                        </td>
                                        
                                        <td class="pe-4 text-center"><span class="badge bg-light text-muted border px-2">@<?= htmlspecialchars($row['username']); ?></span></td>
                                    </tr>

                                    <div class="modal fade" id="editPersonnelModal<?= $id; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow rounded-3">
                                                <div class="modal-header bg-secondary text-white py-3">
                                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Edit Profile: <?= htmlspecialchars($fullname); ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <input type="hidden" name="personnel_id" value="<?= $id; ?>">
                                                    <div class="modal-body p-4 text-start">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Full Name</label>
                                                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($fullname); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Contact No.</label>
                                                            <input type="text" name="contact_no" class="form-control" value="<?= htmlspecialchars($contact_no); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold text-secondary">Designation Role</label>
                                                            <select name="role" class="form-select" required>
                                                                <option value="Laborer" <?= $role == 'Laborer' ? 'selected' : ''; ?>>Laborer</option>
                                                                <option value="Mason" <?= $role == 'Mason' ? 'selected' : ''; ?>>Mason</option>
                                                                <option value="Carpenter" <?= $role == 'Carpenter' ? 'selected' : ''; ?>>Carpenter</option>
                                                                <option value="Foreman" <?= $role == 'Foreman' ? 'selected' : ''; ?>>Foreman</option>
                                                                <option value="Onsite Crew" <?= $role == 'Onsite Crew' ? 'selected' : ''; ?>>Onsite Crew</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-1">
                                                            <label class="form-label small fw-bold text-secondary">Daily Rate (₱)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text bg-light fw-bold">₱</span>
                                                                <input type="number" step="0.01" min="0" name="daily_rate" class="form-control fw-bold text-success" value="<?= $daily_rate; ?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer p-3 bg-light">
                                                        <button type="button" class="btn btn-secondary py-1 px-3 small" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="edit_personnel" class="btn btn-primary py-1 px-4 small fw-semibold shadow-sm">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="assignProjectModal<?= $id; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-sm">
                                            <div class="modal-content border-0 shadow rounded-3">
                                                <div class="modal-header bg-primary text-white py-2">
                                                    <h6 class="modal-title fw-bold">Assign Project to <?= htmlspecialchars($fullname); ?></h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <input type="hidden" name="personnel_id" value="<?= $id; ?>">
                                                    <div class="modal-body p-3">
                                                        <label class="form-label small small-text fw-bold text-secondary">Choose Project:</label>
                                                        <select name="new_project" class="form-select form-select-sm" required>
                                                            <option value="Project Core-Alpha" <?= $project == 'Project Core-Alpha' ? 'selected' : ''; ?>>Project Core-Alpha</option>
                                                            <option value="Project Delta-West" <?= $project == 'Project Delta-West' ? 'selected' : ''; ?>>Project Delta-West</option>
                                                            <option value="Project Eco-Park" <?= $project == 'Project Eco-Park' ? 'selected' : ''; ?>>Project Eco-Park</option>
                                                            <option value="General Pool (No Project)" <?= $project == 'General Pool (No Project)' ? 'selected' : ''; ?>>General Pool (No Project)</option>
                                                        </select>
                                                    </div>
                                                    <div class="modal-footer p-2 bg-light">
                                                        <button type="button" class="btn btn-xs btn-secondary py-1 px-2 small" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="update_project" class="btn btn-xs btn-primary py-1 px-2 small fw-semibold">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center p-5 text-muted">No records found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="addPersonnelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-dark text-white rounded-top-3">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2 text-primary"></i>Adding Onsite Table</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label fw-semibold text-secondary">Full Name</label><input type="text" name="name" class="form-control" placeholder="e.g. Juan Dela Cruz" required></div>
                        <div class="mb-3"><label class="form-label fw-semibold text-secondary">Username</label><input type="text" name="username" class="form-control" placeholder="e.g. juan_field" required></div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Contact No.</label>
                            <input type="text" name="contact_no" class="form-control" placeholder="e.g. 09123456789" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Designation / Trade Role</label>
                            <select name="role" class="form-select" required>
                                <option value="Laborer">Laborer</option>
                                <option value="Mason">Mason</option>
                                <option value="Carpenter">Carpenter</option>
                                <option value="Foreman">Foreman</option>
                                <option value="Onsite Crew">Onsite Crew</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Assigned Project</label>
                            <select name="assigned_project" class="form-select" required>
                                <option value="Project Core-Alpha">Project Core-Alpha</option>
                                <option value="Project Delta-West">Project Delta-West</option>
                                <option value="Project Eco-Park">Project Eco-Park</option>
                                <option value="General Pool (No Project)" selected>General Pool (No Project)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold text-secondary">Daily Rate (₱)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-dark">₱</span>
                                <input type="number" step="0.01" min="0" name="daily_rate" class="form-control fw-bold text-success" placeholder="650.00" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light rounded-bottom-3">
                        <button type="button" class="btn btn-secondary border-0 px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_personnel" class="btn btn-primary px-4 shadow-sm fw-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>