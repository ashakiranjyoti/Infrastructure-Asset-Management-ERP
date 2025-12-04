<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Require lcs_id; derive site_id from lcs table
if (!isset($_GET['lcs_id'])) {
    die('Invalid request. lcs_id is required.');
}
$lcs_id = (int)$_GET['lcs_id'];

// Ensure LCS tables exist
$conn->query("CREATE TABLE IF NOT EXISTS lcs_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    lcs_id INT NOT NULL,
    status_date DATE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    make_model VARCHAR(255) NULL,
    size_capacity VARCHAR(255) NULL,
    status VARCHAR(50) NULL,
    check_hmi_local TINYINT(1) NOT NULL DEFAULT 0,
    check_web TINYINT(1) NOT NULL DEFAULT 0,
    remark VARCHAR(500) NULL,
    created_by VARCHAR(100) NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_row (lcs_id, status_date, item_name)
)");

// Ensure LCS master media table exists (separate from item media)
$conn->query("CREATE TABLE IF NOT EXISTS lcs_master_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image','video') NOT NULL,
    uploaded_by VARCHAR(100) NULL,
    uploaded_at DATETIME NOT NULL,
    status_date DATE NOT NULL,
    INDEX idx_lcs_master_date (lcs_id, status_date, uploaded_at)
)");

// Ensure audit log table for LCS master media
$conn->query("CREATE TABLE IF NOT EXISTS lcs_master_media_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    media_id INT NULL,
    action ENUM('uploaded','deleted') NOT NULL,
    file_path VARCHAR(500) NULL,
    file_type ENUM('image','video') NULL,
    status_date DATE NULL,
    actor VARCHAR(100) NULL,
    action_at DATETIME NOT NULL,
    INDEX idx_lcs_master_media_change (lcs_id, status_date, action_at)
)");

// Ensure LCS media table exists and is date-aware
$conn->query("CREATE TABLE IF NOT EXISTS lcs_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('image', 'video') NOT NULL,
    uploaded_by VARCHAR(100) NULL,
    uploaded_at DATETIME NOT NULL,
    status_date DATE NOT NULL,
    INDEX idx_lcs_item_date (lcs_id, item_name, status_date, uploaded_at)
)");

// Backward compatibility: add status_date and index if missing, backfill from uploaded_at
$lcsCol = $conn->query("SHOW COLUMNS FROM lcs_media LIKE 'status_date'");
if ($lcsCol && $lcsCol->num_rows === 0) {
    $conn->query("ALTER TABLE lcs_media ADD COLUMN status_date DATE NULL");
    $conn->query("UPDATE lcs_media SET status_date = DATE(uploaded_at) WHERE status_date IS NULL");
    $conn->query("ALTER TABLE lcs_media MODIFY COLUMN status_date DATE NOT NULL");
}
$lcsIdx = $conn->query("SHOW INDEX FROM lcs_media WHERE Key_name = 'idx_lcs_item_date'");
if (!$lcsIdx || $lcsIdx->num_rows === 0) {
    $conn->query("CREATE INDEX idx_lcs_item_date ON lcs_media (lcs_id, item_name, status_date, uploaded_at)");
}

// Ensure LCS media audit log table exists
$conn->query("CREATE TABLE IF NOT EXISTS lcs_media_change_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    media_id INT NULL,
    action ENUM('uploaded','deleted') NOT NULL,
    file_path VARCHAR(500) NULL,
    file_type ENUM('image','video') NULL,
    status_date DATE NULL,
    actor VARCHAR(100) NULL,
    action_at DATETIME NOT NULL,
    INDEX idx_lcs_media_change (lcs_id, item_name, status_date, action_at)
)");

// Ensure LCS master notes table exists (one per lcs)
$conn->query("CREATE TABLE IF NOT EXISTS lcs_master_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    note TEXT NULL,
    updated_by VARCHAR(100) NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_lmn_lcs FOREIGN KEY (lcs_id) REFERENCES lcs(id) ON DELETE CASCADE
)");
// Ensure date-wise support on lcs_master_notes
$col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
if ($col && $col->num_rows === 0) {
    // Add column nullable, backfill, then enforce NOT NULL
    $conn->query("ALTER TABLE lcs_master_notes ADD COLUMN status_date DATE NULL");
    $conn->query("UPDATE lcs_master_notes SET status_date = DATE(updated_at) WHERE status_date IS NULL");
    $conn->query("ALTER TABLE lcs_master_notes MODIFY COLUMN status_date DATE NOT NULL");
}
// Fix unique keys: prefer (lcs_id, status_date)
$uniq = $conn->query("SHOW INDEX FROM lcs_master_notes WHERE Key_name = 'uniq_lcs'");
if ($uniq && $uniq->num_rows > 0) {
    $conn->query("ALTER TABLE lcs_master_notes DROP INDEX uniq_lcs");
}
$uniq2 = $conn->query("SHOW INDEX FROM lcs_master_notes WHERE Key_name = 'uniq_lcs_date'");
if (!$uniq2 || $uniq2->num_rows === 0) {
    $conn->query("ALTER TABLE lcs_master_notes ADD UNIQUE KEY uniq_lcs_date (lcs_id, status_date)");
}
$conn->query("CREATE TABLE IF NOT EXISTS lcs_status_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    status_date DATE NOT NULL,
    locked_by VARCHAR(100) NULL,
    locked_at DATETIME NOT NULL,
    UNIQUE KEY uniq_lock (lcs_id, status_date)
)");

