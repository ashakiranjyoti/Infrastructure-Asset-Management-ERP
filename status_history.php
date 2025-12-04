<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Basic auth guard: require logged-in user
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not authenticated
    header('Location: login.php');
    exit();
}

// Check if tubewell_id parameter is set
if (!isset($_GET['tubewell_id'])) {
    die("Invalid request. Tubewell ID parameter is required.");
}

$tubewell_id = $_GET['tubewell_id'];

// Get tubewell info
$tubewell_sql = "SELECT tw.*, s.site_name 
                 FROM tubewells tw 
                 JOIN sites s ON tw.site_id = s.id 
                 WHERE tw.id = ?";
$stmt = $conn->prepare($tubewell_sql);
$stmt->bind_param("i", $tubewell_id);
$stmt->execute();
$tubewell_info = $stmt->get_result()->fetch_assoc();

// Load dynamic active items and their count
$active_items = [];
$items_res = $conn->query("SELECT item_name FROM items_master WHERE is_active = 1 ORDER BY item_name ASC");
if ($items_res) {
    while ($ir = $items_res->fetch_assoc()) {
        $active_items[] = $ir['item_name'];
    }
}
$active_items_count = count($active_items);

// Get all available dates for this tubewell (any items present)
$dates_sql = "SELECT status_date 
              FROM status_history 
              WHERE tubewell_id = ? 
              GROUP BY status_date 
              ORDER BY status_date DESC";
$stmt = $conn->prepare($dates_sql);
$stmt->bind_param("i", $tubewell_id);
$stmt->execute();
$dates_result = $stmt->get_result();

// Get status for specific date if selected
$selected_date = $_GET['date'] ?? '';
$status_data = [];

