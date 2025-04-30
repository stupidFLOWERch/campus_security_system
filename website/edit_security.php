<?php
session_start();

// Check if user is logged in and has admin privileges
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "ADMIN") {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "campus_security_system";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$success = false;

// Check if ID is set in the URL
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Fetch the record to edit
    $stmt = $conn->prepare("SELECT * FROM security_personnel WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "No security personnel found with that ID.";
        $_SESSION["notification"] = [
            "message" => $message,
            "success" => false
        ];
        header("Location: operation.php");
        exit();
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
} else {
    $message = "Invalid security personnel ID.";
    $_SESSION["notification"] = [
        "message" => $message,
        "success" => false
    ];
    header("Location: operation.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $reenter_password = $_POST['reenter_password'];

    if ($password !== $reenter_password) {
        $message = "Passwords do not match!";
    } else {
        // Update query
        $update_stmt = $conn->prepare("UPDATE security_personnel SET username = ?, name = ?, role = ?, password = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $username, $name, $role, $password, $id);

        if ($update_stmt->execute()) {
            $message = "Security personnel record updated successfully!";
            $success = true;
            
            // Store notification in session
            $_SESSION["notification"] = [
                "message" => $message,
                "success" => $success,
                "type" => "edit_security"
            ];
            header("Location: operation.php");
            exit();
        } else {
            $message = "Error updating record. Please try again.";
        }
        $update_stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Security Personnel - Campus Security</title>
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
            min-height: 100vh;
            padding: 20px;
        }
        
        .edit-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-header {
            color: var(--primary-color);
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .card-body {
            padding: 20px 20px;
        }

        .card-header {
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            color: white;
            font-weight: 500;
            border-radius: 10px 10px 0 0 !important;
            padding: 12px 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 8px;
            margin-top: 10px;
            display: block;
        }
        
        .form-control, .form-select {
            border: 2px solid var(--light-color);
            padding: 10px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            width: 100%; /* Add this line to make elements full width */
            box-sizing: border-box; /* Ensures padding doesn't affect width */
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
        }
        
        .btn-success {
            background: linear-gradient(90deg, var(--success-color), #1e8449);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .btn-secondary {
            background: var(--light-color);
            color: var(--primary-color);
        }
        
        .btn-secondary:hover {
            background: #dfe6e9;
            transform: translateY(-2px);
        }
        
        .input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--primary-color);
            z-index: 5;
        }
        
        .error-message {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .edit-container {
                padding: 0 15px;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
<div class="edit-container">
    <!-- Back Button -->
    <a href="operation.php" class="btn btn-secondary mb-4">
        <i class="bi bi-arrow-left"></i> Back to Operations
    </a>
    
    <h1 class="page-header">
        <i class="bi bi-person-gear"></i> Edit Security Personnel
    </h1>
    
    <?php if (!empty($message) && !$success): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <i class="bi bi-person-badge"></i> Personnel Details
        </div>
        <div class="card-body">
            <form method="POST" id="editForm">
                <div class="mb-4">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($row['username']); ?>" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($row['name']); ?>" 
                           required pattern="[A-Za-z\s]+" 
                           title="Only letters and spaces are allowed">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <?php
                        $role_options = ["ADMIN", "SECURITY", "STAFF"];
                        foreach ($role_options as $role_name) {
                            $selected = ($role_name === $row['role']) ? 'selected' : '';
                            echo "<option value='$role_name' $selected>$role_name</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" 
                               value="<?php echo htmlspecialchars($row['password']); ?>" required>
                        <i id="togglePasswordIcon" class="bi bi-eye password-toggle" 
                           onclick="togglePasswordVisibility('password', 'togglePasswordIcon')"></i>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Re-enter Password</label>
                    <div class="input-group">
                        <input type="password" id="reenter_password" name="reenter_password" 
                               class="form-control" required>
                        <i id="toggleReenterPasswordIcon" class="bi bi-eye password-toggle" 
                           onclick="togglePasswordVisibility('reenter_password', 'toggleReenterPasswordIcon')"></i>
                    </div>
                    <div id="passwordError" class="error-message"></div>
                </div>
                
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Update
                    </button>
                    <a href="operation.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility(id, iconId) {
        let passwordField = document.getElementById(id);
        let icon = document.getElementById(iconId);
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }
    
    document.getElementById('editForm').addEventListener('submit', function(e) {
        let password = document.getElementById("password").value;
        let reenterPassword = document.getElementById("reenter_password").value;
        let errorElement = document.getElementById("passwordError");
        
        if (password !== reenterPassword) {
            errorElement.textContent = "Passwords do not match!";
            e.preventDefault();
        } else {
            errorElement.textContent = "";
        }
    });
</script>
</body>
</html>