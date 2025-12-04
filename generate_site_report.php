<?php
include 'db_config.php';
require_once('TCPDF-main/tcpdf.php');

// Parameters: site_id, tubewell_id, from_date, to_date
if (!isset($_GET['site_id']) || !isset($_GET['tubewell_id']) || !isset($_GET['from_date']) || !isset($_GET['to_date'])) {
    die("Invalid request. site_id, tubewell_id, from_date and to_date parameters are required.");
}

// Preload item-wise updates people (primaries and contributors) for By/With display in PDF
$updates_people_by_date_item = [];
$has_updates_tbl = false; $tchk = $conn->query("SHOW TABLES LIKE 'updates'"); if ($tchk && $tchk->num_rows > 0) { $has_updates_tbl = true; }
$has_uc_tbl = false; $tchk2 = $conn->query("SHOW TABLES LIKE 'update_contributors'"); if ($has_updates_tbl && $tchk2 && $tchk2->num_rows > 0) { $has_uc_tbl = true; }
if ($has_updates_tbl) {
    if ($st = $conn->prepare("SELECT status_date, item_name, updated_by FROM updates WHERE entity_type='tubewell' AND entity_id = ? AND status_date BETWEEN ? AND ?")) {
        $st->bind_param('iss', $tubewell_id, $from_date, $to_date);
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
        if ($st = $conn->prepare("SELECT up.status_date, up.item_name, uc.contributor_name FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='tubewell' AND up.entity_id = ? AND up.status_date BETWEEN ? AND ?")) {
            $st->bind_param('iss', $tubewell_id, $from_date, $to_date);
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

$site_id = (int)$_GET['site_id'];
$tubewell_id = (int)$_GET['tubewell_id'];
$from_date = $_GET['from_date'];
$to_date = $_GET['to_date'];

// Load site and tubewell info
$qinfo = "SELECT s.site_name, tw.zone_name, tw.tubewell_name FROM sites s JOIN tubewells tw ON tw.site_id = s.id WHERE s.id = ? AND tw.id = ?";
$stmt = $conn->prepare($qinfo);
$stmt->bind_param('ii', $site_id, $tubewell_id);
$stmt->execute();
$info_res = $stmt->get_result();
$info = $info_res->fetch_assoc();

// Build snapshots per change date (match site_report.php behavior)
// Load active items
$active_items = [];
$ires = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ");
if ($ires) { while ($ir = $ires->fetch_assoc()) { $active_items[] = $ir['item_name']; } }

// Prefetch all rows up to end date
$qall = "SELECT sh.* FROM status_history sh WHERE sh.site_id = ? AND sh.tubewell_id = ? AND sh.status_date <= ? ORDER BY sh.status_date ASC, sh.item_name ASC";
$stmt = $conn->prepare($qall);
$stmt->bind_param('iis', $site_id, $tubewell_id, $to_date);
$stmt->execute();
$rs = $stmt->get_result();
$rows_seq = [];
while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }

// Change dates set within range (including master note/media-only changes)
$chg = $conn->prepare("SELECT DISTINCT status_date FROM status_history WHERE site_id = ? AND tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
$chg->bind_param('iiss', $site_id, $tubewell_id, $from_date, $to_date);
$chg->execute();
$crs = $chg->get_result();
$change_dates = [];
while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

// Union dates from master notes for this tubewell
if ($mn2 = $conn->prepare("SELECT DISTINCT status_date FROM tubewell_master_notes WHERE tubewell_id = ? AND status_date BETWEEN ? AND ?")) {
    $mn2->bind_param('iss', $tubewell_id, $from_date, $to_date);
    $mn2->execute();
    $mnr2 = $mn2->get_result();
    while ($row = $mnr2->fetch_assoc()) { $change_dates[$row['status_date']] = true; }
}
// Union dates from master media for this tubewell
if ($mm2 = $conn->prepare("SELECT DISTINCT status_date FROM tubewell_master_media WHERE tubewell_id = ? AND status_date BETWEEN ? AND ?")) {
    $mm2->bind_param('iss', $tubewell_id, $from_date, $to_date);
    $mm2->execute();
    $mmr2 = $mm2->get_result();
    while ($row = $mmr2->fetch_assoc()) { $change_dates[$row['status_date']] = true; }
}

// Load master notes for all dates in range
$master_notes_by_date = [];
if ($mn_stmt = $conn->prepare("SELECT status_date, note, updated_by, updated_at FROM tubewell_master_notes WHERE tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC")) {
    $mn_stmt->bind_param('iss', $tubewell_id, $from_date, $to_date);
    $mn_stmt->execute();
    $mn_res = $mn_stmt->get_result();
    while ($mn_row = $mn_res->fetch_assoc()) {
        $master_notes_by_date[$mn_row['status_date']] = $mn_row;
    }
}

// Preload master note contributors for this tubewell and date range
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

// Build map of changed items per date for highlighting in PDF
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

// Iterate dates, build last-known snapshot, collect only change dates
$rows_by_date = [];
$last_known = [];
$p = 0; $n = count($rows_seq);
$cur = strtotime($from_date);
$end = strtotime($to_date);
while ($cur !== false && $cur <= $end) {
    $d = date('Y-m-d', $cur);
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
                ];
            }
        }
        // Append custom items (not in items_master) existing as of this date
        foreach ($last_known as $iname => $row) {
            if (!in_array($iname, $active_items, true)) {
                $rows_by_date[$d][] = $row;
            }
        }
    }
    $cur = strtotime('+1 day', $cur);
}

