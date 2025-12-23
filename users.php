<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Handle delete (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)($_POST['delete_id'] ?? 0);
    // Optional: prevent deleting your own account
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $del_id) {
        header('Location: users.php?error=cannot_delete_self');
        exit();
    }
    if ($del = $conn->prepare('DELETE FROM users WHERE id = ?')) {
        $del->bind_param('i', $del_id);
        $del->execute();
    }
    header('Location: users.php?message=deleted');
    exit();
}

// Fetch users
$users = [];
if ($res = $conn->query('SELECT id, username, password, full_name, role, access_type FROM users ')) {
    while ($row = $res->fetch_assoc()) { $users[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
 <?php include('header.php'); ?>
<div class="container">
    <div class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <h2 style="color:#2d3748; margin:0;">👥 Users</h2>
            <a class="btn btn-primary" href="add_user.php">+ Add User</a>
        </div>
        <?php if (isset($_GET['error']) && $_GET['error']==='cannot_delete_self'): ?>
            <div class="alert alert-error">You cannot delete your own account while logged in.</div>
        <?php endif; ?>
        <div class="table-container" style="overflow-x:auto;">
    <table style="width:100%; border-collapse: collapse; font-family: Arial, sans-serif;">
        <thead style="background-color: #f4f4f4; text-align: left;">
            <tr>
                <th style="width:10%; padding: 8px; border-bottom: 2px solid #ddd;">Sr No</th>
                <th style="width:25%; padding: 8px; border-bottom: 2px solid #ddd;">Username</th>
                <th style="width:25%; padding: 8px; border-bottom: 2px solid #ddd;">Password</th>
                <th style="width:35%; padding: 8px; border-bottom: 2px solid #ddd;">Full Name</th>
                <th style="width:15%; padding: 8px; border-bottom: 2px solid #ddd;">Role</th>
                <th style="width:15%; padding: 8px; border-bottom: 2px solid #ddd;">Access</th>
                <th style="width:15%; padding: 8px; border-bottom: 2px solid #ddd;">Action </th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#718096; padding: 12px;">No users found.</td>
                </tr>
            <?php else: $i=1; foreach ($users as $u): ?>
                <tr>
                    <td style="text-align:center; padding: 8px; border-bottom: 1px solid #eee;">&nbsp;<?php echo $i++; ?></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;"><strong><?php echo htmlspecialchars($u['password']); ?></strong></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">&nbsp;<?php echo htmlspecialchars($u['full_name'] ?? ''); ?></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">&nbsp;<?php echo htmlspecialchars($u['role'] ?? 'User'); ?></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">&nbsp;<?php echo htmlspecialchars($u['access_type'] ?? 'Full'); ?></td>
                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                        <!-- Flex container to align buttons horizontally -->
                        <div style="display:flex; gap:4px;">
                            <a href="edit_user.php?user_id=<?php echo (int)$u['id']; ?>" 
                               style="text-decoration:none; padding:4px 8px; background-color:#17a2b8; color:white; border-radius:4px; display:flex; align-items:center; justify-content:center;">
                               ✏️
                            </a>
                            <form method="POST" style="margin:0;" 
                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                <input type="hidden" name="delete_id" value="<?php echo (int)$u['id']; ?>">
                                <button type="submit" 
                                        style="padding:4px 8px; background-color:#dc3545; color:white; border:none; border-radius:4px; display:flex; align-items:center; justify-content:center;">
                                    🗑️
                                </button>
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
