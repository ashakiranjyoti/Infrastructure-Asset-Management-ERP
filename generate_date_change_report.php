<?php
include 'db_config.php';
require_once('TCPDF-main/tcpdf.php');

$on_date = isset($_GET['on_date']) ? $_GET['on_date'] : '';
if ($on_date === '') { die('Invalid request. on_date is required.'); }
// (contributor preloads moved below after IDs are computed)

// Common lists
$sites = [];
if ($sres = $conn->query("SELECT id, site_name FROM sites ")) { while ($r = $sres->fetch_assoc()) { $sites[$r['id']] = $r['site_name']; } }

// Highlight maps (all users on that date)
$tw_hl = [];
$lcs_hl = [];
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

// Prefetch master notes - FIRST get the IDs, THEN fetch notes
$tw_notes_on_date = [];
$lcs_notes_on_date = [];
// Track master media presence per entity for inclusion
$tw_master_media_ids = [];
$lcs_master_media_ids = [];

// Get TW IDs first (include master notes/media only changes)
$tw_ids = [];
if ($q = $conn->prepare("SELECT DISTINCT tubewell_id FROM status_history WHERE status_date = ?")) { 
    $q->bind_param('s', $on_date); 
    $q->execute(); 
    $r = $q->get_result(); 
    while($x = $r->fetch_assoc()) { $tw_ids[] = (int)$x['tubewell_id']; } 
}
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
    while ($x = $r3->fetch_assoc()) { $id = (int)$x['tubewell_id']; $tw_master_media_ids[$id] = true; if (!in_array($id, $tw_ids, true)) { $tw_ids[] = $id; } }
}

// Get LCS IDs first (include master notes/media only changes)
$lcs_ids = [];
if ($q = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_status_history WHERE status_date = ?")) { 
    $q->bind_param('s', $on_date); 
    $q->execute(); 
    $r = $q->get_result(); 
    while($x = $r->fetch_assoc()) { $lcs_ids[] = (int)$x['lcs_id']; } 
}
// From master notes
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
// From master media
if ($q3 = $conn->prepare("SELECT DISTINCT lcs_id FROM lcs_master_media WHERE status_date = ?")) {
    $q3->bind_param('s', $on_date);
    $q3->execute();
    $r3 = $q3->get_result();
    while ($x = $r3->fetch_assoc()) { $id = (int)$x['lcs_id']; $lcs_master_media_ids[$id] = true; if (!in_array($id, $lcs_ids, true)) { $lcs_ids[] = $id; } }
}

// Tubewell master notes
if (!empty($tw_ids)) {
    $id_list = implode(',', array_map('intval', $tw_ids));
    $qn = "SELECT tubewell_id, note, updated_by, updated_at FROM tubewell_master_notes WHERE status_date = ? AND tubewell_id IN ($id_list)";
    if ($ns = $conn->prepare($qn)) {
        $ns->bind_param('s', $on_date);
        $ns->execute();
        $nrs = $ns->get_result();
        while ($row = $nrs->fetch_assoc()) { 
            $tw_notes_on_date[(int)$row['tubewell_id']] = $row; 
        }
    }
}

// LCS master notes
if (!empty($lcs_ids)) {
    $id_list = implode(',', array_map('intval', $lcs_ids));
    $has_sd = false;
    $col = $conn->query("SHOW COLUMNS FROM lcs_master_notes LIKE 'status_date'");
    if ($col && $col->num_rows > 0) { $has_sd = true; }
    if ($has_sd) {
        $qn = "SELECT lcs_id, note, updated_by, updated_at FROM lcs_master_notes WHERE status_date = ? AND lcs_id IN ($id_list)";
        if ($ns = $conn->prepare($qn)) { 
            $ns->bind_param('s', $on_date); 
            $ns->execute(); 
            $nrs = $ns->get_result(); 
            while ($row = $nrs->fetch_assoc()) { 
                $lcs_notes_on_date[(int)$row['lcs_id']] = $row; 
            } 
        }
    } else {
        $qn = "SELECT lcs_id, note, updated_by, updated_at FROM lcs_master_notes WHERE DATE(updated_at) = ? AND lcs_id IN ($id_list)";
        if ($ns = $conn->prepare($qn)) { 
            $ns->bind_param('s', $on_date); 
            $ns->execute(); 
            $nrs = $ns->get_result(); 
            while ($row = $nrs->fetch_assoc()) { 
                $lcs_notes_on_date[(int)$row['lcs_id']] = $row; 
            } 
        }
    }
}

