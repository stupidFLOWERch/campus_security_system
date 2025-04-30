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

// Ensure the active tab is properly set
if (isset($_GET['active_tab'])) {
    $_SESSION["active_tab"] = $_GET['active_tab'];
} elseif (!isset($_SESSION["active_tab"])) {
    $_SESSION["active_tab"] = "registerVehicle"; // Default tab
}

$activeTab = $_SESSION["active_tab"];

// Handle vehicle addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_vehicle"])) {
    $plate_number = strtoupper(trim($_POST["plate_number"]));
    $owner_name = strtoupper(trim($_POST["owner_name"]));
    $student_id = strtoupper(trim($_POST["student_id"]));
    $car_brand_model = strtoupper(trim($_POST["car_brand_model"]));
    $car_colour = strtoupper(trim($_POST["car_colour"]));
    $permit_type = strtoupper(trim($_POST["permit_type"]));
    $phone_number = trim($_POST["phone_number"]);
    $owner_email = trim($_POST["owner_email"]);

    // Check for duplicates
    $stmt = $conn->prepare("SELECT * FROM vehicle_registered_details WHERE license_plate = ? OR owner_name = ? OR student_id = ? OR phone_number = ? OR owner_email = ?");
    $stmt->bind_param("sssss", $plate_number, $owner_name, $student_id, $phone_number, $owner_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $duplicate_fields = [];
        while ($row = $result->fetch_assoc()) {
            if (strcasecmp($row['license_plate'], $plate_number) === 0) $duplicate_fields[] = "Vehicle Plate Number";
            if (strcasecmp($row['owner_name'], $owner_name) === 0) $duplicate_fields[] = "Owner Name";
            if (strcasecmp($row['student_id'], $student_id) === 0) $duplicate_fields[] = "Student ID";
            if (strcasecmp($row['phone_number'], $phone_number) === 0) $duplicate_fields[] = "Phone Number";
            if (strcasecmp($row['owner_email'], $owner_email) === 0) $duplicate_fields[] = "Owner Email";
        }
        $error_message = "❌ Duplicate entry found: " . implode(", ", $duplicate_fields) . ". Each person may only apply one VPP sticker to one vehicle.";
        $_SESSION["active_tab"] = "registerVehicle";
    } else {
        // Insert the new vehicle data
        $stmt = $conn->prepare("INSERT INTO vehicle_registered_details (license_plate, owner_name, student_id, car_brand_model, car_colour, permit_type, phone_number, owner_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $plate_number, $owner_name, $student_id, $car_brand_model, $car_colour, $permit_type, $phone_number, $owner_email);

        if ($stmt->execute()) {
            $success_message = "Vehicle registered successfully!";
            $success = true;
            $_SESSION["notification"] = [
                "message" => $success_message,
                "success" => $success,
                "type" => "register_vehicle"
            ];
            unset($_POST); // Clear the form fields after successful submission
        } else {
            $error_message = "❌ Database error: Failed to save vehicle. Please try again.";
        }
    }
    $stmt->close();
}


// Handle security addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_security"])) {
    $username = trim($_POST["Username"]);
    $name = strtoupper(trim($_POST["name"]));
    $role = strtoupper(trim($_POST["role"]));
    $password = trim($_POST["password"]);

    // Check for duplicates (Username or Name already exists)
    $stmt = $conn->prepare("SELECT * FROM security_personnel WHERE username = ? OR name = ?");
    $stmt->bind_param("ss", $username, $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $duplicate_fields = [];
        while ($row = $result->fetch_assoc()) {
            if (strcasecmp($row['username'], $username) === 0) $duplicate_fields[] = "Username";
            if (strcasecmp($row['name'], $name) === 0) $duplicate_fields[] = "Name";
        }
        $error_message = "❌ Duplicate entry found: " . implode(", ", $duplicate_fields) . ". Please use a unique Username and Name.";
        $_SESSION["active_tab"] = "registerSecurity";
    } else {
        // Insert new security personnel data
        $stmt = $conn->prepare("INSERT INTO security_personnel (username, name, role, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $name, $role, $password);

        if ($stmt->execute()) {
            $success_message = "Security personnel registered successfully!";
            $success = true;
            $_SESSION["notification"] = [
                "message" => $success_message,
                "success" => $success,
                "type" => "register_security"
            ];
            unset($_POST); // Clear the form fields after successful submission
        } else {
            $error_message = "❌ Database error: Failed to register security personnel. Please try again.";
        }
        // if ($stmt->execute()) {
        //     $message = "Security personnel registered successfully!";
        //     $success = true;
            
        //     // Store notification in session
            // $_SESSION["notification"] = [
            //     "message" => $message,
            //     "success" => $success,
            //     "type" => "register_security"
            // ];
        //     unset($_POST); 
        // } else {
        //     $message = "Database error: Failed to register security personnel. Please try again.";
        // }
    }
    $stmt->close();
}

