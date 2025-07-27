<?php
session_start();
include 'db-connector.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

header('Content-Type: application/json'); // Always return JSON

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $error = "";

    $stmt = $conn->prepare("SELECT admin_id, username, password FROM admin WHERE username = ?");
    if (!$stmt) {
        $error = "Database error.";
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($admin_id, $db_username, $db_password);
            $stmt->fetch();
            if (password_verify($password, $db_password)) {
                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['username'] = $db_username;
                echo json_encode(['success' => true, 'redirect' => 'admin/admin_dashboard.php']);
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
    $conn->close();
    echo json_encode(['success' => false, 'error' => $error]);
    exit();
}
?>