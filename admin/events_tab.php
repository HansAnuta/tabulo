<?php
include '../db-connector.php';
session_start();
$admin_id = $_SESSION['admin_id'] ?? 0;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle event deletion FIRST!
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event_id'])) {
    $event_id = intval($_POST['delete_event_id']);
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Delete failed.']]);
    }
    exit;
}

// Handle AJAX form submission (add event)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_name'], $_POST['event_date'])) {
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $errors = [];

    if (empty($event_name)) $errors[] = "Event name is required.";
    if (empty($event_date)) $errors[] = "Event date is required.";

    if ($errors) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, admin_id, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("ssi", $event_name, $event_date, $admin_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Database error.']]);
    }
    exit;
}

// Handle AJAX form submission (edit event)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_event_id'], $_POST['edit_event_name'], $_POST['edit_event_date'])) {
    $event_id = intval($_POST['edit_event_id']);
    $event_name = trim($_POST['edit_event_name']);
    $event_date = $_POST['edit_event_date'];
    $errors = [];

    if (empty($event_name)) $errors[] = "Event name is required.";
    if (empty($event_date)) $errors[] = "Event date is required.";

    if ($errors) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE events SET event_name = ?, event_date = ? WHERE event_id = ?");
    $stmt->bind_param("ssi", $event_name, $event_date, $event_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Database error.']]);
    }
    exit;
}

// Display events list and modal form
$res = $conn->query("SELECT event_id, event_name, event_date FROM events WHERE admin_id = $admin_id ORDER BY created_at DESC");
?>
<div id="events-list">
<?php if ($res->num_rows > 0): ?>
<div class="tab-content">
    <?php while($row = $res->fetch_assoc()): ?>
        <?php
        $eventUrl = "competitions.php?event_id=" . $row['event_id'];
        echo '<div class="item-row" onclick="window.location.href=\'competitions.php?event_id=' . $row['event_id'] . '\';" style="cursor:pointer;">';
        echo '<span class="item-label">' . htmlspecialchars($row['event_name']) . ' (' . htmlspecialchars($row['event_date']) . ')</span>';
        echo '<div style="position:relative;">';
        echo '<button class="menu-btn" onclick="event.stopPropagation();">&#8942;</button>';
        echo '<div class="menu-dropdown" onclick="event.stopPropagation();">';
        echo '<button onclick="event.stopPropagation(); openEditEventModal(' . $row['event_id'] . ', \'' . htmlspecialchars(addslashes($row['event_name'])) . '\', \'' . $row['event_date'] . '\')">Edit</button>';
        echo '<button onclick="event.stopPropagation(); openDeleteEventModal(' . $row['event_id'] . ', \'' . htmlspecialchars($row['event_name']) . '\')">Delete</button>';
        echo '</div></div></div>';
        ?>
    <?php endwhile; ?>
</div>
<?php else: ?>
    <p style="text-align:center;color:#888;margin:2em 0;">No events found.</p>
<?php endif; ?>
</div>

<!-- Add Event Modal -->
<div id="addEventModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;">
    <h3>Add Event</h3>
    <form id="addEventForm" autocomplete="off">
      <label for="event_name">Event Name</label>
      <input type="text" id="event_name" name="event_name" required>
      <label for="event_date">Event Date</label>
      <input type="date" id="event_date" name="event_date" required>
      <div id="add-event-error" style="color:#e53935;font-size:0.95em;margin:0.5em 0;"></div>
      <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Add Event</button>
      <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeAddEventModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteEventModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;text-align:center;">
    <h3 style="color:#e53935;margin-bottom:1rem;">Delete Event</h3>
    <p id="delete-event-msg"></p>
    <div style="margin-top:1.5rem;">
      <button id="confirmDeleteEventBtn" class="btn" style="background:#e53935;">Delete</button>
      <button id="cancelDeleteEventBtn" class="btn" style="background:#888;margin-left:1rem;">Cancel</button>
    </div>
  </div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h3>Edit Event</h3>
    <form id="editEventForm" autocomplete="off">
      <label for="edit_event_name">Event Name</label>
      <input type="text" id="edit_event_name" name="edit_event_name" required>

      <label for="edit_event_date">Event Date</label>
      <input type="date" id="edit_event_date" name="edit_event_date" required>

      <input type="hidden" id="edit_event_id" name="edit_event_id">

      <div id="edit-event-error" style="color:#e53935;font-size:0.95em;margin:0.5em 0;"></div>
      <button type="submit" class="btn" style="width:100%;">Save Changes</button>
      <button type="button" class="btn" style="width:100%;background:#888;" onclick="closeEditEventModal()">Cancel</button>
    </form>
  </div>
</div>