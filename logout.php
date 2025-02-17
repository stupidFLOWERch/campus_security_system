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
    if ($_SESSION["role"] === "admin") {
        header("Location: admin_main.php"); // Redirect admin users
    } else {
        header("Location: main.php"); // Redirect regular users
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
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f4f4f4;
        }
        .logout-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            margin: auto;
            border-radius: 25px;
        }
        h2 {
            margin-bottom: 15px;
        }
        button {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            border-radius: 25px;
        }
        .confirm-btn {
            background-color: #d9534f;
            color: white;
        }
        .confirm-btn:hover {
            background-color: #c9302c;
        }
        .cancel-btn {
            background-color: #5bc0de;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #31b0d5;
        }
    </style>
</head>
<body>

<div class="logout-box">
    <h2>Logout Confirmation</h2>
    <p>User <strong><?php echo htmlspecialchars($username); ?></strong>, are you sure you want to logout?</p>
    
    <form method="post">
        <button type="submit" name="confirm_logout" class="confirm-btn">Yes, Logout</button>
        <button type="submit" name="cancel_logout" class="cancel-btn">Cancel</button>
    </form>
</div>

</body>
</html>
