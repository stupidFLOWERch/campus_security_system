<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "campus_security_system";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Fetch user from database
    $stmt = $conn->prepare("SELECT id, username, password, name, role, last_accessed_time FROM security_personnel WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $username, $stored_password, $name, $role, $last_accessed_time);
        $stmt->fetch();

        // Verify password
        if ($password == $stored_password) {
            $_SESSION["user_id"] = $id;
            $_SESSION["username"] = $username;
            $_SESSION["role"] = $role;
            
            // Update access_time in database
            $update_stmt = $conn->prepare("UPDATE security_personnel SET last_accessed_time = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $id);
            $update_stmt->execute();
            $update_stmt->close();

            // Redirect based on role
            if ($role === "ADMIN") {
                header("Location: admin_main.php");
            } else {
                header("Location: main.php");
            }
            exit();
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "User not found!";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        h2 {
            margin-top: 20px; /* Adjusts the space above the heading */
            font-style: italic;
            font-size: 40px; /* Makes the text italic */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px; /* Adds space between the icon and text */
        }
        h2 img {
            width: 50px;
            height: 50px;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 50px;
        }
        .login-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            height: 400px;
            margin: auto;
            margin-top: 30px;
        }
        input[type="text"], input[type="password"]{
            width: 60%;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        button {
            width: 50%;
            padding: 10px;
            margin-top: 20px;
            border-radius: 25px;
            border: 1px solid #ccc;
            background: #007bff;
            color: white;
            font-size: 20px;
            cursor: pointer;
            
        }
        button:hover {
            background: #0056b3;
        }
        .error {
            color: red;
            font-size: 25px;
        }
        .input-container {
            position: relative;
            width: 80%;
            margin: 10px auto;
            display: flex;
            align-items: center;
        }

        .input-container img {
            position: absolute;
            left: 10px; /* Adjust icon position */
            width: 25px; /* Adjust icon size */
            height: 25px;
        }

        .input-container input {
            width: 100%;
            padding: 12px 40px; /* Prevent overlap with icon */
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 15px;
        }


    </style>
</head>
<body>
<div class="login-container">
    <h2>
        <img src="login-icon.png" alt="Login Icon" width="30" height="30" style="vertical-align: middle; margin-right: 10px;">
        Login
    </h2>
    <form action="login.php" method="post">
        <div class="input-container">
            <img src="user.png" alt="User Icon">
            <input type="text" name="username" placeholder="Username" required>
        </div>
        <div class="input-container">
            <img src="lock.png" alt="Lock Icon">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit">LOGIN</button>
    </form>


    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
</div>
</body>
</html>