// Array of notification types to check
$notificationTypes = [
    'delete_vehicle',
    'delete_security',
    'edit_vehicle',
    'edit_security',
    'register_security',
    'register_vehicle'
    // Add more types here as needed
];

// Check each notification type
foreach ($notificationTypes as $type) {
    if (isset($_SESSION['notification']) && $_SESSION['notification']['type'] === $type) {
        $notification = $_SESSION['notification'];
        $alertClass = $notification['success'] ? 'alert-success' : 'alert-danger';
        $iconClass = $notification['success'] ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
        
        echo "<div class='alert $alertClass alert-dismissible fade show' style='position: fixed; top: 20px; right: 20px; z-index: 1000;'>
                <i class='bi $iconClass'></i> ".htmlspecialchars($notification['message'])."
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        
        // Clear the notification
        unset($_SESSION['notification']);
        break; // Exit loop after displaying one notification
    }
}

// Fetch registered vehicles
$vehicles = $conn->query("SELECT * FROM vehicle_registered_details");

// Fetch security personnel
$personnel = $conn->query("SELECT * FROM security_personnel");

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            min-height: 100vh;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        
        .page-header {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .nav-tabs {
            border-bottom: 2px solid var(--light-color);
        }
        
        .nav-tabs .nav-link {
            color: var(--primary-color);
            font-weight: 500;
            border: none;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--secondary-color);
            border: none;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--secondary-color);
            font-weight: 600;
            border-bottom: 3px solid var(--secondary-color);
            background: transparent;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
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
        }
        
        .form-control, .form-select {
            border: 2px solid var(--light-color);
            padding: 10px 15px;
            border-radius: 8px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn-submit {
            background: linear-gradient(90deg, var(--success-color), #1e8449);
            color: white;
            border: none;
            padding: 12px 25px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead {
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            color: white;
        }
        
        .table th {
            font-weight: 500;
        }
        
        .btn-action {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .btn-edit {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-delete {
            background: var(--error-color);
            color: white;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px 50px 15px 20px; /* More right padding for close button */
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            min-width: 300px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative; /* For absolute positioning of close button */
        }

        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        
        .password-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .nav-tabs .nav-link {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <!-- Back Button -->
    <a href="admin_main.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>
    
    <h2 class="page-header">Admin Management Panel</h2>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs" id="adminTabs">
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab == "registerVehicle") ? "active" : "" ?>" data-bs-toggle="tab" href="#registerVehicle">
                <i class="bi bi-car-front"></i> Register Vehicle
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab == "modifyVehicle") ? "active" : "" ?>" data-bs-toggle="tab" href="#modifyVehicle">
                <i class="bi bi-pencil-square"></i> Modify Vehicles
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab == "registerSecurity") ? "active" : "" ?>" data-bs-toggle="tab" href="#registerSecurity">
                <i class="bi bi-shield-lock"></i> Register Security
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab == "modifySecurity") ? "active" : "" ?>" data-bs-toggle="tab" href="#modifySecurity">
                <i class="bi bi-people"></i> Manage Security
            </a>
        </li>
    </ul>

    <div class="tab-content mt-4">
        <!-- Register New Vehicle -->
        <div id="registerVehicle" class="tab-pane fade <?= ($activeTab == "registerVehicle") ? "show active" : "" ?>">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <!-- <?php if (isset($success_message)): ?>
                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle-fill"></i> <?= $success_message ?>
                </div>
            <?php endif; ?> -->

            <form method="POST">
                <!-- Personal Details Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-person-badge"></i> Personal Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner Name</label>
                                <input type="text" name="owner_name" class="form-control" value="<?= isset($_POST['owner_name']) ? $_POST['owner_name'] : '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" name="student_id" class="form-control" pattern="[0-9]{5,15}" value="<?= isset($_POST['student_id']) ? $_POST['student_id'] : '' ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone_number" class="form-control" pattern="[0-9]{8,15}" value="<?= isset($_POST['phone_number']) ? $_POST['phone_number'] : '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner Email</label>
                                <input type="email" name="owner_email" class="form-control" value="<?= isset($_POST['owner_email']) ? $_POST['owner_email'] : '' ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Details Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-car-front"></i> Vehicle Details
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">License Plate Number</label>
                                <input type="text" name="plate_number" class="form-control" value="<?= isset($_POST['plate_number']) ? $_POST['plate_number'] : '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Car Brand & Model</label>
                                <input type="text" name="car_brand_model" class="form-control" value="<?= isset($_POST['car_brand_model']) ? $_POST['car_brand_model'] : '' ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Car Color</label>
                                <input type="text" name="car_colour" class="form-control" value="<?= isset($_POST['car_colour']) ? $_POST['car_colour'] : '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Permit Type</label>
                                <select name="permit_type" class="form-select" required>
                                    <option disabled selected value="">Select Permit Type</option>
                                    <option value="Yellow Permit" <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Yellow Permit' ? 'selected' : '' ?>>Yellow Permit</option>
                                    <option value="White Permit" <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'White Permit' ? 'selected' : '' ?>>White Permit</option>
                                    <option value="Red Permit" <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Red Permit' ? 'selected' : '' ?>>Red Permit</option>
                                    <option value="Temporary Parking Pass" <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Temporary Parking Pass' ? 'selected' : '' ?>>Temporary Parking Pass</option>
                                    <option value="Blue Permit" <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Blue Permit' ? 'selected' : '' ?>>Blue Permit</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="add_vehicle" class="btn btn-submit">
                    <i class="bi bi-save"></i> Register Vehicle
                </button>
            </form>
        </div>

        <!-- Modify Registered Vehicle -->
        <div id="modifyVehicle" class="tab-pane fade <?= ($activeTab == "modifyVehicle") ? "show active" : "" ?>">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-list-ul"></i> Registered Vehicles
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Owner</th>
                                    <th>License Plate</th>
                                    <th>Student ID</th>
                                    <th>Vehicle</th>
                                    <th>Color</th>
                                    <th>Permit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $vehicles->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= $row['owner_name'] ?></td>
                                    <td><?= $row['license_plate'] ?></td>
                                    <td><?= $row['student_id'] ?></td>
                                    <td><?= $row['car_brand_model'] ?></td>
                                    <td><?= $row['car_colour'] ?></td>
                                    <td><?= $row['permit_type'] ?></td>
                                    <td>
                                        <a href="edit_vehicle.php?id=<?= $row['id'] ?>&active_tab=modifyVehicle" 
                                           class="btn btn-edit btn-action">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete_vehicle.php?id=<?= $row['id'] ?>&active_tab=modifyVehicle"
                                           class="btn btn-delete btn-action"
                                           onclick="return confirm('⚠️ Are you sure you want to delete this vehicle?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Register New Security -->
        <div id="registerSecurity" class="tab-pane fade <?= ($activeTab == "registerSecurity") ? "show active" : "" ?>">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger mb-4">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>
            
            <!-- <?php if (isset($success_message)): ?>
                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle-fill"></i> <?= $success_message ?>
                </div>
            <?php endif; ?> -->

            <form method="POST" onsubmit="return validatePassword()">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-shield-lock"></i> Security Personnel Registration
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="Username" class="form-control" value="<?= isset($_POST['Username']) ? $_POST['Username'] : '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= isset($_POST['name']) ? $_POST['name'] : '' ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="ADMIN">ADMIN</option>
                                    <option value="SECURITY">SECURITY</option>
                                    <option value="STAFF">STAFF</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control" required>
                                    <button type="button" class="btn btn-outline-secondary password-toggle" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')">
                                        <i id="togglePasswordIcon" class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                    <button type="button" class="btn btn-outline-secondary password-toggle" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPasswordIcon')">
                                        <i id="toggleConfirmPasswordIcon" class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small id="password_error" class="text-danger"></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="add_security" class="btn btn-submit">
                    <i class="bi bi-save"></i> Register Security
                </button>
            </form>
        </div>

        <!-- Modify Security Personnel -->
        <div id="modifySecurity" class="tab-pane fade <?= ($activeTab == "modifySecurity") ? "show active" : "" ?>">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-people"></i> Security Personnel
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $personnel->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row["id"] ?></td>
                                    <td><?= $row["username"] ?></td>
                                    <td><?= $row["name"] ?></td>
                                    <td><?= $row["role"] ?></td>
                                    <td><?= $row["last_accessed_time"] ?></td>
                                    <td>
                                        <a href="edit_security.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-edit btn-action">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="delete_security.php?id=<?= $row['id'] ?>"
                                           class="btn btn-delete btn-action"
                                           onclick="return confirm('⚠️ Are you sure you want to delete this security personnel?');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // [Keep all your existing JavaScript functions]
    document.addEventListener("DOMContentLoaded", function () {
        // Activate the correct tab based on session
        let activeTab = "<?= $activeTab ?>";
        if (activeTab) {
            let tabElement = document.querySelector(`a[href="#${activeTab}"]`);
            if (tabElement) {
                new bootstrap.Tab(tabElement).show();
            }
        }
    });

    function validatePassword() {
        let password = document.getElementById('password').value;
        let confirmPassword = document.getElementById('confirm_password').value;
        let errorElement = document.getElementById('password_error');

        if (password !== confirmPassword) {
            errorElement.innerHTML = '<i class="bi bi-exclamation-circle"></i> Passwords do not match!';
            return false;
        } else {
            errorElement.textContent = '';
            return true;
        }
    }

    function togglePasswordVisibility(fieldId, iconId) {
        let field = document.getElementById(fieldId);
        let icon = document.getElementById(iconId);
        
        if (field.type === "password") {
            field.type = "text";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        } else {
            field.type = "password";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        }
    }

    // Auto-hide success message after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            alert.classList.add('fade');
        }
    }, 5000);
</script>
</body>
</html>