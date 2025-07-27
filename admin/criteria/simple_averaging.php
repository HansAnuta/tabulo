<?php
require_once '../../db-connector.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? 0;
$competition_id = isset($_GET['competition_id']) ? (int) $_GET['competition_id'] : 0;
if (!$competition_id || !$admin_id) {
    echo "Access denied or invalid competition.";
    exit;
}

// Get event_id and competition info for header and back button
$stmt = $conn->prepare("SELECT c.competition_name, jm.method_name, e.event_id FROM competitions c JOIN judging_methods jm ON c.judging_method_id = jm.judging_method_id JOIN events e ON c.event_id = e.event_id WHERE c.competition_id = ? AND e.admin_id = ?");
$stmt->bind_param("ii", $competition_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$comp = $result->fetch_assoc();
$event_id = $comp['event_id'] ?? 0;
$competition_name = $comp['competition_name'] ?? '';
$method_name = $comp['method_name'] ?? '';
if (!$event_id) {
    echo "Invalid competition or unauthorized access.";
    exit;
}

// Ensure default category exists
$stmt = $conn->prepare("SELECT category_id FROM categories WHERE competition_id = ?");
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$res = $stmt->get_result();
$category = $res->fetch_assoc();

if (!$category) {
    $stmt = $conn->prepare("INSERT INTO categories (competition_id, category_name, created_at) VALUES (?, 'General', NOW())");
    $stmt->bind_param("i", $competition_id);
    $stmt->execute();
    $category_id = $stmt->insert_id;
} else {
    $category_id = $category['category_id'];
}

// Handle save criteria (bulk insert)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ($_GET['action'] ?? '');

    if ($action === 'save_bulk') {
        $criteria = $_POST['criteria'] ?? [];
        $weights = $_POST['weights'] ?? [];

        if (!empty($criteria) && count($criteria) === count($weights)) {
            $stmt = $conn->prepare("DELETE FROM criteria WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();

            foreach ($criteria as $index => $name) {
                $weight = floatval($weights[$index]);
                $order = $index + 1;
                $stmt = $conn->prepare("INSERT INTO criteria (category_id, criteria_order, criteria_name, weight, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iisd", $category_id, $order, $name, $weight);
                $stmt->execute();
            }
        }
        header("Location: simple_averaging.php?competition_id=$competition_id");
        exit;
    } elseif ($action === 'delete_criteria') {
        $stmt = $conn->prepare("DELETE FROM criteria WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        header("Location: simple_averaging.php?competition_id=$competition_id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Averaging - Criteria</title>
    <link rel="stylesheet" href="../admin_style.css">
    <style>
        .fab {
            position: fixed;
            bottom: 2em;
            right: 2em;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #007BFF;
            color: #fff;
            font-size: 1.5em;
            text-align: center;
            line-height: 48px;
            border: none;
            cursor: pointer;
        }
        .fab:hover {
            background: #0056b3;
        }
        table.criteria-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.criteria-table th, table.criteria-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }
        table.criteria-table tfoot td {
            font-weight: bold;
            background: #f9f9f9;
        }
        .header-actions {
            margin-top: 1em;
        }
    </style>
</head>
<body>
<a href="../competitions.php?event_id=<?= $event_id ?>" class="btn">&larr; Back to Competitions</a>

<div style="text-align:center; margin-top: 1em;">
  <h1>Criteria</h1>
  <h3><?= htmlspecialchars($competition_name) ?> (<?= htmlspecialchars($method_name) ?>)</h3>
</div>

<div class="header-actions">
    <button class="btn" onclick="openAddCriteriaModal(true)">Edit Criteria</button>
    <button class="btn" style="background:#e53935;" onclick="openDeleteCompetitionModal()">Delete Criteria</button>
</div>

<table class="criteria-table">
    <thead>
        <tr>
            <th>Description</th>
            <th>Percentage</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $conn->prepare("SELECT * FROM criteria WHERE category_id = ? ORDER BY criteria_order ASC");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $totalWeight = 0;
        $existingCriteria = [];
        if ($res->num_rows > 0):
            while ($crit = $res->fetch_assoc()):
                $totalWeight += floatval($crit['weight']);
                $existingCriteria[] = $crit;
        ?>
            <tr>
                <td><?= htmlspecialchars($crit['criteria_name']) ?></td>
                <td><?= htmlspecialchars($crit['weight']) ?>%</td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="2" style="text-align:center; color:#666;">No criteria set for this competition yet.</td></tr>
        <?php endif; ?>
    </tbody>
    <?php if ($res->num_rows > 0): ?>
    <tfoot>
        <tr>
            <td>Total</td>
            <td><?= $totalWeight ?>%</td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>

<!-- Add/Edit Criteria Floating Button -->
<button id="fab" class="fab" title="Add Criteria" onclick="openAddCriteriaModal(false)">+</button>

<!-- Add/Edit Criteria Modal -->
<div id="addCriteriaModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:400px;">
    <h3 id="criteriaModalTitle">Add Criteria</h3>
    <form id="addCriteriaForm" method="post">
      <input type="hidden" name="action" value="save_bulk">
      <div id="criteriaInputs">
        <div class="criteria-group">
          <label>Description</label>
          <input type="text" name="criteria[]" required>
          <label>Percentage</label>
          <input type="number" name="weights[]" min="1" max="100" required>
        </div>
      </div>
      <button type="button" class="btn" onclick="addAnotherCriteria()">Add Another Criteria</button>
      <button type="submit" class="btn">Save</button>
      <button type="button" class="btn" style="background:#888;" onclick="closeAddCriteriaModal()">Cancel</button>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteCompetitionModal" class="modal" style="display:none;">
  <div class="modal-content" style="max-width:350px;text-align:center;">
    <h3>Delete Criteria</h3>
    <form method="post">
      <input type="hidden" name="action" value="delete_criteria">
      <p>This will delete the entire competition and all its criteria. Are you sure?</p>
      <button type="submit" class="btn" style="width:100%;margin-top:1rem;background:#e53935;">Delete</button>
      <button type="button" class="btn" style="width:100%;margin-top:0.5rem;background:#888;" onclick="closeDeleteCompetitionModal()">Cancel</button>
    </form>
  </div>
</div>

<script>
function openAddCriteriaModal(isEdit = false) {
    const modal = document.getElementById("addCriteriaModal");
    const title = document.getElementById("criteriaModalTitle");
    const container = document.getElementById("criteriaInputs");
    modal.style.display = "block";
    title.textContent = isEdit ? "Edit Criteria" : "Add Criteria";
    container.innerHTML = '';

    if (isEdit && <?= json_encode(!empty($existingCriteria)) ?>) {
        const criteria = <?= json_encode($existingCriteria) ?>;
        criteria.forEach(c => {
            const group = document.createElement("div");
            group.className = "criteria-group";
            group.innerHTML = `
              <label>Description</label>
              <input type="text" name="criteria[]" value="${c.criteria_name}" required>
              <label>Percentage</label>
              <input type="number" name="weights[]" value="${c.weight}" min="1" max="100" required>
            `;
            container.appendChild(group);
        });
    } else {
        addAnotherCriteria();
    }
}

function closeAddCriteriaModal() {
    document.getElementById("addCriteriaModal").style.display = "none";
}

function addAnotherCriteria() {
    const group = document.createElement("div");
    group.className = "criteria-group";
    group.innerHTML = `
      <label>Description</label>
      <input type="text" name="criteria[]" required>
      <label>Percentage</label>
      <input type="number" name="weights[]" min="1" max="100" required>
    `;
    document.getElementById("criteriaInputs").appendChild(group);
}

function openDeleteCompetitionModal() {
    document.getElementById("deleteCompetitionModal").style.display = "block";
}

function closeDeleteCompetitionModal() {
    document.getElementById("deleteCompetitionModal").style.display = "none";
}
</script>
</body>
</html>
