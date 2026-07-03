<?php
// public/tasks.php
session_start();

// Siguraduhing logged in ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'] ?? 'Team Member';
$user_name = $_SESSION['user_name'] ?? 'User';

// =========================================================================
// ROLE FILTERING LOGIC (MOCK DATA - Papalitan ng SQL Query sa dulo)
// =========================================================================
if ($user_role === 'Admin') {
    // Nakikita ng Admin ang LAHAT ng tasks sa buong system
    $displayTasks = [
        ['id' => 101, 'project' => 'E-Commerce App Redesign', 'title' => 'Integrate Stripe Payment Gateway', 'assignee' => 'Alex Developer', 'priority' => 'High', 'priority_class' => 'bg-danger-subtle text-danger', 'status' => 'In Progress', 'status_class' => 'bg-success-subtle text-success', 'due_date' => 'July 05, 2026'],
        ['id' => 102, 'project' => 'Database Schema Optimization', 'title' => 'Refactor Index Lookups on Transaction Logs', 'assignee' => 'Sarah Jenkins', 'priority' => 'Medium', 'priority_class' => 'bg-warning-subtle text-warning', 'status' => 'Review', 'status_class' => 'bg-warning-subtle text-warning', 'due_date' => 'July 02, 2026'],
        ['id' => 103, 'project' => 'E-Commerce App Redesign', 'title' => 'Create CSS Responsive Navigation Breakpoints', 'assignee' => 'Alex Developer', 'priority' => 'Low', 'priority_class' => 'bg-info-subtle text-info', 'status' => 'ToDo', 'status_class' => 'bg-secondary-subtle text-secondary', 'due_date' => 'July 12, 2026']
    ];
} elseif ($user_role === 'Project Manager') {
    // Nakikita ng Manager ang tasks sa mga hawak niyang proyekto lamang
    $displayTasks = [
        ['id' => 101, 'project' => 'E-Commerce App Redesign', 'title' => 'Integrate Stripe Payment Gateway', 'assignee' => 'Alex Developer', 'priority' => 'High', 'priority_class' => 'bg-danger-subtle text-danger', 'status' => 'In Progress', 'status_class' => 'bg-success-subtle text-success', 'due_date' => 'July 05, 2026'],
        ['id' => 103, 'project' => 'E-Commerce App Redesign', 'title' => 'Create CSS Responsive Navigation Breakpoints', 'assignee' => 'Alex Developer', 'priority' => 'Low', 'priority_class' => 'bg-info-subtle text-info', 'status' => 'ToDo', 'status_class' => 'bg-secondary-subtle text-secondary', 'due_date' => 'July 12, 2026']
    ];
} else {
    // Team Member / Developer: Nakikita LANG ang tasks na naka-assign partikular sa kanya
    $displayTasks = [
        ['id' => 101, 'project' => 'E-Commerce App Redesign', 'title' => 'Integrate Stripe Payment Gateway', 'assignee' => 'Para sa Iyo', 'priority' => 'High', 'priority_class' => 'bg-danger-subtle text-danger', 'status' => 'In Progress', 'status_class' => 'bg-success-subtle text-success', 'due_date' => 'July 05, 2026']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Tasks Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --primary-color: #4e73df; --secondary-bg: #f8f9fc; --dark-sidebar: #1e293b; }
        body { background-color: var(--secondary-bg); font-family: 'Segoe UI', Roboto, sans-serif; overflow-x: hidden; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #fff; height: 70px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .task-card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Task Workspace</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: 600;">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div>
                    <div class="fw-semibold text-dark fs-7" style="line-height: 1.2;"><?php echo htmlspecialchars($user_name); ?></div>
                    <span class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($user_role); ?></span>
                </div>
            </div>
        </header>

        <main class="p-4 container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <?php 
                        if ($user_role === 'Admin') echo "Global Task Registry (Admin View)";
                        elseif ($user_role === 'Project Manager') echo "Assign & Oversee Tasks";
                        else echo "My Assigned Tasks";
                        ?>
                    </h3>
                    <p class="text-muted mb-0">
                        <?php 
                        if ($user_role === 'Admin') echo "Pinamamahalaan ang pangkalahatang listahan ng mga micro-milestones.";
                        elseif ($user_role === 'Project Manager') echo "Magtalaga ng bagong micro-milestones para sa mga software developers at engineers.";
                        else echo "Listahan ng mga task na kailangan mong tapusin at i-update.";
                        ?>
                    </p>
                </div>
                
                <?php if ($user_role === 'Admin' || $user_role === 'Project Manager'): ?>
                    <button class="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="fa-solid fa-plus"></i> Assign New Task
                    </button>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th>Task ID</th>
                                <th>Project Association</th>
                                <th>Task Detail Name</th>
                                <?php if ($user_role !== 'Team Member'): ?>
                                    <th>Assignee</th>
                                <?php endif; ?>
                                <th>Priority</th>
                                <th>Status State</th>
                                <th>Due Date</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayTasks as $task): ?>
                                <tr>
                                    <td class="text-muted fw-semibold">#<?php echo $task['id']; ?></td>
                                    <td><span class="text-dark fw-semibold small"><?php echo htmlspecialchars($task['project']); ?></span></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($task['title']); ?></td>
                                    
                                    <?php if ($user_role !== 'Team Member'): ?>
                                        <td class="text-secondary small fw-semibold"><i class="fa-solid fa-user-gear me-1"></i> <?php echo htmlspecialchars($task['assignee']); ?></td>
                                    <?php endif; ?>

                                    <td><span class="badge rounded-pill px-2.5 py-1.5 <?php echo $task['priority_class']; ?>"><?php echo $task['priority']; ?></span></td>
                                    <td><span class="badge rounded-pill px-2.5 py-1.5 <?php echo $task['status_class']; ?>"><?php echo $task['status']; ?></span></td>
                                    <td class="text-secondary small"><i class="fa-regular fa-calendar-check me-1"></i> <?php echo $task['due_date']; ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <?php if ($user_role === 'Team Member'): ?>
                                                <button class="btn btn-sm btn-light border text-dark rounded-pill d-flex align-items-center gap-1 px-3" data-bs-toggle="modal" data-bs-target="#timeLogModal" onclick="setTaskContext('<?php echo $task['id']; ?>', '<?php echo addslashes($task['title']); ?>')">
                                                    <i class="fa-regular fa-clock text-info"></i> Log Time
                                                </button>
                                            <?php endif; ?>
                                            
                                            <select class="form-select form-select-sm rounded-pill w-auto border-light-subtle bg-light-subtle">
                                                <option <?php echo $task['status'] == 'ToDo' ? 'selected' : ''; ?>>ToDo</option>
                                                <option <?php echo $task['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                <option <?php echo $task['status'] == 'Review' ? 'selected' : ''; ?>>Review</option>
                                                <option <?php echo $task['status'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <?php if ($user_role === 'Admin' || $user_role === 'Project Manager'): ?>
    <div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom p-4">
                    <h5 class="modal-title fw-bold text-dark">Task Assignment Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process_task.php" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Task Title / Specification</label>
                            <input type="text" name="task_name" class="form-control" placeholder="e.g. Implement SweetAlert Popups" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Assignee (Developer / Team Member)</label>
                            <select name="developer_id" class="form-select" required>
                                <option value="">Pumili ng Gagawa...</option>
                                <option value="3">Alex Developer</option>
                                <option value="4">Sarah Jenkins</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-top p-3 bg-light">
                        <button type="button" class="btn btn-light border rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Delegate Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal fade" id="timeLogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark">Log Sprint Time</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="timeLogForm">
                    <div class="modal-body px-4 pb-0">
                        <div class="mb-2">
                            <span class="text-muted small">Target Task:</span>
                            <div id="targetTaskDisplay" class="fw-bold text-primary small mb-3">Task Name</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Hours to Log</label>
                            <input type="number" step="0.5" min="0.5" max="24" class="form-control text-center fs-4 fw-bold text-dark" placeholder="1.5" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 p-4">
                        <button type="button" class="btn btn-light rounded-pill btn-sm px-3" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary rounded-pill btn-sm px-4">Record Logs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setTaskContext(id, title) {
            document.getElementById('targetTaskDisplay').innerText = "#" + id + " - " + title;
        }
    </script>
</body>
</html>