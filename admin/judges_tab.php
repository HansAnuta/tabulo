<?php
include '../db-connector.php';
session_start();
$admin_id = $_SESSION['admin_id'] ?? 0;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle DELETE judge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_judge_id'])) {
    $judge_id = intval($_POST['delete_judge_id']);
    $stmt = $conn->prepare("DELETE FROM judges WHERE judge_id = ?");
    $stmt->bind_param("i", $judge_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Delete failed.']]);
    }
    exit;
}

// Handle EDIT judge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_judge_id'], $_POST['edit_judge_name'], $_POST['edit_competition_id'])) {
    $judge_id = intval($_POST['edit_judge_id']);
    $judge_name = trim($_POST['edit_judge_name']);
    $competition_id = intval($_POST['edit_competition_id']);
    $errors = [];

    if (empty($judge_name)) $errors[] = "Judge name is required.";
    if (empty($competition_id)) $errors[] = "Competition is required.";

    if ($errors) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE judges SET judge_name = ?, competition_id = ? WHERE judge_id = ?");
    $stmt->bind_param("sii", $judge_name, $competition_id, $judge_id);
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => ['Database error.']]);
    }
    exit;
}

// Handle ADD judge
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['judge_name'], $_POST['competition_id'])) {
    $judge_name = trim($_POST['judge_name']);
    $competition_id = intval($_POST['competition_id']);
    $errors = [];

    if (empty($judge_name)) $errors[] = "Judge name is required.";
    if (empty($competition_id)) $errors[] = "Competition is required.";

    if ($errors) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO judges (judge_name, competition_id, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("si", $judge_name, $competition_id);
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

// Fetch judges for display (only judges in competitions owned by this admin)
$res = $conn->query(
    "SELECT j.judge_id, j.judge_name, j.competition_id, c.competition_name
     FROM judges j
     LEFT JOIN competitions c ON j.competition_id = c.competition_id
     LEFT JOIN events e ON c.event_id = e.event_id
     WHERE e.admin_id = $admin_id
     ORDER BY j.created_at DESC"
);
?>
<div id="judges-list">
<?php
if ($res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        echo '<div class="item-row">';
        echo '<button class="item-label">' . htmlspecialchars($row['judge_name'] ?? '') . ' (' . htmlspecialchars($row['competition_name'] ?? '') . ')</button>';
        echo '<div style="position:relative;">';
        echo '<button class="menu-btn">&#8942;</button>';
        echo '<div class="menu-dropdown">';
        echo '<button onclick="openEditJudgeModal('
            . $row['judge_id'] . ', \''
            . htmlspecialchars(addslashes($row['judge_name'] ?? '')) . '\', '
            . $row['competition_id'] . ')">Edit</button>';
        echo '<button onclick="openDeleteJudgeModal(' . $row['judge_id'] . ', \'' . htmlspecialchars($row['judge_name'] ?? '') . '\')">Delete</button>';
        echo '</div></div></div>';
    }
} else {
    echo "<p>No judges found.</p>";
}
?>
</div>

<!-- Add Judge Modal -->
<div id="addJudgeModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;">
    <h3>Add Judge</h3>
    <form id="addJudgeForm" autocomplete="off">
      <label for="judge_name">Judge Name</label>
      <input type="text" id="judge_name" name="judge_name" required>
      <label for="competition_id">Assigned Competition</label>
      <select id="competition_id" name="competition_id" required>
        <option value="">Select Competition</option>
        <?php foreach($competitions as $comp): ?>
            <option value="<?= $comp['competition_id'] ?>"><?= htmlspecialchars($comp['competition_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="add-judge-error" style="color:#e53935;font-size:0.95em;margin:0.5em 0;"></div>
      <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Add Judge</button>
      <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeAddJudgeModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Edit Judge Modal -->
<div id="editJudgeModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;">
    <h3>Edit Judge</h3>
    <form id="editJudgeForm" autocomplete="off">
      <input type="hidden" id="edit_judge_id" name="edit_judge_id">
      <label for="edit_judge_name">Judge Name</label>
      <input type="text" id="edit_judge_name" name="edit_judge_name" required>
      <label for="edit_competition_id">Assigned Competition</label>
      <select id="edit_competition_id" name="edit_competition_id" required>
        <option value="">Select Competition</option>
        <?php foreach($competitions as $comp): ?>
            <option value="<?= $comp['competition_id'] ?>"><?= htmlspecialchars($comp['competition_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="edit-judge-error" style="color:#e53935;font-size:0.95em;margin:0.5em 0;"></div>
      <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Save Changes</button>
      <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeEditJudgeModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Delete Judge Modal -->
<div id="deleteJudgeModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;text-align:center;">
    <h3 style="color:#e53935;margin-bottom:1rem;">Delete Judge</h3>
    <p id="delete-judge-msg"></p>
    <div style="margin-top:1.5rem;">
      <button id="confirmDeleteJudgeBtn" class="btn" style="background:#e53935;">Delete</button>
      <button id="cancelDeleteJudgeBtn" class="btn" style="background:#888;margin-left:1rem;">Cancel</button>
    </div>
  </div>
</div>