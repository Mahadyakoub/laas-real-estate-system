<?php
session_start();
require_once("../config/db.php");



/* ================= FILTERS ================= */
$location = trim($_GET['location'] ?? '');
$type     = trim($_GET['type']     ?? '');
$price    = trim($_GET['price']    ?? '');
$category = trim($_GET['category'] ?? '');

/* ================= QUERY ================= */
$sql = "SELECT property_id, title, location, price, image, category,
               description, bedrooms, bathrooms, size
        FROM properties
        WHERE status='Available'";

$params = [];
$types  = "";

if ($location !== '') {
    $sql .= " AND location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

if ($type !== '') {
    $sql .= " AND category LIKE ?";
    $params[] = "%$type%";
    $types .= "s";
}

if ($price === 'low') {
    $sql .= " AND price < ?";
    $params[] = 500;
    $types .= "i";
} elseif ($price === 'mid') {
    $sql .= " AND price BETWEEN ? AND ?";
    $params[] = 500;
    $params[] = 1000;
    $types .= "ii";
} elseif ($price === 'high') {
    $sql .= " AND price > ?";
    $params[] = 1000;
    $types .= "i";
}

if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY property_id DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) die("Failed to prepare property query.");
if (!empty($params)) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result       = mysqli_stmt_get_result($stmt);
$totalResults = $result ? mysqli_num_rows($result) : 0;

$isLoggedIn = isset($_SESSION['user_id']);

