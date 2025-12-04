<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Check if lcs_id parameter is set
if (!isset($_GET['lcs_id'])) {
    die("Invalid request. LCS ID parameter is required.");
}

$lcs_id = (int)$_GET['lcs_id'];

// Get LCS details with error handling
$lcs_sql = "SELECT l.*, s.site_name 
                 FROM lcs l 
                 JOIN sites s ON l.site_id = s.id 
                 WHERE l.id = ?";
$stmt = $conn->prepare($lcs_sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $lcs_id);
$stmt->execute();
$lcs_result = $stmt->get_result();
$lcs = $lcs_result->fetch_assoc();

// Check if LCS exists
if (!$lcs) {
    die("LCS not found with ID: " . $lcs_id);
}

// Determine unified latest display date = max of item status, master notes, master media
$display_date = '';
// Latest item status date
$stmt = $conn->prepare("SELECT status_date FROM lcs_status_history WHERE lcs_id = ? ORDER BY status_date DESC LIMIT 1");
$stmt->bind_param('i', $lcs_id);
$stmt->execute();
$ld = $stmt->get_result()->fetch_assoc();
if ($ld && !empty($ld['status_date'])) { $display_date = $ld['status_date']; }
// Latest master note date (supports schema without status_date)
$has_sd_for_calc = false;
$colx = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
if ($colx && $colx->num_rows > 0) { $has_sd_for_calc = true; }
if ($has_sd_for_calc) {
    if ($mnmax = $conn->prepare('SELECT MAX(status_date) AS mx FROM lcs_master_notes WHERE lcs_id = ?')) {
        $mnmax->bind_param('i', $lcs_id);
        $mnmax->execute();
        $mr = $mnmax->get_result()->fetch_assoc();
        if (!empty($mr['mx']) && $mr['mx'] > $display_date) { $display_date = $mr['mx']; }
    }
} else {
    if ($mnmax = $conn->prepare('SELECT MAX(DATE(updated_at)) AS mx FROM lcs_master_notes WHERE lcs_id = ?')) {
        $mnmax->bind_param('i', $lcs_id);
        $mnmax->execute();
        $mr = $mnmax->get_result()->fetch_assoc();
        if (!empty($mr['mx']) && $mr['mx'] > $display_date) { $display_date = $mr['mx']; }
    }
}
// Latest master media date
if ($mmmax = $conn->prepare('SELECT MAX(status_date) AS mx FROM lcs_master_media WHERE lcs_id = ?')) {
    $mmmax->bind_param('i', $lcs_id);
    $mmmax->execute();
    $mr2 = $mmmax->get_result()->fetch_assoc();
    if (!empty($mr2['mx']) && $mr2['mx'] > $display_date) { $display_date = $mr2['mx']; }
}

// Load LCS master note only for the display date
$master_note_row = null;
$has_sd = false;
$col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
if ($col && $col->num_rows > 0) { $has_sd = true; }
if ($display_date !== '') {
    if ($has_sd) {
        if ($mn = $conn->prepare('SELECT note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? AND status_date = ? ORDER BY updated_at DESC LIMIT 1')) {
            $mn->bind_param('is', $lcs_id, $display_date);
            $mn->execute();
            $master_note_row = $mn->get_result()->fetch_assoc();
        }
    } else {
        if ($mn = $conn->prepare('SELECT note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? AND DATE(updated_at) = ? ORDER BY updated_at DESC LIMIT 1')) {
            $mn->bind_param('is', $lcs_id, $display_date);
            $mn->execute();
            $master_note_row = $mn->get_result()->fetch_assoc();
        }
    }
}

// Load master note media for the display date (stored in lcs_master_media)
$master_media = [];
if ($display_date !== '') {
    if ($mm = $conn->prepare("SELECT id, file_path, file_type, uploaded_by, uploaded_at FROM lcs_master_media WHERE lcs_id = ? AND status_date = ? ORDER BY uploaded_at DESC")) {
        $mm->bind_param('is', $lcs_id, $display_date);
        $mm->execute();
        $mres = $mm->get_result();
        while ($row = $mres->fetch_assoc()) { $master_media[] = $row; }
    }
}

// Last updated timestamp across all rows for this LCS
$last_updated_at = '—';
if ($mxs = $conn->prepare("SELECT MAX(updated_at) AS mx FROM lcs_status_history WHERE lcs_id = ?")) {
    $mxs->bind_param('i', $lcs_id);
    $mxs->execute();
    $mxr = $mxs->get_result()->fetch_assoc();
    if (!empty($mxr['mx'])) { $last_updated_at = date('d M Y H:i', strtotime($mxr['mx'])); }
}

