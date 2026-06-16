<?php
session_start();
require_once("../config/db.php");

$current    = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);

/* Fetch featured properties */
$properties = mysqli_query($conn, "
    SELECT property_id, title, location, price, image, category, bedrooms, bathrooms, size, description
    FROM properties
    WHERE status = 'Available'
    ORDER BY property_id DESC
    LIMIT 6
");

function shortDescription($text, $limit = 95) {
    $text = trim((string)$text);
    if ($text === '') return "Discover a verified property with clear information and a smooth booking experience.";
    if (mb_strlen($text) <= $limit) return $text;
    return mb_substr($text, 0, $limit) . "...";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laas Real Estate | Find Your Perfect Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/home.css?v=<?= time() ?>">
</head>
<body>

<!-- ================= NAVBAR ================= -->
<header class="navbar">
    <a href="home.php" class="brand">
        <img src="../assets/images/logo.jpg" alt="Laas Real Estate Logo">
        <span>Laas Real Estate</span>
    </a>

    <nav class="nav-links">
        <a href="home.php"        class="<?= $current === 'home.php'        ? 'active' : '' ?>">Home</a>
        <a href="properties.php"  class="<?= $current === 'properties.php'  ? 'active' : '' ?>">Properties</a>
        <a href="appointment.php" class="<?= $current === 'appointment.php' ? 'active' : '' ?>">Appointments</a>
        <a href="feedback.php"    class="<?= $current === 'feedback.php'    ? 'active' : '' ?>">Feedback</a>
        <a href="about.php"       class="<?= $current === 'about.php'       ? 'active' : '' ?>">About</a>
        <a href="contact.php"     class="<?= $current === 'contact.php'     ? 'active' : '' ?>">Contact</a>
    </nav>

    <div class="nav-action">
        <?php if ($isLoggedIn): ?>
            <a href="appointment.php"      class="btn-ghost">My Appointments</a>
            <a href="../auth/logout.php"   class="btn-primary">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        <?php else: ?>
            <a href="../auth/login.php"    class="btn-ghost">Login</a>
            <a href="../auth/register.php" class="btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<!-- ================= HERO ================= -->
<section class="hero">
    <div class="hero-inner">

        <!-- Left: Copy -->
        <div class="hero-copy">
            <div class="hero-eyebrow">
                <i class="fa-solid fa-location-dot"></i>
                #1 Trusted Property Discovery in Laascaanood
            </div>

            <h1>
                Find your <em>perfect</em><br>
                place to call home.
            </h1>

            <p class="hero-subtitle">
                Explore verified rental homes, houses for sale, and land opportunities
                through one modern platform built for clarity, trust, and speed.
            </p>

            <div class="hero-actions">
                <a href="properties.php" class="btn-primary btn-large">
                    <i class="fa-solid fa-magnifying-glass"></i> Explore Properties
                </a>
                <a href="about.php" class="btn-secondary btn-large">Learn More</a>
            </div>

            <div class="hero-stats">
                <div class="stat-item">
                    <strong>100%</strong>
                    <span>Verified Listings</span>
                </div>
                <div class="stat-item">
                    <strong>Fast</strong>
                    <span>Booking Process</span>
                </div>
                <div class="stat-item">
                    <strong>Trusted</strong>
                    <span>Client Feedback</span>
                </div>
            </div>
        </div>

        <!-- Right: Search Panel -->
        <div class="hero-panel">
            <div class="hero-panel-card">
                <div class="panel-header">
                    <h3>Find your ideal property</h3>
                    <p>Search by location, type, or price range</p>
                </div>

                <form method="get" action="properties.php" class="hero-search-form">
                    <div class="field">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="e.g. Laascaanood, Sool">
                    </div>

                    <div class="field">
                        <label>Property Type</label>
                        <select name="type">
                            <option value="">Any type</option>
                            <option value="House">House</option>
                            <option value="Land">Land</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Price Range</label>
                        <select name="price">
                            <option value="">Any price</option>
                            <option value="low">Below $500</option>
                            <option value="mid">$500 – $1,000</option>
                            <option value="high">Above $1,000</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary full-btn">
                        <i class="fa-solid fa-magnifying-glass"></i> Search Now
                    </button>
                </form>
            </div>
        </div>

    </div>
</section>

<!-- ================= CATEGORY STRIP ================= -->
<div class="category-strip">
    <div class="category-strip-inner">
        <a href="properties.php?category=House+-+Rental" class="category-pill">
            <i class="fa-solid fa-house"></i> Rentals
        </a>
        <a href="properties.php?category=House+-+Sale" class="category-pill">
            <i class="fa-solid fa-house-circle-check"></i> House Sales
        </a>
        <a href="properties.php?category=Land+-+Sale" class="category-pill">
            <i class="fa-solid fa-map-location-dot"></i> Land Sales
        </a>
    </div>
</div>

<!-- ================= TRUST BAR ================= -->
<div class="trust-bar">
    <div class="trust-bar-track">
        <span><i class="fa-solid fa-circle-dot"></i> Verified Listings</span>
        <span><i class="fa-solid fa-circle-dot"></i> Fast Appointments</span>
        <span><i class="fa-solid fa-circle-dot"></i> Trusted by Clients</span>
        <span><i class="fa-solid fa-circle-dot"></i> Clear Pricing</span>
        <span><i class="fa-solid fa-circle-dot"></i> No Hidden Fees</span>
        <span><i class="fa-solid fa-circle-dot"></i> Verified Listings</span>
        <span><i class="fa-solid fa-circle-dot"></i> Fast Appointments</span>
        <span><i class="fa-solid fa-circle-dot"></i> Trusted by Clients</span>
        <span><i class="fa-solid fa-circle-dot"></i> Clear Pricing</span>
        <span><i class="fa-solid fa-circle-dot"></i> No Hidden Fees</span>
    </div>
</div>

<!-- ================= FEATURED PROPERTIES ================= -->
<section class="featured">
    <div class="section-container">
        <div class="section-heading">
            <div>
                <div class="section-label">Featured Collection</div>
                <h2>Curated Properties</h2>
                <p>Carefully selected listings with clear details, transparent pricing, and quick booking access.</p>
            </div>
            <a href="properties.php" class="text-link">
                View all <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="property-grid">
            <?php if ($properties && mysqli_num_rows($properties) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($properties)): ?>
                    <div class="property-card">
                        <div class="property-image">
                            <?php if (!empty($row['image'])): ?>
                                <img src="../assets/images/properties/<?= htmlspecialchars($row['image']) ?>" alt="Property Image">
                            <?php else: ?>
                                <img src="../assets/images/admin.jpg" alt="No Image Available">
                            <?php endif; ?>

                            <?php if (!empty($row['category'])): ?>
                                <span class="property-tag"><?= htmlspecialchars($row['category']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="property-info">
                            <div class="property-top">
                                <h3><?= htmlspecialchars($row['title']) ?></h3>
                                <span class="property-price">$<?= number_format((float)$row['price'], 2) ?></span>
                            </div>

                            <p class="property-location">
                                <i class="fa-solid fa-location-dot"></i>
                                <?= htmlspecialchars($row['location']) ?>
                            </p>

                            <div class="property-features">
                                <span><i class="fa-solid fa-bed"></i> <?= (int)$row['bedrooms'] ?> Beds</span>
                                <span><i class="fa-solid fa-bath"></i> <?= (int)$row['bathrooms'] ?> Baths</span>
                                <span><i class="fa-solid fa-ruler-combined"></i> <?= (int)$row['size'] ?> m²</span>
                            </div>

                            <p class="property-description">
                                <?= htmlspecialchars(shortDescription($row['description'])) ?>
                            </p>

                            <div class="property-actions">
                                <!-- View Details — always free for everyone -->
                                <a href="property_details.php?id=<?= (int)$row['property_id'] ?>" class="btn-ghost small-btn">
                                    View Details
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
                    <h3>No properties available yet</h3>
                    <p>Please check back later for new verified listings.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ================= WHY CHOOSE US ================= -->
<section class="experience">
    <div class="section-container">
        <div class="section-heading centered">
            <div>
                <div class="section-label">Why Choose Us</div>
                <h2>A smoother way to discover property</h2>
                <p>Built to reduce confusion, improve trust, and make property search more convenient for every client.</p>
            </div>
        </div>

        <div class="experience-grid">
            <div class="experience-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h4>Verified Listings</h4>
                <p>Every property is carefully reviewed before it appears on the platform — giving you confidence in every listing.</p>
            </div>

            <div class="experience-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h4>Simple Booking</h4>
                <p>Book property viewings quickly and directly through the platform without unnecessary steps or confusion.</p>
            </div>

            <div class="experience-card">
                <div class="icon-wrap">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h4>Honest Feedback</h4>
                <p>Read real client experiences and make better, informed decisions with genuine transparency.</p>
            </div>
        </div>
    </div>
</section>

<!-- ================= CTA ================= -->
<section class="cta">
    <div class="section-container">
        <div class="cta-content">
            <div class="cta-text">
                <div class="section-label">Start Today</div>
                <h2>Ready to find your next property?</h2>
                <p>Browse verified listings, compare your options, and request a property viewing in minutes — all in one place.</p>
            </div>
            <div class="cta-actions">
                <a href="properties.php" class="btn-primary btn-large">
                    <i class="fa-solid fa-magnifying-glass"></i> Explore Now
                </a>
                <a href="contact.php" class="btn-outline-light btn-large">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<!-- ================= FOOTER ================= -->
<footer class="footer">
    <div class="footer-main">
        <div class="footer-brand">
            <div class="brand-name">
                <img src="../assets/images/logo.jpg" alt="Logo">
                Laas Real Estate
            </div>
            <p>Smart property discovery for rentals, house sales, and land opportunities in Laascaanood.</p>
        </div>

        <div class="footer-col">
            <h5>Navigation</h5>
            <a href="home.php">Home</a>
            <a href="properties.php">Properties</a>
            <a href="appointment.php">Appointments</a>
            <a href="feedback.php">Feedback</a>
        </div>

        <div class="footer-col">
            <h5>Company</h5>
            <a href="about.php">About Us</a>
            <a href="contact.php">Contact</a>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© <?= date("Y") ?> Laas Real Estate. All rights reserved.</p>
        <p>Laascaanood, Sool Region, Northeast state</p>
    </div>
</footer>

</body>
</html>