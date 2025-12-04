<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Validate item_id
if (!isset($_GET['item_id'])) {
    die('Invalid request. item_id is required.');
}
$item_id = (int)$_GET['item_id'];

// Load existing LCS item
$stmt = $conn->prepare("SELECT id, item_name, description, is_active FROM lcs_item WHERE id = ?");
if (!$stmt) {
    die('Database error: ' . $conn->error);
}
$stmt->bind_param('i', $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
if (!$item) {
    die('Item not found.');
}

$error = '';
$message = '';
$type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($item_name !== '') {
        // Check for duplicate (excluding current record)
        $check = $conn->prepare("SELECT COUNT(*) FROM lcs_item WHERE LOWER(item_name) = LOWER(?) AND id != ?");
        $check->bind_param('si', $item_name, $item_id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            $error = "⚠️ Another LCS item with this name already exists!";
        } else {
            // Proceed with update
            $up = $conn->prepare("UPDATE lcs_item SET item_name = ?, description = ?, is_active = ? WHERE id = ?");
            if (!$up) {
                $error = 'Database error: ' . $conn->error;
            } else {
                $up->bind_param('ssii', $item_name, $description, $is_active, $item_id);
                if ($up->execute()) {
                    header('Location: add_lcs_item.php?message=updated');
                    exit();
                } else {
                    $error = '❌ Error updating item: ' . $conn->error;
                }
            }
        }
    } else {
        $error = '⚠️ Item Name is required.';
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit LCS Item - <?php echo htmlspecialchars($item['item_name']); ?></title>
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
		<div class="card" style="max-width:800px; margin:0 auto;">
			<h2 style="text-align:center; color:#2d3748;">✏️ Edit LCS Item</h2>
			<p style="text-align:center; color:#718096;">Modify details for the selected LCS item.</p>

			<?php if ($error): ?>
				<div class="alert error" style="max-width:700px; margin:10px auto;">
					<?php echo htmlspecialchars($error); ?>
				</div>
			<?php endif; ?>

			<form method="POST" class="parameter-form" style="max-width:700px; margin:0 auto;">
				<div class="form-group">
					<label class="form-label">Item Name *</label>
					<input type="text" name="item_name" class="form-control" required value="<?php echo htmlspecialchars($item['item_name']); ?>">
				</div>
				<div class="form-group">
					<label class="form-label">Description</label>
					<textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
				</div>
				<div class="form-group" style="display:flex; align-items:center; gap:0.5rem;">
					<input type="checkbox" id="is_active" name="is_active" <?php echo ((int)$item['is_active'] === 1) ? 'checked' : ''; ?>>
					<label for="is_active" class="form-label" style="margin:0;">Active</label>
				</div>
				<div class="btn-group">
					<a href="add_lcs_item.php" class="btn btn-secondary">Cancel</a>
					<button type="submit" class="btn btn-primary">Update Item</button>
				</div>
			</form>
		</div>
	</div>
</body>
</html>
