<?php
include 'db_config.php';

// Parameters: site_id, tubewell_id, from_date, to_date
if (!isset($_GET['site_id']) || !isset($_GET['tubewell_id']) || !isset($_GET['from_date']) || !isset($_GET['to_date'])) {
    die("Invalid request. site_id, tubewell_id, from_date and to_date parameters are required.");
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

// Change dates set within range
$chg = $conn->prepare("SELECT DISTINCT status_date FROM status_history WHERE site_id = ? AND tubewell_id = ? AND status_date BETWEEN ? AND ? ORDER BY status_date ASC");
$chg->bind_param('iiss', $site_id, $tubewell_id, $from_date, $to_date);
$chg->execute();
$crs = $chg->get_result();
$change_dates = [];
while ($cd = $crs->fetch_assoc()) { $change_dates[$cd['status_date']] = true; }

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

// Set headers for Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-disposition: attachment; filename=Site_Report_" . ($info['site_name'] ?? 'unknown') . "_" . $from_date . "_to_" . $to_date . ".xls");

// Start HTML output for Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { font-size: 16px; font-weight: bold; text-align: center; margin-bottom: 10px; }
        .site-info { font-size: 11px; margin-bottom: 15px; }
        .date-section { font-size: 12px; font-weight: bold; background-color: #E6E6FA; padding: 5px; margin: 10px 0; }
        .master-note { background-color: #F0F8FF; padding: 5px; margin: 5px 0; }
        .note-info { font-style: italic; color: #808080; font-size: 9px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
        th { background-color: #4682B4; color: white; font-weight: bold; text-align: center; padding: 6px; border: 1px solid #333; font-size: 9px; }
        td { padding: 4px; border: 1px solid #333; font-size: 9px; }
        .changed-row { background-color: #FFF7E6; }
        .even-row { background-color: #F0F0F0; }
        .odd-row { background-color: #FFFFFF; }
        .center { text-align: center; }
        .footer { font-style: italic; text-align: center; margin-top: 20px; border-top: 1px solid #333; padding-top: 5px; }
    </style>
</head>
<body>

<div class="header">SITE REPORT</div>

<div class="site-info">
    <strong>Site:</strong> <?php echo $info['site_name'] ?? 'N/A'; ?> | 
    <strong>Zone Name:</strong> <?php echo $info['zone_name'] ?? 'N/A'; ?> | 
    <strong>Tubewell:</strong> <?php echo $info['tubewell_name'] ?? 'N/A'; ?> | 
    <strong>From:</strong> <?php echo date('d M Y', strtotime($from_date)); ?> | 
    <strong>To:</strong> <?php echo date('d M Y', strtotime($to_date)); ?>
</div>

<?php
foreach ($rows_by_date as $d => $rows) {
    ?>
    <div class="date-section">Date: <?php echo date('d M Y', strtotime($d)); ?> (<?php echo count($rows); ?> items)</div>
    
    <?php
    // Master note for this date
    if (isset($master_notes_by_date[$d]) && !empty(trim($master_notes_by_date[$d]['note']))) {
        ?>
        <div class="master-note">
            <strong>Master Note:</strong><br>
            <?php echo htmlspecialchars($master_notes_by_date[$d]['note']); ?>
        </div>
        <div class="note-info">
            Updated by: <?php echo $master_notes_by_date[$d]['updated_by'] ?? '—'; ?> on <?php echo date('d M Y H:i', strtotime($master_notes_by_date[$d]['updated_at'])); ?>
        </div>
        <?php
    }
    ?>

    <table>
        <thead>
            <tr>
                <th>Sr.No</th>
                <th>Item Name</th>
                <th>Make/Model</th>
                <th>Size/Cap.</th>
                <th>Status</th>
                <th>HMI/Local</th>
                <th>Web</th>
                <th>Remark</th>
                <th>Added By</th>
                <th>Updated At</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $c = 1;
            foreach ($rows as $r) {
                $isChanged = isset($changed_items_by_date[$d]) && isset($changed_items_by_date[$d][$r['item_name']]);
                $rowClass = $isChanged ? 'changed-row' : ($c % 2 == 0 ? 'even-row' : 'odd-row');
                
                // Fix for updated_at - show actual value properly
                $updatedAt = isset($r['updated_at']) && $r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '—';
                ?>
                <tr class="<?php echo $rowClass; ?>">
                    <td class="center"><?php echo $c; ?></td>
                    <td><?php echo htmlspecialchars($r['item_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($r['make_model'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($r['size_capacity'] ?? 'N/A'); ?></td>
                    <td class="center"><?php echo htmlspecialchars($r['status'] ?? 'N/A'); ?></td>
                    <td class="center"><?php echo ((int)($r['check_hmi_local'] ?? 0)) ? 'Yes' : 'No'; ?></td>
                    <td class="center"><?php echo ((int)($r['check_web'] ?? 0)) ? 'Yes' : 'No'; ?></td>
                    <td><?php echo htmlspecialchars($r['remark'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($r['created_by'] ?? 'N/A'); ?></td>
                    <td class="center"><?php echo $updatedAt; ?></td>
                </tr>
                <?php
                $c++;
            }
            ?>
        </tbody>
    </table>
    <br>
    <?php
}
?>

<div class="footer">
    Generated: <?php echo date('d M Y H:i:s'); ?>
</div>

</body>
</html>