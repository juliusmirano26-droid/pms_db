<?php
session_start();

// Access Control - Anyone logged in can access, but roles dictate visibility
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['user_role'] ?? 'Team Member';

// Handle Task Dispatch Action Setup (Only Admin or Project Manager can add tasks)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    if (!in_array($current_user_role, ['Admin', 'Project Manager'])) {
        die("Unauthorized access.");
    }

    $task_name = trim($_POST['task_name']);
    $developer_id = !empty($_POST['developer_id']) ? intval($_POST['developer_id']) : null;
    $deadline = $_POST['deadline'];
    $priority = $_POST['priority'];

    if (!empty($task_name) && !empty($deadline) && !empty($priority)) {
        $stmt = $conn->prepare("INSERT INTO assignments (task_name, developer_id, deadline, priority) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            die("SQL Prepare Error: " . $conn->error);
        }
        $stmt->bind_param("siss", $task_name, $developer_id, $deadline, $priority);
        $stmt->execute();
        $stmt->close();
        
        header("Location: assignments.php");
        exit;
    }
}

// Fetch all Team Members for dropdown options configuration (Only needed for Management)
$devs_res = $conn->query("SELECT id, name FROM users WHERE role = 'Team Member' ORDER BY name ASC");

// --- DYNAMIC VISIBILITY LAYER ---
// Restrict query parameters based on the authenticated session actor's role matrix
if (in_array($current_user_role, ['Admin', 'Project Manager'])) {
    // Management sees everything
    $query = "SELECT a.id, a.task_name, a.deadline, a.priority, u.name as developer_name 
              FROM assignments a 
              LEFT JOIN users u ON a.developer_id = u.id 
              ORDER BY a.id DESC";
    $stmt = $conn->prepare($query);
} else {
    // Developers/Team Members ONLY see their assigned rows
    $query = "SELECT a.id, a.task_name, a.deadline, a.priority, u.name as developer_name 
              FROM assignments a 
              LEFT JOIN users u ON a.developer_id = u.id 
              WHERE a.developer_id = ? 
              ORDER BY a.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $current_user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Operational Assignments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <?php 
                    // DYNAMIC REDIRECT LOGIC BASED ON ROLE
                    $dashboard_url = 'developer_dashboard.php'; // Default para sa Team Members / Developers
                    if ($current_user_role === 'Admin') {
                        $dashboard_url = 'admin_dashboard.php';
                    } elseif ($current_user_role === 'Project Manager') {
                        $dashboard_url = 'manager_dashboard.php';
                    }
                ?>
                <a href="<?= $dashboard_url; ?>" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                <h2 class="fw-bold text-dark"><i class="fa-solid fa-list-check text-warning me-2"></i>Operational Assignments Matrix Flow</h2>
            </div>
            
            <?php if (in_array($current_user_role, ['Admin', 'Project Manager'])): ?>
                <button class="btn btn-warning text-dark fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#dispatchTaskModal" style="background-color: #ffc107; border: none;">
                    <i class="fa-solid fa-thumbtack me-1"></i> Dispatch Milestone Task
                </button>
            <?php endif; ?>
        </div>

        <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 15%;">Task ID</th>
                                <th style="width: 35%;">Task Milestone Requirement</th>
                                <th style="width: 20%;">Assigned Developer</th>
                                <th style="width: 15%;">Target Deadline</th>
                                <th style="width: 15%;">Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-monospace">#TSK-<?= $row['id']; ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['task_name']); ?></td>
                                        <td>
                                            <i class="fa-regular fa-user text-secondary me-2"></i><?= htmlspecialchars($row['developer_name'] ?? 'Unassigned'); ?>
                                        </td>
                                        <td>
                                            <i class="fa-regular fa-calendar text-muted me-2"></i><?= date("M d, Y", strtotime($row['deadline'])); ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $badgeClass = 'bg-secondary-subtle text-secondary';
                                                if ($row['priority'] === 'High') {
                                                    $badgeClass = 'bg-danger-subtle text-danger';
                                                } elseif ($row['priority'] === 'Medium') {
                                                    $badgeClass = 'bg-warning-subtle text-warning';
                                                } elseif ($row['priority'] === 'Low') {
                                                    $badgeClass = 'bg-info-subtle text-info';
                                                }
                                            ?>
                                            <span class="badge px-3 py-2 rounded-pill <?= $badgeClass; ?>">
                                                <?= htmlspecialchars($row['priority']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-rectangle-list d-block fs-2 mb-2 text-secondary"></i>
                                        No tactical assignments mapped currently.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if (in_array($current_user_role, ['Admin', 'Project Manager'])): ?>
    <div class="modal fade" id="dispatchTaskModal" tabindex="-1" aria-labelledby="dispatchTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="dispatchTaskModalLabel">Dispatch New Milestone Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="assignments.php" method="POST">
                    <input type="hidden" name="action" value="add_task">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Task Milestone Requirement</label>
                            <input type="text" name="task_name" class="form-control" required placeholder="e.g. Optimize Database Indexes & Query Speeds">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Assign Developer</label>
                            <select name="developer_id" class="form-select">
                                <option value="">-- Select Team Member --</option>
                                <?php if ($devs_res && $devs_res->num_rows > 0): ?>
                                    <?php while($dev = $devs_res->fetch_assoc()): ?>
                                        <option value="<?= $dev['id']; ?>"><?= htmlspecialchars($dev['name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Target Deadline Chronology</label>
                            <input type="date" name="deadline" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Critical Priority Level</label>
                            <select name="priority" class="form-select" required>
                                <option value="Low">Low Priority</option>
                                <option value="Medium" selected>Medium Priority</option>
                                <option value="High">High Priority</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 text-dark fw-bold" style="background-color: #ffc107; border: none;">Dispatch Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>