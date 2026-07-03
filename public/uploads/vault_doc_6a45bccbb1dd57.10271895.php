<?php
session_start();

// Access Control
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}

// Ensure the local storage directory exists safely
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// --- HANDLE FILE DELETION OPERATION ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Fetch target file path details first to delete it from local disk
    $file_stmt = $conn->prepare("SELECT file_path FROM documents WHERE id = ?");
    $file_stmt->bind_param("i", $delete_id);
    $file_stmt->execute();
    $file_res = $file_stmt->get_result();
    
    if ($file_res && $row = $file_res->fetch_assoc()) {
        $target_path = $row['file_path'];
        if (!empty($target_path) && file_exists($target_path)) {
            unlink($target_path); // Erase document asset cleanly from the server disk
        }
    }
    $file_stmt->close();

    // Remove metadata document record registry out of SQL table row entries
    $del_stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
    $del_stmt->bind_param("i", $delete_id);
    $del_stmt->execute();
    $del_stmt->close();

    header("Location: vault_documents.php");
    exit;
}

// --- HANDLE REAL FILE UPLOAD SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_doc') {
    $doc_display_name = trim($_POST['document_name']);
    $user_id = $_SESSION['user_id'];

    if (!empty($doc_display_name) && isset($_FILES['vault_file']) && $_FILES['vault_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['vault_file']['tmp_name'];
        $original_name = $_FILES['vault_file']['name'];
        $raw_size = $_FILES['vault_file']['size'];
        
        // Calculate size in a clean, human-readable format
        if ($raw_size >= 1048576) {
            $file_size_formatted = round($raw_size / 1048576, 2) . " MB";
        } else {
            $file_size_formatted = round($raw_size / 1024, 2) . " KB";
        }

        // Generate a unique file name to avoid collisions on server disk
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $secure_file_name = uniqid('vault_doc_', true) . '.' . $file_extension;
        $destination_path = $upload_dir . $secure_file_name;

        if (move_uploaded_file($file_tmp_name, $destination_path)) {
            // Save metadata and server storage path into database safely
            $stmt = $conn->prepare("INSERT INTO documents (document_name, file_path, file_size, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $doc_display_name, $destination_path, $file_size_formatted, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        header("Location: vault_documents.php");
        exit;
    }
}

// Pull historical records mapping against publisher identities
$query = "SELECT d.id, d.document_name, d.file_path, d.file_size, u.name as uploaded_by 
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
    <title>PMS - Vault Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="manager_dashboard.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
                <h2 class="fw-bold text-dark"><i class="fa-solid fa-file-shield text-success me-2"></i>Vault Document Management Repository</h2>
            </div>
            <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload & Index Document
            </button>
        </div>

        <div class="card border shadow-sm" style="border-radius: 12px; background: white;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 12%;">Resource ID</th>
                                <th style="width: 38%;">Document Asset Name</th>
                                <th style="width: 15%;">Size Allocation</th>
                                <th style="width: 20%;">Publisher Origin</th>
                                <th class="text-center" style="width: 15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 text-muted font-monospace">#DOC-<?= $row['id']; ?></td>
                                        <td class="fw-semibold text-dark">
                                            <i class="fa-regular fa-file-lines text-primary me-2"></i><?= htmlspecialchars($row['document_name']); ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['file_size']); ?></span></td>
                                        <td><?= htmlspecialchars($row['uploaded_by'] ?? 'System Core'); ?></td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="<?= htmlspecialchars($row['file_path']); ?>" class="btn btn-sm btn-outline-primary" download title="Download file">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                                <a href="vault_documents.php?delete_id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to permanently purge this document asset from the vault server partition?');" title="Delete record">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-regular fa-folder-open d-block fs-3 mb-2 text-secondary"></i>
                                        Vault structures are currently empty.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="uploadDocModalLabel">Index Document Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="vault_documents.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_doc">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Document/Resource Name</label>
                            <input type="text" name="document_name" class="form-control" required placeholder="e.g. system_architecture_v2.pdf">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Select Target Local File</label>
                            <input type="file" name="vault_file" class="form-control" required>
                            <div class="form-text small text-muted">Supports raw architectural specs, source files, or binary uploads.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4">Register & Upload Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$conn->close(); 
?>