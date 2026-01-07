<?php
header('Content-Type: application/json');
include 'db_config.php';
session_start();

// Expected POST: tubewell_id, site_id, status_date, item_name, make_model, size_capacity, status, remark, changed_by
// Optional POST: contributors (comma-separated list of helper user names)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Helper to read scalar string
function read_str($key) { return isset($_POST[$key]) ? trim($_POST[$key]) : ''; }
function read_int($key) { return isset($_POST[$key]) ? (int)$_POST[$key] : 0; }
function read_bool($key) { return isset($_POST[$key]) && (int)$_POST[$key] === 1 ? 1 : 0; }
function read_tri($state_key, $legacy_key) {
    if (isset($_POST[$state_key]) && $_POST[$state_key] !== '') {
        $v = (int)$_POST[$state_key];
        if ($v === 1 || $v === 2) { return $v; }
        return 0; // treat others as none
    }
    if (isset($_POST[$legacy_key])) {
        return ((int)$_POST[$legacy_key] === 1) ? 1 : 2; // legacy 1->OK, 0->Not OK
    }
    return 0; // default none
}

$tubewell_id = read_int('tubewell_id');
$site_id = read_int('site_id');
$status_date = read_str('status_date');
$item_name = read_str('item_name');
$make_model = read_str('make_model');
$size_capacity = read_str('size_capacity');
$status = read_str('status');
$check_hmi_local = read_tri('check_hmi_local_state', 'check_hmi_local');
$check_web = read_tri('check_web_state', 'check_web');
$remark = read_str('remark');
$changed_by = '';
// Prefer logged-in user's full name from session; fallback to posted changed_by or 'web'
if (isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '') {
    $changed_by = $_SESSION['full_name'];
} elseif (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
    $changed_by = $_SESSION['username'];
} else {
    $changed_by = read_str('changed_by') ?: 'web';
}

if ($tubewell_id <= 0 || $site_id <= 0 || $status_date === '' || $item_name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Enforce that only today's date can be updated
$today = date('Y-m-d');
if ($status_date !== $today) {
    http_response_code(400);
    echo json_encode(['error' => 'Only today\'s date can be updated.']);
    exit;
}

// Block edits if the date is locked for this tubewell
// Ensure lock table exists (lightweight, safe if already exists)
$conn->query("CREATE TABLE IF NOT EXISTS status_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tubewell_id INT NOT NULL,
    status_date DATE NOT NULL,
    locked_by VARCHAR(100) NULL,
    locked_at DATETIME NOT NULL,
    UNIQUE KEY uniq_lock (tubewell_id, status_date)
)");
$lk = $conn->prepare('SELECT 1 FROM status_locks WHERE tubewell_id = ? AND status_date = ?');
$lk->bind_param('is', $tubewell_id, $status_date);
$lk->execute();
$is_locked = $lk->get_result()->fetch_assoc();
if ($is_locked) {
    http_response_code(423); // Locked
    echo json_encode(['error' => 'Editing is locked for this date.']);
    exit;
}

// If this request is meant to delete media, process and return early
if (isset($_POST['delete_media_ids']) && trim($_POST['delete_media_ids']) !== '') {
    $ids_raw = trim($_POST['delete_media_ids']);
    // Sanitize into integer array
    $id_list = array_values(array_filter(array_map(function($v){ return (int)trim($v); }, explode(',', $ids_raw)), function($v){ return $v > 0; }));
    if (empty($id_list)) {
        echo json_encode(['success' => true]);
        exit;
    }
    // Ensure audit table exists
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
    // Resolve actor
    $actor = $changed_by;
    // Fetch matching media rows constrained by tubewell, item, date, and IDs
    $in_placeholders = implode(',', array_fill(0, count($id_list), '?'));
    $types = str_repeat('i', count($id_list) + 1) . 's'; // tubewell_id ints..., plus status_date string at end? We also need item_name, so adjust
    // Build prepared statement dynamically
    $params = [];
    $params_types = '';
    // id list types
    $params_types .= str_repeat('i', count($id_list));
    foreach ($id_list as $iid) { $params[] = $iid; }
    // tubewell_id
    $params_types .= 'i';
    $params[] = $tubewell_id;
    // item_name
    $params_types .= 's';
    $params[] = $item_name;
    // status_date
    $params_types .= 's';
    $params[] = $status_date;
    $sql = "SELECT id, file_path, file_type, status_date FROM media_uploads WHERE id IN ($in_placeholders) AND tubewell_id = ? AND item_name = ? AND status_date = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($params_types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($m = $res->fetch_assoc()) { $rows[] = $m; }
        // Log and delete
        foreach ($rows as $m) {
            if ($log = $conn->prepare("INSERT INTO media_change_log (tubewell_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, ?, 'deleted', ?, ?, ?, ?, NOW())")) {
                $log->bind_param('isissss', $tubewell_id, $item_name, $m['id'], $m['file_path'], $m['file_type'], $m['status_date'], $actor);
                $log->execute();
            }
            // Delete DB row
            $del = $conn->prepare('DELETE FROM media_uploads WHERE id = ? AND tubewell_id = ?');
            if ($del) { $del->bind_param('ii', $m['id'], $tubewell_id); $del->execute(); }
            // Delete file
            if (!empty($m['file_path']) && file_exists($m['file_path'])) { @unlink($m['file_path']); }
        }
    }
    echo json_encode(['success' => true, 'deleted' => count($id_list)]);
    exit;
}

