<?php
session_start();
include 'db-connector.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $error = "";

    // Check if username or email already exists
    $stmt1 = $conn->prepare("SELECT admin_id FROM admin WHERE username = ? OR email = ?");
    if (!$stmt1) {
        echo json_encode(['success' => false, 'error' => 'Database error (prepare select).']);
        exit();
    }
    $stmt1->bind_param("ss", $username, $email);
    $stmt1->execute();
    $stmt1->store_result();
    if ($stmt1->num_rows > 0) {
        $stmt1->close();
        $conn->close();
        echo json_encode(['success' => false, 'error' => 'Username or email already exists.']);
        exit();
    }
    $stmt1->close();

    // Insert new admin
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt2 = $conn->prepare("INSERT INTO admin (username, email, password) VALUES (?, ?, ?)");
    if (!$stmt2) {
        echo json_encode(['success' => false, 'error' => 'Database error (prepare insert).']);
        exit();
    }
    $stmt2->bind_param("sss", $username, $email, $hashed);
    if ($stmt2->execute()) {
        $stmt2->close();
        $conn->close();
        echo json_encode(['success' => true]);
        exit();
    } else {
        $stmt2->close();
        $conn->close();
        echo json_encode(['success' => false, 'error' => 'Failed to create account.']);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit();
}
?>