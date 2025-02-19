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
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch the record to edit
    $stmt = $conn->prepare("SELECT * FROM security_personnel WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
}

// Fetch the roles dynamically
$roles_query = "SELECT DISTINCT role FROM security_personnel"; 
$roles_result = $conn->query($roles_query);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update the record if the form is submitted
    $username = $_POST['username'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $reenter_password = $_POST['reenter_password'];

    if ($password !== $reenter_password) {
        $message = "❌ Passwords do not match!";
    } else {
        // Update query
        $update_stmt = $conn->prepare("UPDATE security_personnel SET username = ?, name = ?, role = ?, password = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $username, $name, $role, $password, $id);

        if ($update_stmt->execute()) {
            $message = "✅ Record updated successfully!";
        } else {
            $message = "❌ Error updating record. Please try again.";
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
    <title>Edit Security Personnel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function validateForm() {
            let password = document.getElementById("password").value;
            let reenterPassword = document.getElementById("reenter_password").value;

            if (password !== reenterPassword) {
                alert("❌ Passwords do not match!");
                return false;
            }
            return true;
        }

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
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Security Personnel</h2>

    <div class="card">
        <div class="card-body">
            <!-- Show Success/Error message -->
            <?php if ($message): ?>
                <script>
                    alert("<?= $message ?>");
                    <?php if ($message === "✅ Record updated successfully!") : ?>
                        window.location.href = 'operation.php'; // Redirect only if successful
                    <?php endif; ?>
                </script>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm()">
                <div class="mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">Security Personnel Details</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="fw-bold">Username</label>
                                <input type="text" name="username" class="form-control" value="<?= $row['username'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= $row['name'] ?>" required pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed">
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Role</label>
                                <select name="role" class="form-control" required>
                                    <?php
                                    $role_options = ["ADMIN", "SECURITY", "STAFF"];
                                    foreach ($role_options as $role_name) {
                                        $selected = ($role_name === $row['role']) ? 'selected' : '';
                                        echo "<option value='$role_name' $selected>$role_name</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3 position-relative">
                                <label class="fw-bold">Password</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password" class="form-control" value="<?= $row['password'] ?>" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')">
                                        <i id="togglePasswordIcon" class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3 position-relative">
                                <label class="fw-bold">Re-enter Password</label>
                                <div class="input-group">
                                    <input type="password" id="reenter_password" name="reenter_password" class="form-control" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('reenter_password', 'toggleReenterPasswordIcon')">
                                        <i id="toggleReenterPasswordIcon" class="bi bi-eye"></i>
                                    </button>
                                </div>
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
