<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

$message = '';
$type = ''; // success or error 

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)($_POST['delete_id'] ?? 0);
    if ($del = $conn->prepare("DELETE FROM items_master WHERE id = ?")) {
        $del->bind_param('i', $del_id);
        $del->execute();
    }
    header("Location: add_item_master.php?message=deleted");
    exit();
}

// Handle create item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($item_name !== '') {
        // Check for duplicate
        $check = $conn->prepare("SELECT COUNT(*) FROM items_master WHERE LOWER(item_name) = LOWER(?)");
        $check->bind_param("s", $item_name);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            header("Location: add_item_master.php?message=exists");
            exit();
        } else {
            $stmt = $conn->prepare("INSERT INTO items_master (item_name, description, is_active) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $item_name, $description, $is_active);
            $stmt->execute();
            header("Location: add_item_master.php?message=created");
            exit();
        }
    }
}

// Show message based on ?message=
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'created':
            $message = "✅ Item added successfully!";
            $type = "success";
            break;
        case 'exists':
            $message = "⚠️ This item already exists!";
            $type = "error";
            break;
        case 'deleted':
            $message = "🗑️ Item deleted successfully!";
            $type = "success";
            break;
        default:
            $message = "";
    }
}

// Fetch existing items
$items = [];
$res = $conn->query("SELECT id, item_name, description, is_active FROM items_master ");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Add Items Master</title>
	<link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .alert {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
	<?php include('header.php'); ?>

	<div class="container">
		<div class="card">
			<h2 style="text-align:center; color:#2d3748;">🧩 Items Master</h2>
			<p style="text-align:center; color:#718096;">Create and manage items used in daily status.</p>

            <?php if ($message): ?>
                <div class="alert <?php echo htmlspecialchars($type); ?>" style="max-width:700px; margin:10px auto;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

			<form method="POST" class="parameter-form" style="max-width:700px; margin:1rem auto 2rem auto;">
				<div class="form-group">
					<label class="form-label">Item Name *</label>
					<input type="text" name="item_name" class="form-control" placeholder="Enter item name" required>
				</div>
				<div class="form-group">
					<label class="form-label">Description</label>
					<textarea name="description" class="form-control" rows="3" placeholder="Optional description"></textarea>
				</div>
				<div class="form-group" style="display:flex; align-items:center; gap:0.5rem;">
					<input type="checkbox" id="is_active" name="is_active" checked>
					<label for="is_active" class="form-label" style="margin:0;">Active</label>
				</div>
				<div class="btn-group">
					<a href="dashboard.php" class="btn btn-secondary">Back</a>
					<button type="submit" class="btn btn-success">Add Item</button>
				</div>
			</form>

			<div class="table-container" style="overflow-x:auto;">
			    <table style="width:100%; border-collapse: collapse; font-family: Arial, sans-serif;">
			        <thead style="background-color: #f4f4f4; text-align: left;">
			            <tr>
			                <th style="width:10%; padding: 8px; border-bottom: 2px solid #ddd;">Sr No</th>
			                <th style="width:25%; padding: 8px; border-bottom: 2px solid #ddd;">Item Name</th>
			                <th style="width:35%; padding: 8px; border-bottom: 2px solid #ddd;">Description</th>
			                <th style="width:15%; padding: 8px; border-bottom: 2px solid #ddd;">Status</th>
			                <th style="width:15%; padding: 8px; border-bottom: 2px solid #ddd;">Actions</th>
			            </tr>
			        </thead>
			        <tbody>
			            <?php if (empty($items)): ?>
			                <tr>
			                    <td colspan="5" style="text-align:center; color:#718096; padding: 12px;">No items found.</td>
			                </tr>
			            <?php else: $i=1; foreach ($items as $it): ?>
			                <tr>
			                    <td style="text-align:center; padding: 8px; border-bottom: 1px solid #eee;"><?php echo $i++; ?></td>
			                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
			                        <strong><?php echo htmlspecialchars($it['item_name']); ?></strong>
			                    </td>
			                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
			                        <?php echo htmlspecialchars($it['description'] ?? ''); ?>
			                    </td>
			                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
			                        <?php if ((int)$it['is_active'] === 1): ?>
			                            <span class="badge badge-success" style="background-color:#28a745; color:#fff; padding:3px 7px; border-radius:4px;">Active</span>
			                        <?php else: ?>
			                            <span class="badge badge-secondary" style="background-color:#6c757d; color:#fff; padding:3px 7px; border-radius:4px;">Inactive</span>
			                        <?php endif; ?>
			                    </td>
			                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
			                        <div style="display:flex; gap:4px;">
			                            <a href="edit_item_master.php?item_id=<?php echo (int)$it['id']; ?>" 
			                               style="text-decoration:none; padding:4px 8px; background-color:#17a2b8; color:white; border-radius:4px;">✏️</a>
			                            <form method="POST" style="margin:0;" 
			                                  onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">
			                                <input type="hidden" name="delete_id" value="<?php echo (int)$it['id']; ?>">
			                                <button type="submit" 
			                                        style="padding:4px 8px; background-color:#dc3545; color:white; border:none; border-radius:4px;">🗑️</button>
			                            </form>
			                        </div>
			                    </td>
			                </tr>
			            <?php endforeach; endif; ?>
			        </tbody>
			    </table>
			</div>
		</div>
	</div>
</body>
</html>
