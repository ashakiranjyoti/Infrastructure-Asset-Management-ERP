<?php
header('Content-Type: application/json');
include 'db_config.php';
session_start();

function read_str($k){ return isset($_POST[$k]) ? trim($_POST[$k]) : ''; }
function read_int($k){ return isset($_POST[$k]) ? (int)$_POST[$k] : 0; }

$site_id = read_int('site_id');
$lcs_id = read_int('lcs_id');
$status_date = read_str('status_date');
$item_name = read_str('item_name');
$make_model = read_str('make_model');
$size_capacity = read_str('size_capacity');
$status = read_str('status');
$check_hmi_local = read_int('check_hmi_local');
$check_web = read_int('check_web');
$remark = read_str('remark');

if (isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '') {
    $changed_by = $_SESSION['full_name'];
} else if (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
    $changed_by = $_SESSION['username'];
} else {
    $changed_by = read_str('changed_by') ?: 'web';
}

if ($lcs_id <= 0 || $site_id <= 0 || $status_date === '' || $item_name === '') {
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

// Ensure lock table exists and block if locked
$conn->query("CREATE TABLE IF NOT EXISTS lcs_status_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lcs_id INT NOT NULL,
    status_date DATE NOT NULL,
    locked_by VARCHAR(100) NULL,
    locked_at DATETIME NOT NULL,
    UNIQUE KEY uniq_lock (lcs_id, status_date)
)");
$lk = $conn->prepare('SELECT 1 FROM lcs_status_locks WHERE lcs_id = ? AND status_date = ?');
$lk->bind_param('is', $lcs_id, $status_date);
$lk->execute();
$is_locked = $lk->get_result()->fetch_assoc();
if ($is_locked) {
    http_response_code(423);
    echo json_encode(['error' => 'Editing is locked for this date.']);
    exit;
}

// If request is only to delete media items for this LCS row, process and return
if (isset($_POST['delete_media_ids']) && trim($_POST['delete_media_ids']) !== '') {
    $ids_raw = trim($_POST['delete_media_ids']);
    $id_list = array_values(array_filter(array_map(function($v){ return (int)trim($v); }, explode(',', $ids_raw)), function($v){ return $v > 0; }));
    if (empty($id_list)) { echo json_encode(['success' => true, 'deleted' => 0]); exit; }

    // Ensure audit table exists
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

    $actor = $changed_by;
    // Fetch matching media rows constrained to this lcs_id, item_name, and status_date
    $placeholders = implode(',', array_fill(0, count($id_list), '?'));
    $sql = "SELECT id, file_path, file_type, status_date FROM lcs_media WHERE id IN ($placeholders) AND lcs_id = ? AND item_name = ? AND status_date = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Build dynamic bind
        $types = str_repeat('i', count($id_list)) . 'iss';
        $params = $id_list;
        $params[] = $lcs_id;
        $params[] = $item_name;
        $params[] = $status_date;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($m = $res->fetch_assoc()) { $rows[] = $m; }
        foreach ($rows as $m) {
            if ($log = $conn->prepare("INSERT INTO lcs_media_change_log (lcs_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, ?, 'deleted', ?, ?, ?, ?, NOW())")) {
                $log->bind_param('isissss', $lcs_id, $item_name, $m['id'], $m['file_path'], $m['file_type'], $m['status_date'], $actor);
                $log->execute();
            }
            // Delete DB row
            if ($del = $conn->prepare('DELETE FROM lcs_media WHERE id = ? AND lcs_id = ?')) {
                $del->bind_param('ii', $m['id'], $lcs_id);
                $del->execute();
            }
            // Delete file
            if (!empty($m['file_path']) && file_exists($m['file_path'])) { @unlink($m['file_path']); }
        }
    }
    echo json_encode(['success' => true, 'deleted' => count($id_list)]);
    exit;
}

// Ensure history table exists
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

// Ensure the unique key exists even if the table was created earlier without it
// This makes ON DUPLICATE KEY UPDATE work reliably to avoid duplicate rows
$chkIdx = $conn->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'lcs_status_history' AND index_name = 'uniq_row' LIMIT 1");
if ($chkIdx) {
    $chkIdx->execute();
    $hasIdx = (bool)$chkIdx->get_result()->fetch_row();
    if (!$hasIdx) {
        // Attempt to add the unique index. If it already exists due to race, ignore the error
        @$conn->query("ALTER TABLE lcs_status_history ADD UNIQUE KEY uniq_row (lcs_id, status_date, item_name)");
    }
}
// (success response will be sent at the very end after all DB writes)
// Fetch existing to compute change type
$fetch = $conn->prepare("SELECT 1 FROM lcs_status_history WHERE lcs_id = ? AND status_date = ? AND item_name = ?");
$fetch->bind_param('iss', $lcs_id, $status_date, $item_name);
$fetch->execute();
$exists = (bool)$fetch->get_result()->fetch_assoc();
$change_type = $exists ? 'Updated' : 'Added';

// Upsert
$sql = "INSERT INTO lcs_status_history (site_id, lcs_id, status_date, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, created_by, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            make_model = VALUES(make_model),
            size_capacity = VALUES(size_capacity),
            status = VALUES(status),
            check_hmi_local = VALUES(check_hmi_local),
            check_web = VALUES(check_web),
            remark = VALUES(remark),
            created_by = VALUES(created_by),
            updated_at = VALUES(updated_at)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iisssssiiss', $site_id, $lcs_id, $status_date, $item_name, $make_model, $size_capacity, $status, $check_hmi_local, $check_web, $remark, $changed_by);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error: '.$conn->error]);
    exit;
}

// Cleanup any pre-existing duplicates for the same (lcs_id, status_date, item_name), keep the latest id
$cleanup = $conn->prepare(
    "DELETE t FROM lcs_status_history t
      JOIN (
        SELECT lcs_id, status_date, item_name, MAX(id) AS keep_id
        FROM lcs_status_history
        WHERE lcs_id = ? AND status_date = ? AND item_name = ?
        GROUP BY lcs_id, status_date, item_name
      ) k
      ON t.lcs_id = k.lcs_id AND t.status_date = k.status_date AND t.item_name = k.item_name
     WHERE t.id <> k.keep_id"
);
if ($cleanup) { $cleanup->bind_param('iss', $lcs_id, $status_date, $item_name); $cleanup->execute(); }

// Ensure contributor tracking tables exist (shared with tubewell flow)
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

// Insert primary update row
if ($lcs_id > 0) {
    if ($st_up = $conn->prepare("INSERT INTO updates (entity_type, entity_id, item_name, status_date, updated_by, updated_at, change_summary) VALUES ('lcs', ?, ?, ?, ?, NOW(), NULL)")) {
        $st_up->bind_param('isss', $lcs_id, $item_name, $status_date, $changed_by);
        $st_up->execute();
        $update_id = $conn->insert_id;
        // Optional contributors
        if (isset($_POST['contributors'])) {
            $raw = trim((string)$_POST['contributors']);
            if ($raw !== '') {
                $names = array_filter(array_map(function($v){ return trim($v); }, explode(',', $raw)), function($v){ return $v !== ''; });
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
}

// All operations completed successfully
echo json_encode(['success' => true, 'change_type' => $change_type]);
exit;
