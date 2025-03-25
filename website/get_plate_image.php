<?php
// Database connection
$host = "localhost";
$dbname = "campus_security_system";
$username = "root";
$password = "";
$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if car_id and frame_nmr are set
if (isset($_GET['car_id']) && isset($_GET['frame_nmr'])) {
    $car_id = $_GET['car_id'];
    $frame_nmr = $_GET['frame_nmr'];

    // Fetch the image from the database
    $stmt = $conn->prepare("SELECT image_blob FROM detection WHERE car_id = :car_id AND frame_nmr = :frame_nmr ORDER BY id DESC LIMIT 1");
    $stmt->execute(['car_id' => $car_id, 'frame_nmr' => $frame_nmr]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        header("Content-Type: image/jpeg");
        echo $row['image_blob'];
        exit;
    }
}

// If no image found, return a placeholder
header("Content-Type: image/png");
readfile("no_plate.png");
?>
