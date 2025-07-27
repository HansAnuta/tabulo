const tabLinks = document.querySelectorAll('.tab-link');
const tabContent = document.getElementById('tab-content');
const fab = document.getElementById('fab');

function showAddEventModal() {
    document.getElementById('addEventModal').style.display = 'block';
}
function closeAddEventModal() {
    document.getElementById('addEventModal').style.display = 'none';
    document.getElementById('add-event-error').textContent = '';
    document.getElementById('addEventForm').reset();
}

function attachEventModalLogic() {
    const form = document.getElementById('addEventForm');
    if (form) {
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('events_tab.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeAddEventModal();
                    loadTab('events');
                } else {
                    document.getElementById('add-event-error').textContent = data.errors ? data.errors.join(', ') : 'Error';
                }
            });
        };
    }
    // Modal close on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('addEventModal');
        if (modal && event.target === modal) closeAddEventModal();
    };
}

function attachMenuLogic() {
    document.querySelectorAll('.menu-btn').forEach(btn => {
        btn.onclick = function(e) {
            e.stopPropagation();
            // Close all other menus
            document.querySelectorAll('.menu-dropdown').forEach(menu => menu.classList.remove('show'));
            // Open this one
            if (this.nextElementSibling) {
                this.nextElementSibling.classList.add('show');
            }
        };
    });
}

// Also close all menus on any click outside
window.addEventListener('click', function() {
    document.querySelectorAll('.menu-dropdown').forEach(menu => menu.classList.remove('show'));
});

function loadTab(tab) {
    tabContent.innerHTML = "Loading...";
    fetch(tab + '_tab.php')
        .then(res => res.text())
        .then(html => {
            tabContent.innerHTML = html;
            // Show FAB only for certain tabs
            if(['events','judges','participants'].includes(tab)) {
                fab.style.display = 'flex';
                if(tab === 'events') {
                    fab.onclick = showAddEventModal;
                    attachEventModalLogic();
                    attachDeleteEventModalLogic();
                    attachEditEventModalLogic();
                } else if(tab === 'judges') {
                    attachJudgeModalLogic();
                } else if(tab === 'participants') {
                    attachParticipantModalLogic();
                }
            } else {
                fab.style.display = 'none';
            }
            attachMenuLogic(); // <-- Add this line
        });
}

tabLinks.forEach(link => {
    link.onclick = function(e) {
        e.preventDefault();
        tabLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');
        loadTab(this.dataset.tab);
    }
});

// Initial load
loadTab('events');

let eventToDelete = null;

function openDeleteEventModal(eventId, eventName) {
    eventToDelete = eventId;
    document.getElementById('delete-event-msg').textContent = `Are you sure you want to delete the event "${eventName}"?`;
    document.getElementById('deleteEventModal').style.display = 'block';
}

function closeDeleteEventModal() {
    document.getElementById('deleteEventModal').style.display = 'none';
    eventToDelete = null;
}

function attachDeleteEventModalLogic() {
    const confirmBtn = document.getElementById('confirmDeleteEventBtn');
    const cancelBtn = document.getElementById('cancelDeleteEventBtn');
    if (confirmBtn && cancelBtn) {
        confirmBtn.onclick = function() {
            if (eventToDelete) {
                fetch('events_tab.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_event_id=' + encodeURIComponent(eventToDelete)
                })
                .then(res => res.json())
                .then(data => {
                    closeDeleteEventModal();
                    if (data.success) {
                        loadTab('events');
                    } else {
                        alert(data.errors ? data.errors.join(', ') : 'Delete failed.');
                    }
                });
            }
        };
        cancelBtn.onclick = closeDeleteEventModal;
    }
    // Modal close on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('deleteEventModal');
        if (modal && event.target === modal) closeDeleteEventModal();
    };
}

// Show the edit modal with pre-filled values
function openEditEventModal(id, name, date) {
    document.getElementById('edit_event_name').value = name;
    document.getElementById('edit_event_date').value = date;
    document.getElementById('edit_event_id').value = id;
    document.getElementById('edit-event-error').textContent = '';
    document.getElementById('editEventModal').style.display = 'block';
}

