<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Check if tubewell_id parameter is set
if (!isset($_GET['tubewell_id'])) {
    die("Invalid request. Tubewell ID parameter is required.");
}

$tubewell_id = $_GET['tubewell_id'];

// Get tubewell details with error handling
$tubewell_sql = "SELECT tw.*, s.site_name 
                 FROM tubewells tw 
                 JOIN sites s ON tw.site_id = s.id 
                 WHERE tw.id = ?";
$stmt = $conn->prepare($tubewell_sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $tubewell_id);
$stmt->execute();
$tubewell_result = $stmt->get_result();
$tubewell = $tubewell_result->fetch_assoc();

// Check if tubewell exists
if (!$tubewell) {
    die("Tubewell not found with ID: " . $tubewell_id);
}

// Fetch tubewell media files
$media_sql = "SELECT * FROM tubewell_media WHERE tubewell_id = ? ORDER BY uploaded_at DESC";
$media_stmt = $conn->prepare($media_sql);
$media_stmt->bind_param("i", $tubewell_id);
$media_stmt->execute();
$tubewell_media = $media_stmt->get_result();
$media_count = $tubewell_media->num_rows;

// Ensure master notes table exists
$conn->query("CREATE TABLE IF NOT EXISTS tubewell_master_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tubewell_id INT NOT NULL,
    status_date DATE NOT NULL,
    note TEXT NULL,
    updated_by VARCHAR(100) NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_tw_date (tubewell_id, status_date),
    CONSTRAINT fk_tmn_tw FOREIGN KEY (tubewell_id) REFERENCES tubewells(id) ON DELETE CASCADE
)");

// Determine unified latest display date = max of item status, master notes, master media
$display_date = '';
// Latest item status date
$stmt = $conn->prepare("SELECT status_date FROM status_history WHERE tubewell_id = ? ORDER BY status_date DESC LIMIT 1");
$stmt->bind_param('i', $tubewell_id);
$stmt->execute();
$ld = $stmt->get_result()->fetch_assoc();
if ($ld && !empty($ld['status_date'])) { $display_date = $ld['status_date']; }
// Latest master note date
if ($mnmax = $conn->prepare('SELECT MAX(status_date) AS mx FROM tubewell_master_notes WHERE tubewell_id = ?')) {
    $mnmax->bind_param('i', $tubewell_id);
    $mnmax->execute();
    $mr = $mnmax->get_result()->fetch_assoc();
    if (!empty($mr['mx']) && $mr['mx'] > $display_date) { $display_date = $mr['mx']; }
}
// Latest master media date
if ($mmmax = $conn->prepare('SELECT MAX(status_date) AS mx FROM tubewell_master_media WHERE tubewell_id = ?')) {
    $mmmax->bind_param('i', $tubewell_id);
    $mmmax->execute();
    $mr2 = $mmmax->get_result()->fetch_assoc();
    if (!empty($mr2['mx']) && $mr2['mx'] > $display_date) { $display_date = $mr2['mx']; }
}

// Load master note for the latest status date (or most recent master note)
$master_note_row = null;
if ($display_date && $mn = $conn->prepare('SELECT note, updated_by, updated_at, status_date FROM tubewell_master_notes WHERE tubewell_id = ? AND status_date = ?')) {
    $mn->bind_param('is', $tubewell_id, $display_date);
    $mn->execute();
    $master_note_row = $mn->get_result()->fetch_assoc();
}
// If not found for display_date, get the most recent master note
if (!$master_note_row) {
    if ($mn_latest = $conn->prepare('SELECT note, updated_by, updated_at, status_date FROM tubewell_master_notes WHERE tubewell_id = ? ORDER BY status_date DESC LIMIT 1')) {
        $mn_latest->bind_param('i', $tubewell_id);
        $mn_latest->execute();
        $master_note_row = $mn_latest->get_result()->fetch_assoc();
    }
}

