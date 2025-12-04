<?php
session_start();
include 'db_config.php';

header('Content-Type: application/json');
$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tubewell_id = isset($_POST['tubewell_id']) ? (int)$_POST['tubewell_id'] : 0;
    $status_date = isset($_POST['status_date']) && $_POST['status_date'] !== '' ? $_POST['status_date'] : date('Y-m-d');
    $actor = 'web';
    if (isset($_SESSION['full_name']) && $_SESSION['full_name'] !== '') {
        $actor = $_SESSION['full_name'];
    } elseif (isset($_SESSION['username']) && $_SESSION['username'] !== '') {
        $actor = $_SESSION['username'];
    }

    if ($tubewell_id <= 0) {
        echo json_encode($response);
        exit;
    }

    // Ensure table exists
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

    if (!empty($_FILES['media_file']['name'])) {
        $file = $_FILES['media_file'];
        $fileName = basename($file['name']);
        $fileTmp = $file['tmp_name'];
        $fileType = isset($file['type']) ? $file['type'] : '';

        $baseDir = 'uploads/master_note/';
        $isImage = $fileType && strpos($fileType, 'image') !== false;
        $isVideo = $fileType && strpos($fileType, 'video') !== false;
        if (!$isImage && !$isVideo) {
            // Fallback detection by extension
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $imgExt = ['jpg','jpeg','png','gif','webp','bmp'];
            $vidExt = ['mp4','mov','avi','mkv','webm'];
            if (in_array($ext, $imgExt)) $isImage = true; else if (in_array($ext, $vidExt)) $isVideo = true;
        }
        if (!$isImage && !$isVideo) {
            $response['error'] = 'Unsupported file type';
            echo json_encode($response); exit;
        }

        if (!file_exists($baseDir)) { @mkdir($baseDir, 0777, true); }
        $subDir = $isImage ? 'images/' : 'videos/';
        if (!file_exists($baseDir . $subDir)) { @mkdir($baseDir . $subDir, 0777, true); }

        $uniqueName = uniqid() . '-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);
        $filePath = $baseDir . $subDir . $uniqueName;

        if (move_uploaded_file($fileTmp, $filePath)) {
            $ftype = $isImage ? 'image' : 'video';
            if ($stmt = $conn->prepare("INSERT INTO tubewell_master_media (tubewell_id, status_date, file_path, file_type, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())")) {
                $stmt->bind_param('issss', $tubewell_id, $status_date, $filePath, $ftype, $actor);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $media_id = $conn->insert_id;
                    // Ensure media_change_log exists and log upload with item_name marker for master note
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
                    if ($log = $conn->prepare("INSERT INTO media_change_log (tubewell_id, item_name, media_id, action, file_path, file_type, status_date, actor, action_at) VALUES (?, '__MASTER_NOTE__', ?, 'uploaded', ?, ?, ?, ?, NOW())")) {
                        $log->bind_param('iissss', $tubewell_id, $media_id, $filePath, $ftype, $status_date, $actor);
                        $log->execute();
                    }
                }
                else { $response['error'] = 'DB insert failed for master media'; }
            }
            else { $response['error'] = 'Prepare failed for master media insert'; }
        }
        else { $response['error'] = 'Failed to move uploaded file'; }
    } else {
        $response['success'] = true; // No file, but don't error out
    }
}

echo json_encode($response);
