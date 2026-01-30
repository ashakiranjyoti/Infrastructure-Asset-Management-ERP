<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Check if tubewell_id parameter is set
if (!isset($_GET['tubewell_id'])) {
    die("Invalid request. Tubewell ID parameter is required.");
}

$tubewell_id = (int)$_GET['tubewell_id'];

// Ensure media_uploads table exists and is date-aware
$conn->query("CREATE TABLE IF NOT EXISTS media_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tubewell_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    uploaded_by VARCHAR(100) NULL,
    uploaded_at DATETIME NOT NULL,
    status_date DATE NOT NULL,
    INDEX idx_tubewell_item_date (tubewell_id, item_name, status_date, uploaded_at)
)");

// Ensure media_change_log table exists for auditing media actions
$conn->query("CREATE TABLE IF NOT EXISTS media_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tubewell_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    media_id INT NULL,
    action ENUM('uploaded','deleted') NOT NULL,
    file_path VARCHAR(500) NULL,
    file_type ENUM('image','video') NULL,
    status_date DATE NULL,
    actor VARCHAR(100) NULL,
    action_at DATETIME NOT NULL,
    INDEX idx_media_change (tubewell_id, item_name, status_date, action_at)
)");

// Backward compatibility: add status_date column and index if missing, and backfill from uploaded_at
$colCheck = $conn->query("SHOW COLUMNS FROM media_uploads LIKE 'status_date'");
if ($colCheck && $colCheck->num_rows === 0) {
    // Add as NULLable first to allow backfill
    $conn->query("ALTER TABLE media_uploads ADD COLUMN status_date DATE NULL");
    // Backfill existing rows with DATE(uploaded_at)
    $conn->query("UPDATE media_uploads SET status_date = DATE(uploaded_at) WHERE status_date IS NULL");
    // Make NOT NULL after backfill
    $conn->query("ALTER TABLE media_uploads MODIFY COLUMN status_date DATE NOT NULL");
}
// Ensure composite index exists for efficient date-wise queries
$idxCheck = $conn->query("SHOW INDEX FROM media_uploads WHERE Key_name = 'idx_tubewell_item_date'");
if (!$idxCheck || $idxCheck->num_rows === 0) {
    $conn->query("CREATE INDEX idx_tubewell_item_date ON media_uploads (tubewell_id, item_name, status_date, uploaded_at)");
}

// status_locks table assumed to exist
// tubewell_master_notes table assumed to exist
// master notes migration assumed complete

// Ensure tubewell_master_media table exists (date-wise media for master note)
$conn->query("CREATE TABLE IF NOT EXISTS tubewell_master_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tubewell_id INT NOT NULL,
    status_date DATE NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image','video') NOT NULL,
    uploaded_by VARCHAR(100) NULL,
    uploaded_at DATETIME NOT NULL,
    INDEX idx_tmm (tubewell_id, status_date, uploaded_at)
)");

// Load tubewell + site info
$tubewell_sql = "SELECT tw.*, s.site_name, s.id as site_id FROM tubewells tw JOIN sites s ON tw.site_id = s.id WHERE tw.id = ?";
$stmt = $conn->prepare($tubewell_sql);
$stmt->bind_param('i', $tubewell_id);
$stmt->execute();
$tubewell_info = $stmt->get_result()->fetch_assoc();
if (!$tubewell_info) { die('Tubewell not found with ID: ' . $tubewell_id); }

// Selected date (locked to today)
$today = date('Y-m-d');
$selected_date = $today;

// Handle finalize action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_day'])) {
    $locker = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'web');
    $ls = $conn->prepare("INSERT INTO status_locks (tubewell_id, status_date, locked_by, locked_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE locked_by = VALUES(locked_by), locked_at = VALUES(locked_at)");
    $ls->bind_param('iss', $tubewell_id, $selected_date, $locker);
    $ls->execute();
    header('Location: add_parameters.php?tubewell_id=' . $tubewell_id . '&date=' . urlencode($selected_date));
    exit();
}

// Handle media deletion (enforce same selected date)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media'])) {
    $media_id = (int)$_POST['media_id'];
    $item_name = $_POST['item_name'] ?? '';
    
    // Get file details before deletion
    $stmt = $conn->prepare("SELECT file_path, status_date, file_type FROM media_uploads WHERE id = ? AND tubewell_id = ?");
    $stmt->bind_param('ii', $media_id, $tubewell_id);
    $stmt->execute();
    $media = $stmt->get_result()->fetch_assoc();
    
    if ($media) {
        // Allow delete only for the currently selected date's media
        if (isset($media['status_date']) && $media['status_date'] === $selected_date) {
            // Determine actor name from session
            $actor = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'web');
            // Insert audit log before deletion
            if ($log = $conn->prepare("INSERT INTO media_change_log (tubewell_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, ?, 'deleted', ?, ?, ?, ?, NOW())")) {
                $ftype = isset($media['file_type']) ? $media['file_type'] : null;
                $log->bind_param('isissss', $tubewell_id, $item_name, $media_id, $media['file_path'], $ftype, $selected_date, $actor);
                $log->execute();
            }
            // Delete from database
            $del_stmt = $conn->prepare("DELETE FROM media_uploads WHERE id = ? AND tubewell_id = ?");
            $del_stmt->bind_param('ii', $media_id, $tubewell_id);
            if ($del_stmt->execute()) {
                // Delete physical file
                if (!empty($media['file_path']) && file_exists($media['file_path'])) {
                    @unlink($media['file_path']);
                }
                $_SESSION['success'] = "Media deleted successfully by $actor at " . date('d M Y H:i');
            }
        } else {
            $_SESSION['success'] = "Cannot delete media of a different date from this screen.";
        }
    }
    
    header('Location: add_parameters.php?tubewell_id=' . $tubewell_id . '&date=' . urlencode($selected_date));
    exit();
}

