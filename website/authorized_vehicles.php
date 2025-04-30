<?php
// Database connection settings
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "campus_security_system"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize filter variables
$searchField = isset($_GET['search_field']) ? $_GET['search_field'] : '';
$searchQuery = isset($_GET['search_query']) ? $_GET['search_query'] : '';

// Build the query based on filter
$sql = "SELECT id, owner_name, student_id, license_plate, car_brand_model, car_colour, permit_type, phone_number, owner_email FROM vehicle_registered_details WHERE 1=1";
if (!empty($searchField) && $searchField !== 'all' && !empty($searchQuery)) {
    $searchFieldEscaped = $conn->real_escape_string($searchField);
    $searchQueryEscaped = $conn->real_escape_string($searchQuery);
    $sql .= " AND $searchFieldEscaped LIKE '$searchQueryEscaped%'";
}

$result = $conn->query($sql);
?>
<?php
// [Previous PHP database connection code remains exactly the same]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Vehicles - Campus Security</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        
        .form-control, .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid var(--light-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
            font-size: 18px;
            background: white;
            border-radius: 10px;
        }
        
        .form-control:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
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
    
    <h1 class="page-header">
        <i class="bi bi-car-front"></i> Registered Vehicle Records
    </h1>
    
    <!-- Search Form -->
    <div class="search-container">
        <form method="GET" action="" class="search-form">
            <div class="form-group">
                <label class="form-label">Search Field</label>
                <select name="search_field" class="form-select" id="searchField">
                    <option value="all" <?= $searchField === 'all' || $searchField === '' ? 'selected' : '' ?>>Display All</option>
                    <option value="owner_name" <?= $searchField === 'owner_name' ? 'selected' : '' ?>>Owner Name</option>
                    <option value="student_id" <?= $searchField === 'student_id' ? 'selected' : '' ?>>Student ID</option>
                    <option value="license_plate" <?= $searchField === 'license_plate' ? 'selected' : '' ?>>License Plate</option>
                    <option value="phone_number" <?= $searchField === 'phone_number' ? 'selected' : '' ?>>Phone Number</option>
                    <option value="owner_email" <?= $searchField === 'owner_email' ? 'selected' : '' ?>>Owner Email</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Search Query</label>
                <input type="text" name="search_query" class="form-control"
                       placeholder="Enter search query"
                       value="<?= htmlspecialchars($searchQuery) ?>"
                       id="searchQuery"
                       <?= $searchField === 'all' || $searchField === '' ? 'disabled' : '' ?>>
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
                        <th>Owner Name</th>
                        <th>Student ID</th>
                        <th>License Plate</th>
                        <th>Vehicle Model</th>
                        <th>Color</th>
                        <th>Permit Type</th>
                        <th>Phone</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row["id"] ?></td>
                            <td><?= strtoupper($row["owner_name"]) ?></td>
                            <td><?= $row["student_id"] ?></td>
                            <td><?= $row["license_plate"] ?></td>
                            <td><?= $row["car_brand_model"] ?></td>
                            <td><?= $row["car_colour"] ?></td>
                            <td><?= $row["permit_type"] ?></td>
                            <td><?= $row["phone_number"] ?></td>
                            <td><?= $row["owner_email"] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-data">
            <i class="bi bi-exclamation-circle" style="font-size: 40px;"></i>
            <p>No vehicle records found for the selected criteria</p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Enable/disable search input based on dropdown selection
    document.getElementById('searchField').addEventListener('change', function() {
        const searchQuery = document.getElementById('searchQuery');
        if (this.value === 'all' || this.value === '') {
            searchQuery.disabled = true;
            searchQuery.value = '';
        } else {
            searchQuery.disabled = false;
        }
    });
</script>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>