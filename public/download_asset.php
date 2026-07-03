<?php
session_start();

// Strict Access Control: Siguraduhing may valid session bago payagang mag-download
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['file_id']) || empty($_GET['file_id'])) {
    die("Error: Invalid resource identifier.");
}

$file_id = intval($_GET['file_id']);

// Database Connection Hook
$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

// Kunin ang file details mula sa database
// TANDAAN: Palitan ang 'file_path' kung iba ang pangalan ng column mo na nagtatago ng destination path
$stmt = $conn->prepare("SELECT document_name, file_path FROM documents WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $file = $result->fetch_assoc();
    $filename = $file['document_name'];
    $filepath = $file['file_path']; // Halimbawa: "uploads/blueprint.pdf" o kaya "uploads/filename.png"

    // Suriin kung physically existing ang file sa iyong storage path directory
    if (file_exists($filepath)) {
        // I-clear ang output buffer para maiwasan ang file corruption sa dulo ng download
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Send binary stream response headers upang pilitin ang browser na i-download ang file
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Basahin ang file at ipadala sa browser download client channel
        readfile($filepath);
        exit;
    } else {
        die("Error: The requested physical asset file could not be found inside the repository storage path storage.");
    }
} else {
    die("Error: Document match index reference does not exist in the vault directory registry.");
}

$stmt->close();
$conn->close();
?>