// Ensure LCS table exists (clone structure if available)
$conn->query("CREATE TABLE IF NOT EXISTS lcs LIKE tubewells");
// Rename/drop columns defensively
$has_name = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lcs' AND COLUMN_NAME = 'lcs_name'");
if (!$has_name || !$has_name->fetch_row()) {
    // Try to rename tubewell_name to lcs_name if present
    $has_tw = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lcs' AND COLUMN_NAME = 'tubewell_name'");
    if ($has_tw && $has_tw->fetch_row()) {
        $conn->query("ALTER TABLE lcs CHANGE tubewell_name lcs_name VARCHAR(255)");
    }
}
// Drop SIM column if present (schema uses sim_no in this app)
$has_sim = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lcs' AND COLUMN_NAME = 'sim_no'");
if ($has_sim && $has_sim->fetch_row()) {
    $conn->query("ALTER TABLE lcs DROP COLUMN sim_no");
}
// Unique per site
$conn->query("ALTER TABLE lcs ADD UNIQUE KEY unique_lcs_per_site (site_id)");

// Retry ensure LCS master notes table now that lcs table is ensured
$conn->query("CREATE TABLE IF NOT EXISTS lcs_master_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    note TEXT NULL,
    updated_by VARCHAR(100) NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_lcs (lcs_id),
    CONSTRAINT fk_lmn_lcs FOREIGN KEY (lcs_id) REFERENCES lcs(id) ON DELETE CASCADE
)");

// Load LCS + site
$ls = $conn->prepare("SELECT l.*, s.site_name FROM lcs l JOIN sites s ON l.site_id = s.id WHERE l.id = ?");
$ls->bind_param('i', $lcs_id);
$ls->execute();
$lcs = $ls->get_result()->fetch_assoc();
if (!$lcs) {
    die('LCS not found.');
}
$site_id = (int)$lcs['site_id'];

// Selected date (locked to today)
$today = date('Y-m-d');
$selected_date = $today;

// Handle finalize lock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_day'])) {
    $locker = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '' ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'web');
    $ls = $conn->prepare("INSERT INTO lcs_status_locks (lcs_id, status_date, locked_by, locked_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE locked_by = VALUES(locked_by), locked_at = VALUES(locked_at)");
    $ls->bind_param('iss', $lcs_id, $selected_date, $locker);
    $ls->execute();
    // Save master note contributors if provided
    $conn->query("CREATE TABLE IF NOT EXISTS lcs_master_note_contributors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lcs_id INT NOT NULL,
        status_date DATE NOT NULL,
        contributor_name VARCHAR(100) NOT NULL,
        added_at DATETIME NOT NULL,
        UNIQUE KEY uniq_lcs_mn (lcs_id, status_date, contributor_name),
        INDEX idx_lcs_mn_contrib (contributor_name)
    )");
    if (!empty($_POST['master_contributors'])) {
        $raw = trim((string)$_POST['master_contributors']);
        if ($raw !== '') {
            $names = array_filter(array_map(function($v){ return trim($v); }, explode(',', $raw)), function($v){ return $v !== ''; });
            $seen = [];
            foreach ($names as $nm) {
                if (strcasecmp($nm, $updated_by) === 0) { continue; }
                $key = mb_strtolower($nm);
                if (isset($seen[$key])) { continue; }
                $seen[$key] = true;
                if ($ins = $conn->prepare("INSERT IGNORE INTO lcs_master_note_contributors (lcs_id, status_date, contributor_name, added_at) VALUES (?, ?, ?, NOW())")) {
                    $ins->bind_param('iss', $lcs_id, $selected_date, $nm);
                    $ins->execute();
                }
            }
        }
    }

    header('Location: add_lcs_parameters.php?lcs_id=' . $lcs_id . '&date=' . urlencode($selected_date));
    exit();
}

// Check lock
$lk = $conn->prepare('SELECT locked_by, locked_at FROM lcs_status_locks WHERE lcs_id = ? AND status_date = ?');
$lk->bind_param('is', $lcs_id, $selected_date);
$lk->execute();
$lock_row = $lk->get_result()->fetch_assoc();
$is_locked = (bool)$lock_row;

