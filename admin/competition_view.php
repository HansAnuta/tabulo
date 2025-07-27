<?php
include '../db-connector.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? 0;
$competition_id = isset($_GET['competition_id']) ? intval($_GET['competition_id']) : 0;

if (!$competition_id || !$admin_id) {
    echo "Access denied or invalid competition.";
    exit;
}

// Validate competition belongs to admin
$sql = "SELECT c.competition_id, c.judging_method_id, e.admin_id
        FROM competitions c
        JOIN events e ON c.event_id = e.event_id
        WHERE c.competition_id = ? AND e.admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $competition_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$comp = $result->fetch_assoc();

if (!$comp) {
    echo "Competition not found or access denied.";
    exit;
}

switch ($comp['judging_method_id']) {
    case 2: // Simple Averaging
        header("Location: criteria/simple_averaging.php?competition_id=" . $competition_id);
        exit;
    case 3: // Weighted Averaging
        header("Location: criteria/weighted_averaging.php?competition_id=" . $competition_id);
        exit;

    // You can add more methods here:
    // case 3: header("Location: criteria/weighted_averaging.php?competition_id=...");
    default:
        echo "Unsupported judging method.";
        exit;
}
