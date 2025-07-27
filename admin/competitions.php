<?php
include '../db-connector.php';
session_start();
$admin_id = $_SESSION['admin_id'] ?? 0;

// Handle add competition POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['competition_name'], $_POST['judging_method_id'])) {
    $competition_name = trim($_POST['competition_name']);
    $judging_method_id = intval($_POST['judging_method_id']);
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $errors = [];

    // Validate
    if (!$competition_name) $errors[] = "Competition name required.";
    if (!$judging_method_id) $errors[] = "Judging method required.";
    // Check event ownership
    $event = $conn->query("SELECT event_id FROM events WHERE event_id = $event_id AND admin_id = $admin_id")->fetch_assoc();
    if (!$event) $errors[] = "Invalid event or access denied.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO competitions (event_id, competition_name, judging_method_id, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("isi", $event_id, $competition_name, $judging_method_id);
        $stmt->execute();
        header("Location: competitions.php?event_id=$event_id");
        exit;
    }
}

// Handle edit competition POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_competition_id'])) {
    $edit_id = intval($_POST['edit_competition_id']);
    $edit_name = trim($_POST['edit_competition_name']);
    $edit_method = intval($_POST['edit_judging_method_id']);
    $edit_errors = [];

    if (!$edit_name) $edit_errors[] = "Competition name required.";
    if (!$edit_method) $edit_errors[] = "Judging method required.";

    $comp = $conn->query("SELECT c.competition_id, c.event_id FROM competitions c JOIN events e ON c.event_id = e.event_id WHERE c.competition_id = $edit_id AND e.admin_id = $admin_id")->fetch_assoc();
    
    if (!$comp) $edit_errors[] = "Invalid competition or access denied.";
    
    if (empty($edit_errors)) {
        $stmt = $conn->prepare("UPDATE competitions SET competition_name=?, judging_method_id=? WHERE competition_id=?");
        $stmt->bind_param("sii", $edit_name, $edit_method, $edit_id);
        $stmt->execute();

        $event_id = $comp['event_id']; // 🔧 Set this before redirect
        header("Location: competitions.php?event_id=$event_id");
        exit;
    }
}

// Handle delete competition POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_competition_id'])) {
    $delete_id = intval($_POST['delete_competition_id']);
    $comp = $conn->query("SELECT c.competition_id, c.event_id FROM competitions c JOIN events e ON c.event_id = e.event_id WHERE c.competition_id = $delete_id AND e.admin_id = $admin_id")->fetch_assoc();
    if ($comp) {
        $conn->query("DELETE FROM competitions WHERE competition_id = $delete_id");
        $event_id = $comp['event_id']; // 🔧 this was missing
    }
    header("Location: competitions.php?event_id=$event_id");
    exit;
}

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if (!$event_id) {
    echo "Invalid event.";
    exit;
}

// Get event info and check ownership
$event = $conn->query("SELECT event_name, event_date FROM events WHERE event_id = $event_id AND admin_id = $admin_id")->fetch_assoc();
if (!$event) {
    echo "Event not found or access denied.";
    exit;
}

