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
$tubewell_id = isset($_GET['tubewell_id']) ? (int)$_GET['tubewell_id'] : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Load all sites
$sites = [];
$sres = $conn->query("SELECT id, site_name FROM sites ");
if ($sres) { while ($r = $sres->fetch_assoc()) { $sites[] = $r; } }

// Load tubewells for selected site only
$tubewells = [];
if ($site_id > 0) {
    $tsql = "SELECT id, tubewell_name FROM tubewells WHERE site_id = ? ";
    $tstmt = $conn->prepare($tsql);
    $tstmt->bind_param('i', $site_id);
    $tstmt->execute();
    $tres = $tstmt->get_result();
    while ($tr = $tres->fetch_assoc()) { $tubewells[] = $tr; }
}

// Query status data if all filters present
$rows_by_date = [];
if ($site_id > 0 && $tubewell_id > 0 && $from_date !== '' && $to_date !== '') {
    // Load active items (to show all items every day)
    $active_items = [];
    $ires = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ");
    if ($ires) { while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } }

    // Prefetch all rows up to the end date for this tubewell/site
    $q = "SELECT sh.*, s.site_name, tw.tubewell_name
          FROM status_history sh
          JOIN sites s ON sh.site_id = s.id
          JOIN tubewells tw ON sh.tubewell_id = tw.id
          WHERE sh.site_id = ? AND sh.tubewell_id = ? AND sh.status_date <= ?
          ORDER BY sh.status_date ASC, sh.item_name ASC";
    $stmt = $conn->prepare($q);
    $stmt->bind_param('iis', $site_id, $tubewell_id, $to_date);
    $stmt->execute();
    $rs = $stmt->get_result();
    $rows_seq = [];
    while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

    // Build a set of dates that actually have changes within the range (including master note/media only)
    $chg = $conn->prepare("SELECT DISTINCT status_date FROM status_history WHERE site_id = ? AND tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
    $chg->bind_param('iiss', $site_id, $tubewell_id, $from_date, $to_date);
    $chg->execute();
    $crs = $chg->get_result();
    $change_dates = [];
    while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

    // Union: dates from master notes
    if ($mn_stmt2 = $conn->prepare("SELECT DISTINCT status_date FROM tubewell_master_notes WHERE tubewell_id = ? AND status_date BETWEEN ? AND ?")) {
        $mn_stmt2->bind_param('iss', $tubewell_id, $from_date, $to_date);
        $mn_stmt2->execute();
        $mn_res2 = $mn_stmt2->get_result();
        while ($row = $mn_res2->fetch_assoc()) { $change_dates[$row['status_date']] = true; }
    }
    // Union: dates from master media
    if ($mm_stmt2 = $conn->prepare("SELECT DISTINCT status_date FROM tubewell_master_media WHERE tubewell_id = ? AND status_date BETWEEN ? AND ?")) {
        $mm_stmt2->bind_param('iss', $tubewell_id, $from_date, $to_date);
        $mm_stmt2->execute();
        $mm_res2 = $mm_stmt2->get_result();
        while ($row = $mm_res2->fetch_assoc()) { $change_dates[$row['status_date']] = true; }
    }

    // Load master notes only for dates in range (exact date display)
    $master_notes_by_date = [];
    if ($mn_stmt = $conn->prepare("SELECT status_date, note, updated_by, updated_at FROM tubewell_master_notes WHERE tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC")) {
        $mn_stmt->bind_param('iss', $tubewell_id, $from_date, $to_date);
        $mn_stmt->execute();
        $mn_res = $mn_stmt->get_result();
        while ($mn_row = $mn_res->fetch_assoc()) {
            $master_notes_by_date[$mn_row['status_date']] = $mn_row;
        }
    }

    // Preload master note contributors by date
    $mn_contrib_by_date = [];
    $chk_mnc = $conn->query("SHOW TABLES LIKE 'tubewell_master_note_contributors'");
    if ($chk_mnc && $chk_mnc->num_rows > 0) {
        if ($stc = $conn->prepare("SELECT status_date, contributor_name FROM tubewell_master_note_contributors WHERE tubewell_id = ? AND status_date BETWEEN ? AND ?")) {
            $stc->bind_param('iss', $tubewell_id, $from_date, $to_date);
            $stc->execute();
            $rsc = $stc->get_result();
            while ($row = $rsc->fetch_assoc()) {
                $d = $row['status_date']; $nm = $row['contributor_name'];
                if (!isset($mn_contrib_by_date[$d])) { $mn_contrib_by_date[$d] = []; }
                if ($nm !== null && $nm !== '' && !in_array($nm, $mn_contrib_by_date[$d], true)) { $mn_contrib_by_date[$d][] = $nm; }
            }
        }
    }

    // Preload master note media strictly by status_date for this tubewell and range
    $master_media_by_date = [];
    if ($mm_stmt = $conn->prepare("SELECT id, file_path, file_type, uploaded_by, uploaded_at, status_date FROM tubewell_master_media WHERE tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY uploaded_at DESC")) {
        $mm_stmt->bind_param('iss', $tubewell_id, $from_date, $to_date);
        $mm_stmt->execute();
        $mm_res = $mm_stmt->get_result();
        while ($m = $mm_res->fetch_assoc()) {
            $dk = $m['status_date'];
            if (!isset($master_media_by_date[$dk])) { $master_media_by_date[$dk] = []; }
            $master_media_by_date[$dk][] = $m;
        }
    }

    // Build map of changed items per date for highlighting
    $changed_items_by_date = [];
    if ($ci = $conn->prepare('SELECT status_date, item_name FROM status_history WHERE site_id = ? AND tubewell_id = ? AND status_date BETWEEN ? AND ?')) {
        $ci->bind_param('iiss', $site_id, $tubewell_id, $from_date, $to_date);
        $ci->execute();
        $cir = $ci->get_result();
        while ($row = $cir->fetch_assoc()) {
            $d = $row['status_date'];
            $it = $row['item_name'];
            if (!isset($changed_items_by_date[$d])) { $changed_items_by_date[$d] = []; }
            $changed_items_by_date[$d][$it] = true;
        }
    }

    // Preload all media for the date range strictly by their upload date
    $media_by_date_item = [];
    if ($media_stmt = $conn->prepare("\n        SELECT m.*, DATE(m.uploaded_at) AS media_date\n        FROM media_uploads m\n        WHERE m.tubewell_id = ? AND DATE(m.uploaded_at) BETWEEN ? AND ?\n        ORDER BY m.uploaded_at DESC\n    ")) {
        $media_stmt->bind_param('iss', $tubewell_id, $from_date, $to_date);
        $media_stmt->execute();
        $media_res = $media_stmt->get_result();
        while ($media = $media_res->fetch_assoc()) {
            $date_key = $media['media_date'];
            $item_key = $media['item_name'];
            if (!isset($media_by_date_item[$date_key])) {
                $media_by_date_item[$date_key] = [];
            }
            if (!isset($media_by_date_item[$date_key][$item_key])) {
                $media_by_date_item[$date_key][$item_key] = [];
            }
            $media_by_date_item[$date_key][$item_key][] = $media;
        }
    }

    // Preload item-wise updates people for By/With display per date and item (latest update per date+item)
    $updates_people_by_date_item = [];
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
                WHERE entity_type='tubewell' AND entity_id = ? AND status_date BETWEEN ? AND ?
                GROUP BY status_date, entity_id, item_name
            ) x ON x.max_id = u.id
            WHERE u.entity_type='tubewell'
        ";
        if ($st = $conn->prepare($sql)) {
            $st->bind_param('iss', $tubewell_id, $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $d = $r['status_date']; $iname = $r['item_name'];
                $primary = $r['updated_by']; $contrib = $r['contributor_name'];
                if (!isset($updates_people_by_date_item[$d])) { $updates_people_by_date_item[$d] = []; }
                if (!isset($updates_people_by_date_item[$d][$iname])) { $updates_people_by_date_item[$d][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                if ($primary !== null && $primary !== '' && !in_array($primary, $updates_people_by_date_item[$d][$iname]['primaries'], true)) { $updates_people_by_date_item[$d][$iname]['primaries'][] = $primary; }
                if ($contrib !== null && $contrib !== '' && !in_array($contrib, $updates_people_by_date_item[$d][$iname]['contributors'], true)) { $updates_people_by_date_item[$d][$iname]['contributors'][] = $contrib; }
            }
        }
    }

    // Iterate dates and build snapshot using last-known values up to each date; output only on change dates
    $last_known = [];
    $p = 0; $n = count($rows_seq);
    $cur = strtotime($from_date);
    $end = strtotime($to_date);

    while ($cur !== false && $cur <= $end) {
        $d = date('Y-m-d', $cur);
        // Advance pointer and update last-known with rows of this day
        while ($p < $n && $rows_seq[$p]['status_date'] <= $d) {
            $rk = $rows_seq[$p];
            $last_known[$rk['item_name']] = $rk;
            $p++;
        }
        if (isset($change_dates[$d])) {
            $rows_by_date[$d] = [];
            foreach ($active_items as $iname) {
                if (isset($last_known[$iname])) {
                    $rows_by_date[$d][] = $last_known[$iname];
                } else {
                    $rows_by_date[$d][] = [
                        'item_name' => $iname,
                        'make_model' => '',
                        'size_capacity' => '',
                        'status' => '',
                        'check_hmi_local' => 0,
                        'check_web' => 0,
                        'remark' => '',
                        'created_by' => '—',
                        'updated_at' => null,
                        'site_name' => '',
                        'tubewell_name' => ''
                    ];
                }
            }
            // Append custom items (not in items_master) that exist as of this date
            foreach ($last_known as $iname => $row) {
                if (!in_array($iname, $active_items, true)) {
                    $rows_by_date[$d][] = $row;
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
    <title>Site Report</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .table-container { overflow-x: auto; }
        table.data-table { width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; }
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
        table.data-table td:nth-child(1),
        table.data-table td:nth-child(6),
        table.data-table td:nth-child(7) { text-align: center; }
        /* Highlight rows changed on that date */
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
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <h2 style="color:#2d3748;">📄 Site Report</h2>
                <p style="color:#718096;">Select Site, Tubewell and From-To dates to view entries.</p>
            </div>

            <form method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end; margin-bottom:1.5rem;">
                <div class="form-group" style="min-width:100px; flex:1;">
                    <label class="form-label">Site</label>
                    <select name="site_id" class="form-control" onchange="this.form.submit()" required>
                        <option value="">-- Select Site --</option>
                        <?php foreach ($sites as $s): ?>
                        <option value="<?php echo (int)$s['id']; ?>" <?php echo $site_id===(int)$s['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($s['site_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="min-width:100px; flex:1;">
                    <label class="form-label">Tubewell</label>
                    <select name="tubewell_id" class="form-control" <?php echo $site_id>0?'':'disabled'; ?> required>
                        <option value="">-- Select Tubewell --</option>
                        <?php foreach ($tubewells as $tw): ?>
                        <option value="<?php echo (int)$tw['id']; ?>" <?php echo $tubewell_id===(int)$tw['id']?'selected':''; ?>>
                            <?php echo htmlspecialchars($tw['tubewell_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="min-width:180px;">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>" required>
                </div>

                <div class="form-group" style="min-width:180px;">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>" required>
                </div>

                <div class="form-group" style="min-width:140px; display:flex; gap:0.5rem; align-items:center;">
                    <button type="submit" class="btn btn-primary" style="flex:1">🔎 View</button>
                </div>
            </form>

            <?php if (!empty($rows_by_date)): ?>
                <div style="text-align:right; margin-bottom:0.5rem;">
                    <a id="quickPdfLink" href="#" target="_blank" class="btn btn-sm btn-primary" style="text-decoration:none;">📄 Download PDF</a>
                    <!-- <a hidden id="quickExcelLink" href="#" target="_blank" class="btn btn-sm btn-success" style="text-decoration:none;">📊 Download Excel</a> -->
                </div>
                <?php foreach ($rows_by_date as $d => $rows): ?>
                <div class="table-container" style="margin-bottom:1.5rem;">
                    <h3 style="margin-bottom:0.75rem; color:#4a5568;">Date: <?php echo date('d M Y', strtotime($d)); ?> (<?php echo count($rows); ?> items)</h3>
                    
                    <?php $mn = $master_notes_by_date[$d] ?? null; if (!empty($mn) && trim((string)($mn['note'] ?? '')) !== ''): ?>
                        <div class="card" style="margin-bottom:1rem; border:1px solid #e5e7eb; background-color:#f7fafc;">
                            <h4 style="margin:0 0 .5rem 0; color:#2d3748; font-size:1rem;">📝 Master Note</h4>
                            <div style="white-space:pre-wrap; line-height:1.5; color:#2d3748; margin-bottom:.5rem;">&nbsp;<?php echo nl2br(htmlspecialchars($mn['note'])); ?></div>
                            <div style="font-size:.85rem; color:#4a5568;">
                                Updated by <strong><?php echo htmlspecialchars($mn['updated_by'] ?? '—'); ?></strong>
                                on <?php echo isset($mn['updated_at']) ? date('d M Y H:i', strtotime($mn['updated_at'])) : '—'; ?>
                            </div>
                            <?php $mnc = $mn_contrib_by_date[$d] ?? []; if (!empty($mnc)): ?>
                                <div style="font-size:.85rem; color:#374151; margin-top:.25rem;">
                                    <strong>Contributors:</strong> <?php echo htmlspecialchars(implode(', ', $mnc)); ?>
                                </div>
                            <?php endif; ?>
                            <?php $mm = $master_media_by_date[$d] ?? []; if (!empty($mm)): ?>
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
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="4%">Sr.No</th>
                                <th width="12%">Item Name</th>
                                <th width="12%">Make/Model</th>
                                <th width="7%">Size/Cap.</th>
                                <th width="8%">Status</th>
                                <th width="5%">HMI/Loc</th>
                                <th width="5%">Web</th>
                                <th width="15%">Remark</th>
                                <th width="10%">Photo / Video</th>
                                <th width="8%">Updated By</th>
                                <th width="10%">Updated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i=1; foreach ($rows as $r): 
                                $isChanged = isset($changed_items_by_date[$d]) && isset($changed_items_by_date[$d][$r['item_name']]);
                                $item_media = $media_by_date_item[$d][$r['item_name']] ?? [];
                            ?>
                            <tr class="<?php echo $isChanged ? 'row-changed' : ''; ?>">
                                <td style="text-align:center;">&nbsp;<?php echo $i++; ?></td>
                                <td><strong><?php echo htmlspecialchars($r['item_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['make_model']); ?></td>
                                <td><?php echo htmlspecialchars($r['size_capacity']); ?></td>
                                <td><?php echo htmlspecialchars($r['status']); ?></td>
                                <td style="text-align:center;">&nbsp;<?php echo (((int)($r['check_hmi_local'] ?? 0)) === 1) ? '✅' : ((((int)($r['check_hmi_local'] ?? 0)) === 2) ? '❌' : '—'); ?></td>
                                <td style="text-align:center;">&nbsp;<?php echo (((int)($r['check_web'] ?? 0)) === 1) ? '✅' : ((((int)($r['check_web'] ?? 0)) === 2) ? '❌' : '—'); ?></td>
                                <td><?php echo htmlspecialchars($r['remark']); ?></td>
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
                                        $ppl = $updates_people_by_date_item[$d][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
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
            <?php elseif ($site_id>0 && $tubewell_id>0 && $from_date!=='' && $to_date!==''): ?>
                <div style="text-align:center; color:#718096; padding:2rem;">
                    <div style="font-size:2rem;">📭</div>
                    <div>No records found in the selected range.</div>
                </div>
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
        var form = document.querySelector('form');
        var quickLink = document.getElementById('quickPdfLink');
        var quickExcelLink = document.getElementById('quickExcelLink');

        function buildUrl(type){
            var site = form.querySelector('select[name="site_id"]').value;
            var tw = form.querySelector('select[name="tubewell_id"]').value;
            var from = form.querySelector('input[name="from_date"]').value;
            var to = form.querySelector('input[name="to_date"]').value;
            if (!site || !tw || !from || !to) return null;
            
            var url = '';
            if (type === 'pdf') {
                url = 'generate_site_report.php?site_id=' + encodeURIComponent(site)
                    + '&tubewell_id=' + encodeURIComponent(tw)
                    + '&from_date=' + encodeURIComponent(from)
                    + '&to_date=' + encodeURIComponent(to);
            } else if (type === 'excel') {
                url = 'generate_site_report_excel.php?site_id=' + encodeURIComponent(site)
                    + '&tubewell_id=' + encodeURIComponent(tw)
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
        
        quickLink.onclick = function(e){ 
            if (!pdfUrl) {
                e.preventDefault(); 
                alert('Select Site, Tubewell and From/To dates first'); 
            } else {
                e.preventDefault(); // Prevent default download behavior
                window.open(pdfUrl, '_blank'); // Open in new tab
            }
        }
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
