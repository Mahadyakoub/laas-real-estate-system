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

$selected_property_id = 0;
$selected_rating      = 0;
$comment_value        = "";

/* ================= HANDLE SUBMISSION ================= */
if (isset($_POST['submit_feedback'])) {
    $selected_property_id = (int)($_POST['property_id']  ?? 0);
    $selected_rating      = (int)($_POST['rating']       ?? 0);
    $comment_value        = trim($_POST['comment']        ?? '');

    if (!$selected_property_id || !$comment_value || $selected_rating < 1 || $selected_rating > 5) {
        $error = "Please select a property, choose a star rating, and write a comment.";
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO feedback (user_id, property_id, rating, comment)
             VALUES (?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iiis",
                $user_id, $selected_property_id, $selected_rating, $comment_value);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Thank you! Your feedback has been submitted.";
                $selected_property_id = 0;
                $selected_rating      = 0;
                $comment_value        = "";
            } else {
                $error = "Failed to submit feedback. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

/* ================= FETCH HISTORY ================= */
$result = false;
$stmt = mysqli_prepare($conn,
    "SELECT f.comment, f.rating, f.created_at, p.title AS property_title
     FROM feedback f
     JOIN properties p ON f.property_id = p.property_id
     WHERE f.user_id = ?
     ORDER BY f.feedback_id DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
$fb_count = $result ? mysqli_num_rows($result) : 0;

/* ================= FETCH PROPERTIES ================= */
$properties = false;
$propStmt = mysqli_prepare($conn,
    "SELECT property_id, title FROM properties ORDER BY title ASC");
if ($propStmt) {
    mysqli_stmt_execute($propStmt);
    $properties = mysqli_stmt_get_result($propStmt);
}

/* star label helper */
$star_labels = [1=>'Poor',2=>'Fair',3=>'Good',4=>'Very Good',5=>'Excellent'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Feedback | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Brand vars + navbar -->
  <link rel="stylesheet" href="../assets/css/properties.css">
  <!-- Page styles -->
  <link rel="stylesheet" href="../assets/css/feedback.css">
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
<section class="fb-hero">
  <div class="fb-hero-inner">
    <div class="fb-hero-tag">Your Voice Matters</div>
    <h1>Share Your Experience</h1>
    <p>Help us improve by rating your property experience and leaving honest feedback.</p>
  </div>
</section>

<!-- ══════════ TRUST TICKER ══════════ -->
<div class="trust-bar">
  <div class="trust-bar-inner">
    <span>Trusted by Clients</span>
    <span>Verified Listings</span>
    <span>No Hidden Fees</span>
    <span>Fast Appointments</span>
    <span>Clear Pricing</span>
    <span>Trusted by Clients</span>
    <span>Verified Listings</span>
    <span>No Hidden Fees</span>
    <span>Fast Appointments</span>
    <span>Clear Pricing</span>
  </div>
</div>

<!-- ══════════ MAIN ══════════ -->
<main class="fb-main">

  <!-- ── LEFT: FORM ── -->
  <div class="fb-card fb-form-card">
    <div class="fb-card-header">
      <h2>Submit Feedback</h2>
      <p>Rate your experience and share your thoughts</p>
    </div>
    <div class="fb-divider"></div>

    <div class="fb-form-body">

      <?php if ($success): ?>
        <div class="fb-alert success">
          <i class="fa-solid fa-circle-check"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="fb-alert error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" id="feedbackForm">

        <!-- Property -->
        <div class="fb-field">
          <label><i class="fa-solid fa-building" style="color:var(--blue);margin-right:5px;"></i>Property</label>
          <select name="property_id" required>
            <option value="">Select a property…</option>
            <?php if ($properties && mysqli_num_rows($properties) > 0):
              while ($p = mysqli_fetch_assoc($properties)): ?>
                <option value="<?= (int)$p['property_id'] ?>"
                  <?= ($selected_property_id === (int)$p['property_id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['title']) ?>
                </option>
              <?php endwhile;
            endif; ?>
          </select>
        </div>

        <!-- Star Rating -->
        <div>
          <label class="star-field-label">
            <i class="fa-solid fa-star" style="color:var(--blue);margin-right:5px;"></i>Your Rating
          </label>

          <div class="star-rating" id="starRating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input
                type="radio"
                name="rating"
                id="star<?= $i ?>"
                value="<?= $i ?>"
                <?= ($selected_rating === $i) ? 'checked' : '' ?>
              >
              <label for="star<?= $i ?>" title="<?= $star_labels[$i] ?>">★</label>
            <?php endfor; ?>
          </div>

          <p class="star-hint" id="starHint">
            <?= $selected_rating > 0 ? $star_labels[$selected_rating] . ' (' . $selected_rating . '/5)' : 'Click to rate' ?>
          </p>
        </div>

        <!-- Comment -->
        <div class="fb-field">
          <label><i class="fa-regular fa-comment-dots" style="color:var(--blue);margin-right:5px;"></i>Your Comment</label>
          <textarea
            name="comment"
            placeholder="Share your honest experience with this property…"
            required
          ><?= htmlspecialchars($comment_value) ?></textarea>
        </div>

        <button type="submit" name="submit_feedback" class="fb-submit-btn">
          <i class="fa-solid fa-paper-plane"></i>
          Submit Feedback
        </button>

      </form>

      <!-- Why feedback matters -->
      <div class="why-box">
        <h4><i class="fa-solid fa-lightbulb" style="margin-right:5px;"></i>Why your feedback matters</h4>
        <div class="why-item">
          <div class="why-icon"><i class="fa-solid fa-chart-line"></i></div>
          Helps us improve property listings and descriptions
        </div>
        <div class="why-item">
          <div class="why-icon"><i class="fa-solid fa-users"></i></div>
          Guides other clients in making the right choice
        </div>
        <div class="why-item">
          <div class="why-icon"><i class="fa-solid fa-shield-halved"></i></div>
          Keeps our platform transparent and trustworthy
        </div>
      </div>

    </div>
  </div>

  <!-- ── RIGHT: HISTORY ── -->
  <div class="fb-card fb-history-card">
    <div class="fb-history-header">
      <h2>My Feedback History</h2>
      <span class="fb-count-badge">
        <i class="fa-solid fa-list" style="margin-right:5px;"></i>
        <?= $fb_count ?> submitted
      </span>
    </div>

    <div class="table-wrapper">
      <?php if ($fb_count > 0): ?>
        <table class="data-table">
          <thead>
            <tr>
              <th>Property</th>
              <th>Rating</th>
              <th>Comment</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <!-- Property -->
              <td>
                <div class="fb-prop-cell">
                  <div class="fb-prop-icon"><i class="fa-solid fa-house"></i></div>
                  <div class="fb-prop-name"><?= htmlspecialchars($row['property_title']) ?></div>
                </div>
              </td>

              <!-- Stars -->
              <td>
                <div class="stars-display">
                  <?php
                    $r = (int)$row['rating'];
                    for ($s = 1; $s <= 5; $s++):
                  ?>
                    <i class="fa-solid fa-star <?= $s <= $r ? 'filled' : 'empty' ?>"></i>
                  <?php endfor; ?>
                  <span class="star-val"><?= $r ?>/5</span>
                </div>
              </td>

              <!-- Comment -->
              <td>
                <span class="fb-comment"><?= htmlspecialchars($row['comment']) ?></span>
              </td>

              <!-- Date -->
              <td>
                <div class="date-main"><?= !empty($row['created_at']) ? date("d M Y", strtotime($row['created_at'])) : '—' ?></div>
                <div class="date-sub"><?= !empty($row['created_at']) ? date("l", strtotime($row['created_at'])) : '' ?></div>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>

      <?php else: ?>
        <div class="fb-empty">
          <i class="fa-regular fa-star"></i>
          <h3>No feedback yet</h3>
          <p>Use the form to share your first property review.</p>
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

<script>
/* Star rating hint text */
const hints = { 1:'Poor',2:'Fair',3:'Good',4:'Very Good',5:'Excellent' };
document.querySelectorAll('.star-rating input').forEach(input => {
  input.addEventListener('change', () => {
    const v = parseInt(input.value);
    const hint = document.getElementById('starHint');
    hint.textContent = hints[v] + ' (' + v + '/5)';
    hint.style.color = '#2563EB';
    hint.style.fontWeight = '600';
  });
});
</script>

</body>
</html>