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

// Check if ID is set in the URL and is a valid integer
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // First get the vehicle details for confirmation
    $stmt = $conn->prepare("SELECT license_plate, owner_name FROM vehicle_registered_details WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $vehicle = $result->fetch_assoc();
        
        // If confirmation is received via POST
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirm_delete"])) {
            $deleteStmt = $conn->prepare("DELETE FROM vehicle_registered_details WHERE id = ?");
            $deleteStmt->bind_param("i", $id);
            
            if ($deleteStmt->execute()) {
                $_SESSION["notification"] = [
                    "message" => "Vehicle record deleted successfully!",
                    "success" => true,
                    "type" => "delete_vehicle" // Add a type identifier
                ];
                header("Location: operation.php?success=1");
                exit();
            } else {
                $message = "Error deleting vehicle record. Please try again.";
            }
            $deleteStmt->close();
        }
    } else {
        $message = "No vehicle found with that ID.";
    }
    $stmt->close();
} else {
    $message = "Invalid vehicle ID.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Vehicle - Campus Security</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .confirmation-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .confirmation-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
        }
        
        .warning-icon {
            font-size: 60px;
            color: var(--error-color);
            margin-bottom: 20px;
        }
        
        h2 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .vehicle-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .detail-label {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .detail-value {
            font-weight: 600;
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
        
        .btn-danger {
            background: linear-gradient(135deg, var(--error-color), #c0392b);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }
        
        .btn-secondary {
            background: var(--light-color);
            color: var(--primary-color);
        }
        
        .btn-secondary:hover {
            background: #dfe6e9;
            transform: translateY(-2px);
        }
        
        @media (max-width: 480px) {
            .confirmation-container {
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

<div class="confirmation-container">
    <div class="warning-icon">
        <i class="bi bi-exclamation-triangle-fill"></i>
    </div>
    
    <h2>Delete Vehicle Record</h2>
    
    <?php if (!empty($message) && !isset($vehicle)): ?>
        <p class="confirmation-text"><?php echo $message; ?></p>
        <div class="button-group">
            <a href="operation.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Operations
            </a>
        </div>
    <?php else: ?>
        <div class="vehicle-details">
            <div class="detail-item">
                <span class="detail-label">License Plate:</span>
                <span class="detail-value"><?php echo htmlspecialchars($vehicle['license_plate']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Owner Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($vehicle['owner_name']); ?></span>
            </div>
        </div>
        
        <p class="confirmation-text">
            Are you sure you want to permanently delete this vehicle record? 
            This action cannot be undone.
        </p>
        
        <form method="POST" class="button-group">
            <button type="submit" name="confirm_delete" class="btn btn-danger">
                <i class="bi bi-trash"></i> Confirm Delete
            </button>
            <a href="operation.php" class="btn btn-secondary">
                <i class="bi bi-x"></i> Cancel
            </a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>