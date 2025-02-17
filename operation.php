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

if (!isset($_SESSION["active_tab"])) {
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
            $success_message = "✅ Vehicle registered successfully!";
            unset($_POST); // Clear the form fields after successful submission
        } else {
            $error_message = "❌ Database error: Failed to save vehicle. Please try again.";
        }
    }
    $stmt->close();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center mb-4">Admin Dashboard</h2>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab == "registerVehicle") ? "active" : "" ?>" data-bs-toggle="tab" href="#registerVehicle">Register New Vehicle</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab == "modifyVehicle") ? "active" : "" ?>" data-bs-toggle="tab" href="#modifyVehicle">Modify Registered Vehicle</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activeTab == "modifySecurity") ? "active" : "" ?>" data-bs-toggle="tab" href="#modifySecurity">Modify Security Personnel</a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <!-- Register New Vehicle -->
        <div id="registerVehicle" class="tab-pane fade show active">
            <div class="card">
                <div class="card-body">
                    <?php if (isset($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>
                    <?php if (isset($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>

                    <form method="POST">
                        <input type="hidden" name="active_tab" id="active_tab" value="<?= $_SESSION["active_tab"] ?>">
                        <!-- Personal Details Section -->
                        <div class="mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">Personal Details</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="fw-bold">Owner Name</label>
                                        <input type="text" name="owner_name" class="form-control" value="<?= isset($_POST['owner_name']) ? $_POST['owner_name'] : '' ?>" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed">
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Student ID</label>
                                        <input type="text" name="student_id" class="form-control" value="<?= isset($_POST['student_id']) ? $_POST['student_id'] : '' ?>" required pattern="[0-9]+" title="Only numbers are allowed">
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Phone Number</label>
                                        <input type="text" name="phone_number" class="form-control" value="<?= isset($_POST['phone_number']) ? $_POST['phone_number'] : '' ?>" required pattern="[0-9]+" title="Only numbers are allowed">
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Owner Email</label>
                                        <input type="email" name="owner_email" class="form-control" value="<?= isset($_POST['owner_email']) ? $_POST['owner_email'] : '' ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Details Section -->
                        <div class="mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">Vehicle Details</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="fw-bold">Vehicle Plate Number</label>
                                        <input type="text" name="plate_number" class="form-control" value="<?= isset($_POST['plate_number']) ? $_POST['plate_number'] : '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Car Brand & Model</label>
                                        <input type="text" name="car_brand_model" class="form-control" value="<?= isset($_POST['car_brand_model']) ? $_POST['car_brand_model'] : '' ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Car Colour</label>
                                        <input type="text" name="car_colour" class="form-control" value="<?= isset($_POST['car_colour']) ? $_POST['car_colour'] : '' ?>" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed">
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Permit Type</label>
                                        <select name="permit_type" class="form-control" required>
                                            <option disabled selected value="">Select Permit Type</option>
                                            <option <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Yellow Permit' ? 'selected' : '' ?>>Yellow Permit</option>
                                            <option <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'White Permit' ? 'selected' : '' ?>>White Permit</option>
                                            <option <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Red Permit' ? 'selected' : '' ?>>Red Permit</option>
                                            <option <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Temporary Parking Pass' ? 'selected' : '' ?>>Temporary Parking Pass</option>
                                            <option <?= isset($_POST['permit_type']) && $_POST['permit_type'] == 'Blue Permit' ? 'selected' : '' ?>>Blue Permit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_vehicle" class="btn btn-success">Register Vehicle</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modify Registered Vehicle -->
        <div id="modifyVehicle" class="tab-pane fade">
            <h4>Registered Vehicles</h4>
            <table class="table table-bordered">
                <tr><th>ID</th><th>Plate Number</th><th>Owner Name</th><th>Student ID</th><th>Car Model</th><th>Car Colour</th><th>Permit Type</th><th>Phone Number</th><th>Email</th><th>Actions</th></tr>
                <?php while ($row = $vehicles->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['owner_name'] ?></td>
                        <td><?= $row['license_plate'] ?></td>
                        <td><?= $row['student_id'] ?></td>
                        <td><?= $row['car_brand_model'] ?></td>
                        <td><?= $row['car_colour'] ?></td>
                        <td><?= $row['permit_type'] ?></td>
                        <td><?= $row['phone_number'] ?></td>
                        <td><?= $row['owner_email'] ?></td>
                        <td>
                            <a href="edit_vehicle.php?id=<?= $row['id'] ?>" class="btn btn-warning">Edit</a>
                            <a href="delete_vehicle.php?id=<?= $row['id'] ?>"
                                class="btn btn-danger"
                                onclick="return confirm('⚠️ Are you sure you want to delete this record? This action cannot be undone!');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <!-- Modify Security Personnel -->
        <div id="modifySecurity" class="tab-pane fade">
            <h4>Security Personnel</h4>
            <table class="table table-bordered">
                <tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Last Accessed Time</th><th>Actions</th></tr>
                <?php while ($row = $personnel->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row["id"] ?></td>
                        <td><?= $row["username"] ?></td>
                        <td><?= $row["name"] ?></td>
                        <td><?= $row["role"] ?></td>
                        <td><?= $row["last_accessed_time"] ?></td>
                        <td>
                            <a href="edit_security.php?id=<?= $row['id'] ?>" class="btn btn-warning">Edit</a>
                            <a href="delete_security.php?id=<?= $row['id'] ?>"
                                class="btn btn-danger"
                                onclick="return confirm('⚠️ Are you sure you want to delete this record? This action cannot be undone!');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

    </div>
</div>
<script>
    function setActiveTab(tabName) {
        document.getElementById('active_tab').value = tabName;
        sessionStorage.setItem("activeTab", tabName);
    }

    document.addEventListener("DOMContentLoaded", function() {
        let activeTab = sessionStorage.getItem("activeTab") || "registerVehicle"; // Default tab
        let tabElement = document.querySelector(`a[href='#${activeTab}']`);
        if (tabElement) {
            new bootstrap.Tab(tabElement).show();
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
