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

$user_name = $_SESSION['user_name'] ?? 'Developer';
$user_role = $_SESSION['user_role'] ?? 'Team Member';
$current_page = basename($_SERVER['PHP_SELF']);

// Kuhanin ang lahat ng naka-index na dokumento mula sa vault repository
$query = "SELECT d.id, d.document_name, d.file_size, u.name as uploaded_by 
          FROM documents d 
          LEFT JOIN users u ON d.user_id = u.id 
          ORDER BY d.id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - Shared Vault</title>
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
                <li>
                    <a href="developer_dashboard.php" class="nav-link">
                        <i class="fa-solid fa-terminal"></i> Terminal Hub
                    </a>
                </li>
                <li>
                    <a href="my_assignments.php" class="nav-link">
                        <i class="fa-solid fa-list-check"></i> My Assignments
                    </a>
                </li>
                <li>
                    <a href="shared_vault.php" class="nav-link active-accent">
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
            <h5 class="mb-0 text-secondary fw-semibold">Secure Resource Vault</h5>
            <span class="text-muted small">Identity: <strong><?= htmlspecialchars($user_name); ?></strong></span>
        </header>

        <main class="p-4 container-fluid">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Shared File Resources</h3>
                <p class="text-muted mb-0">Access system settings, project plans, and database documents.</p>
            </div>

            <!-- NEW: Search Component Layout Container matching image_52e926.png context -->
            <div class="mb-4 col-md-5 col-lg-4">
                <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="vaultSearch" class="form-control border-start-0 ps-1" placeholder="Search document name or publisher..." style="font-size: 0.9rem;">
                </div>
            </div>

            <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="vaultTable" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 15%;">Resource ID</th>
                                    <th style="width: 35%;">Asset Document Name</th>
                                    <th style="width: 15%;">Memory Allocation</th>
                                    <th style="width: 20%;">Publisher Origin</th>
                                    <th class="text-center" style="width: 15%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="document-row">
                                            <td class="ps-4 text-muted font-monospace">#DOC-<?= $row['id']; ?></td>
                                            <td class="fw-semibold text-dark searchable-field">
                                                <i class="fa-regular fa-file-code text-success me-2 fs-5"></i>
                                                <?= htmlspecialchars($row['document_name']); ?>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['file_size']); ?></span></td>
                                            <td><span class="text-secondary small fw-bold searchable-field"><?= htmlspecialchars($row['uploaded_by'] ?? 'System Core'); ?></span></td>
                                            <!-- NEW: Embedded Asset Download Trigger Button -->
                                            <td class="text-center">
                                                <a href="download_asset.php?file_id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary px-3 py-1" style="font-size: 0.75rem; border-radius: 6px;">
                                                    <i class="fa-solid fa-download"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr id="emptyRow"><td colspan="5" class="text-center py-4 text-muted">No shared configuration blueprints currently available in the vault channels.</td></tr>
                                <?php endif; ?>
                                <!-- NEW: Dynamic empty message container for JavaScript filter matching exceptions -->
                                <tr id="noMatchesRow" style="display: none;">
                                    <td colspan="5" class="text-center py-4 text-muted">No configuration records found matching your filter parameter terms.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side instant filter engine script
        document.getElementById('vaultSearch').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.document-row');
            let anyResultVisible = false;

            rows.forEach(row => {
                const targets = row.querySelectorAll('.searchable-field');
                let matchTextCombined = '';
                
                targets.forEach(t => matchTextCombined += ' ' + t.textContent.toLowerCase());

                if (matchTextCombined.includes(searchValue)) {
                    row.style.display = '';
                    anyResultVisible = true;
                } else {
                    row.style.display = 'none';
                }
            });

            // Toggle intermediate messaging visibility context
            const defaultEmptyMsg = document.getElementById('emptyRow');
            const searchEmptyMsg = document.getElementById('noMatchesRow');

            if (searchEmptyMsg) {
                if (!anyResultVisible && rows.length > 0) {
                    searchEmptyMsg.style.display = 'table-row';
                } else {
                    searchEmptyMsg.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>