// Preload contributor maps now that IDs are known
// TW item-wise primaries and contributors for selected date
if (!empty($tw_ids)) {
    $id_list = implode(',', array_map('intval', $tw_ids));
    $tw_people_on_date = [];
    if ($up = $conn->prepare("SELECT entity_id AS tubewell_id, item_name, updated_by FROM updates WHERE entity_type='tubewell' AND status_date = ? AND entity_id IN ($id_list)")) {
        $up->bind_param('s', $on_date);
        $up->execute();
        $upr = $up->get_result();
        while ($row = $upr->fetch_assoc()) {
            $tid = (int)$row['tubewell_id']; $iname = $row['item_name']; $nm = $row['updated_by'];
            if (!isset($tw_people_on_date[$tid])) { $tw_people_on_date[$tid] = []; }
            if (!isset($tw_people_on_date[$tid][$iname])) { $tw_people_on_date[$tid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
            if (!empty($nm) && !in_array($nm, $tw_people_on_date[$tid][$iname]['primaries'], true)) { $tw_people_on_date[$tid][$iname]['primaries'][] = $nm; }
        }
    }
    $tblExists = $conn->query("SHOW TABLES LIKE 'update_contributors'");
    if ($tblExists && $tblExists->num_rows > 0) {
        if ($uc = $conn->prepare("SELECT up.entity_id AS tubewell_id, up.item_name, uc.contributor_name FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='tubewell' AND up.status_date = ? AND up.entity_id IN ($id_list)")) {
            $uc->bind_param('s', $on_date);
            $uc->execute();
            $ucr = $uc->get_result();
            while ($row = $ucr->fetch_assoc()) {
                $tid = (int)$row['tubewell_id']; $iname = $row['item_name']; $nm = $row['contributor_name'];
                if (!isset($tw_people_on_date[$tid])) { $tw_people_on_date[$tid] = []; }
                if (!isset($tw_people_on_date[$tid][$iname])) { $tw_people_on_date[$tid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                if (!empty($nm) && !in_array($nm, $tw_people_on_date[$tid][$iname]['contributors'], true)) { $tw_people_on_date[$tid][$iname]['contributors'][] = $nm; }
            }
        }
    }
    // TW master note contributors
    $tw_master_note_contrib_on_date = [];
    $colC = $conn->query("SHOW TABLES LIKE 'tubewell_master_note_contributors'");
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

// LCS item-wise primaries and contributors for selected date, and master note contributors
if (!empty($lcs_ids)) {
    $id_list = implode(',', array_map('intval', $lcs_ids));
    $lcs_people_on_date = [];
    if ($up = $conn->prepare("SELECT entity_id AS lcs_id, item_name, updated_by FROM updates WHERE entity_type='lcs' AND status_date = ? AND entity_id IN ($id_list)")) {
        $up->bind_param('s', $on_date);
        $up->execute();
        $upr = $up->get_result();
        while ($row = $upr->fetch_assoc()) {
            $lid = (int)$row['lcs_id']; $iname = $row['item_name']; $nm = $row['updated_by'];
            if (!isset($lcs_people_on_date[$lid])) { $lcs_people_on_date[$lid] = []; }
            if (!isset($lcs_people_on_date[$lid][$iname])) { $lcs_people_on_date[$lid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
            if (!empty($nm) && !in_array($nm, $lcs_people_on_date[$lid][$iname]['primaries'], true)) { $lcs_people_on_date[$lid][$iname]['primaries'][] = $nm; }
        }
    }
    $tblExists2 = $conn->query("SHOW TABLES LIKE 'update_contributors'");
    if ($tblExists2 && $tblExists2->num_rows > 0) {
        if ($uc = $conn->prepare("SELECT up.entity_id AS lcs_id, up.item_name, uc.contributor_name FROM update_contributors uc JOIN updates up ON up.id = uc.update_id WHERE up.entity_type='lcs' AND up.status_date = ? AND up.entity_id IN ($id_list)")) {
            $uc->bind_param('s', $on_date);
            $uc->execute();
            $ucr = $uc->get_result();
            while ($row = $ucr->fetch_assoc()) {
                $lid = (int)$row['lcs_id']; $iname = $row['item_name']; $nm = $row['contributor_name'];
                if (!isset($lcs_people_on_date[$lid])) { $lcs_people_on_date[$lid] = []; }
                if (!isset($lcs_people_on_date[$lid][$iname])) { $lcs_people_on_date[$lid][$iname] = ['primaries'=>[], 'contributors'=>[]]; }
                if (!empty($nm) && !in_array($nm, $lcs_people_on_date[$lid][$iname]['contributors'], true)) { $lcs_people_on_date[$lid][$iname]['contributors'][] = $nm; }
            }
        }
    }
    // LCS master note contributors
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

$tw_rows = [];
$lcs_rows = [];

// TW snapshot for entities changed on on_date
$active_tw_items = [];
if ($ires = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ")) { while ($ir = $ires->fetch_assoc()) { $active_tw_items[] = $ir['item_name']; } }

foreach ($tw_ids as $tid) {
  // confirm change; allow inclusion if master note or media changed
  $has_item_change = !empty($tw_hl[$on_date][$tid] ?? []);
  // master change flags (notes loaded later; media tracked in $tw_master_media_ids)
  $has_master_change = isset($tw_notes_on_date[$tid]) || isset($tw_master_media_ids[$tid]);
  if (!$has_item_change && !$has_master_change) { continue; }
  $stmt = $conn->prepare("SELECT sh.*, s.site_name, tw.tubewell_name FROM status_history sh JOIN sites s ON sh.site_id = s.id JOIN tubewells tw ON sh.tubewell_id = tw.id WHERE sh.tubewell_id = ? AND sh.status_date <= ? ORDER BY sh.status_date ASC, sh.item_name ASC");
  $stmt->bind_param('is', $tid, $on_date);
  $stmt->execute();
  $rs = $stmt->get_result();
  $rows_seq = [];
  while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }
  // when no rows in history, still include using fallbacks
  $site_name_fallback = '';
  $tubewell_name_fallback = '';
  if (!$rows_seq) {
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
  $last_known = [];
  foreach ($rows_seq as $rk) { if ($rk['status_date'] <= $on_date) { $last_known[$rk['item_name']] = $rk; } }
  $bucket = [];
  foreach ($active_tw_items as $iname) {
    if (isset($last_known[$iname])) { $bucket[] = $last_known[$iname]; }
    else { 
        $bucket[] = [ 
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
  $tw_rows[$tid] = [ 
      'meta' => [ 
          'site_name' => $site_name_fallback, 
          'tubewell_name' => $tubewell_name_fallback 
      ], 
      'rows' => $bucket, 
      'hl' => array_reduce(array_keys($tw_hl[$on_date][$tid] ?? []), function($acc,$k) use ($tid){ $acc[$tid.'|'.$k]=true; return $acc; }, []),
      'note' => $tw_notes_on_date[$tid] ?? null
  ];
}

// LCS snapshot for entities changed on on_date
$active_lcs_items = [];
if ($ires = $conn->query("SELECT item_name FROM lcs_item WHERE is_active = 1 ")) { while ($ir = $ires->fetch_assoc()) { $active_lcs_items[] = $ir['item_name']; } }

foreach ($lcs_ids as $lid) {
  $has_item_change = !empty($lcs_hl[$on_date][$lid] ?? []);
  $has_master_change = isset($lcs_notes_on_date[$lid]) || isset($lcs_master_media_ids[$lid]);
  if (!$has_item_change && !$has_master_change) { continue; }
  $stmt = $conn->prepare("SELECT h.*, s.site_name, l.lcs_name FROM lcs_status_history h JOIN sites s ON h.site_id = s.id JOIN lcs l ON h.lcs_id = l.id WHERE h.lcs_id = ? AND h.status_date <= ? ORDER BY h.status_date ASC, h.item_name ASC");
  $stmt->bind_param('is', $lid, $on_date);
  $stmt->execute();
  $rs = $stmt->get_result();
  $rows_seq = [];
  while ($r = $rs->fetch_assoc()) { $rows_seq[] = $r; }
  // Build fallback names when there is no history on/before this date
  $site_name_fallback = '';
  $lcs_name_fallback = '';
  if (!$rows_seq) {
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
          if (!in_array($rk['item_name'], $active_lcs_items, true)) { 
              $dynamic_items[$rk['item_name']] = true; 
          } 
      } 
  }
  $all_items = $active_lcs_items; 
  foreach($dynamic_items as $din => $_v) { 
      if (!in_array($din, $all_items, true)) { 
          $all_items[] = $din; 
      } 
  }
  sort($all_items, SORT_NATURAL | SORT_FLAG_CASE);
  $bucket = [];
  foreach ($all_items as $iname) {
    if (isset($last_known[$iname])) { $bucket[] = $last_known[$iname]; }
    else { 
        $bucket[] = [ 
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
  $lcs_rows[$lid] = [ 
      'meta' => [ 
          'site_name' => $site_name_fallback, 
          'lcs_name' => $lcs_name_fallback 
      ], 
      'rows' => $bucket, 
      'hl' => array_reduce(array_keys($lcs_hl[$on_date][$lid] ?? []), function($acc,$k) use ($lid){ $acc[$lid.'|'.$k]=true; return $acc; }, []),
      'note' => $lcs_notes_on_date[$lid] ?? null
  ];
}

if (empty($tw_rows) && empty($lcs_rows)) { die('No records found for the selected date.'); }

$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Site Asset Management System');
$pdf->SetAuthor('Site Asset Management System');
$pdf->SetTitle('Date-wise Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$pdf->SetFont('helvetica','B',16);
$pdf->Cell(0,8,'DATE-WISE REPORT',0,1,'C');
$pdf->Ln(4);
$pdf->SetFont('helvetica','',10);
$pdf->Cell(0,6,'Date: '.date('d M Y',strtotime($on_date)),0,1,'L');
$pdf->Ln(4);

// Function to get HMI/Web status symbols
function getCheckStatus($value) {
    $val = (int)$value;
    if ($val === 1) return 'yes';
    if ($val === 2) return 'no';
    return '—';
}

// Updated headers - Photo/Video removed, Remark width increased
$tw_header = ['Sr.No','Site','Tubewell','Item','Make/Model','Size/Cap.','Status','HMI/Loc.','Web','Remark','Updated By','Updated At'];
$tw_w = [8, 20, 25, 28, 20, 15, 17, 10, 10, 80, 20, 23];
$lcs_header = ['Sr.No','Site','LCS','Item','Make/Model','Size/Cap.','Status','Remark','Updated By','Updated At'];
$lcs_w = [8, 20, 25, 28, 20, 15, 20, 85, 20, 23];

// Function to add master note with proper formatting
function addMasterNote($pdf, $note, $entity_type) {
    if ($note && trim((string)($note['note'] ?? '')) !== '') {
        $pdf->SetFont('helvetica','B',9);
        $pdf->Cell(0,5,$entity_type.' Master Note:',0,1,'L');
        
        $pdf->SetFont('helvetica','',8);
        $note_text = $note['note'];
        
        // Split long note into multiple lines
        $max_width = 250; // Approximate width for note text
        $words = explode(' ', $note_text);
        $line = '';
        $lines = [];
        
        foreach ($words as $word) {
            $test_line = $line . ' ' . $word;
            // Estimate width - adjust based on your font size
            if (strlen($test_line) * 1.5 > $max_width) {
                $lines[] = trim($line);
                $line = $word;
            } else {
                $line = $test_line;
            }
        }
        $lines[] = trim($line);
        
        foreach ($lines as $line) {
            $pdf->Cell(0,4,$line,0,1,'L');
        }
        
        $pdf->SetFont('helvetica','I',7);
        $pdf->Cell(0,4,'— by '.($note['updated_by'] ?? '').' at '.(isset($note['updated_at']) && $note['updated_at'] ? date('d M Y H:i', strtotime($note['updated_at'])) : '—'),0,1,'L');
        $pdf->Ln(2);
    }
}

// Tubewell sections with master notes
foreach ($tw_rows as $tid => $bucket) {
  $meta = $bucket['meta']; 
  $rows = $bucket['rows']; 
  $hl = $bucket['hl'];
  $note = $bucket['note'];
  
  $pdf->SetFont('helvetica','B',12);
  $pdf->Cell(0,7,'Site: '.($meta['site_name'] ?? '').' | Tubewell: '.($meta['tubewell_name'] ?? '').' ('.count($rows).' items)',0,1,'L');
  
  // Display master note if exists
  addMasterNote($pdf, $note, 'Tubewell');
  // Contributors line (if any)
  $tmnc = $tw_master_note_contrib_on_date[$tid] ?? [];
  if (!empty($tmnc)) { $pdf->SetFont('helvetica','',7); $pdf->Cell(0,4,'Contributors: '.implode(', ', $tmnc),0,1,'L'); $pdf->Ln(1); }
  
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
  
  foreach ($rows as $r) {
    if ($pdf->GetY() > 180) { 
        $pdf->AddPage(); 
        // Re-add header on new page
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
    
    // Remark with multi-line support
    $remark = $r['remark'] ?? 'N/A';
    $pdf->Cell($tw_w[9],5,$remark,1,0,'L',true);
    
    // Updated By with By/With
    $byWith = $r['created_by'] ?? 'N/A';
    $ppl = $tw_people_on_date[$r['tubewell_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
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

// LCS sections with master notes
foreach ($lcs_rows as $lid => $bucket) {
  $meta = $bucket['meta']; 
  $rows = $bucket['rows']; 
  $hl = $bucket['hl'];
  $note = $bucket['note'];
  
  $pdf->SetFont('helvetica','B',12);
  $pdf->Cell(0,7,'Site: '.($meta['site_name'] ?? '').' | LCS: '.($meta['lcs_name'] ?? '').' ('.count($rows).' items)',0,1,'L');
  
  // Display master note if exists
  addMasterNote($pdf, $note, 'LCS');
  // Contributors line (if any)
  $lmnc = $lcs_master_note_contrib_on_date[$lid] ?? [];
  if (!empty($lmnc)) { $pdf->SetFont('helvetica','',7); $pdf->Cell(0,4,'Contributors: '.implode(', ', $lmnc),0,1,'L'); $pdf->Ln(1); }
  
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
  
  foreach ($rows as $r) {
    if ($pdf->GetY() > 180) { 
        $pdf->AddPage(); 
        // Re-add header on new page
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
    
    // Remark with more space for LCS
    $remark = $r['remark'] ?? 'N/A';
    $pdf->Cell($lcs_w[7],5,$remark,1,0,'L',true);
    
    // Updated By with By/With
    $byWith = $r['created_by'] ?? 'N/A';
    $ppl = $lcs_people_on_date[$r['lcs_id']][$r['item_name']] ?? ['primaries'=>[], 'contributors'=>[]];
    $p1 = $ppl['primaries'] ?? []; $p2 = $ppl['contributors'] ?? [];
    if (!empty($p1) || !empty($p2)) {
        $parts = [];
        if (!empty($p1)) { $parts[] = 'By: '.implode(', ', $p1); }
        if (!empty($p2)) { $parts[] = 'With: '.implode(', ', $p2); }
        $byWith = $byWith.' ['.implode(' | ', $parts).']';
    }
    $pdf->Cell($lcs_w[8],5,$byWith,1,0,'L',true);
    $pdf->Cell($lcs_w[9],5,(isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i',strtotime($r['updated_at'])) : '—'),1,0,'C',true);
    $pdf->Ln();
    $fill = !$fill;
  }
  $pdf->Ln(3);
}

$pdf->SetY(-15);
$pdf->SetFont('helvetica','I',8);
$pdf->Cell(0,10,'Generated: '.date('d M Y H:i:s').' | Date: '.date('d M Y',strtotime($on_date)), 'T', 0, 'C');

$pdf->Output('date_change_report_'.$on_date.'.pdf','D');