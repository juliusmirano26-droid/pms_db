<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_role = $_SESSION['user_role'] ?? 'Admin';
$query = "SELECT id, username, email, role, name FROM users ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Manage Users</title>
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
                <li><a href="admin_dashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
                <li><a href="manage_users.php" class="nav-link active-accent"><i class="fa-solid fa-users-gear"></i> Manage Users</a></li>
                <li><a href="projects.php" class="nav-link"><i class="fa-solid fa-sitemap"></i> Matrix Flow</a></li>
                <li><a href="documents.php" class="nav-link"><i class="fa-solid fa-folder-tree"></i> Documents</a></li>
                <li><a href="developer_budget.php" class="nav-link"><i class="fa-solid fa-code text-info"></i> Dev Budget</a></li>
                <li><a href="crew_budget_control.php" class="nav-link"><i class="fa-solid fa-helmet-safety text-warning"></i> Crew Budget</a></li>
                <li><a href="sponsors.php" class="nav-link"><i class="fa-solid fa-hand-holding-dollar text-success"></i> Sponsors</a></li>
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
            <h5 class="mb-0 text-secondary fw-semibold">User Management</h5>
            <span class="text-muted small">Authority: <strong class="text-danger"><?= htmlspecialchars($user_role); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">System Account Records</h3>
                    <p class="text-muted mb-0">Add, modify, or delete system authorization access accounts.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addUserModal" style="background-color: var(--pms-electric-blue); border: none;">
                    <i class="fa-solid fa-user-plus me-2"></i> Add New User
                </button>
            </div>

            <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Full Name</th>
                                <th>Username</th>
                                <th>Email Address</th>
                                <th>System Role</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($row['name']); ?></td>
                                        <td><?= htmlspecialchars($row['username']); ?></td>
                                        <td><?= htmlspecialchars($row['email'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge px-3 py-2 rounded-pill <?= $row['role'] === 'Admin' ? 'bg-danger-subtle text-danger' : ($row['role'] === 'Project Manager' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success') ?>">
                                                <?= htmlspecialchars($row['role']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-secondary edit-btn rounded-circle me-1" 
                                                    data-id="<?= $row['id']; ?>" 
                                                    data-name="<?= htmlspecialchars($row['name']); ?>" 
                                                    data-username="<?= htmlspecialchars($row['username']); ?>" 
                                                    data-email="<?= htmlspecialchars($row['email'] ?? ''); ?>" 
                                                    data-role="<?= $row['role']; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <a href="process_user.php?delete=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Are you sure you want to delete this user?');">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="addUserModal" tabindex="-1"><div class="modal-dialog"><form action="process_user.php" method="POST" class="modal-content"><input type="hidden" name="action" value="add"><div class="modal-header"><h5>Add New User</h5></div><div class="modal-body"><input type="text" name="name" class="form-control mb-2" placeholder="Full Name" required><input type="text" name="username" class="form-control mb-2" placeholder="Username" required><input type="email" name="email" class="form-control mb-2" placeholder="Email" required><input type="password" name="password" class="form-control mb-2" placeholder="Password" required><select name="role" class="form-control"><option value="Admin">Admin</option><option value="Project Manager">Project Manager</option><option value="Team Member">Team Member</option></select></div><div class="modal-footer"><button type="submit" class="btn btn-primary">Save</button></div></form></div></div>

    <div class="modal fade" id="editUserModal" tabindex="-1"><div class="modal-dialog"><form action="process_user.php" method="POST" class="modal-content"><input type="hidden" name="action" value="edit"><input type="hidden" name="user_id" id="edit_user_id"><div class="modal-header"><h5>Edit User</h5></div><div class="modal-body"><input type="text" name="name" id="edit_name" class="form-control mb-2" required><input type="text" name="username" id="edit_username" class="form-control mb-2" required><input type="email" name="email" id="edit_email" class="form-control mb-2" required><input type="password" name="password" class="form-control mb-2" placeholder="New Password (optional)"><select name="role" id="edit_role" class="form-control"><option value="Admin">Admin</option><option value="Project Manager">Project Manager</option><option value="Team Member">Team Member</option></select></div><div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div></form></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_user_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_username').value = this.dataset.username;
                document.getElementById('edit_email').value = this.dataset.email;
                document.getElementById('edit_role').value = this.dataset.role;
            });
        });
    </script>
</body>
</html>