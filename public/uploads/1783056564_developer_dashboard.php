<?php
session_start();

// Selyadong Access Control: Team Member (Developer) lamang ang may karapatang mag-execute ng file na ito
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Team Member') {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') {
        header("Location: admin_dashboard.php");
    } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Project Manager') {
        header("Location: manager_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Developer';
$user_role = $_SESSION['user_role'] ?? 'Team Member';
$current_page = basename($_SERVER['PHP_SELF']);

// Database Connection gamit ang mysqli
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * OBJECT-ORIENTED DASHBOARD LAYER
 * Dedicated layout encapsulation para sa Developer/Team Member Component lamang upang maiwasan ang conflict.
 */
class DeveloperDashboardRenderer {
    private $userName;
    private $userRole;
    private $currentPage;
    private $conn;
    private $userId;

    public function __construct($userName, $userRole, $currentPage, $conn, $userId) {
        $this->userName = $userName;
        $this->userRole = $userRole;
        $this->currentPage = $currentPage;
        $this->conn = $conn;
        $this->userId = $userId;
    }

    // Pag-render ng Isolated Dark Navigation Component na may Electric Blue Accents para kay Developer
    public function renderSidebar() {
        echo '
        <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
            <div>
                <div class="p-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                    <span class="fs-5 fw-bold text-white">ProjectMS</span>
                </div>
                <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
                <ul class="nav nav-pills flex-column mt-3">
                    <li class="nav-item">
                        <a href="developer_dashboard.php" class="nav-link active-accent">
                            <i class="fa-solid fa-terminal"></i> Terminal Hub
                        </a>
                    </li>
                    <li>
                        <a href="my_assignments.php" class="nav-link">
                            <i class="fa-solid fa-list-check"></i> My Assignments
                        </a>
                    </li>
                    <li>
                        <a href="project_view.php" class="nav-link">
                            <i class="fa-solid fa-cubes"></i> Project View
                        </a>
                    </li>
                    <li>
                        <a href="shared_vault.php" class="nav-link">
                            <i class="fa-solid fa-file-code"></i> Shared Vault
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
        </div>';
    }

    // Live counter para sa sariling tasks ng developer (Active / Pending Review)
    public function getMyAssignmentsCount() {
        $count = 0;
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM assignments WHERE developer_id = ? AND (status IS NULL OR status = 'Pending Review' OR status = '')");
        if ($stmt) {
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $count = $res['total'];
            $stmt->close();
        }
        return $count;
    }

    // Live counter para sa bilang ng mga proyektong na-approve na (Successful)
    public function getMyProjectsCount() {
        $count = 0;
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM assignments WHERE developer_id = ? AND status = 'Successful'");
        if ($stmt) {
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $count = $res['total'];
            $stmt->close();
        }
        return $count;
    }

    // Live counter para sa kabuuang shared documents sa system vault
    public function getTotalVaultCount() {
        $count = 0;
        if ($res = $this->conn->query("SELECT COUNT(*) as total FROM documents")) {
            $count = $res->fetch_assoc()['total'];
        }
        return $count;
    }

    // Kuhanin ang huling 5 personal tasks ng developer kasama ang evaluation status column
    public function getRecentMyAssignments() {
        $tasks = [];
        $stmt = $this->conn->prepare("SELECT task_name, deadline, priority, status FROM assignments WHERE developer_id = ? ORDER BY id DESC LIMIT 5");
        if ($stmt) {
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
            $stmt->close();
        }
        return $tasks;
    }

    // Kuhanin ang huling 5 shared documents mula sa system
    public function getGlobalDocuments() {
        $docs = [];
        $result = $this->conn->query("SELECT document_name, file_size FROM documents ORDER BY id DESC LIMIT 5");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $docs[] = $row;
            }
        }
        return $docs;
    }
}

