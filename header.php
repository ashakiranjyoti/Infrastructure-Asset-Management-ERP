<style>
    /* Header base */
    .header {
        width: 100%;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 10000;
    }

    /* Navbar container */
    .navbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 20px;
    }

    /* Logo section */
    .logo-section {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo-img {
        width: 130px;
        height: 50px;
        object-fit: contain;
    }
    .logo-img-1 {
        width: 150px;
        height: 70px;
        object-fit: contain;
    }

    .logo-text {
        font-size: 30px;
        font-weight: 700;
        color: #333;
        text-decoration: underline;

    }

    .logo-text span {
        color: #667eea;
        /* Accent color */
    }

    /* Navigation links */
    .nav-links {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .nav-links li {
        position: relative;
    }

    .nav-links a {
        text-decoration: none;
        color: #333;
        padding: 8px 12px;
        display: block;
    }

    /* Dropdown */
    .dropdown-content {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #fff;
        min-width: 180px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .dropdown-content li {
        width: 100%;
    }

    .dropdown-content a {
        padding: 10px;
        color: #333;
        display: block;
        white-space: nowrap;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* Hover effect */
    .nav-links a:hover {
        background-color: #f2f2f2;
    }

    /* User pill */
    .user-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #edf2f7;
        color: #2d3748;
        font-weight: 600;
    }

    .user-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 14px;
    }
</style>


<header class="header">
    <nav class="navbar">
        <div class="logo-section">
            
            <a href="dashboard.php" class="logo-text">Site<span>Track</span></a>
        </div>

        <ul class="nav-links">
            <li><a href="dashboard.php">Dashboard</a></li>

            <?php if ($_SESSION['role'] == 'admin') { ?>
                <li class="dropdown">
                    <a href="#">Settings ▾</a>
                    <ul class="dropdown-content">
                        <li><a href="create_site.php">Add New Site</a></li>
                        <li><a href="add_item_master.php">Items Master</a></li>
                        <li><a href="add_lcs_item.php">LCS Items Master</a></li>
                        <li><a href="users.php">Admin/User</a></li>
                    </ul>
                </li>
            <?php } ?>

            <li class="dropdown">
                <a href="#">Reports ▾</a>
                <ul class="dropdown-content">
                    <li><a href="site_report.php">Site Report</a></li>
                    <li><a href="lcs_site_report.php">LCS Site Report</a></li>
                    <li><a href="user_wise_report.php">User-wise Report</a></li>
                    <li><a href="date_change_report.php">Date-wise Report</a></li>
                </ul>
            </li>

            <li><a href="logout.php">Logout</a></li>

            <li style="margin-left:auto;">
                <?php
                $display_name = isset($_SESSION['full_name']) && $_SESSION['full_name'] !== ''
                    ? $_SESSION['full_name']
                    : (isset($_SESSION['username']) ? $_SESSION['username'] : 'User');
                $initial = strtoupper(substr(trim($display_name), 0, 1));
                ?>
                <span class="user-pill">
                    <span class="user-avatar"><?php echo htmlspecialchars($initial); ?></span>
                    <?php echo htmlspecialchars($display_name); ?>
                </span>
            </li>
        </ul>
    </nav>
</header>