// Handle Master Note (LCS-wise) save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_master_note'])) {
    $note_text = isset($_POST['master_note']) ? trim($_POST['master_note']) : '';
    $updated_by = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== ''
        ? $_SESSION['full_name']
        : (isset($_SESSION['username']) && $_SESSION['username'] !== '' ? $_SESSION['username'] : 'web');

    // Apply deletions for master note media (only for selected date) from lcs_master_media
    if (!empty($_POST['delete_master_media_ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_POST['delete_master_media_ids'])));
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $sql = "SELECT id, file_path, file_type, status_date FROM lcs_master_media WHERE lcs_id = ? AND id IN ($in)";
            if ($st = $conn->prepare($sql)) {
                $bindParams = array_merge([$types ? 'i'.$types : 'i'], [$lcs_id], $ids);
                $refs = [];
                foreach ($bindParams as $k => $v) { $refs[$k] = &$bindParams[$k]; }
                call_user_func_array([$st, 'bind_param'], $refs);
                $st->execute();
                $res = $st->get_result();
                while ($m = $res->fetch_assoc()) {
                    if ($m['status_date'] === $selected_date) {
                        // Log deletion
                        if ($log = $conn->prepare("INSERT INTO lcs_master_media_change_log (lcs_id, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, 'deleted', ?, ?, ?, ?, NOW())")) {
                            $ft = isset($m['file_type']) ? $m['file_type'] : null;
                            $log->bind_param('iissss', $lcs_id, $m['id'], $m['file_path'], $ft, $selected_date, $updated_by);
                            $log->execute();
                        }
                        // Delete file and DB row
                        if (!empty($m['file_path']) && file_exists($m['file_path'])) { @unlink($m['file_path']); }
                        if ($dd = $conn->prepare('DELETE FROM lcs_master_media WHERE id = ? AND lcs_id = ?')) {
                            $dd->bind_param('ii', $m['id'], $lcs_id);
                            $dd->execute();
                        }
                    }
                }
            }
        }
    }

    // If lcs_master_notes has status_date column, save per-date
    $has_sd = false;
    $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
    if ($col && $col->num_rows > 0) {
        $has_sd = true;
    }

    if ($has_sd) {
        if ($stmt = $conn->prepare("INSERT INTO lcs_master_notes (lcs_id, status_date, note, updated_by, updated_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE status_date = VALUES(status_date), note = VALUES(note), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)")) {
            $stmt->bind_param('isss', $lcs_id, $selected_date, $note_text, $updated_by);
            $stmt->execute();
        }
        // Save master note contributors (date-wise) with full replace
        $conn->query("CREATE TABLE IF NOT EXISTS lcs_master_note_contributors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lcs_id INT NOT NULL,
            status_date DATE NOT NULL,
            contributor_name VARCHAR(100) NOT NULL,
            added_at DATETIME NOT NULL,
            UNIQUE KEY uniq_lcs_mn (lcs_id, status_date, contributor_name),
            INDEX idx_lcs_mn_contrib (contributor_name)
        )");
        $selected_names = [];
        if (isset($_POST['master_contributors'])) {
            $raw = trim((string)$_POST['master_contributors']);
            if ($raw !== '') {
                $selected_names = array_values(array_filter(array_map(function($v){ return trim($v); }, explode(',', $raw)), function($v){ return $v !== ''; }));
            }
        }
        if (empty($selected_names)) {
            if ($del = $conn->prepare('DELETE FROM lcs_master_note_contributors WHERE lcs_id = ? AND status_date = ?')) {
                $del->bind_param('is', $lcs_id, $selected_date);
                $del->execute();
            }
        } else {
            $place = implode(',', array_fill(0, count($selected_names), '?'));
            $types = str_repeat('s', count($selected_names));
            $sqlDel = "DELETE FROM lcs_master_note_contributors WHERE lcs_id = ? AND status_date = ? AND contributor_name NOT IN ($place)";
            if ($del = $conn->prepare($sqlDel)) {
                $params = array_merge([$lcs_id, $selected_date], $selected_names);
                $bindTypes = 'is' . $types;
                $refs = [];
                $refs[] = & $bindTypes;
                foreach ($params as $k => $v) { $refs[] = & $params[$k]; }
                call_user_func_array([$del, 'bind_param'], $refs);
                $del->execute();
            }
        }
        $seen = [];
        foreach ($selected_names as $nm) {
            if (strcasecmp($nm, $updated_by) === 0) { continue; }
            $key = mb_strtolower($nm);
            if (isset($seen[$key])) { continue; }
            $seen[$key] = true;
            if ($ins = $conn->prepare("INSERT IGNORE INTO lcs_master_note_contributors (lcs_id, status_date, contributor_name, added_at) VALUES (?, ?, ?, NOW())")) {
                $ins->bind_param('iss', $lcs_id, $selected_date, $nm);
                $ins->execute();
            }
        }
        // Handle new master note media uploads in the same save
        if (!empty($_FILES['lcs_master_media_files']) && is_array($_FILES['lcs_master_media_files']['name'])) {
            // Ensure tables exist
            $conn->query("CREATE TABLE IF NOT EXISTS lcs_master_media (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lcs_id INT NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_type ENUM('image','video') NOT NULL,
                uploaded_by VARCHAR(100) NULL,
                uploaded_at DATETIME NOT NULL,
                status_date DATE NOT NULL,
                INDEX idx_lcs_master_date (lcs_id, status_date, uploaded_at)
            )");
            $conn->query("CREATE TABLE IF NOT EXISTS lcs_master_media_change_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                lcs_id INT NOT NULL,
                media_id INT NULL,
                action ENUM('uploaded','deleted') NOT NULL,
                file_path VARCHAR(500) NULL,
                file_type ENUM('image','video') NULL,
                status_date DATE NULL,
                actor VARCHAR(100) NULL,
                action_at DATETIME NOT NULL,
                INDEX idx_lcs_master_media_change (lcs_id, status_date, action_at)
            )");
            $baseDir = 'uploads/lcs_master_note/';
            if (!file_exists($baseDir)) { @mkdir($baseDir, 0777, true); }
            $count = count($_FILES['lcs_master_media_files']['name']);
            for ($i = 0; $i < $count; $i++) {
                $name = $_FILES['lcs_master_media_files']['name'][$i] ?? '';
                $tmp = $_FILES['lcs_master_media_files']['tmp_name'][$i] ?? '';
                $type = $_FILES['lcs_master_media_files']['type'][$i] ?? '';
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
                $sub = $isImage ? 'images/' : 'videos/';
                if (!file_exists($baseDir.$sub)) { @mkdir($baseDir.$sub, 0777, true); }
                $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name));
                $dest = $baseDir.$sub.uniqid().'-'.$safe;
                if (@move_uploaded_file($tmp, $dest)) {
                    $ft = $isImage ? 'image' : 'video';
                    if ($stm = $conn->prepare("INSERT INTO lcs_master_media (lcs_id, file_path, file_type, uploaded_by, uploaded_at, status_date) VALUES (?, ?, ?, ?, NOW(), ?)")) {
                        $stm->bind_param('issss', $lcs_id, $dest, $ft, $updated_by, $selected_date);
                        if ($stm->execute()) {
                            $mid = $conn->insert_id;
                            if ($log = $conn->prepare("INSERT INTO lcs_master_media_change_log (lcs_id, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, 'uploaded', ?, ?, ?, ?, NOW())")) {
                                $log->bind_param('isssss', $lcs_id, $mid, $dest, $ft, $selected_date, $updated_by);
                                $log->execute();
                            }
                        }
                    }
                }
            }
        }
    } else {
        // Fallback: single-note behavior
        if ($stmt = $conn->prepare("INSERT INTO lcs_master_notes (lcs_id, note, updated_by, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE note = VALUES(note), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)")) {
            $stmt->bind_param('iss', $lcs_id, $note_text, $updated_by);
            $stmt->execute();
        }
        // Optional: if table has no status_date, store contributors without date filter by using a sentinel date (skip for now)
    }

    header('Location: add_lcs_parameters.php?lcs_id=' . $lcs_id . '&date=' . urlencode($selected_date));
    exit();
}

