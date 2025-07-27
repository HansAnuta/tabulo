<?php
include '../db-connector.php';
session_start();
$admin_id = $_SESSION['admin_id'] ?? 0;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle DELETE participant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_participant_id'])) {
    $participant_id = intval($_POST['delete_participant_id']);
    $stmt = $conn->prepare("DELETE FROM participants WHERE participant_id = ?");
    $stmt->bind_param("i", $participant_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Delete failed.']]);
    }
    exit;
}

// Handle EDIT participant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_participant_id'], $_POST['edit_participant_name'], $_POST['edit_competition_id'])) {
    $participant_id = intval($_POST['edit_participant_id']);
    $participant_name = trim($_POST['edit_participant_name']);
    $competition_id = intval($_POST['edit_competition_id']);
    $errors = [];

    if (empty($participant_name)) $errors[] = "Participant name is required.";
    if (empty($competition_id)) $errors[] = "Competition is required.";

    if ($errors) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE participants SET participant_name = ?, competition_id = ? WHERE participant_id = ?");
    $stmt->bind_param("sii", $participant_name, $competition_id, $participant_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Database error.']]);
    }
    exit;
}

// Handle ADD participant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_name'], $_POST['competition_id'])) {
    $participant_name = trim($_POST['participant_name']);
    $competition_id = intval($_POST['competition_id']);
    $errors = [];

    if (empty($participant_name)) $errors[] = "Participant name is required.";
    if (empty($competition_id)) $errors[] = "Competition is required.";

    if ($errors) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO participants (participant_name, competition_id, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("si", $participant_name, $competition_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Database error.']]);
    }
    exit;
}

// Fetch competitions for dropdown (only competitions from events owned by this admin)
$competitions = [];
$compRes = $conn->query(
    "SELECT c.competition_id, c.competition_name 
     FROM competitions c
     JOIN events e ON c.event_id = e.event_id
     WHERE e.admin_id = $admin_id
     ORDER BY c.competition_name ASC"
);
while ($comp = $compRes->fetch_assoc()) {
    $competitions[] = $comp;
}

// Fetch participants for display (only participants in competitions owned by this admin)
$res = $conn->query(
    "SELECT p.participant_id, p.participant_name, p.competition_id, c.competition_name
     FROM participants p
     LEFT JOIN competitions c ON p.competition_id = c.competition_id
     LEFT JOIN events e ON c.event_id = e.event_id
     WHERE e.admin_id = $admin_id
     ORDER BY p.created_at DESC"
);
?>
<div id="participants-list">
<?php
if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo '<div class="item-row">';
        echo '<button class="item-label">' . htmlspecialchars($row['participant_name'] ?? '') . ' (' . htmlspecialchars($row['competition_name'] ?? '') . ')</button>';
        echo '<div style="position:relative;">';
        echo '<button class="menu-btn">&#8942;</button>';
        echo '<div class="menu-dropdown">';
        echo '<button onclick="openEditParticipantModal('
            . $row['participant_id'] . ', \''
            . htmlspecialchars(addslashes($row['participant_name'] ?? '')) . '\', '
            . $row['competition_id'] . ')">Edit</button>';
        echo '<button onclick="openDeleteParticipantModal(' . $row['participant_id'] . ', \'' . htmlspecialchars($row['participant_name'] ?? '') . '\')">Delete</button>';
        echo '</div></div></div>';
    }
} else {
    echo "<p>No participants found.</p>";
}
?>
</div>

<!-- Add Participant Modal -->
<div id="addParticipantModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;">
    <h3>Add Participant</h3>
    <form id="addParticipantForm" autocomplete="off">
      <label for="participant_name">Participant Name</label>
      <input type="text" id="participant_name" name="participant_name" required>
      <label for="competition_id">Assigned Competition</label>
      <select id="competition_id" name="competition_id" required>
        <option value="">Select Competition</option>
        <?php foreach($competitions as $comp): ?>
            <option value="<?= $comp['competition_id'] ?>"><?= htmlspecialchars($comp['competition_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="add-participant-error" style="color:#e53935;font-size:0.95em;margin:0.5em 0;"></div>
      <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Add Participant</button>
      <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeAddParticipantModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Edit Participant Modal -->
<div id="editParticipantModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;">
    <h3>Edit Participant</h3>
    <form id="editParticipantForm" autocomplete="off">
      <input type="hidden" id="edit_participant_id" name="edit_participant_id">
      <label for="edit_participant_name">Participant Name</label>
      <input type="text" id="edit_participant_name" name="edit_participant_name" required>
      <label for="edit_competition_id">Assigned Competition</label>
      <select id="edit_competition_id" name="edit_competition_id" required>
        <option value="">Select Competition</option>
        <?php foreach($competitions as $comp): ?>
            <option value="<?= $comp['competition_id'] ?>"><?= htmlspecialchars($comp['competition_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="edit-participant-error" style="color:#e53935;font-size:0.95em;margin:0.5em 0;"></div>
      <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Save Changes</button>
      <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeEditParticipantModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Delete Participant Modal -->
<div id="deleteParticipantModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;text-align:center;">
    <h3 style="color:#e53935;margin-bottom:1rem;">Delete Participant</h3>
    <p id="delete-participant-msg"></p>
    <div style="margin-top:1.5rem;">
      <button id="confirmDeleteParticipantBtn" class="btn" style="background:#e53935;">Delete</button>
      <button id="cancelDeleteParticipantBtn" class="btn" style="background:#888;margin-left:1rem;">Cancel</button>
    </div>
  </div>
</div>