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
    $stmt = $conn->prepare("DELETE FROM security_personnel WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Set the success message if deletion is successful
        $message = "✅ Security personnel record deleted successfully!";
    } else {
        // Set the error message if deletion fails
        $message = "❌ Error deleting record. Please try again.";
    }

    $stmt->close();
} else {
    // Set the error message if ID is not valid or not set
    $message = "❌ Invalid ID for deletion.";
}

$conn->close();

// Show the notification message and redirect back to the operations page
echo "<script>
    alert('$message');
    window.location.href = 'operation.php'; // Redirect back to the operations page
</script>";
?>
