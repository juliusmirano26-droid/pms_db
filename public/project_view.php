<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Team Member') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// Kuhanin ang lahat ng projects at ang pangalan ng kanilang manager mula sa database
$query = "SELECT p.id, p.project_name, p.status, u.name as manager_name 
          FROM projects p 
          LEFT JOIN users u ON p.manager_id = u.id 
          ORDER BY p.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Project View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="mb-4">
            <a href="developer_dashboard.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left"></i> Back to Terminal Hub</a>
            <h2 class="fw-bold text-dark"><i class="fa-solid fa-cubes text-primary me-2"></i>Global Project Pipelines</h2>
            <p class="text-muted">Overview of structural project manifests tracked within the platform organization.</p>
        </div>

        <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Project ID</th>
                                <th>Project Name</th>
                                <th>Assigned Supervisor</th>
                                <th>Current Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-monospace">#PRJ-<?= $row['id']; ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($row['project_name']); ?></td>
                                        <td class="text-secondary fw-semibold"><?= htmlspecialchars($row['manager_name'] ?? 'Unassigned Manager'); ?></td>
                                        <td>
                                            <span class="badge px-3 py-2 rounded-pill <?= $row['status'] === 'Completed' ? 'bg-success-subtle text-success' : ($row['status'] === 'In Progress' ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning') ?>">
                                                <?= htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No operational projects tracked in the pipeline.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>