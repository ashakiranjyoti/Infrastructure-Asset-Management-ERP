<?php
include 'db_config.php';
require_once('TCPDF-main/tcpdf.php');

// Parameters: site_id, lcs_id, from_date, to_date
if (!isset($_GET['site_id']) || !isset($_GET['lcs_id']) || !isset($_GET['from_date']) || !isset($_GET['to_date'])) {
    die("Invalid request. site_id, lcs_id, from_date and to_date parameters are required.");
}

$site_id = (int)$_GET['site_id'];
$lcs_id = (int)$_GET['lcs_id'];
$from_date = $_GET['from_date'];
$to_date = $_GET['to_date'];

// Preload item-wise updates people (primaries and contributors) for By/With display in PDF
$updates_people_by_date_item = [];
$has_updates_tbl = false; $tchk = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchk && $tchk->num_rows > 0) { $has_updates_tbl = true; }
$has_uc_tbl = false; $tchk2 = $conn->query("SHOW TABLES LIKE 'update_contributors'"); if ($has_updates_tbl && $tchk2 && $tchk2->num_rows > 0) { $has_uc_tbl = true; }
if ($has_updates_tbl) {
    if ($st = $conn->prepare("SELECT status_date, item_name, updated_by FROM updates WHERE entity_type='lcs' AND entity_id = ? AND status_date BETWEEN ? AND ?")) {
        $st->bind_param('iss', $lcs_id, $from_date, $to_date);
        $st->execute();
        $rs = $st->get_result();
        while ($r = $rs->fetch_assoc()) {
            $d = $r['status_date']; $iname = $r['item_name']; $name = $r['updated_by'];
            if (!isset($updates_people_by_date_item[$d])) { $updates_people_by_date_item[$d] = []; }
            if (!isset($updates_people_by_date_item[$d][$iname])) { $updates_people_by_date_item[$d][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
            if ($name !== null && $name !== '' && !in_array($name, $updates_people_by_date_item[$d][$iname]['primaries'], true)) { $updates_people_by_date_item[$d][$iname]['primaries'][] = $name; }
        }
    }
    if ($has_uc_tbl) {
        if ($st = $conn->prepare("SELECT up.status_date, up.item_name, uc.contributor_name FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='lcs' AND up.entity_id = ? AND up.status_date BETWEEN ? AND ?")) {
            $st->bind_param('iss', $lcs_id, $from_date, $to_date);
            $st->execute();
            $rs = $st->get_result();
            while ($r = $rs->fetch_assoc()) {
                $d = $r['status_date']; $iname = $r['item_name']; $name = $r['contributor_name'];
                if (!isset($updates_people_by_date_item[$d])) { $updates_people_by_date_item[$d] = []; }
                if (!isset($updates_people_by_date_item[$d][$iname])) { $updates_people_by_date_item[$d][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                if ($name !== null && $name !== '' && !in_array($name, $updates_people_by_date_item[$d][$iname]['contributors'], true)) { $updates_people_by_date_item[$d][$iname]['contributors'][] = $name; }
            }
        }
    }
}

// Load site and lcs info
$qinfo = "SELECT s.site_name, l.lcs_name FROM sites s JOIN lcs l ON l.site_id = s.id WHERE s.id = ? AND l.id = ?";
$stmt = $conn->prepare($qinfo);
$stmt->bind_param('ii', $site_id, $lcs_id);
$stmt->execute();
$info_res = $stmt->get_result();
$info = $info_res->fetch_assoc();

// Load LCS master note (single note per LCS)
$mn_row = null;
if ($mn = $conn->prepare("SELECT note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ?")) {
    $mn->bind_param('i', $lcs_id);
    $mn->execute();
    $mn_row = $mn->get_result()->fetch_assoc();
}

// Load date-wise LCS master notes if schema supports status_date
$lcs_master_notes_by_date = [];
$col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
if ($col && $col->num_rows > 0) {
    if ($mn2 = $conn->prepare("SELECT status_date, note, updated_by, updated_at FROM lcs_master_notes WHERE lcs_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC")) {
        $mn2->bind_param('iss', $lcs_id, $from_date, $to_date);
        $mn2->execute();
        $rs2 = $mn2->get_result();
        while ($row = $rs2->fetch_assoc()) { $lcs_master_notes_by_date[$row['status_date']] = $row; }
    }
}

// Preload LCS master note contributors by date
$mn_contrib_by_date = [];
$chk_mnc = $conn->query("SHOW TABLES LIKE 'lcs_master_note_contributors'");
if ($chk_mnc && $chk_mnc->num_rows > 0) {
    if ($stc = $conn->prepare("SELECT status_date, contributor_name FROM lcs_master_note_contributors WHERE lcs_id = ? AND status_date BETWEEN ? AND ?")) {
        $stc->bind_param('iss', $lcs_id, $from_date, $to_date);
        $stc->execute();
        $rsc = $stc->get_result();
        while ($row = $rsc->fetch_assoc()) {
            $d = $row['status_date']; $nm = $row['contributor_name'];
            if (!isset($mn_contrib_by_date[$d])) { $mn_contrib_by_date[$d] = []; }
            if ($nm !== null && $nm !== '' && !in_array($nm, $mn_contrib_by_date[$d], true)) { $mn_contrib_by_date[$d][] = $nm; }
        }
    }
}

// Build snapshots per change date (match lcs_site_report.php behavior)
// Load active LCS items
$active_items = [];
$ires = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ");
if ($ires) { while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } }

// Prefetch all rows up to end date
$qall = "SELECT h.* FROM lcs_status_history h WHERE h.site_id = ? AND h.lcs_id = ? AND h.status_date <= ? ORDER BY h.status_date ASC, h.item_name ASC";
$stmt = $conn->prepare($qall);
$stmt->bind_param('iis', $site_id, $lcs_id, $to_date);
$stmt->execute();
$rs = $stmt->get_result();
$rows_seq = [];
while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

// Change dates set within range (including master note/media-only changes)
$chg = $conn->prepare("SELECT DISTINCT status_date FROM lcs_status_history WHERE site_id = ? AND lcs_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
$chg->bind_param('iiss', $site_id, $lcs_id, $from_date, $to_date);
$chg->execute();
$crs = $chg->get_result();
$change_dates = [];
while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

// Union: dates from LCS master notes (status_date if available else DATE(updated_at))
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

// Iterate dates, build last-known snapshot, collect only change dates
$rows_by_date = [];
$last_known = [];
$dynamic_items = []; // non-active items that appear in history
$p = 0; $n = count($rows_seq);
$cur = strtotime($from_date);
$end = strtotime($to_date);
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
    if (isset($change_dates[$d])) {
        $rows_by_date[$d] = [];
        // Merge active items with dynamic/custom items encountered so far
        $all_items = $active_items;
        foreach ($dynamic_items as $din => $_v) { if (!in_array($din, $all_items, true)) { $all_items[] = $din; } }
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
                ];
            }
        }
    }
    $cur = strtotime('+1 day', $cur);
}

