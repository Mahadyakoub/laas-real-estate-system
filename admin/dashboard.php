<?php
session_start();
require_once("../config/db.php");

/* ================= ADMIN AUTH ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/* ================= SAFE COUNT FUNCTION ================= */
function getCount($conn, $sql) {
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return (int)$total;
}

/* ================= KPI COUNTS ================= */
$totalUsers        = getCount($conn, "SELECT COUNT(*) FROM users");
$totalProperties   = getCount($conn, "SELECT COUNT(*) FROM properties");
$totalAppointments = getCount($conn, "SELECT COUNT(*) FROM appointments");
$pending           = getCount($conn, "SELECT COUNT(*) FROM appointments WHERE status = 'Pending'");
$approved          = getCount($conn, "SELECT COUNT(*) FROM appointments WHERE status = 'Approved'");
$rejected          = getCount($conn, "SELECT COUNT(*) FROM appointments WHERE status = 'Rejected'");

/* ================= PENDING NOTIFICATIONS ================= */
$adminNotifications = false;
$notifStmt = mysqli_prepare(
    $conn,
    "SELECT
        u.name AS client,
        p.title AS property,
        a.appointment_date
     FROM appointments a
     JOIN users u ON a.user_id = u.user_id
     JOIN properties p ON a.property_id = p.property_id
     WHERE a.status = 'Pending'
     ORDER BY a.appointment_date DESC
     LIMIT 5"
);

if ($notifStmt) {
    mysqli_stmt_execute($notifStmt);
    $adminNotifications = mysqli_stmt_get_result($notifStmt);
}

/* ================= RECENT APPOINTMENTS ================= */
$appointments = false;
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        u.name AS client,
        p.title AS property,
        a.appointment_date,
        a.status
     FROM appointments a
     JOIN users u ON a.user_id = u.user_id
     JOIN properties p ON a.property_id = p.property_id
     ORDER BY a.appointment_date DESC
     LIMIT 6"
);

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $appointments = mysqli_stmt_get_result($stmt);
}

/* ================= MONTHLY CHART DATA ================= */
$chartLabels = [];
$chartValues = [];

for ($i = 5; $i >= 0; $i--) {
    $monthLabel = date("M", strtotime("-$i month"));
    $monthNum   = (int)date("m", strtotime("-$i month"));
    $yearNum    = (int)date("Y", strtotime("-$i month"));

    $monthStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*)
         FROM appointments
         WHERE MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?"
    );

    $count = 0;

    if ($monthStmt) {
        mysqli_stmt_bind_param($monthStmt, "ii", $monthNum, $yearNum);
        mysqli_stmt_execute($monthStmt);
        mysqli_stmt_bind_result($monthStmt, $count);
        mysqli_stmt_fetch($monthStmt);
        mysqli_stmt_close($monthStmt);
    }

    $chartLabels[] = $monthLabel;
    $chartValues[] = (int)$count;
}

$hasChartData = array_sum($chartValues) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Laas Real Estate</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=7">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="dashboard-body">

<aside class="sidebar" id="sidebar">
    <div class="logo">
        <img src="../assets/images/logo.jpg" alt="Laas Real Estate">
        <h2>Laas Admin</h2>
    </div>

    <nav class="menu">
        <a href="dashboard.php" class="active">
            <i class="fa-solid fa-chart-line"></i><span>Dashboard</span>
        </a>
        <a href="manage_properties.php">
            <i class="fa-solid fa-building"></i><span>Properties</span>
        </a>
        <a href="manage_appointments.php">
            <i class="fa-solid fa-calendar-check"></i><span>Appointments</span>
        </a>
        <a href="view_feedback.php">
            <i class="fa-solid fa-comments"></i><span>Feedback</span>
        </a>
        <a href="reports.php">
            <i class="fa-solid fa-file-lines"></i><span>Reports</span>
        </a>
        <a href="../auth/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </nav>
</aside>

