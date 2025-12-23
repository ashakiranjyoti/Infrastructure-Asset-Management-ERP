<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }
include 'db_config.php';

$on_date = isset($_GET['on_date']) ? $_GET['on_date'] : '';

// Common lists
$sites = [];
if ($sres = $conn->query("SELECT id, site_name FROM sites ")) { while ($r = $sres->fetch_assoc()) { $sites[$r['id']] = $r['site_name']; } }

// Results containers
$tw_rows_by_date = [];
$lcs_rows_by_date = [];
$allowed_dates = [];
if ($on_date !== '') { $allowed_dates[$on_date] = true; }

// Master note media and contributors maps for selected date
$tw_master_media_on_date = [];
$lcs_master_media_on_date = [];
$tw_master_note_contrib_on_date = [];
$lcs_master_note_contrib_on_date = [];

// Precompute highlight maps for selected date (all users)
$tw_hl = [];
$lcs_hl = [];
if ($on_date !== '') {
    if ($h = $conn->prepare("SELECT status_date, tubewell_id, item_name FROM status_history WHERE status_date = ?")) {
        $h->bind_param('s', $on_date);
        $h->execute();
        $rs = $h->get_result();
        while ($r = $rs->fetch_assoc()) {
            $d = $r['status_date']; $tid = (int)$r['tubewell_id']; $it = $r['item_name'];
            if (!isset($tw_hl[$d])) { $tw_hl[$d] = []; }
            if (!isset($tw_hl[$d][$tid])) { $tw_hl[$d][$tid] = []; }
            $tw_hl[$d][$tid][$it] = true;
        }
    }
    if ($h = $conn->prepare("SELECT status_date, lcs_id, item_name FROM lcs_status_history WHERE status_date = ?")) {
        $h->bind_param('s', $on_date);
        $h->execute();
        $rs = $h->get_result();
        while ($r = $rs->fetch_assoc()) {
            $d = $r['status_date']; $lid = (int)$r['lcs_id']; $it = $r['item_name'];
            if (!isset($lcs_hl[$d])) { $lcs_hl[$d] = []; }
            if (!isset($lcs_hl[$d][$lid])) { $lcs_hl[$d][$lid] = []; }
            $lcs_hl[$d][$lid][$it] = true;
        }
    }
}

// Preload all media for the selected date (strictly by upload date)
$tw_media_by_date_item = [];
$lcs_media_by_date_item = [];
if ($on_date !== '') {
    // Preload tubewell media for the selected date using DATE(uploaded_at)
    if ($media_stmt = $conn->prepare("\n        SELECT m.*, DATE(m.uploaded_at) AS media_date\n        FROM media_uploads m\n        WHERE DATE(m.uploaded_at) = ?\n        ORDER BY m.uploaded_at DESC\n    ")) {
        $media_stmt->bind_param('s', $on_date);
        $media_stmt->execute();
        $media_res = $media_stmt->get_result();
        while ($media = $media_res->fetch_assoc()) {
            $date_key = $media['media_date'];
            $item_key = $media['tubewell_id'] . '|' . $media['item_name'];
            if (!isset($tw_media_by_date_item[$date_key])) { $tw_media_by_date_item[$date_key] = []; }
            if (!isset($tw_media_by_date_item[$date_key][$item_key])) { $tw_media_by_date_item[$date_key][$item_key] = []; }
            $tw_media_by_date_item[$date_key][$item_key][] = $media;
        }
    }

    // Preload LCS media for the selected date using DATE(uploaded_at)
    if ($media_stmt = $conn->prepare("\n        SELECT m.*, DATE(m.uploaded_at) AS media_date\n        FROM lcs_media m\n        WHERE DATE(m.uploaded_at) = ?\n        ORDER BY m.uploaded_at DESC\n    ")) {
        $media_stmt->bind_param('s', $on_date);
        $media_stmt->execute();
        $media_res = $media_stmt->get_result();
        while ($media = $media_res->fetch_assoc()) {
            $date_key = $media['media_date'];
            $item_key = $media['lcs_id'] . '|' . $media['item_name'];
            if (!isset($lcs_media_by_date_item[$date_key])) { $lcs_media_by_date_item[$date_key] = []; }
            if (!isset($lcs_media_by_date_item[$date_key][$item_key])) { $lcs_media_by_date_item[$date_key][$item_key] = []; }
            $lcs_media_by_date_item[$date_key][$item_key][] = $media;
        }
    }
}

