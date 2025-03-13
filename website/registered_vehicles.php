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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Vehicles</title>
    <style>
        table {
            width: 80%;
            border-collapse: collapse;
            margin: 20px auto;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
        }
        form {
            margin: 20px auto;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        form input, form button {
            margin: 0 10px;
            padding: 10px;
            font-size: 16px;
        }
        input, select {
            padding: 10px;
            font-size: 16px;
            border-radius: 25px;
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
            display: flex;
            align-items: center; /* Ensures elements inside align properly */
            gap: 10px; /* Adds space between dropdown, input, and button */
            margin-left: auto;
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
        input:disabled {
            background-color: #f2f2f2; /* Light grey background */
            color: #666; /* Slightly dimmed text */
            border: 1px solid #ccc; /* Keep a visible border */
            cursor: not-allowed; /* Show a 'not allowed' cursor */
        }
    </style>
</head>
<body>

    <h1>Registered Vehicle Records</h1>

    <!-- Filter Form -->
    <div class="topnav">
        <div class="topnav-container">
            <button class="back-button" onclick="history.back()">Back</button>
            <div class="search-container">
                <form method="GET" action="">
                    <select name="search_field">
                        <option value="all" <?php echo $searchField === 'all' || $searchField === '' ? 'selected' : ''; ?>>Display All</option>
                        <option value="owner_name" <?php echo $searchField === 'owner_name' ? 'selected' : ''; ?>>Owner Name</option>
                        <option value="license_plate" <?php echo $searchField === 'license_plate' ? 'selected' : ''; ?>>License Plate</option>
                        <option value="phone_number" <?php echo $searchField === 'phone_number' ? 'selected' : ''; ?>>Phone Number</option>
                        <option value="owner_email" <?php echo $searchField === 'owner_email' ? 'selected' : ''; ?>>Owner Email</option>
                    </select>
                    <input type="text" name="search_query" placeholder="Enter search query" value="<?php echo htmlspecialchars($searchQuery); ?>" <?php echo $searchField === 'all' || $searchField === '' ? 'disabled' : ''; ?>>
                    <button type="submit">Search</button>
                </form>
            </div>
        </div>
    </div>
        <!-- Table to display vehicle records -->
    <table>
        <tr>
            <th>ID</th>
            <th>Owner Name</th>
            <th>Student ID</th>
            <th>Permit Type</th>
            <th>License Plate</th>
            <th>Car Brand</th>
            <th>Car Colour</th>
            <th>Phone Number</th>
            <th>Owner Email</th>
        </tr>

        <?php
        // Display the records
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["id"] . "</td>
                        <td>" . strtoupper($row["owner_name"]) . "</td>
                        <td>" . $row["student_id"] . "</td>
                        <td>" . $row["permit_type"] . "</td>
                        <td>" . $row["license_plate"] . "</td>
                        <td>" . $row["car_brand_model"] . "</td>
                        <td>" . $row["car_colour"] . "</td>
                        <td>" . $row["phone_number"] . "</td>
                        <td>" . $row["owner_email"] . "</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No records found</td></tr>";
        }
        ?>
    </table>

    <?php
    // Close the database connection
    $conn->close();
    ?>

    <script>
        // Enable or disable search input based on dropdown selection
        document.querySelector('select[name="search_field"]').addEventListener('change', function(e) {
            const searchInput = document.querySelector('input[name="search_query"]');
            searchInput.disabled = e.target.value === 'all' || e.target.value === '';
        });
    </script>

</body>
</html>