// Handle media deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_media'])) {
    $media_id = (int)$_POST['media_id'];
    $item_name = $_POST['item_name'] ?? '';
    
    // Get file path before deletion
    $stmt = $conn->prepare("SELECT file_path FROM lcs_media WHERE id = ? AND lcs_id = ?");
    $stmt->bind_param('ii', $media_id, $lcs_id);
    $stmt->execute();
    $media = $stmt->get_result()->fetch_assoc();
    
    if ($media) {
        // Delete from database
        $del_stmt = $conn->prepare("DELETE FROM lcs_media WHERE id = ? AND lcs_id = ?");
        $del_stmt->bind_param('ii', $media_id, $lcs_id);
        if ($del_stmt->execute()) {
            // Delete physical file
            if (file_exists($media['file_path'])) {
                unlink($media['file_path']);
            }
            $_SESSION['success'] = "Media deleted successfully!";
        }
    }
    
    header('Location: add_lcs_parameters.php?lcs_id=' . $lcs_id . '&date=' . urlencode($selected_date));
    exit();
}

// Load existing LCS master note (date-wise if column exists, else latest)
$master_note_row = null;
// Detect if status_date column exists for date-wise notes
$has_sd_col = false;
$col_chk = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
if ($col_chk && $col_chk->num_rows > 0) { $has_sd_col = true; }

if ($has_sd_col) {
    // Try to get note for selected date
    if ($mn = $conn->prepare('SELECT note, updated_by, updated_at, status_date FROM lcs_master_notes WHERE lcs_id = ? AND status_date = ?')) {
        $mn->bind_param('is', $lcs_id, $selected_date);
        $mn->execute();
        $master_note_row = $mn->get_result()->fetch_assoc();
    }
    // Fallback to most recent previous date's note
    if (!$master_note_row) {
        if ($mn_prev = $conn->prepare('SELECT note, updated_by, updated_at, status_date FROM lcs_master_notes WHERE lcs_id = ? AND status_date < ? ORDER BY status_date DESC LIMIT 1')) {
            $mn_prev->bind_param('is', $lcs_id, $selected_date);
            $mn_prev->execute();
            $master_note_row = $mn_prev->get_result()->fetch_assoc();
        }
    }
} else {
    // Single master note per LCS: load the latest by updated_at
    if ($mn = $conn->prepare('SELECT note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? ORDER BY updated_at DESC LIMIT 1')) {
        $mn->bind_param('i', $lcs_id);
        $mn->execute();
        $master_note_row = $mn->get_result()->fetch_assoc();
    }
}

// Fetch last update info for this LCS
$lu = $conn->prepare("SELECT created_by, updated_at FROM lcs_status_history WHERE lcs_id = ? AND updated_at IS NOT NULL ORDER BY updated_at DESC LIMIT 1");
$lu->bind_param('i', $lcs_id);
$lu->execute();
$last_update_row = $lu->get_result()->fetch_assoc();

// Preload LCS master note media for the selected date from lcs_master_media
$master_media = [];
if ($mm = $conn->prepare('SELECT id, file_path, file_type, uploaded_by, uploaded_at FROM lcs_master_media WHERE lcs_id = ? AND status_date = ? ORDER BY uploaded_at DESC')) {
    $mm->bind_param('is', $lcs_id, $selected_date);
    $mm->execute();
    $mres = $mm->get_result();
    while ($row = $mres->fetch_assoc()) { $master_media[] = $row; }
}

// Load saved LCS master note contributors for selected date (if date-wise)
$lcs_master_contribs = [];
if ($has_sd_col) {
    if ($mc = $conn->prepare('SELECT contributor_name FROM lcs_master_note_contributors WHERE lcs_id = ? AND status_date = ? ORDER BY contributor_name')) {
        $mc->bind_param('is', $lcs_id, $selected_date);
        $mc->execute();
        $cres = $mc->get_result();
        while ($cr = $cres->fetch_assoc()) { if (!empty($cr['contributor_name'])) { $lcs_master_contribs[] = $cr['contributor_name']; } }
    }
}

// Load active LCS items
$active_items = [];
$items_res = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ");
if ($items_res) {
    while ($ir = $items_res->fetch_assoc()) {
        $active_items[] = $ir['item_name'];
    }
}

// For each item, pick today's entry or last known previous entry
$rows_by_item = [];
foreach ($active_items as $iname) {
    // Try today's
    $stmt = $conn->prepare("SELECT * FROM lcs_status_history WHERE lcs_id = ? AND status_date = ? AND item_name = ? LIMIT 1");
    $stmt->bind_param('iss', $lcs_id, $selected_date, $iname);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        $stmt2 = $conn->prepare("SELECT * FROM lcs_status_history WHERE lcs_id = ? AND item_name = ? ORDER BY status_date DESC LIMIT 1");
        $stmt2->bind_param('is', $lcs_id, $iname);
        $stmt2->execute();
        $row = $stmt2->get_result()->fetch_assoc();
    }
    $rows_by_item[$iname] = $row ?: [];
}

