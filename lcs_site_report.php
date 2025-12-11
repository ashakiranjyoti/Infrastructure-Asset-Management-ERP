<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Read filters
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$lcs_id = isset($_GET['lcs_id']) ? (int)$_GET['lcs_id'] : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Load all sites
$sites = [];
$sres = $conn->query("SELECT id, site_name FROM sites ");
if ($sres) { while ($r = $sres->fetch_assoc()) { $sites[] = $r; } }

// Load LCS for selected site only
$lcs_list = [];
if ($site_id > 0) {
    $lsql = "SELECT id, lcs_name FROM lcs WHERE site_id = ? ";
    if ($lstmt = $conn->prepare($lsql)) {
        $lstmt->bind_param('i', $site_id);
        $lstmt->execute();
        $lres = $lstmt->get_result();
        while ($lr = $lres->fetch_assoc()) { $lcs_list[] = $lr; }
        // If there is exactly one LCS for this site and none selected yet, auto-select it
        if (count($lcs_list) === 1 && $lcs_id === 0) {
            $lcs_id = (int)$lcs_list[0]['id'];
        }
    }

    
    }


// Load LCS master note exact-date map only
$lcs_note_on_date = [];
if ($lcs_id > 0 && $from_date !== '' && $to_date !== '') {
    $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
    if ($col && $col->num_rows > 0) {
        if ($mn2 = $conn->prepare('SELECT status_date, note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC')) {
            $mn2->bind_param('iss', $lcs_id, $from_date, $to_date);
            $mn2->execute();
            $rs2 = $mn2->get_result();
            while ($row = $rs2->fetch_assoc()) { $lcs_note_on_date[$row['status_date']] = $row; }
        }
    } else {
        if ($mn2 = $conn->prepare('SELECT DATE(updated_at) AS d, note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? AND DATE(updated_at) BETWEEN ? AND ? ORDER BY updated_at ASC')) {
            $mn2->bind_param('iss', $lcs_id, $from_date, $to_date);
            $mn2->execute();
            $rs2 = $mn2->get_result();
            while ($row = $rs2->fetch_assoc()) { $lcs_note_on_date[$row['d']] = $row; }
        }
    }
}

// Preload LCS master note media strictly by status_date for this LCS and range
$lcs_master_media_by_date = [];
if ($lcs_id > 0 && $from_date !== '' && $to_date !== '') {
    if ($mm = $conn->prepare("SELECT id, file_path, file_type, uploaded_by, uploaded_at, status_date FROM lcs_master_media WHERE lcs_id = ? AND status_date BETWEEN ? AND ? ORDER BY uploaded_at DESC")) {
        $mm->bind_param('iss', $lcs_id, $from_date, $to_date);
        $mm->execute();
        $mres = $mm->get_result();
        while ($row = $mres->fetch_assoc()) {
            $dk = $row['status_date'];
            if (!isset($lcs_master_media_by_date[$dk])) { $lcs_master_media_by_date[$dk] = []; }
            $lcs_master_media_by_date[$dk][] = $row;
        }
    }
}