<main class="main">

    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div>
                <h1><i class="fa-solid fa-gauge-high"></i> Admin Dashboard</h1>
                <p>Overview of platform activity and performance</p>
            </div>
        </div>

        <div class="topbar-right">
            <div class="notification-wrapper">
                <button class="notification-btn" onclick="toggleNotifications()">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($pending > 0): ?>
                        <span class="notification-badge"><?= $pending ?></span>
                    <?php endif; ?>
                </button>

                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">Notifications</div>

                    <div class="notification-list">
                        <?php if ($adminNotifications && mysqli_num_rows($adminNotifications) > 0): ?>
                            <?php while ($n = mysqli_fetch_assoc($adminNotifications)): ?>
                                <a href="manage_appointments.php" class="notification-item">
                                    <div class="notif-icon">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </div>

                                    <div class="notif-content">
                                        <div class="notif-title">
                                            <?= htmlspecialchars($n['client']) ?>
                                        </div>

                                        <div class="notif-text">
                                            Appointment request for
                                            <strong><?= htmlspecialchars($n['property']) ?></strong>
                                        </div>

                                        <div class="notif-date">
                                            <?= date("d M Y", strtotime($n['appointment_date'])) ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="notification-empty">No new notifications</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="admin-profile">
                <span class="status-dot online"></span>
                <img src="../assets/images/admin.jpg" alt="Admin">
                <span>Administrator</span>
            </div>
        </div>
    </div>

    <section class="stats">
        <div class="stat-card">
            <div class="kpi-icon users">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <h3><?= $totalUsers ?></h3>
                <span>Total Users</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="kpi-icon properties">
                <i class="fa-solid fa-house"></i>
            </div>
            <div>
                <h3><?= $totalProperties ?></h3>
                <span>Total Properties</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="kpi-icon appointments">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <h3><?= $totalAppointments ?></h3>
                <span>Total Appointments</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="kpi-icon pending">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <h3><?= $pending ?></h3>
                <span>Pending Requests</span>
            </div>
        </div>
    </section>

    <section class="charts">
        <div class="chart-card">
            <h3><i class="fa-solid fa-chart-area"></i> Appointment Trends</h3>
            <?php if ($hasChartData): ?>
                <canvas id="appointmentsChart"></canvas>
            <?php else: ?>
                <div class="empty-state">No appointment trend data available yet.</div>
            <?php endif; ?>
        </div>

        <div class="chart-card">
            <h3><i class="fa-solid fa-chart-pie"></i> Appointment Status</h3>
            <?php if (($approved + $pending + $rejected) > 0): ?>
                <canvas id="systemChart"></canvas>
            <?php else: ?>
                <div class="empty-state">No appointment status data available yet.</div>
            <?php endif; ?>
        </div>
    </section>

    <section class="table-section">
        <h2><i class="fa-solid fa-rotate"></i> Recent Appointment Requests</h2>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Property</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments && mysqli_num_rows($appointments) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($appointments)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['client']) ?></td>
                                <td><?= htmlspecialchars($row['property']) ?></td>
                                <td><?= date("d M Y", strtotime($row['appointment_date'])) ?></td>
                                <td>
                                    <span class="status <?= strtolower($row['status']) ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="empty-row">No appointment records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
}

function toggleNotifications() {
    document.getElementById('notificationDropdown').classList.toggle('show');
}

document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('notificationDropdown');
    if (!e.target.closest('.notification-wrapper')) {
        dropdown.classList.remove('show');
    }
});

const appointmentLabels = <?= json_encode($chartLabels) ?>;
const appointmentValues = <?= json_encode($chartValues) ?>;
const hasTrendData = <?= $hasChartData ? 'true' : 'false' ?>;
const hasStatusData = <?= ($approved + $pending + $rejected) > 0 ? 'true' : 'false' ?>;

if (hasTrendData) {
    new Chart(document.getElementById('appointmentsChart'), {
        type: 'line',
        data: {
            labels: appointmentLabels,
            datasets: [{
                label: 'Appointments',
                data: appointmentValues,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.16)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

if (hasStatusData) {
    new Chart(document.getElementById('systemChart'), {
        type: 'doughnut',
        data: {
            labels: ['Approved', 'Pending', 'Rejected'],
            datasets: [{
                data: [<?= $approved ?>, <?= $pending ?>, <?= $rejected ?>],
                backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
</script>

</body>
</html>