<?php
$host = "localhost";
$dbname = "campus_security_system";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prevent caching
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (isset($_GET['license_number'])) {
        $license_number = $_GET['license_number'];

        $stmt = $conn->prepare("
            SELECT license_plate_crop 
            FROM detections 
            WHERE license_number = :license_number 
            ORDER BY license_number_score DESC 
            LIMIT 1
        ");
        $stmt->execute(['license_number' => $license_number]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['license_plate_crop'])) {
            header("Content-Type: image/jpeg");
            echo $row['license_plate_crop']; // directly echo the BLOB
            exit;
        }
    }

    // If not found, return default image
    header("Content-Type: image/png");
    readfile("no_plate.png");
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " . $e->getMessage();
}
?>
