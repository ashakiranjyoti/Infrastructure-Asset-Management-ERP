<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

if (!isset($_GET['lcs_id'])) { die('Invalid request. lcs_id is required.'); }
$lcs_id = (int)$_GET['lcs_id'];

// Load LCS with site info
$stmt = $conn->prepare("SELECT l.*, s.site_name, s.id AS site_id, s.address AS site_address FROM lcs l JOIN sites s ON s.id = l.site_id WHERE l.id = ?");
if (!$stmt) { die('Database error: ' . $conn->error); }
$stmt->bind_param('i', $lcs_id);
$stmt->execute();
$lcs = $stmt->get_result()->fetch_assoc();
if (!$lcs) { die('LCS not found.'); }
$site_id = (int)$lcs['site_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lcs_name = trim($_POST['lcs_name'] ?? '');
    $lcs_incharge = trim($_POST['lcs_incharge'] ?? '');
    $lcs_address = trim($_POST['lcs_address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $installation_date = trim($_POST['installation_date'] ?? '');

    if ($lcs_name === '') { $error = 'LCS Name is required'; }

    if (!isset($error)) {
        $sql = "UPDATE lcs SET lcs_name = ?, tw_address = ?, incharge_name = ?, latitude = ?, longitude = ?, installation_date = ? WHERE id = ?";
        $up = $conn->prepare($sql);
        if (!$up) { die('DB error: ' . $conn->error); }
        $up->bind_param('sssddsi', $lcs_name, $lcs_address, $lcs_incharge, $latitude, $longitude, $installation_date, $lcs_id);
        if ($up->execute()) {
            header('Location: view_site.php?site_id='.(int)$site_id);
            exit();
        } else {
            $error = 'Error updating LCS: '.$conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit LCS - <?php echo htmlspecialchars($lcs['lcs_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
 <?php include('header.php'); ?>
<div class="container">
    <div class="card">
        <div class="progress-steps">
            <div class="step completed">Site Details</div>
            <div class="step completed">Tubewells</div>
            <div class="step active">Edit LCS</div>
        </div>
        <div style="text-align:center; margin-bottom:1.5rem;">
            <h2 style="color:#2d3748;">Edit LCS</h2>
            <p style="color:#718096;">Site: <strong><?php echo htmlspecialchars($lcs['site_name']); ?></strong></p>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">🧰 LCS Name *</label>
                    <input type="text" name="lcs_name" class="form-control" required value="<?php echo htmlspecialchars($lcs['lcs_name']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">👤 LCS Incharge</label>
                    <input type="text" name="lcs_incharge" class="form-control" value="<?php echo htmlspecialchars($lcs['incharge_name']); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">📍 LCS Address</label>
                <textarea name="lcs_address" class="form-control"><?php echo htmlspecialchars($lcs['tw_address']); ?></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">🌐 Latitude</label>
                    <input type="text" name="latitude" class="form-control" value="<?php echo htmlspecialchars($lcs['latitude']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">🌐 Longitude</label>
                    <input type="text" name="longitude" class="form-control" value="<?php echo htmlspecialchars($lcs['longitude']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">📅 Installation Date</label>
                    <input type="date" name="installation_date" class="form-control" value="<?php echo htmlspecialchars($lcs['installation_date']); ?>">
                </div>
            </div>
            <div class="btn-group" style="justify-content:center; margin-top:1.5rem;">
                <a href="view_site.php?site_id=<?php echo (int)$site_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update LCS</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
