<?php
include 'db_config.php';
require_once('TCPDF-main/tcpdf.php');

// Check if parameters are set
if (!isset($_GET['tubewell_id']) || !isset($_GET['date'])) {
    die("Invalid request. Tubewell ID and Date parameters are required.");
}

$tubewell_id = $_GET['tubewell_id'];
$report_date = $_GET['date'];

// Get tubewell and site info
$info_sql = "SELECT tw.*, s.* 
             FROM tubewells tw 
             JOIN sites s ON tw.site_id = s.id 
             WHERE tw.id = ?";
$stmt = $conn->prepare($info_sql);
$stmt->bind_param("i", $tubewell_id);
$stmt->execute();
$info_result = $stmt->get_result();
$info = $info_result->fetch_assoc();

// Get status data for the selected date
$status_sql = "SELECT * FROM status_history 
               WHERE tubewell_id = ? AND status_date = ? 
               ORDER BY item_name";
$stmt = $conn->prepare($status_sql);
$stmt->bind_param("is", $tubewell_id, $report_date);
$stmt->execute();
$status_result = $stmt->get_result();

$status_data = [];
while($row = $status_result->fetch_assoc()) {
    $status_data[$row['item_name']] = $row;
}

// Build timestamp maps for updated times
$selected_start = $report_date . ' 00:00:00';
$selected_end = $report_date . ' 23:59:59';

// Detect updated_at column in status_history
$last_updated = [];
$last_updated_on_selected = [];
$has_updated_at_col = false;
$col_check_ts = $conn->query("SHOW COLUMNS FROM status_history LIKE 'updated_at'");
if ($col_check_ts && $col_check_ts->num_rows > 0) {
    $has_updated_at_col = true;
}

if ($has_updated_at_col) {
    // Overall last update up to selected date per item
    $uh_any_sql = "SELECT item_name, MAX(updated_at) as last_time
                   FROM status_history
                   WHERE tubewell_id = ? AND updated_at <= ?
                   GROUP BY item_name";
    $stmt = $conn->prepare($uh_any_sql);
    $stmt->bind_param("is", $tubewell_id, $selected_end);
    $stmt->execute();
    $uh_any_res = $stmt->get_result();
    while ($ur = $uh_any_res->fetch_assoc()) {
        $last_updated[$ur['item_name']] = $ur['last_time'];
    }

    // Last update on selected date per item
    $uh_sel_sql = "SELECT item_name, MAX(updated_at) as last_time
                   FROM status_history
                   WHERE tubewell_id = ? AND updated_at BETWEEN ? AND ?
                   GROUP BY item_name";
    $stmt = $conn->prepare($uh_sel_sql);
    $stmt->bind_param("iss", $tubewell_id, $selected_start, $selected_end);
    $stmt->execute();
    $uh_sel_res = $stmt->get_result();
    while ($us = $uh_sel_res->fetch_assoc()) {
        $last_updated_on_selected[$us['item_name']] = $us['last_time'];
    }
} else {
    // Fallback to change log
    $log_any_sql = "SELECT item_name, MAX(changed_at) as last_time
                    FROM status_change_log
                    WHERE tubewell_id = ? AND changed_at <= ?
                    GROUP BY item_name";
    $stmt = $conn->prepare($log_any_sql);
    $stmt->bind_param("is", $tubewell_id, $selected_end);
    $stmt->execute();
    $log_any_res = $stmt->get_result();
    while ($lr = $log_any_res->fetch_assoc()) {
        $last_updated[$lr['item_name']] = $lr['last_time'];
    }

    $log_sel_sql = "SELECT item_name, MAX(changed_at) as last_time
                    FROM status_change_log
                    WHERE tubewell_id = ? AND changed_at BETWEEN ? AND ?
                    GROUP BY item_name";
    $stmt = $conn->prepare($log_sel_sql);
    $stmt->bind_param("iss", $tubewell_id, $selected_start, $selected_end);
    $stmt->execute();
    $log_sel_res = $stmt->get_result();
    while ($ls = $log_sel_res->fetch_assoc()) {
        $last_updated_on_selected[$ls['item_name']] = $ls['last_time'];
    }
}

// Fetch dynamic active items from items_master and validate completeness
$active_items = [];
$items_res = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ORDER BY item_name ASC");
if ($items_res) {
    while ($ir = $items_res->fetch_assoc()) {
        $active_items[] = $ir['item_name'];
    }
}

if (empty($active_items)) {
    die("Report not available. No active items defined in items master.");
}

// Check that at least one item exists for the selected date
if (empty($status_data)) {
    die("Report not available. No items found for the selected date.");
}

// Create new PDF document
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Site Asset Management System');
$pdf->SetAuthor('Site Asset Management System');
$pdf->SetTitle('Status Report - ' . $info['tubewell_name']);
$pdf->SetSubject('Daily Status Report');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Define colors
$headerColor = array(70, 130, 180);    // Steel Blue
$titleColor = array(30, 144, 255);     // Dodger Blue
$sectionColor = array(100, 149, 237);  // Cornflower Blue
$rowColor1 = array(240, 248, 255);     // Alice Blue
$rowColor2 = array(224, 255, 255);     // Light Cyan
$borderColor = array(200, 200, 200);   // Light Gray

// Title Section with background
$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 15, 'DAILY STATUS REPORT', 0, 1, 'C', true);
$pdf->Ln(8);

