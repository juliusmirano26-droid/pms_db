<?php
// SidebarFactory.php

interface SidebarInterface {
    public function render($currentPage);
}

class AdminSidebar implements SidebarInterface {
    public function render($currentPage) {
        ?>
        <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
            <div>
                <div class="p-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                    <span class="fs-5 fw-bold text-white">ProjectMS</span>
                </div>
                <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
                <ul class="nav nav-pills flex-column mt-3">
                    <li class="nav-item">
                        <a href="admin_dashboard.php" class="nav-link <?= ($currentPage === 'admin_dashboard.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-chart-pie"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="manage_users.php" class="nav-link <?= ($currentPage === 'manage_users.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-users"></i> Manage Users
                        </a>
                    </li>
                    <li>
                        <a href="projects.php" class="nav-link <?= ($currentPage === 'projects.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-folder"></i> Projects
                        </a>
                    </li>
                    <li>
                        <a href="documents.php" class="nav-link <?= ($currentPage === 'documents.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-file-lines"></i> Documents
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="nav-link <?= ($currentPage === 'reports.php') ? 'active-accent' : ''; ?>">
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
        <?php
    }
}

class ManagerSidebar implements SidebarInterface {
    public function render($currentPage) {
        ?>
        <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
            <div>
                <div class="p-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                    <span class="fs-5 fw-bold text-white">ProjectMS</span>
                </div>
                <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
                <ul class="nav nav-pills flex-column mt-3">
                    <li class="nav-item">
                        <a href="manager_dashboard.php" class="nav-link <?= ($currentPage === 'manager_dashboard.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-chart-pie"></i> Operations
                        </a>
                    </li>
                    <li>
                        <a href="projects.php" class="nav-link <?= ($currentPage === 'projects.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-folder"></i> My Projects
                        </a>
                    </li>
                    <li>
                        <a href="tasks.php" class="nav-link <?= ($currentPage === 'tasks.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-list-check"></i> Assign Tasks
                        </a>
                    </li>
                    <li>
                        <a href="expenses.php" class="nav-link <?= ($currentPage === 'expenses.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-wallet"></i> Project Budget
                        </a>
                    </li>
                    <li>
                        <a href="documents.php" class="nav-link <?= ($currentPage === 'documents.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-file-lines"></i> Documents
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="nav-link <?= ($currentPage === 'reports.php') ? 'active-accent' : ''; ?>">
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
        <?php
    }
}

class DeveloperSidebar implements SidebarInterface {
    public function render($currentPage) {
        ?>
        <div class="custom-sidebar d-flex flex-column justify-content-between pb-3">
            <div>
                <div class="p-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-layer-group fs-4" style="color: #2563eb;"></i>
                    <span class="fs-5 fw-bold text-white">ProjectMS</span>
                </div>
                <hr class="mx-3 my-0" style="border-color: rgba(255,255,255,0.15);">
                <ul class="nav nav-pills flex-column mt-3">
                    <li class="nav-item">
                        <a href="developer_dashboard.php" class="nav-link <?= ($currentPage === 'developer_dashboard.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-chart-pie"></i> Workspace
                        </a>
                    </li>
                    <li>
                        <a href="tasks.php" class="nav-link <?= ($currentPage === 'tasks.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-list-check"></i> My Assigned Tasks
                        </a>
                    </li>
                    <li>
                        <a href="documents.php" class="nav-link <?= ($currentPage === 'documents.php') ? 'active-accent' : ''; ?>">
                            <i class="fa-solid fa-file-lines"></i> Documents
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="nav-link <?= ($currentPage === 'reports.php') ? 'active-accent' : ''; ?>">
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
        <?php
    }
}

// OOP Factory class para piliin ang tamang layout base sa role ng user
class SidebarFactory {
    public static function create($role) {
        switch ($role) {
            case 'Admin':
                return new AdminSidebar();
            case 'Project Manager':
                return new ManagerSidebar();
            case 'Team Member':
                return new DeveloperSidebar();
            default:
                return new DeveloperSidebar();
        }
    }
}