function closeEditEventModal() {
    document.getElementById('editEventModal').style.display = 'none';
    document.getElementById('edit-event-error').textContent = '';
    document.getElementById('editEventForm').reset();
}

function attachEditEventModalLogic() {
    const form = document.getElementById('editEventForm');
    if (form) {
        form.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('events_tab.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeEditEventModal();
                    loadTab('events');
                } else {
                    document.getElementById('edit-event-error').textContent = data.errors ? data.errors.join(', ') : 'Error';
                }
            });
        };
    }
    // Modal close on outside click
    window.onclick = function(event) {
        const modal = document.getElementById('editEventModal');
        if (modal && event.target === modal) closeEditEventModal();
    };
}

// Show Add Judge Modal
function showAddJudgeModal() {
    document.getElementById('addJudgeModal').style.display = 'block';
}
function closeAddJudgeModal() {
    document.getElementById('addJudgeModal').style.display = 'none';
    document.getElementById('add-judge-error').textContent = '';
    document.getElementById('addJudgeForm').reset();
}

// Show Edit Judge Modal
function openEditJudgeModal(id, name, competitionId) {
    document.getElementById('edit_judge_id').value = id;
    document.getElementById('edit_judge_name').value = name;
    document.getElementById('edit_competition_id').value = competitionId;
    document.getElementById('edit-judge-error').textContent = '';
    document.getElementById('editJudgeModal').style.display = 'block';
}
function closeEditJudgeModal() {
    document.getElementById('editJudgeModal').style.display = 'none';
    document.getElementById('edit-judge-error').textContent = '';
    document.getElementById('editJudgeForm').reset();
}

// Show Delete Judge Modal
let judgeToDelete = null;
function openDeleteJudgeModal(id, name) {
    judgeToDelete = id;
    document.getElementById('delete-judge-msg').textContent = `Are you sure you want to delete the judge "${name}"?`;
    document.getElementById('deleteJudgeModal').style.display = 'block';
}
function closeDeleteJudgeModal() {
    document.getElementById('deleteJudgeModal').style.display = 'none';
    judgeToDelete = null;
}

function attachJudgeModalLogic() {
    const fab = document.getElementById('fab');
    if (fab) fab.onclick = showAddJudgeModal;

    // Add
    const addForm = document.getElementById('addJudgeForm');
    if (addForm) {
        addForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(addForm);
            fetch('judges_tab.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeAddJudgeModal();
                    loadTab('judges');
                } else {
                    document.getElementById('add-judge-error').textContent = data.errors ? data.errors.join(', ') : 'Error';
                }
            });
        };
    }

    // Edit
    const editForm = document.getElementById('editJudgeForm');
    if (editForm) {
        editForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(editForm);
            fetch('judges_tab.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeEditJudgeModal();
                    loadTab('judges');
                } else {
                    document.getElementById('edit-judge-error').textContent = data.errors ? data.errors.join(', ') : 'Error';
                }
            });
        };
    }

    // Delete
    const confirmBtn = document.getElementById('confirmDeleteJudgeBtn');
    const cancelBtn = document.getElementById('cancelDeleteJudgeBtn');
    if (confirmBtn && cancelBtn) {
        confirmBtn.onclick = function() {
            if (judgeToDelete) {
                fetch('judges_tab.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_judge_id=' + encodeURIComponent(judgeToDelete)
                })
                .then(res => res.json())
                .then(data => {
                    closeDeleteJudgeModal();
                    if (data.success) {
                        loadTab('judges');
                    } else {
                        alert(data.errors ? data.errors.join(', ') : 'Delete failed.');
                    }
                });
            }
        };
        cancelBtn.onclick = closeDeleteJudgeModal;
    }
    // Modal close on outside click
    window.onclick = function(event) {
        ['addJudgeModal', 'editJudgeModal', 'deleteJudgeModal'].forEach(id => {
            const modal = document.getElementById(id);
            if (modal && event.target === modal) modal.style.display = 'none';
        });
    };
}

// Show Add Participant Modal
function showAddParticipantModal() {
    document.getElementById('addParticipantModal').style.display = 'block';
}
function closeAddParticipantModal() {
    document.getElementById('addParticipantModal').style.display = 'none';
    document.getElementById('add-participant-error').textContent = '';
    document.getElementById('addParticipantForm').reset();
}