// Fetch existing row to compute diff
$fetch_sql = "SELECT item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark
              FROM status_history
              WHERE tubewell_id = ? AND status_date = ? AND item_name = ?";
$fetch_stmt = $conn->prepare($fetch_sql);
$fetch_stmt->bind_param('iss', $tubewell_id, $status_date, $item_name);
$fetch_stmt->execute();
$existing = $fetch_stmt->get_result()->fetch_assoc();

$change_type = $existing ? 'Updated' : 'Added';
$old_value = $existing ? json_encode($existing) : json_encode((object)[]);

// Upsert: delete then insert
$del_sql = "DELETE FROM status_history WHERE tubewell_id = ? AND status_date = ? AND item_name = ?";
$del_stmt = $conn->prepare($del_sql);
$del_stmt->bind_param('iss', $tubewell_id, $status_date, $item_name);
$del_stmt->execute();

// Ensure updated_at column exists; if not, create it
$has_updated_at = false;
$col_check = $conn->query("SHOW COLUMNS FROM status_history LIKE 'updated_at'");
if ($col_check && $col_check->num_rows > 0) {
    $has_updated_at = true;
} else {
    $conn->query("ALTER TABLE status_history ADD COLUMN updated_at DATETIME NULL");
    $col_check2 = $conn->query("SHOW COLUMNS FROM status_history LIKE 'updated_at'");
    if ($col_check2 && $col_check2->num_rows > 0) { $has_updated_at = true; }
}

// Detect created_by column presence
$has_created_by = false;
$col_check_cb = $conn->query("SHOW COLUMNS FROM status_history LIKE 'created_by'");
if ($col_check_cb && $col_check_cb->num_rows > 0) { $has_created_by = true; }

if ($has_updated_at) {
    // Insert and preserve the editor's name in created_by and set updated_at to NOW()
    if ($has_created_by) {
        $ins_sql = "INSERT INTO status_history (site_id, tubewell_id, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, status_date, created_by, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $ins_stmt = $conn->prepare($ins_sql);
        if ($ins_stmt) {
            // types: i i s s s s i i s s s  => 'iissssiisss'
            $ins_stmt->bind_param('iissssiisss', $site_id, $tubewell_id, $item_name, $make_model, $size_capacity, $status, $check_hmi_local, $check_web, $remark, $status_date, $changed_by);
            $ins_stmt->execute();
        }
    } else {
        // created_by not present: insert without it
        $ins_sql2 = "INSERT INTO status_history (site_id, tubewell_id, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, status_date, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $ins_stmt2 = $conn->prepare($ins_sql2);
        if ($ins_stmt2) {
            $ins_stmt2->bind_param('iissssiiss', $site_id, $tubewell_id, $item_name, $make_model, $size_capacity, $status, $check_hmi_local, $check_web, $remark, $status_date);
            $ins_stmt2->execute();
        }
    }
} else {
    // Table doesn't have updated_at: include created_by
    if ($has_created_by) {
        $ins_sql = "INSERT INTO status_history (site_id, tubewell_id, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, status_date, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ins_stmt = $conn->prepare($ins_sql);
        if ($ins_stmt) {
            $ins_stmt->bind_param('iissssiisss', $site_id, $tubewell_id, $item_name, $make_model, $size_capacity, $status, $check_hmi_local, $check_web, $remark, $status_date, $changed_by);
            $ins_stmt->execute();
        }
    } else {
        // If created_by is not present, fallback to insert without it
        $ins_sql2 = "INSERT INTO status_history (site_id, tubewell_id, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, status_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $ins_stmt2 = $conn->prepare($ins_sql2);
        if ($ins_stmt2) {
            $ins_stmt2->bind_param('iissssiiss', $site_id, $tubewell_id, $item_name, $make_model, $size_capacity, $status, $check_hmi_local, $check_web, $remark, $status_date);
            $ins_stmt2->execute();
        }
    }
}

