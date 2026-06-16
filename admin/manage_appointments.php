<?php
session_start();
require_once("../config/db.php");

/* ================= ADMIN ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";
$message_type = "success";

/* ================= FLASH MESSAGE ================= */
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'approved') {
        $message = "Appointment approved successfully.";
    } elseif ($_GET['msg'] === 'rejected') {
        $message = "Appointment rejected successfully.";
    } elseif ($_GET['msg'] === 'error') {
        $message = "Something went wrong. Please try again.";
        $message_type = "error";
    }
}

/* ================= HANDLE APPROVE / REJECT ================= */
if (isset($_GET['action'], $_GET['id'])) {
    $appointment_id = (int) $_GET['id'];
    $action = $_GET['action'];
    $status = null;

    if ($action === 'approve') {
        $status = 'Approved';
    } elseif ($action === 'reject') {
        $status = 'Rejected';
    }

    if ($status !== null) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id, status FROM appointments WHERE appointment_id = ?"
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $appointment_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

            if ($row && $row['status'] === 'Pending') {
                $user_id = (int)$row['user_id'];

                $updateStmt = mysqli_prepare(
                    $conn,
                    "UPDATE appointments SET status = ? WHERE appointment_id = ?"
                );

                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, "si", $status, $appointment_id);

                    if (mysqli_stmt_execute($updateStmt)) {
                        mysqli_stmt_close($updateStmt);

                        $messageText = "Your appointment request has been " . strtolower($status) . ".";

                        $notifStmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO notifications (message, date_sent, user_id)
                             VALUES (?, NOW(), ?)"
                        );

                        if ($notifStmt) {
                            mysqli_stmt_bind_param($notifStmt, "si", $messageText, $user_id);
                            mysqli_stmt_execute($notifStmt);
                            mysqli_stmt_close($notifStmt);
                        }

                        header("Location: manage_appointments.php?msg=" . strtolower($status));
                        exit();
                    } else {
                        mysqli_stmt_close($updateStmt);
                        header("Location: manage_appointments.php?msg=error");
                        exit();
                    }
                } else {
                    header("Location: manage_appointments.php?msg=error");
                    exit();
                }
            }
        }

        header("Location: manage_appointments.php?msg=error");
        exit();
    }
}

/* ================= FETCH APPOINTMENTS ================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        a.appointment_id,
        a.appointment_date,
        a.status,
        u.name AS client_name,
        p.title AS property_title
     FROM appointments a
     JOIN users u ON a.user_id = u.user_id
     JOIN properties p ON a.property_id = p.property_id
     ORDER BY a.appointment_date DESC"
);

$result = false;
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Appointments | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=4">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="dashboard-body">

<aside class="sidebar" id="sidebar">
  <div class="logo">
    <img src="../assets/images/logo.jpg" alt="Laas Real Estate">
    <h2>Laas Admin</h2>
  </div>

  <nav class="menu">
    <a href="dashboard.php"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
    <a href="manage_properties.php"><i class="fa-solid fa-building"></i><span>Properties</span></a>
    <a href="manage_appointments.php" class="active"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a>
    <a href="view_feedback.php"><i class="fa-solid fa-comments"></i><span>Feedback</span></a>
    <a href="reports.php"><i class="fa-solid fa-file-lines"></i><span>Reports</span></a>
    <a href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
  </nav>
</aside>

<main class="main">

  <div class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
      </button>

      <div>
        <h1><i class="fa-solid fa-calendar-check"></i> Manage Appointments</h1>
        <p>Review, approve, or reject client appointment requests</p>
      </div>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $message_type === 'error' ? 'error' : 'success' ?>" style="margin-bottom: 20px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <section class="table-section">
    <h2 class="section-title">
      <i class="fa-solid fa-list-check"></i> Appointment Requests
    </h2>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Property</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= htmlspecialchars($row['client_name']) ?></td>
              <td><?= htmlspecialchars($row['property_title']) ?></td>
              <td><?= date("d M Y", strtotime($row['appointment_date'])) ?></td>
              <td>
                <span class="status <?= strtolower($row['status']) ?>">
                  <?php if ($row['status'] === 'Approved'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                  <?php elseif ($row['status'] === 'Rejected'): ?>
                    <i class="fa-solid fa-circle-xmark"></i>
                  <?php else: ?>
                    <i class="fa-solid fa-clock"></i>
                  <?php endif; ?>
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td>
                <?php if ($row['status'] === 'Pending'): ?>
                  <div class="action-group">
                    <a href="?action=approve&id=<?= (int)$row['appointment_id'] ?>"
                       class="btn edit"
                       onclick="return confirm('Approve this appointment request?');">
                      <i class="fa-solid fa-check"></i> Approve
                    </a>

                    <a href="?action=reject&id=<?= (int)$row['appointment_id'] ?>"
                       class="btn reject"
                       onclick="return confirm('Reject this appointment request?');">
                      <i class="fa-solid fa-xmark"></i> Reject
                    </a>
                  </div>
                <?php else: ?>
                  <span class="completed-text">
                    <i class="fa-solid fa-check-double"></i> Completed
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="empty-row">
              <i class="fa-regular fa-calendar-xmark"></i><br>
              No appointment requests found.
            </td>
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
</script>

</body>
</html>