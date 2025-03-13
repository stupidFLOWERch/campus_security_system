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

    // Prepare and execute the DELETE query
    $stmt = $conn->prepare("DELETE FROM vehicle_registered_details WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Set the success message if deletion is successful
        $message = "✅ Vehicle deleted successfully!";
    } else {
        // Set the error message if deletion fails
        $message = "❌ Error deleting vehicle. Please try again.";
    }

    $stmt->close();
} else {
    // Set the error message if ID is not valid or not set
    $message = "❌ Invalid vehicle ID.";
}

$conn->close();

// Show the notification message
echo "<script>
    alert('$message');
    window.location.href = 'operation.php'; // Redirect back to the dashboard
</script>";
?>