// Handle save master note (date-wise, saves for selected date)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_master_note'])) {
    $note_text = isset($_POST['master_note']) ? trim($_POST['master_note']) : '';
    $editor = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'web');

    // Apply deletions for master note media (only for selected date)
    if (!empty($_POST['delete_master_media_ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_POST['delete_master_media_ids'])));
        if (!empty($ids)) {
            // Fetch matching media to validate date and get file info
            $in = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $sql = "SELECT id, file_path, file_type, status_date FROM tubewell_master_media WHERE tubewell_id = ? AND id IN ($in)";
            if ($st = $conn->prepare($sql)) {
                $bindParams = array_merge([$types ? 'i'.$types : 'i'], [$tubewell_id], $ids);
                // bind dynamically
                $refs = [];
                foreach ($bindParams as $k => $v) { $refs[$k] = &$bindParams[$k]; }
                call_user_func_array([$st, 'bind_param'], $refs);
                $st->execute();
                $res = $st->get_result();
                while ($m = $res->fetch_assoc()) {
                    if ($m['status_date'] === $selected_date) {
                        // log deletion
                        if ($log = $conn->prepare("INSERT INTO media_change_log (tubewell_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, '__MASTER_NOTE__', ?, 'deleted', ?, ?, ?, ?, NOW())")) {
                            $ft = isset($m['file_type']) ? $m['file_type'] : null;
                            $log->bind_param('iissss', $tubewell_id, $m['id'], $m['file_path'], $ft, $selected_date, $editor);
                            $log->execute();
                        }
                        // delete file
                        if (!empty($m['file_path']) && file_exists($m['file_path'])) { @unlink($m['file_path']); }
                        // delete db row
                        if ($dd = $conn->prepare('DELETE FROM tubewell_master_media WHERE id = ? AND tubewell_id = ?')) {
                            $dd->bind_param('ii', $m['id'], $tubewell_id);
                            $dd->execute();
                        }
                    }
                }
            }
        }
    }

    if ($stmt = $conn->prepare("INSERT INTO tubewell_master_notes (tubewell_id, status_date, note, updated_by, updated_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status_date = VALUES(status_date), note = VALUES(note), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)")) {
        $stmt->bind_param('isss', $tubewell_id, $selected_date, $note_text, $editor);
        $stmt->execute();
    }
    // Save master note contributors with full replace (allow removals)
    $conn->query("CREATE TABLE IF NOT EXISTS tubewell_master_note_contributors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tubewell_id INT NOT NULL,
        status_date DATE NOT NULL,
        contributor_name VARCHAR(100) NOT NULL,
        added_at DATETIME NOT NULL,
        UNIQUE KEY uniq_tw_mn (tubewell_id, status_date, contributor_name),
        INDEX idx_tw_mn_contrib (contributor_name)
    )");
    $selected_names = [];
    if (isset($_POST['master_contributors'])) {
        $raw = trim((string)$_POST['master_contributors']);
        if ($raw !== '') {
            $selected_names = array_values(array_filter(array_map(function($v){ return trim($v); }, explode(',', $raw)), function($v){ return $v !== ''; }));
        }
    }
    // Delete any contributors not in current selection
    if (empty($selected_names)) {
        if ($del = $conn->prepare('DELETE FROM tubewell_master_note_contributors WHERE tubewell_id = ? AND status_date = ?')) {
            $del->bind_param('is', $tubewell_id, $selected_date);
            $del->execute();
        }
    } else {
        // Build NOT IN list safely
        $place = implode(',', array_fill(0, count($selected_names), '?'));
        $types = str_repeat('s', count($selected_names));
        $sqlDel = "DELETE FROM tubewell_master_note_contributors WHERE tubewell_id = ? AND status_date = ? AND contributor_name NOT IN ($place)";
        if ($del = $conn->prepare($sqlDel)) {
            $params = array_merge([$tubewell_id, $selected_date], $selected_names);
            $bindTypes = 'is' . $types;
            $refs = [];
            $refs[] = & $bindTypes;
            foreach ($params as $k => $v) { $refs[] = & $params[$k]; }
            call_user_func_array([$del, 'bind_param'], $refs);
            $del->execute();
        }
    }
    // Insert current selection
    $seen = [];
    foreach ($selected_names as $nm) {
        if (strcasecmp($nm, $editor) === 0) { continue; }
        $key = mb_strtolower($nm);
        if (isset($seen[$key])) { continue; }
        $seen[$key] = true;
        if ($ins = $conn->prepare("INSERT IGNORE INTO tubewell_master_note_contributors (tubewell_id, status_date, contributor_name, added_at) VALUES (?, ?, ?, NOW())")) {
            $ins->bind_param('iss', $tubewell_id, $selected_date, $nm);
            $ins->execute();
        }
    }
    // Handle new master note media uploads in the same save
    if (!empty($_FILES['master_media_files']) && is_array($_FILES['master_media_files']['name'])) {
        // Ensure table exists (parity with upload_master_media.php)
        $conn->query("CREATE TABLE IF NOT EXISTS tubewell_master_media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tubewell_id INT NOT NULL,
            status_date DATE NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type ENUM('image','video') NOT NULL,
            uploaded_by VARCHAR(100) NULL,
            uploaded_at DATETIME NOT NULL,
            INDEX idx_tmm (tubewell_id, status_date, uploaded_at)
        )");
        // Ensure change log exists
        $conn->query("CREATE TABLE IF NOT EXISTS media_change_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tubewell_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            media_id INT NULL,
            action ENUM('uploaded','deleted') NOT NULL,
            file_path VARCHAR(500) NULL,
            file_type ENUM('image','video') NULL,
            status_date DATE NULL,
            actor VARCHAR(100) NULL,
            action_at DATETIME NOT NULL,
            INDEX idx_media_change (tubewell_id, item_name, status_date, action_at)
        )");
        $baseDir = 'uploads/master_note/';
        if (!file_exists($baseDir)) { @mkdir($baseDir, 0777, true); }
        $count = count($_FILES['master_media_files']['name']);
        for ($i = 0; $i < $count; $i++) {
            $name = $_FILES['master_media_files']['name'][$i] ?? '';
            $tmp = $_FILES['master_media_files']['tmp_name'][$i] ?? '';
            $type = $_FILES['master_media_files']['type'][$i] ?? '';
            if (!$name || !$tmp) { continue; }
            $isImage = $type && strpos($type, 'image') !== false;
            $isVideo = $type && strpos($type, 'video') !== false;
            if (!$isImage && !$isVideo) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $imgExt = ['jpg','jpeg','png','gif','webp','bmp'];
                $vidExt = ['mp4','mov','avi','mkv','webm'];
                if (in_array($ext, $imgExt)) $isImage = true; else if (in_array($ext, $vidExt)) $isVideo = true;
            }
            if (!$isImage && !$isVideo) { continue; }
            $subDir = $isImage ? 'images/' : 'videos/';
            if (!file_exists($baseDir . $subDir)) { @mkdir($baseDir . $subDir, 0777, true); }
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name));
            $unique = uniqid() . '-' . $safeName;
            $dest = $baseDir . $subDir . $unique;
            if (@move_uploaded_file($tmp, $dest)) {
                $ftype = $isImage ? 'image' : 'video';
                if ($stm = $conn->prepare("INSERT INTO tubewell_master_media (tubewell_id, status_date, file_path, file_type, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())")) {
                    $stm->bind_param('issss', $tubewell_id, $selected_date, $dest, $ftype, $editor);
                    if ($stm->execute()) {
                        $mid = $conn->insert_id;
                        if ($log = $conn->prepare("INSERT INTO media_change_log (tubewell_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, '__MASTER_NOTE__', ?, 'uploaded', ?, ?, ?, ?, NOW())")) {
                            $log->bind_param('iissss', $tubewell_id, $mid, $dest, $ftype, $selected_date, $editor);
                            $log->execute();
                        }
                    }
                }
            }
        }
    }
    header('Location: add_parameters.php?tubewell_id=' . $tubewell_id . '&date=' . urlencode($selected_date));
    exit();
}

// Check lock
$is_locked = false;
$lk = $conn->prepare('SELECT locked_by, locked_at FROM status_locks WHERE tubewell_id = ? AND status_date = ?');
$lk->bind_param('is', $tubewell_id, $selected_date);
$lk->execute();
$lock_row = $lk->get_result()->fetch_assoc();
$is_locked = (bool)$lock_row;

// Load existing master note for selected date, or fallback to previous date's note (like parameters)
$master_note_row = null;
// First try to get note for selected date
if ($mn = $conn->prepare('SELECT note, updated_by, updated_at, status_date FROM tubewell_master_notes WHERE tubewell_id = ? AND status_date = ?')) {
    $mn->bind_param('is', $tubewell_id, $selected_date);
    $mn->execute();
    $master_note_row = $mn->get_result()->fetch_assoc();
}
// If not found for selected date, get the most recent previous date's note
if (!$master_note_row) {
    if ($mn_prev = $conn->prepare('SELECT note, updated_by, updated_at, status_date FROM tubewell_master_notes WHERE tubewell_id = ? AND status_date < ? ORDER BY status_date DESC LIMIT 1')) {
        $mn_prev->bind_param('is', $tubewell_id, $selected_date);
        $mn_prev->execute();
        $master_note_row = $mn_prev->get_result()->fetch_assoc();
    }
}

// Load master note media for the selected date
$master_media = [];
if ($mm = $conn->prepare('SELECT id, file_path, file_type, uploaded_by, uploaded_at FROM tubewell_master_media WHERE tubewell_id = ? AND status_date = ? ORDER BY uploaded_at DESC')) {
    $mm->bind_param('is', $tubewell_id, $selected_date);
    $mm->execute();
    $mres = $mm->get_result();
    while ($row = $mres->fetch_assoc()) { $master_media[] = $row; }
}

