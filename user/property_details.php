<?php
session_start();
require_once("../config/db.php");

// ── No login required — guests can view property details ──
// Only redirect if role is wrong (e.g. admin trying to access client pages)
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'client') {
    header("Location: ../auth/login.php");
    exit();
}

$current     = basename($_SERVER['PHP_SELF']);
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($property_id <= 0) {
    header("Location: properties.php");
    exit();
}

/* ── Main property ── */
$stmt = mysqli_prepare(
    $conn,
    "SELECT property_id, title, location, price, image, category,
            description, bedrooms, bathrooms, size, status
     FROM properties
     WHERE property_id = ? LIMIT 1"
);
if (!$stmt) die("Failed to load property.");
mysqli_stmt_bind_param($stmt, "i", $property_id);
mysqli_stmt_execute($stmt);
$property = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$property) {
    header("Location: properties.php");
    exit();
}

/* ── Additional images ── */
$extra_images = [];
$tc = mysqli_query($conn,
    "SELECT 1 FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'property_images' LIMIT 1");
if ($tc && mysqli_num_rows($tc) > 0) {
    $img_stmt = mysqli_prepare($conn,
        "SELECT image_path FROM property_images WHERE property_id = ? ORDER BY sort_order ASC");
    if ($img_stmt) {
        mysqli_stmt_bind_param($img_stmt, "i", $property_id);
        mysqli_stmt_execute($img_stmt);
        $img_result = mysqli_stmt_get_result($img_stmt);
        while ($r = mysqli_fetch_assoc($img_result)) {
            $extra_images[] = $r['image_path'];
        }
        mysqli_stmt_close($img_stmt);
    }
}

/* ── Build gallery ── */
$gallery = [];
if (!empty($property['image'])) $gallery[] = $property['image'];
foreach ($extra_images as $ei) {
    if (!empty($ei) && !in_array($ei, $gallery)) $gallery[] = $ei;
}

$isLoggedIn = isset($_SESSION['user_id']);

function safeText($v) { return htmlspecialchars((string)$v); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= safeText($property['title']) ?> | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="../assets/css/properties.css">
  <link rel="stylesheet" href="../assets/css/property_details.css">

  <style>
    /* Login prompt card */
    .login-prompt-card {
      background: var(--blue-soft);
      border: 1.5px solid var(--blue-light);
      border-radius: 14px;
      padding: 20px;
      text-align: center;
    }
    .login-prompt-card i {
      font-size: 28px; color: var(--blue);
      margin-bottom: 10px; display: block;
    }
    .login-prompt-card h4 {
      font-size: 15px; font-weight: 700;
      color: var(--navy); margin-bottom: 6px;
    }
    .login-prompt-card p {
      font-size: 13px; color: var(--muted);
      margin-bottom: 16px; line-height: 1.6;
    }
    .login-prompt-card .btn-primary {
      width: 100%; justify-content: center; padding: 12px;
    }
    .login-prompt-card .btn-ghost {
      width: 100%; justify-content: center;
      padding: 11px; margin-top: 10px;
      color: var(--navy); border: 1.5px solid var(--border);
    }
    /* Feedback sidebar card */
    .feedback-sidebar-card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 22px;
      box-shadow: 0 4px 16px rgba(13,27,62,0.06);
    }
    .feedback-sidebar-card .fsc-label {
      font-size: 11px; font-weight: 700;
      letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 6px;
    }
    .feedback-sidebar-card h4 {
      font-size: 16px; font-weight: 700;
      color: var(--navy); margin-bottom: 6px;
    }
    .feedback-sidebar-card p {
      font-size: 13px; color: var(--muted);
      line-height: 1.6; margin-bottom: 16px;
    }
    .feedback-sidebar-card .btn-primary,
    .feedback-sidebar-card .btn-ghost {
      width: 100%; justify-content: center; padding: 12px;
    }
    .feedback-sidebar-card .btn-ghost {
      color: var(--navy); border: 1.5px solid var(--border);
      margin-top: 0;
    }
  </style>
</head>
<body>

