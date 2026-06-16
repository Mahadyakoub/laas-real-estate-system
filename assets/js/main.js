/* =========================================================
   main.js
   Smart Rental System – Client & Admin Side Scripts
   ========================================================= */

/* ================= CONFIRM DELETE ================= */
function confirmDelete(message) {
    return confirm(message || "Are you sure you want to proceed?");
}

/* ================= FORM VALIDATION ================= */
function validateRegisterForm() {
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm_password").value;

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    if (password.length < 6) {
        alert("Password must be at least 6 characters long.");
        return false;
    }

    return true;
}

/* ================= LOGIN VALIDATION ================= */
function validateLoginForm() {
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    if (email === "" || password === "") {
        alert("Please enter both email and password.");
        return false;
    }
    return true;
}

/* ================= STATUS MESSAGE AUTO-HIDE ================= */
document.addEventListener("DOMContentLoaded", () => {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.display = "none";
        }, 4000);
    });
});

/* ================= DASHBOARD CARD HOVER EFFECT ================= */
document.addEventListener("DOMContentLoaded", () => {
    const cards = document.querySelectorAll(".stat-card, .property-card");

    cards.forEach(card => {
        card.addEventListener("mouseenter", () => {
            card.style.transform = "translateY(-5px)";
            card.style.transition = "0.3s";
        });

        card.addEventListener("mouseleave", () => {
            card.style.transform = "translateY(0)";
        });
    });
});

/* ================= MOBILE MENU TOGGLE (OPTIONAL) ================= */
function toggleMenu() {
    const sidebar = document.querySelector(".sidebar");
    if (sidebar) {
        sidebar.classList.toggle("open");
    }
}

/* ================= APPOINTMENT BUTTON CONFIRMATION ================= */
function confirmAppointment() {
    return confirm("Do you want to request an appointment for this property?");
}

/* ================= ADMIN ACTION CONFIRM ================= */
function confirmAdminAction(action) {
    return confirm("Are you sure you want to " + action + " this request?");
}

/* ================= SIMPLE NOTIFICATION ================= */
function showNotification(message) {
    alert(message);
}

/* =========================================================
   ADDITIVE FEATURE — ADMIN NOTIFICATION BELL (NON-DESTRUCTIVE)
   ========================================================= */

/* ================= TOGGLE NOTIFICATION DROPDOWN ================= */
function toggleNotifications() {
    const dropdown = document.getElementById("notificationDropdown");
    if (dropdown) {
        dropdown.classList.toggle("show");
    }
}

/* ================= CLOSE DROPDOWN ON OUTSIDE CLICK ================= */
document.addEventListener("click", function (event) {
    const dropdown = document.getElementById("notificationDropdown");
    const bell = document.querySelector(".notification-btn");

    if (!dropdown || !bell) return;

    if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove("show");
    }
});

/* ================= ESC KEY CLOSE ================= */
document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        const dropdown = document.getElementById("notificationDropdown");
        if (dropdown) {
            dropdown.classList.remove("show");
        }
    }
});
