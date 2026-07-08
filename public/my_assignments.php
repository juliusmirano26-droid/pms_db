<?php
session_start();

// Selyadong Access Control: Team Member (Developer) o authorized roles ang pwedeng pumasok
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Developer';
$user_role = $_SESSION['user_role'] ?? 'Team Member';
$current_page = basename($_SERVER['PHP_SELF']);

// Database Connection
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==========================================
// ASYNCHRONOUS FILE UPLOAD PROCESSING ENGINE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['task_file'])) {
    header('Content-Type: application/json');
    
    $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    
    if ($task_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Task Association Key.']);
        exit;
    }

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_basename = basename($_FILES['task_file']['name']);
    $file_ext = strtolower(pathinfo($file_basename, PATHINFO_EXTENSION));
    
    // UPDATED: Added programming and web source formats to the allowed list
    $allowed_extensions = ['zip', 'rar', 'pdf', 'docx', 'png', 'jpg', 'jpeg', 'txt', 'php', 'html', 'css', 'js', 'sql'];
    if (!in_array($file_ext, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Extension file type forbidden. Allowed files: ' . implode(', ', $allowed_extensions)]);
        exit;
    }

    $new_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_basename);
    $target_filepath = $upload_dir . $new_filename;

    if (move_uploaded_file($_FILES['task_file']['tmp_name'], $target_filepath)) {
        // Update the file_path column for the specific assignment record
        $update_stmt = $conn->prepare("UPDATE assignments SET file_path = ? WHERE id = ?");
        $update_stmt->bind_param("si", $target_filepath, $task_id);
        
        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Submission saved successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database persistence failure.']);
        }
        $update_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move system temporary package to destination folder.']);
    }
    exit;
}

// Handle Task Dispatch Action Setup (Para sa Admin o PM kung sakali)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    if (!in_array($user_role, ['Admin', 'Project Manager'])) {
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
        
        header("Location: my_assignments.php");
        exit;
    }
}

// Fetch all Team Members para sa dropdown modal ng management
$devs_res = $conn->query("SELECT id, name FROM users WHERE role = 'Team Member' ORDER BY name ASC");

