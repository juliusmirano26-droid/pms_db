<?php
session_start();

// Security: Ensure only Admins can perform these actions
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "pms_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ================= HANDLER PARA SA ADD AT EDIT =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Standard sanitization using mysqli_real_escape_string
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $role = $_POST['role'];

    if ($action === 'add') {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $username, $email, $password, $role);
        $stmt->execute();
        $stmt->close();
    } 
    
    elseif ($action === 'edit') {
        $user_id = intval($_POST['user_id']);
        $password = $_POST['password'] ?? '';

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $name, $username, $email, $hashed_password, $role, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $username, $email, $role, $user_id);
        }
        $stmt->execute();
        $stmt->close();
    }

    header("Location: manage_users.php");
    exit;
}

// ================= HANDLER PARA SA DELETE =================
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);

    // Prevent Admin from deleting their own active session
    if ($user_id === intval($_SESSION['user_id'])) {
        echo "<script>alert('Cannot delete your own active account!'); window.location.href='manage_users.php';</script>";
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit;
}
?>