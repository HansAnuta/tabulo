<?php
require_once '../../db-connector.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? 0;
$competition_id = isset($_GET['competition_id']) ? (int) $_GET['competition_id'] : 0;
if (!$competition_id || !$admin_id) {
    echo "Access denied or invalid competition.";
    exit;
}

// Get event_id
$stmt = $conn->prepare("SELECT e.event_id FROM competitions c JOIN events e ON c.event_id = e.event_id WHERE c.competition_id = ? AND e.admin_id = ?");
$stmt->bind_param("ii", $competition_id, $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$comp = $result->fetch_assoc();
$event_id = $comp['event_id'] ?? 0;

if (!$event_id) {
    echo "Invalid competition or unauthorized access.";
    exit;
}

// Get categories
$stmt = $conn->prepare("SELECT * FROM categories WHERE competition_id = ?");
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Handle AJAX
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_criteria') {
    $cat_id = (int) $_GET['category_id'];
    $stmt = $conn->prepare("SELECT * FROM criteria WHERE category_id = ? ORDER BY criteria_order ASC");
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'add':
            $stmt = $conn->prepare("INSERT INTO criteria (category_id, criteria_order, criteria_name, weight, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iisd", $data['category_id'], $data['order'], $data['name'], $data['weight']);
            $stmt->execute();
            break;
        case 'edit':
            $stmt = $conn->prepare("UPDATE criteria SET criteria_order = ?, criteria_name = ?, weight = ?, updated_at = NOW() WHERE criteria_id = ?");
            $stmt->bind_param("ssdi", $data['order'], $data['name'], $data['weight'], $data['criteria_id']);
            $stmt->execute();
            break;
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM criteria WHERE criteria_id = ?");
            $stmt->bind_param("i", $data['criteria_id']);
            $stmt->execute();
            break;
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Weighted Averaging - Criteria</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; }
        h3 { margin-top: 40px; }
        .btn, .add-btn {
            background-color: #007BFF; color: white;
            padding: 8px 14px; border: none;
            border-radius: 4px; cursor: pointer;
            margin-top: 10px;
        }
        .btn:hover, .add-btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>

<a href="../competitions.php?event_id=<?= $event_id ?>" class="btn">← Back to Competitions</a>
<h2>Weighted Averaging - Criteria Management</h2>

<?php foreach ($categories as $cat): ?>
    <div class="category-section">
        <h3><?= htmlspecialchars($cat['category_name']) ?></h3>
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Name</th>
                    <th>Weight</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody data-category-id="<?= $cat['category_id'] ?>" class="criteria-table"></tbody>
        </table>
        <button class="add-btn" onclick="showAddForm(<?= $cat['category_id'] ?>)">Add Criterion</button>
    </div>
<?php endforeach; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".criteria-table").forEach(table => {
        const catId = table.dataset.categoryId;
        fetch(`weighted_averaging.php?action=fetch_criteria&category_id=${catId}`)
            .then(res => res.json())
            .then(data => {
                let totalWeight = 0;
                data.forEach(row => {
                    totalWeight += parseFloat(row.weight);
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${row.criteria_order}</td>
                        <td>${row.criteria_name}</td>
                        <td>${row.weight}</td>
                        <td>
                            <button onclick="editCriterion(${row.criteria_id})">Edit</button>
                            <button onclick="deleteCriterion(${row.criteria_id})">Delete</button>
                        </td>
                    `;
                    table.appendChild(tr);
                });

                if (totalWeight !== 100) {
                    const warn = document.createElement("tr");
                    warn.innerHTML = `<td colspan="4" style="color:red;font-weight:bold;">⚠ Total weight = ${totalWeight}%. Should be 100%.</td>`;
                    table.appendChild(warn);
                }
            });
    });
});

function showAddForm(catId) {
    const name = prompt("Enter criteria name:");
    const order = prompt("Enter order:");
    const weight = prompt("Enter weight (%):");

    if (!name || isNaN(order) || isNaN(weight)) return alert("Invalid input.");

    fetch("weighted_averaging.php?action=add", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({category_id: catId, name, order, weight})
    }).then(() => location.reload());
}

function editCriterion(id) {
    const name = prompt("New name:");
    const order = prompt("New order:");
    const weight = prompt("New weight (%):");
    if (!name || isNaN(order) || isNaN(weight)) return alert("Invalid input.");

    fetch("weighted_averaging.php?action=edit", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({criteria_id: id, name, order, weight})
    }).then(() => location.reload());
}

function deleteCriterion(id) {
    if (!confirm("Delete this criterion?")) return;

    fetch("weighted_averaging.php?action=delete", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({criteria_id: id})
    }).then(() => location.reload());
}
</script>
</body>
</html>
