<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Check if site_id parameter is set
if (!isset($_GET['site_id'])) {
    die("Invalid request. Site ID parameter is required.");
}

$site_id = $_GET['site_id'];

// Get site details with error handling
// Include lcs_available if column exists
$has_lcs_col = false;
$chk = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites' AND COLUMN_NAME = 'lcs_available'");
if ($chk && $chk->fetch_row()) { $has_lcs_col = true; }

$site_sql = $has_lcs_col ? "SELECT *, lcs_available FROM sites WHERE id = ?" : "SELECT * FROM sites WHERE id = ?";
$stmt = $conn->prepare($site_sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $site_id);
$stmt->execute();
$site_result = $stmt->get_result();
$site = $site_result->fetch_assoc();

if (!$site) {
    die("Site not found with ID: " . $site_id);
}

// Get tubewells for this site
$tubewell_sql = "SELECT * FROM tubewells WHERE site_id = ?";
$stmt = $conn->prepare($tubewell_sql);
$stmt->bind_param("i", $site_id);
$stmt->execute();
$tubewells = $stmt->get_result();

// Detect if LCS is available for this site and whether any LCS data exists
$lcs_available = ($has_lcs_col && !empty($site['lcs_available'])) ? 1 : 0;
$lcs = null; $lcs_exists = 0;
if ($lcs_available) {
    // Load LCS row for this site (one per site)
    if ($ls = $conn->prepare("SELECT * FROM lcs WHERE site_id = ? LIMIT 1")) {
        $ls->bind_param('i', $site_id);
        $ls->execute();
        $lcs = $ls->get_result()->fetch_assoc();
        $lcs_exists = $lcs ? 1 : 0;
    }
}

?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Details - <?php echo htmlspecialchars($site['site_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .site-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .tubewell-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .tubewell-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .tubewell-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .tubewell-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f7fafc;
            border-radius: 8px;
        }

        /* Media Gallery Styles */
        .media-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .media-item:hover {
            transform: scale(1.05);
        }

        .media-thumbnail {
            width: 70px;
            height: 70px;
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

        .media-count {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: absolute;
            top: 60%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 90%;
            max-height: 90%;
        }

        .modal-image {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 8px;
        }

        .modal-video {
            max-width: 90%;
            max-height: 80vh;
            border-radius: 8px;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }

        .close-modal:hover {
            color: #ccc;
        }

        .modal-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            transform: translateY(-50%);
        }

        .nav-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            transition: background 0.3s;
        }

        .nav-btn:hover {
            background: rgba(255,255,255,0.4);
        }

        .media-empty {
            text-align: center;
            color: #718096;
            padding: 1rem;
            font-style: italic;
        }
    </style>