// --- DYNAMIC DATA VISIBILITY ---
// MODIFIED: Added a.file_path to select state tracking
if (in_array($user_role, ['Admin', 'Project Manager'])) {
    $query = "SELECT a.id, a.task_name, a.deadline, a.priority, a.file_path, u.name as developer_name 
              FROM assignments a 
              LEFT JOIN users u ON a.developer_id = u.id 
              ORDER BY a.id DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT a.id, a.task_name, a.deadline, a.priority, a.file_path, u.name as developer_name 
              FROM assignments a 
              LEFT JOIN users u ON a.developer_id = u.id 
              WHERE a.developer_id = ? 
              ORDER BY a.id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - My Assignments</title>
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

    <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
        <div>
            <div class="p-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                <span class="fs-5 fw-bold text-white">ProjectMS</span>
            </div>
            <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
            <ul class="nav nav-pills flex-column mt-3">
                <li class="nav-item">
                    <a href="developer_dashboard.php" class="nav-link">
                        <i class="fa-solid fa-terminal"></i> Terminal Hub
                    </a>
                </li>
                <li>
                    <a href="my_assignments.php" class="nav-link active-accent">
                        <i class="fa-solid fa-list-check"></i> My Assignments
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
    </div>

    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Developer Dashboard</h5>
            <span class="text-muted small">Developer Identity: <strong><?= htmlspecialchars($user_name); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Developer Workspace</h3>
                <p class="text-muted mb-0">Track your tasks, access project files, and view system information.</p>
            </div>

            <div class="card border shadow-sm mb-4" style="border-radius: 12px; background: white;">
                <div class="card-header bg-transparent border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check text-warning me-2"></i>Your Active Milestone Flow Tasks</h6>
                    
                    <?php if (in_array($user_role, ['Admin', 'Project Manager'])): ?>
                        <button class="btn btn-warning btn-sm text-dark fw-bold rounded px-3" data-bs-toggle="modal" data-bs-target="#dispatchTaskModal" style="background-color: #ffc107; border: none;">
                            <i class="fa-solid fa-thumbtack me-1"></i> Dispatch Task
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 10%;">Task ID</th>
                                    <th style="width: 25%;">Task Milestone Requirement</th>
                                    <th style="width: 20%;">Assigned Developer</th>
                                    <th style="width: 15%;">Target Deadline</th>
                                    <th style="width: 10%;">Priority</th>
                                    <th class="text-center" style="width: 20%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-3 text-muted font-monospace">#TSK-<?= $row['id']; ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($row['task_name']); ?></td>
                                            <td class="text-secondary">
                                                <i class="fa-regular fa-user me-1"></i> <?= htmlspecialchars($row['developer_name'] ?? 'Unassigned'); ?>
                                            </td>
                                            <td class="text-muted">
                                                <i class="fa-regular fa-clock me-1"></i> <?= htmlspecialchars($row['deadline']); ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $row['priority'] === 'High' ? 'bg-danger' : ($row['priority'] === 'Medium' ? 'bg-warning text-dark' : 'bg-info text-white') ?>">
                                                    <?= htmlspecialchars($row['priority']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($row['file_path'])): ?>
                                                    <span class="badge bg-success py-2 px-3 rounded-pill" style="font-size: 0.75rem;">
                                                        <i class="fa-solid fa-circle-check me-1"></i> Submitted
                                                    </span>
                                                <?php else: ?>
                                                    <form class="upload-task-form" enctype="multipart/form-data" style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                                        <input type="hidden" name="task_id" value="<?= $row['id']; ?>">
                                                        
                                                        <label class="btn btn-sm btn-outline-secondary mb-0 py-1" style="cursor: pointer; font-size: 0.75rem;">
                                                            <i class="fa-solid fa-paperclip"></i> Select
                                                            <input type="file" name="task_file" class="task-file-input" style="display: none;" onchange="updateFileName(this)">
                                                        </label>
                                                        
                                                        <span class="file-name-text text-muted small px-1" style="max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.75rem;"></span>

                                                        <button type="submit" class="btn btn-sm btn-primary upload-submit-btn py-1" style="font-size: 0.75rem;" disabled>
                                                            <i class="fa-solid fa-arrow-up-from-bracket"></i> Upload
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">You have no pending task assignments allocated in the system database.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php if (in_array($user_role, ['Admin', 'Project Manager'])): ?>
    <div class="modal fade" id="dispatchTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Dispatch New Milestone Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="my_assignments.php" method="POST">
                    <input type="hidden" name="action" value="add_task">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Task Milestone Requirement</label>
                            <input type="text" name="task_name" class="form-control" required placeholder="e.g. Optimize Database Indexes">
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
                            <label class="form-label small fw-semibold">Target Deadline</label>
                            <input type="date" name="deadline" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Priority Level</label>
                            <select name="priority" class="form-select" required>
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold">Dispatch Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateFileName(input) {
            const form = input.closest('form');
            const fileNameSpan = form.querySelector('.file-name-text');
            const submitBtn = form.querySelector('.upload-submit-btn');
            
            if (input.files.length > 0) {
                fileNameSpan.textContent = input.files[0].name;
                submitBtn.removeAttribute('disabled');
            } else {
                fileNameSpan.textContent = '';
                submitBtn.setAttribute('disabled', 'true');
            }
        }

        document.querySelectorAll('.upload-task-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('.upload-submit-btn');
                
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                submitBtn.setAttribute('disabled', 'true');

                fetch('my_assignments.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Task file uploaded successfully!');
                        location.reload(); 
                    } else {
                        alert('Upload failed: ' + data.message);
                        submitBtn.innerHTML = '<i class="fa-solid fa-arrow-up-from-bracket"></i> Upload';
                        submitBtn.removeAttribute('disabled');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    submitBtn.innerHTML = '<i class="fa-solid fa-arrow-up-from-bracket"></i> Upload';
                    submitBtn.removeAttribute('disabled');
                });
            });
        });
    </script>
</body>
</html>
<?php 
$stmt->close();
$conn->close(); 
?>