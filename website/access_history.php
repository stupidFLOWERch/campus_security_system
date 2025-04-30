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
$oldestDateQuery = "SELECT MIN(detection_time) as oldest_date FROM detections";
$oldestDateResult = $conn->query($oldestDateQuery);
$oldestDateRow = $oldestDateResult->fetch_assoc();

if (!empty($dateRangeFilter)) {
    $dates = explode(' - ', $dateRangeFilter);
    if (count($dates) === 2) {
        $startDate = $dates[0];
        $endDate = $dates[1];
        $startDate = $startDate . ' 00:00:00';
        $endDate = $endDate . ' 23:59:59';

    }
}
else {
    // Default to oldest recorded date -> current date
    $startDate = $oldestDateRow['oldest_date'] ?? date('Y-m-d'); // Default to today if empty
    $endDate = date('Y-m-d');
    $startDate = $startDate . ' 00:00:00';
    $endDate = $endDate . ' 23:59:59';
}

// Build the query with filters
$sql = "SELECT d1.*
        FROM detections d1
        INNER JOIN (
            SELECT car_id, MAX(license_number_score) AS max_conf
            FROM detections
            GROUP BY car_id
        ) d2 ON d1.car_id = d2.car_id AND d1.license_number_score = d2.max_conf
        WHERE 1=1";

if (!empty($licensePlateFilter)) {
    $sql .= " AND d1.license_number LIKE '" . $conn->real_escape_string($licensePlateFilter) . "%'";
}
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND d1.detection_time BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "'";
}

$sql .= " ORDER BY d1.detection_time DESC";

$result = $conn->query($sql);
?>
<?php
// [Previous PHP code remains exactly the same]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Access History - Campus Security</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Date Range Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
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
            padding: 20px;
        }
        
        .page-header {
            color: var(--primary-color);
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        
        .search-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--light-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn-back {
            background: var(--light-color);
            color: var(--primary-color);
        }
        
        .btn-back:hover {
            background: #dfe6e9;
            transform: translateY(-2px);
        }
        
        .results-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .results-table th {
            background: linear-gradient(90deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }
        
        .results-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-color);
        }
        
        .results-table tr:last-child td {
            border-bottom: none;
        }
        
        .results-table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .thumbnail {
            width: 150px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 18px;
        }
        
        .fullscreen-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-image {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .close-modal {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 30px;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
            }
            
            .results-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Back Button -->
    <a href="admin_main.php" class="btn btn-back">
        <i class="bi bi-arrow-left"></i> Back
    </a>
    </button>
    
    <h1 class="page-header">
        <i class="bi bi-clock-history"></i> Vehicle Access History
    </h1>
    
    <!-- Search Form -->
    <div class="search-container">
        <form action="access_history.php" method="GET" class="search-form">
            <div class="form-group">
                <label for="dateRange" class="form-label">Date Range</label>
                <input type="text" id="dateRange" name="date_range" class="form-control" 
                       placeholder="Select date range"
                       value="<?php echo isset($dateRangeFilter) && !empty($dateRangeFilter) ? htmlspecialchars($dateRangeFilter) : "$startDate - $endDate"; ?>">
            </div>
            
            <div class="form-group">
                <label for="license_plate" class="form-label">License Plate</label>
                <input type="text" id="license_plate" name="license_plate" class="form-control" 
                       placeholder="Enter license plate"
                       value="<?php echo htmlspecialchars($licensePlateFilter); ?>">
            </div>
            
            <div class="form-group" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
    </div>
    
    <!-- Results Table -->
    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>License Plate</th>
                        <th>Timestamp</th>
                        <th>Vehicle Image</th>
                        <th>Processed Plate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row["id"]; ?></td>
                            <td><?php echo $row["license_number"]; ?></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($row["detection_time"])); ?></td>
                            
                            <td>
                                <?php if ($row["license_plate_crop"]): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['license_plate_crop']); ?>"
                                        alt="Vehicle image" class="thumbnail"
                                        onclick="openFullscreen(this)">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if ($row["license_plate_crop_thresh"]): ?>
                                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['license_plate_crop_thresh']); ?>"
                                        alt="Processed plate" class="thumbnail"
                                        onclick="openFullscreen(this)">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-data">
            <i class="bi bi-exclamation-circle" style="font-size: 40px;"></i>
            <p>No access records found for the selected criteria</p>
        </div>
    <?php endif; ?>
</div>

<!-- Fullscreen Image Modal -->
<div id="fullscreenModal" class="fullscreen-modal" onclick="closeFullscreen()">
    <div class="modal-content">
        <span class="close-modal" onclick="closeFullscreen()">&times;</span>
        <img id="fullscreenImage" class="modal-image" src="">
    </div>
</div>

<script src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(function() {
        $('#dateRange').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD',
                applyLabel: 'Apply',
                cancelLabel: 'Cancel',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom',
                daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr','Sa'],
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                firstDay: 1
            },
            opens: 'center',
            drops: 'down',
            maxDate: moment(),
            autoUpdateInput: true,
            showDropdowns: true,
            alwaysShowCalendars: true
        });
    });
    
    // Open image in fullscreen modal
    function openFullscreen(imgElement) {
        const modal = document.getElementById('fullscreenModal');
        const modalImg = document.getElementById('fullscreenImage');
        
        modal.style.display = 'flex';
        modalImg.src = imgElement.src;
        
        // Prevent modal close when clicking on the image
        modalImg.onclick = function(e) {
            e.stopPropagation();
        };
    }
    
    // Close the fullscreen modal
    function closeFullscreen() {
        document.getElementById('fullscreenModal').style.display = 'none';
    }
    
    // Close modal when pressing ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeFullscreen();
        }
    });
</script>

</body>
</html>

<?php
// Close connection
$conn->close();
?>