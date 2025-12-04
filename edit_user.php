<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

if (!isset($_GET['user_id'])) { die('Invalid request. user_id is required.'); }
$user_id = (int)$_GET['user_id'];

$errors = [];

// Load user
$u = null;
if ($stmt = $conn->prepare('SELECT id, username, full_name, role, access_type FROM users WHERE id = ?')) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
}
if (!$u) { die('User not found.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = strtolower(trim($_POST['role'] ?? 'user'));
    if ($role !== 'admin' && $role !== 'user') { $role = 'user'; }
    $access_type = strtolower(trim($_POST['access_type'] ?? 'full'));
if ($access_type !== 'full' && $access_type !== 'view') {
    $access_type = 'full';
}

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '') { $errors[] = 'Username is required.'; }
    if ($password !== '' && $password !== $confirm_password) { $errors[] = 'Passwords do not match.'; }

    // Username uniqueness (exclude current user)
    if (empty($errors)) {
        if ($chk = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND id <> ? LIMIT 1')) {
            $chk->bind_param('si', $username, $user_id);
            $chk->execute();
            $r = $chk->get_result();
            if ($r && $r->num_rows > 0) { $errors[] = 'Username already exists. Please choose a different username.'; }
            $chk->close();
        }
    }

    if (empty($errors)) {
        if ($password !== '') {
            $sql = 'UPDATE users SET username = ?, password = ?, full_name = ?, role = ?, access_type = ? WHERE id = ?';
            if ($up = $conn->prepare($sql)) {
                $up->bind_param('sssssi', $username, $password, $full_name, $role, $access_type,  $user_id);
                if ($up->execute()) { header('Location: users.php?message=updated'); exit(); }
                else { $errors[] = 'Database error while updating user.'; }
            } else { $errors[] = 'Database error while preparing update.'; }
        } else {
            $sql = 'UPDATE users SET username = ?, full_name = ?, role = ?, access_type = ? WHERE id = ?';
            if ($up = $conn->prepare($sql)) {
                $up->bind_param('ssssi', $username, $full_name, $role, $access_type, $user_id);
                if ($up->execute()) { header('Location: users.php?message=updated'); exit(); }
                else { $errors[] = 'Database error while updating user.'; }
            } else { $errors[] = 'Database error while preparing update.'; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?php echo htmlspecialchars($u['username']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>
<body>
 <?php include('header.php'); ?>
<div class="container">
    <div class="card" style="max-width:800px; margin:0 auto;">
        <h2 style="text-align:center; color:#2d3748;">✏️ Edit User</h2>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin:0; padding-left:1rem;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? $u['username']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($_POST['full_name'] ?? $u['full_name']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control">
                    <?php $roleVal = strtolower($_POST['role'] ?? $u['role'] ?? 'user'); ?>
                    <option value="admin" <?php echo ($roleVal==='admin')?'selected':''; ?>>Admin</option>
                    <option value="user" <?php echo ($roleVal==='user')?'selected':''; ?>>User</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Access Type</label>
                <select name="access_type" class="form-control">
                    <?php $accVal = strtolower($_POST['full'] ?? $u['view'] ?? 'full'); ?>
                    <option value="full" <?php echo ($accVal==='full')?'selected':''; ?>>Full</option>
                    <option value="view" <?php echo ($accVal==='view')?'selected':''; ?>>View</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">New Password (leave blank to keep unchanged)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control">
            </div>
            <div class="btn-group">
                <a href="users.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