// Fetch rows for the display date if any
$status_rows = [];
$status_by_item = [];
if ($display_date !== '') {
    $rows_sql = "SELECT * FROM lcs_status_history WHERE lcs_id = ? AND status_date = ? ";
    $stmt = $conn->prepare($rows_sql);
    $stmt->bind_param("is", $lcs_id, $display_date);
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
if ($im = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ")) {
    while ($row = $im->fetch_assoc()) { $all_items[] = $row['item_name']; }
}

// For items missing on the latest date, fallback to last known previous record up to display_date
$fallback_by_item = [];
if (!empty($all_items)) {
    foreach ($all_items as $iname) {
        if (!isset($status_by_item[$iname])) {
            if ($ps = $conn->prepare("SELECT * FROM lcs_status_history WHERE lcs_id = ? AND item_name = ? AND status_date <= ? ORDER BY status_date DESC LIMIT 1")) {
                $ps->bind_param('iss', $lcs_id, $iname, $display_date);
                $ps->execute();
                if ($pr = $ps->get_result()->fetch_assoc()) { $fallback_by_item[$iname] = $pr; }
            }
        }
    }
}

// Load additional custom items (spares) not in lcs_item
// Show the latest row per spare item_name up to display_date (if today's not present, fallback to previous)
$extra_rows = [];
if ($display_date !== '') {
    if ($ex = $conn->prepare(
        "SELECT t.* FROM lcs_status_history t
          JOIN (
            SELECT item_name, MAX(status_date) AS sd
            FROM lcs_status_history
            WHERE lcs_id = ? AND status_date <= ? AND item_name NOT IN (SELECT item_name FROM lcs_item WHERE is_active = 1)
            GROUP BY item_name
          ) x ON t.lcs_id = ? AND t.item_name = x.item_name AND t.status_date = x.sd
         ORDER BY t.item_name"
    )) {
        $ex->bind_param('isi', $lcs_id, $display_date, $lcs_id);
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
    <title>LCS Parameters - <?php echo htmlspecialchars($lcs['lcs_name'] ?? 'Site LCS'); ?></title>
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
    </style>
</head>
<body>
     <?php include('header.php'); ?>

    <div class="container">
        <div style="text-align:right; margin-bottom: 1rem;">
            <a href="view_site.php?site_id=<?php echo (int)$lcs['site_id']; ?>" class="btn btn-secondary">← Back</a>
        </div>
        <div class="tubewell-header">
            <h1><?php echo htmlspecialchars($lcs['lcs_name'] ?? 'Site LCS'); ?></h1>
            <p>Site: <?php echo htmlspecialchars($lcs['site_name']); ?></p>
            <p style="margin-top:0.5rem; font-size:0.95rem; opacity:0.95;">Last Updated: <strong><?php echo $last_updated_at; ?></strong></p>
        </div>

        <div class="card" style="margin-top:-1rem; margin-bottom: 1.5rem;">
            <h2 style="color: #2d3748; margin-bottom: .5rem;">📝 Master Note</h2>
            <?php if (!empty($master_note_row) && trim((string)$master_note_row['note']) !== ''): ?>
                <div style="white-space:pre-wrap; line-height:1.5; color:#2d3748;"><?php echo nl2br(htmlspecialchars($master_note_row['note'])); ?></div>
                <div style="margin-top:.5rem; font-size:.85rem; color:#4a5568;">
                    Updated by <strong><?php echo htmlspecialchars($master_note_row['updated_by'] ?? '—'); ?></strong>
                    on <?php echo date('d M Y H:i', strtotime($master_note_row['updated_at'])); ?>
                    <?php
                    // Show LCS master note contributors for the display date (if table exists)
                    $mn_contribs = [];
                    $colC = $conn->query("SHOW TABLES LIKE 'lcs_master_note_contributors'");
                    if ($colC && $colC->num_rows > 0 && $display_date) {
                        if ($stc = $conn->prepare("SELECT contributor_name FROM lcs_master_note_contributors WHERE lcs_id = ? AND status_date = ? ORDER BY contributor_name")) {
                            $stc->bind_param('is', $lcs_id, $display_date);
                            $stc->execute();
                            $rc = $stc->get_result();
                            while ($cr = $rc->fetch_assoc()) { if (!empty($cr['contributor_name'])) { $mn_contribs[] = $cr['contributor_name']; } }
                        }
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
                <div style="color:#718096;">No master note added for this LCS.</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="color: #2d3748; margin-bottom: 1.5rem;">📋 LCS Information</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <p><strong>📍 Address:</strong> <?php echo htmlspecialchars($lcs['tw_address']); ?></p>
                    <p><strong>👤 Incharge:</strong> <?php echo htmlspecialchars($lcs['incharge_name']); ?></p>
                    <p><strong>📱 SIM Number:</strong> <?php echo htmlspecialchars($lcs['sim_no'] ?? '—'); ?></p>
                </div>
                <div>
                    <p><strong>🌐 Latitude:</strong> <?php echo htmlspecialchars($lcs['latitude']); ?></p>
                    <p><strong>🌐 Longitude:</strong> <?php echo htmlspecialchars($lcs['longitude']); ?></p>
                    <p><strong>📅 Installation Date:</strong> <?php echo htmlspecialchars($lcs['installation_date']); ?></p>
                </div>
            </div>
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
                            <th width="10%">Size/Capacity</th>
                            <th width="10%">Status</th>
                            <th width="15%">Remark</th>
                            <th width="12%">Photo / Video</th>
                            <th width="8%">Updated By</th>
                            <th width="8%">Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_items as $item_name): 
                            $row = $status_by_item[$item_name] ?? ($fallback_by_item[$item_name] ?? null);
                            $status_value = $row['status'] ?? '';
                            $status_class = $status_value !== '' ? ('status-' . strtolower($status_value)) : '';
                            $make_model = $row['make_model'] ?? '';
                            $size_capacity = $row['size_capacity'] ?? '';
                            $remark = $row['remark'] ?? '';
                            $created_by = $row['created_by'] ?? '—';
                            $updated_at_cell = (isset($row['updated_at']) && $row['updated_at']) ? date('d M Y H:i', strtotime($row['updated_at'])) : '—';
                            // Fetch latest update contributors for this LCS item & date
                            $with_list = [];
                            if ($st = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='lcs' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                $st->bind_param('iss', $lcs_id, $item_name, $display_date);
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
                            
                            // Get media for this LCS item for display date only
                            $media_stmt = $conn->prepare("SELECT * FROM lcs_media WHERE lcs_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $lcs_id, $item_name, $display_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            // Fetch latest update contributors for this extra item & date
                            $by_extra = $row['created_by'] ?? '—';
                            $with_list2 = [];
                            if ($stx = $conn->prepare("SELECT id, updated_by FROM updates WHERE entity_type='lcs' AND entity_id=? AND item_name=? AND status_date=? ORDER BY updated_at DESC LIMIT 1")) {
                                $stx->bind_param('iss', $lcs_id, $row['item_name'], $display_date);
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
                            
                            // Get media for this extra LCS item for display date only
                            $media_stmt = $conn->prepare("SELECT * FROM lcs_media WHERE lcs_id = ? AND item_name = ? AND status_date = ? ORDER BY uploaded_at DESC");
                            $media_stmt->bind_param('iss', $lcs_id, $row['item_name'], $display_date);
                            $media_stmt->execute();
                            $all_media = $media_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                    <a href="add_lcs_parameters.php?lcs_id=<?php echo $lcs_id; ?>" class="btn btn-primary">➕ Add Today's Status</a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <!-- <div class="btn-group" style="justify-content: center; margin-top: 2rem; gap: 1rem;">
            <a href="add_lcs_parameters.php?lcs_id=<?php echo $lcs_id; ?>" class="btn btn-primary">➕ Add Today's Status</a>
            <a href="lcs_status_history.php?lcs_id=<?php echo $lcs_id; ?>" class="btn btn-info">📊 View History</a>
        </div> -->
        
    </div>

    <!-- Modal for media preview -->
    <div id="mediaModal" class="modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
        <video class="modal-content" id="modalVideo" controls style="display:none;"></video>
    </div>

    <script>
    // Media modal functions
    function openModal(src, type) {
        var modal = document.getElementById('mediaModal');
        var modalImage = document.getElementById('modalImage');
        var modalVideo = document.getElementById('modalVideo');
        
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
    }

    function closeModal() {
        var modal = document.getElementById('mediaModal');
        var modalVideo = document.getElementById('modalVideo');
        
        modal.style.display = 'none';
        if (modalVideo) {
            modalVideo.pause();
            modalVideo.currentTime = 0;
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById('mediaModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
    
</body>
</html>