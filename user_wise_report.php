<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit(); }

include 'db_config.php';

// Read filters
$sel_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
// (Global snapshots will use from_date/to_date only)

// Build users list from multiple sources
$users = [];
$queries = [
    "SELECT DISTINCT created_by AS uname FROM status_history WHERE created_by IS NOT NULL AND created_by <> ''",
    "SELECT DISTINCT created_by AS uname FROM lcs_status_history WHERE created_by IS NOT NULL AND created_by <> ''",
    "SELECT DISTINCT updated_by AS uname FROM tubewell_master_notes WHERE updated_by IS NOT NULL AND updated_by <> ''",
    "SELECT DISTINCT updated_by AS uname FROM lcs_master_notes WHERE updated_by IS NOT NULL AND updated_by <> ''"
];
// Conditionally include users from updates tables if they exist
$has_updates_tbl = false; $tchk = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchk && $tchk->num_rows > 0) { $has_updates_tbl = true; }
$has_uc_tbl = false; $tchk2 = $conn->query("SHOW TABLES LIKE 'update_contributors'"); if ($tchk2 && $tchk2->num_rows > 0) { $has_uc_tbl = true; }
if ($has_updates_tbl) { $queries[] = "SELECT DISTINCT updated_by AS uname FROM updates WHERE updated_by IS NOT NULL AND updated_by <> ''"; }
if ($has_uc_tbl) { $queries[] = "SELECT DISTINCT contributor_name AS uname FROM update_contributors WHERE contributor_name IS NOT NULL AND contributor_name <> ''"; }
foreach ($queries as $q) {
    if ($res = $conn->query($q)) {
        while ($r = $res->fetch_assoc()) {
            $u = $r['uname'];
            if ($u !== null && $u !== '' && !in_array($u, $users, true)) { $users[] = $u; }
        }
    }
}
sort($users, SORT_NATURAL | SORT_FLAG_CASE);

// Common lists (for names during joins)
$sites = [];
if ($sres = $conn->query("SELECT id, site_name FROM sites ")) { while ($r = $sres->fetch_assoc()) { $sites[$r['id']] = $r['site_name']; } }

// Preload LCS updates using only the latest update per (date, entity, item)
$lcs_updates_people_by_date = [];
$lcs_updates_people_by_date_item = [];
if ($from_date !== '' && $to_date !== '' && $has_updates_tbl) {
    $sql = "
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
    if ($st = $conn->prepare($sql)) {
        $st->bind_param('ss', $from_date, $to_date);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) {
            $d = $r['status_date']; $lid = (int)$r['entity_id']; $iname = $r['item_name'];
            $primary = $r['updated_by']; $contrib = $r['contributor_name'];
            if (!isset($lcs_updates_people_by_date[$d])) { $lcs_updates_people_by_date[$d] = []; }
            if (!isset($lcs_updates_people_by_date[$d][$lid])) { $lcs_updates_people_by_date[$d][$lid] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $lcs_updates_people_by_date[$d][$lid]['primaries'], true)) {
                $lcs_updates_people_by_date[$d][$lid]['primaries'][] = $primary;
            }
            if (!isset($lcs_updates_people_by_date_item[$d])) { $lcs_updates_people_by_date_item[$d] = []; }
            if (!isset($lcs_updates_people_by_date_item[$d][$lid])) { $lcs_updates_people_by_date_item[$d][$lid] = []; }
            if (!isset($lcs_updates_people_by_date_item[$d][$lid][$iname])) { $lcs_updates_people_by_date_item[$d][$lid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $lcs_updates_people_by_date_item[$d][$lid][$iname]['primaries'], true)) {
                $lcs_updates_people_by_date_item[$d][$lid][$iname]['primaries'][] = $primary;
            }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $lcs_updates_people_by_date[$d][$lid]['contributors'], true)) {
                $lcs_updates_people_by_date[$d][$lid]['contributors'][] = $contrib;
            }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $lcs_updates_people_by_date_item[$d][$lid][$iname]['contributors'], true)) {
                $lcs_updates_people_by_date_item[$d][$lid][$iname]['contributors'][] = $contrib;
            }
        }
    }
}

// Preload Tubewell updates using only the latest update per (date, entity, item)
$tw_updates_people_by_date = [];
$tw_updates_people_by_date_item = [];
if ($from_date !== '' && $to_date !== '' && $has_updates_tbl) {
    $sql = "
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
    if ($st = $conn->prepare($sql)) {
        $st->bind_param('ss', $from_date, $to_date);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) {
            $d = $r['status_date']; $tid = (int)$r['entity_id']; $iname = $r['item_name'];
            $primary = $r['updated_by']; $contrib = $r['contributor_name'];
            if (!isset($tw_updates_people_by_date[$d])) { $tw_updates_people_by_date[$d] = []; }
            if (!isset($tw_updates_people_by_date[$d][$tid])) { $tw_updates_people_by_date[$d][$tid] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $tw_updates_people_by_date[$d][$tid]['primaries'], true)) {
                $tw_updates_people_by_date[$d][$tid]['primaries'][] = $primary;
            }
            if (!isset($tw_updates_people_by_date_item[$d])) { $tw_updates_people_by_date_item[$d] = []; }
            if (!isset($tw_updates_people_by_date_item[$d][$tid])) { $tw_updates_people_by_date_item[$d][$tid] = []; }
            if (!isset($tw_updates_people_by_date_item[$d][$tid][$iname])) { $tw_updates_people_by_date_item[$d][$tid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($primary !== null && $primary !== '' && !in_array($primary, $tw_updates_people_by_date_item[$d][$tid][$iname]['primaries'], true)) {
                $tw_updates_people_by_date_item[$d][$tid][$iname]['primaries'][] = $primary;
            }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $tw_updates_people_by_date[$d][$tid]['contributors'], true)) {
                $tw_updates_people_by_date[$d][$tid]['contributors'][] = $contrib;
            }
            if ($contrib !== null && $contrib !== '' && !in_array($contrib, $tw_updates_people_by_date_item[$d][$tid][$iname]['contributors'], true)) {
                $tw_updates_people_by_date_item[$d][$tid][$iname]['contributors'][] = $contrib;
            }
        }
    }
}