// Build TW snapshot for only entities changed on selected date
if ($on_date !== '') {
    // Active items
    $active_items = [];
    if ($ires = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ")) { while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } }

    // IDs that changed on that date
    $tw_ids = [];
    if ($q = $conn->prepare("SELECT DISTINCT tubewell_id FROM status_history WHERE status_date = ?")) {
        $q->bind_param('s', $on_date);
        $q->execute();
        $r = $q->get_result();
        while ($x = $r->fetch_assoc()) { $tw_ids[] = (int)$x['tubewell_id']; }
    }
    // Also include IDs where only master note/media changed on that date
    if ($q2 = $conn->prepare("SELECT DISTINCT tubewell_id FROM tubewell_master_notes WHERE status_date = ?")) {
        $q2->bind_param('s', $on_date);
        $q2->execute();
        $r2 = $q2->get_result();
        while ($x = $r2->fetch_assoc()) { $id = (int)$x['tubewell_id']; if (!in_array($id, $tw_ids, true)) { $tw_ids[] = $id; } }
    }
    if ($q3 = $conn->prepare("SELECT DISTINCT tubewell_id FROM tubewell_master_media WHERE status_date = ?")) {
        $q3->bind_param('s', $on_date);
        $q3->execute();
        $r3 = $q3->get_result();
        while ($x = $r3->fetch_assoc()) { $id = (int)$x['tubewell_id']; if (!in_array($id, $tw_ids, true)) { $tw_ids[] = $id; } }
    }

    // Prefetch tubewell master notes for the selected date (exact date only)
    $tw_notes_on_date = [];
    if (!empty($tw_ids)) {
        $id_list = implode(',', array_map('intval', $tw_ids));
        $qn = "SELECT tubewell_id, note, updated_by, updated_at FROM tubewell_master_notes WHERE status_date = ? AND tubewell_id IN ($id_list)";
        if ($ns = $conn->prepare($qn)) {
            $ns->bind_param('s', $on_date);
            $ns->execute();
            $nrs = $ns->get_result();
            while ($row = $nrs->fetch_assoc()) { $tw_notes_on_date[(int)$row['tubewell_id']] = $row; }
        }

        // Prefetch tubewell master note media for the selected date
        $qm = "SELECT tubewell_id, id, file_path, file_type, uploaded_by, uploaded_at FROM tubewell_master_media WHERE status_date = ? AND tubewell_id IN ($id_list) ORDER BY uploaded_at DESC";
        if ($ms = $conn->prepare($qm)) {
            $ms->bind_param('s', $on_date);
            $ms->execute();
            $mrs = $ms->get_result();
            while ($m = $mrs->fetch_assoc()) {
                $tid = (int)$m['tubewell_id'];
                if (!isset($tw_master_media_on_date[$tid])) { $tw_master_media_on_date[$tid] = []; }
                $tw_master_media_on_date[$tid][] = $m;
            }
        }

        // Prefetch item-wise By/With for the selected date using ONLY the latest update per (date, entity, item)
        $tw_people_on_date = [];
        $has_updates_tbl = false; $tchk = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchk && $tchk->num_rows > 0) { $has_updates_tbl = true; }
        if ($has_updates_tbl) {
            $sql = "
                SELECT u.entity_id AS tubewell_id, u.item_name, u.updated_by, uc.contributor_name
                FROM updates u
                LEFT JOIN update_contributors uc ON uc.update_id = u.id
                JOIN (
                    SELECT status_date, entity_id, item_name, MAX(id) AS max_id
                    FROM updates
                    WHERE entity_type='tubewell' AND status_date = ? AND entity_id IN ($id_list)
                    GROUP BY status_date, entity_id, item_name
                ) x ON x.max_id = u.id
                WHERE u.entity_type='tubewell'
            ";
            if ($st = $conn->prepare($sql)) {
                $st->bind_param('s', $on_date);
                $st->execute();
                $rs = $st->get_result();
                while ($r = $rs->fetch_assoc()) {
                    $tid = (int)$r['tubewell_id']; $iname = $r['item_name'];
                    $primary = $r['updated_by']; $contrib = $r['contributor_name'];
                    if (!isset($tw_people_on_date[$tid])) { $tw_people_on_date[$tid] = []; }
                    if (!isset($tw_people_on_date[$tid][$iname])) { $tw_people_on_date[$tid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                    if ($primary !== null && $primary !== '' && !in_array($primary, $tw_people_on_date[$tid][$iname]['primaries'], true)) {
                        $tw_people_on_date[$tid][$iname]['primaries'][] = $primary;
                    }
                    if ($contrib !== null && $contrib !== '' && !in_array($contrib, $tw_people_on_date[$tid][$iname]['contributors'], true)) {
                        $tw_people_on_date[$tid][$iname]['contributors'][] = $contrib;
                    }
                }
            }
        }

        // Prefetch master note contributors for the selected date
        $tw_master_note_contrib_on_date = [];
        if ($colC = $conn->query("SHOW TABLES LIKE 'tubewell_master_note_contributors'")) {
            if ($colC && $colC->num_rows > 0) {
                if ($qc = $conn->prepare("SELECT tubewell_id, contributor_name FROM tubewell_master_note_contributors WHERE status_date = ? AND tubewell_id IN ($id_list) ORDER BY contributor_name")) {
                    $qc->bind_param('s', $on_date);
                    $qc->execute();
                    $rc = $qc->get_result();
                    while ($cr = $rc->fetch_assoc()) {
                        $tid = (int)$cr['tubewell_id']; $nm = $cr['contributor_name'];
                        if (!isset($tw_master_note_contrib_on_date[$tid])) { $tw_master_note_contrib_on_date[$tid] = []; }
                        if (!empty($nm) && !in_array($nm, $tw_master_note_contrib_on_date[$tid], true)) { $tw_master_note_contrib_on_date[$tid][] = $nm; }
                    }
                }
            }
        }
    }

    foreach ($tw_ids as $tid) {
        // Prefetch rows up to selected date
        $stmt = $conn->prepare("SELECT sh.*, s.site_name, tw.tubewell_name
                                 FROM status_history sh
                                 JOIN sites s ON sh.site_id = s.id
                                 JOIN tubewells tw ON sh.tubewell_id = tw.id
                                 WHERE sh.tubewell_id = ? AND sh.status_date <= ?
                                 ORDER BY sh.status_date ASC, sh.item_name ASC");
        $stmt->bind_param('is', $tid, $on_date);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows_seq = [];
        while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

        // Change on selected date?
        $had_change = false;
        if ($c = $conn->prepare("SELECT 1 FROM status_history WHERE tubewell_id = ? AND status_date = ? LIMIT 1")) {
            $c->bind_param('is', $tid, $on_date);
            $c->execute();
            $cres = $c->get_result();
            $had_change = (bool)$cres->fetch_row();
        }
        // Allow inclusion when master note/media changed even if no item change
        $has_master_change = isset($tw_notes_on_date[$tid]) || (!empty($tw_master_media_on_date[$tid] ?? []));
        if (!$had_change && !$has_master_change) { continue; }

        // Fallback names when there are no history rows
        $site_name_fallback = '';
        $tubewell_name_fallback = '';
        if (empty($rows_seq)) {
            if ($nm = $conn->prepare("SELECT s.site_name, t.tubewell_name FROM tubewells t JOIN sites s ON t.site_id = s.id WHERE t.id = ? LIMIT 1")) {
                $nm->bind_param('i', $tid);
                $nm->execute();
                $nmr = $nm->get_result();
                if ($row = $nmr->fetch_assoc()) { $site_name_fallback = $row['site_name'] ?? ''; $tubewell_name_fallback = $row['tubewell_name'] ?? ''; }
            }
        } else {
            $site_name_fallback = $rows_seq[0]['site_name'] ?? '';
            $tubewell_name_fallback = $rows_seq[0]['tubewell_name'] ?? '';
        }

        // Build snapshot for that single date (include dynamic items not in master)
        $last_known = [];
        $dynamic_items = [];
        foreach ($rows_seq as $rk) {
            if ($rk['status_date'] <= $on_date) {
                $last_known[$rk['item_name']] = $rk;
                if (!in_array($rk['item_name'], $active_items, true)) { $dynamic_items[$rk['item_name']] = true; }
            }
        }
        if (!isset($tw_rows_by_date[$on_date])) { $tw_rows_by_date[$on_date] = []; }
        if (!isset($tw_rows_by_date[$on_date][$tid])) { $tw_rows_by_date[$on_date][$tid] = []; }
        $all_items = $active_items;
        foreach ($dynamic_items as $din => $_v) { if (!in_array($din, $all_items, true)) { $all_items[] = $din; } }
        sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($all_items as $iname) {
            if (isset($last_known[$iname])) { $tw_rows_by_date[$on_date][$tid][] = $last_known[$iname]; }
            else {
                $tw_rows_by_date[$on_date][$tid][] = [
                    'tubewell_id' => $tid,
                    'item_name' => $iname,
                    'make_model' => '',
                    'size_capacity' => '',
                    'status' => '',
                    'check_hmi_local' => 0,
                    'check_web' => 0,
                    'remark' => '',
                    'created_by' => '—',
                    'updated_at' => null,
                    'site_name' => $site_name_fallback,
                    'tubewell_name' => $tubewell_name_fallback
                ];
            }
        }
        // Attach highlight map per TW
        if (!isset($tw_rows_by_date[$on_date.'__hl'])) { $tw_rows_by_date[$on_date.'__hl'] = []; }
        $map = $tw_hl[$on_date][$tid] ?? [];
        $map2 = [];
        foreach ($map as $iname => $_v) { $map2[$tid.'|'.$iname] = true; }
        $tw_rows_by_date[$on_date.'__hl'][$tid] = $map2;
    }
}

// Build LCS snapshot for only entities changed on selected date
if ($on_date !== '') {
    $active_items = [];
    if ($ires = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ")) { while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } }

    $lcs_ids = [];
    if ($q = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_status_history WHERE status_date = ?")) {
        $q->bind_param('s', $on_date);
        $q->execute();
        $r = $q->get_result();
        while ($x = $r->fetch_assoc()) { $lcs_ids[] = (int)$x['lcs_id']; }
    }
    // Also include LCS IDs where only master note/media changed on that date
    $has_sd = false;
    $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
    if ($col && $col->num_rows > 0) { $has_sd = true; }
    if ($has_sd) {
        if ($q2 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_notes WHERE status_date = ?")) {
            $q2->bind_param('s', $on_date);
            $q2->execute();
            $r2 = $q2->get_result();
            while ($x = $r2->fetch_assoc()) { $id = (int)$x['lcs_id']; if (!in_array($id, $lcs_ids, true)) { $lcs_ids[] = $id; } }
        }
    } else {
        if ($q2 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_notes WHERE DATE(updated_at) = ?")) {
            $q2->bind_param('s', $on_date);
            $q2->execute();
            $r2 = $q2->get_result();
            while ($x = $r2->fetch_assoc()) { $id = (int)$x['lcs_id']; if (!in_array($id, $lcs_ids, true)) { $lcs_ids[] = $id; } }
        }
    }
    if ($q3 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_media WHERE status_date = ?")) {
        $q3->bind_param('s', $on_date);
        $q3->execute();
        $r3 = $q3->get_result();
        while ($x = $r3->fetch_assoc()) { $id = (int)$x['lcs_id']; if (!in_array($id, $lcs_ids, true)) { $lcs_ids[] = $id; } }
    }

    // Prefetch LCS master notes for the selected date (status_date if exists else DATE(updated_at))
    $lcs_notes_on_date = [];
    if (!empty($lcs_ids)) {
        $id_list = implode(',', array_map('intval', $lcs_ids));
        $has_sd = false;
        $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
        if ($col && $col->num_rows > 0) { $has_sd = true; }
        if ($has_sd) {
            $qn = "SELECT lcs_id, note, updated_by, updated_at FROM lcs_master_notes WHERE status_date = ? AND lcs_id IN ($id_list)";
            if ($ns = $conn->prepare($qn)) { $ns->bind_param('s', $on_date); $ns->execute(); $nrs = $ns->get_result(); while ($row = $nrs->fetch_assoc()) { $lcs_notes_on_date[(int)$row['lcs_id']] = $row; } }
        } else {
            $qn = "SELECT lcs_id, note, updated_by, updated_at FROM lcs_master_notes WHERE DATE(updated_at) = ? AND lcs_id IN ($id_list)";
            if ($ns = $conn->prepare($qn)) { $ns->bind_param('s', $on_date); $ns->execute(); $nrs = $ns->get_result(); while ($row = $nrs->fetch_assoc()) { $lcs_notes_on_date[(int)$row['lcs_id']] = $row; } }
        }

        // Prefetch LCS master note media for the selected date
        $qm2 = "SELECT lcs_id, id, file_path, file_type, uploaded_by, uploaded_at FROM lcs_master_media WHERE status_date = ? AND lcs_id IN ($id_list) ORDER BY uploaded_at DESC";
        if ($ms2 = $conn->prepare($qm2)) {
            $ms2->bind_param('s', $on_date);
            $ms2->execute();
            $mrs2 = $ms2->get_result();
            while ($m = $mrs2->fetch_assoc()) {
                $lid = (int)$m['lcs_id'];
                if (!isset($lcs_master_media_on_date[$lid])) { $lcs_master_media_on_date[$lid] = []; }
                $lcs_master_media_on_date[$lid][] = $m;
            }
        }
        // Prefetch LCS item-wise By/With for the selected date using ONLY the latest update per (date, entity, item)
        $lcs_people_on_date = [];
        $has_updates_tbl2 = false; $tchkL = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchkL && $tchkL->num_rows > 0) { $has_updates_tbl2 = true; }
        if ($has_updates_tbl2) {
            $sqlL = "
                SELECT u.entity_id AS lcs_id, u.item_name, u.updated_by, uc.contributor_name
                FROM updates u
                LEFT JOIN update_contributors uc ON uc.update_id = u.id
                JOIN (
                    SELECT status_date, entity_id, item_name, MAX(id) AS max_id
                    FROM updates
                    WHERE entity_type='lcs' AND status_date = ? AND entity_id IN ($id_list)
                    GROUP BY status_date, entity_id, item_name
                ) x ON x.max_id = u.id
                WHERE u.entity_type='lcs'
            ";
            if ($stL = $conn->prepare($sqlL)) {
                $stL->bind_param('s', $on_date);
                $stL->execute();
                $rsL = $stL->get_result();
                while ($r = $rsL->fetch_assoc()) {
                    $lid = (int)$r['lcs_id']; $iname = $r['item_name'];
                    $primary = $r['updated_by']; $contrib = $r['contributor_name'];
                    if (!isset($lcs_people_on_date[$lid])) { $lcs_people_on_date[$lid] = []; }
                    if (!isset($lcs_people_on_date[$lid][$iname])) { $lcs_people_on_date[$lid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                    if ($primary !== null && $primary !== '' && !in_array($primary, $lcs_people_on_date[$lid][$iname]['primaries'], true)) {
                        $lcs_people_on_date[$lid][$iname]['primaries'][] = $primary;
                    }
                    if ($contrib !== null && $contrib !== '' && !in_array($contrib, $lcs_people_on_date[$lid][$iname]['contributors'], true)) {
                        $lcs_people_on_date[$lid][$iname]['contributors'][] = $contrib;
                    }
                }
            }
        }

        // Prefetch LCS master note contributors for the selected date
        $lcs_master_note_contrib_on_date = [];
        $colC2 = $conn->query("SHOW TABLES LIKE 'lcs_master_note_contributors'");
        if ($colC2 && $colC2->num_rows > 0) {
            if ($qc2 = $conn->prepare("SELECT lcs_id, contributor_name FROM lcs_master_note_contributors WHERE status_date = ? AND lcs_id IN ($id_list) ORDER BY contributor_name")) {
                $qc2->bind_param('s', $on_date);
                $qc2->execute();
                $rc2 = $qc2->get_result();
                while ($cr = $rc2->fetch_assoc()) {
                    $lid2 = (int)$cr['lcs_id']; $nm2 = $cr['contributor_name'];
                    if (!isset($lcs_master_note_contrib_on_date[$lid2])) { $lcs_master_note_contrib_on_date[$lid2] = []; }
                    if (!empty($nm2) && !in_array($nm2, $lcs_master_note_contrib_on_date[$lid2], true)) { $lcs_master_note_contrib_on_date[$lid2][] = $nm2; }
                }
            }
        }
    }

    foreach ($lcs_ids as $lid) {
        $stmt = $conn->prepare("SELECT h.*, s.site_name, l.lcs_name
                                 FROM lcs_status_history h
                                 JOIN sites s ON h.site_id = s.id
                                 JOIN lcs l ON h.lcs_id = l.id
                                 WHERE h.lcs_id = ? AND h.status_date <= ?
                                 ORDER BY h.status_date ASC, h.item_name ASC");
        $stmt->bind_param('is', $lid, $on_date);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows_seq = [];
        while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

        $had_change = false;
        if ($c = $conn->prepare("SELECT 1 FROM lcs_status_history WHERE lcs_id = ? AND status_date = ? LIMIT 1")) {
            $c->bind_param('is', $lid, $on_date);
            $c->execute();
            $cres = $c->get_result();
            $had_change = (bool)$cres->fetch_row();
        }
        // Allow inclusion when master note/media changed even if no item change
        $has_master_change = isset($lcs_notes_on_date[$lid]) || (!empty($lcs_master_media_on_date[$lid] ?? []));
        if (!$had_change && !$has_master_change) { continue; }

        // Fallback names when there are no history rows
        $site_name_fallback = '';
        $lcs_name_fallback = '';
        if (empty($rows_seq)) {
            if ($nm = $conn->prepare("SELECT s.site_name, l.lcs_name FROM lcs l JOIN sites s ON l.site_id = s.id WHERE l.id = ? LIMIT 1")) {
                $nm->bind_param('i', $lid);
                $nm->execute();
                $nmr = $nm->get_result();
                if ($row = $nmr->fetch_assoc()) { $site_name_fallback = $row['site_name'] ?? ''; $lcs_name_fallback = $row['lcs_name'] ?? ''; }
            }
        } else {
            $site_name_fallback = $rows_seq[0]['site_name'] ?? '';
            $lcs_name_fallback = $rows_seq[0]['lcs_name'] ?? '';
        }

        $last_known = [];
        $dynamic_items = [];
        foreach ($rows_seq as $rk) {
            if ($rk['status_date'] <= $on_date) {
                $last_known[$rk['item_name']] = $rk;
                if (!in_array($rk['item_name'], $active_items, true)) { $dynamic_items[$rk['item_name']] = true; }
            }
        }
        if (!isset($lcs_rows_by_date[$on_date])) { $lcs_rows_by_date[$on_date] = []; }
        if (!isset($lcs_rows_by_date[$on_date][$lid])) { $lcs_rows_by_date[$on_date][$lid] = []; }
        $all_items = $active_items;
        foreach ($dynamic_items as $din => $_v) { if (!in_array($din, $all_items, true)) { $all_items[] = $din; } }
        sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($all_items as $iname) {
            if (isset($last_known[$iname])) { $lcs_rows_by_date[$on_date][$lid][] = $last_known[$iname]; }
            else {
                $lcs_rows_by_date[$on_date][$lid][] = [
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
        if (!isset($lcs_rows_by_date[$on_date.'__hl'])) { $lcs_rows_by_date[$on_date.'__hl'] = []; }
        $map = $lcs_hl[$on_date][$lid] ?? [];
        $map2 = [];
        foreach ($map as $iname => $_v) { $map2[$lid.'|'.$iname] = true; }
        $lcs_rows_by_date[$on_date.'__hl'][$lid] = $map2;
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date-wise Change Report</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .table-container{ overflow-x:auto; }
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
            overflow-wrap:anywhere; 
            vertical-align: top;
        }
        tr.row-highlight { background-color:#fff7e6; }
        .section-title { margin:1rem 0 .5rem; color:#2d3748; }
        
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
<?php include 'header.php'; ?>
<div class="container">
    <div class="card">
        <h2 style="color:#2d3748; margin-bottom:1rem;">📅 Date-wise Change Report</h2>
        <form method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end; margin-bottom:1rem;">
            <div class="form-group" style="min-width:220px;">
                <label class="form-label">Select Date</label>
                <input type="date" name="on_date" class="form-control" value="<?php echo htmlspecialchars($on_date); ?>" required>
            </div>
            <div class="form-group" style="min-width:140px;">
                <button type="submit" class="btn btn-primary" style="width:100%">🔎 View</button>
            </div>
        </form>

        <?php if ($on_date!==''): ?>
            <div style="display:flex; justify-content:flex-end; margin-bottom:10px; gap:10px;">
                <a class="btn btn-secondary" href="generate_date_change_report.php?on_date=<?php echo urlencode($on_date); ?>">⬇️ Export PDF</a>
            </div>
            <?php if (!empty($tw_rows_by_date)): ?>
                <h3 class="section-title">🛠️ Tubewell Item Updates (<?php echo htmlspecialchars($on_date); ?>)</h3>
                <?php $twBuckets = $tw_rows_by_date[$on_date] ?? []; $hlAll = $tw_rows_by_date[$on_date.'__hl'] ?? []; ?>
                <?php foreach ($twBuckets as $tid => $rows): ?>
                    <?php $hl = $hlAll[$tid] ?? []; $first = $rows[0] ?? null; ?>
                    <div class="table-container" style="margin-bottom:1rem;">
                        <h4 style="margin:0 0 .5rem 0; color:#4a5568;">Site: <?php echo htmlspecialchars($first['site_name'] ?? ''); ?> | Tubewell: <?php echo htmlspecialchars($first['tubewell_name'] ?? ''); ?> (<?php echo count($rows); ?> items)</h4>
                        <?php if (isset($tw_notes_on_date[$tid]) && trim((string)($tw_notes_on_date[$tid]['note'] ?? '')) !== ''): $mn = $tw_notes_on_date[$tid]; ?>
                        <div class="card" style="margin:0 0 .5rem 0; border:1px solid #e5e7eb; background-color:#f7fafc;">
                            <h4 style="margin:0 0 .25rem 0; color:#2d3748; font-size:1rem;">📝 Master Note (<?php echo htmlspecialchars($on_date); ?>)</h4>
                            <div style="white-space:pre-wrap; line-height:1.5; color:#2d3748; margin-bottom:.25rem;">
                                <?php echo nl2br(htmlspecialchars($mn['note'])); ?>
                            </div>
                            <div style="font-size:.85rem; color:#4a5568;">
                                Updated by <strong><?php echo htmlspecialchars($mn['updated_by'] ?? '—'); ?></strong>
                                on <?php echo isset($mn['updated_at']) ? date('d M Y H:i', strtotime($mn['updated_at'])) : '—'; ?>
                            </div>
                            <?php $mnc = $tw_master_note_contrib_on_date[$tid] ?? []; if (!empty($mnc)): ?>
                                <div style="font-size:.85rem; color:#374151; margin-top:.25rem;">
                                    <strong>Contributors:</strong> <?php echo htmlspecialchars(implode(', ', $mnc)); ?>
                                </div>
                            <?php endif; ?>
                            <?php $mm = $tw_master_media_on_date[$tid] ?? []; if (!empty($mm)): ?>
                                <div style="margin-top:.25rem;">
                                    <div style="font-weight:600; margin-bottom:.15rem;">Photo / Video</div>
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
                                    <th width="8%">Site</th>
                                    <th width="8%">Tubewell</th>
                                    <th width="8%">Item</th>
                                    <th width="8%">Make/Model</th>
                                    <th width="8%">Size/Cap.</th>
                                    <th width="8%">Status</th>
                                    <th width="6%">HMI/Local</th>
                                    <th width="6%">Web</th>
                                    <th width="10%">Remark</th>
                                    <th width="8%">Photo / Video</th>
                                    <th width="8%">Updated By</th>
                                    <th width="10%">Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; foreach ($rows as $r): 
                                    $isSel = isset($hl[$r['tubewell_id'].'|'.$r['item_name']]);
                                    $item_media = $tw_media_by_date_item[$on_date][$r['tubewell_id'].'|'.$r['item_name']] ?? [];
                                ?>
                                <tr class="<?php echo $isSel ? 'row-highlight' : ''; ?>">
                                    <td style="text-align:center;">&nbsp;<?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($r['site_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['tubewell_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($r['item_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($r['make_model'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['size_capacity'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                    <td style="text-align:center;"><?php echo (((int)($r['check_hmi_local'] ?? 0)) === 1) ? '✅' : ((((int)($r['check_hmi_local'] ?? 0)) === 2) ? '❌' : '—'); ?></td>
                                    <td style="text-align:center;"><?php echo (((int)($r['check_web'] ?? 0)) === 1) ? '✅' : ((((int)($r['check_web'] ?? 0)) === 2) ? '❌' : '—'); ?></td>
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
                                            $map = $tw_people_on_date[$r['tubewell_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
                                            $p1 = $map['primaries'] ?? []; $p2 = $map['contributors'] ?? [];
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
            <?php endif; ?>

            <?php if (!empty($lcs_rows_by_date)): ?>
                <h3 class="section-title">🧰 LCS Item Updates (<?php echo htmlspecialchars($on_date); ?>)</h3>
                <?php $lcsBuckets = $lcs_rows_by_date[$on_date] ?? []; $hlAll = $lcs_rows_by_date[$on_date.'__hl'] ?? []; ?>
                <?php foreach ($lcsBuckets as $lid => $rows): ?>
                    <?php $hl = $hlAll[$lid] ?? []; $first = $rows[0] ?? null; ?>
                    <div class="table-container" style="margin-bottom:1rem;">
                        <h4 style="margin:0 0 .5rem 0; color:#4a5568;">Site: <?php echo htmlspecialchars($first['site_name'] ?? ''); ?> | LCS: <?php echo htmlspecialchars($first['lcs_name'] ?? ''); ?> (<?php echo count($rows); ?> items)</h4>
                        <?php if (isset($lcs_notes_on_date[$lid]) && trim((string)($lcs_notes_on_date[$lid]['note'] ?? '')) !== ''): $mn = $lcs_notes_on_date[$lid]; ?>
                        <div class="card" style="margin:0 0 .5rem 0; border:1px solid #e5e7eb; background-color:#f7fafc;">
                            <h4 style="margin:0 0 .25rem 0; color:#2d3748; font-size:1rem;">📝 LCS Master Note (<?php echo htmlspecialchars($on_date); ?>)</h4>
                            <div style="white-space:pre-wrap; line-height:1.5; color:#2d3748; margin-bottom:.25rem;">
                                <?php echo nl2br(htmlspecialchars($mn['note'])); ?>
                            </div>
                            <div style="font-size:.85rem; color:#4a5568;">
                                Updated by <strong><?php echo htmlspecialchars($mn['updated_by'] ?? '—'); ?></strong>
                                on <?php echo isset($mn['updated_at']) ? date('d M Y H:i', strtotime($mn['updated_at'])) : '—'; ?>
                            </div>
                            <?php $lmnc = $lcs_master_note_contrib_on_date[$lid] ?? []; if (!empty($lmnc)): ?>
                                <div style="font-size:.85rem; color:#374151; margin-top:.25rem;">
                                    <strong>Contributors:</strong> <?php echo htmlspecialchars(implode(', ', $lmnc)); ?>
                                </div>
                            <?php endif; ?>
                            <?php $mm = $lcs_master_media_on_date[$lid] ?? []; if (!empty($mm)): ?>
                                <div style="margin-top:.25rem;">
                                    <div style="font-weight:600; margin-bottom:.15rem;">Photo / Video</div>
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
                                    <th width="8%">Site</th>
                                    <th width="8%">LCS</th>
                                    <th width="8%">Item</th>
                                    <th width="8%">Make/Model</th>
                                    <th width="8%">Size/Cap.</th>
                                    <th width="8%">Status</th>
                                    <th width="10%">Remark</th>
                                    <th width="8%">Photo / Video</th>
                                    <th width="8%">Updated By</th>
                                    <th width="10%">Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; foreach ($rows as $r): 
                                    $isSel = isset($hl[$r['lcs_id'].'|'.$r['item_name']]);
                                    $item_media = $lcs_media_by_date_item[$on_date][$r['lcs_id'].'|'.$r['item_name']] ?? [];
                                ?>
                                <tr class="<?php echo $isSel ? 'row-highlight' : ''; ?>">
                                    <td style="text-align:center;">&nbsp;<?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($r['site_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['lcs_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($r['item_name']); ?></strong></td>
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
                                            $map = $lcs_people_on_date[$r['lcs_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
                                            $p1 = $map['primaries'] ?? []; $p2 = $map['contributors'] ?? [];
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
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">Select a date to view the report.</div>
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