</head>
<body>
     <?php include('header.php'); ?>

    <div class="container">
        <div class="site-header">
            <h1><?php echo htmlspecialchars($site['site_name']); ?></h1>
            <p><?php echo htmlspecialchars($site['address']); ?></p>
        </div>

        <?php if ($has_lcs_col && $lcs_available): ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="color: #2d3748; margin: 0;">🧰 LCS</h2>
            </div>
            <div class="tubewell-card">
                <div class="tubewell-header">
                    <h3 style="color: #2d3748; margin: 0;"><?php echo $lcs_exists ? htmlspecialchars($lcs['lcs_name']) : 'Site LCS'; ?></h3>
                    <span class="badge <?php echo $lcs_exists ? 'badge-success' : 'badge-warning'; ?>"><?php echo $lcs_exists ? 'Configured' : 'Pending'; ?></span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <p><strong>📍 Address:</strong> <?php echo $lcs_exists ? htmlspecialchars($lcs['tw_address']) : htmlspecialchars($site['address']); ?></p>
                        <p><strong>👤 Incharge:</strong> <?php echo $lcs_exists ? htmlspecialchars($lcs['incharge_name']) : '—'; ?></p>
                        <p><strong>🔗 Linked To:</strong> <?php echo htmlspecialchars($site['site_name']); ?></p>
                    </div>
                    <div>
                        <?php
                        $last_upd = '—';
                        if ($lcs_exists) {
                            $mx_times = [];
                            if ($lu = $conn->prepare("SELECT MAX(updated_at) AS mx FROM lcs_status_history WHERE lcs_id = ?")) {
                                $lu->bind_param('i', $lcs['id']); $lu->execute(); $mx = $lu->get_result()->fetch_assoc(); if (!empty($mx['mx'])) { $mx_times[] = $mx['mx']; }
                            }
                            if ($mn = $conn->prepare("SELECT MAX(updated_at) AS mx FROM lcs_master_notes WHERE lcs_id = ?")) {
                                $mn->bind_param('i', $lcs['id']); $mn->execute(); $mr = $mn->get_result()->fetch_assoc(); if (!empty($mr['mx'])) { $mx_times[] = $mr['mx']; }
                            }
                            if ($mm = $conn->prepare("SELECT MAX(uploaded_at) AS mx FROM lcs_master_media WHERE lcs_id = ?")) {
                                $mm->bind_param('i', $lcs['id']); $mm->execute(); $mr2 = $mm->get_result()->fetch_assoc(); if (!empty($mr2['mx'])) { $mx_times[] = $mr2['mx']; }
                            }
                            if (!empty($mx_times)) { $last_upd = date('d M Y H:i', strtotime(max($mx_times))); }
                        }
                        ?>
                        <p><strong>🕒 Last Update:</strong> <?php echo $last_upd; ?></p>
                        <?php if ($lcs_exists): ?>
                        <p><strong>🌐 Coordinates:</strong> <?php echo htmlspecialchars($lcs['latitude']); ?>, <?php echo htmlspecialchars($lcs['longitude']); ?></p>
                        <p><strong>📅 Installed:</strong> <?php echo htmlspecialchars($lcs['installation_date']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tubewell-actions">
                    <?php if ($lcs_exists): ?>
                        <?php if ($_SESSION['role'] == 'admin') { ?>
                        <a href="edit_lcs.php?lcs_id=<?php echo (int)$lcs['id']; ?>" class="btn btn-info">✏️ Edit LCS</a>
                        <?php } ?>
                        <a href="view_lcs.php?lcs_id=<?php echo (int)$lcs['id']; ?>" class="btn btn-primary">View Parameters</a>
                        <a href="add_lcs_parameters.php?lcs_id=<?php echo (int)$lcs['id']; ?>" class="btn btn-warning">📅 Add Daily Status</a>
                        
                    <?php else: ?>
                        <?php if ($_SESSION['role'] == 'admin') { ?>
                        <a href="add_lcs.php?site_id=<?php echo (int)$site_id; ?>" class="btn btn-primary">+ Create LCS</a>
                        <?php } ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
                <h2 style="color: #2d3748; margin: 0;">📊 Site Overview</h2>
                <?php if ($_SESSION['role'] == 'admin') { ?>
                <a href="edit_site.php?site_id=<?php echo (int)$site_id; ?>" class="btn btn-info">✏️ Edit Site</a>
                <?php } ?>
            </div>
            
            <div class="info-grid">
                <div class="info-card">
                    <h3 style="color: #667eea; margin-bottom: 1rem;">🏛️ Division Information</h3>
                    <p><strong>Division Name:</strong> <?php echo htmlspecialchars($site['division_name']); ?></p>
                    <p><strong>Contractor:</strong> <?php echo htmlspecialchars($site['contractor_name']); ?></p>
                </div>
                
                <div class="info-card">
                    <h3 style="color: #667eea; margin-bottom: 1rem;">👤 Contact Information</h3>
                    <p><strong>Site Incharge:</strong> <?php echo htmlspecialchars($site['site_incharge']); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($site['contact']); ?></p>
                </div>
                
                <div class="info-card">
                    <h3 style="color: #667eea; margin-bottom: 1rem;">💧 Tubewell Statistics</h3>
                    <p><strong>Total Tubewells:</strong> <?php echo $site['number_of_tubewell']; ?></p>
                    <p><strong>Added Tubewells:</strong> <?php echo $tubewells->num_rows; ?></p>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div style="font-size: 2rem; color: #667eea;">💧</div>
                    <h3><?php echo $site['number_of_tubewell']; ?></h3>
                    <p>Total Tubewells</p>
                </div>
                <div class="stat-item">
                    <div style="font-size: 2rem; color: #48bb78;">✅</div>
                    <h3><?php echo $tubewells->num_rows; ?></h3>
                    <p>Added Tubewells</p>
                </div>
                <div class="stat-item">
                    <div style="font-size: 2rem; color: #ed8936;">⏳</div>
                    <h3><?php echo $site['number_of_tubewell'] - $tubewells->num_rows; ?></h3>
                    <p>Remaining</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem;">
                <h2 style="color: #2d3748; margin: 0;">💧 Tubewells</h2>
                
                <?php if ($_SESSION['role'] == 'admin') { ?>
                <div style="margin-left:auto; display:flex; gap:0.5rem;">
                    <?php if ($has_lcs_col && $lcs_available && !$lcs_exists): ?>
                        <a href="add_lcs.php?site_id=<?php echo $site_id; ?>" class="btn btn-secondary">+ Add LCS</a>
                    <?php endif; ?>
                    <?php if ($tubewells->num_rows < $site['number_of_tubewell']): ?>
                    <a href="add_tubewell.php?site_id=<?php echo $site_id; ?>&count=<?php echo $site['number_of_tubewell'] - $tubewells->num_rows; ?>" class="btn btn-primary">
                        + Add Tubewell
                    </a>
                    <?php endif; ?>
                </div>
                <?php } ?>
                
            </div>
            
            <?php if ($tubewells->num_rows > 0): ?>
                <?php while($tubewell = $tubewells->fetch_assoc()): ?>
                <?php
                // Get media files for this tubewell
                $media_sql = "SELECT * FROM tubewell_media WHERE tubewell_id = ? ORDER BY uploaded_at DESC";
                $media_stmt = $conn->prepare($media_sql);
                $media_stmt->bind_param("i", $tubewell['id']);
                $media_stmt->execute();
                $media_files = $media_stmt->get_result();
                $media_count = $media_files->num_rows;
                ?>
                <div class="tubewell-card">
                    <div class="tubewell-header">
                        <div style="display: flex; align-items: center;">
                            <h3 style="color: #2d3748; margin: 0;"><?php echo htmlspecialchars($tubewell['zone_name']); ?>- </h3>
                            <h3 style="color: #2d3748; margin: 0;"><?php echo htmlspecialchars($tubewell['tubewell_name']); ?></h3>
                            <?php if ($media_count > 0): ?>
                                <span class="media-count">📷 <?php echo $media_count; ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="badge badge-success">Active</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <p><strong>📍 Address:</strong> <?php echo htmlspecialchars($tubewell['tw_address']); ?></p>
                        <p><strong>👤 Incharge:</strong> <?php echo htmlspecialchars($tubewell['incharge_name']); ?></p>
                        <p><strong>📞 Contact:</strong> <?php echo htmlspecialchars($tubewell['incharge_contact']); ?></p>
                        <p><strong>📱 SIM:</strong> <?php echo htmlspecialchars($tubewell['sim_no']); ?></p>
                    </div>
                    <div>
                        <?php
                        $tw_last_upd = '—';
                        $tid = (int)$tubewell['id'];
                        $mx_times = [];
                        if ($lu = $conn->prepare("SELECT MAX(updated_at) AS mx FROM status_history WHERE tubewell_id = ?")) {
                            $lu->bind_param('i', $tid); $lu->execute(); $mx = $lu->get_result()->fetch_assoc(); if (!empty($mx['mx'])) { $mx_times[] = $mx['mx']; }
                        }
                        if ($mn = $conn->prepare("SELECT MAX(updated_at) AS mx FROM tubewell_master_notes WHERE tubewell_id = ?")) {
                            $mn->bind_param('i', $tid); $mn->execute(); $mr = $mn->get_result()->fetch_assoc(); if (!empty($mr['mx'])) { $mx_times[] = $mr['mx']; }
                        }
                        if ($mm = $conn->prepare("SELECT MAX(uploaded_at) AS mx FROM tubewell_master_media WHERE tubewell_id = ?")) {
                            $mm->bind_param('i', $tid); $mm->execute(); $mr2 = $mm->get_result()->fetch_assoc(); if (!empty($mr2['mx'])) { $mx_times[] = $mr2['mx']; }
                        }
                        if (!empty($mx_times)) { $tw_last_upd = date('d M Y H:i', strtotime(max($mx_times))); }
                        ?>
                        <p><strong>🕒 Last Update:</strong> <?php echo $tw_last_upd; ?></p>
                        <p><strong>🌐 Coordinates:</strong> <?php echo htmlspecialchars($tubewell['latitude']); ?>, <?php echo htmlspecialchars($tubewell['longitude']); ?></p>
                        <p><strong>📅 Installed:</strong> <?php echo htmlspecialchars($tubewell['installation_date']); ?></p>
                        <p><strong>👨🏻‍💼 Created By:</strong> <?php echo htmlspecialchars($tubewell['created_by']); ?></p>
                    </div>
                </div>

                <!-- Media Gallery Section -->
                <?php if ($media_count > 0): ?>
                <div class="media-section">
                    <h4 style="color: #4a5568; margin-bottom: 10px;">📁 Media Files</h4>
                    <div class="media-gallery">
                        <?php while($media = $media_files->fetch_assoc()): ?>
                            <?php if ($media['file_type'] == 'image' || $media['file_type'] == 'video'): ?>
                            <div class="media-item <?php echo $media['file_type'] == 'video' ? 'video-thumbnail' : ''; ?>" 
                                 onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', '<?php echo $media['file_type']; ?>')">
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
                            </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
                    
                    <div class="tubewell-actions">
                        <?php if ($_SESSION['role'] == 'admin') { ?>
                        <a href="edit_tubewell.php?tubewell_id=<?php echo (int)$tubewell['id']; ?>" class="btn btn-info">✏️ Edit Tubewell</a>
                        <?php } ?>
                        <a href="view_tubewell.php?tubewell_id=<?php echo $tubewell['id']; ?>" class="btn btn-primary">View Parameters</a>
                        <a href="add_parameters.php?tubewell_id=<?php echo $tubewell['id']; ?>" class="btn btn-warning">📅 Add Daily Status</a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #718096;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">💧</div>
                    <h3>No Tubewells Added Yet</h3>
                    <p>Start by adding the first tubewell for this site</p>
                    <a href="add_tubewell.php?site_id=<?php echo $site_id; ?>&count=<?php echo $site['number_of_tubewell']; ?>" class="btn btn-primary">
                        Add First Tubewell
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Modal for full-size media view -->
    <div id="mediaModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-nav">
            <button class="nav-btn" onclick="changeMedia(-1)">❮</button>
            <button class="nav-btn" onclick="changeMedia(1)">❯</button>
        </div>
        <div class="modal-content">
            <img id="modalImage" class="modal-image" style="display:none;">
            <video id="modalVideo" class="modal-video" controls style="display:none;"></video>
        </div>
    </div>

    <script>
        let currentMediaIndex = 0;
        let mediaItems = [];

        // Collect all media items from the page
        function collectMediaItems() {
            mediaItems = [];
            document.querySelectorAll('.media-item').forEach(item => {
                const mediaElement = item.querySelector('img, video');
                if (mediaElement) {
                    const source = mediaElement.tagName === 'IMG' ? 
                                 mediaElement.src : 
                                 mediaElement.querySelector('source').src;
                    const type = mediaElement.tagName === 'IMG' ? 'image' : 'video';
                    mediaItems.push({ source, type });
                }
            });
        }

        function openModal(src, type) {
            collectMediaItems();
            
            // Find the clicked media index
            currentMediaIndex = mediaItems.findIndex(item => item.source === src);
            if (currentMediaIndex === -1) currentMediaIndex = 0;
            
            const modal = document.getElementById('mediaModal');
            const modalImage = document.getElementById('modalImage');
            const modalVideo = document.getElementById('modalVideo');
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            if (type === 'image') {
                modalImage.src = src;
                modalImage.style.display = 'block';
                modalVideo.style.display = 'none';
            } else {
                modalVideo.innerHTML = `<source src="${src}" type="video/mp4">`;
                modalVideo.style.display = 'block';
                modalImage.style.display = 'none';
                modalVideo.load();
            }
        }

        function closeModal() {
            const modal = document.getElementById('mediaModal');
            const modalVideo = document.getElementById('modalVideo');
            
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Pause video when closing modal
            if (modalVideo) {
                modalVideo.pause();
            }
        }

        function changeMedia(direction) {
            if (mediaItems.length === 0) return;
            
            currentMediaIndex += direction;
            if (currentMediaIndex < 0) currentMediaIndex = mediaItems.length - 1;
            if (currentMediaIndex >= mediaItems.length) currentMediaIndex = 0;
            
            const media = mediaItems[currentMediaIndex];
            const modalImage = document.getElementById('modalImage');
            const modalVideo = document.getElementById('modalVideo');
            
            if (media.type === 'image') {
                modalImage.src = media.source;
                modalImage.style.display = 'block';
                modalVideo.style.display = 'none';
                modalVideo.pause();
            } else {
                modalVideo.innerHTML = `<source src="${media.source}" type="video/mp4">`;
                modalVideo.style.display = 'block';
                modalImage.style.display = 'none';
                modalVideo.load();
                modalVideo.play();
            }
        }

        // Close modal when clicking outside content
        window.onclick = function(event) {
            const modal = document.getElementById('mediaModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById('mediaModal');
            if (modal.style.display === 'block') {
                if (event.key === 'Escape') {
                    closeModal();
                } else if (event.key === 'ArrowLeft') {
                    changeMedia(-1);
                } else if (event.key === 'ArrowRight') {
                    changeMedia(1);
                }
            }
        });
    </script>
</body>
</html>