<!-- ══════════ NAVBAR ══════════ -->
<header class="navbar">
  <div class="brand">
    <img src="../assets/images/logo.jpg" alt="Laas Real Estate">
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
    <?php if ($isLoggedIn): ?>
      <a href="appointment.php" class="btn-ghost">My Appointments</a>
      <a href="../auth/logout.php" class="btn-primary">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    <?php else: ?>
      <a href="../auth/login.php" class="btn-ghost">Login</a>
      <a href="../auth/register.php" class="btn-primary">Sign Up</a>
    <?php endif; ?>
  </div>
</header>

<!-- ══════════ MAIN ══════════ -->
<main class="details-page">

  <!-- BREADCRUMB -->
  <nav class="breadcrumb">
    <a href="home.php">Home</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="properties.php">Properties</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= safeText($property['title']) ?></span>
  </nav>

  <!-- ══ GALLERY ══ -->
  <div class="gallery-section">

    <?php if (!empty($gallery)): ?>

      <div class="gallery-main" id="galleryMain" onclick="openLightbox(currentIndex)">
        <img
          id="mainImg"
          src="../assets/images/properties/<?= safeText($gallery[0]) ?>"
          alt="<?= safeText($property['title']) ?>"
        >
        <span class="gallery-badge"><?= safeText($property['category'] ?: 'Property') ?></span>

        <?php if (count($gallery) > 1): ?>
          <button class="gallery-arrow prev" onclick="event.stopPropagation(); slideGallery(-1)">
            <i class="fa-solid fa-chevron-left"></i>
          </button>
          <button class="gallery-arrow next" onclick="event.stopPropagation(); slideGallery(1)">
            <i class="fa-solid fa-chevron-right"></i>
          </button>
          <span class="gallery-counter" id="galleryCounter">1 / <?= count($gallery) ?></span>
        <?php endif; ?>

        <button class="gallery-expand" onclick="event.stopPropagation(); openLightbox(currentIndex)" title="View fullscreen">
          <i class="fa-solid fa-expand"></i>
        </button>
      </div>

      <?php if (count($gallery) > 1): ?>
        <div class="gallery-thumbs" id="galleryThumbs">
          <?php foreach ($gallery as $i => $img): ?>
            <div class="gallery-thumb <?= $i===0?'active':'' ?>"
                 data-index="<?= $i ?>"
                 onclick="goToSlide(<?= $i ?>)">
              <img src="../assets/images/properties/<?= safeText($img) ?>"
                   alt="Photo <?= $i+1 ?>" loading="lazy">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="gallery-placeholder">
        <i class="fa-regular fa-image"></i>
      </div>
    <?php endif; ?>

  </div>

  <!-- ══ HEADER INFO ══ -->
  <section class="details-header">
    <div class="details-header-top">
      <div>
        <span class="details-category">
          <i class="fa-solid fa-tag"></i>
          <?= safeText($property['category']) ?>
        </span>
        <h1><?= safeText($property['title']) ?></h1>
        <p class="details-location">
          <i class="fa-solid fa-location-dot"></i>
          <?= safeText($property['location']) ?>
        </p>
      </div>

      <div class="details-price-box">
        <strong>$<?= number_format((float)$property['price'], 0) ?></strong>
        <span class="status-pill"><?= safeText($property['status']) ?></span>
      </div>
    </div>
  </section>

  <!-- ══ CONTENT + SIDEBAR ══ -->
  <section class="details-content">

    <!-- LEFT -->
    <div class="details-left">

      <div class="details-card">
        <h2><i class="fa-solid fa-chart-bar" style="color:var(--blue);margin-right:10px;font-size:16px;"></i>Property Overview</h2>
        <div class="details-features">
          <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-bed"></i></div>
            <div>
              <strong><?= (int)$property['bedrooms'] ?></strong>
              <span>Bedrooms</span>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-bath"></i></div>
            <div>
              <strong><?= (int)$property['bathrooms'] ?></strong>
              <span>Bathrooms</span>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon"><i class="fa-solid fa-ruler-combined"></i></div>
            <div>
              <strong><?= (int)$property['size'] ?></strong>
              <span>m² Size</span>
            </div>
          </div>
        </div>
      </div>

      <div class="details-card">
        <h2><i class="fa-solid fa-align-left" style="color:var(--blue);margin-right:10px;font-size:16px;"></i>Description</h2>
        <p class="details-description">
          <?= nl2br(safeText($property['description'] ?: 'No description available for this property yet.')) ?>
        </p>
      </div>

      <div class="details-card">
        <h2><i class="fa-solid fa-circle-check" style="color:var(--blue);margin-right:10px;font-size:16px;"></i>Why This Property?</h2>
        <div class="details-benefits">
          <div>
            <div class="benefit-icon"><i class="fa-solid fa-shield-halved"></i></div>
            Verified listing — fully reviewed by our team
          </div>
          <div>
            <div class="benefit-icon"><i class="fa-solid fa-calendar-check"></i></div>
            Easy viewing — book an appointment in seconds
          </div>
          <div>
            <div class="benefit-icon"><i class="fa-solid fa-comments"></i></div>
            Trusted platform — transparent pricing, no hidden fees
          </div>
          <div>
            <div class="benefit-icon"><i class="fa-solid fa-bolt"></i></div>
            Fast process — quick responses, smooth experience
          </div>
        </div>
      </div>

    </div>

    <!-- SIDEBAR -->
    <aside class="details-sidebar">

      <!-- ── BOOKING CARD ── -->
      <div class="booking-card">
        <h3>Book a Viewing</h3>
        <p>Schedule your appointment and connect with the property owner easily.</p>

        <div class="booking-summary">
          <div>
            <span>Price</span>
            <strong>$<?= number_format((float)$property['price'], 0) ?></strong>
          </div>
          <div>
            <span>Category</span>
            <strong><?= safeText($property['category']) ?></strong>
          </div>
          <div>
            <span>Status</span>
            <strong><?= safeText($property['status']) ?></strong>
          </div>
          <div>
            <span>Size</span>
            <strong><?= (int)$property['size'] ?> m²</strong>
          </div>
        </div>

        <?php if ($isLoggedIn): ?>
          <!-- Logged in — go straight to appointment -->
          <a href="appointment.php?property_id=<?= (int)$property['property_id'] ?>"
             class="btn-primary full-btn">
            <i class="fa-solid fa-calendar-check"></i> Request Appointment
          </a>
        <?php else: ?>
          <!-- Guest — show login prompt inside card -->
          <div class="login-prompt-card">
            <i class="fa-solid fa-lock"></i>
            <h4>Login Required</h4>
            <p>Create a free account or login to book a viewing for this property.</p>
            <a href="../auth/login.php?redirect=user/property_details.php?id=<?= (int)$property['property_id'] ?>"
               class="btn-primary">
              <i class="fa-solid fa-right-to-bracket"></i> Login to Book
            </a>
            <a href="../auth/register.php"
               class="btn-ghost" style="display:flex;align-items:center;justify-content:center;gap:7px;padding:11px;margin-top:10px;color:var(--navy);border:1.5px solid var(--border);border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;transition:all 0.22s;">
              <i class="fa-solid fa-user-plus"></i> Create Free Account
            </a>
          </div>
        <?php endif; ?>

        <a href="properties.php" class="btn-ghost full-btn secondary-space">
          <i class="fa-solid fa-arrow-left"></i> Back to Properties
        </a>
      </div>

      <!-- ── AGENT CARD ── -->
      <div class="agent-card">
        <div class="agent-card-title">Listed by</div>
        <div class="agent-info">
          <div class="agent-avatar">
            <i class="fa-solid fa-user-tie"></i>
          </div>
          <div>
            <div class="agent-name">Laas Real Estate</div>
            <div class="agent-role">Verified Agent</div>
          </div>
        </div>
        <a href="contact.php" class="btn-primary full-btn">
          <i class="fa-solid fa-phone"></i> Contact Agent
        </a>
      </div>

      <!-- ── FEEDBACK CARD ── -->
      <div class="feedback-sidebar-card">
        <div class="fsc-label">
          <i class="fa-solid fa-star" style="color:var(--blue);margin-right:4px;"></i>
          Client Reviews
        </div>
        <h4>Visited This Property?</h4>
        <p>Share your experience and help other clients make better decisions.</p>

        <?php if ($isLoggedIn): ?>
          <a href="feedback.php?property_id=<?= (int)$property['property_id'] ?>"
             class="btn-primary full-btn">
            <i class="fa-solid fa-star"></i> Leave a Review
          </a>
        <?php else: ?>
          <a href="../auth/login.php?redirect=user/feedback.php?property_id=<?= (int)$property['property_id'] ?>"
             class="btn-ghost full-btn" style="display:flex;align-items:center;justify-content:center;gap:7px;padding:12px;color:var(--navy);border:1.5px solid var(--border);border-radius:8px;text-decoration:none;font-weight:600;font-size:13px;transition:all 0.22s;">
            <i class="fa-solid fa-star"></i> Login to Leave a Review
          </a>
        <?php endif; ?>
      </div>

    </aside>
  </section>

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

