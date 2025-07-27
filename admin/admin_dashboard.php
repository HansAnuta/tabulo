<?php
session_start();
include '../db-connector.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <header>
        <h1>Admin Dashboard</h1>
        <nav>
            <ul>
                <li><a href="#" class="tab-link active" data-tab="events">Events</a></li>
                <li><a href="#" class="tab-link" data-tab="judges">Judges</a></li>
                <li><a href="#" class="tab-link" data-tab="participants">Participants</a></li>
                <li><a href="#" class="tab-link" data-tab="results">Results</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div id="tab-content" class="center">Loading...</div>
        <button id="fab" class="fab" title="Add" style="display:none;">+</button>
    </main>

    <!-- Modals go here, outside #tab-content and <main> -->
    <div id="addEventModal" class="modal" style="display:none;">...</div>
    <div id="editEventModal" class="modal" style="display:none;">...</div>
    <div id="deleteEventModal" class="modal" style="display:none;">...</div>
    <!-- ...other modals... -->

    <script src="admin_dashboard.js"></script>
</body>
</html>
