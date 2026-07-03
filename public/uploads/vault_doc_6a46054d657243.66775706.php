<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['Admin', 'Project Manager'])) {
    header("Location: login.php");
    exit;
}
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --primary-color: #4e73df; --secondary-bg: #f8f9fc; --dark-sidebar: #1e293b; }
        body { background-color: var(--secondary-bg); font-family: 'Segoe UI', sans-serif; overflow-x: hidden; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--dark-sidebar); color: #fff; z-index: 100; }
        .sidebar .nav-link { color: rgba(255,255,255,0.75); padding: 12px 20px; border-radius: 8px; margin: 4px 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background-color: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { background-color: var(--primary-color); }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .top-navbar { background: #fff; height: 70px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <header class="top-navbar">
            <h5 class="mb-0 text-secondary fw-semibold">Financial Ledger Track</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: 600;"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div class="d-none d-sm-block">
                    <div class="fw-semibold text-dark fs-7" style="line-height: 1.2;"><?php echo htmlspecialchars($user_name); ?></div>
                    <span class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($user_role); ?></span>
                </div>
            </div>
        </header>

        <main class="p-4 container-fluid">
            <div class="card stat-card bg-white p-4 mb-4">
                <h3 class="fw-bold text-dark mb-1">Financial Budget & Expenses</h3>
                <p class="text-muted mb-0">Review project fund burning tracking statements and structural allocations metrics.</p>
            </div>

            <div class="card stat-card bg-white p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-secondary small text-uppercase">
                            <tr>
                                <th>Item Descriptor</th>
                                <th>Project Allocation</th>
                                <th>Allocated Value</th>
                                <th>Audit Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Premium Server Hosting Core Integration</td>
                                <td>E-Commerce App</td>
                                <td class="text-danger fw-semibold">-$1,250.00</td>
                                <td><span class="badge bg-success-subtle text-success">Cleared Ledger</span></td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">UI/UX Design Wireframe Licensing Asset</td>
                                <td>E-Commerce App</td>
                                <td class="text-danger fw-semibold">-$3,000.00</td>
                                <td><span class="badge bg-success-subtle text-success">Cleared Ledger</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>