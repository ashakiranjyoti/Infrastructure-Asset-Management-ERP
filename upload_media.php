<?php
session_start();
include 'db_config.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tubewell_id = (int)$_POST['tubewell_id'];
    $item_name = $_POST['item_name'] ?? '';
    $changed_by = $_POST['changed_by'] ?? 'web';
    $status_date = isset($_POST['status_date']) && $_POST['status_date'] !== '' ? $_POST['status_date'] : date('Y-m-d');
    // Prefer logged-in user's full name from session; fallback to posted changed_by or 'web'
    if (isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '') {
        $changed_by = $_SESSION['full_name'];
    } elseif (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
        $changed_by = $_SESSION['username'];
    }

    if (!empty($_FILES['media_file']['name'])) {
        $file = $_FILES['media_file'];
        $fileName = basename($file['name']);
        $fileTmp = $file['tmp_name'];
        $fileType = $file['type'];

        $uploadDir = 'uploads/';
        $isImage = strpos($fileType, 'image') !== false;
        $isVideo = strpos($fileType, 'video') !== false;

        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $subDir = $isImage ? 'images/' : 'videos/';
        if (!file_exists($uploadDir . $subDir)) mkdir($uploadDir . $subDir, 0777, true);

        $uniqueName = uniqid() . '-' . $fileName;
        $filePath = $uploadDir . $subDir . $uniqueName;

        if (move_uploaded_file($fileTmp, $filePath)) {
            $ftype = $isImage ? 'image' : 'video';
            // Attempt to insert with uploaded_at = NOW() and status_date provided
            $stmt = $conn->prepare("INSERT INTO media_uploads (tubewell_id, item_name, file_path, file_type, uploaded_by, uploaded_at, status_date) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
            if ($stmt) {
                $stmt->bind_param('isssss', $tubewell_id, $item_name, $filePath, $ftype, $changed_by, $status_date);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $media_id = $conn->insert_id;
                    // Audit log for upload
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
                    if ($log = $conn->prepare("INSERT INTO media_change_log (tubewell_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, ?, ?, 'uploaded', ?, ?, ?, ?, NOW())")) {
                        $log->bind_param('isissss', $tubewell_id, $item_name, $media_id, $filePath, $ftype, $status_date, $changed_by);
                        $log->execute();
                    }
                }
            }
        }
    } else {
        // Even if no media uploaded, still save other changes
        $response['success'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