// Load additional custom items (spares) not in lcs_item
// Show the latest row per spare item_name up to selected_date (today's if exists, else previous)
$extra_rows = [];
if ($ex = $conn->prepare(
    "SELECT t.* FROM lcs_status_history t
      JOIN (
        SELECT item_name, MAX(status_date) AS sd
        FROM lcs_status_history
        WHERE lcs_id = ? AND status_date <= ? AND item_name NOT IN (SELECT item_name FROM lcs_item WHERE is_active = 1)
        GROUP BY item_name
      ) x ON t.lcs_id = ? AND t.item_name = x.item_name AND t.status_date = x.sd
     ORDER BY t.item_name"
)) {
    $ex->bind_param('isi', $lcs_id, $selected_date, $lcs_id);
    $ex->execute();
    $er = $ex->get_result();
    while ($r = $er->fetch_assoc()) {
        $extra_rows[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LCS Daily Status - <?php echo htmlspecialchars($lcs['site_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .table-container {
            overflow-x: auto;
        }

        table.data-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #d1d5db;
        }

        table.data-table th,
        table.data-table td {
            white-space: normal;
            padding: 6px 8px;
            font-size: 13px;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        table.data-table input[type="text"],
        table.data-table select {
            width: 100%;
            box-sizing: border-box;
        }

        .col-actions {
            width: 120px;
        }
        
        .col-media {
            width: 120px;
        }
        
        .media-preview {
            max-width: 60px;
            max-height: 60px;
            border-radius: 4px;
            cursor: pointer;
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
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
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
            max-width: 90%;
            max-height: 90%;
            margin-top: 2%;
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
            <a href="view_lcs.php?lcs_id=<?php echo (int)$lcs_id; ?>" class="btn btn-secondary">← Back</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div style="text-align:center; margin-bottom:1.5rem;">
                <h2 style="color:#2d3748;">🧰 LCS Daily Status</h2>
                <p style="color:#718096;">Site: <strong><?php echo htmlspecialchars($lcs['site_name']); ?></strong></p>
                <?php if (!empty($last_update_row) && !empty($last_update_row['updated_at'])): ?>
                    <div class="alert alert-success" style="display:inline-block; margin-top:.25rem;">
                        Last updated by <strong><?php echo htmlspecialchars($last_update_row['created_by'] ?? '—'); ?></strong>
                        on <?php echo date('d M Y H:i', strtotime($last_update_row['updated_at'])); ?>
                    </div>
            <script>
                (function(){
                    var form = document.getElementById('lcsMasterNoteForm');
                    var checksWrap = document.getElementById('lcsMasterContribChecks');
                    var display = document.getElementById('lcsMasterContribDisplay');
                    var hidden = document.getElementById('lcsMasterContribs');
                    if (!form || !checksWrap || !hidden) return;
                    function refreshDisplay(){
                        var sel = Array.prototype.slice.call(checksWrap.querySelectorAll('input.lcs-mn-contrib:checked')).map(function(x){return x.value;});
                        hidden.value = sel.join(',');
                        display.textContent = sel.length ? ('Contributors: ' + sel.join(', ')) : '';
                    }
                    checksWrap.addEventListener('change', refreshDisplay);
                    form.addEventListener('submit', function(){ refreshDisplay(); });
                    // init
                    refreshDisplay();
                })();
            </script>
                <?php endif; ?>
            </div>

            <div class="card" style="margin-bottom:1rem; border:1px solid #e5e7eb;">
                <h3 style="margin:0 0 .5rem 0; color:#2d3748;">📝 Master Note (<?php echo $has_sd_col ? 'Date-wise' : 'LCS-wise'; ?>)</h3>
                <form method="POST" enctype="multipart/form-data" id="lcsMasterNoteForm" style="display:flex; flex-direction:column; gap:.5rem;">
                    <input type="hidden" name="save_master_note" value="1">
                    <input type="hidden" name="delete_master_media_ids" id="deleteLcsMasterMediaIds" value="">
                    <input type="hidden" name="master_contributors" id="lcsMasterContribs" value="">
                    <textarea name="master_note" rows="3" class="form-control" placeholder="Note down any important issue or matter related to the LCS here..." style="resize:vertical;" readonly><?php echo htmlspecialchars($master_note_row['note'] ?? ''); ?></textarea>
                    <div>
                        <div style="font-weight:600; margin-bottom:.25rem;">Contributors</div>
                        <div id="lcsMasterContribChecks" style="display:flex; flex-wrap:wrap; gap:8px;">
                            <?php foreach ($all_user_names as $nm): ?>
                                <label style="display:inline-flex; align-items:center; gap:4px; font-weight:normal;">
                                    <input type="checkbox" class="lcs-mn-contrib" value="<?php echo htmlspecialchars($nm); ?>" <?php echo in_array($nm, $lcs_master_contribs, true) ? 'checked' : ''; ?> disabled> <?php echo htmlspecialchars($nm); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div id="lcsMasterContribDisplay" style="margin-top:.25rem; color:#4a5568; font-size:.9rem;"></div>
                    </div>
                    <?php if (!$is_locked): ?>
                    <div>
                        <div style="font-weight:600; margin-bottom:.25rem;">Add Photo / Video</div>
                        <input type="file" id="lcsMasterMediaFiles" name="lcs_master_media_files[]" class="form-control-sm" accept="image/*,video/*" multiple disabled>
                    </div>
                    <?php endif; ?>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="font-size:.85rem; color:#4a5568;">
                            <?php if (!empty($master_note_row)): ?>
                                <?php 
                                $note_date = isset($master_note_row['status_date']) ? $master_note_row['status_date'] : '';
                                $is_prev_note = $has_sd_col && $note_date && $note_date !== $selected_date;
                                ?>
                                <?php if ($is_prev_note): ?>
                                    <span style="color:#e53e3e;">⚠️ Showing previous date's note:</span><br>
                                    Note by <strong><?php echo htmlspecialchars($master_note_row['updated_by'] ?? '—'); ?></strong>
                                    on <?php echo date('d M Y H:i', strtotime($master_note_row['updated_at'])); ?>
                                    (Date: <?php echo date('d M Y', strtotime($note_date)); ?>)
                                <?php else: ?>
                                    Note by <strong><?php echo htmlspecialchars($master_note_row['updated_by'] ?? '—'); ?></strong>
                                    on <?php echo date('d M Y H:i', strtotime($master_note_row['updated_at'])); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php echo $has_sd_col ? 'No master note added yet for this date.' : 'No master note added yet.'; ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if (!$is_locked): ?>
                                <div style="display:flex; gap:.5rem; align-items:center;">
                                    <button type="button" id="lcsMnEditBtn" class="btn btn-warning btn-sm" title="Edit">✏️ Edit</button>
                                    <button type="submit" id="lcsMnSaveBtn" class="btn btn-success btn-sm" title="Save" disabled>✅ Save</button>
                                    <button type="button" id="lcsMnCancelBtn" class="btn btn-secondary btn-sm" title="Cancel" style="display:none;">✖️ Cancel</button>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-warning">Locked - Cannot edit</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <script>
                    (function(){
                        var form = document.getElementById('lcsMasterNoteForm');
                        var checksWrap = document.getElementById('lcsMasterContribChecks');
                        var display = document.getElementById('lcsMasterContribDisplay');
                        var hidden = document.getElementById('lcsMasterContribs');
                        var ta = form ? form.querySelector('textarea[name="master_note"]') : null;
                        var editBtn = document.getElementById('lcsMnEditBtn');
                        var saveBtn = document.getElementById('lcsMnSaveBtn');
                        var cancelBtn = document.getElementById('lcsMnCancelBtn');
                        var fileInput = document.getElementById('lcsMasterMediaFiles');
                        var delBtns = Array.prototype.slice.call(document.querySelectorAll('.media-container .lcs-master-del-btn'));
                        if (!form || !checksWrap || !hidden) return;
                        function refreshDisplay(){
                            var sel = Array.prototype.slice.call(checksWrap.querySelectorAll('input.lcs-mn-contrib:checked')).map(function(x){return x.value;});
                            hidden.value = sel.join(',');
                            display.textContent = sel.length ? ('Contributors: ' + sel.join(', ')) : '';
                        }
                        checksWrap.addEventListener('change', refreshDisplay);
                        form.addEventListener('submit', function(){ refreshDisplay(); });
                        refreshDisplay();

                        var initial = {
                            text: ta ? ta.value : '',
                            selected: Array.prototype.slice.call(checksWrap.querySelectorAll('input.lcs-mn-contrib:checked')).map(function(x){return x.value;})
                        };
                        function setDisabledState(disabled){
                            if (ta) ta.readOnly = disabled ? true : false;
                            Array.prototype.slice.call(checksWrap.querySelectorAll('input.lcs-mn-contrib')).forEach(function(cb){ cb.disabled = disabled; });
                            if (saveBtn) saveBtn.disabled = disabled;
                            if (cancelBtn) cancelBtn.style.display = disabled ? 'none' : 'inline-block';
                            if (fileInput) fileInput.disabled = disabled;
                            delBtns.forEach(function(b){ b.style.display = disabled ? 'none' : 'inline-block'; });
                            if (editBtn) editBtn.style.display = disabled ? 'inline-block' : 'none';
                        }
                        setDisabledState(true);
                        if (editBtn) editBtn.addEventListener('click', function(){ setDisabledState(false); if (ta){ ta.focus(); ta.scrollIntoView({behavior:'smooth', block:'center'});} });
                        if (cancelBtn) cancelBtn.addEventListener('click', function(){ window.location.reload(); });
                    })();
                </script>

                <!-- LCS Master Note Media (date-wise) -->
                <div style="margin-top:.5rem;">
                    <div style="font-weight:600; margin-bottom:.25rem;">Photo / Video for this note</div>
                    <?php if (!empty($master_media)): ?>
                        <div style="display:flex; flex-wrap:wrap; gap:5px;" class="media-grid">
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
                                        <button type="button" class="delete-media lcs-master-del-btn" title="Mark for delete" style="display:none;">×</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span style="color:#999;">No media</span>
                    <?php endif; ?>
                
                </div>
            </div>

            <form method="GET" style="display:flex; gap:1rem; align-items:end; margin-bottom:1rem;">
                <input type="hidden" name="lcs_id" value="<?php echo (int)$lcs_id; ?>">
                <div class="form-group" style="min-width:220px;">
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
                            <th width="16%">Item</th>
                            <th width="14%">Make/Model</th>
                            <th width="12%">Size/Capacity</th>
                            <th width="10%">Status</th>
                            <th width="20%">Remark</th>
                            <th width="12%">Photo/Video</th>
                            <th width="8%">Updated By</th>
                            <th width="10%">Updated At</th>
                            <th width="8%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_items as $iname): 
                            $r = $rows_by_item[$iname] ?? []; 
                            // Get media for this item for selected date only
                            $media_stmt = $conn->prepare("SELECT * FROM lcs_media WHERE lcs_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $lcs_id, $iname, $selected_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                            <tr data-item="<?php echo htmlspecialchars($iname); ?>">
                                <td class="col-item"><strong><?php echo htmlspecialchars($iname); ?></strong></td>
                                <td class="col-make"><?php echo htmlspecialchars($r['make_model'] ?? ''); ?></td>
                                <td class="col-size"><?php echo htmlspecialchars($r['size_capacity'] ?? ''); ?></td>
                                <td class="col-status"><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                <td class="col-remark"><?php echo htmlspecialchars($r['remark'] ?? ''); ?></td>
                                <td class="col-media">
                                    <?php if (!empty($all_media)): ?>
                                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
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
                                        —
                                    <?php endif; ?>
                                </td>
                                <?php
                                    // Fetch latest update and its contributors for this LCS item & date
                                    $by_text = isset($r['created_by']) && $r['created_by'] !== '' ? $r['created_by'] : '—';
                                    $with_list = [];
                                    if ($st = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='lcs' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                        $st->bind_param('iss', $lcs_id, $iname, $selected_date);
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
                                <td class="col-at"><?php echo isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—'; ?></td>
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
                            $media_stmt = $conn->prepare("SELECT * FROM lcs_media WHERE lcs_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $lcs_id, $r['item_name'], $selected_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>
                            <tr data-item="<?php echo htmlspecialchars($r['item_name']); ?>">
                                <td class="col-item"><strong><?php echo htmlspecialchars($r['item_name']); ?></strong></td>
                                <td class="col-make"><?php echo htmlspecialchars($r['make_model'] ?? ''); ?></td>
                                <td class="col-size"><?php echo htmlspecialchars($r['size_capacity'] ?? ''); ?></td>
                                <td class="col-status"><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                <td class="col-remark"><?php echo htmlspecialchars($r['remark'] ?? ''); ?></td>
                                <td class="col-media">
                                    <?php if (!empty($all_media)): ?>
                                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                            <?php foreach ($all_media as $media): ?>
                                                <div class="media-container">
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
                                        —
                                    <?php endif; ?>
                                </td>
                                <?php
                                    // Latest update for spare (extra) item & its contributors
                                    $by_text2 = isset($r['created_by']) && $r['created_by'] !== '' ? $r['created_by'] : '—';
                                    $with_list2 = [];
                                    if ($stx = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='lcs' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                        $stx->bind_param('iss', $lcs_id, $r['item_name'], $selected_date);
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
                                <td class="col-at"><?php echo isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—'; ?></td>
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
                            <!-- Spare row for quick custom LCS item add -->
                            <tr class="spare-row">
                                <td class="col-item"><input type="text" class="form-control-sm spare-item-name" placeholder="Item name"></td>
                                <td class="col-make"><input type="text" class="form-control-sm spare-make" placeholder="Make/Model"></td>
                                <td class="col-size"><input type="text" class="form-control-sm spare-size" placeholder="Size/Cap."></td>
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
                                <td class="col-remark"><input type="text" class="form-control-sm spare-remark" placeholder="Remark"></td>
                                <td class="col-media"><input type="file" class="form-control-sm spare-media" accept="image/*,video/*" multiple></td>
                                <td class="col-by">—</td>
                                <td class="col-at">—</td>
                                <td class="col-actions" style="text-align:center;">
                                    <button type="button" class="btn btn-success btn-sm save-spare" title="Save" aria-label="Save">✅</button>
                                    <button type="button" class="btn btn-secondary btn-sm clear-spare" title="Clear" aria-label="Clear">✖️</button>
                                </td>
                            </tr>
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

            <div hidden class="btn-group" style="justify-content:center; margin-top:1rem;">
                <?php if (!$is_locked): ?>
                    <form hidden method="POST" onsubmit="return confirm('Finalize today\'s LCS changes? You will not be able to edit for this date.');">
                        <input type="hidden" name="complete_day" value="1">
                        <button type="submit" class="btn btn-success">Today changes completed</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var isLocked = <?php echo $is_locked ? 'true' : 'false'; ?>;
            if (isLocked) return;
            var siteId = <?php echo (int)$site_id; ?>;
            var lcsId = <?php echo (int)$lcs_id; ?>;
            var selectedDate = '<?php echo $selected_date; ?>';

            function toInput(td, name, value, cls) {
                td.innerHTML = '';
                var i = document.createElement('input');
                i.type = 'text';
                i.name = name;
                i.className = cls || 'form-control-sm';
                i.value = value || '';
                td.appendChild(i);
                return i;
            }

            function toSelect(td, name, value) {
                td.innerHTML = '';
                var s = document.createElement('select');
                s.name = name;
                s.className = 'form-control-sm';
                ['Not Required', 'Not Supply', 'Supplied', 'In - installation', 'Installed', 'Working', 'Not Working'].forEach(function(opt) {
                    var o = document.createElement('option');
                    o.value = opt;
                    o.textContent = opt;
                    if (opt === value) o.selected = true;
                    s.appendChild(o);
                });
                td.appendChild(s);
                return s;
            }

            // 🟢 Edit existing rows
            function wireEditButtons() {
                document.querySelectorAll('table.data-table tbody tr[data-item]').forEach(function(tr) {
                    var btn = tr.querySelector('.edit-row');
                    if (!btn) return;

                    btn.addEventListener('click', function() {
                        var item = tr.getAttribute('data-item');
                        var tdMake = tr.querySelector('.col-make');
                        var tdSize = tr.querySelector('.col-size');
                        var tdStatus = tr.querySelector('.col-status');
                        var tdRemark = tr.querySelector('.col-remark');
                        var tdMedia = tr.querySelector('.col-media');
                        var tdActions = tr.querySelector('.col-actions');

                        var inMake = toInput(tdMake, 'make_model', tdMake.textContent.trim());
                        var inSize = toInput(tdSize, 'size_capacity', tdSize.textContent.trim());
                        var inStatus = toSelect(tdStatus, 'status', tdStatus.textContent.trim());
                        var inRemark = toInput(tdRemark, 'remark', tdRemark.textContent.trim());

                        // Build media edit area: existing media with mark-for-delete + new upload input
                        var existingContainers = Array.prototype.slice.call(tdMedia.querySelectorAll('.media-container'));
                        var toDeleteIds = [];
                        var mediaArea = document.createElement('div');
                        mediaArea.style.display = 'flex';
                        mediaArea.style.flexWrap = 'wrap';
                        mediaArea.style.gap = '5px';
                        existingContainers.forEach(function(mc){
                            var id = mc.getAttribute('data-media-id');
                            var clone = mc.cloneNode(true);
                            var img = clone.querySelector('img.media-preview');
                            var vid = clone.querySelector('video.media-preview');
                            if (img) { img.onclick = null; }
                            if (vid) { vid.onclick = null; }
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

                        // 📸 File input for new media
                        var inMedia = document.createElement('input');
                        inMedia.type = 'file';
                        inMedia.accept = 'image/*,video/*';
                        inMedia.multiple = true;
                        inMedia.className = 'form-control-sm edit-media';
                        tdMedia.innerHTML = '';
                        tdMedia.appendChild(mediaArea);
                        tdMedia.appendChild(inMedia);

                        // Contributors checklist (item-wise)
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

                        cancelBtn.addEventListener('click', () => window.location.reload());

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
                            var sel = Array.from(cboxWrap.querySelectorAll('input.contrib-user:checked')).map(function(x){return x.value;});
                            var byCell = tdActions.parentElement.querySelector('.col-by');
                            if (!byCell) return;
                            // Keep the original base unchanged; only toggle the With: line dynamically
                            var base = initialByBase;
                            var extra = sel.length ? ('\nWith: ' + sel.join(', ')) : '';
                            byCell.innerText = base + extra;
                        });

                        saveBtn.addEventListener('click', function() {
                            var payload = new URLSearchParams();
                            payload.append('site_id', siteId);
                            payload.append('lcs_id', lcsId);
                            payload.append('status_date', selectedDate);
                            payload.append('item_name', item);
                            payload.append('make_model', inMake.value);
                            payload.append('size_capacity', inSize.value);
                            payload.append('status', inStatus.value);
                            payload.append('remark', inRemark.value);
                            payload.append('changed_by', 'web');
                            var sel = Array.from(cboxWrap.querySelectorAll('input.contrib-user:checked')).map(function(x){return x.value;});
                            if (sel.length > 0) { payload.append('contributors', sel.join(',')); }
                            // Update UI Updated By cell for confirmation
                            var byCell = tdActions.parentElement.querySelector('.col-by');
                            if (byCell) {
                                // Reflect selection while preserving the original base
                                var base = initialByBase;
                                var extra = sel.length ? ('\nWith: ' + sel.join(', ')) : '';
                                byCell.innerText = base + extra;
                            }

                            // Step 1: Save item data
                            fetch('update_lcs_status.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: payload.toString()
                                })
                                .then(r => r.json())
                                .then(resp => {
                                    if (resp && resp.success) {
                                        // Step 1.5: Delete any marked media first
                                        var postDelete = Promise.resolve(true);
                                        if (toDeleteIds.length > 0) {
                                            var delParams = new URLSearchParams();
                                            delParams.append('site_id', siteId);
                                            delParams.append('lcs_id', lcsId);
                                            delParams.append('status_date', selectedDate);
                                            delParams.append('item_name', item);
                                            delParams.append('delete_media_ids', toDeleteIds.join(','));
                                            delParams.append('changed_by', 'web');
                                            postDelete = fetch('update_lcs_status.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                                body: delParams.toString()
                                            }).then(r2 => r2.json()).then(d2 => d2 && d2.success === true);
                                        }
                                        // ✅ Upload files if selected
                                        postDelete.then(function(){
                                        if (inMedia && inMedia.files.length > 0) {
                                            var uploadPromises = [];
                                            for (var i = 0; i < inMedia.files.length; i++) {
                                                var formData = new FormData();
                                                formData.append('lcs_id', lcsId);
                                                formData.append('item_name', item);
                                                formData.append('status_date', selectedDate);
                                                formData.append('media_file', inMedia.files[i]);
                                                formData.append('changed_by', 'web');

                                                uploadPromises.push(
                                                    fetch('upload_lcs_media.php', {
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
                                .catch(() => alert('Error saving changes'));
                        });
                    });
                });
            }

            // 🟢 Spare row (add new custom item)
            function wireSpareRows() {
                document.querySelectorAll('table.data-table tbody tr.spare-row').forEach(function(tr) {
                    var inItem = tr.querySelector('.spare-item-name');
                    var inMake = tr.querySelector('.spare-make');
                    var inSize = tr.querySelector('.spare-size');
                    var inStatus = tr.querySelector('.spare-status');
                    var inRemark = tr.querySelector('.spare-remark');
                    var inMedia = tr.querySelector('.spare-media');
                    var saveBtn = tr.querySelector('.save-spare');
                    var clearBtn = tr.querySelector('.clear-spare');

                    if (saveBtn) {
                        saveBtn.addEventListener('click', function() {
                            var itemName = (inItem && inItem.value || '').trim();
                            if (!itemName) {
                                alert('Please enter Item name');
                                return;
                            }

                            var payload = new URLSearchParams();
                            payload.append('site_id', siteId);
                            payload.append('lcs_id', lcsId);
                            payload.append('status_date', selectedDate);
                            payload.append('item_name', itemName);
                            payload.append('make_model', inMake.value);
                            payload.append('size_capacity', inSize.value);
                            payload.append('status', inStatus.value);
                            payload.append('remark', inRemark.value);
                            payload.append('changed_by', 'web');

                            var lcsSpareContribs = window.prompt('Contributors (comma-separated names, optional)', '');
                            if (lcsSpareContribs !== null && lcsSpareContribs.trim() !== '') {
                                payload.append('contributors', lcsSpareContribs.trim());
                            }

                            // Step 1: Save item first
                            fetch('update_lcs_status.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: payload.toString()
                                })
                                .then(r => r.json())
                                .then(resp => {
                                    if (resp && resp.success) {
                                        // Step 2: Upload media (if any)
                                        if (inMedia && inMedia.files.length > 0) {
                                            var uploadPromises = [];
                                            for (var i = 0; i < inMedia.files.length; i++) {
                                                var formData = new FormData();
                                                formData.append('lcs_id', lcsId);
                                                formData.append('item_name', itemName);
                                                formData.append('status_date', selectedDate);
                                                formData.append('media_file', inMedia.files[i]);
                                                formData.append('changed_by', 'web');

                                                uploadPromises.push(
                                                    fetch('upload_lcs_media.php', {
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
                                        alert('Failed to save new item');
                                    }
                                })
                                .catch(() => alert('Error saving new item'));
                        });
                    }

                    if (clearBtn) {
                        clearBtn.addEventListener('click', function() {
                            [inItem, inMake, inSize, inRemark].forEach(i => i && (i.value = ''));
                            if (inStatus) inStatus.selectedIndex = 0;
                            if (inMedia) inMedia.value = '';
                        });
                    }
                });
            }

            wireEditButtons();
            wireSpareRows();

            // Removed separate uploader: uploads are part of Save

            // LCS Master note media delete marking
            var delHidden = document.getElementById('deleteLcsMasterMediaIds');
            var masterDeleteIds = [];
            document.querySelectorAll('.media-container .lcs-master-del-btn').forEach(function(btn){
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
            var masterForm = document.getElementById('lcsMasterNoteForm');
            if (masterForm) {
                masterForm.addEventListener('submit', function(){
                    if (delHidden) delHidden.value = masterDeleteIds.join(',');
                    var mc = document.getElementById('lcsMasterContribs');
                    if (mc) {
                        var sel = Array.from(document.querySelectorAll('#lcsMasterContribChecks input.lcs-mn-contrib:checked')).map(function(x){return x.value;});
                        mc.value = sel.join(',');
                    }
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