// Instantiate the renderer object with parameters
$renderer = new DeveloperDashboardRenderer($user_name, $user_role, $current_page, $conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Developer Hub</title>
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

        body {
            background-color: var(--pms-bg);
            color: #0f172a;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .top-navbar {
            background: #ffffff;
            height: 70px;
            border-bottom: 1px solid var(--pms-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .stat-card {
            background-color: #ffffff;
            border: 1px solid var(--pms-border);
            border-radius: 12px;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .custom-sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--pms-dark-slate);
            z-index: 100;
        }

        .custom-sidebar .nav-link {
            color: #94a3b8 !important;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .custom-sidebar .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        .custom-sidebar .nav-link.active-accent {
            color: #ffffff !important;
            background-color: var(--pms-electric-blue) !important;
        }
    </style>
</head>
<body>

    <?php $renderer->renderSidebar(); ?>

    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Sandbox Terminal Environment</h5>
            <span class="text-muted small">Developer Identity: <strong><?= htmlspecialchars($user_name); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Developer Production Workspace</h3>
                <p class="text-muted mb-0">Track your personal milestone queues, access source configuration blueprints, and check platform alerts.</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">My Active Tasks</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $renderer->getMyAssignmentsCount(); ?></h2>
                            </div>
                            <div class="bg-warning-subtle text-warning rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Committed Tasks</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $renderer->getMyProjectsCount(); ?></h2>
                            </div>
                            <div class="bg-primary-subtle text-primary rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-code-commit"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Shared Artifacts</span>
                                <h2 class="fw-bold text-dark mb-0 mt-1"><?= $renderer->getTotalVaultCount(); ?></h2>
                            </div>
                            <div class="bg-success-subtle text-success rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fa-solid fa-box-archive"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="card border shadow-sm mb-4" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-thumbtack text-warning me-2"></i>My Active Milestone Targets</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Assigned Task Requirement</th>
                                            <th>Target Deadline</th>
                                            <th>Priority</th>
                                            <th>Current Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $my_tasks = $renderer->getRecentMyAssignments();
                                        if (!empty($my_tasks)): foreach ($my_tasks as $task): 
                                        ?>
                                            <tr>
                                                <td class="ps-3 fw-semibold text-dark"><?= htmlspecialchars($task['task_name']); ?></td>
                                                <td class="text-muted"><i class="fa-regular fa-clock me-1"></i> <?= htmlspecialchars($task['deadline']); ?></td>
                                                <td>
                                                    <span class="badge <?= $task['priority'] === 'High' ? 'bg-danger' : ($task['priority'] === 'Medium' ? 'bg-warning text-dark' : 'bg-info text-white') ?>">
                                                        <?= htmlspecialchars($task['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $task_status = $task['status'] ?? 'Pending Review';
                                                    $badge_status_class = 'bg-secondary';
                                                    if ($task_status === 'Successful') $badge_status_class = 'bg-success';
                                                    if ($task_status === 'Rejected') $badge_status_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?= $badge_status_class; ?> rounded px-2 py-1">
                                                        <?= htmlspecialchars($task_status); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">You have no pending task assignments allocated in the system database.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-shield text-success me-2"></i>Repository Shared Blueprints</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Asset Spec File</th>
                                            <th>Memory Size</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $vault_docs = $renderer->getGlobalDocuments();
                                        if (!empty($vault_docs)): foreach ($vault_docs as $doc): 
                                        ?>
                                            <tr>
                                                <td class="ps-3 text-dark font-monospace"><i class="fa-regular fa-file-code text-success me-1"></i><?= htmlspecialchars($doc['document_name']); ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($doc['file_size']); ?></span></td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr><td colspan="2" class="text-center py-4 text-muted">No configuration blueprints shared inside vault channels.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
                        <div class="card-header bg-transparent border-bottom p-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-bell text-danger me-2"></i>System Diagnostic Logs</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                                <div class="text-primary fs-5 mt-1"><i class="fa-solid fa-circle-info"></i></div>
                                <div>
                                    <p class="mb-1 text-dark small fw-semibold">Security patches successfully configured across clusters</p>
                                    <span class="text-muted" style="font-size: 0.75rem;">10 minutes ago</span>
                                </div>
                            </div>
                            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                                <div class="text-success fs-5 mt-1"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                <div>
                                    <p class="mb-1 text-dark small fw-semibold">Database schema deployment completed by Admin</p>
                                    <span class="text-muted" style="font-size: 0.75rem;">2 hours ago</span>
                                </div>
                            </div>
                            <div class="d-flex gap-3">
                                <div class="text-warning fs-5 mt-1"><i class="fa-solid fa-triangle-exclamation"></i></div>
                                <div>
                                    <p class="mb-1 text-dark small fw-semibold">Sprint review deadline approaching in 2 days</p>
                                    <span class="text-muted" style="font-size: 0.75rem;">Yesterday</span>
                                </div>
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