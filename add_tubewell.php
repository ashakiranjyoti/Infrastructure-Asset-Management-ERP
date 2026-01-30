<?php
session_start();
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Validate site_id and count
if (!isset($_GET['site_id']) || !isset($_GET['count'])) {
    die("Invalid request. Site ID and count parameters are required.");
}

$site_id = (int)$_GET['site_id'];
$tubewell_count = (int)$_GET['count'];

// Check if site exists
$has_lcs_col = false;
$chk = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites' AND COLUMN_NAME = 'lcs_available'");
if ($chk && $chk->fetch_row()) { $has_lcs_col = true; }

$site_sql = $has_lcs_col ? "SELECT site_name, lcs_available FROM sites WHERE id = ?" : "SELECT site_name FROM sites WHERE id = ?";
$stmt = $conn->prepare($site_sql);
if (!$stmt) { die("Database error: " . $conn->error); }

$stmt->bind_param("i", $site_id);
$stmt->execute();
$result = $stmt->get_result();
$site = $result->fetch_assoc();
if (!$site) { die("Site not found with ID: " . $site_id); }
$stmt->close();

// Initialize messages
$error = '';
$success = '';
$media_files = [];

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
    $created_by = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'system');

    // 🧾 Validation
    if ($zone_name === '' || $tubewell_name === '') {
        $error = "⚠️ Zone Name and Tubewell Name are required.";
    } elseif (!empty($incharge_contact) && !preg_match('/^[0-9]{10,15}$/', $incharge_contact)) {
        $error = "⚠️ Incharge contact number must be numeric (10–15 digits).";
    } elseif (!empty($latitude) && !is_numeric($latitude)) {
        $error = "⚠️ Latitude must be a valid number.";
    } elseif (!empty($longitude) && !is_numeric($longitude)) {
        $error = "⚠️ Longitude must be a valid number.";
    } else {
        // 🔍 Check for duplicate tubewell in same site
        $check = $conn->prepare("SELECT COUNT(*) FROM tubewells WHERE LOWER(tubewell_name) = LOWER(?) AND site_id = ?");
        $check->bind_param("si", $tubewell_name, $site_id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            $error = "⚠️ A tubewell with this name already exists for this site.";
        } else {
            // Handle file uploads
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
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi', 'video/mov', 'application/pdf'];
                        if (!in_array($file_type, $allowed_types)) {
                            $error = "⚠️ Invalid file type: " . $name . ". Allowed types: JPEG, PNG, GIF, MP4, AVI, MOV, PDF";
                            continue;
                        }
                        
                        // Validate file size (max 10MB)
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
                                'type' => strpos($file_type, 'image/') === 0 ? 'image' : 
                                         (strpos($file_type, 'video/') === 0 ? 'video' : 'document')
                            ];
                        } else {
                            $error = "⚠️ Failed to upload file: " . $name;
                        }
                    }
                }
            }

            if (empty($error)) {
                // ✅ Insert new tubewell
                $sql = "INSERT INTO tubewells 
                    (site_id, zone_name, tubewell_name, tw_address, incharge_name, incharge_contact, sim_no, latitude, longitude, installation_date, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("issssssddss", $site_id, $zone_name, $tubewell_name, $tw_address, $incharge_name, $incharge_contact, $sim_no, $latitude, $longitude, $installation_date, $created_by);

                    if ($stmt->execute()) {
                        $tubewell_id = $stmt->insert_id;
                        
                        // Insert media files into database
                        if (!empty($uploaded_files)) {
                            $media_sql = "INSERT INTO tubewell_media (tubewell_id, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)";
                            $media_stmt = $conn->prepare($media_sql);
                            
                            foreach ($uploaded_files as $file) {
                                $media_stmt->bind_param("issss", $tubewell_id, $file['name'], $file['path'], $file['type'], $created_by);
                                $media_stmt->execute();
                            }
                            $media_stmt->close();
                        }
                        
                        $remaining_count = $tubewell_count - 1;

                        if ($remaining_count > 0) {
                            // Redirect to add next tubewell
                            header("Location: add_tubewell.php?site_id=$site_id&count=$remaining_count&message=added");
                            exit();
                        } else {
                            header("Location: dashboard.php?message=tubewells_added");
                            exit();
                        }
                    } else {
                        $error = "❌ Error adding tubewell: " . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tubewell</title>
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
        .file-upload-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1rem;
            background: #f8f9fa;
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
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 1rem;
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
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .file-item .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 2px 8px;
            cursor: pointer;
            font-size: 12px;
        }
        .file-item .remove-btn:hover {
            background: #cc0000;
        }
        .file-preview {
            max-width: 100px;
            max-height: 100px;
            margin-right: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <div class="container">
        <div class="card">
            <div class="progress-steps">
                <div class="step completed">Site Details</div>
                <div class="step active">Add Tubewells</div>
                <div class="step">Configure Parameters</div>
            </div>

            <div style="text-align:center; margin-bottom:2rem;">
                <h2 style="color:#2d3748;">Add Tubewell</h2>
                <p style="color:#718096;">Site: <strong><?php echo htmlspecialchars($site['site_name']); ?></strong></p>
                <div class="badge badge-info" style="margin-top:0.5rem;">Remaining: <?php echo $tubewell_count; ?> tubewells to add</div>
            </div>

            <?php if (!empty($_GET['message']) && $_GET['message'] === 'added'): ?>
                <div class="alert alert-success fade-out">✅ Tubewell added successfully! Add the next one.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error fade-out"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="tubewell-form" enctype="multipart/form-data">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                    <div class="form-group">
                        <label class="form-label">☯ Zone Name *</label>
                        <input type="text" name="zone_name" class="form-control" placeholder="Enter zone name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">💧 Tubewell Name *</label>
                        <input type="text" name="tubewell_name" class="form-control" placeholder="Enter tubewell name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">👤 Incharge Name</label>
                        <input type="text" name="incharge_name" class="form-control" placeholder="Enter incharge name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">📞 Incharge Contact</label>
                        <input type="text" name="incharge_contact" class="form-control" placeholder="Enter incharge contact">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">📍 Tubewell Address</label>
                    <textarea name="tw_address" class="form-control" placeholder="Enter tubewell address"></textarea>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:2rem;">
                    <div class="form-group">
                        <label class="form-label">📱 SIM Number</label>
                        <input type="text" name="sim_no" class="form-control" placeholder="Enter SIM number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">🌐 Latitude</label>
                        <input type="text" name="latitude" class="form-control" placeholder="Enter latitude">
                    </div>
                    <div class="form-group">
                        <label class="form-label">🌐 Longitude</label>
                        <input type="text" name="longitude" class="form-control" placeholder="Enter longitude">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">📅 Installation Date</label>
                    <input type="date" name="installation_date" class="form-control">
                </div>

                <!-- Media Upload Section -->
                <div class="form-group">
                    <label class="form-label">📁 Upload Photos & Videos</label>
                    <div class="file-upload-container" id="uploadContainer">
                        <button type="button" class="upload-btn" onclick="document.getElementById('media_files').click()">
                            📎 Choose Files
                        </button>
                        <input type="file" name="media_files[]" id="media_files" class="file-input" multiple 
                               accept="image/*,video" onchange="handleFileSelect(this.files)">
                        <p style="color:#666; margin:0.5rem 0;">Drag & drop files here or click to browse</p>
                        <small style="color:#888;">Supported formats: JPEG, PNG (Max 50MB each)</small>
                        
                        <div class="file-list" id="fileList"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">👤 Created By</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'system'); ?>" readonly>
                </div>

                <div class="btn-group" style="justify-content:center; margin-top:2rem;">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Tubewell & Continue</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide alerts after 3 seconds
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
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/avi', 'video/quicktime', 'application/pdf'];
            const maxSize = 50 * 1024 * 1024; // 50MB

            if (!allowedTypes.includes(file.type)) {
                alert(`Invalid file type: ${file.name}. Please upload images, videos, or PDF files.`);
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
            
            // Create preview for images
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'file-preview';
                fileInfo.appendChild(img);
            }
            
            const fileName = document.createElement('span');
            fileName.textContent = file.name;
            fileName.style.flex = '1';
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
