<?php
session_start();
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lcs_id = (int)$_POST['lcs_id'];
    $item_name = $_POST['item_name'] ?? '';
    $changed_by = $_POST['changed_by'] ?? 'web';
    $status_date = isset($_POST['status_date']) && $_POST['status_date'] !== '' ? $_POST['status_date'] : date('Y-m-d');
    if (isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '') {
        $changed_by = $_SESSION['full_name'];
    } elseif (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
        $changed_by = $_SESSION['username'];
    }
    $file = $_FILES['media_file'] ?? null;

    if (!$lcs_id || !$item_name || !$file) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    // Check file type
    $fileType = $file['type'];
    $isImage = strpos($fileType, 'image') !== false;
    $isVideo = strpos($fileType, 'video') !== false;

    if (!$isImage && !$isVideo) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type']);
        exit;
    }

    // Folder selection
    $uploadDir = $isImage ? 'uploads/lcs/images/' : 'uploads/lcs/videos/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // Unique file name
    $uniqueName = uniqid() . '-' . basename($file['name']);
    $uploadPath = $uploadDir . $uniqueName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Ensure schema compatible with date-wise media
        $conn->query("CREATE TABLE IF NOT EXISTS lcs_media (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lcs_id INT NOT NULL,
            item_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type ENUM('image','video') NOT NULL,
            uploaded_by VARCHAR(100) NULL,
            uploaded_at DATETIME NOT NULL,
            status_date DATE NOT NULL,
            INDEX idx_lcs_item_date (lcs_id, item_name, status_date, uploaded_at)
        )");
        // Add/adjust missing columns if needed
        $c1 = $conn->query("SHOW COLUMNS FROM lcs_media LIKE 'status_date'");
        if ($c1 && $c1->num_rows === 0) {
            $conn->query("ALTER TABLE lcs_media ADD COLUMN status_date DATE NULL");
            $conn->query("UPDATE lcs_media SET status_date = DATE(uploaded_at) WHERE status_date IS NULL");
            $conn->query("ALTER TABLE lcs_media MODIFY COLUMN status_date DATE NOT NULL");
        }
        $c2 = $conn->query("SHOW COLUMNS FROM lcs_media LIKE 'file_path'");
        if ($c2 && $c2->num_rows === 0) {
            $conn->query("ALTER TABLE lcs_media ADD COLUMN file_path VARCHAR(500) NULL");
        }
        $c3 = $conn->query("SHOW COLUMNS FROM lcs_media LIKE 'file_type'");
        if ($c3 && $c3->num_rows === 0) {
            $conn->query("ALTER TABLE lcs_media ADD COLUMN file_type ENUM('image','video') NULL");
        }
        $c4 = $conn->query("SHOW COLUMNS FROM lcs_media LIKE 'uploaded_at'");
        if ($c4 && $c4->num_rows === 0) {
            $conn->query("ALTER TABLE lcs_media ADD COLUMN uploaded_at DATETIME NULL");
        }

        $type = $isImage ? 'image' : 'video';
        $stmt = $conn->prepare("INSERT INTO lcs_media (lcs_id, item_name, file_path, file_type, uploaded_by, uploaded_at, status_date)
                                VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        if ($stmt) {
            $stmt->bind_param('isssss', $lcs_id, $item_name, $uploadPath, $type, $changed_by, $status_date);
            if ($stmt->execute()) {
                $media_id = $conn->insert_id;
                // Audit log
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
                if ($log = $conn->prepare("INSERT INTO lcs_media_change_log (lcs_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, ?, 'uploaded', ?, ?, ?, ?, NOW())")) {
                    $log->bind_param('isissss', $lcs_id, $item_name, $media_id, $uploadPath, $type, $status_date, $changed_by);
                    $log->execute();
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'file' => $uniqueName, 'type' => $type]);
                return;
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'DB error']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'File move failed']);
    }
}
?>