// Precompute highlight maps and allowed dates (dates where selected user did any update)
$tw_hl = [];
$lcs_hl = [];
$allowed_dates = [];
// Map of updates participation by date and tubewell
$tw_update_hl = [];
// Map of updates participation by date and lcs
$lcs_update_hl = [];
if ($sel_user !== '' && $from_date !== '' && $to_date !== '') {
    // Tubewell highlights
    if ($hls = $conn->prepare("SELECT status_date, tubewell_id, item_name FROM status_history WHERE created_by = ? AND status_date BETWEEN ? AND ?")) {
        $hls->bind_param('sss', $sel_user, $from_date, $to_date);
        $hls->execute();
        $hlsr = $hls->get_result();
        while ($r = $hlsr->fetch_assoc()) {
            $d = $r['status_date']; $tid = (int)$r['tubewell_id']; $it = $r['item_name'];
            if (!isset($tw_hl[$d])) { $tw_hl[$d] = []; }
            if (!isset($tw_hl[$d][$tid])) { $tw_hl[$d][$tid] = []; }
            $tw_hl[$d][$tid][$it] = true;
            $allowed_dates[$d] = true;
        }
    }
    // LCS highlights
    if ($hls = $conn->prepare("SELECT status_date, lcs_id, item_name FROM lcs_status_history WHERE created_by = ? AND status_date BETWEEN ? AND ?")) {
        $hls->bind_param('sss', $sel_user, $from_date, $to_date);
        $hls->execute();
        $hlsr = $hls->get_result();
        while ($r = $hlsr->fetch_assoc()) {
            $d = $r['status_date']; $lid = (int)$r['lcs_id']; $it = $r['item_name'];
            if (!isset($lcs_hl[$d])) { $lcs_hl[$d] = []; }
            if (!isset($lcs_hl[$d][$lid])) { $lcs_hl[$d][$lid] = []; }
            $lcs_hl[$d][$lid][$it] = true;
            $allowed_dates[$d] = true;
        }
    }
    // Add allowed dates from Tubewell master notes updated by this user
    if ($mn = $conn->prepare("SELECT DISTINCT status_date FROM tubewell_master_notes WHERE updated_by = ? AND status_date BETWEEN ? AND ?")) {
        $mn->bind_param('sss', $sel_user, $from_date, $to_date);
        $mn->execute();
        $mnr = $mn->get_result();
        while ($row = $mnr->fetch_assoc()) { $allowed_dates[$row['status_date']] = true; }
    }
    // Include dates from tubewell master note contributors with this user
    $has_tw_mn_c = $conn->query("SHOW TABLES LIKE 'tubewell_master_note_contributors'");
    if ($has_tw_mn_c && $has_tw_mn_c->num_rows > 0) {
        if ($st = $conn->prepare("SELECT DISTINCT status_date FROM tubewell_master_note_contributors WHERE contributor_name = ? AND status_date BETWEEN ? AND ?")) {
            $st->bind_param('sss', $sel_user, $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) { $allowed_dates[$r['status_date']] = true; }
        }
    }
    // Add allowed dates from LCS master notes updated by this user (status_date if available else DATE(updated_at))
    $has_sd = false; $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'"); if ($col && $col->num_rows > 0) { $has_sd = true; }
    if ($has_sd) {
        if ($mn = $conn->prepare("SELECT DISTINCT status_date FROM lcs_master_notes WHERE updated_by = ? AND status_date BETWEEN ? AND ?")) {
            $mn->bind_param('sss', $sel_user, $from_date, $to_date);
            $mn->execute();
            $mnr = $mn->get_result();
            while ($row = $mnr->fetch_assoc()) { $allowed_dates[$row['status_date']] = true; }
        }
    } else {
        if ($mn = $conn->prepare("SELECT DISTINCT DATE(updated_at) AS d FROM lcs_master_notes WHERE updated_by = ? AND DATE(updated_at) BETWEEN ? AND ?")) {
            $mn->bind_param('sss', $sel_user, $from_date, $to_date);
            $mn->execute();
            $mnr = $mn->get_result();
            while ($row = $mnr->fetch_assoc()) { $allowed_dates[$row['d']] = true; }
        }
    }
}

// Also mark dates/entities from Updates participation (primary or contributor)
if ($sel_user !== '' && $from_date !== '' && $to_date !== '' && $has_updates_tbl) {
    // Tubewell updates participation
    foreach ($tw_updates_people_by_date as $d => $byTid) {
        foreach ($byTid as $tid => $ppl) {
            $p1 = $ppl['primaries'] ?? [];
            $p2 = $ppl['contributors'] ?? [];
            if (in_array($sel_user, $p1, true) || in_array($sel_user, $p2, true)) {
                $allowed_dates[$d] = true;
                if (!isset($tw_update_hl[$d])) { $tw_update_hl[$d] = []; }
                $tw_update_hl[$d][$tid] = true;
            }
        }
    }
    // LCS updates participation
    foreach ($lcs_updates_people_by_date as $d => $byLid) {
        foreach ($byLid as $lid => $ppl) {
            $p1 = $ppl['primaries'] ?? [];
            $p2 = $ppl['contributors'] ?? [];
            if (in_array($sel_user, $p1, true) || in_array($sel_user, $p2, true)) {
                $allowed_dates[$d] = true;
                if (!isset($lcs_update_hl[$d])) { $lcs_update_hl[$d] = []; }
                $lcs_update_hl[$d][$lid] = true;
            }
        }
    }
}