// Load saved master note contributors for selected date
$master_contribs = [];
if ($mc = $conn->prepare('SELECT contributor_name FROM tubewell_master_note_contributors WHERE tubewell_id = ? AND status_date = ? ORDER BY contributor_name')) {
    $mc->bind_param('is', $tubewell_id, $selected_date);
    $mc->execute();
    $cres = $mc->get_result();
    while ($cr = $cres->fetch_assoc()) { if (!empty($cr['contributor_name'])) { $master_contribs[] = $cr['contributor_name']; } }
}

// Fetch last update info for this tubewell
$lu = $conn->prepare("SELECT created_by, updated_at FROM status_history WHERE tubewell_id = ? AND updated_at IS NOT NULL ORDER BY updated_at DESC LIMIT 1");
$lu->bind_param('i', $tubewell_id);
$lu->execute();
$last_update_row = $lu->get_result()->fetch_assoc();

// Load active items
$active_items = [];
$items_res = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1");
if ($items_res) { while ($ir = $items_res->fetch_assoc()) { $active_items[] = $ir['item_name']; } }

// For each item, pick today's row if exists; else last known previous as default display
$rows_by_item = [];
foreach ($active_items as $iname) {
    // Try today's
    $stmt = $conn->prepare("SELECT * FROM status_history WHERE tubewell_id = ? AND status_date = ? AND item_name = ? LIMIT 1");
    $stmt->bind_param('iss', $tubewell_id, $selected_date, $iname);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        // Fallback to last known previous
        $stmt2 = $conn->prepare("SELECT * FROM status_history WHERE tubewell_id = ? AND item_name = ? ORDER BY status_date DESC LIMIT 1");
        $stmt2->bind_param('is', $tubewell_id, $iname);
        $stmt2->execute();
        $row = $stmt2->get_result()->fetch_assoc();
    }
    $rows_by_item[$iname] = $row ?: [];
}

