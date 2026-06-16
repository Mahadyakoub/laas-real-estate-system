<?php
session_start();
$current = basename($_SERVER['PHP_SELF']);
$success = "";
$error   = "";

if (isset($_POST['send'])) {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $success = "Your message has been sent! We'll get back to you within 24 hours.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Brand vars + navbar + buttons -->
  <link rel="stylesheet" href="../assets/css/properties.css">
  <!-- Page styles -->
  <link rel="stylesheet" href="../assets/css/contact.css">
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
<section class="contact-hero">
  <div class="contact-hero-inner">
    <div class="contact-hero-tag">Get in Touch</div>
    <h1>We're Here to Help You</h1>
    <p>Reach out with any questions about properties, appointments, or our services. We respond within 24 hours.</p>
  </div>
</section>

<!-- ══════════ TRUST TICKER ══════════ -->
<div class="trust-bar">
  <div class="trust-bar-inner">
    <span>Fast Response</span>
    <span>Verified Listings</span>
    <span>No Hidden Fees</span>
    <span>Trusted by Clients</span>
    <span>Clear Pricing</span>
    <span>Fast Response</span>
    <span>Verified Listings</span>
    <span>No Hidden Fees</span>
    <span>Trusted by Clients</span>
    <span>Clear Pricing</span>
  </div>
</div>

<!-- ══════════ MAIN CONTENT ══════════ -->
<main class="contact-main">

  <!-- ── LEFT: INFO PANEL ── -->
  <div class="contact-info-panel">

    <div class="info-heading">
      <div class="info-label">Contact Info</div>
      <h2>Let's Start a Conversation</h2>
      <p>Choose the best way to reach us. Our team is ready to assist with any property inquiries.</p>
    </div>

    <!-- Email -->
    <a href="mailto:support@laasrealestate.com" class="contact-detail">
      <div class="detail-icon"><i class="fa-solid fa-envelope"></i></div>
      <div class="detail-text">
        <div class="detail-label">Email</div>
        <div class="detail-value">support@laasrealestate.com</div>
      </div>
    </a>

    <!-- Phone -->
    <a href="tel:+2526344665545678" class="contact-detail">
      <div class="detail-icon"><i class="fa-solid fa-phone"></i></div>
      <div class="detail-text">
        <div class="detail-label">Phone</div>
        <div class="detail-value">+252 634 466 5545</div>
      </div>
    </a>

    <!-- Location -->
    <div class="contact-detail">
      <div class="detail-icon"><i class="fa-solid fa-location-dot"></i></div>
      <div class="detail-text">
        <div class="detail-label">Office</div>
        <div class="detail-value">Las'anod, Sool, Somalia</div>
      </div>
    </div>

    <!-- WhatsApp -->
    <a href="https://wa.me/252634466" target="_blank" class="contact-detail">
      <div class="detail-icon"><i class="fa-brands fa-whatsapp"></i></div>
      <div class="detail-text">
        <div class="detail-label">WhatsApp</div>
        <div class="detail-value">Chat with us directly</div>
      </div>
    </a>

    <!-- Office hours -->
    <div class="hours-card">
      <h4><i class="fa-regular fa-clock"></i> Office Hours</h4>
      <div class="hours-row">
        <span class="hours-day">Monday – Friday</span>
        <span class="hours-time">8:00 AM – 6:00 PM</span>
      </div>
      <div class="hours-row">
        <span class="hours-day">Saturday</span>
        <span class="hours-time">9:00 AM – 3:00 PM</span>
      </div>
      <div class="hours-row">
        <span class="hours-day">Sunday</span>
        <span class="hours-time closed">Closed</span>
      </div>
    </div>

  </div>

  <!-- ── RIGHT: FORM CARD ── -->
  <div class="contact-form-card">
    <div class="form-card-header">
      <h2>Send Us a Message</h2>
      <p>Fill in the form and we'll get back to you as soon as possible</p>
    </div>
    <div class="form-card-divider"></div>

    <div class="form-card-body">

      <?php if ($success): ?>
        <div class="contact-alert success">
          <i class="fa-solid fa-circle-check"></i>
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="contact-alert error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="post">

        <!-- Name + Email row -->
        <div class="form-row">
          <div class="contact-field" style="margin-bottom:0;">
            <label><i class="fa-solid fa-user" style="color:var(--blue);margin-right:4px;"></i>Full Name <span style="color:#EF4444;">*</span></label>
            <input
              type="text" name="name"
              placeholder="John Doe"
              value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
              required
            >
          </div>
          <div class="contact-field" style="margin-bottom:0;">
            <label><i class="fa-solid fa-envelope" style="color:var(--blue);margin-right:4px;"></i>Email Address <span style="color:#EF4444;">*</span></label>
            <input
              type="email" name="email"
              placeholder="you@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              required
            >
          </div>
        </div>

        <!-- Subject -->
        <div class="contact-field" style="margin-top:18px;">
          <label><i class="fa-solid fa-tag" style="color:var(--blue);margin-right:4px;"></i>Subject</label>
          <select name="subject">
            <option value="">Select a topic…</option>
            <option value="Property Inquiry"     <?= (($_POST['subject'] ?? '')==='Property Inquiry')     ?'selected':'' ?>>Property Inquiry</option>
            <option value="Appointment Support"  <?= (($_POST['subject'] ?? '')==='Appointment Support')  ?'selected':'' ?>>Appointment Support</option>
            <option value="Pricing Question"     <?= (($_POST['subject'] ?? '')==='Pricing Question')     ?'selected':'' ?>>Pricing Question</option>
            <option value="General Feedback"     <?= (($_POST['subject'] ?? '')==='General Feedback')     ?'selected':'' ?>>General Feedback</option>
            <option value="Other"                <?= (($_POST['subject'] ?? '')==='Other')                ?'selected':'' ?>>Other</option>
          </select>
        </div>

        <!-- Message -->
        <div class="contact-field">
          <label><i class="fa-regular fa-comment-dots" style="color:var(--blue);margin-right:4px;"></i>Message <span style="color:#EF4444;">*</span></label>
          <textarea
            name="message"
            placeholder="Tell us how we can help you…"
            required
          ><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>

        <button type="submit" name="send" class="contact-submit-btn">
          <i class="fa-solid fa-paper-plane"></i>
          Send Message
        </button>

        <p class="response-promise">
          <i class="fa-solid fa-clock"></i>
          We typically respond within 24 hours
        </p>

      </form>
    </div>
  </div>

</main>

<!-- ══════════ MAP SECTION ══════════ -->
<section class="map-section">
  <div class="map-wrapper" style="padding:0;display:block;">
  <iframe
    src="https://maps.google.com/maps?q=Laascaanood,+Sool,+Somalia&output=embed&z=14"
    width="100%"
    height="280"
    style="border:0;display:block;"
    allowfullscreen=""
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
  ></iframe>
</div>
</section>

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