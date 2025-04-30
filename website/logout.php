<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Get username for the confirmation message
$username = $_SESSION["username"];

// If the user confirms logout
if (isset($_POST["confirm_logout"])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// If the user cancels logout, redirect back
if (isset($_POST["cancel_logout"])) {
    if (!empty($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']); // Redirect to the previous page
    } else {
        header("Location: main.php"); // Fallback if no referrer is available
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --error-color: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .logout-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .logout-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
        }
        
        .logout-icon {
            font-size: 60px;
            color: var(--accent-color);
            margin-bottom: 20px;
        }
        
        h2 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .username {
            color: var(--secondary-color);
            font-weight: 500;
        }
        
        .confirmation-text {
            color: #7f8c8d;
            margin: 25px 0;
            font-size: 16px;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, var(--accent-color), #c0392b);
            color: white;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-cancel {
            background: var(--light-color);
            color: var(--primary-color);
        }
        
        .btn-cancel:hover {
            background: #dfe6e9;
            transform: translateY(-2px);
        }
        
        @media (max-width: 480px) {
            .logout-container {
                padding: 30px 20px;
            }
            
            .button-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="logout-container">
    <div class="logout-icon">
        <i class="bi bi-box-arrow-right"></i>
    </div>
    
    <h2>Logout Confirmation</h2>
    
    <p class="confirmation-text">
        You are currently logged in as <span class="username"><?php echo htmlspecialchars($username); ?></span>.
        Are you sure you want to sign out?
    </p>
    
    <form method="post">
        <div class="button-group">
            <button type="submit" name="confirm_logout" class="btn btn-logout">
                <i class="bi bi-check-lg"></i> Yes, Logout
            </button>
            <button type="button" onclick="window.history.back();" class="btn btn-cancel">
                <i class="bi bi-x-lg"></i> Cancel
            </button>
        </div>
    </form>
</div>

</body>
</html>