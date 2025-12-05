<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_config.php'; // includes sanitizeInput already

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = sanitizeInput($_POST['username']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // prepare statement: fetch the hash by username
    $stmt = $conn->prepare("SELECT id, username, full_name, role, password, access_type FROM users WHERE username = ?");
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        echo "<script>alert('Server error, please try again later.');</script>";
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // verify hashed password
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['access_type'] = $user['access_type'];
            $_SESSION['role'] = $user['role']; // Correctly setting role
            $stmt->close();
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid Username or Password');</script>";
        }
    } else {
        echo "<script>alert('Invalid Username or Password');</script>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login - Site Asset Management</title>
    <link rel="stylesheet" href="style.css">
    <!-- <link rel="icon" href="img/logo.png" type="image/x-icon"> -->
    <style>
        body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    padding: 0;
    background-image: url("img/img2.jpg");
    background-repeat: no-repeat;
    background-position: center center;
    background-size: contain; /* image poori dikhegi, zoom/crop nahi hoga */
    background-attachment: fixed;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    overflow: hidden;
    background-size: cover;
background-position: center;
background-repeat: no-repeat;
background-attachment: fixed;

}


        .login-container {
            position: relative;
            padding: 40px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: zoomIn 0.8s ease-in-out;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            border-radius: 15px;
            background: linear-gradient(270deg, #ff007f, #ff7700, #ffeb00, #16d9e3, #ff007f);
            z-index: -1;
            background-size: 400% 400%;
            animation: borderAnimation 5s infinite;
        }

        @keyframes borderAnimation {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        h2 {
            color: #333;
            text-align: center;
            font-size: 2rem;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            color: #444;
            display: block;
            margin-bottom: 5px;
        }

        input {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: #f0f0f0;
            box-shadow: inset 1px 3px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            font-size: 16px;
        }

        input:focus {
            background: #e8e8e8;
            outline: none;
            box-shadow: 0 0 10px rgba(255, 120, 120, 0.6);
        }

        button {
            width: 100%;
            padding: 12px;
            /* background: linear-gradient(to right, #ff758c, #ff5460); */
            /* background-image: linear-gradient(to right, #b8cbb8 0%, #b8cbb8 0%, #b465da 0%, #cf6cc9 33%, #ee609c 66%, #ee609c 100%); */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            /* background: linear-gradient(to right, #ff5460, #ff758c); */
            background-image: linear-gradient(to right, #b8cbb8 0%, #b8cbb8 0%, #b465da 0%, #cf6cc9 33%, #ee609c 66%, #ee609c 100%);
        }

        .error {
            color: #ff5460;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.9rem;
        }

        footer {
            position: absolute;
            bottom: 10px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            width: 100%;
        }
        .navbar-brand img {
            height: 60px;
            width: auto;
        }
        .navbar-brand-1 img {
            height: 80px;
            width: 170px;
        }
    </style>
</head>

<body>
    <div class="login-container">
    <div class="login-logo" style="text-align: center;">
                    <a href="index.php">
                        
                    </a>
                </div>
        <h2>SiteTrack Management System</h2>



        <form method="POST" action="">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" placeholder="Enter Username" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" placeholder="Enter Password" required>
            </div>

            <button type="submit">Login</button>
        </form>
    </div>
    <footer>
        <!-- © 2025 Job Route System. All rights reserved. -->
        <h2 style="font-size:15px; margin-top:-0px; color:white; display:<?php echo $_DISPLAY_FLAG ?>;">
            Copyright &copy; <?php echo (date('Y') - 1) . '-' . date('Y'); ?>
            <a href="#" target="_blank" style="color:#fff; text-decoration:none; font-weight:;">
                Infrastructure Asset Management ERP Module
            </a>
        </h2>
    </footer>
</body>

</html>