if (empty($rows_by_date)) {
    die("No records found for the selected filters.");
}

// Create PDF - LANDSCAPE MODE FOR BETTER HORIZONTAL DISPLAY
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Site Asset Management System');
$pdf->SetAuthor('Site Asset Management System');
$pdf->SetTitle('LCS Site Report - ' . ($info['site_name'] ?? 'N/A'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Header
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 8, 'LCS SITE REPORT', 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('helvetica', '', 10);
$siteInfo = "Site: " . ($info['site_name'] ?? 'N/A') . " | " .
            "LCS: " . ($info['lcs_name'] ?? 'N/A') . " | " .
            "From: " . date('d M Y', strtotime($from_date)) . "  To: " . date('d M Y', strtotime($to_date));
$pdf->Cell(0, 6, $siteInfo, 0, 1, 'L');
$pdf->Ln(4);

// No generic master note at the top; show per-date notes only

// Table header style - ADJUSTED WIDTHS FOR LANDSCAPE
$header = ['Sr.No','Item Name','Make/Model','Size/Cap.','Status','Remark','Added By','Updated At'];
$w = [12, 40, 45, 20, 25, 60, 35, 38]; // Adjusted widths for landscape

foreach ($rows_by_date as $d => $rows) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Date: ' . date('d M Y', strtotime($d)) . ' (' . count($rows) . ' items)', 0, 1, 'L');
    $pdf->Ln(2);

    // Date-wise LCS Master Note (show only if available for this date)
    $note_row = isset($lcs_master_notes_by_date[$d]) ? $lcs_master_notes_by_date[$d] : null;
    if (!empty($note_row) && isset($note_row['note']) && trim((string)$note_row['note']) !== '') {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'LCS Master Note', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(0, 0, $note_row['note'], 0, 'L');
        $pdf->Ln(1);
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->Cell(0, 5, 'Updated by ' . ($note_row['updated_by'] ?? '—') . (isset($note_row['updated_at']) ? (' on ' . date('d M Y H:i', strtotime($note_row['updated_at']))) : ''), 0, 1, 'L');
        // Contributors line (if any)
        $tmnc = $mn_contrib_by_date[$d] ?? [];
        if (!empty($tmnc)) {
            $pdf->SetFont('helvetica','',7);
            $pdf->Cell(0, 5, 'Contributors: ' . implode(', ', $tmnc), 0, 1, 'L');
        }
        $pdf->Ln(2);
    }

    // Header row
    $pdf->SetFillColor(70,130,180);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('helvetica', 'B', 7); // Smaller font for header
    for ($i=0;$i<count($header);$i++) {
        $pdf->Cell($w[$i], 6, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Build map of changed items per date for highlighting
    $changed_items_by_date = [];
    if ($ci = $conn->prepare('SELECT status_date, item_name FROM lcs_status_history WHERE site_id = ? AND lcs_id = ? AND status_date BETWEEN ? AND ?')) {
        $ci->bind_param('iiss', $site_id, $lcs_id, $from_date, $to_date);
        $ci->execute();
        $cir = $ci->get_result();
        while ($row = $cir->fetch_assoc()) {
            $dkey = $row['status_date'];
            $it = $row['item_name'];
            if (!isset($changed_items_by_date[$dkey])) { $changed_items_by_date[$dkey] = []; }
            $changed_items_by_date[$dkey][$it] = true;
        }
    }

    $pdf->SetFont('helvetica', '', 7); // Smaller font for content
    $pdf->SetTextColor(0,0,0);
    $fill = false;
    $c = 1;
    foreach ($rows as $r) {
        // Auto page break check
        if ($pdf->GetY() > 180) {
            $pdf->AddPage();
            // Repeat header on new page
            $pdf->SetFillColor(70,130,180);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFont('helvetica', 'B', 7);
            for ($i=0;$i<count($header);$i++) {
                $pdf->Cell($w[$i], 6, $header[$i], 1, 0, 'C', true);
            }
            $pdf->Ln();
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(0,0,0);
        }
        
        // Determine highlight color: changed rows get light yellow; otherwise zebra
        $isChanged = isset($changed_items_by_date[$d]) && isset($changed_items_by_date[$d][$r['item_name']]);
        if ($isChanged) {
            $pdf->SetFillColor(255, 247, 230);
        } else {
            $pdf->SetFillColor($fill?240:255, $fill?248:255, $fill?255:255);
        }
        $pdf->Cell($w[0], 5, $c++, 1, 0, 'C', true);
        $pdf->Cell($w[1], 5, $r['item_name'] ?? 'N/A', 1, 0, 'L', true);
        $pdf->Cell($w[2], 5, $r['make_model'] ?? 'N/A', 1, 0, 'L', true);
        $pdf->Cell($w[3], 5, $r['size_capacity'] ?? 'N/A', 1, 0, 'L', true);
        $pdf->Cell($w[4], 5, $r['status'] ?? 'N/A', 1, 0, 'C', true);
        $pdf->Cell($w[5], 5, $r['remark'] ?? 'N/A', 1, 0, 'L', true);
        // Updated By with By/With breakdown
        $byWith = $r['created_by'] ?? 'N/A';
        $ppl = $updates_people_by_date_item[$d][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
        $p1 = $ppl['primaries'] ?? []; $p2 = $ppl['contributors'] ?? [];
        if (!empty($p1) || !empty($p2)) {
            $parts = [];
            if (!empty($p1)) { $parts[] = 'By: ' . implode(', ', $p1); }
            if (!empty($p2)) { $parts[] = 'With: ' . implode(', ', $p2); }
            $byWith = $byWith . ' [' . implode(' | ', $parts) . ']';
        }
        $pdf->Cell($w[6], 5, $byWith, 1, 0, 'L', true);
        $pdf->Cell($w[7], 5, isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—', 1, 0, 'C', true);
        $pdf->Ln();
        $fill = !$fill;
    }

    $pdf->Ln(4);
}

// Footer
$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0,10, 'Generated: ' . date('d M Y H:i:s'), 'T', 0, 'C');

// Output
$pdf->Output('lcs_site_report_' . ($info['site_name'] ?? 'unknown') . '_' . $from_date . '_' . $to_date . '.pdf', 'D');
?>
