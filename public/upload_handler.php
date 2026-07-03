<?php
session_start();

// Database Connection
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['submission_file'])) {
    $task_id = intval($_POST['task_id']);
    
    // Create an uploads directory if it doesn't exist yet
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . '_' . basename($_FILES['submission_file']['name']);
    $target_file = $upload_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Optional: Restrict file formats if necessary (e.g., zip, pdf, rar, docx, png, jpg)
    $allowed_types = ['zip', 'rar', 'pdf', 'docx', 'png', 'jpg', 'jpeg', 'txt'];
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file extension style.']);
        exit;
    }

    // Move file from temporary directory to server folder
    if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $target_file)) {
        // Update database row dynamically where the assignment matches
        $stmt = $conn->prepare("UPDATE assignments SET file_path = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $task_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'File uploaded successfully! Manager notified.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save file path instance to system database.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error moving physical file to storage folder.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file payload detected.']);
}

$conn->close();
?>  