if ($selected_date) {
    $status_sql = "SELECT * FROM status_history 
                   WHERE tubewell_id = ? AND status_date = ? 
                   ORDER BY item_name";
    $stmt = $conn->prepare($status_sql);
    $stmt->bind_param("is", $tubewell_id, $selected_date);
    $stmt->execute();
    $status_result = $stmt->get_result();
    
    $row_updated_at_selected = [];
    while($row = $status_result->fetch_assoc()) {
        $status_data[$row['item_name']] = $row;
        if (isset($row['updated_at']) && $row['updated_at']) {
            $row_updated_at_selected[$row['item_name']] = $row['updated_at'];
        }
    }

    // Count items added on this date
    $count_sql = "SELECT COUNT(*) as c FROM status_history WHERE tubewell_id = ? AND status_date = ?";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("is", $tubewell_id, $selected_date);
    $stmt->execute();
    $count_row = $stmt->get_result()->fetch_assoc();
    $items_added = (int)$count_row['c'];

    // Compute missing items for this date
    $present_items = array_keys($status_data);
    $missing_items = array_values(array_diff($active_items, $present_items));

    // Build timestamp maps from status_history.updated_at when available and fallback to change log
    $last_updated = [];
    $last_updated_on_selected = [];
    $selected_start = $selected_date . ' 00:00:00';
    $selected_end = $selected_date . ' 23:59:59';

    // Detect updated_at column in status_history
    $has_updated_at_col = false;
    $col_check_ts = $conn->query("SHOW COLUMNS FROM status_history LIKE 'updated_at'");
    if ($col_check_ts && $col_check_ts->num_rows > 0) {
        $has_updated_at_col = true;
    }

    if ($has_updated_at_col) {
        // Prefer status_history.updated_at
        // Overall last update up to selected
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

        // Last update specifically on selected date
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
        // Ensure current selected rows with updated_at are reflected
        foreach ($row_updated_at_selected as $iname => $ts) {
            $last_updated_on_selected[$iname] = $ts;
            if (!isset($last_updated[$iname]) || strtotime($ts) > strtotime($last_updated[$iname])) {
                $last_updated[$iname] = $ts;
            }
        }
    } else {
        // Fallback to change log when updated_at does not exist
        $log_sql = "SELECT item_name, MAX(changed_at) as last_time
                    FROM status_change_log
                    WHERE tubewell_id = ? AND changed_at <= ?
                    GROUP BY item_name";
        $stmt = $conn->prepare($log_sql);
        $stmt->bind_param("is", $tubewell_id, $selected_end);
        $stmt->execute();
        $log_res = $stmt->get_result();
        while ($lr = $log_res->fetch_assoc()) {
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

    // Find previous date and load its statuses to compare
    $prev_date_sql = "SELECT MAX(status_date) as prev_date
                      FROM status_history
                      WHERE tubewell_id = ? AND status_date < ?";
    $stmt = $conn->prepare($prev_date_sql);
    $stmt->bind_param("is", $tubewell_id, $selected_date);
    $stmt->execute();
    $prev_date_row = $stmt->get_result()->fetch_assoc();
    $previous_date = $prev_date_row && $prev_date_row['prev_date'] ? $prev_date_row['prev_date'] : '';

    $prev_status_data = [];
    if ($previous_date) {
        $p_sql = "SELECT * FROM status_history WHERE tubewell_id = ? AND status_date = ? ORDER BY item_name";
        $pstmt = $conn->prepare($p_sql);
        $pstmt->bind_param("is", $tubewell_id, $previous_date);
        $pstmt->execute();
        $p_res = $pstmt->get_result();
        while ($pr = $p_res->fetch_assoc()) {
            $prev_status_data[$pr['item_name']] = $pr;
        }
    }

    // Compute last change timestamp per item in the window (after previous_date up to selected_date end)
    $changed_time_in_window = [];
    $window_start = $previous_date ? ($previous_date . ' 23:59:59') : '1970-01-01 00:00:00';
    $chg_sql = "SELECT item_name, MAX(changed_at) AS last_change
                FROM status_change_log
                WHERE tubewell_id = ? AND changed_at > ? AND changed_at <= ?
                GROUP BY item_name";
    $stmt = $conn->prepare($chg_sql);
    $stmt->bind_param("iss", $tubewell_id, $window_start, $selected_end);
    $stmt->execute();
    $chg_res = $stmt->get_result();
    while ($cr = $chg_res->fetch_assoc()) {
        $changed_time_in_window[$cr['item_name']] = $cr['last_change'];
    }
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status History - <?php echo htmlspecialchars($tubewell_info['tubewell_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
     <?php include('header.php'); ?>

    <div class="container">
        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #2d3748;">📈 Status History</h2>
                <p style="color: #718096;">
                    Site: <strong><?php echo htmlspecialchars($tubewell_info['site_name']); ?></strong> | 
                    Tubewell: <strong><?php echo htmlspecialchars($tubewell_info['tubewell_name']); ?></strong>
                </p>
            </div>

            <!-- Date Selection Form -->
            <form method="GET" style="margin-bottom: 2rem;">
                <input type="hidden" name="tubewell_id" value="<?php echo $tubewell_id; ?>">
                <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label class="form-label">Select Date</label>
                        <select name="date" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Select Date --</option>
                            <?php while($date_row = $dates_result->fetch_assoc()): ?>
                            <option value="<?php echo $date_row['status_date']; ?>" 
                                <?php echo $selected_date == $date_row['status_date'] ? 'selected' : ''; ?>>
                                <?php echo date('d M Y', strtotime($date_row['status_date'])); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php if ($selected_date): ?>
                    <div>
                    <?php if ($items_added > 0): ?>
                        <a href="generate_report.php?tubewell_id=<?php echo $tubewell_id; ?>&date=<?php echo $selected_date; ?>" 
                           class="btn btn-primary" target="_blank">
                            📄 Generate PDF Report
                        </a>
                    <?php else: ?>
                        <span class="badge badge-warning">No items added yet</span>
                    <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($selected_date && !empty($status_data)): ?>
            <div class="table-container">
                <h3 style="text-align: center; margin-bottom: 1rem; color: #4a5568;">
                    Status for: <?php echo date('d M Y', strtotime($selected_date)); ?>
                </h3>
                <div style="text-align:center; margin-bottom: 1rem;">
                    <span class="badge badge-info">Items Added: <?php echo $items_added; ?></span>
                    <a href="add_parameters.php?tubewell_id=<?php echo $tubewell_id; ?>&date=<?php echo $selected_date; ?>" class="btn btn-primary" style="margin-left:0.5rem;">➕ Add More Items</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sr.No</th>
                            <th>Item Name</th>
                            <th>Make/Model</th>
                            <th>Size/Capacity</th>
                            <th>Status</th>
                            <th>HMI/Local</th>
                            <th>Web</th>
                            <th>Remark</th>
                            <th>Added By</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($status_data as $item => $data): 
                            $last_time_specific = $last_updated_on_selected[$item] ?? '';
                            $last_time_any = $last_updated[$item] ?? '';
                            $last_str_selected = $last_time_specific ? date('d M Y H:i', strtotime($last_time_specific)) : '—';
                            $last_str = $last_time_any ? date('d M Y H:i', strtotime($last_time_any)) : '—';
                            $prev = $prev_status_data[$item] ?? [];
                            $changed_fields = [];
                            if (!empty($prev)) {
                                if (($data['make_model'] ?? '') !== ($prev['make_model'] ?? '')) $changed_fields[] = 'Make/Model';
                                if (($data['size_capacity'] ?? '') !== ($prev['size_capacity'] ?? '')) $changed_fields[] = 'Size/Capacity';
                                if (($data['status'] ?? '') !== ($prev['status'] ?? '')) $changed_fields[] = 'Status';
                                if ((int)($data['check_hmi_local'] ?? 0) !== (int)($prev['check_hmi_local'] ?? 0)) $changed_fields[] = 'HMI/Local';
                                if ((int)($data['check_web'] ?? 0) !== (int)($prev['check_web'] ?? 0)) $changed_fields[] = 'Web';
                                if (($data['remark'] ?? '') !== ($prev['remark'] ?? '')) $changed_fields[] = 'Remark';
                            }
                        ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($item); ?></strong></td>
                            <td><?php echo htmlspecialchars($data['make_model'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($data['size_capacity'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if(isset($data['status'])): ?>
                                <span class="status-<?php echo strtolower($data['status']); ?>">
                                    <?php echo $data['status']; ?>
                                </span>
                                <?php else: ?>
                                N/A
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if(isset($data['check_hmi_local'])): ?>
                                <span class="<?php echo $data['check_hmi_local'] ? 'check-true' : 'check-false'; ?>">
                                    <?php echo $data['check_hmi_local'] ? '✅' : '❌'; ?>
                                </span>
                                <?php else: ?>
                                N/A
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if(isset($data['check_web'])): ?>
                                <span class="<?php echo $data['check_web'] ? 'check-true' : 'check-false'; ?>">
                                    <?php echo $data['check_web'] ? '✅' : '❌'; ?>
                                </span>
                                <?php else: ?>
                                N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($data['remark'] ?? 'N/A'); ?></td>
                            <td style="white-space:nowrap;"><?php echo htmlspecialchars($data['created_by'] ?? '—'); ?></td>
                            <td>
                                <?php echo $last_str; ?>
                                <?php 
                                    $change_time_win = $changed_time_in_window[$item] ?? '';
                                    $change_time_label = $change_time_win ? date('d M Y H:i', strtotime($change_time_win))
                                        : ($last_str !== '—' ? $last_str : 'n/a');
                                ?>
                                <?php if ($previous_date && !empty($changed_fields)): ?>
                                    <div style="margin-top:4px; color:#3182ce; font-size:0.85rem;">
                                        Changed (<?php echo $change_time_label; ?>): <?php echo htmlspecialchars(implode(', ', $changed_fields)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($previous_date): ?>
                <div style="margin-top:1rem; color:#718096; font-size:0.9rem;">
                    Compared against previous date: <strong><?php echo date('d M Y', strtotime($previous_date)); ?></strong>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif ($selected_date): ?>
            <div style="text-align: center; padding: 3rem; color: #718096;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📊</div>
                <h3>No Status Record Found</h3>
                <p>No status recorded for the selected date</p>
                <a href="add_parameters.php?tubewell_id=<?php echo $tubewell_id; ?>" class="btn btn-primary">
                    Add Status for This Date
                </a>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #718096;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                <h3>Select a Date</h3>
                <p>Choose a date from the dropdown to view historical status</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="view_tubewell.php?tubewell_id=<?php echo $tubewell_id; ?>" class="btn btn-secondary">← Back to Tubewell</a>
        </div>
    </div>
</body>
</html>