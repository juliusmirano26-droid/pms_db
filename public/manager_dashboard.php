<?php
session_start();

// Strict Access Control: Project Manager lamang ang may karapatang mag-execute ng file na ito
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

// Database Connection Hook
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

// Update status sa bawat page load
$uid = $_SESSION['user_id'];
$conn->query("UPDATE users SET is_online = 1, last_active = NOW() WHERE id = '$uid'");

$user_name = $_SESSION['user_name'] ?? 'John Manager';
$user_role = $_SESSION['user_role'] ?? 'Project Manager';
$current_page = basename($_SERVER['PHP_SELF']);

// ================= LIVE DATABASE METRICS AGGREGATION =================

// 1. DYNAMIC MATRIX CONTEXT: Counts live rows within your assignments matrix flow
$assignments_count_query = "SELECT COUNT(*) as total_assignments FROM assignments";
$assignments_res = $conn->query($assignments_count_query);
$total_assignments = ($assignments_res) ? $assignments_res->fetch_assoc()['total_assignments'] : 0;

// 2. Bilang ng Lahat ng Dokumento (Graceful fallback kung wala pang documents table)
$doc_count_query = "SELECT COUNT(*) as total_docs FROM documents";
$doc_res = $conn->query($doc_count_query);
$total_docs = ($doc_res) ? $doc_res->fetch_assoc()['total_docs'] : 0;

// 3. Bilang ng mga Aktibong Team Members / Developers sa System
$team_count_query = "SELECT COUNT(*) as total_team FROM users WHERE role = 'Team Member'";
$team_res = $conn->query($team_count_query);
$total_team = ($team_res) ? $team_res->fetch_assoc()['total_team'] : 0;

// 4. Kuhanin ang listahan ng mga Team Members at kanilang Status
$team_list_query = "SELECT name, username, email, is_online FROM users WHERE role = 'Team Member' ORDER BY is_online DESC, name ASC LIMIT 5";
$team_list_result = $conn->query($team_list_query);

// 5. Kuhanin ang mga Submissions (Para sa bagong section)
$submission_query = "SELECT a.id, a.task_name, u.name as dev_name, a.status FROM assignments a JOIN users u ON a.developer_id = u.id WHERE a.file_path IS NOT NULL LIMIT 5";
$submission_result = $conn->query($submission_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --pms-dark-slate: #1e293b; --pms-electric-blue: #3b82f6; --pms-bg: #f8fafc; --pms-border: #e2e8f0; }
        body { background-color: var(--pms-bg); color: #0f172a; overflow-x: hidden; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #ffffff; height: 70px; border-bottom: 1px solid var(--pms-border); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .stat-card { background-color: #ffffff; border: 1px solid var(--pms-border); border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
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
                <i class="fa-solid fa-layer-group fs-4" style="color: var(--pms-electric-blue);"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item">
                    <a href="manager_dashboard.php" class="nav-link active-accent">
                        <i class="fa-solid fa-gauge-high"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="vault_documents.php" class="nav-link">
                        <i class="fa-solid fa-file-shield"></i> Vault Documents
                    </a>
                </li>
                <li>
                    <a href="assignments.php" class="nav-link">
                        <i class="fa-solid fa-list-check"></i> Assignments
                    </a>
                </li>
                <li>
                    <a href="uploaded_submissions.php" class="nav-link">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Developer Submissions
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
            <h5 class="mb-0 text-secondary fw-semibold">Dashboard</h5>
            <span class="text-muted small">Logged in as: <strong><?= htmlspecialchars($user_name); ?></strong> (<?= htmlspecialchars($user_role); ?>)</span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Project Workspace</h3>
                <p class="text-muted mb-0">Monitor team activities, access project files, and track team progress.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Assign Tasks</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_assignments; ?></h2>
                            </div>
                            <div class="bg-primary-subtle text-primary rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">Active operational tasks dispatched</div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Vault Documents</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_docs; ?></h2>
                            </div>
                            <div class="bg-success-subtle text-success rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">Stored system configuration specifications</div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3 h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Allocated Developers</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $total_team; ?></h2>
                            </div>
                            <div class="bg-info-subtle text-info rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-laptop-code"></i>
                            </div>
                        </div>
                        <div class="mt-2 small text-muted">Active developers assigned to roles</div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Team Directory -->
                <div class="col-12 col-lg-6">
                    <div class="card border shadow-sm h-100" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-address-book text-primary me-2"></i>Assigned Team Directory</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Full Name</th>
                                            <th>Status</th>
                                            <th>Username</th>
                                            <th>Email Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($team_list_result && $team_list_result->num_rows > 0): ?>
                                            <?php while ($team = $team_list_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($team['name']); ?></td>
                                                    <td>
                                                        <span class="badge <?= $team['is_online'] ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $team['is_online'] ? 'Online' : 'Offline' ?>
                                                        </span>
                                                    </td>
                                                    <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= htmlspecialchars($team['username']); ?></span></td>
                                                    <td class="text-muted"><?= htmlspecialchars($team['email'] ?? 'No Email Set'); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Developer Submissions Section -->
                <div class="col-12 col-lg-6">
                    <div class="card border shadow-sm h-100" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-4">
                            <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-arrow-up-right-from-square text-info me-2"></i>Developer Submissions</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Task</th>
                                            <th>Developer</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($submission_result && $submission_result->num_rows > 0): ?>
                                            <?php while ($sub = $submission_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 text-dark"><?= htmlspecialchars($sub['task_name']); ?></td>
                                                    <td><?= htmlspecialchars($sub['dev_name']); ?></td>
                                                    <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($sub['status']); ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center p-3 text-muted">No submissions yet.</td></tr>
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