function shortDesc($text) {
    $text = trim((string)$text);
    if ($text === '') return "No description available.";
    return strlen($text) > 100 ? substr($text, 0, 100) . "…" : $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Properties | Laas Real Estate</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/properties.css">
</head>

<body>

<!-- ══════════ NAVBAR ══════════ -->
<header class="navbar">
  <div class="brand">
    <img src="../assets/images/logo.jpg" alt="Laas Real Estate Logo">
    <span>Laas Real Estate</span>
  </div>

  <nav class="nav-links">
    <a href="home.php">Home</a>
    <a href="properties.php" class="active">Properties</a>
    <a href="appointment.php">Appointments</a>
    <a href="feedback.php">Feedback</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
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

<!-- ══════════ TRUST TICKER ══════════ -->
<div class="trust-bar">
  <div class="trust-bar-inner">
    <span>No Hidden Fees</span>
    <span>Verified Listings</span>
    <span>Fast Appointments</span>
    <span>Trusted By Clients</span>
    <span>Clear Pricing</span>
    <span>No Hidden Fees</span>
    <span>Verified Listings</span>
    <span>Fast Appointments</span>
    <span>Trusted By Clients</span>
    <span>Clear Pricing</span>
  </div>
</div>

<!-- ══════════ MAIN ══════════ -->
<main class="featured">

  <!-- HEADING -->
  <div class="section-heading">
    <span class="section-tag">Browse Listings</span>
    <h2>Find Your Perfect Property</h2>
    <p>Carefully selected listings with clear details, transparent pricing, and quick booking access.</p>
  </div>

  <!-- FILTER FORM -->
  <form class="modern-filter" method="get">

    <div class="filter-group">
      <label>Location</label>
      <input
        type="text"
        name="location"
        placeholder="e.g. Laascaanood, Sool"
        value="<?= htmlspecialchars($location) ?>"
      >
    </div>

    <div class="filter-group">
      <label>Property Type</label>
      <select name="type">
        <option value="">Any type</option>
        <option value="House" <?= $type==='House'?'selected':'' ?>>House</option>
        <option value="Land"  <?= $type==='Land' ?'selected':'' ?>>Land</option>
      </select>
    </div>

    <div class="filter-group">
      <label>Price Range</label>
      <select name="price">
        <option value="">Any price</option>
        <option value="low"  <?= $price==='low' ?'selected':'' ?>>Below $500</option>
        <option value="mid"  <?= $price==='mid' ?'selected':'' ?>>$500 – $1,000</option>
        <option value="high" <?= $price==='high'?'selected':'' ?>>Above $1,000</option>
      </select>
    </div>

    <?php if ($category !== ''): ?>
      <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
    <?php endif; ?>

    <div class="filter-submit">
      <button type="submit" class="btn-primary">
        <i class="fa-solid fa-magnifying-glass"></i> Search Now
      </button>
    </div>

  </form>

  <!-- CATEGORY TABS -->
  <?php
  $baseQuery = [];
  if ($location !== '') $baseQuery['location'] = $location;
  if ($type     !== '') $baseQuery['type']     = $type;
  if ($price    !== '') $baseQuery['price']    = $price;
  $baseQS = !empty($baseQuery) ? '&' . http_build_query($baseQuery) : '';
  ?>

  <div class="category-tabs">
    <a href="properties.php<?= !empty($baseQuery) ? '?' . http_build_query($baseQuery) : '' ?>">
      <button type="button" class="<?= $category === '' ? 'active' : '' ?>">
        <i class="fa-solid fa-border-all"></i> All Properties
      </button>
    </a>
    <a href="properties.php?category=House+-+Rental<?= $baseQS ?>">
      <button type="button" class="<?= $category === 'House - Rental' ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i> Rentals
      </button>
    </a>
    <a href="properties.php?category=House+-+Sale<?= $baseQS ?>">
      <button type="button" class="<?= $category === 'House - Sale' ? 'active' : '' ?>">
        <i class="fa-solid fa-house-chimney"></i> House Sales
      </button>
    </a>
    <a href="properties.php?category=Land+-+Sale<?= $baseQS ?>">
      <button type="button" class="<?= $category === 'Land - Sale' ? 'active' : '' ?>">
        <i class="fa-solid fa-mountain-sun"></i> Land Sales
      </button>
    </a>
  </div>

  <!-- RESULTS COUNT -->
  <p class="results-count">
    <strong><?= $totalResults ?></strong>
    propert<?= $totalResults === 1 ? 'y' : 'ies' ?> found
  </p>

  <!-- GRID -->
  <section class="property-grid">

  <?php if ($totalResults > 0): ?>
    <?php while ($row = mysqli_fetch_assoc($result)): ?>

      <div class="property-card">

        <div class="property-image">
          <?php if (!empty($row['image'])): ?>
            <img
              src="../assets/images/properties/<?= htmlspecialchars($row['image']) ?>"
              alt="<?= htmlspecialchars($row['title']) ?>"
              loading="lazy"
            >
          <?php else: ?>
            <img src="../assets/images/admin.jpg" alt="No image" loading="lazy">
          <?php endif; ?>

          <span class="property-tag">
            <?= htmlspecialchars($row['category'] ?: 'Property') ?>
          </span>
        </div>

        <div class="property-info">

          <div class="property-top">
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <span class="property-price">
              $<?= number_format((float)$row['price'], 0) ?>
            </span>
          </div>

          <p class="property-location">
            <i class="fa-solid fa-location-dot"></i>
            <?= htmlspecialchars($row['location']) ?>
          </p>

          <div class="property-divider"></div>

          <div class="property-features">
            <span><i class="fa-solid fa-bed"></i> <?= (int)$row['bedrooms'] ?> Beds</span>
            <span><i class="fa-solid fa-bath"></i> <?= (int)$row['bathrooms'] ?> Baths</span>
            <span><i class="fa-solid fa-ruler-combined"></i> <?= (int)$row['size'] ?> m²</span>
          </div>

          <p class="property-description">
            <?= htmlspecialchars(shortDesc($row['description'] ?: '')) ?>
          </p>

          <div class="property-actions">
            <!-- View Details — always visible to everyone -->
            <a href="property_details.php?id=<?= (int)$row['property_id'] ?>" class="btn-ghost small-btn">
              <i class="fa-regular fa-eye"></i> View Details
            </a>

            <!-- Book Viewing — redirect guests to login -->
            <?php if ($isLoggedIn): ?>
              <a href="appointment.php?property_id=<?= (int)$row['property_id'] ?>" class="btn-primary small-btn">
                <i class="fa-regular fa-calendar"></i> Book Viewing
              </a>
            <?php else: ?>
              <a href="../auth/login.php?redirect=user/appointment.php%3Fproperty_id%3D<?= (int)$row['property_id'] ?>" class="btn-primary small-btn">
                <i class="fa-regular fa-calendar"></i> Book Viewing
              </a>
            <?php endif; ?>
          </div>

        </div>
      </div>

    <?php endwhile; ?>

  <?php else: ?>
    <div class="empty-state-card">
      <i class="fa-solid fa-house-circle-xmark"></i>
      <h3>No Properties Found</h3>
      <p>Try adjusting your filters to discover more listings.</p>
    </div>
  <?php endif; ?>

  </section>

</main>

</body>
</html>