// Results containers
$tw_rows_by_date = [];
$lcs_rows_by_date = [];
$tw_master_by_date = [];
$lcs_master_by_date = [];

// Preload all media for the date range (strictly by upload date)
$tw_media_by_date_item = [];
$lcs_media_by_date_item = [];
// Master note media maps (date-wise)
$tw_master_media_by_date = [];
$lcs_master_media_by_date = [];
// Master note contributors maps
$tw_mn_contrib_by_date = [];
$lcs_mn_contrib_by_date = [];
if ($from_date !== '' && $to_date !== '') {
    // Preload Tubewell master note contributors
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

    // Preload LCS master note contributors
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
    // Preload tubewell media by DATE(uploaded_at)
    if ($media_stmt = $conn->prepare("\n        SELECT m.*, DATE(m.uploaded_at) AS media_date\n        FROM media_uploads m\n        WHERE DATE(m.uploaded_at) BETWEEN ? AND ?\n        ORDER BY m.uploaded_at DESC\n    ")) {
        $media_stmt->bind_param('ss', $from_date, $to_date);
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

    // Preload LCS media by DATE(uploaded_at)
    if ($media_stmt = $conn->prepare("\n        SELECT m.*, DATE(m.uploaded_at) AS media_date\n        FROM lcs_media m\n        WHERE DATE(m.uploaded_at) BETWEEN ? AND ?\n        ORDER BY m.uploaded_at DESC\n    ")) {
        $media_stmt->bind_param('ss', $from_date, $to_date);
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

    // Preload tubewell master note media by status_date, filtered by selected user if provided
    if ($sel_user !== '' && $mm = $conn->prepare("SELECT tubewell_id, id, file_path, file_type, uploaded_by, uploaded_at, status_date FROM tubewell_master_media WHERE uploaded_by = ? AND status_date BETWEEN ? AND ? ORDER BY uploaded_at DESC")) {
        $mm->bind_param('sss', $sel_user, $from_date, $to_date);
        $mm->execute();
        $mrs = $mm->get_result();
        while ($row = $mrs->fetch_assoc()) {
            $dk = $row['status_date']; $tid = (int)$row['tubewell_id'];
            if (!isset($tw_master_media_by_date[$dk])) { $tw_master_media_by_date[$dk] = []; }
            if (!isset($tw_master_media_by_date[$dk][$tid])) { $tw_master_media_by_date[$dk][$tid] = []; }
            $tw_master_media_by_date[$dk][$tid][] = $row;
        }
    } elseif ($mm = $conn->prepare("SELECT tubewell_id, id, file_path, file_type, uploaded_by, uploaded_at, status_date FROM tubewell_master_media WHERE status_date BETWEEN ? AND ? ORDER BY uploaded_at DESC")) {
        // Fallback: no user selected; keep previous behavior
        $mm->bind_param('ss', $from_date, $to_date);
        $mm->execute();
        $mrs = $mm->get_result();
        while ($row = $mrs->fetch_assoc()) {
            $dk = $row['status_date']; $tid = (int)$row['tubewell_id'];
            if (!isset($tw_master_media_by_date[$dk])) { $tw_master_media_by_date[$dk] = []; }
            if (!isset($tw_master_media_by_date[$dk][$tid])) { $tw_master_media_by_date[$dk][$tid] = []; }
            $tw_master_media_by_date[$dk][$tid][] = $row;
        }
    }

    // Preload LCS master note media by status_date, filtered by selected user if provided
    if ($sel_user !== '' && $mm2 = $conn->prepare("SELECT lcs_id, id, file_path, file_type, uploaded_by, uploaded_at, status_date FROM lcs_master_media WHERE uploaded_by = ? AND status_date BETWEEN ? AND ? ORDER BY uploaded_at DESC")) {
        $mm2->bind_param('sss', $sel_user, $from_date, $to_date);
        $mm2->execute();
        $mrs2 = $mm2->get_result();
        while ($row = $mrs2->fetch_assoc()) {
            $dk = $row['status_date']; $lid = (int)$row['lcs_id'];
            if (!isset($lcs_master_media_by_date[$dk])) { $lcs_master_media_by_date[$dk] = []; }
            if (!isset($lcs_master_media_by_date[$dk][$lid])) { $lcs_master_media_by_date[$dk][$lid] = []; }
            $lcs_master_media_by_date[$dk][$lid][] = $row;
        }
    } elseif ($mm2 = $conn->prepare("SELECT lcs_id, id, file_path, file_type, uploaded_by, uploaded_at, status_date FROM lcs_master_media WHERE status_date BETWEEN ? AND ? ORDER BY uploaded_at DESC")) {
        // Fallback when no user filter
        $mm2->bind_param('ss', $from_date, $to_date);
        $mm2->execute();
        $mrs2 = $mm2->get_result();
        while ($row = $mrs2->fetch_assoc()) {
            $dk = $row['status_date']; $lid = (int)$row['lcs_id'];
            if (!isset($lcs_master_media_by_date[$dk])) { $lcs_master_media_by_date[$dk] = []; }
            if (!isset($lcs_master_media_by_date[$dk][$lid])) { $lcs_master_media_by_date[$dk][$lid] = []; }
            $lcs_master_media_by_date[$dk][$lid][] = $row;
        }
    }
}

// Build Tubewell master notes map for selected user by date (for inclusion and rendering)
if ($sel_user !== '' && $from_date !== '' && $to_date !== '') {
    if ($tmn = $conn->prepare("SELECT tubewell_id, status_date, note, updated_by, updated_at FROM tubewell_master_notes WHERE updated_by = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC, updated_at ASC")) {
        $tmn->bind_param('sss', $sel_user, $from_date, $to_date);
        $tmn->execute();
        $trs = $tmn->get_result();
        while ($row = $trs->fetch_assoc()) {
            $dk = $row['status_date']; $tid = (int)$row['tubewell_id'];
            if (!isset($tw_master_by_date[$dk])) { $tw_master_by_date[$dk] = []; }
            if (!isset($tw_master_by_date[$dk][$tid])) { $tw_master_by_date[$dk][$tid] = []; }
            $tw_master_by_date[$dk][$tid][] = $row;
        }
    }
}

// Build Tubewell snapshots globally if filters present
if ($from_date !== '' && $to_date !== '') {
    // Active items
    $active_items = [];
    if ($ires = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ")) {
        while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; }
    }
    // Find all tubewell_ids that changed in range or have master note/media/updates by this user
    $tw_ids = [];
    if ($twq = $conn->prepare("SELECT DISTINCT tubewell_id FROM status_history WHERE status_date BETWEEN ? AND ?")) {
        $twq->bind_param('ss', $from_date, $to_date);
        $twq->execute();
        $twr = $twq->get_result();
        while ($row = $twr->fetch_assoc()) { $tw_ids[] = (int)$row['tubewell_id']; }
    }
    if ($twq2 = $conn->prepare("SELECT DISTINCT tubewell_id FROM tubewell_master_notes WHERE updated_by = ? AND status_date BETWEEN ? AND ?")) {
        $twq2->bind_param('sss', $sel_user, $from_date, $to_date);
        $twq2->execute();
        $twr2 = $twq2->get_result();
        while ($row = $twr2->fetch_assoc()) { $id=(int)$row['tubewell_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
    }
    if ($twq3 = $conn->prepare("SELECT DISTINCT tubewell_id FROM tubewell_master_media WHERE uploaded_by = ? AND status_date BETWEEN ? AND ?")) {
        $twq3->bind_param('sss', $sel_user, $from_date, $to_date);
        $twq3->execute();
        $twr3 = $twq3->get_result();
        while ($row = $twr3->fetch_assoc()) { $id=(int)$row['tubewell_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
    }
    // Include tubewell IDs from updates where user is primary (updated_by)
    if ($has_updates_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT entity_id FROM updates WHERE entity_type='tubewell' AND updated_by = ? AND status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
        }
    }
    // Include tubewell IDs from updates where user is a contributor
    if ($has_updates_tbl && $has_uc_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT up.entity_id FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='tubewell' AND uc.contributor_name = ? AND up.status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$tw_ids,true)) { $tw_ids[]=$id; } }
        }
    }

    // Highlights are precomputed globally above in $tw_hl and filtered via $allowed_dates

    foreach ($tw_ids as $tid) {
        // Prefetch all rows up to end date for this tubewell
        $q = "SELECT sh.*, s.site_name, tw.tubewell_name
              FROM status_history sh
              JOIN sites s ON sh.site_id = s.id
              JOIN tubewells tw ON sh.tubewell_id = tw.id
              WHERE sh.tubewell_id = ? AND sh.status_date <= ?
              ORDER BY sh.status_date ASC, sh.item_name ASC";
        $stmt = $conn->prepare($q);
        $stmt->bind_param('is', $tid, $to_date);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows_seq = [];
        while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

        // Change dates for this tubewell in range
        $chg = $conn->prepare("SELECT DISTINCT status_date FROM status_history WHERE tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
        $chg->bind_param('iss', $tid, $from_date, $to_date);
        $chg->execute();
        $crs = $chg->get_result();
        $change_dates = [];
        while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

        // Snapshot (include dynamic items)
        $last_known = [];
        $dynamic_items = [];
        $p = 0; $n = count($rows_seq);
        $cur = strtotime($from_date);
        $end = strtotime($to_date);
        while ($cur !== false && $cur <= $end) {
            $d = date('Y-m-d', $cur);
            while ($p < $n && $rows_seq[$p]['status_date'] <= $d) {
                $rk = $rows_seq[$p];
                $last_known[$rk['item_name']] = $rk;
                if (!in_array($rk['item_name'], $active_items, true)) { $dynamic_items[$rk['item_name']] = true; }
                $p++;
            }
            $has_item_change = isset($change_dates[$d]) && isset($allowed_dates[$d]) && isset($tw_hl[$d]) && isset($tw_hl[$d][$tid]) && !empty($tw_hl[$d][$tid]);
            // Master change triggers when this user updated a master note, uploaded master media, or is listed as a contributor on this date
            $is_mn_contrib = in_array($sel_user, $tw_mn_contrib_by_date[$d][$tid] ?? [], true);
            $has_master_change = (!empty($tw_master_by_date[$d][$tid] ?? [])) || (!empty($tw_master_media_by_date[$d][$tid] ?? [])) || $is_mn_contrib;
            $has_update_change = isset($tw_update_hl[$d]) && isset($tw_update_hl[$d][$tid]);
            if ($has_item_change || $has_master_change || $has_update_change) {
                if (!isset($tw_rows_by_date[$d])) { $tw_rows_by_date[$d] = []; }
                if (!isset($tw_rows_by_date[$d][$tid])) { $tw_rows_by_date[$d][$tid] = []; }
                $all_items = $active_items;
                foreach ($dynamic_items as $din => $_v) { if (!in_array($din, $all_items, true)) { $all_items[] = $din; } }
                sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);
                foreach ($all_items as $iname) {
                    if (isset($last_known[$iname])) { $tw_rows_by_date[$d][$tid][] = $last_known[$iname]; }
                    else {
                        // Fallback names when no history row exists
                        $site_name_fallback = '';
                        $tubewell_name_fallback = '';
                        if (!empty($rows_seq)) {
                            $site_name_fallback = $rows_seq[0]['site_name'] ?? ($sites[$rows_seq[0]['site_id']] ?? '');
                            $tubewell_name_fallback = $rows_seq[0]['tubewell_name'] ?? '';
                        } else {
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
                // Attach highlight map per tubewell using composite key: tubewell_id|item_name
                if (!isset($tw_rows_by_date[$d.'__hl'])) { $tw_rows_by_date[$d.'__hl'] = []; }
                $map = $tw_hl[$d][$tid] ?? [];
                $map2 = [];
                foreach ($map as $iname => $_v) { $map2[$tid.'|'.$iname] = true; }
                $tw_rows_by_date[$d.'__hl'][$tid] = $map2;
            }
            $cur = strtotime('+1 day', $cur);
        }
    }
}

// Build Tubewell Master Notes exact-date map per tubewell
$tw_note_on_date = [];
if ($from_date !== '' && $to_date !== '') {
    // Collect all tubewell IDs present in tw_rows_by_date
    $tids_set = [];
    foreach ($tw_rows_by_date as $dk => $buckets) {
        if (strpos($dk,'__hl')!==false) continue;
        foreach ($buckets as $tid => $_rows) { $tids_set[$tid] = true; }
    }
    if (!empty($tids_set)) {
        $id_list = implode(',', array_map('intval', array_keys($tids_set)));
        $q = "SELECT tubewell_id, status_date, note, updated_by, updated_at FROM tubewell_master_notes WHERE tubewell_id IN ($id_list) AND status_date BETWEEN ? AND ? ORDER BY tubewell_id ASC, status_date ASC";
        if ($st = $conn->prepare($q)) {
            $st->bind_param('ss', $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($row = $rs->fetch_assoc()) { $tw_note_on_date[$row['status_date']][(int)$row['tubewell_id']] = $row; }
        }
    }
}

// Build LCS Master Notes buckets using DATE(updated_at) or status_date for the selected user (used for inclusion/highlight)
$lcs_master_by_date = [];
if ($sel_user !== '' && $from_date !== '' && $to_date !== '') {
    $has_sd2 = false; $col2 = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'"); if ($col2 && $col2->num_rows > 0) { $has_sd2 = true; }
    if ($has_sd2) {
        if ($slm = $conn->prepare("SELECT lmn.lcs_id, lmn.status_date AS d, lmn.note, lmn.updated_by, lmn.updated_at, s.site_name, l.lcs_name
                                    FROM lcs_master_notes lmn
                                    JOIN lcs l ON lmn.lcs_id = l.id
                                    JOIN sites s ON l.site_id = s.id
                                    WHERE lmn.updated_by = ? AND lmn.status_date BETWEEN ? AND ?
                                    ORDER BY lmn.status_date ASC, s.site_name ASC, l.lcs_name ASC")) {
            $slm->bind_param('sss', $sel_user, $from_date, $to_date);
            $slm->execute();
            $res2 = $slm->get_result();
            while ($r = $res2->fetch_assoc()) {
                $d = $r['d']; $lid = (int)$r['lcs_id'];
                if (!isset($lcs_master_by_date[$d])) { $lcs_master_by_date[$d] = []; }
                if (!isset($lcs_master_by_date[$d][$lid])) { $lcs_master_by_date[$d][$lid] = []; }
                $lcs_master_by_date[$d][$lid][] = $r;
            }
        }
    } else {
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
                $d = $r['d']; $lid = (int)$r['lcs_id'];
                if (!isset($lcs_master_by_date[$d])) { $lcs_master_by_date[$d] = []; }
                if (!isset($lcs_master_by_date[$d][$lid])) { $lcs_master_by_date[$d][$lid] = []; }
                $lcs_master_by_date[$d][$lid][] = $r;
            }
        }
    }
}

// Build LCS Master Notes exact-date map per lcs (placed AFTER lcs_rows_by_date is computed)
$lcs_note_on_date = [];

// Build LCS snapshots globally if filters present
if ($from_date !== '' && $to_date !== '') {
    // Active items
    $active_items = [];
    if ($ires = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ")) {
        while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; }
    }
    // Find all lcs_ids that changed in range or have master note/media/updates by this user
    $lcs_ids = [];
    if ($lq = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_status_history WHERE status_date BETWEEN ? AND ?")) {
        $lq->bind_param('ss', $from_date, $to_date);
        $lq->execute();
        $lr = $lq->get_result();
        while ($row = $lr->fetch_assoc()) { $lcs_ids[] = (int)$row['lcs_id']; }
    }
    $has_sd2 = false; $col2 = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'"); if ($col2 && $col2->num_rows > 0) { $has_sd2 = true; }
    if ($has_sd2) {
        if ($lq2 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_notes WHERE updated_by = ? AND status_date BETWEEN ? AND ?")) {
            $lq2->bind_param('sss', $sel_user, $from_date, $to_date);
            $lq2->execute();
            $lr2 = $lq2->get_result();
            while ($row = $lr2->fetch_assoc()) { $id=(int)$row['lcs_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    } else {
        if ($lq2 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_notes WHERE updated_by = ? AND DATE(updated_at) BETWEEN ? AND ?")) {
            $lq2->bind_param('sss', $sel_user, $from_date, $to_date);
            $lq2->execute();
            $lr2 = $lq2->get_result();
            while ($row = $lr2->fetch_assoc()) { $id=(int)$row['lcs_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    }
    if ($lq3 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_media WHERE uploaded_by = ? AND status_date BETWEEN ? AND ?")) {
        $lq3->bind_param('sss', $sel_user, $from_date, $to_date);
        $lq3->execute();
        $lr3 = $lq3->get_result();
        while ($row = $lr3->fetch_assoc()) { $id=(int)$row['lcs_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
    }
    // Include LCS IDs from updates where user is primary (updated_by)
    if ($has_updates_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT entity_id FROM updates WHERE entity_type='lcs' AND updated_by = ? AND status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    }
    // Include LCS IDs from updates where user is a contributor
    if ($has_updates_tbl && $has_uc_tbl) {
        if ($uq = $conn->prepare("SELECT DISTINCT up.entity_id FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='lcs' AND uc.contributor_name = ? AND up.status_date BETWEEN ? AND ?")) {
            $uq->bind_param('sss', $sel_user, $from_date, $to_date);
            $uq->execute();
            $urs = $uq->get_result();
            while ($row = $urs->fetch_assoc()) { $id=(int)$row['entity_id']; if (!in_array($id,$lcs_ids,true)) { $lcs_ids[]=$id; } }
        }
    }

    // Highlights are precomputed globally above in $lcs_hl and filtered via $allowed_dates

    foreach ($lcs_ids as $lid) {
        // Prefetch all rows up to end date for this LCS
        $q = "SELECT h.*, s.site_name, l.lcs_name
              FROM lcs_status_history h
              JOIN sites s ON h.site_id = s.id
              JOIN lcs l ON h.lcs_id = l.id
              WHERE h.lcs_id = ? AND h.status_date <= ?
              ORDER BY h.status_date ASC, h.item_name ASC";
        $stmt = $conn->prepare($q);
        $stmt->bind_param('is', $lid, $to_date);
        $stmt->execute();
        $rs = $stmt->get_result();
        $rows_seq = [];
        while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

        // Change dates for this LCS in range
        $chg = $conn->prepare("SELECT DISTINCT status_date FROM lcs_status_history WHERE lcs_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
        $chg->bind_param('iss', $lid, $from_date, $to_date);
        $chg->execute();
        $crs = $chg->get_result();
        $change_dates = [];
        while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

        // Snapshot (include dynamic items)
        $last_known = [];
        $dynamic_items = [];
        $p = 0; $n = count($rows_seq);
        $cur = strtotime($from_date);
        $end = strtotime($to_date);
        while ($cur !== false && $cur <= $end) {
            $d = date('Y-m-d', $cur);
            while ($p < $n && $rows_seq[$p]['status_date'] <= $d) {
                $rk = $rows_seq[$p];
                $last_known[$rk['item_name']] = $rk;
                if (!in_array($rk['item_name'], $active_items, true)) { $dynamic_items[$rk['item_name']] = true; }
                $p++;
            }
            $has_item_change = isset($change_dates[$d]) && isset($allowed_dates[$d]) && isset($lcs_hl[$d]) && isset($lcs_hl[$d][$lid]) && !empty($lcs_hl[$d][$lid]);
            // Master change triggers when updated, uploaded media, or contributed
            $is_lcs_mn_contrib = in_array($sel_user, $lcs_mn_contrib_by_date[$d][$lid] ?? [], true);
            $has_master_change = (isset($lcs_master_by_date[$d]) && !empty($lcs_master_by_date[$d][$lid] ?? [])) || (!empty($lcs_master_media_by_date[$d][$lid] ?? [])) || $is_lcs_mn_contrib;
    $has_update_change = isset($lcs_update_hl[$d]) && isset($lcs_update_hl[$d][$lid]);
            if ($has_item_change || $has_master_change || $has_update_change) {
                if (!isset($lcs_rows_by_date[$d])) { $lcs_rows_by_date[$d] = []; }
                if (!isset($lcs_rows_by_date[$d][$lid])) { $lcs_rows_by_date[$d][$lid] = []; }
                
                $all_items = $active_items;
                foreach ($dynamic_items as $din => $_v) { if (!in_array($din, $all_items, true)) { $all_items[] = $din; } }
                sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);
                
                foreach ($all_items as $iname) {
                    if (isset($last_known[$iname])) { 
                        $lcs_rows_by_date[$d][$lid][] = $last_known[$iname]; 
                    } else {
                        // Fallback names when no history row exists
                        $site_name_fallback = '';
                        $lcs_name_fallback = '';
                        if (!empty($rows_seq)) {
                            $site_name_fallback = $rows_seq[0]['site_name'] ?? ($sites[$rows_seq[0]['site_id']] ?? '');
                            $lcs_name_fallback = $rows_seq[0]['lcs_name'] ?? '';
                        } else {
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

// Now that $lcs_rows_by_date is built, populate $lcs_note_on_date using present LCS IDs
if ($from_date !== '' && $to_date !== '') {
    $lids_set = [];
    foreach ($lcs_rows_by_date as $dk => $buckets) {
        if (strpos($dk,'__hl')!==false) continue;
        foreach ($buckets as $lid => $_rows) { $lids_set[$lid] = true; }
    }
    if (!empty($lids_set)) {
        $id_list = implode(',', array_map('intval', array_keys($lids_set)));
        $has_sd_lcs = false; $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'"); if ($col && $col->num_rows > 0) { $has_sd_lcs = true; }
        if ($has_sd_lcs) {
            $q = "SELECT lcs_id, status_date AS d, note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id IN ($id_list) AND status_date BETWEEN ? AND ? ORDER BY lcs_id ASC, status_date ASC";
        } else {
            $q = "SELECT lcs_id, DATE(updated_at) AS d, note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id IN ($id_list) AND DATE(updated_at) BETWEEN ? AND ? ORDER BY lcs_id ASC, updated_at ASC";
        }
        if ($st = $conn->prepare($q)) {
            $st->bind_param('ss', $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($row = $rs->fetch_assoc()) { $lcs_note_on_date[$row['d']][(int)$row['lcs_id']] = $row; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User-wise Report</title>
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
        <h2 style="color:#2d3748; margin-bottom:1rem;">👤 User-wise Report</h2>
        <form method="GET" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:end; margin-bottom:1rem;">
            <div class="form-group" style="min-width:220px; flex:1;">
                <label class="form-label">User</label>
                <select name="user" class="form-control" required>
                    <option value="">-- Select User --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $sel_user===$u?'selected':''; ?>><?php echo htmlspecialchars($u); ?></option>
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
            <div class="form-group" style="min-width:140px;">
                <button type="submit" class="btn btn-primary" style="width:100%">🔎 View</button>
            </div>
        </form>

        <?php if ($sel_user!=='' && $from_date!=='' && $to_date!==''): ?>
            <div style="display:flex; justify-content:flex-end; margin-bottom:10px; gap:10px;">
                <a class="btn btn-secondary" href="generate_user_wise_report.php?user=<?php echo urlencode($sel_user); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>">⬇️ Export PDF</a>
            </div>
            <?php if (!empty($tw_rows_by_date)): ?>
                <h3 class="section-title">🛠️ Tubewell Item Updates</h3>
                <?php foreach ($tw_rows_by_date as $d => $twBuckets): ?>
                    <?php if (strpos($d,'__hl')!==false) { continue; } $hlAll = $tw_rows_by_date[$d.'__hl'] ?? []; ?>
                    <?php foreach ($twBuckets as $tid => $rows): ?>
                        <?php $hl = $hlAll[$tid] ?? []; $first = $rows[0] ?? null; ?>
                        <div class="table-container" style="margin-bottom:1rem;">
                            <h4 style="margin:0 0 .25rem 0; color:#4a5568;">Date: <?php echo htmlspecialchars($d); ?> | Site: <?php echo htmlspecialchars($first['site_name'] ?? ''); ?> | Tubewell: <?php echo htmlspecialchars($first['tubewell_name'] ?? ''); ?> (<?php echo count($rows); ?> items)</h4>
                            <?php /* Removed Tubewell-level item updates summary to avoid confusion with Master Note block */ ?>
                            <?php if (!empty($tw_note_on_date[$d][$tid] ?? [])): $mn = $tw_note_on_date[$d][$tid]; $mnc = $tw_mn_contrib_by_date[$d][$tid] ?? []; if ($mn['updated_by'] === $sel_user || in_array($sel_user, $mnc, true)): ?>
                                <div class="alert alert-info" style="margin:0 0 .5rem 0;">
                                    <strong>Master Note:</strong> <?php echo htmlspecialchars($mn['note'] ?? ''); ?>
                                    <span style="color:#4a5568; font-size:.85rem;">— by <?php echo htmlspecialchars($mn['updated_by'] ?? '—'); ?> at <?php echo isset($mn['updated_at']) && $mn['updated_at'] ? date('d M Y H:i', strtotime($mn['updated_at'])) : '—'; ?></span>
                                    <?php if (!empty($mnc)): ?>
                                        <div style="margin-top:.15rem; font-size:.9em; color:#374151;">
                                            <strong>Contributors:</strong> <?php echo htmlspecialchars(implode(', ', $mnc)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php $mm = $tw_master_media_by_date[$d][$tid] ?? []; if (!empty($mm)): ?>
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
                            <?php endif; endif; ?>
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
                                        $item_media = $tw_media_by_date_item[$d][$r['tubewell_id'].'|'.$r['item_name']] ?? [];
                                        $ppl = $tw_updates_people_by_date_item[$d][$r['tubewell_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
                                        $p1 = $ppl['primaries'] ?? []; $p2 = $ppl['contributors'] ?? [];
                                    ?>
                                    <tr class="<?php echo $isSel ? 'row-highlight' : ''; ?>">
                                        <td style="text-align:center;">&nbsp;<?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($r['site_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['tubewell_name']); ?></td>
                                        <td class="col-item"><strong><?php echo htmlspecialchars($r['item_name']); ?></strong>
                                            <!-- <?php if (!empty($p1) || !empty($p2)): ?>
                                                <div style="font-size:11px; color:#4a5568; margin-top:2px;">
                                                    <?php if (!empty($p1)): ?><span><strong>By:</strong> <?php echo htmlspecialchars(implode(', ', $p1)); ?></span><?php endif; ?>
                                                    <?php if (!empty($p1) && !empty($p2)): ?> <span> | </span> <?php endif; ?>
                                                    <?php if (!empty($p2)): ?><span><strong>With:</strong> <?php echo htmlspecialchars(implode(', ', $p2)); ?></span><?php endif; ?>
                                                </div>
                                            <?php endif; ?> -->
                                        </td>
                                        <td><?php echo htmlspecialchars($r['make_model'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['size_capacity'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                                        <td style="text-align:center;">
                                            <?php
                                            echo ((int)($r['check_hmi_local'] ?? 0)) === 1
                                                ? '✅'
                                                : (((int)($r['check_hmi_local'] ?? 0)) === 2 ? '❌' : '—');
                                            ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <?php
                                            echo ((int)($r['check_web'] ?? 0)) === 1
                                                ? '✅'
                                                : (((int)($r['check_web'] ?? 0)) === 2 ? '❌' : '—');
                                            ?>
                                        </td>
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
                                        <td><?php echo htmlspecialchars($r['created_by'] ?? '—'); ?>
                                    <?php if (!empty($p1) || !empty($p2)): ?>
                                                <div style="font-size:11px; color:#4a5568; margin-top:2px;">
                                                    <?php if (!empty($p1)): ?><span><strong>By:</strong> <?php echo htmlspecialchars(implode(', ', $p1)); ?></span><?php endif; ?>
                                                    <?php if (!empty($p1) && !empty($p2)): ?> <span> | </span> <?php endif; ?>
                                                    <?php if (!empty($p2)): ?><span><strong>With:</strong> <?php echo htmlspecialchars(implode(', ', $p2)); ?></span><?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                    </td>
                                        <td><?php echo isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—'; ?>
                                    </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>


            <?php if (!empty($lcs_rows_by_date)): ?>
                <h3 class="section-title">🧰 LCS Item Updates</h3>
                <?php foreach ($lcs_rows_by_date as $d => $lcsBuckets): ?>
                    <?php if (strpos($d,'__hl')!==false) { continue; } $hlAll = $lcs_rows_by_date[$d.'__hl'] ?? []; ?>
                    <?php foreach ($lcsBuckets as $lid => $rows): ?>
                        <?php $hl = $hlAll[$lid] ?? []; $first = $rows[0] ?? null; ?>
                        <div class="table-container" style="margin-bottom:1rem;">
                            <h4 style="margin:0 0 .25rem 0; color:#4a5568;">Date: <?php echo htmlspecialchars($d); ?> | Site: <?php echo htmlspecialchars($first['site_name'] ?? ''); ?> | LCS: <?php echo htmlspecialchars($first['lcs_name'] ?? ''); ?> (<?php echo count($rows); ?> items)</h4>
                            <?php if (!empty($lcs_note_on_date[$d][$lid] ?? [])): $mn = $lcs_note_on_date[$d][$lid]; $lmnc = $lcs_mn_contrib_by_date[$d][$lid] ?? []; ?>
                            <div class="alert alert-info" style="margin:0 0 .5rem 0;">
                                <strong>Master Note:</strong> <?php echo htmlspecialchars($mn['note'] ?? ''); ?>
                                <span style="color:#4a5568; font-size:.85rem;">— by <?php echo htmlspecialchars($mn['updated_by'] ?? '—'); ?> at <?php echo isset($mn['updated_at']) && $mn['updated_at'] ? date('d M Y H:i', strtotime($mn['updated_at'])) : '—'; ?></span>
                                <?php if (!empty($lmnc)): ?>
                                    <div style="margin-top:.15rem; font-size:.9em; color:#374151;">
                                        <strong>Contributors:</strong> <?php echo htmlspecialchars(implode(', ', $lmnc)); ?>
                                    </div>
                                <?php endif; ?>
                                <?php $mm = $lcs_master_media_by_date[$d][$lid] ?? []; if (!empty($mm)): ?>
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
                                        $item_media = $lcs_media_by_date_item[$d][$r['lcs_id'].'|'.$r['item_name']] ?? [];
                                    ?>
                                    <tr class="<?php echo $isSel ? 'row-highlight' : ''; ?>">
                                        <td style="text-align:center;">&nbsp;<?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($r['site_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['lcs_name']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($r['item_name']); ?></strong>
                                            <?php $lppl = $lcs_updates_people_by_date_item[$d][$r['lcs_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
                                            $lp1 = $lppl['primaries'] ?? []; $lp2 = $lppl['contributors'] ?? [];
                                            if (!empty($lp1) || !empty($lp2)): ?>
                                                <div style="font-size:11px; color:#4a5568; margin-top:2px;">
                                                    <?php if (!empty($lp1)): ?><span><strong>By:</strong> <?php echo htmlspecialchars(implode(', ', $lp1)); ?></span><?php endif; ?>
                                                    <?php if (!empty($lp1) && !empty($lp2)): ?> <span> | </span> <?php endif; ?>
                                                    <?php if (!empty($lp2)): ?><span><strong>With:</strong> <?php echo htmlspecialchars(implode(', ', $lp2)); ?></span><?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
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
                                        <td><?php echo htmlspecialchars($r['created_by'] ?? '—'); ?>
                                            <?php $lppl = $lcs_updates_people_by_date_item[$d][$r['lcs_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
                                            $lp1 = $lppl['primaries'] ?? []; $lp2 = $lppl['contributors'] ?? [];
                                            if (!empty($lp1) || !empty($lp2)): ?>
                                                <div style="font-size:11px; color:#4a5568; margin-top:2px;">
                                                    <?php if (!empty($lp1)): ?><span><strong>By:</strong> <?php echo htmlspecialchars(implode(', ', $lp1)); ?></span><?php endif; ?>
                                                    <?php if (!empty($lp1) && !empty($lp2)): ?> <span> | </span> <?php endif; ?>
                                                    <?php if (!empty($lp2)): ?><span><strong>With:</strong> <?php echo htmlspecialchars(implode(', ', $lp2)); ?></span><?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">Select User and From/To dates to view the report.</div>
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