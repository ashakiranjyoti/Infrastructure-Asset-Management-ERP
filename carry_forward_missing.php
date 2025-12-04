<?php
header('Content-Type: application/json');
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

function read_str($k){ return isset($_POST[$k]) ? trim($_POST[$k]) : ''; }
function read_int($k){ return isset($_POST[$k]) ? (int)$_POST[$k] : 0; }

$tubewell_id = read_int('tubewell_id');
$site_id = read_int('site_id');
$status_date = read_str('status_date');
$changed_by = read_str('changed_by');

if ($tubewell_id <= 0 || $site_id <= 0 || $status_date === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Load active items
$active_items = [];
$items_res = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ORDER BY item_name ASC");
if ($items_res) {
    while ($ir = $items_res->fetch_assoc()) { $active_items[] = $ir['item_name']; }
}

// Fetch existing for selected date
$existing = [];
$ex_stmt = $conn->prepare("SELECT item_name FROM status_history WHERE tubewell_id = ? AND status_date = ?");
$ex_stmt->bind_param('is', $tubewell_id, $status_date);
$ex_stmt->execute();
$ex_rs = $ex_stmt->get_result();
while ($r = $ex_rs->fetch_assoc()) { $existing[$r['item_name']] = true; }

$inserted = 0;

// For each active item missing on the selected date, copy last known row
$last_stmt = $conn->prepare("SELECT item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark
                             FROM status_history
                             WHERE tubewell_id = ? AND item_name = ?
                             ORDER BY status_date DESC
                             LIMIT 1");

// Prepare insert, include updated_at if present
$has_updated_at_cf = false;
$col_check_cf = $conn->query("SHOW COLUMNS FROM status_history LIKE 'updated_at'");
if ($col_check_cf && $col_check_cf->num_rows > 0) { $has_updated_at_cf = true; }

if ($has_updated_at_cf) {
    $ins_stmt = $conn->prepare("INSERT INTO status_history (site_id, tubewell_id, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, status_date, updated_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
} else {
    $ins_stmt = $conn->prepare("INSERT INTO status_history (site_id, tubewell_id, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, status_date)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
}

$log_stmt = $conn->prepare("INSERT INTO status_change_log (tubewell_id, item_name, changed_by, change_type, old_value, new_value, changed_at)
                            VALUES (?, ?, ?, 'Added', ?, ?, NOW())");

foreach ($active_items as $iname) {
    if (isset($existing[$iname])) { continue; }

    $last_stmt->bind_param('is', $tubewell_id, $iname);
    $last_stmt->execute();
    $lr = $last_stmt->get_result()->fetch_assoc();
    if (!$lr) { continue; }

    $make_model = $lr['make_model'];
    $size_capacity = $lr['size_capacity'];
    $status = $lr['status'];
    $check_hmi_local = (int)$lr['check_hmi_local'];
    $check_web = (int)$lr['check_web'];
    $remark = $lr['remark'];

    $ins_stmt->bind_param('iissssiiss', $site_id, $tubewell_id, $iname, $make_model, $size_capacity, $status, $check_hmi_local, $check_web, $remark, $status_date);
    $ins_stmt->execute();

    $old_value = json_encode((object)[]);
    $new_value = json_encode([
        'item_name' => $iname,
        'make_model' => $make_model,
        'size_capacity' => $size_capacity,
        'status' => $status,
        'check_hmi_local' => $check_hmi_local,
        'check_web' => $check_web,
        'remark' => $remark
    ]);
    $log_stmt->bind_param('issss', $tubewell_id, $iname, $changed_by, $old_value, $new_value);
    $log_stmt->execute();

    $inserted++;
}

echo json_encode(['success' => true, 'inserted' => $inserted]);
?>

