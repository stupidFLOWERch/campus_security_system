<?php
// Database connection details
$servername = "localhost";
$username = "root";   // Update with your MySQL username
$password = "";       // Update with your MySQL password
$dbname = "campus_security_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for filters
$licensePlateFilter = isset($_GET['license_plate']) ? $_GET['license_plate'] : '';
$dateRangeFilter = isset($_GET['date_range']) ? $_GET['date_range'] : '';

// Get the oldest recorded timestamp from the database
$oldestDateQuery = "SELECT MIN(timestamp) as oldest_date FROM vehicle_records";
$oldestDateResult = $conn->query($oldestDateQuery);
$oldestDateRow = $oldestDateResult->fetch_assoc();

if (!empty($dateRangeFilter)) {
    $dates = explode(' - ', $dateRangeFilter);
    if (count($dates) === 2) {
        $startDate = $dates[0];
        $endDate = $dates[1];
    }
}
else {
    // Default to oldest recorded date -> current date
    $startDate = $oldestDateRow['oldest_date'] ?? date('Y-m-d'); // Default to today if empty
    $endDate = date('Y-m-d');
}

// Build the query with filters
$sql = "SELECT id, license_plate, timestamp, video, image FROM vehicle_records WHERE 1=1";
if (!empty($licensePlateFilter)) {
    $sql .= " AND license_plate LIKE '" . $conn->real_escape_string($licensePlateFilter) . "%'";
}
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND timestamp BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Records - Campus Security System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
        }
        form {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        form input, form button {
            margin: 0 10px;
            padding: 10px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        td img {
            width: 100px;
            height: 100px;
        }
        td video {
            width: 150px;
        }
        /* Keeps the image size fixed in the table */
        .clickable-image {
            width: 100px;  /* Thumbnail size */
            height: auto;
            cursor: pointer;
            transition: 0.3s;
            object-fit: cover;
            border-radius: 5px;
        }
        .fullscreen-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .fullscreen-modal img {
            width: auto;
            height: auto;
            max-width: 95vw;  /* Ensure it takes almost full screen */
            max-height: 95vh;
            object-fit: cover; /* Fill the screen */
            border-radius: 8px;
            transform: scale(4); /* Enlarge */
            transition: transform 0.3s ease-in-out;
        }
        .topnav {
            background-color: #e9e9e9;
            padding: 10px;
            overflow: hidden;
        }
        .topnav-container {
            display: flex;
            align-items: center;
            justify-content: space-between; /* Keeps them on opposite ends */
            width: 100%;
            height: 50px; /* Set a consistent height */
        }
        /* Aligns the search field to the right */
        .search-container {
            margin-left: auto; /* Pushes the search container to the right */
            margin-top: 8px;
        }
        .search-container input[type=text] {
            padding: 8px;
            margin-top: 8px;
            font-size: 17px;
            border: none;
            border-radius: 5px;
        }
        /* Hover effect for input fields (search and datepicker) */
        .search-container input[type="text"]:hover,
        .search-container input[type="text"]:focus {
            border: 1px;
            transform: scale(1.05); /* Slightly enlarges the input */
        }
        .search-container button {
            height: 40px;
            width: 100px;
            padding: 10px;
            margin-top: 8px;
            margin-right: 16px;
            background-color: #2196F3;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
        }
        .back-button:hover ,
        .search-container button:hover {
            background-color: #0b7dda;
            transform: scale(1.1); /* Slightly enlarges the button */
        }
        .back-button {
            height: 40px;
            width: 100px;
            padding: 10px;      
            margin-left: 16px;
            background-color: #2196F3;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h1>Vehicle Access History</h1>

<div class="topnav">
    <div class="topnav-container">
        <button class="back-button" onclick="history.back()">Back</button>
        <div class="search-container">
            <form action="access_history.php">
                <!-- Date Picker Input -->
                <input type="text" id="dateRange" name="date_range" placeholder="Select Date Range"
                    value="<?php echo isset($dateRangeFilter) && !empty($dateRangeFilter) ? htmlspecialchars($dateRangeFilter) : "$startDate - $endDate"; ?>">

                <!-- License Plate Input -->
                <input type="text" placeholder="License Plate" name="license_plate">
                <!-- Search Button -->
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
</div>

<?php if ($result->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>License Plate</th>
            <th>Timestamp</th>
            <th>Image</th>
            <th>Video</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row["id"]; ?></td>
                <td><?php echo $row["license_plate"]; ?></td>
                <td><?php echo $row["timestamp"]; ?></td>

                <!-- Display Image -->
                <td>
                    <?php if ($row["image"]): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>" 
                            alt="Vehicle Image" class="clickable-image"
                            onclick="openFullscreen(this)">
                    <?php else: ?>
                        No Image Available
                    <?php endif; ?>
                </td>

                <!-- Display Video -->
                <td>
                    <?php if ($row["video"]): ?>
                        <video controls>
                            <source src="data:video/mp4;base64,<?php echo base64_encode($row['video']); ?>" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    <?php else: ?>
                        No Video Available
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p>No records found.</p>
<?php endif; ?>

<?php
// Close connection
$conn->close();
?>

<script src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(function() {
        $('#dateRange').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD'
            },
            maxDate: moment().format('YYYY-MM-DD'), // Disables future dates
            opens: 'center' // Calendar opens centered on the screen
        });
    });
    
    // Open Image in Fullscreen
    function openFullscreen(imageElement) {
        let modal = document.getElementById("fullscreenModal");
        let modalImg = document.getElementById("fullscreenImage");
        modal.style.display = "flex";
        modalImg.src = imageElement.src;  // Directly use the clicked image's source
    }

    // Close Fullscreen
    function closeFullscreen() {
        document.getElementById("fullscreenModal").style.display = "none";
    }
    
</script>

<!-- Fullscreen Image Modal -->
<div id="fullscreenModal" class="fullscreen-modal" onclick="closeFullscreen()">
    <img id="fullscreenImage" src="">
</div>

</body>
</html>
