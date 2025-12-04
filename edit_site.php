<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

if (!isset($_GET['site_id'])) { die('Invalid request. site_id is required.'); }
$site_id = (int)$_GET['site_id'];

$has_lcs_col = false;
$col_check_sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites' AND COLUMN_NAME = 'lcs_available'";
$col_exists = $conn->query($col_check_sql);
if ($col_exists && $col_exists->fetch_row()) { $has_lcs_col = true; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $address = $_POST['address'] ?? '';
    $division_name = $_POST['division_name'] ?? '';
    $contractor_name = $_POST['contractor_name'] ?? '';
    $site_incharge = $_POST['site_incharge'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $number_of_tubewell = isset($_POST['number_of_tubewell']) ? (int)$_POST['number_of_tubewell'] : 0;
    $lcs_available = isset($_POST['lcs_available']) ? (int)$_POST['lcs_available'] : 0;

    // Check if site name already exists (excluding current site)
    $check_sql = "SELECT id FROM sites WHERE site_name = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) { die('Database error: ' . $conn->error); }
    $check_stmt->bind_param('si', $site_name, $site_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error = 'Site name already exists. Please use a different site name.';
    } else {
        // Proceed with update if site name is unique
        if ($has_lcs_col) {
            $sql = "UPDATE sites SET site_name = ?, address = ?, division_name = ?, contractor_name = ?, site_incharge = ?, contact = ?, number_of_tubewell = ?, lcs_available = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { die('Database error: ' . $conn->error); }
            $stmt->bind_param('ssssssiii', $site_name, $address, $division_name, $contractor_name, $site_incharge, $contact, $number_of_tubewell, $lcs_available, $site_id);
        } else {
            $sql = "UPDATE sites SET site_name = ?, address = ?, division_name = ?, contractor_name = ?, site_incharge = ?, contact = ?, number_of_tubewell = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) { die('Database error: ' . $conn->error); }
            $stmt->bind_param('ssssssii', $site_name, $address, $division_name, $contractor_name, $site_incharge, $contact, $number_of_tubewell, $site_id);
        }

        if ($stmt->execute()) {
            header('Location: view_site.php?site_id='.(int)$site_id);
            exit();
        } else {
            $error = 'Error updating site: ' . $conn->error;
        }
    }
    $check_stmt->close();
}

$cols = $has_lcs_col ? 'id, site_name, address, division_name, contractor_name, site_incharge, contact, number_of_tubewell, lcs_available' : 'id, site_name, address, division_name, contractor_name, site_incharge, contact, number_of_tubewell';
$stmt = $conn->prepare("SELECT $cols FROM sites WHERE id = ?");
if (!$stmt) { die('Database error: ' . $conn->error); }
$stmt->bind_param('i', $site_id);
$stmt->execute();
$site = $stmt->get_result()->fetch_assoc();
if (!$site) { die('Site not found.'); }
$stmt->close();
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Site</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
 <?php include('header.php'); ?>
<div class="container">
    <div class="card">
        <div class="progress-steps">
            <div class="step active">Edit Site</div>
            <div class="step">Add Tubewells</div>
            <div class="step">Configure Parameters</div>
        </div>
        <h2 style="text-align:center; margin-bottom:2rem; color:#2d3748;">Edit Site</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" class="site-form">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">🏢 Site Name *</label>
                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($site['site_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">🏗️ Contractor Name</label>
                    <input type="text" name="contractor_name" class="form-control" value="<?php echo htmlspecialchars($site['contractor_name']); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">📍 Site Address *</label>
                <textarea name="address" class="form-control" required><?php echo htmlspecialchars($site['address']); ?></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">🏛️ Division Name</label>
                    <input type="text" name="division_name" class="form-control" value="<?php echo htmlspecialchars($site['division_name']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">👤 Site Incharge</label>
                    <input type="text" name="site_incharge" class="form-control" value="<?php echo htmlspecialchars($site['site_incharge']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">📞 Contact Number</label>
                    <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($site['contact']); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">💧 Number of Tubewells *</label>
                <input type="number" name="number_of_tubewell" class="form-control" min="1" value="<?php echo htmlspecialchars($site['number_of_tubewell']); ?>" required>
            </div>
            <?php if ($has_lcs_col): ?>
            <div class="form-group">
                <label class="form-label">📦 LCS Availability</label>
                <select name="lcs_available" class="form-control">
                    <option value="0" <?php echo empty($site['lcs_available']) ? 'selected' : ''; ?>>Not Available</option>
                    <option value="1" <?php echo !empty($site['lcs_available']) ? 'selected' : ''; ?>>Available</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="btn-group" style="justify-content:center; margin-top:2rem;">
                <a href="view_site.php?site_id=<?php echo (int)$site_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Site</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>