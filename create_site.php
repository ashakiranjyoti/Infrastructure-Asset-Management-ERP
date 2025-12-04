<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $site_name = trim($_POST['site_name']);
    $address = trim($_POST['address']);
    $division_name = trim($_POST['division_name']);
    $contractor_name = trim($_POST['contractor_name']);
    $site_incharge = trim($_POST['site_incharge']);
    $contact = trim($_POST['contact']);
    $number_of_tubewell = $_POST['number_of_tubewell'];
    $created_by = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'system');
    $lcs_available = isset($_POST['lcs_available']) ? (int)$_POST['lcs_available'] : 0;
    
    // Uniqueness check for site_name (case-insensitive)
    if ($dup = $conn->prepare("SELECT id FROM sites WHERE LOWER(site_name) = LOWER(?) LIMIT 1")) {
        $dup->bind_param('s', $site_name);
        $dup->execute();
        $dup_res = $dup->get_result();
        if ($dup_res && $dup_res->num_rows > 0) {
            $error = 'A site with the name "' . htmlspecialchars($site_name) . '" already exists. Please choose a different name.';
        }
        $dup->close();
    }

    if (!isset($error)) {
        // Check if sites.lcs_available column exists
        $col_check_sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites' AND COLUMN_NAME = 'lcs_available'";
        $col_exists = $conn->query($col_check_sql);
        $has_lcs_col = $col_exists && $col_exists->fetch_row();

        if ($has_lcs_col) {
            $sql = "INSERT INTO sites (site_name, address, division_name, contractor_name, site_incharge, contact, number_of_tubewell, lcs_available, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        } else {
            $sql = "INSERT INTO sites (site_name, address, division_name, contractor_name, site_incharge, contact, number_of_tubewell, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        }

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Database error: " . $conn->error);
        }

        if ($has_lcs_col) {
            $stmt->bind_param("ssssssiis", $site_name, $address, $division_name, $contractor_name, $site_incharge, $contact, $number_of_tubewell, $lcs_available, $created_by);
        } else {
            $stmt->bind_param("ssssssis", $site_name, $address, $division_name, $contractor_name, $site_incharge, $contact, $number_of_tubewell, $created_by);
        }

        if ($stmt->execute()) {
            $site_id = $stmt->insert_id;
            // Redirect to add tubewell page with site_id and count
            header("Location: add_tubewell.php?site_id=" . $site_id . "&count=" . $number_of_tubewell);
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Site</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
     <?php include('header.php'); ?>

    <div class="container">
        <div class="card">
            <div class="progress-steps">
                <div class="step active">Site Details</div>
                <div class="step">Add Tubewells</div>
                <div class="step">Configure Parameters</div>
            </div>

            <h2 style="text-align: center; margin-bottom: 2rem; color: #2d3748;">Create New Site</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="site-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="form-group">
                        <label class="form-label">🏢 Site Name *</label>
                        <input type="text" name="site_name" class="form-control" placeholder="Enter site name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">🏗️ Contractor Name</label>
                        <input type="text" name="contractor_name" class="form-control" placeholder="Enter contractor name">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">📍 Site Address *</label>
                    <textarea name="address" class="form-control" placeholder="Enter complete address" required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
                    <div class="form-group">
                        <label class="form-label">🏛️ Division Name</label>
                        <input type="text" name="division_name" class="form-control" placeholder="Enter division name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">👤 Site Incharge</label>
                        <input type="text" name="site_incharge" class="form-control" placeholder="Enter incharge name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">📞 Contact Number</label>
                        <input type="text" name="contact" class="form-control" placeholder="Enter contact number">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">👤 Created By</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(isset($_SESSION['full_name']) && $_SESSION['full_name']!=='' ? $_SESSION['full_name'] : (isset($_SESSION['username'])?$_SESSION['username']:'system')); ?>" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">💧 Number of Tubewells *</label>
                    <input type="number" name="number_of_tubewell" class="form-control" placeholder="Enter number of tubewells" min="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">📦 LCS Availability</label>
                    <select name="lcs_available" class="form-control">
                        <option value="0">Not Available</option>
                        <option value="1">Available</option>
                    </select>
                </div>

                <div class="btn-group" style="justify-content: center; margin-top: 2rem;">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Site & Continue</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>