// Site Information Section
$pdf->SetFillColor($sectionColor[0], $sectionColor[1], $sectionColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'SITE INFORMATION', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('helvetica', '', 10);

$site_info = "Site Name: " . ($info['site_name'] ?? 'N/A') . "\n" .
             "Address: " . ($info['address'] ?? 'N/A') . "\n" .
             "Division: " . ($info['division_name'] ?? 'N/A') . "\n" .
             "Contractor: " . ($info['contractor_name'] ?? 'N/A') . "\n" .
             "Site Incharge: " . ($info['site_incharge'] ?? 'N/A');

$pdf->MultiCell(0, 6, $site_info, 1, 'L', true);
$pdf->Ln(5);

// Tubewell Information Section
$pdf->SetFillColor($sectionColor[0], $sectionColor[1], $sectionColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'TUBEWELL INFORMATION', 0, 1, 'L', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('helvetica', '', 10);

$tubewell_info = "Tubewell Name: " . ($info['tubewell_name'] ?? 'N/A') . "\n" .
                 "Tubewell Address: " . ($info['tw_address'] ?? 'N/A') . "\n" .
                 "Incharge: " . ($info['incharge_name'] ?? 'N/A') . "\n" .
                 "Report Date: " . date('d M Y', strtotime($report_date));

$pdf->MultiCell(0, 6, $tubewell_info, 1, 'L', true);
$pdf->Ln(10);

// Equipment Status Table
$pdf->SetFillColor($titleColor[0], $titleColor[1], $titleColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'EQUIPMENT STATUS', 0, 1, 'C', true);
$pdf->Ln(5);

// Table header
$header = array('Sr.No', 'Item Name', 'Make/Model', 'Size/Capacity', 'Status', 'HMI/Local', 'Web', 'Remark', 'Added By', 'Last Updated');
$w = array(10, 45, 35, 22, 22, 16, 14, 40, 25, 25);

// Header row with color
$pdf->SetFillColor($sectionColor[0], $sectionColor[1], $sectionColor[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 8);
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 8, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();

// Table data with alternating row colors
$counter = 1;
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);

foreach ($status_data as $item => $data) {
    $last_time_selected = $last_updated_on_selected[$item] ?? ($data['updated_at'] ?? '');
    $last_time_any = $last_updated[$item] ?? '';
    $last_str_selected = $last_time_selected ? date('d M Y H:i', strtotime($last_time_selected)) : 'N/A';
    $last_str_any = $last_time_any ? date('d M Y H:i', strtotime($last_time_any)) : 'N/A';
    
    // Alternate row colors
    $fillColor = ($counter % 2 == 0) ? $rowColor1 : $rowColor2;
    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
    
    $pdf->Cell($w[0], 6, $counter++, 1, 0, 'C', true);
    $pdf->Cell($w[1], 6, $item, 1, 0, 'L', true);
    $pdf->Cell($w[2], 6, $data['make_model'] ?? 'N/A', 1, 0, 'L', true);
    $pdf->Cell($w[3], 6, $data['size_capacity'] ?? 'N/A', 1, 0, 'L', true);
    
    // Status with conditional coloring
    $status = $data['status'] ?? 'N/A';
    if (strtoupper($status) === 'OPERATIONAL') {
        $pdf->SetTextColor(0, 128, 0); // Green for operational
    } elseif (strtoupper($status) === 'DOWN') {
        $pdf->SetTextColor(255, 0, 0); // Red for down
    }
    $pdf->Cell($w[4], 6, $status, 1, 0, 'C', true);
    $pdf->SetTextColor(0, 0, 0); // Reset color
    
    // HMI/Local check with icon-like representation
    $hmi_check = ($data['check_hmi_local'] ?? 0) ? 'Yes' : 'No';
    // $hmi_check = ($data['check_hmi_local'] ?? 0) ? '✓' : '✗';
    $pdf->Cell($w[5], 6, $hmi_check, 1, 0, 'C', true);
    
    // Web check with icon-like representation
    $web_check = ($data['check_web'] ?? 0) ? 'Yes' : 'No';
    // $web_check = ($data['check_web'] ?? 0) ? '✓' : '✗';
    $pdf->Cell($w[6], 6, $web_check, 1, 0, 'C', true);
    
    $pdf->Cell($w[7], 6, $data['remark'] ?? 'N/A', 1, 0, 'L', true);
    // Created by column
    $pdf->Cell($w[8], 6, $data['created_by'] ?? 'N/A', 1, 0, 'L', true);
    // Prefer row's updated_at, then overall last, then selected-day last
    $row_updated = isset($data['updated_at']) && $data['updated_at'] ? date('d M Y H:i', strtotime($data['updated_at'])) : '';
    $final_last = $row_updated ?: ($last_str_any !== 'N/A' ? $last_str_any : $last_str_selected);
    $final_last = $final_last ?: 'N/A';
    $pdf->Cell($w[9], 6, $final_last, 1, 0, 'C', true);
    $pdf->Ln();
}

// Custom Footer
$pdf->SetY(-20);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated on: ' . date('d M Y H:i:s') . ' | Site Asset Management System', 'T', 0, 'C', true);

// Add page border
$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(200, 200, 200);
$pdf->Rect(5, 5, $pdf->getPageWidth() - 10, $pdf->getPageHeight() - 10);

// Output PDF
$pdf->Output('status_report_' . ($info['tubewell_name'] ?? 'unknown') . '_' . $report_date . '.pdf', 'D');
?>