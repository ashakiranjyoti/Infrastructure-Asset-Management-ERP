<?php
header('Content-Type: application/json');
include 'db_config.php';

$tubewell_id = isset($_GET['tubewell_id']) ? (int)$_GET['tubewell_id'] : 0;
$item_name = isset($_GET['item_name']) ? trim($_GET['item_name']) : '';

if ($tubewell_id <= 0 || $item_name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$sql = "SELECT item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark, status_date
        FROM status_history
        WHERE tubewell_id = ? AND item_name = ?
        ORDER BY status_date DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $tubewell_id, $item_name);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if ($row) {
    echo json_encode(['data' => $row]);
} else {
    echo json_encode(['data' => null]);
}
?>

