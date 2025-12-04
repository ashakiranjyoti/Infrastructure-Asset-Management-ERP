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

// Ensure lcs table exists similar to tubewells but without SIM and with lcs_name
$conn->query("CREATE TABLE IF NOT EXISTS lcs LIKE tubewells");
// Rename name column if needed
$has_name = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lcs' AND COLUMN_NAME = 'lcs_name'");
if (!$has_name || !$has_name->fetch_row()) {
    $has_tw = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lcs' AND COLUMN_NAME = 'tubewell_name'");
    if ($has_tw && $has_tw->fetch_row()) { $conn->query("ALTER TABLE lcs CHANGE tubewell_name lcs_name VARCHAR(255)"); }
}
// Drop SIM column if present (schema uses sim_no in tubewells file)
$has_sim = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lcs' AND COLUMN_NAME = 'sim_no'");
if ($has_sim && $has_sim->fetch_row()) { $conn->query("ALTER TABLE lcs DROP COLUMN sim_no"); }
// Only one LCS per site
$conn->query("ALTER TABLE lcs ADD UNIQUE KEY unique_lcs_per_site (site_id)");

// Load site for header
$ss = $conn->prepare("SELECT id, site_name, address FROM sites WHERE id = ?");
$ss->bind_param('i', $site_id);
$ss->execute();
$site = $ss->get_result()->fetch_assoc();
if (!$site) { die('Site not found.'); }

// Prevent adding if already exists
$chk = $conn->prepare("SELECT id FROM lcs WHERE site_id = ? LIMIT 1");
$chk->bind_param('i', $site_id);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();
if ($existing) {
    header('Location: view_site.php?site_id='.(int)$site_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lcs_name = trim($_POST['lcs_name'] ?? '');
    $lcs_incharge = trim($_POST['lcs_incharge'] ?? '');
    $lcs_address = trim($_POST['lcs_address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $installation_date = trim($_POST['installation_date'] ?? '');
    $created_by = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'system');

    if ($lcs_name === '') { $error = 'LCS Name is required'; }

    if (!isset($error)) {
        $sql = "INSERT INTO lcs (site_id, lcs_name, tw_address, incharge_name, latitude, longitude, installation_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) { die('DB error: '.$conn->error); }
        $stmt->bind_param('isssddss', $site_id, $lcs_name, $lcs_address, $lcs_incharge, $latitude, $longitude, $installation_date, $created_by);
        if ($stmt->execute()) {
            $lcs_id = $stmt->insert_id;
            header('Location: view_site.php?site_id='.(int)$site_id);
            exit();
        } else {
            $error = 'Error adding LCS: '.$conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add LCS - <?php echo htmlspecialchars($site['site_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
 <?php include('header.php'); ?>
<div class="container">
    <div class="card">
        <div class="progress-steps">
            <div class="step completed">Site Details</div>
            <div class="step completed">Add Tubewells</div>
            <div class="step active">Add LCS</div>
        </div>
        <div style="text-align:center; margin-bottom:1.5rem;">
            <h2 style="color:#2d3748;">Add LCS</h2>
            <p style="color:#718096;">Site: <strong><?php echo htmlspecialchars($site['site_name']); ?></strong></p>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">🧰 LCS Name *</label>
                    <input type="text" name="lcs_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">👤 LCS Incharge</label>
                    <input type="text" name="lcs_incharge" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">📍 LCS Address</label>
                <textarea name="lcs_address" class="form-control"><?php echo htmlspecialchars($site['address']); ?></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">🌐 Latitude</label>
                    <input type="text" name="latitude" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">🌐 Longitude</label>
                    <input type="text" name="longitude" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">📅 Installation Date</label>
                    <input type="date" name="installation_date" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">👤 Created By</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars(isset($_SESSION['full_name']) && $_SESSION['full_name']!=='' ? $_SESSION['full_name'] : (isset($_SESSION['username'])?$_SESSION['username']:'system')); ?>" readonly>
            </div>
            <div class="btn-group" style="justify-content:center; margin-top:1.5rem;">
                <a href="view_site.php?site_id=<?php echo (int)$site_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create LCS</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