// Load master note media for only the display date
$master_media = [];
if ($display_date !== '') {
    if ($mm = $conn->prepare('SELECT id, file_path, file_type, uploaded_by, uploaded_at FROM tubewell_master_media WHERE tubewell_id = ? AND status_date = ? ORDER BY uploaded_at DESC')) {
        $mm->bind_param('is', $tubewell_id, $display_date);
        $mm->execute();
        $mres = $mm->get_result();
        while ($row = $mres->fetch_assoc()) { $master_media[] = $row; }
    }
}

// Last updated timestamp across all rows for this tubewell
$last_updated_at = '—';
if ($mxs = $conn->prepare("SELECT MAX(updated_at) AS mx FROM status_history WHERE tubewell_id = ?")) {
    $mxs->bind_param('i', $tubewell_id);
    $mxs->execute();
    $mxr = $mxs->get_result()->fetch_assoc();
    if (!empty($mxr['mx'])) { $last_updated_at = date('d M Y H:i', strtotime($mxr['mx'])); }
}

// Fetch rows for the display date if any
$status_rows = [];
$status_by_item = [];
if ($display_date !== '') {
    $rows_sql = "SELECT * FROM status_history WHERE tubewell_id = ? AND status_date = ? ";
    $stmt = $conn->prepare($rows_sql);
    $stmt->bind_param("is", $tubewell_id, $display_date);
    $stmt->execute();
    $status_res = $stmt->get_result();
    while ($r = $status_res->fetch_assoc()) {
        $status_rows[] = $r;
        $key = trim((string)$r['item_name']);
        if ($key !== '') { $status_by_item[$key] = $r; }
    }
}

// Load all items from master to show full list even if not updated that day
$all_items = [];
if ($im = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ")) {
    while ($row = $im->fetch_assoc()) { $all_items[] = $row['item_name']; }
}

// For items missing on the latest date, fallback to last known previous record up to display_date (like add_parameters)
$fallback_by_item = [];
if (!empty($all_items)) {
    foreach ($all_items as $iname) {
        if (!isset($status_by_item[$iname])) {
            if ($ps = $conn->prepare("SELECT * FROM status_history WHERE tubewell_id = ? AND item_name = ? AND status_date <= ? ORDER BY status_date DESC LIMIT 1")) {
                $ps->bind_param('iss', $tubewell_id, $iname, $display_date);
                $ps->execute();
                if ($pr = $ps->get_result()->fetch_assoc()) { $fallback_by_item[$iname] = $pr; }
            }
        }
    }
}

