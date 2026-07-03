<?php
session_start();
header('Content-Type: application/json');

// I-connect ang iyong Database Configuration
$conn = new mysqli("localhost", "root", "", "pms_db");

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kunin at i-sanitize ang dinalang data mula sa AJAX input field
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please provide both email and password.'
        ]);
        exit;
    }

    // Tiyaking tinatawag natin ang bagong 'email' field sa query check nito
    $stmt = $conn->prepare("SELECT id, name, username, email, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // I-verify ang naka-bcrypt na portal password string
        if (password_verify($password, $user['password'])) {
            
            // Itakda ang global session storage para sa user identification profile
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // IPINANUMBALIK/INAYOS: Dynamic routing batay sa nakatalagang tungkulin o role sa system
            if ($user['role'] === 'Admin') {
                $redirect_url = 'manage_users.php';
            } elseif ($user['role'] === 'Project Manager') {
                $redirect_url = 'manager_dashboard.php'; // Dito ididirekta ang Project Manager
            } else {
                $redirect_url = 'developer_dashboard.php'; // Para sa mga Team Member/Developer
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => $redirect_url
            ]);
            exit;
        }
    }

    // Standard fallback error kapag hindi nahanap ang account o hindi tugma ang login validation
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address or matching portal password.'
    ]);
    exit;
}
?>