<?php
include 'db_config.php';
require_once('TCPDF-main/tcpdf.php');

$sel_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
if ($sel_user === '' || $from_date === '' || $to_date === '') { 
    die('Invalid request. user, from_date and to_date are required.'); 
}
// Initialize maps used across the report
$tw_hl = [];
$lcs_hl = [];
$allowed_dates = [];
$tw_update_hl = [];
$lcs_update_hl = [];
$tw_updates_people_by_date_item = [];
$lcs_updates_people_by_date_item = [];
$tw_updates_people_by_date = [];
$lcs_updates_people_by_date = [];

// Preload updates people maps (entity-level and item-level) using only the latest update per (date, entity, item)
$has_updates_tbl = false; $tchk = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchk && $tchk->num_rows > 0) { $has_updates_tbl = true; }
$has_uc_tbl = false; $tchk2 = $conn->query("SHOW TABLES LIKE 'update_contributors'"); if ($tchk2 && $tchk2->num_rows > 0) { $has_uc_tbl = true; }
if ($has_updates_tbl) {
    // LCS
    $sqlL = "
        SELECT u.status_date, u.entity_id, u.item_name, u.updated_by, uc.contributor_name
        FROM updates u
        LEFT JOIN update_contributors uc ON uc.update_id = u.id
        JOIN (
            SELECT status_date, entity_id, item_name, MAX(id) AS max_id
            FROM updates
            WHERE entity_type='lcs' AND status_date BETWEEN ? AND ?
            GROUP BY status_date, entity_id, item_name
        ) x ON x.max_id = u.id
        WHERE u.entity_type='lcs'
    ";
    if ($st = $conn->prepare($sqlL)) {
        $st->bind_param('ss', $from_date, $to_date);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) {
            $d = $r['status_date']; $lid = (int)$r['entity_id']; $iname = $r['item_name'];
            $primary = $r['updated_by']; $contrib = $r['contributor_name'];
            if (!isset($lcs_updates_people_by_date[$d])) { $lcs_updates_people_by_date[$d] = []; }
            if (!isset($lcs_updates_people_by_date[$d][$lid])) { $lcs_updates_people_by_date[$d][$lid] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $lcs_updates_people_by_date[$d][$lid]['primaries'], true)) { $lcs_updates_people_by_date[$d][$lid]['primaries'][] = $primary; }
            if (!isset($lcs_updates_people_by_date_item[$d])) { $lcs_updates_people_by_date_item[$d] = []; }
            if (!isset($lcs_updates_people_by_date_item[$d][$lid])) { $lcs_updates_people_by_date_item[$d][$lid] = []; }
            if (!isset($lcs_updates_people_by_date_item[$d][$lid][$iname])) { $lcs_updates_people_by_date_item[$d][$lid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $lcs_updates_people_by_date_item[$d][$lid][$iname]['primaries'], true)) { $lcs_updates_people_by_date_item[$d][$lid][$iname]['primaries'][] = $primary; }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $lcs_updates_people_by_date[$d][$lid]['contributors'], true)) { $lcs_updates_people_by_date[$d][$lid]['contributors'][] = $contrib; }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $lcs_updates_people_by_date_item[$d][$lid][$iname]['contributors'], true)) { $lcs_updates_people_by_date_item[$d][$lid][$iname]['contributors'][] = $contrib; }
        }
    }
    // Tubewell
    $sqlT = "
        SELECT u.status_date, u.entity_id, u.item_name, u.updated_by, uc.contributor_name
        FROM updates u
        LEFT JOIN update_contributors uc ON uc.update_id = u.id
        JOIN (
            SELECT status_date, entity_id, item_name, MAX(id) AS max_id
            FROM updates
            WHERE entity_type='tubewell' AND status_date BETWEEN ? AND ?
            GROUP BY status_date, entity_id, item_name
        ) x ON x.max_id = u.id
        WHERE u.entity_type='tubewell'
    ";
    if ($st = $conn->prepare($sqlT)) {
        $st->bind_param('ss', $from_date, $to_date);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) {
            $d = $r['status_date']; $tid = (int)$r['entity_id']; $iname = $r['item_name'];
            $primary = $r['updated_by']; $contrib = $r['contributor_name'];
            if (!isset($tw_updates_people_by_date[$d])) { $tw_updates_people_by_date[$d] = []; }
            if (!isset($tw_updates_people_by_date[$d][$tid])) { $tw_updates_people_by_date[$d][$tid] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $tw_updates_people_by_date[$d][$tid]['primaries'], true)) { $tw_updates_people_by_date[$d][$tid]['primaries'][] = $primary; }
            if (!isset($tw_updates_people_by_date_item[$d])) { $tw_updates_people_by_date_item[$d] = []; }
            if (!isset($tw_updates_people_by_date_item[$d][$tid])) { $tw_updates_people_by_date_item[$d][$tid] = []; }
            if (!isset($tw_updates_people_by_date_item[$d][$tid][$iname])) { $tw_updates_people_by_date_item[$d][$tid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $tw_updates_people_by_date_item[$d][$tid][$iname]['primaries'], true)) { $tw_updates_people_by_date_item[$d][$tid][$iname]['primaries'][] = $primary; }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $tw_updates_people_by_date[$d][$tid]['contributors'], true)) { $tw_updates_people_by_date[$d][$tid]['contributors'][] = $contrib; }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $tw_updates_people_by_date_item[$d][$tid][$iname]['contributors'], true)) { $tw_updates_people_by_date_item[$d][$tid][$iname]['contributors'][] = $contrib; }
        }
    }
}

