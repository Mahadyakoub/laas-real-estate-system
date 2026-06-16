<?php
session_start();
require_once("../config/db.php");

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

function fetchCount($conn, $query) {
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) return 0;
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $total);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    return (int)$total;
}

/* ===== KPIs ===== */
$totalUsers        = fetchCount($conn, "SELECT COUNT(*) FROM users WHERE role='client'");
$totalProperties   = fetchCount($conn, "SELECT COUNT(*) FROM properties");
$totalAppointments = fetchCount($conn, "SELECT COUNT(*) FROM appointments");

/* ===== APPOINTMENT STATUS SUMMARY ===== */
$statusRows = [];
$s = mysqli_prepare($conn, "SELECT status, COUNT(*) AS total FROM appointments GROUP BY status ORDER BY status");
if ($s) { mysqli_stmt_execute($s); $r = mysqli_stmt_get_result($s); while ($row = mysqli_fetch_assoc($r)) $statusRows[] = $row; mysqli_stmt_close($s); }

/* ===== ALL APPOINTMENTS ===== */
$allAppointments = [];
$s = mysqli_prepare($conn,
    "SELECT u.name AS client_name, p.title AS property_title,
            a.appointment_date, a.status, a.note
     FROM appointments a
     JOIN users u ON a.user_id = u.user_id
     JOIN properties p ON a.property_id = p.property_id
     ORDER BY a.appointment_date DESC");
if ($s) { mysqli_stmt_execute($s); $r = mysqli_stmt_get_result($s); while ($row = mysqli_fetch_assoc($r)) $allAppointments[] = $row; mysqli_stmt_close($s); }

/* ===== FILTER APPOINTMENTS BY STATUS (exact DB values: Pending, Approved, Rejected) ===== */
$pendingAppts   = array_values(array_filter($allAppointments, fn($r) => $r['status'] === 'Pending'));
$approvedAppts  = array_values(array_filter($allAppointments, fn($r) => $r['status'] === 'Approved'));
$rejectedAppts  = array_values(array_filter($allAppointments, fn($r) => $r['status'] === 'Rejected'));

/* ===== ALL PROPERTIES ===== */
$allProperties = [];
$s = mysqli_prepare($conn,
    "SELECT title, category, status, price, location, bedrooms, bathrooms, size
     FROM properties ORDER BY created_at DESC");
if ($s) { mysqli_stmt_execute($s); $r = mysqli_stmt_get_result($s); while ($row = mysqli_fetch_assoc($r)) $allProperties[] = $row; mysqli_stmt_close($s); }

/* ===== FILTER PROPERTIES
   Real category values: 'House - Sale', 'House - Rental', 'Land - Sale'
   Real status values:   'Available', 'Rented', 'Sold'
===== */
$availableProps  = array_values(array_filter($allProperties, fn($r) => $r['status'] === 'Available'));
$rentedProps     = array_values(array_filter($allProperties, fn($r) => $r['status'] === 'Rented'));
$soldProps       = array_values(array_filter($allProperties, fn($r) => $r['status'] === 'Sold'));
$forSaleProps    = array_values(array_filter($allProperties, fn($r) => $r['category'] === 'House - Sale'));
$rentalProps     = array_values(array_filter($allProperties, fn($r) => $r['category'] === 'House - Rental'));
$landProps       = array_values(array_filter($allProperties, fn($r) => $r['category'] === 'Land - Sale'));

/* ===== USERS (clients only) ===== */
$allUsers = [];
$s = mysqli_prepare($conn,
    "SELECT name, email, phone, created_at FROM users WHERE role='client' ORDER BY created_at DESC");
if ($s) { mysqli_stmt_execute($s); $r = mysqli_stmt_get_result($s); while ($row = mysqli_fetch_assoc($r)) $allUsers[] = $row; mysqli_stmt_close($s); }

/* ===== FEEDBACK ===== */
$allFeedback = [];
$s = mysqli_prepare($conn,
    "SELECT u.name AS client_name,
            COALESCE(p.title, 'General') AS property_title,
            f.rating, f.comment, f.created_at
     FROM feedback f
     JOIN users u ON f.user_id = u.user_id
     LEFT JOIN properties p ON f.property_id = p.property_id
     ORDER BY f.created_at DESC");
if ($s) { mysqli_stmt_execute($s); $r = mysqli_stmt_get_result($s); while ($row = mysqli_fetch_assoc($r)) $allFeedback[] = $row; mysqli_stmt_close($s); }

/* ===== CHART DATA ===== */
$statusLabels = array_column($statusRows, 'status');
$statusCounts = array_map('intval', array_column($statusRows, 'total'));

$printDate = date("d M Y, h:i A");

/* helper to render appointment table rows */
function apptRows($rows, $cols = 5) {
    if (empty($rows)) {
        echo "<tr><td colspan='{$cols}'>No records found.</td></tr>";
        return;
    }
    foreach ($rows as $i => $row) {
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['property_title']) . "</td>";
        echo "<td>" . date("d M Y", strtotime($row['appointment_date'])) . "</td>";
        if ($cols === 5)
            echo "<td><span class='ps " . strtolower($row['status']) . "'>" . htmlspecialchars($row['status']) . "</span></td>";
        echo "</tr>";
    }
}

/* helper to render property table rows */
function propRows($rows, $showCategory = true) {
    if (empty($rows)) {
        echo "<tr><td colspan='6'>No records found.</td></tr>";
        return;
    }
    foreach ($rows as $i => $row) {
        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        if ($showCategory) echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td><span class='ps " . strtolower(str_replace(' ', '-', $row['status'])) . "'>" . htmlspecialchars($row['status']) . "</span></td>";
        echo "<td>$" . number_format($row['price']) . "</td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "</tr>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Reports | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=6">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.print-btn {
  display:inline-flex;align-items:center;gap:8px;padding:10px 20px;
  background:linear-gradient(135deg,#2563eb,#1341C8);color:#fff;border:none;
  border-radius:10px;font-family:'Poppins',sans-serif;font-size:13.5px;font-weight:600;
  cursor:pointer;box-shadow:0 6px 18px rgba(37,99,235,.28);transition:all .22s;
}
.print-btn:hover{transform:translateY(-2px);box-shadow:0 10px 26px rgba(37,99,235,.38);}

/* ===== MODAL ===== */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(13,27,62,.45);backdrop-filter:blur(3px);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:#fff;border-radius:18px;padding:28px 26px;width:100%;max-width:540px;box-shadow:0 24px 64px rgba(14,30,80,.22);max-height:90vh;overflow-y:auto;}
.modal-box h3{font-size:17px;font-weight:700;color:#0D1B3E;margin-bottom:3px;}
.modal-box > p{font-size:12.5px;color:#64748b;margin-bottom:18px;}
.modal-group-title{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#94a3b8;margin:14px 0 8px;}
.report-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:4px;}
.report-option{display:flex;align-items:center;gap:9px;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all .16s;user-select:none;}
.report-option:hover{border-color:#2563eb;background:#eff6ff;}
.report-option.selected{border-color:#2563eb;background:#eff6ff;}
.report-option input[type=checkbox]{accent-color:#2563eb;width:14px;height:14px;flex-shrink:0;pointer-events:none;}
.ro-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;}
.ro-icon.blue{background:#dbeafe;color:#2563eb;}
.ro-icon.green{background:#dcfce7;color:#16a34a;}
.ro-icon.yellow{background:#fef9c3;color:#ca8a04;}
.ro-icon.red{background:#fee2e2;color:#dc2626;}
.ro-icon.purple{background:#ede9fe;color:#7c3aed;}
.ro-icon.teal{background:#ccfbf1;color:#0d9488;}
.ro-icon.orange{background:#ffedd5;color:#ea580c;}
.ro-icon.gray{background:#f1f5f9;color:#475569;}
.ro-label{font-size:12px;font-weight:600;color:#334155;line-height:1.3;}
.ro-sub{font-size:10.5px;color:#94a3b8;}
.modal-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:18px;padding-top:14px;border-top:1px solid #f1f5f9;}
.btn-cancel{padding:9px 18px;border:1.5px solid #e2e8f0;border-radius:9px;background:#fff;color:#64748b;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;}
.btn-print{padding:9px 20px;background:linear-gradient(135deg,#2563eb,#1341C8);color:#fff;border:none;border-radius:9px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px;}

/* ===== PRINT DOC (hidden on screen) ===== */
#printDoc { display: none; }

@media print {
  body > *        { display: none !important; }
  #printDoc       { display: block !important; font-family: 'Poppins', sans-serif; color: #0D1B3E; padding: 28px 36px; font-size: 11.5px; }

  .pd-header      { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 18px; }
  .ph-logo        { font-size: 18px; font-weight: 700; color: #0D1B3E; }
  .ph-sub         { font-size: 11px; color: #64748b; margin-top: 2px; }
  .ph-meta        { text-align: right; font-size: 11px; color: #64748b; line-height: 1.7; }

  .pd-kpis        { display: flex; gap: 12px; margin-bottom: 22px; }
  .pd-kpi         { flex: 1; border: 1.5px solid #e2e8f0; border-radius: 9px; padding: 10px 14px; text-align: center; }
  .pd-kpi strong  { display: block; font-size: 22px; font-weight: 700; color: #2563eb; }
  .pd-kpi span    { font-size: 9.5px; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }

  .pd-section           { margin-bottom: 22px; page-break-inside: avoid; }
  .pd-section-title     { font-size: 10.5px; font-weight: 700; color: #0D1B3E; text-transform: uppercase; letter-spacing: .07em; padding-bottom: 5px; border-bottom: 1px solid #e2e8f0; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
  .pd-section-title .badge { padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 600; background: #dbeafe; color: #1d4ed8; }

  table           { width: 100%; border-collapse: collapse; font-size: 10.5px; }
  th              { background: #f1f5f9; color: #475569; text-align: left; padding: 6px 9px; font-weight: 600; font-size: 9.5px; text-transform: uppercase; letter-spacing: .04em; border: 1px solid #e2e8f0; }
  td              { padding: 6px 9px; border: 1px solid #e2e8f0; color: #334155; }
  tr:nth-child(even) td { background: #f8fafc; }

  .ps             { display: inline-block; padding: 1px 8px; border-radius: 20px; font-size: 9px; font-weight: 600; }
  .ps.pending     { background: #fef9c3; color: #92400e; }
  .ps.approved    { background: #dcfce7; color: #15803d; }
  .ps.rejected    { background: #fee2e2; color: #b91c1c; }
  .ps.available   { background: #dcfce7; color: #15803d; }
  .ps.rented      { background: #e0e7ff; color: #4338ca; }
  .ps.sold        { background: #fee2e2; color: #b91c1c; }

  .pd-footer      { margin-top: 24px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; display: flex; justify-content: space-between; }
}
</style>
</head>
<body class="dashboard-body">

<!-- ========== PRINT DOCUMENT ========== -->
<div id="printDoc">
  <div class="pd-header">
    <div>
      <div class="ph-logo">Laas Real Estate &mdash; System Report</div>
      <div class="ph-sub">Administrative Analytics &amp; Operational Insights</div>
    </div>
    <div class="ph-meta">
      <div>Generated: <?= $printDate ?></div>
      <div>Prepared by: Admin</div>
    </div>
  </div>

  <!-- Always-visible KPIs -->
  <div class="pd-kpis">
    <div class="pd-kpi"><strong><?= $totalUsers ?></strong><span>Registered Clients</span></div>
    <div class="pd-kpi"><strong><?= $totalProperties ?></strong><span>Total Properties</span></div>
    <div class="pd-kpi"><strong><?= $totalAppointments ?></strong><span>Total Appointments</span></div>
  </div>

  <!-- ALL APPOINTMENTS -->
  <div class="pd-section" id="ps-all-appts">
    <div class="pd-section-title">All Appointments <span class="badge"><?= count($allAppointments) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Client</th><th>Property</th><th>Date</th><th>Status</th></tr></thead>
      <tbody><?php apptRows($allAppointments, 5); ?></tbody>
    </table>
  </div>

  <!-- PENDING -->
  <div class="pd-section" id="ps-pending">
    <div class="pd-section-title" style="color:#92400e">Pending Appointments <span class="badge" style="background:#fef9c3;color:#92400e"><?= count($pendingAppts) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Client</th><th>Property</th><th>Date</th></tr></thead>
      <tbody><?php apptRows($pendingAppts, 4); ?></tbody>
    </table>
  </div>

  <!-- APPROVED -->
  <div class="pd-section" id="ps-approved">
    <div class="pd-section-title" style="color:#15803d">Approved Appointments <span class="badge" style="background:#dcfce7;color:#15803d"><?= count($approvedAppts) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Client</th><th>Property</th><th>Date</th></tr></thead>
      <tbody><?php apptRows($approvedAppts, 4); ?></tbody>
    </table>
  </div>

  <!-- REJECTED -->
  <div class="pd-section" id="ps-rejected">
    <div class="pd-section-title" style="color:#b91c1c">Rejected Appointments <span class="badge" style="background:#fee2e2;color:#b91c1c"><?= count($rejectedAppts) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Client</th><th>Property</th><th>Date</th></tr></thead>
      <tbody><?php apptRows($rejectedAppts, 4); ?></tbody>
    </table>
  </div>

  <!-- ALL PROPERTIES -->
  <div class="pd-section" id="ps-all-props">
    <div class="pd-section-title">All Properties <span class="badge"><?= count($allProperties) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Location</th></tr></thead>
      <tbody><?php propRows($allProperties, true); ?></tbody>
    </table>
  </div>

  <!-- AVAILABLE -->
  <div class="pd-section" id="ps-available">
    <div class="pd-section-title" style="color:#15803d">Available Properties <span class="badge" style="background:#dcfce7;color:#15803d"><?= count($availableProps) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Location</th></tr></thead>
      <tbody><?php propRows($availableProps, true); ?></tbody>
    </table>
  </div>

  <!-- RENTED -->
  <div class="pd-section" id="ps-rented">
    <div class="pd-section-title" style="color:#4338ca">Rented Properties <span class="badge" style="background:#e0e7ff;color:#4338ca"><?= count($rentedProps) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Location</th></tr></thead>
      <tbody><?php propRows($rentedProps, true); ?></tbody>
    </table>
  </div>

  <!-- SOLD -->
  <div class="pd-section" id="ps-sold">
    <div class="pd-section-title" style="color:#b91c1c">Sold Properties <span class="badge" style="background:#fee2e2;color:#b91c1c"><?= count($soldProps) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Location</th></tr></thead>
      <tbody><?php propRows($soldProps, true); ?></tbody>
    </table>
  </div>

  <!-- FOR SALE (House - Sale) -->
  <div class="pd-section" id="ps-sale">
    <div class="pd-section-title">Houses For Sale <span class="badge" style="background:#ffedd5;color:#ea580c"><?= count($forSaleProps) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Location</th></tr></thead>
      <tbody><?php propRows($forSaleProps, true); ?></tbody>
    </table>
  </div>

  <!-- RENTALS (House - Rental) -->
  <div class="pd-section" id="ps-rental">
    <div class="pd-section-title">Rental Houses <span class="badge" style="background:#ede9fe;color:#7c3aed"><?= count($rentalProps) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Location</th></tr></thead>
      <tbody><?php propRows($rentalProps, true); ?></tbody>
    </table>
  </div>

  <!-- LAND (Land - Sale) -->
  <div class="pd-section" id="ps-land">
    <div class="pd-section-title">Land For Sale <span class="badge" style="background:#f1f5f9;color:#475569"><?= count($landProps) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Price</th><th>Location</th></tr></thead>
      <tbody><?php propRows($landProps, true); ?></tbody>
    </table>
  </div>

  <!-- USERS -->
  <div class="pd-section" id="ps-users">
    <div class="pd-section-title">Registered Clients <span class="badge"><?= count($allUsers) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th></tr></thead>
      <tbody>
        <?php if (empty($allUsers)): ?>
          <tr><td colspan="5">No users found.</td></tr>
        <?php else: foreach ($allUsers as $i => $row): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['phone'] ?? '—') ?></td>
            <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- FEEDBACK -->
  <div class="pd-section" id="ps-feedback">
    <div class="pd-section-title">Client Feedback <span class="badge" style="background:#ccfbf1;color:#0d9488"><?= count($allFeedback) ?></span></div>
    <table>
      <thead><tr><th>#</th><th>Client</th><th>Property</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
      <tbody>
        <?php if (empty($allFeedback)): ?>
          <tr><td colspan="6">No feedback found.</td></tr>
        <?php else: foreach ($allFeedback as $i => $row): ?>
          <tr>
            <td><?= $i+1 ?></td>
            <td><?= htmlspecialchars($row['client_name']) ?></td>
            <td><?= htmlspecialchars($row['property_title']) ?></td>
            <td><?= htmlspecialchars($row['rating']) ?>/5</td>
            <td><?= htmlspecialchars($row['comment']) ?></td>
            <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <div class="pd-footer">
    <span>Laas Real Estate &middot; Laascaanood</span>
    <span>Confidential &middot; <?= $printDate ?></span>
  </div>
</div>

<!-- ========== REPORT SELECTOR MODAL ========== -->
<div class="modal-overlay" id="reportModal">
  <div class="modal-box">
    <h3><i class="fa-solid fa-print" style="color:#2563eb;margin-right:8px;"></i>Select Report Sections</h3>
    <p>Tick the sections you want included in the printed report.</p>

    <!-- APPOINTMENTS -->
    <div class="modal-group-title"><i class="fa-solid fa-calendar" style="margin-right:5px;color:#2563eb;"></i>Appointments</div>
    <div class="report-grid">
      <label class="report-option">
        <input type="checkbox" value="all-appts">
        <div class="ro-icon blue"><i class="fa-solid fa-calendar"></i></div>
        <div><div class="ro-label">All Appointments</div><div class="ro-sub"><?= count($allAppointments) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="pending">
        <div class="ro-icon yellow"><i class="fa-solid fa-clock"></i></div>
        <div><div class="ro-label">Pending</div><div class="ro-sub"><?= count($pendingAppts) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="approved">
        <div class="ro-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div><div class="ro-label">Approved</div><div class="ro-sub"><?= count($approvedAppts) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="rejected">
        <div class="ro-icon red"><i class="fa-solid fa-circle-xmark"></i></div>
        <div><div class="ro-label">Rejected</div><div class="ro-sub"><?= count($rejectedAppts) ?> records</div></div>
      </label>
    </div>

    <!-- PROPERTIES -->
    <div class="modal-group-title"><i class="fa-solid fa-building" style="margin-right:5px;color:#0d9488;"></i>Properties</div>
    <div class="report-grid">
      <label class="report-option">
        <input type="checkbox" value="all-props">
        <div class="ro-icon teal"><i class="fa-solid fa-building"></i></div>
        <div><div class="ro-label">All Properties</div><div class="ro-sub"><?= count($allProperties) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="available">
        <div class="ro-icon green"><i class="fa-solid fa-door-open"></i></div>
        <div><div class="ro-label">Available</div><div class="ro-sub"><?= count($availableProps) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="rented">
        <div class="ro-icon purple"><i class="fa-solid fa-key"></i></div>
        <div><div class="ro-label">Rented</div><div class="ro-sub"><?= count($rentedProps) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="sold">
        <div class="ro-icon red"><i class="fa-solid fa-handshake"></i></div>
        <div><div class="ro-label">Sold</div><div class="ro-sub"><?= count($soldProps) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="sale">
        <div class="ro-icon orange"><i class="fa-solid fa-tag"></i></div>
        <div><div class="ro-label">Houses For Sale</div><div class="ro-sub"><?= count($forSaleProps) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="rental">
        <div class="ro-icon purple"><i class="fa-solid fa-house"></i></div>
        <div><div class="ro-label">Rental Houses</div><div class="ro-sub"><?= count($rentalProps) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="land">
        <div class="ro-icon gray"><i class="fa-solid fa-map"></i></div>
        <div><div class="ro-label">Land For Sale</div><div class="ro-sub"><?= count($landProps) ?> records</div></div>
      </label>
    </div>

    <!-- OTHER -->
    <div class="modal-group-title"><i class="fa-solid fa-users" style="margin-right:5px;color:#2563eb;"></i>Other</div>
    <div class="report-grid">
      <label class="report-option">
        <input type="checkbox" value="users">
        <div class="ro-icon blue"><i class="fa-solid fa-users"></i></div>
        <div><div class="ro-label">Clients</div><div class="ro-sub"><?= count($allUsers) ?> records</div></div>
      </label>
      <label class="report-option">
        <input type="checkbox" value="feedback">
        <div class="ro-icon teal"><i class="fa-solid fa-comments"></i></div>
        <div><div class="ro-label">Feedback</div><div class="ro-sub"><?= count($allFeedback) ?> records</div></div>
      </label>
    </div>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeModal()">Cancel</button>
      <button class="btn-print" onclick="doPrint()">
        <i class="fa-solid fa-print"></i> Print Selected
      </button>
    </div>
  </div>
</div>

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">
  <div class="logo">
    <img src="../assets/images/logo.jpg" alt="Laas Real Estate">
    <h2>Laas Admin</h2>
  </div>
  <nav class="menu">
    <a href="dashboard.php"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
    <a href="manage_properties.php"><i class="fa-solid fa-building"></i><span>Properties</span></a>
    <a href="manage_appointments.php"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a>
    <a href="view_feedback.php"><i class="fa-solid fa-comments"></i><span>Feedback</span></a>
    <a href="reports.php" class="active"><i class="fa-solid fa-file-lines"></i><span>Reports</span></a>
    <a href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
  </nav>
</aside>

<main class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
      <div>
        <h1><i class="fa-solid fa-file-chart-column"></i> System Reports</h1>
        <p>Administrative analytics and operational insights</p>
      </div>
    </div>
    <div class="topbar-right">
      <button class="print-btn" onclick="openModal()">
        <i class="fa-solid fa-print"></i> Print Report
      </button>
    </div>
  </div>

  <section class="stats">
    <div class="stat-card">
      <div class="kpi-icon users"><i class="fa-solid fa-users"></i></div>
      <div><h3><?= $totalUsers ?></h3><span>Total Clients</span></div>
    </div>
    <div class="stat-card">
      <div class="kpi-icon properties"><i class="fa-solid fa-building"></i></div>
      <div><h3><?= $totalProperties ?></h3><span>Total Properties</span></div>
    </div>
    <div class="stat-card">
      <div class="kpi-icon appointments"><i class="fa-solid fa-calendar"></i></div>
      <div><h3><?= $totalAppointments ?></h3><span>Total Appointments</span></div>
    </div>
  </section>

  <section class="charts">
    <div class="chart-card">
      <h3 class="section-title"><i class="fa-solid fa-chart-pie"></i> Appointment Status Distribution</h3>
      <canvas id="statusChart"></canvas>
    </div>
    <div class="chart-card">
      <h3 class="section-title"><i class="fa-solid fa-list-check"></i> Appointment Summary</h3>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>Status</th><th>Total</th></tr></thead>
          <tbody>
            <?php if (!empty($statusRows)): foreach ($statusRows as $row): ?>
              <tr>
                <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                <td><?= htmlspecialchars($row['total']) ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="2" class="empty-row">No data available.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="table-section reports-section">
    <h2 class="section-title"><i class="fa-solid fa-table"></i> Detailed Appointment Report</h2>
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>Client</th><th>Property</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (!empty($allAppointments)): foreach ($allAppointments as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['client_name']) ?></td>
              <td><?= htmlspecialchars($row['property_title']) ?></td>
              <td><?= date("d M Y", strtotime($row['appointment_date'])) ?></td>
              <td><span class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="4" class="empty-row">No appointment records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('collapsed'); }
function openModal()     { document.getElementById('reportModal').classList.add('open'); }
function closeModal()    { document.getElementById('reportModal').classList.remove('open'); }

document.getElementById('reportModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

// Tick handling via change event on checkboxes
document.querySelectorAll('.report-option input[type=checkbox]').forEach(function(cb) {
  cb.addEventListener('change', function() {
    this.closest('.report-option').classList.toggle('selected', this.checked);
  });
});

const ALL_SECTIONS = ['all-appts','pending','approved','rejected',
                      'all-props','available','rented','sold','sale','rental','land',
                      'users','feedback'];

function doPrint() {
  // Hide all sections
  ALL_SECTIONS.forEach(id => {
    const el = document.getElementById('ps-' + id);
    if (el) el.style.display = 'none';
  });

  // Show only selected
  const checked = document.querySelectorAll('#reportModal input[type=checkbox]:checked');
  if (checked.length === 0) {
    alert('Please select at least one section to print.');
    return;
  }
  checked.forEach(cb => {
    const el = document.getElementById('ps-' + cb.value);
    if (el) el.style.display = 'block';
  });

  closeModal();
  setTimeout(() => window.print(), 150);
}

new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($statusLabels) ?>,
    datasets: [{
      data: <?= json_encode($statusCounts) ?>,
      backgroundColor: ['#f59e0b','#16a34a','#dc2626','#2563eb'],
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>
</body>
</html>