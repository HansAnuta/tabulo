<?php
include '../db-connector.php';
session_start();
$admin_id = $_SESSION['admin_id'] ?? 0;

$res = $conn->query(
    "SELECT c.competition_id, c.competition_name
     FROM competitions c
     JOIN events e ON c.event_id = e.event_id
     WHERE e.admin_id = $admin_id
     ORDER BY c.competition_name ASC"
);

if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo '<div class="item-row">';
        echo '<button class="item-label" onclick="alert(\'View results for: ' . htmlspecialchars($row['competition_name']) . '\')">' . htmlspecialchars($row['competition_name']) . '</button>';
        echo '</div>';
    }
} else {
    echo "<p>No competitions found.</p>";
}
?>