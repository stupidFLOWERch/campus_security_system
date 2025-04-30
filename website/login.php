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
    <title>Campus Security System | Login</title>
    
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
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            width: 280px;
            height: 80px;
            margin-bottom: 15px;
        }
        
        h2 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .input-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 18px;
        }
        
        .input-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-container .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 18px;
            pointer-events: none;
        }
        
        .eye-toggle {
            position: absolute;
            right: 25px; /* Changed from 15px to 12px to move left */
            top: 50%;
            transform: translateY(-50%) translateY(-1px);
            cursor: pointer;
            font-size: 18px;
            color: #95a5a6;
            transition: all 0.3s ease;
            background: none;
            border: none;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            line-height: 1;
            z-index: 2; /* Ensure it stays above input */
        }

        /* Input field adjustment to prevent text under icon */
        .input-container input {
            width: 100%;
            padding: 15px 42px 15px 45px; /* Right padding reduced from 45px to 42px */
            border: 2px solid #dfe6e9;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        /* For the lock icon on the left */
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%) translateY(-1px); /* Matching adjustment */
            color: #95a5a6;
            font-size: 18px;
            pointer-events: none;
            margin: 0;
            line-height: 1;
        }
        
        
        
        button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .error {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 15px;
            text-align: center;
            padding: 10px;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--error-color);
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 13px;
            color: #95a5a6;
        }
        
        .footer a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .logo {
                width: 60px;
                height: 60px;
            }
            
            h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo-container">
        <img src="uni-logo.png" alt="Uni Logo" class="logo">
        <h2>Campus Security System</h2>
        <p class="subtitle">Secure access for authorized personnel only</p>
    </div>

    <form action="login.php" method="post">
        <!-- Username -->
        <div class="input-container">
            <i class="bi bi-person-fill"></i>
            <input type="text" name="username" placeholder="Username" required>
        </div>

        <!-- Password with Eye Icon -->
        <div class="input-container">
            <i class="bi bi-lock-fill input-icon"></i>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <button type="button" class="eye-toggle" id="togglePassword">
                <i class="bi bi-eye"></i>
            </button>
        </div>

        <button type="submit">SIGN IN</button>
    </form>

    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    
    <div class="footer">
        <p style="margin-top: 5px;">&copy; <?php echo date("Y"); ?> Campus Security System</p>
    </div>
</div>

<script>
    // Toggle Password Visibility - Final Fixed Version
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");
    
    togglePassword.addEventListener("click", function(e) {
        e.preventDefault();
        const icon = this.querySelector('i');
        
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            icon.classList.replace("bi-eye", "bi-eye-slash");
        } else {
            passwordInput.type = "password";
            icon.classList.replace("bi-eye-slash", "bi-eye");
        }
        
        // Maintain cursor position and focus
        const cursorPosition = passwordInput.selectionStart;
        passwordInput.focus();
        passwordInput.setSelectionRange(cursorPosition, cursorPosition);
    });
    
    // Add animation to input focus
    document.querySelectorAll('.input-container input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentNode.querySelector('i').style.color = '#3498db';
        });
        
        input.addEventListener('blur', function() {
            this.parentNode.querySelector('i').style.color = '#95a5a6';
        });
    });
</script>

</body>
</html>