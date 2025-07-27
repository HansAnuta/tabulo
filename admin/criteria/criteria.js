document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".criteria-table").forEach(table => {
        const categoryId = table.dataset.categoryId;
        fetch(`handlers/fetch_criteria.php?category_id=${categoryId}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(row => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${row.criteria_order}</td>
                        <td>${row.criteria_name}</td>
                        <td>${row.weight}</td>
                        <td>
                            <button onclick="editCriterion(${row.criteria_id})">Edit</button>
                            <button onclick="deleteCriterion(${row.criteria_id}, ${categoryId})">Delete</button>
                        </td>
                    `;
                    table.appendChild(tr);
                });
            });
    });
});

function showAddForm(categoryId) {
    const name = prompt("Enter criteria name:");
    const order = prompt("Enter order:");
    const weight = prompt("Enter weight:");
    if (!name || !order || !weight) return;

    fetch("handlers/add_criteria.php", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({category_id: categoryId, name, order, weight})
    }).then(() => location.reload());
}

function editCriterion(id) {
    const name = prompt("New name:");
    const order = prompt("New order:");
    const weight = prompt("New weight:");
    if (!name || !order || !weight) return;

    fetch("handlers/edit_criteria.php", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({criteria_id: id, name, order, weight})
    }).then(() => location.reload());
}

function deleteCriterion(id, categoryId) {
    if (!confirm("Are you sure?")) return;

    fetch("handlers/delete_criteria.php", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({criteria_id: id})
    }).then(() => location.reload());
}
