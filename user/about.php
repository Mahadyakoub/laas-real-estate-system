<?php
session_start();
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About Us | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Brand vars + navbar + buttons -->
  <link rel="stylesheet" href="../assets/css/properties.css">
  <!-- Page styles -->
  <link rel="stylesheet" href="../assets/css/about.css">
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
    <?php if (isset($_SESSION['user_id'])): ?>
      <a href="appointment.php" class="btn-ghost">My Appointments</a>
      <a href="../auth/logout.php" class="btn-primary">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    <?php else: ?>
      <a href="../auth/login.php"    class="btn-ghost">Login</a>
      <a href="../auth/register.php" class="btn-primary">Sign Up</a>
    <?php endif; ?>
  </div>
</header>

<!-- ══════════ HERO ══════════ -->
<section class="about-hero">
  <div class="about-hero-inner">

    <div class="about-hero-text">
      <div class="about-hero-tag">About Us</div>
      <h1>Connecting You to<br><em>Perfect Properties</em></h1>
      <p>
        Laas Real Estate is a trusted digital platform built for Laascaanood
        and beyond — simplifying property discovery, appointments, and
        ownership through transparency and technology.
      </p>
      <div class="about-hero-cta">
        <a href="properties.php" class="btn-primary">
          <i class="fa-solid fa-building"></i> Browse Properties
        </a>
        <a href="contact.php" class="btn-ghost" style="color:#fff;border-color:rgba(255,255,255,0.30);">
          Get in Touch
        </a>
      </div>
    </div>

    <div class="about-hero-stats">
      <div class="stat-box">
        <div class="stat-num">100<span>%</span></div>
        <div class="stat-label">Verified Listings</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">24<span>h</span></div>
        <div class="stat-label">Fast Response</div>
      </div>
      <div class="stat-box">
        <div class="stat-num">0</div>
        <div class="stat-label">Hidden Fees</div>
      </div>
    </div>

  </div>
</section>

<!-- ══════════ TRUST TICKER ══════════ -->
<div class="trust-bar">
  <div class="trust-bar-inner">
    <span>Verified Listings</span>
    <span>No Hidden Fees</span>
    <span>Fast Appointments</span>
    <span>Trusted by Clients</span>
    <span>Clear Pricing</span>
    <span>Verified Listings</span>
    <span>No Hidden Fees</span>
    <span>Fast Appointments</span>
    <span>Trusted by Clients</span>
    <span>Clear Pricing</span>
  </div>
</div>

<!-- ══════════ MAIN CONTENT ══════════ -->
<main class="about-main">

  <!-- ── WHO WE ARE / MISSION / WHY ── -->
  <div>
    <div class="section-label">Our Story</div>
    <h2 class="section-title">Built on Trust, Driven by Purpose</h2>
    <p class="section-sub">
      Everything we do is designed to make property discovery simpler,
      faster, and more trustworthy for every client.
    </p>
  </div>

  <div class="about-cards">

    <div class="about-card">
      <div class="card-icon"><i class="fa-solid fa-building"></i></div>
      <h3>Who We Are</h3>
      <p>
        Laas Real Estate is a digital property management and listing platform
        designed to connect clients with verified rental properties, houses for
        sale, and land opportunities in a transparent and efficient manner.
        We serve Laascaanood and surrounding regions with pride.
      </p>
    </div>

    <div class="about-card">
      <div class="card-icon"><i class="fa-solid fa-bullseye"></i></div>
      <h3>Our Mission</h3>
      <p>
        Our mission is to simplify property discovery and appointment booking
        through a secure, user-friendly system that ensures trust, accuracy,
        and reliability for both clients and administrators. We eliminate
        unnecessary friction at every step.
      </p>
    </div>

    <div class="about-card">
      <div class="card-icon"><i class="fa-solid fa-shield-halved"></i></div>
      <h3>Why Choose Us</h3>
      <p>
        All properties are verified by our administrators. Feedback is
        transparent and public. Appointment requests are managed efficiently
        to ensure the best user experience — from first search to final
        signing.
      </p>
    </div>

  </div>

  <!-- ── CORE VALUES ── -->
  <div class="values-section">
    <div class="values-inner">
      <div class="values-header">
        <div class="section-label">Our Values</div>
        <h2 class="section-title">What We Stand For</h2>
        <p class="section-sub">
          Four principles that guide every decision we make.
        </p>
      </div>

      <div class="values-grid">
        <div class="value-item">
          <div class="value-num">01</div>
          <div class="value-title">Transparency</div>
          <div class="value-desc">Clear pricing, honest listings, and no hidden fees — ever.</div>
        </div>
        <div class="value-item">
          <div class="value-num">02</div>
          <div class="value-title">Trust</div>
          <div class="value-desc">Every property is reviewed and verified by our admin team before publishing.</div>
        </div>
        <div class="value-item">
          <div class="value-num">03</div>
          <div class="value-title">Speed</div>
          <div class="value-desc">Fast appointment confirmations and quick responses keep things moving.</div>
        </div>
        <div class="value-item">
          <div class="value-num">04</div>
          <div class="value-title">Community</div>
          <div class="value-desc">We're built for Laascaanood — serving local families, investors, and businesses.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── TEAM ── -->
  <div class="team-section">
    <div class="section-label">Our Team</div>
    <h2 class="section-title">The People Behind Laas</h2>
    <p class="section-sub">
      A dedicated team committed to delivering the best property experience possible.
    </p>

    <div class="team-grid">
      <div class="team-card">
        <div class="team-avatar"><i class="fa-solid fa-user-tie"></i></div>
        <div class="team-name">Admin Team</div>
        <div class="team-role">Property Managers</div>
        <p class="team-bio">
          Our admins review every listing, manage appointments, and ensure the
          platform runs smoothly for all clients.
        </p>
      </div>
      <div class="team-card">
        <div class="team-avatar"><i class="fa-solid fa-headset"></i></div>
        <div class="team-name">Support Team</div>
        <div class="team-role">Client Relations</div>
        <p class="team-bio">
          Dedicated to answering questions, resolving issues, and making sure
          every client feels supported throughout their journey.
        </p>
      </div>
      <div class="team-card">
        <div class="team-avatar"><i class="fa-solid fa-magnifying-glass-location"></i></div>
        <div class="team-name">Listing Team</div>
        <div class="team-role">Property Scouts</div>
        <p class="team-bio">
          We actively scout and verify new properties to keep our listings
          fresh, accurate, and relevant to the local market.
        </p>
      </div>
    </div>
  </div>

  <!-- ── CTA ── -->
  <div class="about-cta">
    <div class="cta-text">
      <h2>Ready to Find Your Property?</h2>
      <p>Browse verified listings and book a viewing today — completely free.</p>
    </div>
    <div class="cta-actions">
      <a href="properties.php" class="btn-white">
        <i class="fa-solid fa-building"></i> Browse Properties
      </a>
      <a href="contact.php" class="btn-outline-white">
        <i class="fa-solid fa-envelope"></i> Contact Us
      </a>
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