if (empty($rows_by_date)) {
    die("No records found for the selected filters.");
}

// Function to get HMI/Web status symbols
function getCheckStatus($value) {
    $val = (int)$value;
    if ($val === 1) return 'yes';
    if ($val === 2) return 'no';
    return '—';
}

// Create PDF - LANDSCAPE MODE FOR BETTER HORIZONTAL DISPLAY
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Site Asset Management System');
$pdf->SetAuthor('Site Asset Management System');
$pdf->SetTitle('Site Report - ' . ($info['site_name'] ?? 'N/A'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// Header
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 8, 'SITE REPORT', 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('helvetica', '', 10);
$siteInfo = "Site: " . ($info['site_name'] ?? 'N/A') . " | " .
            "Zone Name: " . ($info['zone_name'] ?? 'N/A') . " | " .
            "Tubewell: " . ($info['tubewell_name'] ?? 'N/A') . " | " .
            "From: " . date('d M Y', strtotime($from_date)) . "  To: " . date('d M Y', strtotime($to_date));
$pdf->Cell(0, 6, $siteInfo, 0, 1, 'L');
$pdf->Ln(4);

// Table header style - ADJUSTED WIDTHS FOR LANDSCAPE
$header = ['Sr.No','Item Name','Make/Model','Size/Cap.','Status','HMI/Loc.','Web','Remark','Updated By','Updated At'];
$w = [12, 30, 28, 17, 17, 15, 15, 100, 20, 23]; // Adjusted widths for landscape

foreach ($rows_by_date as $d => $rows) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Date: ' . date('d M Y', strtotime($d)) . ' (' . count($rows) . ' items)', 0, 1, 'L');
    $pdf->Ln(2);

    // Display master note for this date if exists
    if (isset($master_notes_by_date[$d]) && !empty(trim($master_notes_by_date[$d]['note']))) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 248, 255);
        $pdf->Cell(0, 6, 'Master Note:', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $note_text = $master_notes_by_date[$d]['note'];
        $pdf->MultiCell(0, 5, $note_text, 0, 'L', false);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 4, 'Updated by: ' . ($master_notes_by_date[$d]['updated_by'] ?? '—') . ' on ' . date('d M Y H:i', strtotime($master_notes_by_date[$d]['updated_at'])), 0, 1, 'L');
        // Contributors line
        $tmnc = $mn_contrib_by_date[$d] ?? [];
        if (!empty($tmnc)) {
            $pdf->SetFont('helvetica','',8);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->Cell(0, 4, 'Contributors: ' . implode(', ', $tmnc), 0, 1, 'L');
        }
        $pdf->Ln(2);
        $pdf->SetTextColor(0, 0, 0);
    }

    // Header row
    $pdf->SetFillColor(70,130,180);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('helvetica', 'B', 7); // Smaller font for header
    for ($i=0;$i<count($header);$i++) {
        $pdf->Cell($w[$i], 6, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

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
        
        // HMI/Local with new logic
        $pdf->Cell($w[5], 5, getCheckStatus($r['check_hmi_local'] ?? 0), 1, 0, 'C', true);
        
        // Web with new logic
        $pdf->Cell($w[6], 5, getCheckStatus($r['check_web'] ?? 0), 1, 0, 'C', true);
        
        $pdf->Cell($w[7], 5, $r['remark'] ?? 'N/A', 1, 0, 'L', true);
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
        $pdf->Cell($w[8], 5, $byWith, 1, 0, 'L', true);
        $pdf->Cell($w[9], 5, isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—', 1, 0, 'C', true);
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
$pdf->Output('site_report_' . ($info['site_name'] ?? 'unknown') . '_' . $from_date . '_' . $to_date . '.pdf', 'D');
?>