<!-- ══════════ LIGHTBOX ══════════ -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()">
    <i class="fa-solid fa-xmark"></i>
  </button>
  <div class="lightbox-inner" onclick="event.stopPropagation()">
    <button class="gallery-arrow prev" onclick="lightboxSlide(-1)">
      <i class="fa-solid fa-chevron-left"></i>
    </button>
    <img id="lightboxImg" src="" alt="Property photo">
    <button class="gallery-arrow next" onclick="lightboxSlide(1)">
      <i class="fa-solid fa-chevron-right"></i>
    </button>
  </div>
  <div class="lightbox-counter" id="lightboxCounter"></div>
</div>

<!-- ══════════ GALLERY SCRIPT ══════════ -->
<script>
const gallery = <?= json_encode(array_map(
    fn($img) => '../assets/images/properties/' . htmlspecialchars($img, ENT_QUOTES),
    $gallery
)) ?>;

let currentIndex = 0;

function goToSlide(idx) {
  currentIndex = idx;
  const mainImg = document.getElementById('mainImg');
  mainImg.style.opacity = '0';
  mainImg.style.transition = 'opacity 0.25s ease';
  setTimeout(() => { mainImg.src = gallery[idx]; mainImg.style.opacity = '1'; }, 150);
  const counter = document.getElementById('galleryCounter');
  if (counter) counter.textContent = (idx + 1) + ' / ' + gallery.length;
  document.querySelectorAll('.gallery-thumb').forEach((t, i) => {
    t.classList.toggle('active', i === idx);
  });
}

