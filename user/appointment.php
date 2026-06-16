<?php
session_start();
require_once("../config/db.php");

/* ================= CLIENT ACCESS ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header("Location: ../auth/login.php");
    exit();
}

$current  = basename($_SERVER['PHP_SELF']);
$user_id  = $_SESSION['user_id'];
$success  = "";
$error    = "";

$selected_property_id = (int)($_GET['property_id'] ?? 0);
$date = "";
$note = "";

/* ================= HANDLE FORM ================= */
if (isset($_POST['request_appointment'])) {
    $selected_property_id = (int)$_POST['property_id'];
    $date = trim($_POST['appointment_date'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if (!$selected_property_id || !$date) {
        $error = "Please select a property and date.";
    } elseif ($date < date("Y-m-d")) {
        $error = "Appointment date cannot be in the past.";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO appointments (user_id, property_id, appointment_date, status, note)
             VALUES (?, ?, ?, 'Pending', ?)"
        );
        mysqli_stmt_bind_param($stmt, "iiss",
            $user_id, $selected_property_id, $date, $note);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Your appointment has been requested successfully!";
            $date = "";
            $note = "";
            $selected_property_id = 0;
        } else {
            $error = "Failed to request appointment. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

/* ================= FETCH DATA ================= */
$properties = mysqli_query($conn,
    "SELECT property_id, title, location FROM properties WHERE status='Available' ORDER BY title ASC"
);

$appointments = mysqli_query($conn,
    "SELECT a.*, p.title, p.location
     FROM appointments a
     JOIN properties p ON a.property_id = p.property_id
     WHERE a.user_id = $user_id
     ORDER BY a.appointment_date DESC"
);

$appt_count = $appointments ? mysqli_num_rows($appointments) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Appointments | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Brand variables + navbar -->
  <link rel="stylesheet" href="../assets/css/properties.css">
  <!-- Page styles -->
  <link rel="stylesheet" href="../assets/css/appointment.css">
</head>
<body>

<!-- ══════════ NAVBAR ══════════ -->
<header class="navbar">
  <div class="brand">
    <img src="../assets/images/logo.jpg" alt="Laas Real Estate Logo">
    <span>Laas Real Estate</span>
  </div>

  <nav class="nav-links">
    <a href="home.php"        class="<?= $current==='home.php'        ?'active':'' ?>">Home</a>
    <a href="properties.php"  class="<?= $current==='properties.php'  ?'active':'' ?>">Properties</a>
    <a href="appointment.php" class="<?= $current==='appointment.php' ?'active':'' ?>">Appointments</a>
    <a href="feedback.php"    class="<?= $current==='feedback.php'    ?'active':'' ?>">Feedback</a>
    <a href="about.php"       class="<?= $current==='about.php'       ?'active':'' ?>">About</a>
    <a href="contact.php"     class="<?= $current==='contact.php'     ?'active':'' ?>">Contact</a>
  </nav>

  <div class="nav-action">
    <a href="appointment.php" class="btn-ghost">My Appointments</a>
    <a href="../auth/logout.php" class="btn-primary">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </div>
</header>

<!-- ══════════ HERO ══════════ -->
<section class="appt-hero">
  <div class="appt-hero-inner">
    <div class="appt-hero-tag">Schedule a Visit</div>
    <h1>Book a Property Viewing</h1>
    <p>Connect with our team and schedule your visit at a time that suits you.</p>
  </div>
</section>

<!-- ══════════ TRUST TICKER ══════════ -->
<div class="trust-bar">
  <div class="trust-bar-inner">
    <span>Fast Confirmation</span>
    <span>Verified Properties</span>
    <span>No Hidden Fees</span>
    <span>Trusted by Clients</span>
    <span>Easy Scheduling</span>
    <span>Fast Confirmation</span>
    <span>Verified Properties</span>
    <span>No Hidden Fees</span>
    <span>Trusted by Clients</span>
    <span>Easy Scheduling</span>
  </div>
</div>

<!-- ══════════ MAIN CONTENT ══════════ -->
<main class="appt-main">

  <!-- ── LEFT: BOOKING FORM ── -->
  <div class="appt-card booking-form-card">

    <div class="appt-card-header">
      <h2>Request Appointment</h2>
      <p>Fill in the details below to schedule your viewing</p>
    </div>
    <div class="appt-divider"></div>

    <div class="appt-form-body">

      <?php if ($success): ?>
        <div class="appt-alert success">
          <i class="fa-solid fa-circle-check"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="appt-alert error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post">

        <div class="form-field">
          <label><i class="fa-solid fa-building" style="color:var(--blue);margin-right:5px;"></i>Property</label>
          <select name="property_id" required>
            <option value="">Select a property…</option>
            <?php
              // Reset pointer in case it was used
              if ($properties) mysqli_data_seek($properties, 0);
              while ($p = mysqli_fetch_assoc($properties)):
            ?>
              <option
                value="<?= (int)$p['property_id'] ?>"
                <?= ((int)$p['property_id'] === $selected_property_id) ? 'selected' : '' ?>
              >
                <?= htmlspecialchars($p['title']) ?> — <?= htmlspecialchars($p['location']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-field">
          <label><i class="fa-regular fa-calendar" style="color:var(--blue);margin-right:5px;"></i>Preferred Date</label>
          <input
            type="date"
            name="appointment_date"
            min="<?= date('Y-m-d') ?>"
            value="<?= htmlspecialchars($date) ?>"
            required
          >
        </div>

        <div class="form-field">
          <label><i class="fa-regular fa-comment-dots" style="color:var(--blue);margin-right:5px;"></i>Note <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
          <textarea
            name="note"
            placeholder="Any questions or special requests for the viewing…"
          ><?= htmlspecialchars($note) ?></textarea>
        </div>

        <button type="submit" name="request_appointment" class="appt-submit-btn">
          <i class="fa-solid fa-calendar-check"></i>
          Request Appointment
        </button>

      </form>

      <!-- How it works -->
      <div class="how-it-works">
        <h4><i class="fa-solid fa-circle-info" style="margin-right:5px;"></i>How it works</h4>
        <div class="how-step">
          <div class="step-num">1</div>
          <div class="step-text"><strong>Submit</strong> your preferred date and property</div>
        </div>
        <div class="how-step">
          <div class="step-num">2</div>
          <div class="step-text"><strong>We confirm</strong> your appointment via our team</div>
        </div>
        <div class="how-step">
          <div class="step-num">3</div>
          <div class="step-text"><strong>Visit</strong> the property at your scheduled time</div>
        </div>
      </div>

    </div>
  </div>

  <!-- ── RIGHT: HISTORY ── -->
  <div class="appt-card history-card">

    <div class="history-header">
      <h2>My Appointments</h2>
      <span class="appt-count-badge">
        <i class="fa-solid fa-list" style="margin-right:5px;"></i>
        <?= $appt_count ?> total
      </span>
    </div>

    <div class="table-wrapper">
      <?php if ($appt_count > 0): ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Property</th>
              <th>Date</th>
              <th>Status</th>
              <th>Note</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($row = mysqli_fetch_assoc($appointments)): ?>
            <tr>
              <!-- Property -->
              <td>
                <div class="prop-cell">
                  <div class="prop-icon"><i class="fa-solid fa-house"></i></div>
                  <div>
                    <div class="prop-name"><?= htmlspecialchars($row['title']) ?></div>
                    <div class="prop-loc">
                      <i class="fa-solid fa-location-dot" style="font-size:10px;color:var(--blue);margin-right:3px;"></i>
                      <?= htmlspecialchars($row['location']) ?>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Date -->
              <td class="date-cell">
                <div class="date-main"><?= date("d M Y", strtotime($row['appointment_date'])) ?></div>
                <div class="date-sub"><?= date("l", strtotime($row['appointment_date'])) ?></div>
              </td>

              <!-- Status -->
              <td>
                <?php
                  $s = strtolower($row['status']);
                  $icons = ['pending'=>'fa-clock','approved'=>'fa-circle-check','rejected'=>'fa-circle-xmark'];
                  $icon  = $icons[$s] ?? 'fa-circle';
                ?>
                <span class="status-badge <?= $s ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>

              <!-- Note -->
              <td>
                <?php if (!empty($row['note'])): ?>
                  <span class="note-text"><?= htmlspecialchars($row['note']) ?></span>
                <?php else: ?>
                  <span class="note-empty">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>

      <?php else: ?>
        <div class="empty-state">
          <i class="fa-regular fa-calendar-xmark"></i>
          <h3>No appointments yet</h3>
          <p>Use the form to book your first property viewing.</p>
        </div>
      <?php endif; ?>
    </div>

  </div>

</main>

<!-- ══════════ FOOTER ══════════ -->
<footer class="footer">
  <div class="footer-inner">
    <div>
      <h4>Laas Real Estate</h4>
      <p>Smart property discovery for rentals, house sales, and land opportunities.</p>
    </div>
    <div class="footer-links">
      <a href="about.php">About</a>
      <a href="properties.php">Properties</a>
      <a href="contact.php">Contact</a>
    </div>
  </div>
  <div class="footer-bottom">
    © <?= date("Y") ?> Laas Real Estate. All rights reserved.
  </div>
</footer>

</body>
</html>