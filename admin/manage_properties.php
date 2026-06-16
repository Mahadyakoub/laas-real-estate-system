<?php
session_start();
require_once("../config/db.php");

/* ================= ADMIN ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_id     = $_SESSION['user_id'];
$message      = "";
$message_type = "success";

/* ================= LAND DETECTION ================= */
function isLand($title) {
    foreach (['land','plot','lot','parcel'] as $word) {
        if (stripos($title, $word) !== false) return true;
    }
    return false;
}

/* ================= HELPER: upload one file ================= */
function uploadImage($file_array, $index = null) {
    $allowed = ['jpg','jpeg','png','webp'];
    $upload_dir = "../assets/images/properties/";

    if ($index !== null) {
        // multi-file array: $_FILES['extra_images']
        $name  = $file_array['name'][$index];
        $tmp   = $file_array['tmp_name'][$index];
        $error = $file_array['error'][$index];
        $size  = $file_array['size'][$index];
    } else {
        $name  = $file_array['name'];
        $tmp   = $file_array['tmp_name'];
        $error = $file_array['error'];
        $size  = $file_array['size'];
    }

    if ($error !== UPLOAD_ERR_OK || empty($name)) return [null, null];
    if ($size > 5 * 1024 * 1024) return [null, "File too large (max 5 MB): $name"];

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return [null, "Invalid type: $name"];

    $newname = time() . '_' . mt_rand(1000,9999) . '.' . $ext;
    if (!move_uploaded_file($tmp, $upload_dir . $newname)) return [null, "Upload failed: $name"];

    return [$newname, null];
}

/* ================= DELETE EXTRA IMAGE ================= */
if (isset($_GET['delete_img'], $_GET['prop'])) {
    $img_id  = (int)$_GET['delete_img'];
    $prop_id = (int)$_GET['prop'];

    $ds = mysqli_prepare($conn, "SELECT image_path FROM property_images WHERE image_id = ? LIMIT 1");
    mysqli_stmt_bind_param($ds, "i", $img_id);
    mysqli_stmt_execute($ds);
    $dr = mysqli_fetch_assoc(mysqli_stmt_get_result($ds));
    mysqli_stmt_close($ds);

    if ($dr) {
        $f = "../assets/images/properties/" . $dr['image_path'];
        if (file_exists($f)) unlink($f);
        $del = mysqli_prepare($conn, "DELETE FROM property_images WHERE image_id = ?");
        mysqli_stmt_bind_param($del, "i", $img_id);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
    }
    header("Location: manage_properties.php?edit=$prop_id&msg=img_deleted");
    exit();
}

/* ================= FLASH MESSAGE ================= */
$flash_map = [
    'added'       => "Property added successfully.",
    'updated'     => "Property updated successfully.",
    'deleted'     => "Property deleted successfully.",
    'img_deleted' => "Image deleted successfully.",
    'error'       => "Something went wrong. Please try again.",
];
if (isset($_GET['msg']) && isset($flash_map[$_GET['msg']])) {
    $message = $flash_map[$_GET['msg']];
    if ($_GET['msg'] === 'error') $message_type = "error";
}

/* ================= FETCH PROPERTY FOR EDIT ================= */
$editProperty  = null;
$editImages    = [];

