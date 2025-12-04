<?php
session_start();
// Redirect to login if not logged in (must run before any output or includes)
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

include 'db_config.php';

// Search functionality
$search = '';
$where_conditions = [];
$params = [];
$types = '';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $where_conditions = [
        "site_name LIKE ?",
        "address LIKE ?", 
        "division_name LIKE ?",
        "contractor_name LIKE ?",
        "site_incharge LIKE ?",
        "contact LIKE ?",
        "created_by LIKE ?"
    ];
    $search_param = "%$search%";
    // Add multiple parameters for each condition
    for($i = 0; $i < count($where_conditions); $i++) {
        $params[] = $search_param;
        $types .= 's';
    }
}

// Build SQL query
$sql = "SELECT * FROM sites";
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" OR ", $where_conditions);
}
// $sql .= " ORDER BY site_name";

// Prepare and execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Asset Management - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/x-icon">
    <style>
        .search-container {
            margin-bottom: 2rem;
            text-align: center;
        }
        .search-form {
            display: inline-flex;
            gap: 10px;
            max-width: 500px;
            width: 100%;
        }
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
        }
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .search-btn {
            padding: 10px 20px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }
        .search-btn:hover {
            background-color: #2563eb;
        }
        .clear-btn {
            padding: 10px 20px;
            background-color: #6b7280;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .clear-btn:hover {
            background-color: #4b5563;
        }
        .search-results-info {
            text-align: center;
            margin-bottom: 1rem;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <?php include('header.php'); ?>

    <div class="container">
        <div class="card">
            <h1 style="text-align: center; margin-bottom: 1rem; color: #2d3748;  ">SiteTrack Management Dashboard</h1>
            
            <!-- Search Form -->
            <div class="search-container">
                <form method="GET" action="" class="search-form">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Search sites by name, address, contractor, incharge, contact, division or creator..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="dashboard.php" class="clear-btn">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Search Results Info -->
            <?php if(!empty($search)): ?>
                <div class="search-results-info">
                    <p>Search results for: "<strong><?php echo htmlspecialchars($search); ?></strong>"</p>
                </div>
            <?php endif; ?>
            
            <div class="sites-grid">
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="site-card">
                    <h3><?php echo htmlspecialchars($row['site_name']); ?></h3>
                    <div class="site-info">
                        <p><strong>📍 Address:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                        <p><strong>🏗️ Contractor:</strong> <?php echo htmlspecialchars($row['contractor_name']); ?></p>
                        <p><strong>👤 Incharge:</strong> <?php echo htmlspecialchars($row['site_incharge']); ?></p>
                        <p><strong>📞 Contact:</strong> <?php echo htmlspecialchars($row['contact']); ?></p>
                        <p><strong>💧 Tubewells:</strong> <span class="badge badge-success"><?php echo $row['number_of_tubewell']; ?> Total</span></p>
                        <p><strong>🏛️ Division:</strong> <?php echo htmlspecialchars($row['division_name']); ?></p>
                        <p><strong>👨🏻‍💼 Created By:</strong> <?php echo htmlspecialchars($row['created_by']); ?></p>
                    </div>
                    <div class="site-actions">
                        <a href="view_site.php?site_id=<?php echo $row['id']; ?>" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                <?php endwhile; ?>
                
                <?php if($result->num_rows == 0): ?>
                <div class="card" style="text-align: center; grid-column: 1 / -1;">
                    <h3><?php echo empty($search) ? 'No Sites Found' : 'No Matching Sites Found'; ?></h3>
                    <p><?php echo empty($search) ? 'Start by adding your first site!' : 'Try adjusting your search terms.'; ?></p>
                    <?php if(empty($search)): ?>
                        <a href="create_site.php" class="btn btn-primary">Add First Site</a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary">View All Sites</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($_SESSION['role'] == 'admin') { ?>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="create_site.php" class="btn btn-success">+ Add New Site</a>
            </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>