// Precompute highlight maps from status_history/lcs_status_history for created_by
// Tubewell highlights
if ($h = $conn->prepare("SELECT status_date, tubewell_id, item_name FROM status_history WHERE created_by = ? AND status_date BETWEEN ? AND ?")) {
    $h->bind_param('sss', $sel_user, $from_date, $to_date);
    $h->execute();
    $rs = $h->get_result();
    while ($r = $rs->fetch_assoc()) {
        $d = $r['status_date']; $tid = (int)$r['tubewell_id']; $it = $r['item_name'];
        if (!isset($tw_hl[$d])) { $tw_hl[$d] = []; }
        if (!isset($tw_hl[$d][$tid])) { $tw_hl[$d][$tid] = []; }
        $tw_hl[$d][$tid][$it] = true;
        $allowed_dates[$d] = true;
    }
}

// LCS highlights
if ($h = $conn->prepare("SELECT status_date, lcs_id, item_name FROM lcs_status_history WHERE created_by = ? AND status_date BETWEEN ? AND ?")) {
    $h->bind_param('sss', $sel_user, $from_date, $to_date);
    $h->execute();
    $rs = $h->get_result();
    while ($r = $rs->fetch_assoc()) {
        $d = $r['status_date']; $lid = (int)$r['lcs_id']; $it = $r['item_name'];
        if (!isset($lcs_hl[$d])) { $lcs_hl[$d] = []; }
        if (!isset($lcs_hl[$d][$lid])) { $lcs_hl[$d][$lid] = []; }
        $lcs_hl[$d][$lid][$it] = true;
        $allowed_dates[$d] = true;
    }
}

$tw_rows_by_date = [];
$lcs_rows_by_date = [];
$tw_note_on_date = [];
$lcs_master_by_date = [];
// Preload item-wise updates contributors for By/With display

// Build Tubewell Master Notes
if ($from_date !== '' && $to_date !== '') {
    // Tubewell master note contributors
    $colT = $conn->query("SHOW TABLES LIKE 'tubewell_master_note_contributors'");
    if ($colT && $colT->num_rows > 0) {
        if ($st = $conn->prepare("SELECT tubewell_id, status_date, contributor_name FROM tubewell_master_note_contributors WHERE status_date BETWEEN ? AND ?")) {
            $st->bind_param('ss', $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $d = $r['status_date']; $tid = (int)$r['tubewell_id']; $nm = $r['contributor_name'];
                if (!isset($tw_mn_contrib_by_date[$d])) { $tw_mn_contrib_by_date[$d] = []; }
                if (!isset($tw_mn_contrib_by_date[$d][$tid])) { $tw_mn_contrib_by_date[$d][$tid] = []; }
                if ($nm !== null && $nm !== '' && !in_array($nm, $tw_mn_contrib_by_date[$d][$tid], true)) { $tw_mn_contrib_by_date[$d][$tid][] = $nm; }
            }
        }
    }
    $q = "SELECT tubewell_id, status_date, note, updated_by, updated_at FROM tubewell_master_notes WHERE status_date BETWEEN ? AND ? ORDER BY tubewell_id ASC, status_date ASC";
    if ($st = $conn->prepare($q)) {
        $st->bind_param('ss', $from_date, $to_date);
        $st->execute();
        $rs = $st->get_result();
        while ($row = $rs->fetch_assoc()) { 
            $tw_note_on_date[$row['status_date']][(int)$row['tubewell_id']] = $row; 
        }
    }
}