// Preload LCS master note contributors (by status_date) after filters
$lcs_mn_contrib_by_date = [];
if ($lcs_id > 0 && $from_date !== '' && $to_date !== '') {
    $hasTbl = $conn->query("SHOW TABLES LIKE 'lcs_master_note_contributors'");
    if ($hasTbl && $hasTbl->num_rows > 0) {
        if ($st = $conn->prepare("SELECT status_date, contributor_name FROM lcs_master_note_contributors WHERE lcs_id = ? AND status_date BETWEEN ? AND ?")) {
            $st->bind_param('iss', $lcs_id, $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($row = $rs->fetch_assoc()) {
                $d = $row['status_date']; $nm = $row['contributor_name'];
                if (!isset($lcs_mn_contrib_by_date[$d])) { $lcs_mn_contrib_by_date[$d] = []; }
                if ($nm !== null && $nm !== '' && !in_array($nm, $lcs_mn_contrib_by_date[$d], true)) { $lcs_mn_contrib_by_date[$d][] = $nm; }
            }
        }
    }
}

// Query status data if all filters present
$rows_by_date = [];
if ($site_id > 0 && $lcs_id > 0 && $from_date !== '' && $to_date !== '') {
    // Load active LCS items
    $active_items = [];
    $ires = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ");
    if ($ires) { while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } }

    // Prefetch all rows up to the end date for this LCS/site
    $q = "SELECT h.*, s.site_name
          FROM lcs_status_history h
          JOIN sites s ON h.site_id = s.id
          WHERE h.site_id = ? AND h.lcs_id = ? AND h.status_date <= ?
          ";
    $stmt = $conn->prepare($q);
    $stmt->bind_param('iis', $site_id, $lcs_id, $to_date);
    $stmt->execute();
    $rs = $stmt->get_result();
    $rows_seq = [];
    while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

    // Build a set of dates that actually have changes within the range (including master note/media only)
    $chg = $conn->prepare("SELECT DISTINCT status_date FROM lcs_status_history WHERE site_id = ? AND lcs_id = ? AND status_date BETWEEN ? AND ? ");
    $chg->bind_param('iiss', $site_id, $lcs_id, $from_date, $to_date);
    $chg->execute();
    $crs = $chg->get_result();
    $change_dates = [];
    while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

    // Union: dates from LCS master notes (status_date if available, else DATE(updated_at))
    $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
    if ($col && $col->num_rows > 0) {
        if ($mn3 = $conn->prepare('SELECT DISTINCT status_date FROM lcs_master_notes WHERE lcs_id = ? AND status_date BETWEEN ? AND ?')) {
            $mn3->bind_param('iss', $lcs_id, $from_date, $to_date);
            $mn3->execute();
            $rs3 = $mn3->get_result();
            while ($row = $rs3->fetch_assoc()) { $change_dates[$row['status_date']] = true; }
        }
    } else {
        if ($mn3 = $conn->prepare('SELECT DISTINCT DATE(updated_at) AS d FROM lcs_master_notes WHERE lcs_id = ? AND DATE(updated_at) BETWEEN ? AND ?')) {
            $mn3->bind_param('iss', $lcs_id, $from_date, $to_date);
            $mn3->execute();
            $rs3 = $mn3->get_result();
            while ($row = $rs3->fetch_assoc()) { $change_dates[$row['d']] = true; }
        }
    }
    // Union: dates from LCS master media
    if ($mm2 = $conn->prepare('SELECT DISTINCT status_date FROM lcs_master_media WHERE lcs_id = ? AND status_date BETWEEN ? AND ?')) {
        $mm2->bind_param('iss', $lcs_id, $from_date, $to_date);
        $mm2->execute();
        $m2 = $mm2->get_result();
        while ($row = $m2->fetch_assoc()) { $change_dates[$row['status_date']] = true; }
    }

    // Build map of changed items per date (to highlight rows actually updated on that date)
    $changed_items_by_date = [];
    if ($ci = $conn->prepare("SELECT status_date, item_name FROM lcs_status_history WHERE site_id = ? AND lcs_id = ? AND status_date BETWEEN ? AND ?")) {
        $ci->bind_param('iiss', $site_id, $lcs_id, $from_date, $to_date);
        $ci->execute();
        $cir = $ci->get_result();
        while ($row = $cir->fetch_assoc()) {
            $d = $row['status_date'];
            $it = $row['item_name'];
            if (!isset($changed_items_by_date[$d])) { $changed_items_by_date[$d] = []; }
            $changed_items_by_date[$d][$it] = true;
        }
    }

    // Preload all LCS media for the date range strictly by their upload date
    $lcs_media_by_date_item = [];
    if ($media_stmt = $conn->prepare("\n        SELECT m.*, DATE(m.uploaded_at) AS media_date\n        FROM lcs_media m\n        WHERE m.lcs_id = ? AND DATE(m.uploaded_at) BETWEEN ? AND ?\n        ORDER BY m.uploaded_at DESC\n    ")) {
        $media_stmt->bind_param('iss', $lcs_id, $from_date, $to_date);
        $media_stmt->execute();
        $media_res = $media_stmt->get_result();
        while ($media = $media_res->fetch_assoc()) {
            $date_key = $media['media_date'];
            $item_key = $media['item_name'];
            if (!isset($lcs_media_by_date_item[$date_key])) {
                $lcs_media_by_date_item[$date_key] = [];
            }
            if (!isset($lcs_media_by_date_item[$date_key][$item_key])) {
                $lcs_media_by_date_item[$date_key][$item_key] = [];
            }
            $lcs_media_by_date_item[$date_key][$item_key][] = $media;
        }
    }

    // Preload item-wise updates people for By/With display per date and item (latest update per date+item)
    $lcs_updates_people_by_date_item = [];
    $has_updates_tbl = false; $tchk = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchk && $tchk->num_rows > 0) { $has_updates_tbl = true; }
    $has_uc_tbl = false; $tchk2 = $conn->query("SHOW TABLES LIKE 'update_contributors'"); if ($has_updates_tbl && $tchk2 && $tchk2->num_rows > 0) { $has_uc_tbl = true; }
    if ($has_updates_tbl) {
        $sql = "
            SELECT u.status_date, u.item_name, u.updated_by, uc.contributor_name
            FROM updates u
            LEFT JOIN update_contributors uc ON uc.update_id = u.id
            JOIN (
                SELECT status_date, entity_id, item_name, MAX(id) AS max_id
                FROM updates
                WHERE entity_type='lcs' AND entity_id = ? AND status_date BETWEEN ? AND ?
                GROUP BY status_date, entity_id, item_name
            ) x ON x.max_id = u.id
            WHERE u.entity_type='lcs'
        ";
        if ($st = $conn->prepare($sql)) {
            $st->bind_param('iss', $lcs_id, $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $d = $r['status_date']; $iname = $r['item_name'];
                $primary = $r['updated_by']; $contrib = $r['contributor_name'];
                if (!isset($lcs_updates_people_by_date_item[$d])) { $lcs_updates_people_by_date_item[$d] = []; }
                if (!isset($lcs_updates_people_by_date_item[$d][$iname])) { $lcs_updates_people_by_date_item[$d][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                if ($primary !== null && $primary !== '' && !in_array($primary, $lcs_updates_people_by_date_item[$d][$iname]['primaries'], true)) { $lcs_updates_people_by_date_item[$d][$iname]['primaries'][] = $primary; }
                if ($contrib !== null && $contrib !== '' && !in_array($contrib, $lcs_updates_people_by_date_item[$d][$iname]['contributors'], true)) { $lcs_updates_people_by_date_item[$d][$iname]['contributors'][] = $contrib; }
            }
        }
    }

    // Iterate dates and build snapshot using last-known values up to each date; output only on change dates
    $last_known = [];
    $dynamic_items = []; // item names that are NOT part of active_items but appear in history
    $p = 0; $n = count($rows_seq);
    $cur = strtotime($from_date);
    $end = strtotime($to_date);
    while ($cur !== false && $cur <= $end) {
        $d = date('Y-m-d', $cur);
        // Advance pointer and update last-known with rows of this day or earlier
        while ($p < $n && $rows_seq[$p]['status_date'] <= $d) {
            $rk = $rows_seq[$p];
            $last_known[$rk['item_name']] = $rk;
            if (!in_array($rk['item_name'], $active_items, true)) {
                $dynamic_items[$rk['item_name']] = true;
            }
            $p++;
        }
        if (isset($change_dates[$d])) {
            $rows_by_date[$d] = [];
            // Merge active items with dynamic/custom items discovered so far
            $all_items = $active_items;
            foreach ($dynamic_items as $din => $_v) { if (!in_array($din, $all_items, true)) { $all_items[] = $din; } }
            // Sort for consistent display
            sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($all_items as $iname) {
                if (isset($last_known[$iname])) {
                    $rows_by_date[$d][] = $last_known[$iname];
                } else {
                    $rows_by_date[$d][] = [
                        'item_name' => $iname,
                        'make_model' => '',
                        'size_capacity' => '',
                        'status' => '',
                        'remark' => '',
                        'created_by' => '—',
                        'updated_at' => null,
                        'site_name' => ''
                    ];
                }
            }
        }
        $cur = strtotime('+1 day', $cur);
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LCS Site Report</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .table-container { overflow-x: auto; }
        table.data-table { 
            width: 100%; 
            border-collapse: collapse; 
            border: 1px solid #d1d5db; 
        }
        table.data-table th, table.data-table td { 
            white-space: normal; 
            padding: 6px 8px; 
            font-size: 13px; 
            border-left: 1px solid #e5e7eb; 
            border-right: 1px solid #e5e7eb; 
            overflow-wrap: anywhere; 
            word-break: break-word; 
            vertical-align: top;
        }
        table.data-table td:nth-child(1) { text-align: center; }
        .date-title { margin: 1.5rem 0 0.5rem; color: #2d3748; }
        /* Highlight rows that changed on that date */
        tr.row-changed { background-color: #fff7e6; }
        
        .media-preview {
            max-width: 40px;
            max-height: 40px;
            border-radius: 4px;
            cursor: pointer;
            object-fit: cover;
            margin-right: 3px;
            margin-bottom: 3px;
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
            gap: 3px;
        }
        
        .col-media {
            width: 100px;
        }
    </style>
</head>
<body>
 <?php include('header.php'); ?>
<div class="container">
    <div class="card">
        <h2 style="color:#2d3748; margin-bottom:1rem;">🧰 LCS Site Report</h2>
        <form method="GET" class="site-form" style="margin-bottom: 1rem; display:grid; grid-template-columns: repeat(5, minmax(160px, 1fr)); gap:0.75rem; align-items:end;">
            <div class="form-group" >
                <label class="form-label">🏢 Site</label>
                <select name="site_id" class="form-control" onchange="this.form.submit()" >
                    <option value="0">-- Select Site --</option>
                    <?php foreach ($sites as $s): ?>
                        <option value="<?php echo (int)$s['id']; ?>" <?php echo $site_id == $s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['site_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">🧰 LCS</label>
                <select name="lcs_id" class="form-control" <?php echo $site_id ? '' : 'disabled'; ?> onchange="this.form.submit()">
                    <option value="0">-- Select LCS --</option>
                    <?php foreach ($lcs_list as $l): ?>
                        <option value="<?php echo (int)$l['id']; ?>" <?php echo $lcs_id == $l['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($l['lcs_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">From</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">To</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
            </div>
            <div class="btn-group form-group">
                <button type="submit" class="btn btn-primary">🔎 View</button>
            </div>
        </form>

        <?php if (!empty($rows_by_date)): ?>
            <div style="text-align:right; margin-bottom:0.5rem;">
                <a id="lcsQuickPdfLink" href="#" target="_blank" class="btn btn-sm btn-primary" style="text-decoration:none;">📄 Download PDF</a>
                <!-- <a id="lcsQuickExcelLink" href="#" target="_blank" class="btn btn-sm btn-success" style="text-decoration:none;">📊 Download Excel</a> -->
            </div>
            <?php foreach ($rows_by_date as $d => $rows): ?>
                <?php $note_row = $lcs_note_on_date[$d] ?? null; ?>
                <?php if (!empty($note_row) && isset($note_row['note']) && trim((string)$note_row['note']) !== ''): ?>
                <div class="card" style="margin-bottom:1rem; border:1px solid #e5e7eb; background-color:#f7fafc;">
                    <h4 style="margin:0 0 .5rem 0; color:#2d3748; font-size:1rem;">📝 LCS Master Note</h4>
                    <div style="white-space:pre-wrap; line-height:1.5; color:#2d3748; margin-bottom:.5rem;">
                        <?php echo nl2br(htmlspecialchars($note_row['note'])); ?>
                    </div>
                    <div style="font-size:.85rem; color:#4a5568;">
                        Updated by <strong><?php echo htmlspecialchars($note_row['updated_by'] ?? '—'); ?></strong>
                        on <?php echo isset($note_row['updated_at']) ? date('d M Y H:i', strtotime($note_row['updated_at'])) : '—'; ?>
                    </div>
                    <?php $lmnc = $lcs_mn_contrib_by_date[$d] ?? []; if (!empty($lmnc)): ?>
                        <div style="font-size:.85rem; color:#374151; margin-top:.25rem;">
                            <strong>Contributors:</strong> <?php echo htmlspecialchars(implode(', ', $lmnc)); ?>
                        </div>
                    <?php endif; ?>
                    <?php $mm = $lcs_master_media_by_date[$d] ?? []; if (!empty($mm)): ?>
                        <div style="margin-top:.5rem;">
                            <div style="font-weight:600; margin-bottom:.25rem;">Photo / Video</div>
                            <div class="media-grid">
                                <?php foreach ($mm as $m): ?>
                                    <div class="media-container">
                                        <?php if ($m['file_type'] === 'image'): ?>
                                            <img src="<?php echo htmlspecialchars($m['file_path']); ?>" class="media-preview" onclick="openModal('<?php echo htmlspecialchars($m['file_path']); ?>','image')" alt="Master media">
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
                </div>
                <?php endif; ?>
                <h3 class="date-title">Date: <?php echo htmlspecialchars($d); ?></h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="4%">Sr.No</th>
                                <th width="12%">Item</th>
                                <th width="12%">Make/Model</th>
                                <th width="7%">Size/Cap.</th>
                                <th width="8%">Status</th>
                                <th width="15%">Remark</th>
                                <th width="10%">Photo / Video</th>
                                <th width="8%">Updated By</th>
                                <th width="10%">Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $sr=1; foreach ($rows as $r): 
                                $isChanged = isset($changed_items_by_date[$d]) && isset($changed_items_by_date[$d][$r['item_name']]);
                                $item_media = $lcs_media_by_date_item[$d][$r['item_name']] ?? [];
                            ?>
                                <tr class="<?php echo $isChanged ? 'row-changed' : ''; ?>">
                                    <td><?php echo $sr++; ?></td>
                                    <td><?php echo htmlspecialchars($r['item_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['make_model'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['size_capacity'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['remark'] ?? ''); ?></td>
                                    <td class="col-media">
                                        <?php if (!empty($item_media)): ?>
                                            <div class="media-grid">
                                                <?php foreach ($item_media as $media): ?>
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
                                            <span style="color:#999; font-size:11px;">No media</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $byWith = $r['created_by'] ?? '—';
                                            $ppl = $lcs_updates_people_by_date_item[$d][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
                                            $p1 = $ppl['primaries'] ?? []; $p2 = $ppl['contributors'] ?? [];
                                            if (!empty($p1) || !empty($p2)) {
                                                $parts = [];
                                                if (!empty($p1)) { $parts[] = 'By: '.htmlspecialchars(implode(', ', $p1)); }
                                                if (!empty($p2)) { $parts[] = 'With: '.htmlspecialchars(implode(', ', $p2)); }
                                                $byWith = htmlspecialchars($byWith).' ['.implode(' | ', $parts).']';
                                            } else {
                                                $byWith = htmlspecialchars($byWith);
                                            }
                                            echo $byWith;
                                        ?>
                                    </td>
                                    <td><?php echo isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php elseif ($site_id && $lcs_id && $from_date && $to_date): ?>
            <div class="alert alert-info">No changes found for the selected LCS and date range.</div>
        <?php else: ?>
            <div class="alert alert-info">Select Site, LCS and date range to view the report.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for media preview -->
<div id="mediaModal" class="modal">
    <span class="close-modal" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="modalImage">
    <video class="modal-content" id="modalVideo" controls style="display:none;"></video>
</div>

<script>
(function(){
    var form = document.querySelector('form.site-form') || document.querySelector('form');
    var quickLink = document.getElementById('lcsQuickPdfLink');
    var quickExcelLink = document.getElementById('lcsQuickExcelLink');
    
    function buildUrl(type){
        var site = form.querySelector('select[name="site_id"]').value;
        var lcs = form.querySelector('select[name="lcs_id"]').value;
        var from = form.querySelector('input[name="from_date"]').value;
        var to = form.querySelector('input[name="to_date"]').value;
        if (!site || !lcs || !from || !to) return null;
        
        var url = '';
        if (type === 'pdf') {
            url = 'generate_lcs_site_report.php?site_id=' + encodeURIComponent(site)
                + '&lcs_id=' + encodeURIComponent(lcs)
                + '&from_date=' + encodeURIComponent(from)
                + '&to_date=' + encodeURIComponent(to);
        } else if (type === 'excel') {
            url = 'generate_lcs_site_report_excel.php?site_id=' + encodeURIComponent(site)
                + '&lcs_id=' + encodeURIComponent(lcs)
                + '&from_date=' + encodeURIComponent(from)
                + '&to_date=' + encodeURIComponent(to);
        }
        return url;
    }
    
    if (quickLink) {
        var updateQuick = function(){
            var pdfUrl = buildUrl('pdf');
            quickLink.href = pdfUrl || '#';
            quickLink.style.opacity = pdfUrl ? '1' : '0.5';
            quickLink.onclick = function(e){ if (!pdfUrl) { e.preventDefault(); alert('Select Site, LCS and From/To dates first'); } }
        };
        form.addEventListener('change', updateQuick);
        form.addEventListener('input', updateQuick);
        updateQuick();
    }
})();

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