// Load additional custom items (spares) not in items_master
// Show the latest row per spare item_name up to selected_date (today's if exists, else previous)
$extra_rows = [];
if ($ex = $conn->prepare(
    "SELECT t.* FROM status_history t
      JOIN (
        SELECT item_name, MAX(status_date) AS sd
        FROM status_history
        WHERE tubewell_id = ? AND status_date <= ? AND item_name NOT IN (SELECT item_name FROM items_master WHERE is_active = 1)
        GROUP BY item_name
      ) x ON t.tubewell_id = ? AND t.item_name = x.item_name AND t.status_date = x.sd
     ORDER BY t.item_name"
)) {
    $ex->bind_param('isi', $tubewell_id, $selected_date, $tubewell_id);
    $ex->execute();
    $er = $ex->get_result();
    while ($r = $er->fetch_assoc()) { $extra_rows[] = $r; }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Daily Status - <?php echo htmlspecialchars($tubewell_info['tubewell_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .table-container { overflow-x: auto; }
        table.data-table { width: 100%; table-layout: fixed; border-collapse: collapse; border: 1px solid #d1d5db; }
        table.data-table th, table.data-table td { white-space: normal; padding: 6px 8px; font-size: 13px; border-left: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; overflow-wrap: anywhere; word-break: break-word; }
        .col-remark { word-break: break-word; }
        table.data-table input[type="text"], table.data-table select { width: 100%; box-sizing: border-box; }
        .col-by, .col-at { white-space: wrap; }
        .checkbox-cell { text-align: center; width: 50px; }
        .col-actions { width: 110px; }
        .col-media { width: 120px; }
        
        .media-preview {
            max-width: 50px;
            max-height: 50px;
            border-radius: 4px;
            cursor: pointer;
            object-fit: cover;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .media-container {
            position: relative;
            display: inline-block;
        }
        
        .delete-media {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            cursor: pointer;
            font-size: 12px;
            line-height: 1;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            max-width: 70%;
            max-height: 70%;
            margin-top: 10%;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .media-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
    </style>
    <?php
    $all_user_names = [];
    $has_active_col = $conn->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($has_active_col && $has_active_col->num_rows > 0) {
        $uq = "SELECT COALESCE(NULLIF(full_name,''), username) AS name FROM users WHERE is_active = 1";
    } else {
        $uq = "SELECT COALESCE(NULLIF(full_name,''), username) AS name FROM users";
    }
    if ($ur = $conn->query($uq)) {
        while ($u = $ur->fetch_assoc()) { if ($u['name'] !== null && $u['name'] !== '') { $all_user_names[] = $u['name']; } }
    }
    sort($all_user_names, SORT_NATURAL | SORT_FLAG_CASE);
    ?>
    <script>
        window.ALL_USERS = <?php echo json_encode($all_user_names); ?>;
    </script>
</head>
<body>
     <?php include('header.php'); ?>

    <div class="container">
        <div style="text-align:right; margin-bottom: 1rem;">
            <a href="view_tubewell.php?tubewell_id=<?php echo (int)$tubewell_id; ?>" class="btn btn-secondary">← Back</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #2d3748;">📊 Daily Status Editor</h2>
                <p style="color: #718096;">
                    Site: <strong><?php echo htmlspecialchars($tubewell_info['site_name']); ?></strong> |
                    Zone: <strong><?php echo htmlspecialchars($tubewell_info['zone_name']); ?></strong> |
                    Tubewell: <strong><?php echo htmlspecialchars($tubewell_info['tubewell_name']); ?></strong>
                </p>
                <?php if (!empty($last_update_row) && !empty($last_update_row['updated_at'])): ?>
                    <div class="alert alert-success" style="display:inline-block; margin-top: .25rem;">
                        Last updated by <strong><?php echo htmlspecialchars($last_update_row['created_by'] ?? '—'); ?></strong>
                        on <?php echo date('d M Y H:i', strtotime($last_update_row['updated_at'])); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-bottom:1rem; border:1px solid #e5e7eb;">
                <h3 style="margin:0 0 .5rem 0; color:#2d3748;">📝 Master Note (Date-wise)</h3>
                <form method="POST" enctype="multipart/form-data" id="masterNoteForm" style="display:flex; flex-direction:column; gap:.5rem;">
                    <input type="hidden" name="save_master_note" value="1">
                    <input type="hidden" name="delete_master_media_ids" id="deleteMasterMediaIds" value="">
                    <input type="hidden" name="master_contributors" id="masterContribs" value="">
                    <textarea name="master_note" rows="3" class="form-control" placeholder="Note down any important issue or matter related to the tubewell here..." style="resize:vertical;" readonly><?php echo htmlspecialchars($master_note_row['note'] ?? ''); ?></textarea>
                    <div>
                        <div style="font-weight:600; margin-bottom:.25rem;">Contributors</div>
                        <div id="masterContribChecks" style="display:flex; flex-wrap:wrap; gap:8px;">
                            <?php foreach ($all_user_names as $nm): ?>
                                <label style="display:inline-flex; align-items:center; gap:4px; font-weight:normal;">
                                    <input type="checkbox" class="mn-contrib" value="<?php echo htmlspecialchars($nm); ?>" <?php echo in_array($nm, $master_contribs, true) ? 'checked' : ''; ?> disabled> <?php echo htmlspecialchars($nm); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div id="masterContribDisplay" style="margin-top:.25rem; color:#4a5568; font-size:.9rem;"></div>
                    </div>
                    <?php if (!$is_locked): ?>
                    <div>
                        <div style="font-weight:600; margin-bottom:.25rem;">Add Photo / Video</div>
                        <input type="file" id="masterMediaFiles" name="master_media_files[]" class="form-control-sm" accept="image/*,video/*" multiple disabled>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="font-size:.85rem; color:#4a5568;">
                            <?php if (!empty($master_note_row)): ?>
                            <?php 
                                $note_date = isset($master_note_row['status_date']) ? $master_note_row['status_date'] : '';
                                $is_previous_date = $note_date && $note_date !== $selected_date;
                            ?>
                            <?php if ($is_previous_date): ?>
                                <span style="color:#e53e3e;">⚠️ Showing previous date's note:</span><br>
                                Note by <strong><?php echo htmlspecialchars($master_note_row['updated_by'] ?? '—'); ?></strong>
                                on <?php echo date('d M Y H:i', strtotime($master_note_row['updated_at'])); ?>
                                (Date: <?php echo date('d M Y', strtotime($note_date)); ?>)
                            <?php else: ?>
                                Note by <strong><?php echo htmlspecialchars($master_note_row['updated_by'] ?? '—'); ?></strong>
                                on <?php echo date('d M Y H:i', strtotime($master_note_row['updated_at'])); ?>
                            <?php endif; ?>
                            <?php else: ?>
                                No master note added yet for this date.
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if (!$is_locked): ?>
                                <div style="display:flex; gap:.5rem; align-items:center;">
                                    <button type="button" id="mnEditBtn" class="btn btn-warning btn-sm" title="Edit">✏️ Edit</button>
                                    <button type="submit" id="mnSaveBtn" class="btn btn-success btn-sm" title="Save" disabled>✅ Save</button>
                                    <button type="button" id="mnCancelBtn" class="btn btn-secondary btn-sm" title="Cancel" style="display:none;">✖️ Cancel</button>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-warning">Locked - Cannot edit</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <script>
                    (function(){
                        var form = document.getElementById('masterNoteForm');
                        var checksWrap = document.getElementById('masterContribChecks');
                        var display = document.getElementById('masterContribDisplay');
                        var hidden = document.getElementById('masterContribs');
                        var ta = form ? form.querySelector('textarea[name="master_note"]') : null;
                        var editBtn = document.getElementById('mnEditBtn');
                        var saveBtn = document.getElementById('mnSaveBtn');
                        var cancelBtn = document.getElementById('mnCancelBtn');
                        var fileInput = document.getElementById('masterMediaFiles');
                        var delBtns = Array.prototype.slice.call(document.querySelectorAll('.media-container .master-del-btn'));
                        if (!form || !checksWrap || !hidden) return;
                        function refreshDisplay(){
                            var sel = Array.prototype.slice.call(checksWrap.querySelectorAll('input.mn-contrib:checked')).map(function(x){return x.value;});
                            hidden.value = sel.join(',');
                            display.textContent = sel.length ? ('Contributors: ' + sel.join(', ')) : '';
                        }
                        checksWrap.addEventListener('change', refreshDisplay);
                        form.addEventListener('submit', function(){ refreshDisplay(); });
                        // init
                        refreshDisplay();

                        // Manage edit mode like item-wise: Edit, Save, Cancel
                        var initial = {
                            text: ta ? ta.value : '',
                            selected: Array.prototype.slice.call(checksWrap.querySelectorAll('input.mn-contrib:checked')).map(function(x){return x.value;})
                        };
                        function setDisabledState(disabled){
                            if (ta) ta.readOnly = disabled ? true : false;
                            Array.prototype.slice.call(checksWrap.querySelectorAll('input.mn-contrib')).forEach(function(cb){ cb.disabled = disabled; });
                            if (saveBtn) saveBtn.disabled = disabled;
                            if (cancelBtn) cancelBtn.style.display = disabled ? 'none' : 'inline-block';
                            if (fileInput) fileInput.disabled = disabled;
                            // toggle delete buttons visibility
                            delBtns.forEach(function(b){ b.style.display = disabled ? 'none' : 'inline-block'; });
                            if (editBtn) editBtn.style.display = disabled ? 'inline-block' : 'none';
                        }
                        // start disabled (view mode)
                        setDisabledState(true);
                        if (editBtn) editBtn.addEventListener('click', function(){ setDisabledState(false); if (ta){ ta.focus(); ta.scrollIntoView({behavior:'smooth', block:'center'});} });
                        if (cancelBtn) cancelBtn.addEventListener('click', function(){
                            // simpler reset to ensure media delete marks & files are cleared
                            window.location.reload();
                        });
                    })();
                </script>

                <!-- Master Note Media (date-wise) -->
                <div style="margin-top:.5rem;">
                    <div style="font-weight:600; margin-bottom:.25rem;">Photo / Video for this note</div>
                    <?php if (!empty($master_media)): ?>
                        <div class="media-grid">
                            <?php foreach ($master_media as $m): ?>
                                <div class="media-container" data-media-id="<?php echo (int)$m['id']; ?>">
                                    <?php if ($m['file_type'] === 'image'): ?>
                                        <img src="<?php echo htmlspecialchars($m['file_path']); ?>" class="media-preview" onclick="openModal('<?php echo htmlspecialchars($m['file_path']); ?>','image')" alt="Master media">
                                    <?php else: ?>
                                        <video class="media-preview" onclick="openModal('<?php echo htmlspecialchars($m['file_path']); ?>','video')">
                                            <source src="<?php echo htmlspecialchars($m['file_path']); ?>" type="video/mp4">
                                        </video>
                                    <?php endif; ?>
                                    <?php if (!$is_locked): ?>
                                        <button type="button" class="delete-media master-del-btn" title="Mark for delete" style="display:none;">×</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span style="color:#999;">No media</span>
                    <?php endif; ?>

                    <?php if (!$is_locked): ?>
                    <?php endif; ?>
                </div>
            </div>

            <form method="GET" style="display:flex; gap:1rem; align-items:end; margin-bottom:1rem;">
                <input type="hidden" name="tubewell_id" value="<?php echo (int)$tubewell_id; ?>">
                <div class="form-group" style="min-width: 220px;">
                    <label class="form-label">📅 Status Date *</label>
                    <input type="date" name="date" class="form-control" value="<?php echo $selected_date; ?>" min="<?php echo $today; ?>" max="<?php echo $today; ?>" onkeydown="return false" readonly required>
                </div>
                <?php if ($is_locked): ?>
                    <div class="alert alert-info" style="margin:0;">
                        Locked by <strong><?php echo htmlspecialchars($lock_row['locked_by']); ?></strong> at <?php echo date('d M Y H:i', strtotime($lock_row['locked_at'])); ?>
                    </div>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="7%">Item</th>
                            <th width="7%">Make/Model</th>
                            <th width="5%">Size/Cap.</th>
                            <th width="7%">Status</th>
                            <th width="8%">HMI/Loc.</th>
                            <th width="8%">Web</th>
                            <th width="10%">Remark</th>
                            <th width="10%">Photo / Video</th>
                            <th width="5%">Updated By</th>
                            <th width="5%">Updated At</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_items as $iname): 
                            $r = $rows_by_item[$iname] ?? []; 
                            // Get media for this item for selected date only
                            $media_stmt = $conn->prepare("SELECT * FROM media_uploads WHERE tubewell_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $tubewell_id, $iname, $selected_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                        <tr data-item="<?php echo htmlspecialchars($iname); ?>">
                            <td class="col-item"><strong><?php echo htmlspecialchars($iname); ?></strong></td>
                            <td class="col-make"><?php echo htmlspecialchars($r['make_model'] ?? ''); ?></td>
                            <td class="col-size"><?php echo htmlspecialchars($r['size_capacity'] ?? ''); ?></td>
                            <td class="col-status"><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                            <td class="col-hmi checkbox-cell" data-state="<?php echo isset($r['check_hmi_local']) ? (int)$r['check_hmi_local'] : 0; ?>">
                                <?php $hs = isset($r['check_hmi_local']) ? (int)$r['check_hmi_local'] : 0; echo $hs===1?'✅':($hs===2?'❌':'—'); ?>
                            </td>
                            <td class="col-web checkbox-cell" data-state="<?php echo isset($r['check_web']) ? (int)$r['check_web'] : 0; ?>">
                                <?php $ws = isset($r['check_web']) ? (int)$r['check_web'] : 0; echo $ws===1?'✅':($ws===2?'❌':'—'); ?>
                            </td>
                            <td class="col-remark"><?php echo htmlspecialchars($r['remark'] ?? ''); ?></td>
                            <td class="col-media">
                                <?php if (!empty($all_media)): ?>
                                    <div class="media-grid">
                                        <?php foreach ($all_media as $media): ?>
                                            <div class="media-container" data-media-id="<?php echo (int)$media['id']; ?>">
                                                <?php if ($media['file_type'] === 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                         class="media-preview" 
                                                         onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'image')"
                                                         alt="Media">
                                                <?php else: ?>
                                                    <video class="media-preview" onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'video')">
                                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="video/mp4">
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#999;">No media</span>
                                <?php endif; ?>
                            </td>
                            <?php
                                // Fetch latest update and its contributors for this item & date
                                $by_text = isset($r['created_by']) && $r['created_by'] !== '' ? $r['created_by'] : '—';
                                $with_list = [];
                                if ($st = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='tubewell' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                    $st->bind_param('iss', $tubewell_id, $iname, $selected_date);
                                    $st->execute();
                                    $ur = $st->get_result()->fetch_assoc();
                                    if ($ur && isset($ur['id'])) {
                                        $by_text = $ur['updated_by'] ?: $by_text;
                                        if ($uc = $conn->prepare("SELECT contributor_name FROM update_contributors WHERE update_id=? ORDER BY contributor_name")) {
                                            $uc->bind_param('i', $ur['id']);
                                            $uc->execute();
                                            $cres = $uc->get_result();
                                            while ($cr = $cres->fetch_assoc()) { if (!empty($cr['contributor_name'])) { $with_list[] = $cr['contributor_name']; } }
                                        }
                                    }
                                }
                                $with_str = !empty($with_list) ? ("\nWith: " . implode(', ', $with_list)) : '';
                            ?>
                            <td class="col-by" style="white-space:wrap;">&nbsp;<?php echo htmlspecialchars($by_text); ?><?php echo htmlspecialchars($with_str); ?></td>
                            <td class="col-at" style="white-space:wrap;">
                                <?php echo isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—'; ?>
                            </td>
                            <td class="col-actions" style="text-align:center;">
                                <?php if (!$is_locked): ?>
                                    <button type="button" class="btn btn-warning btn-sm edit-row" title="Edit" aria-label="Edit">✏️</button>
                                <?php else: ?>
                                    <span class="badge badge-warning">Locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (!empty($extra_rows)): foreach ($extra_rows as $r): 
                            // Get media for this extra item for selected date only
                            $media_stmt = $conn->prepare("SELECT * FROM media_uploads WHERE tubewell_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $tubewell_id, $r['item_name'], $selected_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                        <tr data-item="<?php echo htmlspecialchars($r['item_name']); ?>">
                            <td class="col-item"><strong><?php echo htmlspecialchars($r['item_name']); ?></strong></td>
                            <td class="col-make"><?php echo htmlspecialchars($r['make_model'] ?? ''); ?></td>
                            <td class="col-size"><?php echo htmlspecialchars($r['size_capacity'] ?? ''); ?></td>
                            <td class="col-status"><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                            <td class="col-hmi checkbox-cell" data-state="<?php echo isset($r['check_hmi_local']) ? (int)$r['check_hmi_local'] : 0; ?>">
                                <?php $hs = isset($r['check_hmi_local']) ? (int)$r['check_hmi_local'] : 0; echo $hs===1?'✅':($hs===2?'❌':'—'); ?>
                            </td>
                            <td class="col-web checkbox-cell" data-state="<?php echo isset($r['check_web']) ? (int)$r['check_web'] : 0; ?>">
                                <?php $ws = isset($r['check_web']) ? (int)$r['check_web'] : 0; echo $ws===1?'✅':($ws===2?'❌':'—'); ?>
                            </td>
                            <td class="col-remark"><?php echo htmlspecialchars($r['remark'] ?? ''); ?></td>
                            <td class="col-media">
                                <?php if (!empty($all_media)): ?>
                                    <div class="media-grid">
                                        <?php foreach ($all_media as $media): ?>
                                            <div class="media-container" data-media-id="<?php echo (int)$media['id']; ?>">
                                                <?php if ($media['file_type'] === 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                         class="media-preview" 
                                                         onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'image')"
                                                         alt="Media">
                                                <?php else: ?>
                                                    <video class="media-preview" onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'video')">
                                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="video/mp4">
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#999;">No media</span>
                                <?php endif; ?>
                            </td>
                            <?php
                                $by_text2 = isset($r['created_by']) && $r['created_by'] !== '' ? $r['created_by'] : '—';
                                $with_list2 = [];
                                if ($stx = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='tubewell' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                    $stx->bind_param('iss', $tubewell_id, $r['item_name'], $selected_date);
                                    $stx->execute();
                                    $urx = $stx->get_result()->fetch_assoc();
                                    if ($urx && isset($urx['id'])) {
                                        $by_text2 = $urx['updated_by'] ?: $by_text2;
                                        if ($ucx = $conn->prepare("SELECT contributor_name FROM update_contributors WHERE update_id=? ORDER BY contributor_name")) {
                                            $ucx->bind_param('i', $urx['id']);
                                            $ucx->execute();
                                            $cres2 = $ucx->get_result();
                                            while ($cr2 = $cres2->fetch_assoc()) { if (!empty($cr2['contributor_name'])) { $with_list2[] = $cr2['contributor_name']; } }
                                        }
                                    }
                                }
                                $with_str2 = !empty($with_list2) ? ("\nWith: " . implode(', ', $with_list2)) : '';
                            ?>
                            <td class="col-by" style="white-space:wrap;">&nbsp;<?php echo htmlspecialchars($by_text2); ?><?php echo htmlspecialchars($with_str2); ?></td>
                            <td class="col-at" style="white-space:wrap;">
                                <?php echo isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—'; ?>
                            </td>
                            <td class="col-actions" style="text-align:center;">
                                <?php if (!$is_locked): ?>
                                    <button type="button" class="btn btn-warning btn-sm edit-row" title="Edit" aria-label="Edit">✏️</button>
                                <?php else: ?>
                                    <span class="badge badge-warning">Locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>

                        <?php if (!$is_locked): ?>
                        <!-- Spare rows for quick custom item add -->
                        <?php for ($i=1; $i<=1; $i++): ?>
                        <tr class="spare-row">
                            <td class="col-item">
                                <input type="text" class="form-control-sm spare-item-name" placeholder="Item name">
                            </td>
                            <td class="col-make">
                                <input type="text" class="form-control-sm spare-make" placeholder="Make/Model">
                            </td>
                            <td class="col-size">
                                <input type="text" class="form-control-sm spare-size" placeholder="Size/Cap.">
                            </td>
                            <td class="col-status">
                                <select class="form-control-sm spare-status">
                                    <option>Not Required</option>
                                    <option>Not Supply</option>
                                    <option>Supplied</option>
                                    <option>In - installation</option>
                                    <option>Installed</option>
                                    <option>Working</option>
                                    <option>Not Working</option>
                                </select>
                            </td>
                            <td class="col-hmi checkbox-cell">
                                <label style="display:inline-flex; align-items:center; gap:4px;"><input type="checkbox" class="spare-hmi-ok"> OK</label>
                                <label style="display:inline-flex; align-items:center; gap:4px; margin-left:6px;"><input type="checkbox" class="spare-hmi-no"> NOK</label>
                            </td>
                            <td class="col-web checkbox-cell">
                                <label style="display:inline-flex; align-items:center; gap:4px;"><input type="checkbox" class="spare-web-ok"> OK</label>
                                <label style="display:inline-flex; align-items:center; gap:4px; margin-left:6px;"><input type="checkbox" class="spare-web-no"> NOK</label>
                            </td>
                            <td class="col-remark">
                                <input type="text" class="form-control-sm spare-remark" placeholder="Remark">
                            </td>
                            <td class="col-media">
                                <input type="file" class="form-control-sm spare-media" accept="image/*,video/*" multiple>
                            </td>
                            <td class="col-by">—</td>
                            <td class="col-at">—</td>
                            <td class="col-actions" style="text-align:center;">
                                <button type="button" class="btn btn-success btn-sm save-spare" title="Save" aria-label="Save">✅</button>
                                <button type="button" class="btn btn-secondary btn-sm clear-spare" title="Clear" aria-label="Clear">✖️</button>
                            </td>
                        </tr>
                        <?php endfor; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal for media preview -->
            <div id="mediaModal" class="modal">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <img class="modal-content" id="modalImage">
                <video class="modal-content" id="modalVideo" controls style="display:none;"></video>
            </div>

            <div class="btn-group" style="justify-content:center; margin-top:1rem;">
                <?php if (!$is_locked): ?>
                <form hidden method="POST" onsubmit="return confirm('Finalize today\'s changes? You will not be able to edit for this date.');">
                    <input type="hidden" name="complete_day" value="1">
                    <button type="submit" class="btn btn-success">Today changes completed</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var isLocked = <?php echo $is_locked ? 'true' : 'false'; ?>;
        if (isLocked) return; // No editing when locked

        var tubewellId = <?php echo (int)$tubewell_id; ?>;
        var siteId = <?php echo (int)$tubewell_info['site_id']; ?>;
        var selectedDate = '<?php echo $selected_date; ?>';

        function toInput(td, name, value, cls){
            td.innerHTML = '';
            var input = document.createElement('input');
            input.type = 'text';
            input.name = name;
            input.className = cls || 'form-control-sm';
            input.value = value || '';
            td.appendChild(input);
            return input;
        }
        function toSelect(td, name, value){
            td.innerHTML = '';
            var sel = document.createElement('select');
            sel.name = name;
            sel.className = 'form-control-sm';
            ['Not Required','Not Supply','Supplied','In - installation','Installed','Working','Not Working'].forEach(function(opt){
                var o = document.createElement('option');
                o.value = opt; o.textContent = opt; if (opt === value) o.selected = true; sel.appendChild(o);
            });
            td.appendChild(sel);
            return sel;
        }
        function toDualCheck(td, baseName, currentState){
            // currentState: 0 none, 1 ok, 2 not ok
            td.innerHTML = '';
            var wrap = document.createElement('div');
            var lblOk = document.createElement('label');
            var ok = document.createElement('input'); ok.type = 'checkbox'; ok.className = baseName+'-ok';
            var lblNo = document.createElement('label');
            var no = document.createElement('input'); no.type = 'checkbox'; no.className = baseName+'-no';
            lblOk.style.marginRight = '6px';
            lblOk.appendChild(ok); lblOk.appendChild(document.createTextNode(' OK'));
            lblNo.appendChild(no); lblNo.appendChild(document.createTextNode(' NOK'));
            wrap.appendChild(lblOk); wrap.appendChild(lblNo); td.appendChild(wrap);
            ok.checked = (parseInt(currentState||0) === 1);
            no.checked = (parseInt(currentState||0) === 2);
            ok.addEventListener('change', function(){ if (ok.checked) no.checked = false; });
            no.addEventListener('change', function(){ if (no.checked) ok.checked = false; });
            return { ok: ok, no: no };
        }

        function wireEditButtons(){
            var rows = document.querySelectorAll('table.data-table tbody tr[data-item]');
            rows.forEach(function(tr){
                var editBtn = tr.querySelector('.edit-row');
                if (!editBtn) return;
                editBtn.addEventListener('click', function(){
                    var item = tr.getAttribute('data-item');
                    var tdMake = tr.querySelector('.col-make');
                    var tdSize = tr.querySelector('.col-size');
                    var tdStatus = tr.querySelector('.col-status');
                    var tdHmi = tr.querySelector('.col-hmi');
                    var tdWeb = tr.querySelector('.col-web');
                    var tdRemark = tr.querySelector('.col-remark');
                    var tdMedia = tr.querySelector('.col-media');
                    var tdActions = tr.querySelector('.col-actions');

                    var inMake = toInput(tdMake, 'make_model', tdMake.textContent.trim());
                    var inSize = toInput(tdSize, 'size_capacity', tdSize.textContent.trim());
                    var inStatus = toSelect(tdStatus, 'status', tdStatus.textContent.trim());
                    var hmiState = parseInt(tdHmi.getAttribute('data-state')||'0');
                    var webState = parseInt(tdWeb.getAttribute('data-state')||'0');
                    var inHmi = toDualCheck(tdHmi, 'hmi', hmiState);
                    var inWeb = toDualCheck(tdWeb, 'web', webState);
                    var inRemark = toInput(tdRemark, 'remark', tdRemark.textContent.trim());
                    
                    // Build media edit area: existing media with delete toggles + new upload input
                    var existingContainers = Array.prototype.slice.call(tdMedia.querySelectorAll('.media-container'));
                    var toDeleteIds = [];
                    var mediaArea = document.createElement('div');
                    mediaArea.className = 'media-grid';
                    // Re-render existing media with a small delete toggle visible only in edit mode
                    existingContainers.forEach(function(mc){
                        var id = mc.getAttribute('data-media-id');
                        var clone = mc.cloneNode(true);
                        // Remove modal onclick on preview when in edit
                        var img = clone.querySelector('img.media-preview');
                        var vid = clone.querySelector('video.media-preview');
                        if (img) { img.onclick = null; }
                        if (vid) { vid.onclick = null; }
                        // Add delete mark button
                        var delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 'delete-media';
                        delBtn.title = 'Mark for delete';
                        delBtn.textContent = '×';
                        var marked = false;
                        delBtn.addEventListener('click', function(){
                            marked = !marked;
                            if (marked) {
                                toDeleteIds.push(id);
                                clone.style.outline = '2px solid red';
                                delBtn.title = 'Unmark delete';
                            } else {
                                toDeleteIds = toDeleteIds.filter(function(x){ return x !== id; });
                                clone.style.outline = '';
                                delBtn.title = 'Mark for delete';
                            }
                        });
                        clone.appendChild(delBtn);
                        mediaArea.appendChild(clone);
                    });

                    // File upload input (multiple files)
                    var uploadInput = document.createElement('input');
                    uploadInput.type = 'file';
                    uploadInput.name = 'media_files';
                    uploadInput.accept = 'image/*,video/*';
                    uploadInput.multiple = true;
                    uploadInput.className = 'form-control-sm';
                    tdMedia.innerHTML = '';
                    tdMedia.appendChild(mediaArea);
                    tdMedia.appendChild(uploadInput);

                    tdActions.innerHTML = '';
                    var contribWrap = document.createElement('div');
                    contribWrap.style.marginBottom = '.25rem';
                    var contribTitle = document.createElement('div');
                    contribTitle.textContent = 'Contributors';
                    contribTitle.style.fontWeight = '600';
                    contribTitle.style.fontSize = '12px';
                    contribWrap.appendChild(contribTitle);
                    var cboxWrap = document.createElement('div');
                    cboxWrap.style.display = 'flex'; cboxWrap.style.flexWrap = 'wrap'; cboxWrap.style.gap = '6px';
                    (window.ALL_USERS || []).forEach(function(n){
                        var lb = document.createElement('label'); lb.style.display='inline-flex'; lb.style.alignItems='center'; lb.style.gap='4px'; lb.style.fontWeight='normal'; lb.style.fontSize='12px';
                        var cb = document.createElement('input'); cb.type='checkbox'; cb.className='contrib-user'; cb.value=n;
                        lb.appendChild(cb); lb.appendChild(document.createTextNode(n)); cboxWrap.appendChild(lb);
                    });
                    contribWrap.appendChild(cboxWrap);

                    // Capture the initial base text from the "Updated By" cell when entering edit mode
                    var byCellRef = tdActions.parentElement.querySelector('.col-by');
                    var initialByBase = byCellRef ? ((byCellRef.textContent || '').split('\n')[0].trim() || '—') : '—';

                    var clearBtn = document.createElement('button');
                    clearBtn.type = 'button'; clearBtn.className = 'btn btn-light btn-sm'; clearBtn.textContent = 'Clear'; clearBtn.title = 'Clear all contributors'; clearBtn.setAttribute('aria-label','Clear contributors');
                    clearBtn.addEventListener('click', function(){
                        Array.from(cboxWrap.querySelectorAll('input.contrib-user')).forEach(function(cb){ cb.checked = false; });
                        var byCell = tdActions.parentElement.querySelector('.col-by');
                        if (byCell) {
                            // Restore only the original base, removing any dynamic With: line
                            byCell.innerText = initialByBase;
                        }
                    });
                    var saveBtn = document.createElement('button');
                    saveBtn.type = 'button'; saveBtn.className = 'btn btn-success btn-sm'; saveBtn.textContent = '✅'; saveBtn.title = 'Save'; saveBtn.setAttribute('aria-label','Save');
                    var cancelBtn = document.createElement('button');
                    cancelBtn.type = 'button'; cancelBtn.className = 'btn btn-secondary btn-sm'; cancelBtn.textContent = '✖️'; cancelBtn.title = 'Cancel'; cancelBtn.setAttribute('aria-label','Cancel');
                    tdActions.innerHTML = '';
                    tdActions.appendChild(contribWrap);
                    tdActions.appendChild(clearBtn);
                    tdActions.appendChild(saveBtn); tdActions.appendChild(cancelBtn);

                    cancelBtn.addEventListener('click', function(){ window.location.reload(); });

                    // Pre-check from existing "Updated By" cell (if it already shows "With: ...")
                    (function precheckFromByCell(){
                        var byCell = tdActions.parentElement.querySelector('.col-by');
                        if (!byCell) return;
                        var txt = byCell.innerText || '';
                        var idx = txt.indexOf('With:');
                        if (idx === -1) return;
                        var names = txt.substring(idx + 5).split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                        if (names.length === 0) return;
                        Array.from(cboxWrap.querySelectorAll('input.contrib-user')).forEach(function(cb){
                            if (names.indexOf(cb.value) !== -1) cb.checked = true;
                        });
                    })();

                    // Live update "With:" display when toggling checkboxes
                    cboxWrap.addEventListener('change', function(){
                        var selLive = Array.from(cboxWrap.querySelectorAll('input.contrib-user:checked')).map(function(x){return x.value;});
                        var byCell = tdActions.parentElement.querySelector('.col-by');
                        if (!byCell) return;
                        // Keep the original base unchanged; only toggle the With: line dynamically
                        var base = initialByBase;
                        var extra = selLive.length ? ('\nWith: ' + selLive.join(', ')) : '';
                        byCell.innerText = base + extra;
                    });

                    saveBtn.addEventListener('click', function(){
                        // Step 1: Save item details
                        var payload = new URLSearchParams();
                        payload.append('tubewell_id', String(tubewellId));
                        payload.append('site_id', String(siteId));
                        payload.append('status_date', selectedDate);
                        payload.append('item_name', item);
                        payload.append('make_model', inMake.value);
                        payload.append('size_capacity', inSize.value);
                        payload.append('status', inStatus.value);
                        var hmiVal = inHmi.ok.checked ? 1 : (inHmi.no.checked ? 2 : 0);
                        var webVal = inWeb.ok.checked ? 1 : (inWeb.no.checked ? 2 : 0);
                        payload.append('check_hmi_local_state', String(hmiVal));
                        payload.append('check_web_state', String(webVal));
                        payload.append('remark', inRemark.value);
                        payload.append('changed_by', 'web');
                        var sel = Array.from(cboxWrap.querySelectorAll('input.contrib-user:checked')).map(function(x){return x.value;});
                        if (sel.length > 0) { payload.append('contributors', sel.join(',')); }

                        fetch('update_status.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: payload.toString()
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                // If any media were marked for deletion, send after status save
                                var postDelete = Promise.resolve(true);
                                if (toDeleteIds.length > 0) {
                                    var delParams = new URLSearchParams();
                                    delParams.append('tubewell_id', String(tubewellId));
                                    delParams.append('site_id', String(siteId));
                                    delParams.append('status_date', selectedDate);
                                    delParams.append('item_name', item);
                                    delParams.append('delete_media_ids', toDeleteIds.join(','));
                                    delParams.append('changed_by', 'web');
                                    postDelete = fetch('update_status.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: delParams.toString()
                                    }).then(rr => rr.json()).then(dd => dd.success === true);
                                }
                                // Step 2: Upload files if any
                                postDelete.then(function(){
                                if (uploadInput.files.length > 0) {
                                    var uploadPromises = [];
                                    for (var i = 0; i < uploadInput.files.length; i++) {
                                        var formData = new FormData();
                                        formData.append('tubewell_id', tubewellId);
                                        formData.append('item_name', item);
                                        formData.append('status_date', selectedDate);
                                        formData.append('media_file', uploadInput.files[i]);
                                        formData.append('changed_by', 'web');

                                        uploadPromises.push(
                                            fetch('upload_media.php', {
                                                method: 'POST',
                                                body: formData
                                            }).then(r => r.json())
                                        );
                                    }

                                    Promise.all(uploadPromises)
                                        .then(results => {
                                            var allSuccess = results.every(r => r.success);
                                            if (allSuccess) {
                                                alert('Changes & all media uploaded successfully!');
                                                window.location.reload();
                                            } else {
                                                alert('Changes saved, but some media uploads failed.');
                                                window.location.reload();
                                            }
                                        })
                                        .catch(() => {
                                            alert('Changes saved, but media upload failed.');
                                            window.location.reload();
                                        });
                                } else {
                                    alert('Changes saved successfully!');
                                    window.location.reload();
                                }
                                });
                            } else {
                                alert('Failed to save changes');
                            }
                        })
                        .catch(() => alert('Error while saving'));
                    });
                });
            });
        }

        wireEditButtons();

        function wireSpareRows(){
            var spareRows = document.querySelectorAll('table.data-table tbody tr.spare-row');
            spareRows.forEach(function(tr){
                var inItem = tr.querySelector('.spare-item-name');
                var inMake = tr.querySelector('.spare-make');
                var inSize = tr.querySelector('.spare-size');
                var inStatus = tr.querySelector('.spare-status');
                var inHmiOk = tr.querySelector('.spare-hmi-ok');
                var inHmiNo = tr.querySelector('.spare-hmi-no');
                var inWebOk = tr.querySelector('.spare-web-ok');
                var inWebNo = tr.querySelector('.spare-web-no');
                var inRemark = tr.querySelector('.spare-remark');
                var inMedia = tr.querySelector('.spare-media');
                var saveBtn = tr.querySelector('.save-spare');
                var clearBtn = tr.querySelector('.clear-spare');

                // Mutual exclusivity for spare row
                if (inHmiOk && inHmiNo) {
                    inHmiOk.addEventListener('change', function(){ if (inHmiOk.checked) inHmiNo.checked = false; });
                    inHmiNo.addEventListener('change', function(){ if (inHmiNo.checked) inHmiOk.checked = false; });
                }
                if (inWebOk && inWebNo) {
                    inWebOk.addEventListener('change', function(){ if (inWebOk.checked) inWebNo.checked = false; });
                    inWebNo.addEventListener('change', function(){ if (inWebNo.checked) inWebOk.checked = false; });
                }

                if (saveBtn) {
                    saveBtn.addEventListener('click', function(){
                        var itemName = (inItem && inItem.value || '').trim();
                        if (!itemName) { alert('Please enter Item name'); return; }

                        // Step 1: Save item details first
                        var payload = new URLSearchParams();
                        payload.append('tubewell_id', tubewellId);
                        payload.append('site_id', siteId);
                        payload.append('status_date', selectedDate);
                        payload.append('item_name', itemName);
                        payload.append('make_model', inMake ? inMake.value : '');
                        payload.append('size_capacity', inSize ? inSize.value : '');
                        payload.append('status', inStatus ? inStatus.value : '');
                        var hmiVal = (inHmiOk && inHmiOk.checked) ? 1 : ((inHmiNo && inHmiNo.checked) ? 2 : 0);
                        var webVal = (inWebOk && inWebOk.checked) ? 1 : ((inWebNo && inWebNo.checked) ? 2 : 0);
                        payload.append('check_hmi_local_state', hmiVal);
                        payload.append('check_web_state', webVal);
                        payload.append('remark', inRemark ? inRemark.value : '');
                        payload.append('changed_by', 'web');
                        var sel2 = [];
                        if (!tr._contribWrap) {
                            var cw = document.createElement('div'); cw.style.marginTop = '.25rem';
                            var title = document.createElement('div'); title.textContent='Contributors'; title.style.fontWeight='600'; title.style.fontSize='12px'; cw.appendChild(title);
                            var wrap = document.createElement('div'); wrap.style.display='flex'; wrap.style.flexWrap='wrap'; wrap.style.gap='6px';
                            (window.ALL_USERS || []).forEach(function(n){ var lb=document.createElement('label'); lb.style.display='inline-flex'; lb.style.alignItems='center'; lb.style.gap='4px'; lb.style.fontSize='12px'; var cb=document.createElement('input'); cb.type='checkbox'; cb.className='contrib-user'; cb.value=n; lb.appendChild(cb); lb.appendChild(document.createTextNode(n)); wrap.appendChild(lb); });
                            cw.appendChild(wrap); tr.querySelector('.col-actions')?.insertBefore(cw, tr.querySelector('.col-actions').firstChild); tr._contribWrap = cw;
                            // Live update for spare row if there is a By cell
                            wrap.addEventListener('change', function(){
                                var selLive = Array.from(wrap.querySelectorAll('input.contrib-user:checked')).map(function(x){return x.value;});
                                var byCell = tr.querySelector('.col-by');
                                if (!byCell) return;
                                var base = (byCell.textContent || '').split('\n')[0].trim() || (<?php echo json_encode($_SESSION['full_name'] ?? ($_SESSION['username'] ?? '')); ?>);
                                var extra = selLive.length ? ('\nWith: ' + selLive.join(', ')) : '';
                                byCell.innerText = base + extra;
                            });
                        }
                        sel2 = Array.from(tr._contribWrap.querySelectorAll('input.contrib-user:checked')).map(function(x){return x.value;});
                        if (sel2.length > 0) { payload.append('contributors', sel2.join(',')); }

                        fetch('update_status.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: payload.toString()
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                // Step 2: Upload files if any
                                if (inMedia && inMedia.files.length > 0) {
                                    var uploadPromises = [];
                                    for (var i = 0; i < inMedia.files.length; i++) {
                                        var formData = new FormData();
                                        formData.append('tubewell_id', tubewellId);
                                        formData.append('item_name', itemName);
                                        formData.append('status_date', selectedDate);
                                        formData.append('media_file', inMedia.files[i]);
                                        formData.append('changed_by', 'web');

                                        uploadPromises.push(
                                            fetch('upload_media.php', {
                                                method: 'POST',
                                                body: formData
                                            }).then(r => r.json())
                                        );
                                    }

                                    Promise.all(uploadPromises)
                                        .then(results => {
                                            var allSuccess = results.every(r => r.success);
                                            if (allSuccess) {
                                                alert('Item & all media uploaded successfully!');
                                                window.location.reload();
                                            } else {
                                                alert('Item saved, but some media uploads failed');
                                                window.location.reload();
                                            }
                                        })
                                        .catch(() => {
                                            alert('Item saved, but media upload failed');
                                            window.location.reload();
                                        });
                                } else {
                                    alert('Item saved successfully!');
                                    window.location.reload();
                                }
                            } else {
                                alert('Failed to save spare item');
                            }
                        })
                        .catch(() => alert('Error while saving spare item'));
                    });
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', function(){
                        if (inItem) inItem.value = '';
                        if (inMake) inMake.value = '';
                        if (inSize) inSize.value = '';
                        if (inStatus) inStatus.selectedIndex = 0;
                        if (inHmiOk) inHmiOk.checked = false;
                        if (inHmiNo) inHmiNo.checked = false;
                        if (inWebOk) inWebOk.checked = false;
                        if (inWebNo) inWebNo.checked = false;
                        if (inRemark) inRemark.value = '';
                        if (inMedia) inMedia.value = '';
                    });
                }
            });
        }

        wireSpareRows();

        // Master note media uploader
        var masterBtn = document.getElementById('uploadMasterMediaBtn');
        if (masterBtn) {
            masterBtn.addEventListener('click', function(){
                var filesInput = document.getElementById('masterNoteFiles');
                if (!filesInput || filesInput.files.length === 0) { alert('Please select photo/video files'); return; }

                var uploads = [];
                for (var i = 0; i < filesInput.files.length; i++) {
                    var fd = new FormData();
                    fd.append('tubewell_id', tubewellId);
                    fd.append('status_date', selectedDate);
                    fd.append('media_file', filesInput.files[i]);
                    fd.append('changed_by', 'web');
                    uploads.push(fetch('upload_master_media.php', { method: 'POST', body: fd }).then(r => r.json()));
                }

                Promise.all(uploads).then(function(results){
                    var ok = results.every(function(r){ return r && r.success; });
                    if (ok) { alert('Master note media uploaded successfully'); window.location.reload(); }
                    else { alert('Some master note media failed to upload'); window.location.reload(); }
                }).catch(function(){
                    alert('Failed to upload master note media');
                });
            });
        }

        // Master note media delete marking
        var masterDeleteIds = [];
        var delHidden = document.getElementById('deleteMasterMediaIds');
        var containers = document.querySelectorAll('.media-container .master-del-btn');
        containers.forEach(function(btn){
            btn.addEventListener('click', function(){
                var wrap = btn.closest('.media-container');
                if (!wrap) return;
                var mid = wrap.getAttribute('data-media-id');
                var idx = masterDeleteIds.indexOf(mid);
                if (idx === -1) { masterDeleteIds.push(mid); wrap.style.outline = '2px solid red'; btn.title = 'Unmark delete'; }
                else { masterDeleteIds.splice(idx,1); wrap.style.outline = ''; btn.title = 'Mark for delete'; }
                if (delHidden) delHidden.value = masterDeleteIds.join(',');
            });
        });
        var masterForm = document.getElementById('masterNoteForm');
        if (masterForm) {
            masterForm.addEventListener('submit', function(){
                if (delHidden) delHidden.value = masterDeleteIds.join(',');
                var mc = document.getElementById('masterContribs');
                var sel = Array.from(document.querySelectorAll('#masterContribChecks input.mn-contrib:checked')).map(function(x){return x.value;});
                if (mc) { mc.value = sel.join(','); }
            });
        }
    })();

    // Media modal functions
    function openModal(src, type) {
        var modal = document.getElementById('mediaModal');
        var modalImage = document.getElementById('modalImage');
        var modalVideo = document.getElementById('modalVideo');
        
        if (type === 'image') {
            modalImage.src = src;
            modalImage.style.display = 'block';
            modalVideo.style.display = 'none';
        } else {
            modalVideo.src = src;
            modalVideo.style.display = 'block';
            modalImage.style.display = 'none';
        }
        
        modal.style.display = 'block';
    }

    function closeModal() {
        var modal = document.getElementById('mediaModal');
        var modalVideo = document.getElementById('modalVideo');
        
        modal.style.display = 'none';
        modalVideo.pause();
        modalVideo.currentTime = 0;
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('mediaModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
</body>
</html>