// Load additional custom items (spares) not in items_master
// Show the latest row per spare item_name up to display_date (if today's not present, fallback to previous)
$extra_rows = [];
if ($display_date !== '') {
    if ($ex = $conn->prepare(
        "SELECT t.* FROM status_history t
          JOIN (
            SELECT item_name, MAX(status_date) AS sd
            FROM status_history
            WHERE tubewell_id = ? AND status_date <= ? AND item_name NOT IN (SELECT item_name FROM items_master WHERE is_active = 1)
            GROUP BY item_name
          ) x ON t.tubewell_id = ? AND t.item_name = x.item_name AND t.status_date = x.sd
         ORDER BY t.item_name"
    )) {
        $ex->bind_param('isi', $tubewell_id, $display_date, $tubewell_id);
        $ex->execute();
        $er = $ex->get_result();
        while ($r = $er->fetch_assoc()) { $extra_rows[] = $r; }
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tubewell Parameters - <?php echo htmlspecialchars($tubewell['tubewell_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .tubewell-header {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .parameter-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #48bb78;
        }
        
        .status-active {
            color: #48bb78;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e53e3e;
            font-weight: bold;
        }
        
        .status-maintenance {
            color: #ed8936;
            font-weight: bold;
        }
        
        .check-true {
            color: #48bb78;
            font-weight: bold;
        }
        
        .check-false {
            color: #a0aec0;
        }
        
        .media-preview {
            max-width: 50px;
            max-height: 50px;
            border-radius: 4px;
            cursor: pointer;
            object-fit: cover;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .media-container {
            position: relative;
            display: inline-block;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            margin: auto;
            display: block;
            max-width: 70%;
            max-height: 70%;
            margin-top: 10%;
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .media-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #d1d5db;
        }
        
        table.data-table th, 
        table.data-table td {
            white-space: normal;
            padding: 8px 10px;
            font-size: 13px;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        
        .col-media {
            width: 120px;
        }
        
        /* Tubewell Media Styles */
        .tubewell-media-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .tubewell-media-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .tubewell-media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .tubewell-media-item:hover {
            transform: scale(1.05);
        }
        
        .tubewell-media-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .tubewell-video-thumbnail {
            position: relative;
        }
        
        .tubewell-video-thumbnail::after {
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
        
        .media-count-badge {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .media-empty-state {
            text-align: center;
            color: #718096;
            padding: 1rem;
            font-style: italic;
        }
        
        .media-section-title {
            color: #4a5568;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .modal-nav {
    position: absolute;
    top: 50%;
    width: 100%;
    display: none;
    justify-content: space-between;
    padding: 0 20px;
    transform: translateY(-50%);
    z-index: 1002;
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
    </style>
</head>
<body>
     <?php include('header.php'); ?>

    <div class="container">
        <div style="text-align:right; margin-bottom: 1rem;">
            <a href="view_site.php?site_id=<?php echo (int)$tubewell['site_id']; ?>" class="btn btn-secondary">← Back</a>
        </div>
        <div class="tubewell-header">
            <h1><?php echo htmlspecialchars($tubewell['tubewell_name']); ?></h1>
            <p>Site: <?php echo htmlspecialchars($tubewell['site_name']); ?></p>
            <p style="margin-top:0.5rem; font-size:0.95rem; opacity:0.95;">Last Updated: <strong><?php echo $last_updated_at; ?></strong></p>
        </div>

        <div class="card" style="margin-top:-1rem; margin-bottom: 1.5rem;">
            <h2 style="color: #2d3748; margin-bottom: .5rem;">📝 Master Note</h2>
            <?php if (!empty($master_note_row) && trim((string)$master_note_row['note']) !== ''): ?>
                <?php 
                $note_date = isset($master_note_row['status_date']) ? $master_note_row['status_date'] : '';
                $is_same_date = $note_date && $display_date && $note_date === $display_date;
                ?>
                <div style="white-space:pre-wrap; line-height:1.5; color:#2d3748;"><?php echo nl2br(htmlspecialchars($master_note_row['note'])); ?></div>
                <div style="margin-top:.5rem; font-size:.85rem; color:#4a5568;">
                    <?php if ($note_date): ?>
                        <?php if ($is_same_date): ?>
                            Date: <strong><?php echo date('d M Y', strtotime($note_date)); ?></strong> | 
                        <?php else: ?>
                            <span style="color:#e53e3e;">Note from: <strong><?php echo date('d M Y', strtotime($note_date)); ?></strong></span> | 
                        <?php endif; ?>
                    <?php endif; ?>
                    Updated by <strong><?php echo htmlspecialchars($master_note_row['updated_by'] ?? '—'); ?></strong>
                    on <?php echo date('d M Y H:i', strtotime($master_note_row['updated_at'])); ?>
                    <?php
                    // Show master note contributors for the display date
                    $mn_contribs = [];
                    if ($display_date && ($stc = $conn->prepare("SELECT contributor_name FROM tubewell_master_note_contributors WHERE tubewell_id = ? AND status_date = ? ORDER BY contributor_name"))) {
                        $stc->bind_param('is', $tubewell_id, $display_date);
                        $stc->execute();
                        $rc = $stc->get_result();
                        while ($cr = $rc->fetch_assoc()) { if (!empty($cr['contributor_name'])) { $mn_contribs[] = $cr['contributor_name']; } }
                    }
                    if (!empty($mn_contribs)) {
                        echo '<br>Contributors: <strong>' . htmlspecialchars(implode(', ', $mn_contribs)) . '</strong>';
                    }
                    ?>
                </div>
                <?php if (!empty($master_media)): ?>
                    <div style="margin-top:.75rem;">
                        <div style="font-weight:600; margin-bottom:.25rem;">Photo / Video</div>
                        <div class="media-grid">
                            <?php foreach ($master_media as $m): ?>
                                <div class="media-container">
                                    <?php if ($m['file_type'] === 'image'): ?>
                                        <img src="<?php echo htmlspecialchars($m['file_path']); ?>" 
                                             class="media-preview" 
                                             onclick="openModal('<?php echo htmlspecialchars($m['file_path']); ?>','image')" 
                                             alt="Master media">
                                    <?php else: ?>
                                        <video class="media-preview" onclick="openModal('<?php echo htmlspecialchars($m['file_path']); ?>','video')">
                                            <source src="<?php echo htmlspecialchars($m['file_path']); ?>" type="video/mp4">
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="color:#718096;">No master note added for this tubewell.</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                <h2 style="color: #2d3748; margin: 0;">📋 Tubewell Information</h2>
                <?php if ($media_count > 0): ?>
                    <span class="media-count-badge">📷 <?php echo $media_count; ?> files</span>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <p><strong>📍 Address:</strong> <?php echo htmlspecialchars($tubewell['tw_address']); ?></p>
                    <p><strong>👤 Incharge:</strong> <?php echo htmlspecialchars($tubewell['incharge_name']); ?></p>
                    <p><strong>📱 SIM Number:</strong> <?php echo htmlspecialchars($tubewell['sim_no']); ?></p>
                </div>
                <div>
                    <p><strong>🌐 Latitude:</strong> <?php echo htmlspecialchars($tubewell['latitude']); ?></p>
                    <p><strong>🌐 Longitude:</strong> <?php echo htmlspecialchars($tubewell['longitude']); ?></p>
                    <p><strong>📅 Installation Date:</strong> <?php echo htmlspecialchars($tubewell['installation_date']); ?></p>
                </div>
            </div>

            <!-- Tubewell Media Gallery -->
            <?php if ($media_count > 0): ?>
            <div class="tubewell-media-section">
                <h4 class="media-section-title">📁 Tubewell Photos & Videos</h4>
                <div class="tubewell-media-gallery">
                    <?php while($media = $tubewell_media->fetch_assoc()): ?>
                        <?php if ($media['file_type'] == 'image' || $media['file_type'] == 'video'): ?>
                        <div class="tubewell-media-item <?php echo $media['file_type'] == 'video' ? 'tubewell-video-thumbnail' : ''; ?>" 
                             onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', '<?php echo $media['file_type']; ?>')">
                            <?php if ($media['file_type'] == 'image'): ?>
                                <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                     alt="Tubewell Media" 
                                     class="tubewell-media-thumbnail"
                                     onerror="this.src='img/placeholder-image.jpg'">
                            <?php else: ?>
                                <video class="tubewell-media-thumbnail">
                                    <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="video/mp4">
                                </video>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 style="color: #2d3748; margin: 0;">📋 Latest Status</h2>
                <?php if ($display_date !== ''): ?>
                    <span class="badge badge-info">Date: <?php echo htmlspecialchars($display_date); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($display_date !== ''): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="15%">Item</th>
                            <th width="12%">Make/Model</th>
                            <th width="8%">Size/Capacity</th>
                            <th width="8%">Status</th>
                            <th width="6%">HMI/Local</th>
                            <th width="6%">Web</th>
                            <th width="15%">Remark</th>
                            <th width="12%">Photo / Video</th>
                            <th width="8%">Updated By</th>
                            <th width="10%">Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_items as $item_name): 
                            $row = $status_by_item[$item_name] ?? ($fallback_by_item[$item_name] ?? null);
                            $status_value = $row['status'] ?? '';
                            $status_class = $status_value !== '' ? ('status-' . strtolower($status_value)) : '';
                            $make_model = $row['make_model'] ?? '';
                            $size_capacity = $row['size_capacity'] ?? '';
                            $check_hmi_local = isset($row['check_hmi_local']) ? (int)$row['check_hmi_local'] === 1 : null;
                            $check_web = isset($row['check_web']) ? (int)$row['check_web'] === 1 : null;
                            $remark = $row['remark'] ?? '';
                            $created_by = $row['created_by'] ?? '—';
                            $updated_at_cell = (isset($row['updated_at']) && $row['updated_at']) ? date('d M Y H:i', strtotime($row['updated_at'])) : '—';
                            // Fetch latest update contributors for this item & date
                            $with_list = [];
                            if ($st = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='tubewell' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                $st->bind_param('iss', $tubewell_id, $item_name, $display_date);
                                $st->execute();
                                $ur = $st->get_result()->fetch_assoc();
                                if ($ur && isset($ur['id'])) {
                                    $created_by = $ur['updated_by'] ?: $created_by;
                                    if ($uc = $conn->prepare("SELECT contributor_name FROM update_contributors WHERE update_id=? ORDER BY contributor_name")) {
                                        $uc->bind_param('i', $ur['id']);
                                        $uc->execute();
                                        $cres = $uc->get_result();
                                        while ($cr = $cres->fetch_assoc()) { if (!empty($cr['contributor_name'])) { $with_list[] = $cr['contributor_name']; } }
                                    }
                                }
                            }
                            $with_str = !empty($with_list) ? ("\nWith: " . implode(', ', $with_list)) : '';
                            
                            // Get media for this item for the display date only
                            $media_stmt = $conn->prepare("SELECT * FROM media_uploads WHERE tubewell_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $tubewell_id, $item_name, $display_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        ?>
                        <tr>
                            <td class="col-item"><strong><?php echo htmlspecialchars($item_name); ?></strong></td>
                            <td class="col-make"><?php echo htmlspecialchars($make_model); ?></td>
                            <td class="col-size"><?php echo htmlspecialchars($size_capacity); ?></td>
                            <td>
                                <?php if ($status_value !== ''): ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_value); ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php $hs = isset($row['check_hmi_local']) ? (int)$row['check_hmi_local'] : 0; echo $hs===1?'<span class="check-true">✅</span>':($hs===2?'<span class="check-false">❌</span>':'—'); ?>
                            </td>
                            <td style="text-align: center;">
                                <?php $ws = isset($row['check_web']) ? (int)$row['check_web'] : 0; echo $ws===1?'<span class="check-true">✅</span>':($ws===2?'<span class="check-false">❌</span>':'—'); ?>
                            </td>
                            <td class="col-remark"><?php echo htmlspecialchars($remark); ?></td>
                            <td class="col-media">
                                <?php if (!empty($all_media)): ?>
                                    <div class="media-grid">
                                        <?php foreach ($all_media as $media): ?>
                                            <div class="media-container">
                                                <?php if ($media['file_type'] === 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                         class="media-preview" 
                                                         onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'image')"
                                                         alt="Media">
                                                <?php else: ?>
                                                    <video class="media-preview" onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'video')">
                                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="video/mp4">
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#999;">No media</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-by" style="white-space:wrap;">&nbsp;<?php echo htmlspecialchars($created_by); ?><?php echo htmlspecialchars($with_str); ?></td>
                            <td class="col-at" style="white-space:nowrap;">&nbsp;<?php echo $updated_at_cell; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!empty($extra_rows)): foreach ($extra_rows as $row): 
                            $status_value = $row['status'] ?? '';
                            $status_class = $status_value !== '' ? ('status-' . strtolower($status_value)) : '';
                            
                            // Get media for this extra item for the display date only
                            $media_stmt = $conn->prepare("SELECT * FROM media_uploads WHERE tubewell_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $tubewell_id, $row['item_name'], $display_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            // Fetch contributors for this extra item & date
                            $by_extra = $row['created_by'] ?? '—';
                            $with_list2 = [];
                            if ($stx = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='tubewell' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                $stx->bind_param('iss', $tubewell_id, $row['item_name'], $display_date);
                                $stx->execute();
                                $urx = $stx->get_result()->fetch_assoc();
                                if ($urx && isset($urx['id'])) {
                                    $by_extra = $urx['updated_by'] ?: $by_extra;
                                    if ($ucx = $conn->prepare("SELECT contributor_name FROM update_contributors WHERE update_id=? ORDER BY contributor_name")) {
                                        $ucx->bind_param('i', $urx['id']);
                                        $ucx->execute();
                                        $cres2 = $ucx->get_result();
                                        while ($cr2 = $cres2->fetch_assoc()) { if (!empty($cr2['contributor_name'])) { $with_list2[] = $cr2['contributor_name']; } }
                                    }
                                }
                            }
                            $with_str2 = !empty($with_list2) ? ("\nWith: " . implode(', ', $with_list2)) : '';
                        ?>
                        <tr>
                            <td class="col-item"><strong><?php echo htmlspecialchars($row['item_name']); ?></strong></td>
                            <td class="col-make"><?php echo htmlspecialchars($row['make_model'] ?? ''); ?></td>
                            <td class="col-size"><?php echo htmlspecialchars($row['size_capacity'] ?? ''); ?></td>
                            <td>
                                <?php if ($status_value !== ''): ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_value); ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php $hs = isset($row['check_hmi_local']) ? (int)$row['check_hmi_local'] : 0; echo $hs===1?'<span class="check-true">✅</span>':($hs===2?'<span class="check-false">❌</span>':'—'); ?>
                            </td>
                            <td style="text-align: center;">
                                <?php $ws = isset($row['check_web']) ? (int)$row['check_web'] : 0; echo $ws===1?'<span class="check-true">✅</span>':($ws===2?'<span class="check-false">❌</span>':'—'); ?>
                            </td>
                            <td class="col-remark"><?php echo htmlspecialchars($row['remark'] ?? ''); ?></td>
                            <td class="col-media">
                                <?php if (!empty($all_media)): ?>
                                    <div class="media-grid">
                                        <?php foreach ($all_media as $media): ?>
                                            <div class="media-container">
                                                <?php if ($media['file_type'] === 'image'): ?>
                                                    <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                         class="media-preview" 
                                                         onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'image')"
                                                         alt="Media">
                                                <?php else: ?>
                                                    <video class="media-preview" onclick="openModal('<?php echo htmlspecialchars($media['file_path']); ?>', 'video')">
                                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="video/mp4">
                                                    </video>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#999;">No media</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-by" style="white-space:wrap;">&nbsp;<?php echo htmlspecialchars($by_extra); ?><?php echo htmlspecialchars($with_str2); ?></td>
                            <td class="col-at" style="white-space:nowrap;">&nbsp;<?php echo isset($row['updated_at']) && $row['updated_at'] ? date('d M Y H:i', strtotime($row['updated_at'])) : '—'; ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #718096;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                <h3>No Status Recorded Yet</h3>
                <p>Please add today's status for available items to get started</p>
                <div class="btn-group" style="justify-content: center; margin-top: 1rem;">
                    <a href="add_parameters.php?tubewell_id=<?php echo $tubewell_id; ?>" class="btn btn-primary">➕ Add Today's Status</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
    </div>

   <!-- Modal for media preview -->
<div id="mediaModal" class="modal">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <div class="modal-nav" style="display: none;" id="modalNav">
        <button class="nav-btn" onclick="changeMedia(-1)">❮</button>
        <button class="nav-btn" onclick="changeMedia(1)">❯</button>
    </div>
    <img class="modal-content" id="modalImage">
    <video class="modal-content" id="modalVideo" controls style="display:none;"></video>
</div>

<script>
// Media modal functions
function openModal(src, type) {
    var modal = document.getElementById('mediaModal');
    var modalImage = document.getElementById('modalImage');
    var modalVideo = document.getElementById('modalVideo');
    var modalNav = document.getElementById('modalNav');
    
    // Reset body overflow
    document.body.style.overflow = 'hidden';
    
    if (type === 'image') {
        modalImage.src = src;
        modalImage.style.display = 'block';
        modalVideo.style.display = 'none';
    } else {
        modalVideo.src = src;
        modalVideo.style.display = 'block';
        modalImage.style.display = 'none';
    }
    
    modal.style.display = 'block';
    
    // Check if it's from tubewell media gallery and show navigation
    const isTubewellMedia = document.querySelector('.tubewell-media-item img[src="' + src + '"], .tubewell-media-item video source[src="' + src + '"]');
    if (isTubewellMedia && tubewellMediaItems.length > 1) {
        collectTubewellMediaItems();
        currentMediaIndex = tubewellMediaItems.findIndex(item => item.source === src);
        modalNav.style.display = 'flex';
    } else {
        modalNav.style.display = 'none';
    }
}

function closeModal() {
    var modal = document.getElementById('mediaModal');
    var modalVideo = document.getElementById('modalVideo');
    var modalNav = document.getElementById('modalNav');
    
    modal.style.display = 'none';
    
    // Reset body overflow to allow scrolling
    document.body.style.overflow = 'auto';
    
    if (modalVideo) {
        modalVideo.pause();
        modalVideo.currentTime = 0;
    }
    
    // Hide navigation
    modalNav.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('mediaModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Enhanced modal for tubewell media with navigation
let currentMediaIndex = 0;
let tubewellMediaItems = [];

// Collect all tubewell media items
function collectTubewellMediaItems() {
    tubewellMediaItems = [];
    document.querySelectorAll('.tubewell-media-item').forEach(item => {
        const mediaElement = item.querySelector('img, video');
        if (mediaElement) {
            const source = mediaElement.tagName === 'IMG' ? 
                         mediaElement.src : 
                         mediaElement.querySelector('source').src;
            const type = mediaElement.tagName === 'IMG' ? 'image' : 'video';
            tubewellMediaItems.push({ source, type });
        }
    });
}

function changeMedia(direction) {
    if (tubewellMediaItems.length === 0) return;
    
    currentMediaIndex += direction;
    if (currentMediaIndex < 0) currentMediaIndex = tubewellMediaItems.length - 1;
    if (currentMediaIndex >= tubewellMediaItems.length) currentMediaIndex = 0;
    
    const media = tubewellMediaItems[currentMediaIndex];
    const modalImage = document.getElementById('modalImage');
    const modalVideo = document.getElementById('modalVideo');
    
    if (media.type === 'image') {
        modalImage.src = media.source;
        modalImage.style.display = 'block';
        modalVideo.style.display = 'none';
        modalVideo.pause();
    } else {
        modalVideo.src = media.source;
        modalVideo.style.display = 'block';
        modalImage.style.display = 'none';
        modalVideo.load();
        modalVideo.play();
    }
}

// Keyboard navigation for tubewell media
document.addEventListener('keydown', function(event) {
    const modal = document.getElementById('mediaModal');
    if (modal.style.display === 'block' && tubewellMediaItems.length > 0) {
        if (event.key === 'ArrowLeft') {
            changeMedia(-1);
            event.preventDefault();
        } else if (event.key === 'ArrowRight') {
            changeMedia(1);
            event.preventDefault();
        } else if (event.key === 'Escape') {
            closeModal();
            event.preventDefault();
        }
    }
});

// Add modal navigation styles
const style = document.createElement('style');
style.textContent = `
    .modal-nav {
        position: absolute;
        top: 50%;
        width: 100%;
        display: none;
        justify-content: space-between;
        padding: 0 20px;
        transform: translateY(-50%);
        z-index: 1002;
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
`;
document.head.appendChild(style);
</script>
    
</body>
</html>