if (isset($_GET['edit'])) {
    $property_id = (int)$_GET['edit'];

    $stmt = mysqli_prepare($conn,
        "SELECT * FROM properties WHERE property_id = ? AND admin_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $property_id, $admin_id);
        mysqli_stmt_execute($stmt);
        $editProperty = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
    }

    // Load extra images if table exists
    $tc = mysqli_query($conn,
        "SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = 'property_images' LIMIT 1");
    if ($tc && mysqli_num_rows($tc) > 0) {
        $is = mysqli_prepare($conn,
            "SELECT image_id, image_path FROM property_images
             WHERE property_id = ? ORDER BY sort_order ASC");
        if ($is) {
            mysqli_stmt_bind_param($is, "i", $property_id);
            mysqli_stmt_execute($is);
            $ir = mysqli_stmt_get_result($is);
            while ($r = mysqli_fetch_assoc($ir)) $editImages[] = $r;
            mysqli_stmt_close($is);
        }
    }
}

/* ================= ADD / UPDATE PROPERTY ================= */
if (isset($_POST['save_property'])) {
    $title       = trim($_POST['title']       ?? '');
    $location    = trim($_POST['location']    ?? '');
    $price       = trim($_POST['price']       ?? '');
    $status      = trim($_POST['status']      ?? '');
    $category    = trim($_POST['category']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $bedrooms    = (int)($_POST['bedrooms']   ?? 0);
    $bathrooms   = (int)($_POST['bathrooms']  ?? 0);
    $size        = (int)($_POST['size']       ?? 0);
    $property_id = !empty($_POST['property_id']) ? (int)$_POST['property_id'] : null;
    $imageName   = $_POST['existing_image'] ?? '';

    if ($title === '' || $location === '' || $price === '' || $status === '' || $category === '') {
        $message = "All required fields must be filled."; $message_type = "error";
    } elseif (!is_numeric($price) || (float)$price <= 0) {
        $message = "Please enter a valid price."; $message_type = "error";
    } else {

        // ── Upload main image ──
        if (!empty($_FILES['image']['name'])) {
            [$newMain, $mainErr] = uploadImage($_FILES['image']);
            if ($mainErr) { $message = $mainErr; $message_type = "error"; }
            elseif ($newMain) $imageName = $newMain;
        }

        if ($message === "") {
            $priceValue = (float)$price;

            if ($property_id) {
                // UPDATE
                $stmt = mysqli_prepare($conn,
                    "UPDATE properties
                     SET title=?, location=?, price=?, status=?, category=?, image=?,
                         description=?, bedrooms=?, bathrooms=?, size=?
                     WHERE property_id=? AND admin_id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssdssssiiiii",
                        $title, $location, $priceValue, $status, $category, $imageName,
                        $description, $bedrooms, $bathrooms, $size, $property_id, $admin_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            } else {
                // INSERT — get new property_id
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO properties
                     (title, location, price, status, category, admin_id, image, description, bedrooms, bathrooms, size)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssdssissiii",
                        $title, $location, $priceValue, $status, $category,
                        $admin_id, $imageName, $description, $bedrooms, $bathrooms, $size);
                    mysqli_stmt_execute($stmt);
                    $property_id = (int)mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);
                }
            }

            // ── Upload extra images ──
            if ($property_id && !empty($_FILES['extra_images']['name'][0])) {
                // Ensure property_images table exists
                $tc = mysqli_query($conn,
                    "SELECT 1 FROM information_schema.tables
                     WHERE table_schema = DATABASE() AND table_name = 'property_images' LIMIT 1");
                if (!$tc || mysqli_num_rows($tc) === 0) {
                    mysqli_query($conn,
                        "CREATE TABLE IF NOT EXISTS `property_images` (
                          `image_id`    INT(11)      NOT NULL AUTO_INCREMENT,
                          `property_id` INT(11)      NOT NULL,
                          `image_path`  VARCHAR(255) NOT NULL,
                          `sort_order`  INT(11)      NOT NULL DEFAULT 0,
                          `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          PRIMARY KEY (`image_id`),
                          KEY `fk_pi_pid` (`property_id`),
                          CONSTRAINT `fk_pi_pid`
                            FOREIGN KEY (`property_id`) REFERENCES `properties`(`property_id`)
                            ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                }

                $sort = count($editImages); // continue sort after existing
                $uploadErrors = [];
                foreach ($_FILES['extra_images']['name'] as $i => $fname) {
                    if (empty($fname)) continue;
                    [$newImg, $imgErr] = uploadImage($_FILES['extra_images'], $i);
                    if ($imgErr) { $uploadErrors[] = $imgErr; continue; }
                    if ($newImg) {
                        $ins = mysqli_prepare($conn,
                            "INSERT INTO property_images (property_id, image_path, sort_order) VALUES (?,?,?)");
                        mysqli_stmt_bind_param($ins, "isi", $property_id, $newImg, $sort);
                        mysqli_stmt_execute($ins);
                        mysqli_stmt_close($ins);
                        $sort++;
                    }
                }
                if (!empty($uploadErrors)) {
                    $message = "Some images failed: " . implode(', ', $uploadErrors);
                    $message_type = "error";
                }
            }

            if ($message === "") {
                header("Location: manage_properties.php?msg=" . ($property_id && isset($_POST['property_id']) && $_POST['property_id'] ? 'updated' : 'added'));
                exit();
            }
        }
    }
}

/* ================= DELETE PROPERTY ================= */
if (isset($_GET['delete'])) {
    $property_id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn,
        "DELETE FROM properties WHERE property_id = ? AND admin_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $property_id, $admin_id);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: manage_properties.php?msg=" . ($ok ? 'deleted' : 'error'));
        exit();
    }
}

/* ================= FETCH ALL PROPERTIES ================= */
$result = false;
$stmt = mysqli_prepare($conn,
    "SELECT * FROM properties WHERE admin_id = ? ORDER BY property_id DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Properties | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=8">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ── Extra-image upload zone ── */
.extra-images-section {
  grid-column: 1 / -1;
  margin-top: 4px;
}
.extra-images-section label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 8px;
}

.drop-zone {
  border: 2px dashed #BFDBFE;
  background: #EFF6FF;
  border-radius: 14px;
  padding: 28px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.22s;
  position: relative;
}
.drop-zone:hover, .drop-zone.dragover {
  border-color: #2563EB;
  background: #DBEAFE;
}
.drop-zone input[type="file"] {
  position: absolute; inset: 0;
  opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.drop-zone-icon { font-size: 30px; color: #2563EB; margin-bottom: 8px; }
.drop-zone p { font-size: 13.5px; color: #475569; margin: 0; }
.drop-zone span { font-size: 12px; color: #94A3B8; }

/* preview strip */
#previewStrip {
  display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px;
}
.preview-thumb {
  position: relative; width: 88px; height: 72px;
  border-radius: 10px; overflow: hidden;
  border: 2px solid #BFDBFE;
  box-shadow: 0 2px 8px rgba(37,99,235,0.10);
}
.preview-thumb img { width: 100%; height: 100%; object-fit: cover; }
.preview-thumb .remove-preview {
  position: absolute; top: 3px; right: 3px;
  background: rgba(239,68,68,0.88); color: #fff;
  border: none; border-radius: 50%;
  width: 20px; height: 20px;
  font-size: 10px; cursor: pointer;
  display: grid; place-items: center;
}

/* existing images grid */
.existing-imgs {
  display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px;
}
.existing-img-wrap {
  position: relative; width: 100px; height: 80px;
  border-radius: 10px; overflow: hidden;
  border: 2px solid #E2E8F0;
  box-shadow: 0 2px 8px rgba(13,27,62,0.08);
}
.existing-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
.existing-img-wrap .del-img-btn {
  position: absolute; top: 4px; right: 4px;
  background: rgba(239,68,68,0.90); color: #fff;
  border: none; border-radius: 50%;
  width: 22px; height: 22px; font-size: 11px;
  cursor: pointer; display: grid; place-items: center;
  transition: 0.2s;
}
.existing-img-wrap .del-img-btn:hover { background: #DC2626; }
.existing-imgs-label {
  font-size: 12.5px; font-weight: 600;
  color: #64748B; margin-bottom: 6px;
  text-transform: uppercase; letter-spacing: 0.05em;
}
</style>
</head>

<body class="dashboard-body">

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
  <div class="logo">
    <img src="../assets/images/logo.jpg" alt="Laas Real Estate">
    <h2>Laas Admin</h2>
  </div>
  <nav class="menu">
    <a href="dashboard.php"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
    <a href="manage_properties.php" class="active"><i class="fa-solid fa-building"></i><span>Properties</span></a>
    <a href="manage_appointments.php"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a>
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
        <h1>
          <i class="fa-solid fa-building"></i>
          <?= $editProperty ? "Edit Property" : "Manage Properties" ?>
        </h1>
        <p>Add, update, and organize rental houses, sale houses, and land listings</p>
      </div>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $message_type === 'error' ? 'error' : 'success' ?>" style="margin-bottom: 20px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- ══ FORM ══ -->
  <section class="table-section" style="margin-bottom: 30px;">
    <h2 class="section-title">
      <i class="fa-solid fa-pen-to-square"></i>
      <?= $editProperty ? "Update Property Information" : "Add New Property" ?>
    </h2>

    <form method="post" enctype="multipart/form-data" class="property-form-grid">
      <input type="hidden" name="property_id"    value="<?= htmlspecialchars($editProperty['property_id'] ?? '') ?>">
      <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editProperty['image'] ?? '') ?>">

      <!-- Title -->
      <div class="form-group">
        <label>Property Title</label>
        <input type="text" name="title" placeholder="Property Title" required
          value="<?= htmlspecialchars($editProperty['title'] ?? '') ?>">
      </div>

      <!-- Location -->
      <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" placeholder="Location" required
          value="<?= htmlspecialchars($editProperty['location'] ?? '') ?>">
      </div>

      <!-- Price -->
      <div class="form-group">
        <label>Price (USD)</label>
        <input type="number" step="0.01" name="price" placeholder="Price (USD)" required
          value="<?= htmlspecialchars($editProperty['price'] ?? '') ?>">
      </div>

      <!-- Status -->
      <div class="form-group">
        <label>Status</label>
        <select name="status" required>
          <option value="">Select Status</option>
          <?php foreach (['Available','Rented','Sold'] as $s): ?>
            <option value="<?= $s ?>" <?= ($editProperty && $editProperty['status']===$s)?'selected':'' ?>>
              <?= $s ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Category -->
      <div class="form-group">
        <label>Category</label>
        <select name="category" required>
          <option value="">Select Property Category</option>
          <?php foreach (['House - Rental','House - Sale','Land - Sale'] as $cat): ?>
            <option value="<?= $cat ?>" <?= ($editProperty && $editProperty['category']===$cat)?'selected':'' ?>>
              <?= $cat ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Main Image -->
      <div class="form-group">
        <label>
          Main / Cover Image
          <?php if (!empty($editProperty['image'])): ?>
            <small style="color:#64748B;font-weight:400;">— leave blank to keep current</small>
          <?php endif; ?>
        </label>

        <?php if (!empty($editProperty['image'])): ?>
          <div style="margin-bottom:8px;">
            <img
              src="../assets/images/properties/<?= htmlspecialchars($editProperty['image']) ?>"
              style="height:64px;border-radius:8px;object-fit:cover;border:2px solid #DBEAFE;"
              alt="Current main image"
            >
          </div>
        <?php endif; ?>

        <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
      </div>

      <!-- Description -->
      <div class="form-group full-width">
        <label>Description</label>
        <textarea name="description" rows="4" placeholder="Write a clear property description..."
        ><?= htmlspecialchars($editProperty['description'] ?? '') ?></textarea>
      </div>

      <!-- Bedrooms / Bathrooms / Size -->
      <div class="form-group">
        <label>Bedrooms</label>
        <input type="number" name="bedrooms" min="0"
          value="<?= htmlspecialchars($editProperty['bedrooms'] ?? 0) ?>">
      </div>

      <div class="form-group">
        <label>Bathrooms</label>
        <input type="number" name="bathrooms" min="0"
          value="<?= htmlspecialchars($editProperty['bathrooms'] ?? 0) ?>">
      </div>

      <div class="form-group">
        <label>Size (m²)</label>
        <input type="number" name="size" min="0"
          value="<?= htmlspecialchars($editProperty['size'] ?? 0) ?>">
      </div>

      <!-- ══ EXTRA IMAGES ══ -->
      <div class="extra-images-section">

        <!-- Existing extra images (edit mode) -->
        <?php if (!empty($editImages)): ?>
          <p class="existing-imgs-label">
            <i class="fa-solid fa-images" style="color:#2563EB;"></i>
            Gallery Images (<?= count($editImages) ?>) — click × to delete
          </p>
          <div class="existing-imgs">
            <?php foreach ($editImages as $ei): ?>
              <div class="existing-img-wrap">
                <img
                  src="../assets/images/properties/<?= htmlspecialchars($ei['image_path']) ?>"
                  alt="Gallery photo"
                >
                <button
                  type="button"
                  class="del-img-btn"
                  onclick="confirmDeleteImg(<?= (int)$ei['image_id'] ?>, <?= (int)$editProperty['property_id'] ?>)"
                  title="Delete this image"
                >
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Upload new extra images -->
        <label style="margin-top:<?= !empty($editImages)?'18px':'0' ?>;">
          <i class="fa-solid fa-plus" style="color:#2563EB;"></i>
          Add More Gallery Images
          <span style="font-weight:400;color:#94A3B8;"> (JPG/PNG/WEBP, max 5 MB each)</span>
        </label>

        <div class="drop-zone" id="dropZone">
          <input
            type="file"
            name="extra_images[]"
            id="extraImagesInput"
            accept=".jpg,.jpeg,.png,.webp"
            multiple
          >
          <div class="drop-zone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
          <p>Drag &amp; drop images here, or <strong style="color:#2563EB;">click to browse</strong></p>
          <span>You can select multiple files at once</span>
        </div>

        <!-- Preview strip -->
        <div id="previewStrip"></div>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="submit" name="save_property" class="primary-btn">
          <i class="fa-solid fa-save"></i>
          <?= $editProperty ? "Update Property" : "Add Property" ?>
        </button>

        <?php if ($editProperty): ?>
          <a href="manage_properties.php" class="btn edit">
            <i class="fa-solid fa-xmark"></i> Cancel Edit
          </a>
        <?php endif; ?>
      </div>
    </form>
  </section>

  <!-- ══ PROPERTIES TABLE ══ -->
  <section class="table-section">
    <h2 class="section-title">
      <i class="fa-solid fa-list"></i> Your Properties
    </h2>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Property</th>
            <th>Category</th>
            <th>Location</th>
            <th>Details</th>
            <th>Price</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td>
                <?= htmlspecialchars($row['title']) ?>
                <?php if (isLand($row['title'])): ?>
                  <span class="badge-land">LAND</span>
                <?php endif; ?>
              </td>
              <td><strong><?= htmlspecialchars($row['category']) ?></strong></td>
              <td><?= htmlspecialchars($row['location']) ?></td>
              <td>
                <div class="property-mini-details">
                  <span><i class="fa-solid fa-bed"></i> <?= (int)$row['bedrooms'] ?></span>
                  <span><i class="fa-solid fa-bath"></i> <?= (int)$row['bathrooms'] ?></span>
                  <span><i class="fa-solid fa-ruler-combined"></i> <?= (int)$row['size'] ?> m²</span>
                </div>
              </td>
              <td>$<?= number_format((float)$row['price'], 2) ?></td>
              <td>
                <span class="status <?= strtolower($row['status']) ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td>
                <div class="action-group">
                  <a href="?edit=<?= (int)$row['property_id'] ?>" class="btn edit">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <a href="?delete=<?= (int)$row['property_id'] ?>" class="btn delete"
                     onclick="return confirm('Delete this property permanently?');">
                    <i class="fa-solid fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php if (!empty($row['description'])): ?>
              <tr class="property-description-row">
                <td colspan="7">
                  <strong>Description:</strong> <?= htmlspecialchars($row['description']) ?>
                </td>
              </tr>
            <?php endif; ?>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="empty-row">No properties found yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</main>

<!-- ══ SCRIPTS ══ -->
<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('collapsed');
}

/* ── Image delete confirmation ── */
function confirmDeleteImg(imgId, propId) {
  if (confirm('Delete this gallery image permanently?')) {
    window.location.href = 'manage_properties.php?delete_img=' + imgId + '&prop=' + propId;
  }
}

/* ── Drag & drop highlight ── */
const dropZone = document.getElementById('dropZone');
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault();
  dropZone.classList.remove('dragover');
  const input = document.getElementById('extraImagesInput');
  // Transfer dragged files to the input
  const dt = new DataTransfer();
  [...(input.files || [])].forEach(f => dt.items.add(f));
  [...e.dataTransfer.files].forEach(f => dt.items.add(f));
  input.files = dt.files;
  renderPreviews(input.files);
});

/* ── Preview thumbnails ── */
document.getElementById('extraImagesInput').addEventListener('change', function() {
  renderPreviews(this.files);
});

function renderPreviews(files) {
  const strip = document.getElementById('previewStrip');
  strip.innerHTML = '';
  [...files].forEach((file, i) => {
    if (!file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = e => {
      const wrap = document.createElement('div');
      wrap.className = 'preview-thumb';
      wrap.innerHTML = `
        <img src="${e.target.result}" alt="preview">
        <button type="button" class="remove-preview" data-index="${i}" title="Remove">×</button>
      `;
      strip.appendChild(wrap);
    };
    reader.readAsDataURL(file);
  });
}

/* Remove from preview (doesn't remove from file input — just visual) */
document.getElementById('previewStrip').addEventListener('click', e => {
  if (e.target.classList.contains('remove-preview')) {
    e.target.closest('.preview-thumb').remove();
  }
});
</script>

</body>
</html>