<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_role = $_SESSION['user_role'] ?? 'Team Member';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .custom-sidebar {
        width: 260px; 
        height: 100vh; 
        position: fixed; 
        top: 0; 
        left: 0; 
        background-color: #1e293b; /* Slate Dark Navy background */
        z-index: 100;
    }
    .custom-sidebar .nav-link {
        color: #94a3b8 !important; /* Muted gray text */
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
    /* Ang fixed Matingkad na Electric Blue highlight indicator */
    .custom-sidebar .nav-link.active-accent {
        color: #ffffff !important;
        background-color: #2563eb !important; 
    }
</style>

<div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
    <div>
        <div class="p-4 d-flex align-items-center gap-2">
            <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
            <span class="fs-5 fw-bold text-white">ProjectMS</span>
        </div>
        <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
        
        <ul class="nav nav-pills flex-column mt-3">
            
            <?php if ($user_role === 'Admin'): ?>
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="manage_users.php" class="nav-link <?php echo ($current_page === 'manage_users.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-users"></i> Manage Users
                    </a>
                </li>
                <li>
                    <a href="projects.php" class="nav-link <?php echo ($current_page === 'projects.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-folder"></i> Projects
                    </a>
                </li>

            <?php elseif ($user_role === 'Project Manager'): ?>
                <li class="nav-item">
                    <a href="manager_dashboard.php" class="nav-link <?php echo ($current_page === 'manager_dashboard.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> Operations
                    </a>
                </li>
                <li>
                    <a href="projects.php" class="nav-link <?php echo ($current_page === 'projects.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-folder"></i> My Projects
                    </a>
                </li>
                <li>
                    <a href="tasks.php" class="nav-link <?php echo ($current_page === 'tasks.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-list-check"></i> Assign Tasks
                    </a>
                </li>
                <li>
                    <a href="expenses.php" class="nav-link <?php echo ($current_page === 'expenses.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-wallet"></i> Project Budget
                    </a>
                </li>

            <?php elseif ($user_role === 'Team Member'): ?>
                <li class="nav-item">
                    <a href="developer_dashboard.php" class="nav-link <?php echo ($current_page === 'developer_dashboard.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-chart-pie"></i> Workspace
                    </a>
                </li>
                <li>
                    <a href="tasks.php" class="nav-link <?php echo ($current_page === 'tasks.php') ? 'active-accent' : ''; ?>">
                        <i class="fa-solid fa-list-check"></i> My Assigned Tasks
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="documents.php" class="nav-link <?php echo ($current_page === 'documents.php') ? 'active-accent' : ''; ?>">
                    <i class="fa-solid fa-file-lines"></i> Documents
                </a>
            </li>
            <li>
                <a href="reports.php" class="nav-link <?php echo ($current_page === 'reports.php') ? 'active-accent' : ''; ?>">
                    <i class="fa-solid fa-chart-line"></i> Reports
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