function slideGallery(dir) {
  goToSlide((currentIndex + dir + gallery.length) % gallery.length);
}

function openLightbox(idx) {
  if (!gallery.length) return;
  currentIndex = idx;
  document.getElementById('lightboxImg').src = gallery[idx];
  updateLightboxCounter();
  document.getElementById('lightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.body.style.overflow = '';
}

function lightboxSlide(dir) {
  currentIndex = (currentIndex + dir + gallery.length) % gallery.length;
  const img = document.getElementById('lightboxImg');
  img.style.opacity = '0';
  img.style.transition = 'opacity 0.2s ease';
  setTimeout(() => { img.src = gallery[currentIndex]; img.style.opacity = '1'; }, 150);
  updateLightboxCounter();
  goToSlide(currentIndex);
}

function updateLightboxCounter() {
  const el = document.getElementById('lightboxCounter');
  if (el) el.textContent = (currentIndex + 1) + ' of ' + gallery.length + ' photos';
}

document.addEventListener('keydown', e => {
  const lb = document.getElementById('lightbox');
  if (lb.classList.contains('open')) {
    if (e.key === 'ArrowLeft')  lightboxSlide(-1);
    if (e.key === 'ArrowRight') lightboxSlide(1);
    if (e.key === 'Escape')     closeLightbox();
  } else {
    if (e.key === 'ArrowLeft')  slideGallery(-1);
    if (e.key === 'ArrowRight') slideGallery(1);
  }
});
</script>

</body>
</html>