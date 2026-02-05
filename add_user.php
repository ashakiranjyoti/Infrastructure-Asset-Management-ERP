<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

$errors = [];

// Handle deletion from Existing Users table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int)($_POST['delete_id'] ?? 0);
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $del_id) {
        header('Location: add_user.php?error=cannot_delete_self');
        exit();
    }
    if ($del = $conn->prepare('DELETE FROM users WHERE id = ?')) {
        $del->bind_param('i', $del_id);
        $del->execute();
    }
    header('Location: add_user.php?message=deleted');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = strtolower(trim($_POST['role'] ?? 'user'));
    if ($role !== 'admin' && $role !== 'user') {
        $role = 'user';
    }
    $access_type = strtolower(trim($_POST['access_type'] ?? 'full'));
    if ($access_type !== 'full' && $access_type !== 'view') {
        $access_type = 'full';
    }

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Username uniqueness
    if (empty($errors)) {
        if ($chk = $conn->prepare('SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1')) {
            $chk->bind_param('s', $username);
            $chk->execute();
            $r = $chk->get_result();
            if ($r && $r->num_rows > 0) {
                $errors[] = 'Username already exists. Please choose a different username.';
            }
            $chk->close();
        }
    }

    if (empty($errors)) {
        if ($ins = $conn->prepare('INSERT INTO users (username, password, full_name, role, access_type) VALUES (?, ?, ?, ?, ?)')) {
            $ins->bind_param('sssss', $username, $password, $full_name, $role, $access_type);

            if ($ins->execute()) {
                header('Location: users.php?message=created');
                exit();
            } else {
                $errors[] = 'Database error while creating user.';
            }
        } else {
            $errors[] = 'Database error while preparing statement.';
        }
    }
}
// Fetch existing users (for quick reference below the form)
$existing_users = [];
if ($rs = $conn->query("SELECT id, username, COALESCE(full_name,'') AS full_name, COALESCE(role, 'User') AS role, COALESCE(access_type, 'full') AS access_type FROM users ORDER BY username ASC")) {
    while ($row = $rs->fetch_assoc()) {
        $existing_users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="hi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
</head>

<body>
    <?php include('header.php'); ?>
    <div class="container">
        <div class="card" style="max-width:600px; margin:0 auto;">
            <h2 style="text-align:center; color:#2d3748;">+ Add User</h2>
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
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control">
                            <?php $roleSel = strtolower($_POST['role'] ?? 'user'); ?>
                            <option value="admin" <?php echo ($roleSel === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="user" <?php echo ($roleSel === 'user') ? 'selected' : ''; ?>>User</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Access Type</label>
                        <?php $accessSel = strtolower($_POST['access_type'] ?? 'full'); ?>
                        <select name="access_type" class="form-control">
                            <option value="full" <?php echo ($accessSel === 'full') ? 'selected' : ''; ?>>Full Control</option>
                            <option value="view" <?php echo ($accessSel === 'view') ? 'selected' : ''; ?>>View Only</option>
                        </select>
                    </div>

                    
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="btn-group" style="margin-top:1rem;">
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
        <div class="card" style="max-width:600px; margin:1.25rem auto 0 auto;" hidden>
            <h3 style="color:#2d3748; margin-top:0;">Existing Users</h3>
            <p style="color:#718096; margin-top:0;">Check username and role before adding a new user.</p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="8%">#</th>
                            <th width="32%">Username</th>
                            <th width="28%">Full Name</th>
                            <th width="16%">Role</th>
                            <th width="16%">Access</th>

                            <th width="16%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($existing_users)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#718096;">No users found.</td>
                            </tr>
                            <?php else: $i = 1;
                            foreach ($existing_users as $eu): ?>
                                <tr>
                                    <td style="text-align:center;"><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($eu['username']); ?></td>
                                    <td><?php echo htmlspecialchars($eu['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($eu['role']); ?></td>
                                    <td><?php echo htmlspecialchars($eu['access_type'] === 'full' ? 'Full Control' : 'View Only'); ?></td>

                                    <td>
                                        <a class="btn btn-info btn-sm" href="edit_user.php?user_id=<?php echo (int)$eu['id']; ?>">✏️ Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo (int)$eu['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                        </form>
                                    </td>
                                </tr>
                        <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
