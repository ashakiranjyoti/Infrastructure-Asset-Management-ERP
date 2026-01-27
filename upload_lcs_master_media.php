<?php
session_start();
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lcs_id = isset($_POST['lcs_id']) ? (int)$_POST['lcs_id'] : 0;
    $status_date = isset($_POST['status_date']) && $_POST['status_date'] !== '' ? $_POST['status_date'] : date('Y-m-d');
    $changed_by = isset($_POST['changed_by']) ? $_POST['changed_by'] : 'web';
    if (isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '') {
        $changed_by = $_SESSION['full_name'];
    } elseif (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
        $changed_by = $_SESSION['username'];
    }
    $file = $_FILES['media_file'] ?? null;

    header('Content-Type: application/json');

    if (!$lcs_id || !$file) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    // Validate file type
    $fileType = $file['type'] ?? '';
    $isImage = strpos($fileType, 'image') !== false;
    $isVideo = strpos($fileType, 'video') !== false;
    if (!$isImage && !$isVideo) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }

    // Ensure master media table exists
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

    // Ensure audit log table exists
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

    // Folder (separate for master notes)
    $uploadDir = 'uploads/lcs_master_note/';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }

    $uniqueName = uniqid() . '-' . basename($file['name']);
    $uploadPath = $uploadDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        echo json_encode(['success' => false, 'error' => 'File move failed']);
        exit;
    }
    $type = $isImage ? 'image' : 'video';
    if ($stmt = $conn->prepare("INSERT INTO lcs_master_media (lcs_id, file_path, file_type, uploaded_by, uploaded_at, status_date) VALUES (?, ?, ?, ?, NOW(), ?)")) {
        $stmt->bind_param('issss', $lcs_id, $uploadPath, $type, $changed_by, $status_date);
        if ($stmt->execute()) {
            $media_id = $conn->insert_id;
            if ($log = $conn->prepare("INSERT INTO lcs_master_media_change_log (lcs_id, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, 'uploaded', ?, ?, ?, ?, NOW())")) {
                $log->bind_param('iissss', $lcs_id, $media_id, $uploadPath, $type, $status_date, $changed_by);
                $log->execute();
            }
            echo json_encode(['success' => true, 'file' => $uniqueName, 'type' => $type]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'DB error']);
}