// Prepare new value for log
$new_value = json_encode([
    'item_name' => $item_name,
    'make_model' => $make_model,
    'size_capacity' => $size_capacity,
    'status' => $status,
    'check_hmi_local' => $check_hmi_local,
    'check_web' => $check_web,
    'remark' => $remark
]);

// Insert into status_change_log
$log_sql = "INSERT INTO status_change_log (tubewell_id, item_name, changed_by, change_type, old_value, new_value, changed_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
$log_stmt = $conn->prepare($log_sql);
$log_stmt->bind_param('isssss', $tubewell_id, $item_name, $changed_by, $change_type, $old_value, $new_value);
$log_stmt->execute();

// Ensure contributor tracking tables exist (lightweight, safe if already exists)
// Primary update record per save
$conn->query("CREATE TABLE IF NOT EXISTS updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    status_date DATE NOT NULL,
    updated_by VARCHAR(100) NOT NULL,
    updated_at DATETIME NOT NULL,
    change_summary TEXT NULL,
    INDEX idx_updates_entity (entity_type, entity_id, status_date, updated_at),
    INDEX idx_updates_by (updated_by, status_date)
)");

// Many-to-many helpers for an update
$conn->query("CREATE TABLE IF NOT EXISTS update_contributors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    update_id INT NOT NULL,
    contributor_name VARCHAR(100) NOT NULL,
    role VARCHAR(50) NULL,
    added_at DATETIME NOT NULL,
    UNIQUE KEY uniq_update_contributor (update_id, contributor_name),
    INDEX idx_contributor_name (contributor_name),
    CONSTRAINT fk_uc_update FOREIGN KEY (update_id) REFERENCES updates(id) ON DELETE CASCADE
)");

// Insert a new update row for this save
$upd_stmt = $conn->prepare("INSERT INTO updates (entity_type, entity_id, item_name, status_date, updated_by, updated_at, change_summary) VALUES ('tubewell', ?, ?, ?, ?, NOW(), ?)");
if ($upd_stmt) {
    // Optional short summary
    $summary = null;
    $upd_stmt->bind_param('issss', $tubewell_id, $item_name, $status_date, $changed_by, $summary);
    $upd_stmt->execute();
    $update_id = $conn->insert_id;

    // Parse optional contributors from POST (comma-separated names)
    if (isset($_POST['contributors'])) {
        $raw = trim((string)$_POST['contributors']);
        if ($raw !== '') {
            $names = array_filter(array_map(function($v){ return trim($v); }, explode(',', $raw)), function($v){ return $v !== ''; });
            // Deduplicate and avoid duplicating the primary editor
            $seen = [];
            foreach ($names as $nm) {
                if (strcasecmp($nm, $changed_by) === 0) { continue; }
                $key = mb_strtolower($nm);
                if (isset($seen[$key])) { continue; }
                $seen[$key] = true;
                if ($uc = $conn->prepare("INSERT IGNORE INTO update_contributors (update_id, contributor_name, role, added_at) VALUES (?, ?, NULL, NOW())")) {
                    $uc->bind_param('is', $update_id, $nm);
                    $uc->execute();
                }
            }
        }
    }
}

echo json_encode(['success' => true, 'change_type' => $change_type]);
?>