// Get competitions
$comps = $conn->query("SELECT c.*, jm.method_name FROM competitions c
    JOIN events e ON c.event_id = e.event_id
    JOIN judging_methods jm ON c.judging_method_id = jm.judging_method_id
    WHERE e.admin_id = $admin_id AND c.event_id = $event_id
    ORDER BY c.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event['event_name']) ?> Competitions</title>
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <header>
        <a href="admin_dashboard.php" class="btn header-btn" style="margin-right:1em;">Back</a>
        <h1 style="margin:0;font-size:1.5rem;display:inline;">
            <?= htmlspecialchars($event['event_name']) ?> Competitions (<?= htmlspecialchars($event['event_date']) ?>)
        </h1>
    </header>
    <main>
        <div id="competitions-list">
            <?php
            if ($comps->num_rows > 0) {
                while($row = $comps->fetch_assoc()) {
                    echo '<div class="item-row" style="cursor:pointer;position:relative;" onclick="window.location.href=\'competition_view.php?competition_id=' . $row['competition_id'] . '\'">';
                    echo '<span class="item-label">' . htmlspecialchars($row['competition_name']) . 
                         ' <span style="color:#888;">(' . htmlspecialchars($row['method_name']) . ')</span></span>';
                    // Menu button (stops propagation so it doesn't trigger the item click)
                    echo '<div style="position:absolute;right:1em;top:50%;transform:translateY(-50%);">';
                    echo '<button class="menu-btn" onclick="event.stopPropagation();this.nextElementSibling.classList.toggle(\'show\');">&#8942;</button>';
                    echo '<div class="menu-dropdown">';
                    echo '<button onclick="event.stopPropagation();openEditCompetitionModal('
                        . $row['competition_id'] . ', \''
                        . htmlspecialchars(addslashes($row['competition_name'])) . '\', '
                        . $row['judging_method_id'] . ')">Edit</button>';
                    echo '<button onclick="event.stopPropagation();openDeleteCompetitionModal('
                        . $row['competition_id'] . ', \''
                        . htmlspecialchars(addslashes($row['competition_name'])) . '\')">Delete</button>';
                    echo '</div></div>';
                    echo '</div>';
                }
            } else {
                echo "<p>No competitions found for this event.</p>";
            }
            ?>
        </div>
        <button id="fab" class="fab" title="Add" style="position:fixed;bottom:2em;right:2em;" onclick="showAddCompetitionModal()">+</button>
    </main>
    <!-- Add Competition Modal -->
    <div id="addCompetitionModal" class="modal" style="display:none;">
      <div class="modal-content" style="max-width:350px;">
        <h3>Add Competition</h3>
        <form id="addCompetitionForm" method="post" autocomplete="off">
          <input type="hidden" name="event_id" value="<?= $event_id ?>">
          <label for="competition_name">Competition Name</label>
          <input type="text" id="competition_name" name="competition_name" required>
          <label for="judging_method_id">Judging Method</label>
          <select id="judging_method_id" name="judging_method_id" required>
            <option value="">Select Judging Method</option>
            <option value="1">Ranking</option>
            <option value="2">Simple Averaging</option>
            <option value="3">Weighted Averaging</option>
            <option value="4">Segmented Judging</option>
            <option value="5">Elimination and Bracketing</option>
          </select>
          <?php if (!empty($errors)): ?>
            <div style="color:#e53935;font-size:0.95em;margin:0.5em 0;">
              <?= implode('<br>', $errors) ?>
            </div>
          <?php endif; ?>
          <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Add Competition</button>
          <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeAddCompetitionModal()">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Edit Competition Modal -->
    <div id="editCompetitionModal" class="modal" style="display:none;">
      <div class="modal-content" style="max-width:350px;">
        <h3>Edit Competition</h3>
        <form id="editCompetitionForm" method="post" autocomplete="off">
          <input type="hidden" id="edit_competition_id" name="edit_competition_id">
          <label for="edit_competition_name">Competition Name</label>
          <input type="text" id="edit_competition_name" name="edit_competition_name" required>
          <label for="edit_judging_method_id">Judging Method</label>
          <select id="edit_judging_method_id" name="edit_judging_method_id" required>
            <option value="">Select Judging Method</option>
            <option value="1">Ranking</option>
            <option value="2">Simple Averaging</option>
            <option value="3">Weighted Averaging</option>
            <option value="4">Segmented Judging</option>
            <option value="5">Elimination and Bracketing</option>
          </select>
          <?php if (!empty($edit_errors)): ?>
            <div style="color:#e53935;font-size:0.95em;margin:0.5em 0;">
              <?= implode('<br>', $edit_errors) ?>
            </div>
          <?php endif; ?>
          <button type="submit" class="btn" style="width:100%;margin-top:1rem;">Save Changes</button>
          <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeEditCompetitionModal()">Cancel</button>
        </form>
      </div>
    </div>

    <!-- Delete Competition Modal -->
    <div id="deleteCompetitionModal" class="modal" style="display:none;">
      <div class="modal-content" style="max-width:350px;text-align:center;">
        <h3>Delete Competition</h3>
        <form id="deleteCompetitionForm" method="post">
          <input type="hidden" id="delete_competition_id" name="delete_competition_id">
          <p id="deleteCompetitionText"></p>
          <button type="submit" class="btn" style="width:100%;margin-top:1rem;background:#e53935;">Delete</button>
          <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeDeleteCompetitionModal()">Cancel</button>
        </form>
      </div>
    </div>
    <script src="admin_dashboard.js"></script>
</body>
</html>