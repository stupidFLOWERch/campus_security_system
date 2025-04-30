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
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Fetch the record to edit
    $stmt = $conn->prepare("SELECT * FROM vehicle_registered_details WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "No vehicle found with that ID.";
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
    $message = "Invalid vehicle ID.";
    $_SESSION["notification"] = [
        "message" => $message,
        "success" => false
    ];
    header("Location: operation.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $owner_name = trim($_POST['owner_name']);
    $vehicle_number = trim($_POST['vehicle_number']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_colour = trim($_POST['vehicle_colour']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $permit_type = $_POST['permit_type'];

    // Update query
    $update_stmt = $conn->prepare("UPDATE vehicle_registered_details 
        SET owner_name = ?, license_plate = ?, car_brand_model = ?, car_colour = ?, 
        phone_number = ?, owner_email = ?, permit_type = ? 
        WHERE id = ?");
    $update_stmt->bind_param("sssssssi", $owner_name, $vehicle_number, $vehicle_type, 
        $vehicle_colour, $phone_number, $email, $permit_type, $id);

    if ($update_stmt->execute()) {
        $message = "Vehicle record updated successfully!";
        $success = true;
        
        // Store notification in session
        $_SESSION["notification"] = [
            "message" => $message,
            "success" => $success,
            "type" => "edit_vehicle"
        ];
        header("Location: operation.php");
        exit();
    } else {
        $message = "Error updating vehicle record. Please try again.";
    }
    $update_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vehicle - Campus Security</title>
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
        <i class="bi bi-car-front"></i> Edit Vehicle Details
    </h1>
    
    <?php if (!empty($message) && !$success): ?>
        <div class="alert alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <i class="bi bi-card-checklist"></i> Vehicle Information
        </div>
        <div class="card-body">
            <form method="POST" id="editForm">
                <input type="hidden" name="id" value="<?= $id ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Owner Name</label>
                        <input type="text" name="owner_name" class="form-control"
                               value="<?= htmlspecialchars($row['owner_name']) ?>"
                               required pattern="[A-Za-z\s]+"
                               title="Only letters and spaces are allowed">
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">License Plate</label>
                        <input type="text" name="vehicle_number" class="form-control" 
                               value="<?= htmlspecialchars($row['license_plate']) ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Vehicle Model</label>
                        <input type="text" name="vehicle_type" class="form-control" 
                               value="<?= htmlspecialchars($row['car_brand_model']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Vehicle Color</label>
                        <input type="text" name="vehicle_colour" class="form-control"
                               value="<?= htmlspecialchars($row['car_colour']) ?>"
                               required pattern="[A-Za-z\s]+"
                               title="Only letters and spaces are allowed">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone_number" class="form-control" 
                               value="<?= htmlspecialchars($row['phone_number']) ?>" 
                               required pattern="[0-9]+" 
                               title="Only numbers are allowed">
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($row['owner_email']) ?>" required>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Permit Type</label>
                    <select name="permit_type" class="form-select" required>
                        <?php
                        $permit_options = ["White Permit", "Red Permit", "Yellow Permit", "Temporary Parking Pass", "Blue Permit"];
                        foreach ($permit_options as $permit_name) {
                            $selected = ($permit_name === $row['permit_type']) ? 'selected' : '';
                            echo "<option value='$permit_name' $selected>$permit_name</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Update Vehicle
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
    // Form validation can be added here if needed
    document.getElementById('editForm').addEventListener('submit', function(e) {
        // Add any additional client-side validation here
    });
</script>
</body>
</html>