<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Check if tubewell_id parameter is set
if (!isset($_GET['tubewell_id'])) {
    die("Invalid request. Tubewell ID parameter is required.");
}

$tubewell_id = $_GET['tubewell_id'];
$hardcoded_items = ['Actuator Main', 'Actuator Bypass', 'Actuator Outlet', 'Motor', 'Control Panel', 'Sensor'];

// Get tubewell info for display
$tubewell_sql = "SELECT tw.*, s.site_name, s.id as site_id 
                 FROM tubewells tw 
                 JOIN sites s ON tw.site_id = s.id 
                 WHERE tw.id = ?";
$stmt = $conn->prepare($tubewell_sql);
$stmt->bind_param("i", $tubewell_id);
$stmt->execute();
$result = $stmt->get_result();
$tubewell_info = $result->fetch_assoc();

if (!$tubewell_info) {
    die("Tubewell not found with ID: " . $tubewell_id);
}

// Get existing parameters from parameters table (current configuration)
$param_sql = "SELECT * FROM parameters WHERE tubewell_id = ?";
$stmt = $conn->prepare($param_sql);
$stmt->bind_param("i", $tubewell_id);
$stmt->execute();
$existing_params = $stmt->get_result();

$existing_data = [];
while($row = $existing_params->fetch_assoc()) {
    $existing_data[$row['item_name']] = $row;
}

// If no parameters exist, redirect to add_parameters.php
if (empty($existing_data)) {
    header("Location: add_parameters.php?tubewell_id=" . $tubewell_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete existing parameters
    $delete_sql = "DELETE FROM parameters WHERE tubewell_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $tubewell_id);
    $delete_stmt->execute();
    
    // Insert updated parameters
    foreach ($hardcoded_items as $item) {
        $make_model = $_POST['make_model'][$item] ?? '';
        $size_capacity = $_POST['size_capacity'][$item] ?? '';
        $status = $_POST['status'][$item] ?? 'Active';
        $check_hmi_local = isset($_POST['check_hmi_local'][$item]) ? 1 : 0;
        $check_web = isset($_POST['check_web'][$item]) ? 1 : 0;
        $remark = $_POST['remark'][$item] ?? '';

        $sql = "INSERT INTO parameters (tubewell_id, item_name, make_model, size_capacity, status, check_hmi_local, check_web, remark) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssiis", $tubewell_id, $item, $make_model, $size_capacity, $status, $check_hmi_local, $check_web, $remark);
        $stmt->execute();
    }
    
    header("Location: view_tubewell.php?tubewell_id=" . $tubewell_id . "&message=parameters_updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parameters - <?php echo htmlspecialchars($tubewell_info['tubewell_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .edit-form table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .edit-form th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .edit-form td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .edit-form tr:hover {
            background-color: #f7fafc;
        }
        
        .edit-form tr:last-child td {
            border-bottom: none;
        }
        
        .form-control-sm {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            width: 100%;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control-sm:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .checkbox-cell {
            text-align: center;
        }
        
        .item-name {
            font-weight: 600;
            color: #2d3748;
        }
        
        .last-updated {
            background: #f0fff4;
            border: 1px solid #c6f6d5;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
     <?php include('header.php'); ?>

    <div class="container">
        <div class="card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="color: #2d3748;">✏️ Edit Parameters</h2>
                <p style="color: #718096;">
                    Site: <strong><?php echo htmlspecialchars($tubewell_info['site_name']); ?></strong> | 
                    Tubewell: <strong><?php echo htmlspecialchars($tubewell_info['tubewell_name']); ?></strong>
                </p>
            </div>

            <div class="last-updated">
                <h4 style="color: #276749; margin: 0;">📝 Editing Current Configuration</h4>
                <p style="color: #2d3748; margin: 0.5rem 0 0 0;">
                    You are editing the base parameters configuration. For daily status updates, use "Add Daily Status".
                </p>
            </div>
            
            <form method="POST" class="edit-form">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">Sr.No</th>
                                <th width="15%">Item Name</th>
                                <th width="20%">Make/Model</th>
                                <th width="15%">Size/Capacity</th>
                                <th width="10%">Status</th>
                                <th width="10%">HMI/Local</th>
                                <th width="10%">Web</th>
                                <th width="15%">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hardcoded_items as $index => $item): 
                                $existing = $existing_data[$item] ?? [];
                            ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600;"><?php echo $index + 1; ?></td>
                                <td class="item-name"><?php echo $item; ?></td>
                                <td>
                                    <input type="text" 
                                           name="make_model[<?php echo $item; ?>]" 
                                           class="form-control-sm" 
                                           placeholder="Enter make/model"
                                           value="<?php echo htmlspecialchars($existing['make_model'] ?? ''); ?>"
                                           required>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="size_capacity[<?php echo $item; ?>]" 
                                           class="form-control-sm" 
                                           placeholder="Enter size/capacity"
                                           value="<?php echo htmlspecialchars($existing['size_capacity'] ?? ''); ?>"
                                           required>
                                </td>
                                <td>
                                    <select name="status[<?php echo $item; ?>]" class="form-control-sm" required>
                                        <option value="Active" <?php echo ($existing['status'] ?? 'Active') == 'Active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="Inactive" <?php echo ($existing['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="Maintenance" <?php echo ($existing['status'] ?? '') == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </td>
                                <td class="checkbox-cell">
                                    <input type="checkbox" 
                                           name="check_hmi_local[<?php echo $item; ?>]" 
                                           class="custom-checkbox"
                                           <?php echo ($existing['check_hmi_local'] ?? 0) ? 'checked' : ''; ?>>
                                </td>
                                <td class="checkbox-cell">
                                    <input type="checkbox" 
                                           name="check_web[<?php echo $item; ?>]" 
                                           class="custom-checkbox"
                                           <?php echo ($existing['check_web'] ?? 0) ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="remark[<?php echo $item; ?>]" 
                                           class="form-control-sm" 
                                           placeholder="Enter remark"
                                           value="<?php echo htmlspecialchars($existing['remark'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="btn-group" style="justify-content: center; margin-top: 2rem;">
                    <a href="view_tubewell.php?tubewell_id=<?php echo $tubewell_id; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">Update Parameters</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.edit-form');
            const inputs = form.querySelectorAll('input[required], select[required]');
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = '#e53e3e';
                    } else {
                        input.style.borderColor = '#e2e8f0';
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill all required fields');
                }
            });
            
            // Real-time validation
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '#48bb78';
                    } else {
                        this.style.borderColor = '#e53e3e';
                    }
                });
            });
        });
    </script>
</body>
</html>