// Build LCS Master Notes
if ($sel_user !== '' && $from_date !== '' && $to_date !== '') {
    // LCS master note contributors
    $colL = $conn->query("SHOW TABLES LIKE 'lcs_master_note_contributors'");
    if ($colL && $colL->num_rows > 0) {
        if ($st = $conn->prepare("SELECT lcs_id, status_date, contributor_name FROM lcs_master_note_contributors WHERE status_date BETWEEN ? AND ?")) {
            $st->bind_param('ss', $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $d = $r['status_date']; $lid = (int)$r['lcs_id']; $nm = $r['contributor_name'];
                if (!isset($lcs_mn_contrib_by_date[$d])) { $lcs_mn_contrib_by_date[$d] = []; }
                if (!isset($lcs_mn_contrib_by_date[$d][$lid])) { $lcs_mn_contrib_by_date[$d][$lid] = []; }
                if ($nm !== null && $nm !== '' && !in_array($nm, $lcs_mn_contrib_by_date[$d][$lid], true)) { $lcs_mn_contrib_by_date[$d][$lid][] = $nm; }
            }
        }
    }
    // LCS master note contributors
    $colL = $conn->query("SHOW TABLES LIKE 'lcs_master_note_contributors'");
    if ($colL && $colL->num_rows > 0) {
        if ($st = $conn->prepare("SELECT lcs_id, status_date, contributor_name FROM lcs_master_note_contributors WHERE status_date BETWEEN ? AND ?")) {
            $st->bind_param('ss', $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $d = $r['status_date']; $lid = (int)$r['lcs_id']; $nm = $r['contributor_name'];
                if (!isset($lcs_mn_contrib_by_date[$d])) { $lcs_mn_contrib_by_date[$d] = []; }
                if (!isset($lcs_mn_contrib_by_date[$d][$lid])) { $lcs_mn_contrib_by_date[$d][$lid] = []; }
                if ($nm !== null && $nm !== '' && !in_array($nm, $lcs_mn_contrib_by_date[$d][$lid], true)) { $lcs_mn_contrib_by_date[$d][$lid][] = $nm; }
            }
        }
    }
    if ($slm = $conn->prepare("SELECT lmn.lcs_id, DATE(lmn.updated_at) AS d, lmn.note, lmn.updated_by, lmn.updated_at, s.site_name, l.lcs_name
                                FROM lcs_master_notes lmn
                                JOIN lcs l ON lmn.lcs_id = l.id
                                JOIN sites s ON l.site_id = s.id
                                WHERE lmn.updated_by = ? AND DATE(lmn.updated_at) BETWEEN ? AND ?
                                ORDER BY DATE(lmn.updated_at) ASC, s.site_name ASC, l.lcs_name ASC")) {
        $slm->bind_param('sss', $sel_user, $from_date, $to_date);
        $slm->execute();
        $res2 = $slm->get_result();
        while ($r = $res2->fetch_assoc()) {
            $d = $r['d'];
            $lid = (int)$r['lcs_id'];
            if (!isset($lcs_master_by_date[$d])) { $lcs_master_by_date[$d] = []; }
            if (!isset($lcs_master_by_date[$d][$lid])) { $lcs_master_by_date[$d][$lid] = []; }
            $lcs_master_by_date[$d][$lid][] = $r;
        }
    }
}

// Tubewell snapshots
if ($from_date !== '' && $to_date !== '') {
    $active_items = [];
    if ($ires = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ")) { 
        while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } 
    }
    // Preload Tubewell item-wise updates (primaries and contributors)
    $has_updates_tbl = false; $tchk = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchk && $tchk->num_rows > 0) { $has_updates_tbl = true; }
    $has_uc_tbl = false; $tchk2 = $conn->query("SHOW TABLES LIKE 'update_contributors'"); if ($tchk2 && $tchk2->num_rows > 0) { $has_uc_tbl = true; }
    if ($has_updates_tbl) {
        if ($st = $conn->prepare("SELECT status_date, entity_id, item_name, updated_by FROM updates WHERE entity_type='tubewell' AND status_date BETWEEN ? AND ?")) {
            $st->bind_param('ss', $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $d = $r['status_date']; $tid = (int)$r['entity_id']; $iname = $r['item_name']; $name = $r['updated_by'];
                if (!isset($tw_updates_people_by_date_item[$d])) { $tw_updates_people_by_date_item[$d] = []; }
                if (!isset($tw_updates_people_by_date_item[$d][$tid])) { $tw_updates_people_by_date_item[$d][$tid] = []; }
                if (!isset($tw_updates_people_by_date_item[$d][$tid][$iname])) { $tw_updates_people_by_date_item[$d][$tid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                if ($name !== null && $name !== '' && !in_array($name, $tw_updates_people_by_date_item[$d][$tid][$iname]['primaries'], true)) { $tw_updates_people_by_date_item[$d][$tid][$iname]['primaries'][] = $name; }
            }
        }
        if ($has_uc_tbl) {
            if ($st = $conn->prepare("SELECT up.status_date, up.entity_id, up.item_name, uc.contributor_name FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='tubewell' AND up.status_date BETWEEN ? AND ?")) {
                $st->bind_param('ss', $from_date, $to_date);
                $st->execute();
                $rs = $st->get_result();
                while ($r = $rs->fetch_assoc()) {
                    $d = $r['status_date']; $tid = (int)$r['entity_id']; $iname = $r['item_name']; $name = $r['contributor_name'];
                    if (!isset($tw_updates_people_by_date_item[$d])) { $tw_updates_people_by_date_item[$d] = []; }
                    if (!isset($tw_updates_people_by_date_item[$d][$tid])) { $tw_updates_people_by_date_item[$d][$tid] = []; }
                    if (!isset($tw_updates_people_by_date_item[$d][$tid][$iname])) { $tw_updates_people_by_date_item[$d][$tid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                    if ($name !== null && $name !== '' && !in_array($name, $tw_updates_people_by_date_item[$d][$tid][$iname]['contributors'], true)) { $tw_updates_people_by_date_item[$d][$tid][$iname]['contributors'][] = $name; }
                }
            }
        }
    }

    $tw_ids = [];
    if ($q = $conn->prepare("SELECT DISTINCT tubewell_id FROM status_history WHERE status_date BETWEEN ? AND ?")) { 
        $q->bind_param('ss', $from_date, $to_date); 
        $q->execute(); 
        $r = $q->get_result(); 
        while($x = $r->fetch_assoc()) { $tw_ids[] = (int)$x['tubewell_id']; } 
    }
    // Add TW IDs from master notes/media by this user
    if ($q2 = $conn->prepare("SELECT DISTINCT tubewell_id FROM tubewell_master_notes WHERE updated_by = ? AND status_date BETWEEN ? AND ?")) {
        $q2->bind_param('sss', $sel_user, $from_date, $to_date);
        $q2->execute();
        $r2 = $q2->get_result();
        while ($x = $r2->fetch_assoc()) { $id=(int)$x['tubewell_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
    }
    if ($q3 = $conn->prepare("SELECT DISTINCT tubewell_id FROM tubewell_master_media WHERE uploaded_by = ? AND status_date BETWEEN ? AND ?")) {
        $q3->bind_param('sss', $sel_user, $from_date, $to_date);
        $q3->execute();
        $r3 = $q3->get_result();
        while ($x = $r3->fetch_assoc()) { $id=(int)$x['tubewell_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
    }
    // Include tubewell IDs from item updates where user is primary or contributor
    if ($has_updates_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT entity_id FROM updates WHERE entity_type='tubewell' AND updated_by = ? AND status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
        }
    }
    if ($has_updates_tbl && $has_uc_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT up.entity_id FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='tubewell' AND uc.contributor_name = ? AND up.status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
        }
    }
    foreach ($tw_ids as $tid) {
        $stmt = $conn->prepare("SELECT sh.*, s.site_name, tw.tubewell_name FROM status_history sh JOIN sites s ON sh.site_id = s.id JOIN tubewells tw ON sh.tubewell_id = tw.id WHERE sh.tubewell_id = ? AND sh.status_date <= ? ORDER BY sh.status_date ASC, sh.item_name ASC");
        $stmt->bind_param('is', $tid, $to_date);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows_seq = [];
        while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

        $chg = $conn->prepare("SELECT DISTINCT status_date FROM status_history WHERE tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
        $chg->bind_param('iss', $tid, $from_date, $to_date);
        $chg->execute();
        $crs = $chg->get_result();
        $change_dates = [];
        while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

        $last_known = [];
        $dynamic_items = [];
        $p = 0; $n = count($rows_seq); 
        $cur = strtotime($from_date); $end = strtotime($to_date);
        
        while ($cur !== false && $cur <= $end) {
            $d = date('Y-m-d', $cur);
            while ($p < $n && $rows_seq[$p]['status_date'] <= $d) { 
                $rk = $rows_seq[$p]; 
                $last_known[$rk['item_name']] = $rk; 
                if (!in_array($rk['item_name'], $active_items, true)) { 
                    $dynamic_items[$rk['item_name']] = true; 
                }
                $p++; 
            }
            
            $has_item_change = isset($change_dates[$d]) && isset($allowed_dates[$d]) && isset($tw_hl[$d][$tid]) && !empty($tw_hl[$d][$tid]);
            $has_master_change = (isset($tw_note_on_date[$d]) && isset($tw_note_on_date[$d][$tid]));
            $has_update_change = isset($tw_update_hl[$d]) && isset($tw_update_hl[$d][$tid]);
            if ($has_item_change || $has_master_change || $has_update_change) {
                if (!isset($tw_rows_by_date[$d])) { $tw_rows_by_date[$d] = []; }
                if (!isset($tw_rows_by_date[$d][$tid])) { $tw_rows_by_date[$d][$tid] = []; }
                
                $all_items = $active_items;
                foreach ($dynamic_items as $din => $_v) { 
                    if (!in_array($din, $all_items, true)) { $all_items[] = $din; } 
                }
                sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);
                
                foreach ($all_items as $iname) {
                    if (isset($last_known[$iname])) { 
                        $tw_rows_by_date[$d][$tid][] = $last_known[$iname]; 
                    } else {
                        // Fallback names when history is absent
                        $site_name_fallback = $rows_seq[0]['site_name'] ?? '';
                        $tubewell_name_fallback = $rows_seq[0]['tubewell_name'] ?? '';
                        if (empty($rows_seq)) {
                            if ($nm = $conn->prepare("SELECT s.site_name, t.tubewell_name FROM tubewells t JOIN sites s ON t.site_id = s.id WHERE t.id = ? LIMIT 1")) {
                                $nm->bind_param('i', $tid);
                                $nm->execute();
                                $nmr = $nm->get_result();
                                if ($row = $nmr->fetch_assoc()) { $site_name_fallback = $row['site_name'] ?? ''; $tubewell_name_fallback = $row['tubewell_name'] ?? ''; }
                            }
                        }
                        $tw_rows_by_date[$d][$tid][] = [ 
                            'tubewell_id' => $tid, 
                            'item_name' => $iname, 
                            'make_model' => '', 
                            'size_capacity' => '', 
                            'status' => '', 
                            'remark' => '', 
                            'created_by' => '—', 
                            'updated_at' => null, 
                            'site_name' => $site_name_fallback, 
                            'tubewell_name' => $tubewell_name_fallback 
                        ]; 
                    }
                }
                
                if (!isset($tw_rows_by_date[$d.'__hl'])) { $tw_rows_by_date[$d.'__hl'] = []; }
                $map = $tw_hl[$d][$tid] ?? [];
                $map2 = []; 
                foreach($map as $iname => $_v) { $map2[$tid.'|'.$iname] = true; }
                $tw_rows_by_date[$d.'__hl'][$tid] = $map2;
            }
            $cur = strtotime('+1 day', $cur);
        }
    }
}

// LCS snapshots
if ($from_date !== '' && $to_date !== '') {
    $active_items = [];
    if ($ires = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ")) { 
        while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } 
    }
    
    $lcs_ids = [];
    if ($q = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_status_history WHERE status_date BETWEEN ? AND ?")) { 
        $q->bind_param('ss', $from_date, $to_date); 
        $q->execute(); 
        $r = $q->get_result(); 
        while($x = $r->fetch_assoc()) { $lcs_ids[] = (int)$x['lcs_id']; } 
    }
    // Add LCS IDs from master notes/media by this user
    $has_sd2 = false; $col2 = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'"); if ($col2 && $col2->num_rows > 0) { $has_sd2 = true; }
    if ($has_sd2) {
        if ($q2 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_notes WHERE updated_by = ? AND status_date BETWEEN ? AND ?")) {
            $q2->bind_param('sss', $sel_user, $from_date, $to_date);
            $q2->execute();
            $r2 = $q2->get_result();
            while ($x = $r2->fetch_assoc()) { $id=(int)$x['lcs_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    } else {
        if ($q2 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_notes WHERE updated_by = ? AND DATE(updated_at) BETWEEN ? AND ?")) {
            $q2->bind_param('sss', $sel_user, $from_date, $to_date);
            $q2->execute();
            $r2 = $q2->get_result();
            while ($x = $r2->fetch_assoc()) { $id=(int)$x['lcs_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    }
    if ($q3 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_media WHERE uploaded_by = ? AND status_date BETWEEN ? AND ?")) {
        $q3->bind_param('sss', $sel_user, $from_date, $to_date);
        $q3->execute();
        $r3 = $q3->get_result();
        while ($x = $r3->fetch_assoc()) { $id=(int)$x['lcs_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
    }
    // Include LCS IDs from item updates where user is primary or contributor
    if ($has_updates_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT entity_id FROM updates WHERE entity_type='lcs' AND updated_by = ? AND status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    }
    if ($has_updates_tbl && $has_uc_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT up.entity_id FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='lcs' AND uc.contributor_name = ? AND up.status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    }
    
    foreach ($lcs_ids as $lid) {
        $stmt = $conn->prepare("SELECT h.*, s.site_name, l.lcs_name FROM lcs_status_history h JOIN sites s ON h.site_id = s.id JOIN lcs l ON h.lcs_id = l.id WHERE h.lcs_id = ? AND h.status_date <= ? ORDER BY h.status_date ASC, h.item_name ASC");
        $stmt->bind_param('is', $lid, $to_date);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows_seq = [];
        while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

        $chg = $conn->prepare("SELECT DISTINCT status_date FROM lcs_status_history WHERE lcs_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
        $chg->bind_param('iss', $lid, $from_date, $to_date);
        $chg->execute();
        $crs = $chg->get_result();
        $change_dates = [];
        while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

        $last_known = [];
        $dynamic_items = [];
        $p = 0; $n = count($rows_seq); 
        $cur = strtotime($from_date); $end = strtotime($to_date);
        
        while ($cur !== false && $cur <= $end) {
            $d = date('Y-m-d', $cur);
            while ($p < $n && $rows_seq[$p]['status_date'] <= $d) { 
                $rk = $rows_seq[$p]; 
                $last_known[$rk['item_name']] = $rk; 
                if (!in_array($rk['item_name'], $active_items, true)) { 
                    $dynamic_items[$rk['item_name']] = true; 
                }
                $p++; 
            }
            
            $has_item_change = isset($change_dates[$d]) && isset($allowed_dates[$d]) && isset($lcs_hl[$d][$lid]) && !empty($lcs_hl[$d][$lid]);
            $has_master_change = (isset($lcs_master_by_date[$d]) && !empty($lcs_master_by_date[$d][$lid] ?? []));
            $has_update_change = isset($lcs_update_hl[$d]) && isset($lcs_update_hl[$d][$lid]);
            if ($has_item_change || $has_master_change || $has_update_change) {
                if (!isset($lcs_rows_by_date[$d])) { $lcs_rows_by_date[$d] = []; }
                if (!isset($lcs_rows_by_date[$d][$lid])) { $lcs_rows_by_date[$d][$lid] = []; }
                
                $all_items = $active_items;
                foreach ($dynamic_items as $din => $_v) { 
                    if (!in_array($din, $all_items, true)) { $all_items[] = $din; } 
                }
                sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);
                
                foreach ($all_items as $iname) {
                    if (isset($last_known[$iname])) { 
                        $lcs_rows_by_date[$d][$lid][] = $last_known[$iname]; 
                    } else {
                        // Fallback names
                        $site_name_fallback = $rows_seq[0]['site_name'] ?? '';
                        $lcs_name_fallback = $rows_seq[0]['lcs_name'] ?? '';
                        if (empty($rows_seq)) {
                            if ($nm = $conn->prepare("SELECT s.site_name, l.lcs_name FROM lcs l JOIN sites s ON l.site_id = s.id WHERE l.id = ? LIMIT 1")) {
                                $nm->bind_param('i', $lid);
                                $nm->execute();
                                $nmr = $nm->get_result();
                                if ($row = $nmr->fetch_assoc()) { $site_name_fallback = $row['site_name'] ?? ''; $lcs_name_fallback = $row['lcs_name'] ?? ''; }
                            }
                        }
                        $lcs_rows_by_date[$d][$lid][] = [ 
                            'lcs_id' => $lid, 
                            'item_name' => $iname, 
                            'make_model' => '', 
                            'size_capacity' => '', 
                            'status' => '', 
                            'remark' => '', 
                            'created_by' => '—', 
                            'updated_at' => null, 
                            'site_name' => $site_name_fallback, 
                            'lcs_name' => $lcs_name_fallback 
                        ]; 
                    }
                }
                
                if (!isset($lcs_rows_by_date[$d.'__hl'])) { $lcs_rows_by_date[$d.'__hl'] = []; }
                $map = $lcs_hl[$d][$lid] ?? [];
                $map2 = []; 
                foreach($map as $iname => $_v) { $map2[$lid.'|'.$iname] = true; }
                $lcs_rows_by_date[$d.'__hl'][$lid] = $map2;
            }
            $cur = strtotime('+1 day', $cur);
        }
    }
}

if (empty($tw_rows_by_date) && empty($lcs_rows_by_date)) { 
    die('No records found.'); 
}

$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Site Asset Management System');
$pdf->SetAuthor('Site Asset Management System');
$pdf->SetTitle('User-wise Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$pdf->SetFont('helvetica','B',16);
$pdf->Cell(0,8,'USER-WISE REPORT',0,1,'C');
$pdf->Ln(4);
$pdf->SetFont('helvetica','',10);
$pdf->Cell(0,6,'User: '.$sel_user.' | From: '.date('d M Y',strtotime($from_date)).'  To: '.date('d M Y',strtotime($to_date)),0,1,'L');
$pdf->Ln(4);

// Updated headers - Photo/Video removed, HMI/Web kept with symbols
$tw_header = ['Sr.No','Site','Tubewell','Item','Make/Model','Size/Cap.','Status','HMI/Loc.','Web','Remark','Updated By','Updated At'];
$tw_w = [10, 20, 30, 25, 25, 17, 17, 12, 12, 72, 20, 23];
$lcs_header = ['Sr.No','Site','LCS','Item','Make/Model','Size/Cap.','Status','Remark','Updated By','Updated At'];
$lcs_w = [10, 20, 30, 25, 25, 15, 17, 80, 20, 23];

// Function to get HMI/Web status symbols
function getCheckStatus($value) {
    $val = (int)$value;
    if ($val === 1) return 'yes';
    if ($val === 2) return 'no';
    return '—';
}

// Tubewell sections with master notes
foreach ($tw_rows_by_date as $d => $buckets) {
    if (strpos($d,'__hl') !== false) { continue; }
    $hlAll = $tw_rows_by_date[$d.'__hl'] ?? [];
    
    foreach ($buckets as $tid => $rows) {
        $first = $rows[0] ?? null;
        
        $pdf->SetFont('helvetica','B',12);
        $pdf->Cell(0,7,'Date: '.date('d M Y',strtotime($d)).' | Site: '.($first['site_name'] ?? '').' | Tubewell: '.($first['tubewell_name'] ?? '').' ('.count($rows).' items)',0,1,'L');
        
        // Display master note if exists (multiline with byline) + contributors
        if (isset($tw_note_on_date[$d]) && isset($tw_note_on_date[$d][$tid])) {
            $mn = $tw_note_on_date[$d][$tid];
            $note_text = (string)($mn['note'] ?? '');
            if (trim($note_text) !== '') {
                $pdf->SetFont('helvetica','B',9);
                $pdf->Cell(0,5,'Master Note:',0,1,'L');
                $pdf->SetFont('helvetica','',8);
                $pdf->MultiCell(0, 0, $note_text, 0, 'L');
                $pdf->SetFont('helvetica','I',7);
                $pdf->Cell(0,4,'— by '.($mn['updated_by'] ?? '—').' at '.(isset($mn['updated_at']) && $mn['updated_at'] ? date('d M Y H:i', strtotime($mn['updated_at'])) : '—'),0,1,'L');
                // Contributors line
                $tmnc = $tw_mn_contrib_by_date[$d][$tid] ?? [];
                if (!empty($tmnc)) { $pdf->SetFont('helvetica','',7); $pdf->Cell(0,4,'Contributors: '.implode(', ', $tmnc),0,1,'L'); }
                $pdf->Ln(2);
            }
        }
        
        $pdf->Ln(1);
        $pdf->SetFillColor(70,130,180); 
        $pdf->SetTextColor(255,255,255); 
        $pdf->SetFont('helvetica','B',7);
        
        for($i = 0; $i < count($tw_header); $i++) { 
            $pdf->Cell($tw_w[$i],6,$tw_header[$i],1,0,'C',true); 
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica','',7); 
        $pdf->SetTextColor(0,0,0); 
        $fill = false; 
        $c = 1; 
        $hl = $hlAll[$tid] ?? [];
        
        foreach ($rows as $r) {
            if ($pdf->GetY() > 180) { 
                $pdf->AddPage(); 
                $pdf->SetFillColor(70,130,180); 
                $pdf->SetTextColor(255,255,255); 
                $pdf->SetFont('helvetica','B',7); 
                for($i = 0; $i < count($tw_header); $i++) { 
                    $pdf->Cell($tw_w[$i],6,$tw_header[$i],1,0,'C',true);
                } 
                $pdf->Ln(); 
                $pdf->SetFont('helvetica','',7); 
                $pdf->SetTextColor(0,0,0); 
            }
            
            $isSel = isset($hl[$r['tubewell_id'].'|'.$r['item_name']]);
            if ($isSel) { 
                $pdf->SetFillColor(255,247,230); 
            } else { 
                $pdf->SetFillColor($fill ? 240 : 255, $fill ? 248 : 255, $fill ? 255 : 255); 
            }
            
            $pdf->Cell($tw_w[0],5,$c++,1,0,'C',true);
            $pdf->Cell($tw_w[1],5,$r['site_name'] ?? 'N/A',1,0,'L',true);
            $pdf->Cell($tw_w[2],5,$r['tubewell_name'] ?? 'N/A',1,0,'L',true);
            $pdf->Cell($tw_w[3],5,$r['item_name'] ?? 'N/A',1,0,'L',true);
            $pdf->Cell($tw_w[4],5,($r['make_model'] ?? 'N/A'),1,0,'L',true);
            $pdf->Cell($tw_w[5],5,($r['size_capacity'] ?? 'N/A'),1,0,'L',true);
            $pdf->Cell($tw_w[6],5,($r['status'] ?? 'N/A'),1,0,'C',true);
            $pdf->Cell($tw_w[7],5,getCheckStatus($r['check_hmi_local'] ?? 0),1,0,'C',true);
            $pdf->Cell($tw_w[8],5,getCheckStatus($r['check_web'] ?? 0),1,0,'C',true);
            $pdf->Cell($tw_w[9],5,($r['remark'] ?? 'N/A'),1,0,'L',true);
            // Updated By with By/With breakdown
            $byWith = $r['created_by'] ?? 'N/A';
            $ppl = $tw_updates_people_by_date_item[$d][$r['tubewell_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
            $p1 = $ppl['primaries'] ?? []; $p2 = $ppl['contributors'] ?? [];
            if (!empty($p1) || !empty($p2)) {
                $parts = [];
                if (!empty($p1)) { $parts[] = 'By: '.implode(', ', $p1); }
                if (!empty($p2)) { $parts[] = 'With: '.implode(', ', $p2); }
                $byWith = $byWith.' ['.implode(' | ', $parts).']';
            }
            $pdf->Cell($tw_w[10],5,$byWith,1,0,'L',true);
            $pdf->Cell($tw_w[11],5,(isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i',strtotime($r['updated_at'])) : '—'),1,0,'C',true);
            $pdf->Ln();
            $fill = !$fill;
        }
        $pdf->Ln(3);
    }
}

// LCS sections with master notes
foreach ($lcs_rows_by_date as $d => $buckets) {
    if (strpos($d,'__hl') !== false) { continue; }
    $hlAll = $lcs_rows_by_date[$d.'__hl'] ?? [];
    
    foreach ($buckets as $lid => $rows) {
        $first = $rows[0] ?? null;
        
        $pdf->SetFont('helvetica','B',12);
        $pdf->Cell(0,7,'Date: '.date('d M Y',strtotime($d)).' | Site: '.($first['site_name'] ?? '').' | LCS: '.($first['lcs_name'] ?? '').' ('.count($rows).' items)',0,1,'L');
        
        // Display master note if exists (multiline with byline) and contributors. Fallback to note-on-date when user is only contributor
        $mn = null;
        if (isset($lcs_master_by_date[$d]) && isset($lcs_master_by_date[$d][$lid]) && !empty($lcs_master_by_date[$d][$lid])) {
            $mn = $lcs_master_by_date[$d][$lid][0];
        } else {
            // Try to fetch by exact date for this LCS
            $has_sd2 = false; $col2 = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'"); if ($col2 && $col2->num_rows > 0) { $has_sd2 = true; }
            if ($has_sd2) {
                if ($stm = $conn->prepare("SELECT note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? AND status_date = ? LIMIT 1")) {
                    $stm->bind_param('is', $lid, $d);
                    $stm->execute(); $rr = $stm->get_result(); $mn = $rr->fetch_assoc();
                }
            } else {
                if ($stm = $conn->prepare("SELECT note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? AND DATE(updated_at) = ? LIMIT 1")) {
                    $stm->bind_param('is', $lid, $d);
                    $stm->execute(); $rr = $stm->get_result(); $mn = $rr->fetch_assoc();
                }
            }
        }
        if (!empty($mn)) {
            $note_text = (string)($mn['note'] ?? '');
            if (trim($note_text) !== '') {
                $pdf->SetFont('helvetica','B',9);
                $pdf->Cell(0,5,'Master Note:',0,1,'L');
                $pdf->SetFont('helvetica','',8);
                $pdf->MultiCell(0, 0, $note_text, 0, 'L');
                $pdf->SetFont('helvetica','I',7);
                $pdf->Cell(0,4,'— by '.($mn['updated_by'] ?? '—').' at '.(isset($mn['updated_at']) && $mn['updated_at'] ? date('d M Y H:i', strtotime($mn['updated_at'])) : '—'),0,1,'L');
                // Contributors line
                $lmnc = $lcs_mn_contrib_by_date[$d][$lid] ?? [];
                if (!empty($lmnc)) {
                    $pdf->SetFont('helvetica','',7);
                    $pdf->Cell(0,4,'Contributors: '.implode(', ', $lmnc),0,1,'L');
                }
                $pdf->Ln(2);
            }
        }
        
        $pdf->Ln(1);
        $pdf->SetFillColor(70,130,180); 
        $pdf->SetTextColor(255,255,255); 
        $pdf->SetFont('helvetica','B',7);
        
        for($i = 0; $i < count($lcs_header); $i++) { 
            $pdf->Cell($lcs_w[$i],6,$lcs_header[$i],1,0,'C',true); 
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica','',7); 
        $pdf->SetTextColor(0,0,0); 
        $fill = false; 
        $c = 1; 
        $hl = $hlAll[$lid] ?? [];
        
        foreach ($rows as $r) {
            if ($pdf->GetY() > 180) { 
                $pdf->AddPage(); 
                $pdf->SetFillColor(70,130,180); 
                $pdf->SetTextColor(255,255,255); 
                $pdf->SetFont('helvetica','B',7); 
                for($i = 0; $i < count($lcs_header); $i++) { 
                    $pdf->Cell($lcs_w[$i],6,$lcs_header[$i],1,0,'C',true);
                } 
                $pdf->Ln(); 
                $pdf->SetFont('helvetica','',7); 
                $pdf->SetTextColor(0,0,0); 
            }
            
            $isSel = isset($hl[$r['lcs_id'].'|'.$r['item_name']]);
            if ($isSel) { 
                $pdf->SetFillColor(255,247,230); 
            } else { 
                $pdf->SetFillColor($fill ? 240 : 255, $fill ? 248 : 255, $fill ? 255 : 255); 
            }
            
            $pdf->Cell($lcs_w[0],5,$c++,1,0,'C',true);
            $pdf->Cell($lcs_w[1],5,$r['site_name'] ?? 'N/A',1,0,'L',true);
            $pdf->Cell($lcs_w[2],5,$r['lcs_name'] ?? 'N/A',1,0,'L',true);
            $pdf->Cell($lcs_w[3],5,$r['item_name'] ?? 'N/A',1,0,'L',true);
            $pdf->Cell($lcs_w[4],5,($r['make_model'] ?? 'N/A'),1,0,'L',true);
            $pdf->Cell($lcs_w[5],5,($r['size_capacity'] ?? 'N/A'),1,0,'L',true);
            $pdf->Cell($lcs_w[6],5,($r['status'] ?? 'N/A'),1,0,'C',true);
            $pdf->Cell($lcs_w[7],5,($r['remark'] ?? 'N/A'),1,0,'L',true);
            // Updated By with By/With breakdown
            $byWith = $r['created_by'] ?? 'N/A';
            $lppl = $lcs_updates_people_by_date_item[$d][$r['lcs_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
            $lp1 = $lppl['primaries'] ?? []; $lp2 = $lppl['contributors'] ?? [];
            if (!empty($lp1) || !empty($lp2)) {
                $parts = [];
                if (!empty($lp1)) { $parts[] = 'By: '.implode(', ', $lp1); }
                if (!empty($lp2)) { $parts[] = 'With: '.implode(', ', $lp2); }
                $byWith = $byWith.' ['.implode(' | ', $parts).']';
            }
            $pdf->Cell($lcs_w[8],5,$byWith,1,0,'L',true);
            $pdf->Cell($lcs_w[9],5,(isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i',strtotime($r['updated_at'])) : '—'),1,0,'C',true);
            $pdf->Ln();
            $fill = !$fill;
        }
        $pdf->Ln(3);
    }
}

$pdf->SetY(-15);
$pdf->SetFont('helvetica','I',8);
$pdf->Cell(0,10,'Generated: '.date('d M Y H:i:s')." | User: ".$sel_user, 'T', 0, 'C');

$pdf->Output('user_wise_report_'.preg_replace('/[^A-Za-z0-9_-]/','_',$sel_user).'_'.$from_date.'_'.$to_date.'.pdf','D');
?>