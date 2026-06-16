<?php
session_start();
require_once("../config/db.php");

/* ================= ADMIN ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/* ================= FEEDBACK SUMMARY ================= */
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

$totalFeedback = getCount($conn, "SELECT COUNT(*) FROM feedback");
$fiveStar      = getCount($conn, "SELECT COUNT(*) FROM feedback WHERE rating = 5");
$lowRatings    = getCount($conn, "SELECT COUNT(*) FROM feedback WHERE rating <= 2");

/* ================= FETCH FEEDBACK ================= */
$result = false;
$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        f.feedback_id,
        f.comment,
        f.rating,
        f.created_at,
        u.name AS client_name,
        p.title AS property_title
     FROM feedback f
     JOIN users u ON f.user_id = u.user_id
     LEFT JOIN properties p ON f.property_id = p.property_id
     ORDER BY f.created_at DESC"
);

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    die("Failed to load feedback records.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Feedback | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=5">
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
    <a href="manage_appointments.php"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a>
    <a href="view_feedback.php" class="active"><i class="fa-solid fa-comments"></i><span>Feedback</span></a>
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
        <h1><i class="fa-solid fa-comments"></i> Client Feedback</h1>
        <p>Review user satisfaction, ratings, and property experience</p>
      </div>
    </div>
  </div>

  <!-- ================= SUMMARY CARDS ================= -->
  <section class="stats">
    <div class="stat-card">
      <div class="kpi-icon users">
        <i class="fa-solid fa-comments"></i>
      </div>
      <div>
        <h3><?= $totalFeedback ?></h3>
        <span>Total Feedback</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="kpi-icon appointments">
        <i class="fa-solid fa-star"></i>
      </div>
      <div>
        <h3><?= $fiveStar ?></h3>
        <span>5-Star Reviews</span>
      </div>
    </div>

    <div class="stat-card">
      <div class="kpi-icon pending">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <div>
        <h3><?= $lowRatings ?></h3>
        <span>Low Ratings</span>
      </div>
    </div>
  </section>

  <!-- ================= FEEDBACK TABLE ================= -->
  <section class="table-section">
    <h2 class="section-title">
      <i class="fa-solid fa-star"></i> Submitted Feedback
    </h2>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Property</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Date</th>
          </tr>
        </thead>

        <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?= htmlspecialchars($row['client_name']) ?></td>

            <td>
              <?php if (!empty($row['property_title'])): ?>
                <?= htmlspecialchars($row['property_title']) ?>
              <?php else: ?>
                <span class="general-tag">General</span>
              <?php endif; ?>
            </td>

            <td>
              <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <?php if ($i <= (int)$row['rating']): ?>
                    <i class="fa-solid fa-star filled"></i>
                  <?php else: ?>
                    <i class="fa-regular fa-star empty"></i>
                  <?php endif; ?>
                <?php endfor; ?>
              </div>
            </td>

            <td><?= htmlspecialchars($row['comment']) ?></td>
            <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="empty-row">
              <i class="fa-regular fa-face-smile"></i><br>
              No feedback submitted yet.
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