<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Validate tubewell ID
if (!isset($_GET['tubewell_id'])) {
    die('Invalid request. tubewell_id is required.');
}
$tubewell_id = (int)$_GET['tubewell_id'];

// Fetch existing tubewell info
$tw = $conn->prepare("SELECT t.*, s.site_name FROM tubewells t JOIN sites s ON s.id = t.site_id WHERE t.id = ?");
if (!$tw) {
    die('Database error: ' . $conn->error);
}
$tw->bind_param('i', $tubewell_id);
$tw->execute();
$tubewell = $tw->get_result()->fetch_assoc();
if (!$tubewell) {
    die('Tubewell not found.');
}
$tw->close();

$site_id = (int)$tubewell['site_id'];
$error = '';
$success = '';

// Handle media deletion (separate form submission)
if (isset($_GET['delete_media'])) {
    $media_id = (int)$_GET['media_id'];
    
    // Get file path before deletion
    $media_stmt = $conn->prepare("SELECT file_path FROM tubewell_media WHERE id = ? AND tubewell_id = ?");
    $media_stmt->bind_param("ii", $media_id, $tubewell_id);
    $media_stmt->execute();
    $media_result = $media_stmt->get_result();
    $media_file = $media_result->fetch_assoc();
    
    if ($media_file) {
        // Delete from database
        $delete_stmt = $conn->prepare("DELETE FROM tubewell_media WHERE id = ? AND tubewell_id = ?");
        $delete_stmt->bind_param("ii", $media_id, $tubewell_id);
        
        if ($delete_stmt->execute()) {
            // Delete physical file
            if (file_exists($media_file['file_path'])) {
                unlink($media_file['file_path']);
            }
            $success = "✅ Media file deleted successfully!";
        } else {
            $error = "❌ Error deleting media file.";
        }
        $delete_stmt->close();
    }
    $media_stmt->close();
}