// Show Edit Participant Modal
function openEditParticipantModal(id, name, competitionId) {
    document.getElementById('edit_participant_id').value = id;
    document.getElementById('edit_participant_name').value = name;
    document.getElementById('edit_competition_id').value = competitionId;
    document.getElementById('edit-participant-error').textContent = '';
    document.getElementById('editParticipantModal').style.display = 'block';
}
function closeEditParticipantModal() {
    document.getElementById('editParticipantModal').style.display = 'none';
    document.getElementById('edit-participant-error').textContent = '';
    document.getElementById('editParticipantForm').reset();
}

// Show Delete Participant Modal
let participantToDelete = null;
function openDeleteParticipantModal(id, name) {
    participantToDelete = id;
    document.getElementById('delete-participant-msg').textContent = `Are you sure you want to delete the participant "${name}"?`;
    document.getElementById('deleteParticipantModal').style.display = 'block';
}
function closeDeleteParticipantModal() {
    document.getElementById('deleteParticipantModal').style.display = 'none';
    participantToDelete = null;
}

function attachParticipantModalLogic() {
    const fab = document.getElementById('fab');
    if (fab) fab.onclick = showAddParticipantModal;

    // Add
    const addForm = document.getElementById('addParticipantForm');
    if (addForm) {
        addForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(addForm);
            fetch('participants_tab.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeAddParticipantModal();
                    loadTab('participants');
                } else {
                    document.getElementById('add-participant-error').textContent = data.errors ? data.errors.join(', ') : 'Error';
                }
            });
        };
    }

    // Edit
    const editForm = document.getElementById('editParticipantForm');
    if (editForm) {
        editForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(editForm);
            fetch('participants_tab.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeEditParticipantModal();
                    loadTab('participants');
                } else {
                    document.getElementById('edit-participant-error').textContent = data.errors ? data.errors.join(', ') : 'Error';
                }
            });
        };
    }

    // Delete
    const confirmBtn = document.getElementById('confirmDeleteParticipantBtn');
    const cancelBtn = document.getElementById('cancelDeleteParticipantBtn');
    if (confirmBtn && cancelBtn) {
        confirmBtn.onclick = function() {
            if (participantToDelete) {
                fetch('participants_tab.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_participant_id=' + encodeURIComponent(participantToDelete)
                })
                .then(res => res.json())
                .then(data => {
                    closeDeleteParticipantModal();
                    if (data.success) {
                        loadTab('participants');
                    } else {
                        alert(data.errors ? data.errors.join(', ') : 'Delete failed.');
                    }
                });
            }
        };
        cancelBtn.onclick = closeDeleteParticipantModal;
    }
    // Modal close on outside click
    window.onclick = function(event) {
        ['addParticipantModal', 'editParticipantModal', 'deleteParticipantModal'].forEach(id => {
            const modal = document.getElementById(id);
            if (modal && event.target === modal) modal.style.display = 'none';
        });
    };
}

// Show Add Competition Modal
function showAddCompetitionModal() {
    document.getElementById('addCompetitionModal').style.display = 'block';
}
function closeAddCompetitionModal() {
    document.getElementById('addCompetitionModal').style.display = 'none';
    document.getElementById('addCompetitionForm').reset();
}

// Show Edit Competition Modal
function openEditCompetitionModal(id, name, methodId) {
    document.getElementById('edit_competition_id').value = id;
    document.getElementById('edit_competition_name').value = name;
    document.getElementById('edit_judging_method_id').value = methodId;
    document.getElementById('editCompetitionModal').style.display = 'block';
}
function closeEditCompetitionModal() {
    document.getElementById('editCompetitionModal').style.display = 'none';
    document.getElementById('editCompetitionForm').reset();
}

function openDeleteCompetitionModal(id, name) {
    document.getElementById('delete_competition_id').value = id;
    document.getElementById('deleteCompetitionText').textContent = "Are you sure you want to delete '" + name + "'?";
    document.getElementById('deleteCompetitionModal').style.display = 'block';
}
function closeDeleteCompetitionModal() {
    document.getElementById('deleteCompetitionModal').style.display = 'none';
    document.getElementById('deleteCompetitionForm').reset();
}