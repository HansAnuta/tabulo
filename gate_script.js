// Tab switching and floating add button logic
document.addEventListener('DOMContentLoaded', function() {
    const fab = document.getElementById('fab-add');
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    const tabMap = {
        'tab-events': { text: 'Add Event', class: 'add-event', show: true },
        'tab-judges': { text: 'Add Judge', class: 'add-judge', show: true },
        'tab-participants': { text: 'Add Participant', class: 'add-participant', show: true },
        'tab-results': { text: '', class: '', show: false }
    };

    function updateFab(tabId) {
        const config = tabMap[tabId];
        if (config && fab) {
            fab.textContent = config.text;
            fab.className = 'fixed-add-btn ' + (config.class || '');
            fab.style.display = config.show ? 'block' : 'none';
        }
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Remove 'active' from all tabs and contents
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Add 'active' to clicked tab and corresponding content
            this.classList.add('active');
            const tabId = this.id.replace('tab-', '');
            document.getElementById(tabId).classList.add('active');

            // Update FAB
            updateFab(this.id);
        });
    });

    // Initial FAB state
    updateFab('tab-events');

    // Show modal when Add Event is clicked
    const addEventBtn = document.querySelector('.add-event');
    if (addEventBtn) {
        addEventBtn.onclick = function() {
            document.getElementById('eventModal').style.display = 'block';
        };
    }

    // Handle form submission via AJAX for events
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('add-event.php', {
                method: 'POST',
                body: formData
            }).then(res => res.text()).then(data => {
                if (data.trim() === "success") {
                    alert("Event added!");
                    closeModal('eventModal');
                    this.reset();
                } else {
                    alert("Error adding event.");
                }
            });
        };
    }
});

// Login modal logic (landing page)
const loginBtn = document.querySelector('.login-btn');
if (loginBtn) {
    loginBtn.onclick = function() {
        document.getElementById('loginModal').style.display = 'block';
    };
}

// Close modal function
window.closeModal = function(id) {
    document.getElementById(id).style.display = 'none';
};

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('loginModal');
    if (modal && event.target === modal) modal.style.display = "none";
};

// Login form submission (landing page)
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.onsubmit = function(e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);

        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                document.getElementById('login-error').textContent = data.error;
                form.querySelector('[name="username"]').value = '';
                form.querySelector('[name="password"]').value = '';
                form.querySelector('[name="username"]').focus();
            }
        });
    };
}

// Signup modal logic
const signupBtn = document.querySelector('.signup-btn');
if (signupBtn) {
    signupBtn.onclick = function() {
        document.getElementById('signupModal').style.display = 'block';
    };
}

// Signup form submission
const signupForm = document.getElementById('signupForm');
if (signupForm) {
    signupForm.onsubmit = function(e) {
        e.preventDefault();
        const form = this;
        const password = form.querySelector('[name="password"]').value;
        const password2 = form.querySelector('[name="password2"]').value;
        const signupError = document.getElementById('signup-error');

        // Reset color to red for errors
        signupError.style.color = "#e53935";

        if (password !== password2) {
            signupError.textContent = "Passwords do not match.";
            form.querySelector('[name="password"]').value = '';
            form.querySelector('[name="password2"]').value = '';
            form.querySelector('[name="password"]').focus();
            return;
        }

        const formData = new FormData(form);

        fetch('signup.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                signupError.style.color = "#2e7d32";
                signupError.textContent = "Account Created Successfully";
                form.reset();
            } else {
                signupError.style.color = "#e53935";
                signupError.textContent = data.error;
            }
        })
        .catch(() => {
            signupError.style.color = "#e53935";
            signupError.textContent = "An unexpected error occurred.";
        });
    };
}

function toggleMenu(btn) {
    // Close any open menus first
    document.querySelectorAll('.menu-container.show').forEach(el => {
        if (el !== btn.parentElement) el.classList.remove('show');
    });
    // Toggle this menu
    btn.parentElement.classList.toggle('show');
}

// Optional: Close menu when clicking outside
document.addEventListener('click', function(e) {
    document.querySelectorAll('.menu-container.show').forEach(el => {
        if (!el.contains(e.target)) el.classList.remove('show');
    });
});

// Additional logic to handle redirects after login/signup
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');

    if (loginForm) {
        loginForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.redirect) {
                    // Only allow redirect to internal dashboard
                    if (data.redirect === 'admin/admin_dashboard.php') {
                        window.location.href = data.redirect;
                    }
                } else {
                    document.getElementById('login-error').textContent = data.error;
                }
            });
        };
    }

    if (signupForm) {
        signupForm.onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('signup.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Signup successful! Please log in.");
                    closeModal('signupModal');
                } else {
                    document.getElementById('signup-error').textContent = data.error;
                }
            });
        };
    }
});