// Handle tubewell update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zone_name = trim($_POST['zone_name'] ?? '');
    $tubewell_name = trim($_POST['tubewell_name'] ?? '');
    $tw_address = trim($_POST['tw_address'] ?? '');
    $incharge_name = trim($_POST['incharge_name'] ?? '');
    $incharge_contact = trim($_POST['incharge_contact'] ?? '');
    $sim_no = trim($_POST['sim_no'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $installation_date = trim($_POST['installation_date'] ?? '');

    // ✅ Validation
    if ($zone_name === '' || $tubewell_name === '') {
        $error = "⚠️ Zone Name and Tubewell Name are required.";
    } elseif (!empty($incharge_contact) && !preg_match('/^[0-9]{10,15}$/', $incharge_contact)) {
        $error = "⚠️ Incharge contact must be numeric (10–15 digits).";
    } elseif (!empty($latitude) && !is_numeric($latitude)) {
        $error = "⚠️ Latitude must be a valid number.";
    } elseif (!empty($longitude) && !is_numeric($longitude)) {
        $error = "⚠️ Longitude must be a valid number.";
    } else {
        // 🔍 Check for duplicate tubewell name (excluding current)
        $check = $conn->prepare("SELECT COUNT(*) FROM tubewells WHERE LOWER(tubewell_name) = LOWER(?) AND site_id = ? AND id != ?");
        $check->bind_param("sii", $tubewell_name, $site_id, $tubewell_id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            $error = "⚠️ Another tubewell with this name already exists for this site.";
        } else {
            // Handle new file uploads
            $uploaded_files = [];
            if (!empty($_FILES['media_files']['name'][0])) {
                $upload_dir = 'uploads/tubewells/';
                
                // Create directory if not exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['media_files']['name'] as $key => $name) {
                    if ($_FILES['media_files']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['media_files']['tmp_name'][$key];
                        $file_size = $_FILES['media_files']['size'][$key];
                        $file_type = $_FILES['media_files']['type'][$key];
                        
                        // Validate file type
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi', 'video/mov'];
                        if (!in_array($file_type, $allowed_types)) {
                            $error = "⚠️ Invalid file type: " . $name . ". Allowed types: JPEG, PNG, GIF, MP4, AVI, MOV";
                            continue;
                        }
                        
                        // Validate file size (max 50MB)
                        if ($file_size > 50 * 1024 * 1024) {
                            $error = "⚠️ File too large: " . $name . ". Maximum size is 50MB";
                            continue;
                        }
                        
                        // Generate unique filename
                        $file_ext = pathinfo($name, PATHINFO_EXTENSION);
                        $file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $tubewell_name) . '.' . $file_ext;
                        $file_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            $uploaded_files[] = [
                                'name' => $name,
                                'path' => $file_path,
                                'type' => strpos($file_type, 'image/') === 0 ? 'image' : 'video'
                            ];
                        } else {
                            $error = "⚠️ Failed to upload file: " . $name;
                        }
                    }
                }
            }

            if (empty($error)) {
                // ✅ Update query
                $sql = "UPDATE tubewells 
                        SET zone_name = ?, tubewell_name = ?, tw_address = ?, 
                            incharge_name = ?, incharge_contact = ?, sim_no = ?, 
                            latitude = ?, longitude = ?, installation_date = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param('ssssssddsi', $zone_name, $tubewell_name, $tw_address, $incharge_name, $incharge_contact, $sim_no, $latitude, $longitude, $installation_date, $tubewell_id);
                    if ($stmt->execute()) {
                        // Insert new media files into database
                        if (!empty($uploaded_files)) {
                            $media_sql = "INSERT INTO tubewell_media (tubewell_id, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)";
                            $media_stmt = $conn->prepare($media_sql);
                            $created_by = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'system');
                            
                            foreach ($uploaded_files as $file) {
                                $media_stmt->bind_param("issss", $tubewell_id, $file['name'], $file['path'], $file['type'], $created_by);
                                $media_stmt->execute();
                            }
                            $media_stmt->close();
                        }
                        
                        header('Location: view_site.php?site_id=' . $site_id . '&message=updated');
                        exit();
                    } else {
                        $error = "❌ Error updating tubewell: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Fetch existing media files
$media_sql = "SELECT * FROM tubewell_media WHERE tubewell_id = ? ORDER BY uploaded_at DESC";
$media_stmt = $conn->prepare($media_sql);
$media_stmt->bind_param("i", $tubewell_id);
$media_stmt->execute();
$media_files = $media_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tubewell - <?php echo htmlspecialchars($tubewell['tubewell_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .alert {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .fade-out { transition: opacity 1s ease-out; }
        
        /* Media Gallery Styles */
        .media-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }
        
        .media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .media-item:hover {
            transform: scale(1.05);
        }
        
        .media-thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .video-thumbnail {
            position: relative;
        }
        
        .video-thumbnail::after {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.7);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }
        
        .delete-btn:hover {
            background: #cc0000;
        }
        
        .file-upload-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            margin-top: 1rem;
            background: white;
            transition: all 0.3s ease;
        }
        
        .file-upload-container:hover {
            border-color: #4CAF50;
            background: #f0fff0;
        }
        
        .file-upload-container.dragover {
            border-color: #4CAF50;
            background: #e8f5e8;
        }
        
        .file-input {
            display: none;
        }
        
        .upload-btn {
            background: #4CAF50;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 0.5rem;
        }
        
        .upload-btn:hover {
            background: #45a049;
        }
        
        .file-list {
            margin-top: 1rem;
            text-align: left;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 10px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .file-item .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 2px 6px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .file-item .remove-btn:hover {
            background: #cc0000;
        }
        
        .media-empty {
            text-align: center;
            color: #718096;
            padding: 2rem;
            font-style: italic;
        }
        
        .section-title {
            color: #2d3748;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .media-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
<?php include('header.php'); ?>
<div class="container">
    <div class="card">
        <div class="progress-steps">
            <div class="step">Site Details</div>
            <div class="step active">Edit Tubewell</div>
            <div class="step">Configure Parameters</div>
        </div>

        <div style="text-align:center; margin-bottom:2rem;">
            <h2 style="color:#2d3748;">Edit Tubewell</h2>
            <p style="color:#718096;">Site: <strong><?php echo htmlspecialchars($tubewell['site_name']); ?></strong></p>
        </div>

        <?php if (!empty($_GET['message']) && $_GET['message'] === 'updated'): ?>
            <div class="alert alert-success fade-out">✅ Tubewell updated successfully!</div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success fade-out"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error fade-out"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="tubewell-form" enctype="multipart/form-data">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">☯ Zone Name *</label>
                    <input type="text" name="zone_name" class="form-control" required value="<?php echo htmlspecialchars($tubewell['zone_name']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">💧 Tubewell Name *</label>
                    <input type="text" name="tubewell_name" class="form-control" required value="<?php echo htmlspecialchars($tubewell['tubewell_name']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">👤 Incharge Name</label>
                    <input type="text" name="incharge_name" class="form-control" value="<?php echo htmlspecialchars($tubewell['incharge_name']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">📞 Incharge Contact</label>
                    <input type="text" name="incharge_contact" class="form-control" value="<?php echo htmlspecialchars($tubewell['incharge_contact']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">📍 Tubewell Address</label>
                <textarea name="tw_address" class="form-control"><?php echo htmlspecialchars($tubewell['tw_address']); ?></textarea>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:2rem;">
                <div class="form-group">
                    <label class="form-label">📱 SIM Number</label>
                    <input type="text" name="sim_no" class="form-control" value="<?php echo htmlspecialchars($tubewell['sim_no']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">🌐 Latitude</label>
                    <input type="text" name="latitude" class="form-control" value="<?php echo htmlspecialchars($tubewell['latitude']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">🌐 Longitude</label>
                    <input type="text" name="longitude" class="form-control" value="<?php echo htmlspecialchars($tubewell['longitude']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">📅 Installation Date</label>
                <input type="date" name="installation_date" class="form-control" value="<?php echo htmlspecialchars($tubewell['installation_date']); ?>">
            </div>

            <!-- Existing Media Section -->
            <div class="media-section">
                <div class="media-actions">
                    <h3 class="section-title" style="margin:0;">📁 Existing Media Files</h3>
                    <span class="badge badge-info"><?php echo $media_files->num_rows; ?> files</span>
                </div>
                <?php if ($media_files->num_rows > 0): ?>
                    <div class="media-gallery">
                        <?php while($media = $media_files->fetch_assoc()): ?>
                            <div class="media-item <?php echo $media['file_type'] == 'video' ? 'video-thumbnail' : ''; ?>">
                                <?php if ($media['file_type'] == 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                         alt="Media" 
                                         class="media-thumbnail"
                                         onerror="this.src='img/placeholder-image.jpg'">
                                <?php else: ?>
                                    <video class="media-thumbnail">
                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>
                                <a href="edit_tubewell.php?tubewell_id=<?php echo $tubewell_id; ?>&delete_media=1&media_id=<?php echo $media['id']; ?>" 
                                   class="delete-btn" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this media file?')">✕</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="media-empty">
                        <p>No media files uploaded yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add New Media Section -->
            <div class="media-section">
                <h3 class="section-title">📤 Add New Media Files</h3>
                <div class="file-upload-container" id="uploadContainer">
                    <button type="button" class="upload-btn" onclick="document.getElementById('media_files').click()">
                        📎 Choose Files
                    </button>
                    <input type="file" name="media_files[]" id="media_files" class="file-input" multiple 
                           accept="image/*,video/*" onchange="handleFileSelect(this.files)">
                    <p style="color:#666; margin:0.5rem 0; font-size:14px;">Drag & drop files here or click to browse</p>
                    <small style="color:#888;">Supported formats: JPEG, PNG (Max 50MB each)</small>
                    
                    <div class="file-list" id="fileList"></div>
                </div>
            </div>

            <div class="btn-group" style="justify-content:center; margin-top:2rem;">
                <a href="view_site.php?site_id=<?php echo (int)$site_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Tubewell</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-hide alert after 3s
    setTimeout(() => {
        document.querySelectorAll('.fade-out').forEach(el => {
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 1000);
        });
    }, 3000);

    // File upload functionality
    const uploadContainer = document.getElementById('uploadContainer');
    const fileInput = document.getElementById('media_files');
    const fileList = document.getElementById('fileList');
    let selectedFiles = [];

    // Drag and drop functionality
    uploadContainer.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadContainer.classList.add('dragover');
    });

    uploadContainer.addEventListener('dragleave', () => {
        uploadContainer.classList.remove('dragover');
    });

    uploadContainer.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadContainer.classList.remove('dragover');
        handleFileSelect(e.dataTransfer.files);
    });

    function handleFileSelect(files) {
        for (let file of files) {
            if (validateFile(file)) {
                selectedFiles.push(file);
                displayFile(file);
            }
        }
        updateFileInput();
    }

    function validateFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi', 'video/quicktime'];
        const maxSize = 50 * 1024 * 1024; // 50MB

        if (!allowedTypes.includes(file.type)) {
            alert(`Invalid file type: ${file.name}. Please upload images or videos only.`);
            return false;
        }

        if (file.size > maxSize) {
            alert(`File too large: ${file.name}. Maximum size is 10MB.`);
            return false;
        }

        return true;
    }

    function displayFile(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        
        const fileInfo = document.createElement('div');
        fileInfo.style.display = 'flex';
        fileInfo.style.alignItems = 'center';
        fileInfo.style.flex = '1';
        
        // Create preview for images
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.width = '30px';
            img.style.height = '30px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '4px';
            img.style.marginRight = '10px';
            fileInfo.appendChild(img);
        } else {
            // Video icon
            const videoIcon = document.createElement('span');
            videoIcon.textContent = '🎬';
            videoIcon.style.marginRight = '10px';
            videoIcon.style.fontSize = '20px';
            fileInfo.appendChild(videoIcon);
        }
        
        const fileName = document.createElement('span');
        fileName.textContent = file.name;
        fileName.style.fontSize = '14px';
        fileInfo.appendChild(fileName);
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-btn';
        removeBtn.textContent = '✕';
        removeBtn.onclick = () => removeFile(file);
        
        fileItem.appendChild(fileInfo);
        fileItem.appendChild(removeBtn);
        fileList.appendChild(fileItem);
    }

    function removeFile(fileToRemove) {
        selectedFiles = selectedFiles.filter(file => file !== fileToRemove);
        updateFileList();
        updateFileInput();
    }

    function updateFileList() {
        fileList.innerHTML = '';
        selectedFiles.forEach(displayFile);
    }

    function updateFileInput() {
        // This is a workaround since we can't directly set files property
        // The actual files will be handled by the form submission
    }
</script>
</body>
</html>
