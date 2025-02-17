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

$message = ''; // For holding the success or error message

// Check if ID is set in the URL and is a valid integer
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the record to edit
    $stmt = $conn->prepare("SELECT * FROM vehicle_registered_details WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
} else {
    die("Invalid request.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ensure ID is included in POST
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        die("Invalid request.");
    }
    $id = $_POST['id'];

    // Get updated values from form
    $owner_name = $_POST['owner_name'];
    $vehicle_number = $_POST['vehicle_number'];
    $vehicle_type = $_POST['vehicle_type'];
    $vehicle_colour = $_POST['vehicle_colour'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];
    $permit_type = $_POST['permit_type'];

    // Update query
    $update_stmt = $conn->prepare("UPDATE vehicle_registered_details 
        SET owner_name = ?, license_plate = ?, car_brand_model = ?, car_colour = ?, phone_number = ?, owner_email = ?, permit_type = ? 
        WHERE id = ?");
    $update_stmt->bind_param("sssssssi", $owner_name, $vehicle_number, $vehicle_type, $vehicle_colour, $phone_number, $email, $permit_type, $id);

    if ($update_stmt->execute()) {
        $message = "✅ Vehicle record updated successfully!";
        echo "<script>alert('$message'); window.location.href = 'operation.php';</script>";
        exit();
    } else {
        $message = "❌ Error updating vehicle record. Please try again.";
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
    <title>Edit Vehicle Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Vehicle Details</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="id" value="<?= $id ?>">

                <!-- Vehicle Details Section -->
                <div class="mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">Vehicle Details</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="fw-bold">Owner Name</label>
                                <input type="text" name="owner_name" class="form-control" value="<?= $row['owner_name'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Vehicle Number</label>
                                <input type="text" name="vehicle_number" class="form-control" value="<?= $row['license_plate'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Vehicle Model</label>
                                <input type="text" name="vehicle_type" class="form-control" value="<?= $row['car_brand_model'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Vehicle Colour</label>
                                <input type="text" name="vehicle_colour" class="form-control" value="<?= $row['car_colour'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" value="<?= $row['phone_number'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= $row['owner_email'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Permit Type</label>
                                <select name="permit_type" class="form-control" required>
                                    <?php
                                    $permit_options = ["White Permit", "Red Permit", "Yellow Permit", "Temporary Parking Pass", "Blue Permit"];
                                    foreach ($permit_options as $permit_name) {
                                        $selected = ($permit_name === $row['permit_type']) ? 'selected' : '';
                                        echo "<option value='$permit_name' $selected>$permit_name</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Update</button>
                <a href="operation.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
